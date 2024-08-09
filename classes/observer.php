<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Event observers used in Wooclap.
 *
 * @package    mod_wooclap
 * @copyright  2018 Cblue sprl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/wooclap/lib.php');
require_once($CFG->dirroot . '/mod/wooclap/classes/wooclap_curl.php');
require_once($CFG->dirroot . '/lib/datalib.php');

/**
 * Event observer for mod_wooclap.
 */
class mod_wooclap_observer {

    /**
     * Handler for user_loggedin
     * If a redirect parameter is set in the SESSION, redirect the user to
     * the correct URL.
     * Otherwise, let the normal auth workflow play out.
     *
     * @param \core\event\user_loggedin $event
     * @throws moodle_exception
     */
    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $CFG, $SESSION;

        if (isset($SESSION->wooclap_callback)
            && isset($SESSION->wooclap_courseid)
            && isset($SESSION->wooclap_cmid)) {
            try {
                wooclap_redirect_auth($event->userid);
            } catch (Exception $e) {
                throw new \moodle_exception($e->getMessage());
            }
        } else {
            if (isset($SESSION->wooclap_wantsurl)) {
                $url = $SESSION->wooclap_wantsurl;
                unset($SESSION->wooclap_wantsurl);
                redirect($url);
            }
        }

        // Otherwise: do nothing and let the default behaviour play out.
    }

    /**
     * @param \core\event\course_module_created $event
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     * @throws require_login_exception
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        global $CFG, $DB, $USER, $OUTPUT;

        if ($event->other['modulename'] !== 'wooclap') {
            return;
        }

        $cm = get_coursemodule_from_id('wooclap', $event->objectid);
        $wooclap = $DB->get_record('wooclap', ['id' => $cm->instance]);

        if (!is_object($wooclap)) {
            return;
        }

        // Convert the quiz to the MoodleXML format.
        if (isset($wooclap->quiz) && $wooclap->quiz > 0) {
            $questions = wooclap_get_questions_quiz($wooclap->quiz);
            $qformat = new qformat_wooclap();
            $qformat->setQuestions($questions);
            $quizfile = $qformat->exportprocess();
        }

        // Prepare data for call to the Wooclap CREATEv3 webservice.
        $trainer = $DB->get_record('user', ['id' => $USER->id]);

        $authurl = $CFG->wwwroot
        . '/mod/wooclap/auth_wooclap.php?id='
        . $event->other['instanceid']
        . '&course='
        . $event->courseid
        . '&cm='
        . $event->objectid;

        $reporturl = $CFG->wwwroot
        . '/mod/wooclap/report_wooclap_v3.php?cm='
        . $event->objectid;

        grade_update(
            'mod/wooclap',
            $event->courseid,
            'mod',
            'wooclap',
            $wooclap->id,
            0,
            null,
            ['itemname' => $wooclap->name]
        );

        $displayname = $trainer->firstname . ' ' . $trainer->lastname;
        $firstname = $trainer->firstname;
        $lastname = $trainer->lastname;

        $ts = wooclap_get_isotime();
        try {
            $accesskeyid = get_config('wooclap', 'accesskeyid');
        } catch (Exception $exc) {
            echo "<h1>Missing AccessKeyId parameter</h1>";
            echo $exc->getMessage();

            // Delete the newly created Wooclap activity.
            wooclap_delete_instance($event->other['instanceid']);
            return;
        }
        try {
            $createurl = wooclap_get_create_url();
        } catch (Exception $exc) {
            echo "<h1>Missing baseUrl parameter</h1>";
            echo $exc->getMessage();

            // Delete the newly created Wooclap activity.
            wooclap_delete_instance($event->other['instanceid']);
            return;
        }

        $courseurl = $CFG->wwwroot
        . '/course/view.php?id='
        . $event->courseid;

        $datatoken = [
            'accessKeyId' => $accesskeyid,
            'authUrl' => $authurl,
            'courseUrl' => $courseurl,
            'moodleUsername' => $trainer->username,
            'name' => $event->other['name'],
            'reportUrl' => $reporturl,
            'ts' => $ts,
            'version' => get_config('mod_wooclap')->version,
        ];

        $curldata = new StdClass;
        $curldata->name = $wooclap->name;

        $curldata->description = isset($wooclap->intro)
        ? $wooclap->intro
        : '';

        $curldata->quiz = isset($quizfile) ? $quizfile : '';
        $curldata->moodleUsername = $USER->username;
        $curldata->displayName = $displayname;
        $curldata->firstName = $firstname;
        $curldata->lastName = $lastname;
        $curldata->email = $trainer->email;
        $curldata->authUrl = $authurl;
        $curldata->courseUrl = $courseurl;
        $curldata->reportUrl = $reporturl;
        $curldata->accessKeyId = $accesskeyid;
        $curldata->ts = $ts;

        // For compatibility reason, only add wooclapeventid to the data_token
        // ...when it is actually used.
        if (isset($wooclap->wooclapeventid) && $wooclap->wooclapeventid != 'none') {
            $datatoken['wooclapeventid'] = $wooclap->wooclapeventid;
            $curldata->wooclapeventid = $wooclap->wooclapeventid;
        }

        if (isset($wooclap->linkedwooclapeventslug)) {
            $datatoken['linkedwooclapeventslug'] = $wooclap->linkedwooclapeventslug;
            $curldata->linkedwooclapeventslug = $wooclap->linkedwooclapeventslug;
        }

        $curldata->token = wooclap_generate_token(
            'CREATEv3?' . wooclap_http_build_query($datatoken)
        );
        $curldata->version = get_config('mod_wooclap')->version;

        // Call the Wooclap CREATEv3 webservice.
        $curl = new wooclap_curl();
        $headers = [];
        $headers[0] = "Content-Type: application/json";
        $headers[1] = "X-Wooclap-PluginVersion: " . get_config('mod_wooclap')->version;
        $curl->setHeader($headers);
        $response = $curl->post($createurl, json_encode($curldata));
        $curlinfo = $curl->info;

        if (!$response || !is_array($curlinfo) || $curlinfo['http_code'] !== 200) {
            // If CREATE call ends in error, delete this instance.
            wooclap_delete_instance($event->other['instanceid']);

            \core\notification::error(get_string('error-during-quiz-import', 'wooclap'));
            return;
        }

        // Update editurl for this newly created wooclap instance.
        $activity = $DB->get_record(
            'wooclap',
            ['id' => $event->other['instanceid']]
        );

        $responsedata = json_decode($response);

        $activity->editurl = $responsedata->viewUrl;
        $activity->linkedwooclapeventslug = $responsedata->wooclapEventSlug;
        $DB->update_record('wooclap', $activity);

        $role = wooclap_get_role(context_course::instance($cm->course));
        $canedit = $role == 'teacher';

        // Make a JOINv3 Wooclap API call to view Wooclap event in an iframe.
        $ts = wooclap_get_isotime();
        $datatoken = [
            'accessKeyId' => $accesskeyid,
            'authUrl' => $authurl,
            'canEdit' => $canedit,
            'courseUrl' => $courseurl,
            'moodleUsername' => $trainer->username,
            'reportUrl' => $reporturl,
            'ts' => $ts,
            'version' => get_config('mod_wooclap')->version,
            'wooclapEventSlug' => $activity->linkedwooclapeventslug,
        ];
        $token = wooclap_generate_token(
            'JOINv3?' . wooclap_http_build_query($datatoken)
        );
        $dataframe = [
            'accessKeyId' => $accesskeyid,
            'authUrl' => $authurl,
            'canEdit' => $canedit,
            'courseUrl' => $courseurl,
            'displayName' => $displayname,
            'email' => $trainer->email,
            'firstName' => $firstname,
            'lastName' => $lastname,
            'moodleUsername' => $trainer->username,
            'reportUrl' => $reporturl,
            'role' => $role,
            'token' => $token,
            'ts' => $ts,
            'version' => get_config('mod_wooclap')->version,
            'wooclapEventSlug' => $activity->linkedwooclapeventslug,
        ];

        // Do not display frame view when duplicating an activity.
        // Moodle does not expect HTML when duplicating via dropdown.
        if (!isset($wooclap->linkedwooclapeventslug)) {
            wooclap_frame_view(
                $responsedata->viewUrl . '?' . wooclap_http_build_query($dataframe),
                true
            );
        }
    }

    /**
     * Updates the gradebook item and the Wooclap event when the activity is updated (ex. when name is changed).
     * @param \core\event\course_module_updated $event
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function course_module_updated(\core\event\course_module_updated $event) {
        global $DB;

        $context = $event->get_context();

        // Get the activity from the database.
        $cm = get_coursemodule_from_id('wooclap', $event->contextinstanceid, 0, false, MUST_EXIST);
        $instance = $DB->get_record($cm->modname, array('id' => $cm->instance), '*', MUST_EXIST);

        // Update the grade item name.
        $gradeitem = $DB->get_record('grade_items', array('iteminstance' => $cm->instance, 'itemmodule' => $cm->modname), '*', MUST_EXIST);
        if ($gradeitem) {
            $gradeitem->itemname = $instance->name;
            $DB->update_record('grade_items', $gradeitem);
        }

        // Update the name within Wooclap
        self::rename_wooclap_event($instance->linkedwooclapeventslug, $instance->name);
    }

    private static function rename_wooclap_event($slug, $name) {
        $data = new StdClass;

        $data->slug = $slug;
        $data->name = $name;
        $data->accessKeyId = get_config('wooclap', 'accesskeyid');
        $data->ts = wooclap_get_isotime();
        $data->version = get_config('mod_wooclap')->version;

        $data->token = wooclap_generate_token(
            'RENAME?' . wooclap_http_build_query($data)
        );

        // Make an HTTP request to the API to request a RENAME.
        $curl = new wooclap_curl();

        $headers = [];
        $headers[0] = "Content-Type: application/json";
        $headers[1] = "X-Wooclap-PluginVersion: " . get_config('mod_wooclap')->version;
        $curl->setHeader($headers);

        try {
            $curl->post(wooclap_get_rename_url(), json_encode($data));
        } catch (Exception $e) {
            echo $e->getMessage();
            return;
        }
    }
}

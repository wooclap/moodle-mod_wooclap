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

require_once $CFG->dirroot . '/mod/wooclap/lib.php';
require_once $CFG->dirroot . '/mod/wooclap/classes/wooclap_curl.php';
require_once $CFG->dirroot . '/lib/datalib.php';

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
                throw new moodle_exception($e->getMessage());
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
        global $CFG, $DB, $USER;

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
            $questions = get_questions_quiz($wooclap->quiz);
            $qformat = new qformat_wooclap();
            $qformat->setQuestions($questions);
            $quiz_file = $qformat->exportprocess();
        }

        // Prepare data for call to the Wooclap CREATE webservice.
        $trainer = $DB->get_record('user', ['id' => $USER->id]);

        $auth_url = $CFG->wwwroot
        . '/mod/wooclap/auth_wooclap.php?id='
        . $event->other['instanceid']
        . '&course='
        . $event->courseid
        . '&cm='
        . $event->objectid;

        $report_url = $CFG->wwwroot
        . '/mod/wooclap/report_wooclap.php?cm='
        . $event->objectid;

        $displayName = $trainer->firstname . ' ' . $trainer->lastname;
        $firstName = $trainer->firstname;
        $lastName = $trainer->lastname;

        $ts = get_isotime();
        try {
            $accesskeyid = wooclap_get_accesskeyid();
        } catch (Exception $exc) {
            echo "<h1>Missing AccesKeyId parameter</h1>";
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

        $course_url = $CFG->wwwroot
        . '/course/view.php?id='
        . $event->courseid;

        $data_token = [
            'accessKeyId' => $accesskeyid,
            'authUrl' => $auth_url,
            'courseUrl' => $course_url,
            'id' => $event->other['instanceid'],
            'moodleUserId' => $trainer->id,
            'name' => $event->other['name'],
            'reportUrl' => $report_url,
            'ts' => $ts,
        ];

        $curl_data = new StdClass;
        $curl_data->id = $event->other['instanceid'];
        $curl_data->name = $wooclap->name;

        $curl_data->description = isset($wooclap->intro)
        ? $wooclap->intro
        : '';

        $curl_data->quiz = isset($quiz_file) ? $quiz_file : '';
        $curl_data->moodleUserId = intval($USER->id);
        $curl_data->displayName = $displayName;
        $curl_data->firstName = $firstName;
        $curl_data->lastName = $lastName;
        $curl_data->email = $trainer->email;
        $curl_data->authUrl = $auth_url;
        $curl_data->courseUrl = $course_url;
        $curl_data->reportUrl = $report_url;
        $curl_data->accessKeyId = $accesskeyid;
        $curl_data->ts = $ts;

        // For compatibility reason, only add wooclapeventid to the data_token
        // ...when it is actually used.
        if (isset($wooclap->wooclapeventid)) {
            $data_token['wooclapeventid'] = $wooclap->wooclapeventid;
            $curl_data->wooclapeventid = $wooclap->wooclapeventid;
        }

        $curl_data->token = wooclap_generate_token(
            'CREATE?' . wooclap_http_build_query($data_token)
        );

        // Call the Wooclap CREATE webservice.
        $curl = new wooclap_curl();
        $headers = [];
        $headers[] = "Content-Type: application/json";
        $curl->setHeader($headers);
        $response = $curl->post($createurl, json_encode($curl_data));
        $curlinfo = $curl->info;

        if (!$response || !is_array($curlinfo) || $curlinfo['http_code'] !== 200) {
            echo "<h1>Error during JOIN Wooclap API call</h1>";
            // If CREATE call ends in error, delete this instance.
            wooclap_delete_instance($event->other['instanceid']);
            return;
        }

        // Update editurl for this newly created wooclap instance.
        $activity = $DB->get_record(
            'wooclap',
            ['id' => $event->other['instanceid']]
        );
        $activity->editurl = $response;
        $DB->update_record('wooclap', $activity);

        // Make a JOIN Wooclap API call to view Wooclap event in an iframe.
        $ts = get_isotime();
        $data_token = [
            'accessKeyId' => $accesskeyid,
            'id' => $activity->id,
            'moodleUserId' => $trainer->id,
            'ts' => $ts,
        ];
        $token = wooclap_generate_token(
            'JOIN?' . wooclap_http_build_query($data_token)
        );
        $data_frame = [
            'id' => $activity->id,
            'moodleUserId' => $trainer->id,
            'displayName' => $displayName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $trainer->email,
            // The role can be hardcoded as only teachers can create courses.
            'role' => 'teacher',
            'accessKeyId' => $accesskeyid,
            'ts' => $ts,
            'token' => $token,
        ];

        wooclap_frame_view(
            $response . '?' . wooclap_http_build_query($data_frame)
        );
    }
}

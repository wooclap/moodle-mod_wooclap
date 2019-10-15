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
//

/**
 * This file contains a library of functions and constants for the wooclap module
 *
 * @package mod_wooclap
 * @copyright  2018 CBlue sprl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once $CFG->dirroot . '/mod/wooclap/classes/wooclap_curl.php';
require_once $CFG->dirroot . '/question/editlib.php';
require_once $CFG->dirroot . '/question/export_form.php';
require_once $CFG->dirroot . '/question/format.php';
require_once $CFG->dirroot . '/question/format/xml/format.php';
require_once $CFG->dirroot . '/mod/wooclap/format.php';

/**
 * @param $feature
 * @return bool|null
 */
function wooclap_supports($feature) {
    switch ($feature) {
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GROUPINGS:
        case FEATURE_GROUPS:
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_GRADE_OUTCOMES:
            return false;

        default:
            return null;
    }
}

/**
 * @param int $id
 * @return bool
 * @throws dml_exception
 */
function wooclap_delete_instance($id) {
    global $DB;

    if (!$wooclap = $DB->get_record('wooclap', ['id' => $id])) {
        return false;
    }

    // Note: all context files are deleted automatically.

    $DB->delete_records('wooclap', ['id' => $id]);
    $DB->delete_records('wooclap_completion', ['wooclapid' => $id]);

    grade_update('mod/wooclap', $wooclap->course, 'mod', 'wooclap', $id, 0, null, ['deleted' => 1]);

    return true;
}

/**
 * @param $data
 * @return bool
 * @throws dml_exception
 */
function wooclap_update_instance($data) {
    global $DB;

    if (!isset($data->update)) {
        return false;
    }

    $cm = $DB->get_record('course_modules', ['id' => $data->update]);

    $wooclap = $DB->get_record('wooclap', ['id' => $cm->instance]);

    $activity = new StdClass;
    $activity->id = $wooclap->id;
    $activity->course = $data->course;
    $activity->name = $data->name;
    $activity->intro = $data->intro;
    $activity->introformat = $data->introformat;
    $activity->editurl = $wooclap->editurl;
    $activity->quiz = $data->quiz;
    $activity->timecreated = $wooclap->timecreated;
    $activity->timemodified = time();
    $activity->wooclapeventid = $data->wooclapeventid;
    $DB->update_record('wooclap', $activity);

    wooclap_grade_item_update($wooclap);

    return true;
}

/**
 * @param $data
 * @return bool|int
 * @throws dml_exception
 */
function wooclap_add_instance($data) {
    global $DB, $USER;

    $activity = new StdClass;
    $activity->course = $data->course;
    $activity->name = $data->name;
    $activity->intro = $data->intro;
    $activity->introformat = $data->introformat;
    // Fill editurl later with curl response from observer::course_module_created.
    $activity->editurl = '';
    $activity->quiz = $data->quiz;
    $activity->authorid = $USER->id;
    $activity->timecreated = time();
    $activity->timemodified = $activity->timecreated;
    $activity->wooclapeventid = $data->wooclapeventid;
    $activity->id = $DB->insert_record('wooclap', $activity);

    return $activity->id;
}

/**
 * @param int $id
 * @return mixed
 * @throws Exception
 */
function wooclap_get_instance($id) {
    global $DB;
    try {
        return $DB->get_record('wooclap', ['id' => $id], '*', MUST_EXIST);
    } catch (Exception $e) {
        throw new Exception('This wooclap instance does not exist!');
    }
}

/**
 * @return string
 */
function wooclap_get_create_url() {
    $baseurl = get_config('wooclap', 'baseurl');
    $hastrailingslash = substr($baseurl, -1) === '/';
    return $baseurl . ($hastrailingslash ? '' : '/') . 'api/moodle/events';
}

/**
 * @return string
 */
function wooclap_get_events_list_url() {
    $baseurl = get_config('wooclap', 'baseurl');
    $hastrailingslash = substr($baseurl, -1) === '/';
    return $baseurl . ($hastrailingslash ? '' : '/') . 'api/moodle/events_list';
}

/**
 * @return string
 */
function wooclap_get_ping_url() {
    $baseurl = get_config('wooclap', 'baseurl');
    $hastrailingslash = substr($baseurl, -1) === '/';
    return $baseurl . ($hastrailingslash ? '' : '/') . 'api/moodle/ping';
}

/**
 * @param $data
 * @return string
 * @throws Exception
 */
function wooclap_generate_token($data) {
    return hash_hmac('sha256', $data, get_config('wooclap', 'secretaccesskey'));
}

/**
 * @param $data
 * @return string
 */
function wooclap_http_build_query($data) {
    return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
}

/**
 * @param int $courseid
 * @param int $cmid
 * @param int $userid
 * @return bool
 * @throws moodle_exception
 */
function wooclap_check_activity_user_access($courseid, $cmid, $userid) {
    try {
        $modinfo = get_fast_modinfo($courseid, $userid);
        $cm = $modinfo->get_cm($cmid);
    } catch (Exception $e) {
        throw new moodle_exception($e->getMessage());
    }
    if (isset($cm) && $cm->uservisible == true) {
        return true;
    }

    return false;
}

/**
 * @param $userid
 * @throws moodle_exception
 */
function wooclap_redirect_auth($userid) {
    global $DB, $SESSION;

    if (!isset($SESSION->wooclap_courseid) || !isset($SESSION->wooclap_cmid) || !isset($SESSION->wooclap_callback)) {
        print_error('error-missingparameters', 'wooclap');
        header("HTTP/1.0 401");
    }

    try {
        $cm = get_coursemodule_from_id('wooclap', $SESSION->wooclap_cmid);
        $course_context = context_course::instance($cm->course);
        $userdb = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $activity = $DB->get_record('wooclap', ['id' => $cm->instance], '*', MUST_EXIST);
        $accesskeyid = get_config('wooclap', 'accesskeyid');
    } catch (Exception $e) {
        print_error('error-couldnotauth', 'wooclap');
    }

    $role = wooclap_get_role($course_context);
    $ts = get_isotime();
    $hasAccess = wooclap_check_activity_user_access($SESSION->wooclap_courseid, $SESSION->wooclap_cmid, $userid);

    $data_token = [
        'accessKeyId' => $accesskeyid,
        'hasAccess' => $hasAccess,
        'id' => $activity->id,
        'moodleUserId' => $userdb->id,
        'role' => $role,
        'ts' => $ts,
    ];

    $data = [
        'id' => $activity->id,
        'moodleUserId' => $userdb->id,
        'displayName' => $userdb->firstname . ' ' . $userdb->lastname,
        'firstName' => $userdb->firstname,
        'lastName' => $userdb->lastname,
        'email' => $userdb->email,
        'role' => $role,
        'hasAccess' => $hasAccess,
        'accessKeyId' => $accesskeyid,
        'ts' => $ts,
        'token' => wooclap_generate_token('AUTH?' . wooclap_http_build_query($data_token)),
    ];

    $callback_url = wooclap_validate_callback_url($SESSION->wooclap_callback);

    redirect($callback_url . '?' . wooclap_http_build_query($data));
}

/**
 * Perform a PING with the Wooclap server based on the plugin settings
 * @throws moodle_exception
 * @return bool true if the current settings are accepted by Wooclap
 */
function get_ping_status() {
    // Generate a token based on necessary parameters.

    $ts = get_isotime();

    try {
        $accesskeyid = get_config('wooclap', 'accesskeyid');
    } catch (Exception $e) {
        // Could not get access key id => ping can be considered failed.
        return false;
    }

    $data_token = [
        'accessKeyId' => $accesskeyid,
        'ts' => $ts,
    ];
    $data = [
        'accessKeyId' => $accesskeyid,
        'ts' => $ts,
        'token' => wooclap_generate_token('PING?' . wooclap_http_build_query($data_token)),
    ];

    $ping_url = wooclap_get_ping_url();

    // Use curl to make a call and check the result.

    $curl = new wooclap_curl();
    $headers = [];
    $headers[0] = "Content-Type: application/json";
    $headers[1] ="X-Wooclap-PluginVersion: " . get_config('mod_wooclap')->version;
    $curl->setHeader($headers);
    $response = $curl->get(
        $ping_url . '?' . wooclap_http_build_query($data)
    );
    $curlinfo = $curl->info;

    if (!$response || !is_array($curlinfo) || $curlinfo['http_code'] != 200) {
        return false;
    }

    $response_data = json_decode($response);
    return $response_data->keysAreValid;
}

/**
 * @param $course_context
 * @return string
 */
function wooclap_get_role($course_context) {
    if ($course_context and has_capability('moodle/course:update', $course_context)) {
        $role = 'teacher';
    } else {
        $role = 'student';
    }
    return $role;
}

/**
 * @param string $callback_url
 * @return string
 */
function wooclap_validate_callback_url($callback_url) {
    if (strpos($callback_url, 'https://') === false) {
        $callback_url = 'https://' . $callback_url;
    }
    if (!filter_var($callback_url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
        print_error('error-callback-is-not-url', 'wooclap');
    }
    return $callback_url;
}

/**
 * @param $wooclapinstance
 * @param $userid
 * @param $gradeval
 * @param $completionstatus
 * @return bool
 * @throws dml_exception
 */
function wooclap_update_grade($wooclapinstance, $userid, $gradeval, $completionstatus) {
    global $CFG, $DB;
    require_once $CFG->libdir . '/gradelib.php';

    $grade = new stdClass();
    $grade->userid = $userid;
    $grade->rawgrade = $gradeval;

    $status = grade_update(
        'mod/wooclap',
        $wooclapinstance->course,
        'mod',
        'wooclap',
        $wooclapinstance->id,
        0,
        $grade,
        ['itemname' => $wooclapinstance->name]
    );

    $record = $DB->get_record('wooclap_completion', ['wooclapid' => $wooclapinstance->id, 'userid' => $userid], 'id');
    if ($record) {
        $id = $record->id;
    } else {
        $id = null;
    }
    $time = time();

    if (!empty($id)) {
        $DB->update_record('wooclap_completion', [
            'id' => $id,
            'timemodified' => $time,
            'grade' => $gradeval,
            'completionstatus' => $completionstatus,
        ]);
    } else {
        $DB->insert_record('wooclap_completion', [
            'wooclapid' => $wooclapinstance->id,
            'userid' => $userid,
            'timecreated' => $time,
            'timemodified' => $time,
            'grade' => $gradeval,
            'completionstatus' => $completionstatus,
        ]);
    }

    return $status == GRADE_UPDATE_OK;
}

/**
 * @return string
 */
function get_isotime() {
    $date = new datetime();
    $date->setTimezone(new DateTimeZone('Etc/Zulu'));
    return $date->format('Y-m-d\TH:i:s\Z');
}

/**
 * @param string $src
 */
function wooclap_frame_view($src) {
    echo '<iframe
            id="contentframe"
            height="100%" width="100%"
            style="margin: 0; padding: 0; border: 0; position: fixed; top: 0; left: 0;"
            src="' . $src . '"
            webkitallowfullscreen mozallowfullscreen allowfullscreen
          >
          </iframe>';
}

/**
 * @param $course
 * @param $cm
 * @param $userid
 * @param $type
 * @return bool
 * @throws dml_exception
 */
function wooclap_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    $wooclap = $DB->get_record('wooclap', array('id' => $cm->instance), '*',
        MUST_EXIST);

    $result = $type; // Default return value.
    // If completion option is enabled, evaluate it and return true/false.
    if ($wooclap->customcompletion) {
        $value = $DB->record_exists('wooclap_completion', array(
            'wooclapid' => $wooclap->id, 'userid' => $userid, 'completionstatus' => 2));
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }
    return $result;
}

/**
 * Create/update grade item for given wooclap activity
 *
 * @param $wooclapinstance
 * @param null $grades
 * @return int
 */
function wooclap_grade_item_update($wooclapinstance, $grades = null) {
    global $CFG;
    if (!function_exists('grade_update')) {
        // We use a workaround for buggy PHP versions.
        require_once $CFG->libdir . '/gradelib.php';
    }
    return grade_update(
        'mod/wooclap',
        $wooclapinstance->course,
        'mod',
        'wooclap',
        $wooclapinstance->id,
        0,
        $grades,
        ['itemname' => $wooclapinstance->name]
    );
}

/**
 * Add a get_coursemodule_info function in case to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
// See: https://github.com/moodle/moodle/blob/master/mod/lesson/lib.php#L1693
// See: https://github.com/moodle/moodle/blob/master/mod/resource/lib.php#L207
// See: https://docs.moodle.org/dev/Module_visibility_and_display for more info.
function wooclap_get_coursemodule_info($cm) {
    global $CFG;

    $info = new cached_cm_info();

    $fullurl = "$CFG->wwwroot/mod/wooclap/view.php?id=$cm->id&amp;redirect=1";
    $info->onclick = "window.open('$fullurl'); return false;";

    return $info;
}

/**
 * Function to read all questions for quiz into big array
 *
 * @param int $quiz quiz id
 */
function get_questions_quiz($quiz, $export = true) {
    global $DB;

    // Fetch the quiz slots.
    $quiz_slots = $DB->get_records('quiz_slots', ['quizid' => $quiz]);
    // Create an array with all the question ids.
    $question_ids = array_map(
        function ($elem) {
            return $elem->questionid;
        },
        $quiz_slots
    );
    // Get the list of questions for the quiz.
    $questions = $DB->get_records_list('question', 'id', $question_ids);

    // Iterate through questions, getting stuff we need.
    $qresults = array();

    foreach ($questions as $key => $question) {
        $question->export_process = $export;
        $qtype = question_bank::get_qtype($question->qtype, false);
        if ($export && $qtype->name() == 'missingtype') {
            // Unrecognised question type. Skip this question when exporting.
            continue;
        }
        $qtype->get_question_options($question);
        $qresults[] = $question;
    }

    return $qresults;
}

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
 * This file contains a library of functions and constants for the wooclap module
 *
 * @package   mod_wooclap
 * @copyright 2018 CBlue sprl
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @see https://github.com/moodle/moodle/blob/master/mod/lesson/lib.php#L1693
 * @see https://github.com/moodle/moodle/blob/master/mod/resource/lib.php#L207
 * @see https://docs.moodle.org/dev/Module_visibility_and_display for more info.
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/mod/wooclap/classes/wooclap_curl.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/wooclap/format.php');

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function wooclap_supports($feature) {
    switch ($feature) {
        case FEATURE_BACKUP_MOODLE2:
            // Activity has custom completion rules.
        case FEATURE_COMPLETION_HAS_RULES:
            // Activity provides a grade for students.
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GROUPINGS:
        case FEATURE_GROUPS:
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        // Marked complete as soon as a user clicks on it.
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_GRADE_OUTCOMES:
            return false;

        default:
            return null;
    }
}

/**
 * Removes a wooclap instance from the database.
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
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
 * Updates an instance of the wooclap activity in the database.
 *
 * @param  object $wooclap The wooclap activity.
 * @return bool
 * @throws dml_exception
 */
function wooclap_update_instance($wooclap) {
    global $DB;

    if (!isset($wooclap->update)) {
        return false;
    }

    $wooclap->id = $wooclap->instance;
    $wooclap->timemodified = time();
    $DB->update_record('wooclap', $wooclap);

    wooclap_grade_item_update($wooclap);

    return true;
}

/**
 * Add a new instance of the wooclap activity to the database.
 *
 * @param object $wooclap The wooclap activity.
 * @return bool|int
 * @throws dml_exception
 */
function wooclap_add_instance($wooclap) {
    global $DB, $USER;

    // Fill editurl later with curl response from observer::course_module_created.
    $wooclap->editurl = '';
    $wooclap->authorid = $USER->id;
    $wooclap->timecreated = time();
    $wooclap->timemodified = $wooclap->timecreated;

    $wooclap->id = $DB->insert_record('wooclap', $wooclap);
    wooclap_grade_item_update($wooclap);

    return $wooclap->id;
}

/**
 * Get the course module instance.
 *
 * @param  int $id
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
 * Get the create URL.
 *
 * @return string
 */
function wooclap_get_create_url() {
    $baseurl = get_config('wooclap', 'baseurl');
    $hastrailingslash = substr($baseurl, -1) === '/';
    return $baseurl . ($hastrailingslash ? '' : '/') . 'api/moodle/v3/events';
}

/**
 * Get the events list URL.
 *
 * @return string
 */
function wooclap_get_events_list_url() {
    $baseurl = get_config('wooclap', 'baseurl');
    $hastrailingslash = substr($baseurl, -1) === '/';
    return $baseurl . ($hastrailingslash ? '' : '/') . 'api/moodle/v3/events_list';
}

/**
 * Get the ping URL.
 *
 * @return string
 */
function wooclap_get_ping_url() {
    $baseurl = get_config('wooclap', 'baseurl');
    $hastrailingslash = substr($baseurl, -1) === '/';
    return $baseurl . ($hastrailingslash ? '' : '/') . 'api/moodle/v3/ping';
}

/**
 * Get the rename event URL.
 *
 * @return string
 */
function wooclap_get_rename_url() {
    $baseurl = get_config('wooclap', 'baseurl');
    $hastrailingslash = substr($baseurl, -1) === '/';
    return $baseurl . ($hastrailingslash ? '' : '/') . 'api/integration/moodle-plugin/rename-event';
}

/**
 * Generate a token based on the data and the secret access key.
 *
 * @param  string $data Data to be hashed.
 * @return string
 * @throws Exception
 */
function wooclap_generate_token($data) {
    return hash_hmac('sha256', $data, get_config('wooclap', 'secretaccesskey'));
}

/**
 * Build a query string from an array.
 *
 * @param  array|object $data May be an array or object containing properties.
 * @return string
 */
function wooclap_http_build_query($data) {
    return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
}

/**
 * Check if the user has access to the activity.
 *
 * @param  int $courseid The course id
 * @param  int $cmid The course module id.
 * @param  int $userid The user id.
 * @return bool Whether the user has access to the activity or not.
 * @throws moodle_exception
 */
function wooclap_check_activity_user_access($courseid, $cmid, $userid) {
    try {
        $modinfo = get_fast_modinfo($courseid, $userid);
        $cm = $modinfo->get_cm($cmid);
    } catch (Exception $e) {
        throw new \moodle_exception($e->getMessage());
    }
    if (isset($cm) && $cm->uservisible == true) {
        return true;
    }

    return false;
}

/**
 * Redirect the user to the Wooclap platform.
 *
 * @param int $userid The user id.
 * @return void
 * @throws dml_exception
 * @throws moodle_exception
 */
function wooclap_redirect_auth($userid) {
    global $DB, $SESSION;

    wooclap_ask_consent_if_not_given();

    if (!wooclap_is_valid_callback_url($SESSION->wooclap_callback)) {
        throw new \moodle_exception('error-invalid-callback-url', 'wooclap');
    }

    if (!isset($SESSION->wooclap_courseid) || !isset($SESSION->wooclap_cmid) || !isset($SESSION->wooclap_callback)) {
        throw new \moodle_exception('error-missingparameters', 'wooclap');
        header("HTTP/1.0 401");
    }

    try {
        $cm = get_coursemodule_from_id('wooclap', $SESSION->wooclap_cmid);
        $coursecontext = context_course::instance($cm->course);
        $userdb = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $wooclap = $DB->get_record('wooclap', ['id' => $cm->instance], '*', MUST_EXIST);
        $accesskeyid = get_config('wooclap', 'accesskeyid');
    } catch (Exception $e) {
        throw new \moodle_exception('error-couldnotauth', 'wooclap');
    }

    $role = wooclap_get_role($coursecontext);
    $ts = wooclap_get_isotime();
    $hasaccess = wooclap_check_activity_user_access($SESSION->wooclap_courseid, $SESSION->wooclap_cmid, $userid);

    $datatoken = [
        'accessKeyId' => $accesskeyid,
        'hasAccess' => $hasaccess,
        'moodleUsername' => $userdb->username,
        'role' => $role,
        'ts' => $ts,
        'version' => get_config('mod_wooclap')->version,
        'wooclapEventSlug' => $wooclap->linkedwooclapeventslug,
    ];

    $data = [
        'moodleUsername' => $userdb->username,
        'displayName' => $userdb->firstname . ' ' . $userdb->lastname,
        'firstName' => $userdb->firstname,
        'lastName' => $userdb->lastname,
        // Only add the email if the user has consented.
        'email' => $SESSION->hasConsented ? $userdb->email : '',
        'role' => $role,
        'hasAccess' => $hasaccess,
        'accessKeyId' => $accesskeyid,
        'ts' => $ts,
        'token' => wooclap_generate_token('AUTHv3?' . wooclap_http_build_query($datatoken)),
        'version' => get_config('mod_wooclap')->version,
        'wooclapEventSlug' => $wooclap->linkedwooclapeventslug,
    ];

    $callbackurl = wooclap_validate_callback_url($SESSION->wooclap_callback);

    $callbackurl .= (parse_url($callbackurl, PHP_URL_QUERY) ? '&' : '?') . wooclap_http_build_query($data);

    redirect($callbackurl);
}

/**
 * Ask for consent if not given.
 *
 * @param string $redirecturl The URL to redirect to after the consent screen.
 * @param string $role The role of the user.
 * @return void
 * @throws dml_exception
 * @throws moodle_exception
 */
function wooclap_ask_consent_if_not_given($redirecturl = null, $role = null) {
    global $CFG, $DB, $SESSION;

    $showconsentscreen = get_config('wooclap', 'showconsentscreen');

    // Consider that consent has been obtained otherwise if the consent screen
    // is not shown or if it's a teacher.
    if (!$showconsentscreen || $role == 'teacher') {
        $SESSION->hasConsented = true;
    }

    // If the user has not consented yet, redirect them to the consent screen.
    if (!isset($SESSION->hasConsented)) {
        redirect(
            new moodle_url(
                '/mod/wooclap/wooclap_consent_screen.php',
                ['redirectUrl' => $redirecturl]
            )
        );
    }
}

/**
 * Perform a PING with the Wooclap server based on the plugin settings
 *
 * @throws moodle_exception
 * @return bool true if the current settings are accepted by Wooclap
 */
function wooclap_get_ping_status() {
    // Generate a token based on necessary parameters.

    $ts = wooclap_get_isotime();

    try {
        $accesskeyid = get_config('wooclap', 'accesskeyid');
    } catch (Exception $e) {
        // Could not get access key id => ping can be considered failed.
        return false;
    }

    $datatoken = [
        'accessKeyId' => $accesskeyid,
        'ts' => $ts,
        'version' => get_config('mod_wooclap')->version,
    ];
    $data = [
        'accessKeyId' => $accesskeyid,
        'ts' => $ts,
        'token' => wooclap_generate_token('PING?' . wooclap_http_build_query($datatoken)),
        'version' => get_config('mod_wooclap')->version,
    ];

    $pingurl = wooclap_get_ping_url();

    // Use curl to make a call and check the result.

    $curl = new wooclap_curl();
    $headers = [];
    $headers[0] = "Content-Type: application/json";
    $headers[1] = "X-Wooclap-PluginVersion: " . get_config('mod_wooclap')->version;
    $curl->setHeader($headers);
    $response = $curl->get(
        $pingurl . '?' . wooclap_http_build_query($data)
    );
    $curlinfo = $curl->info;

    if (!$response || !is_array($curlinfo) || $curlinfo['http_code'] != 200) {
        return false;
    }

    $responsedata = json_decode($response);
    return $responsedata->keysAreValid;
}

/**
 * Get the role of the user in the course
 *
 * @param  context_course $coursecontext The course context
 * @return string
 */
function wooclap_get_role($coursecontext) {
    if ($coursecontext && has_capability('moodle/course:update', $coursecontext)) {
        $role = 'teacher';
    } else {
        $role = 'student';
    }
    return $role;
}

/**
 * Validate the callback URL.
 *
 * @param string $callbackurl The callback URL to validate
 * @return mixed|string
 * @throws moodle_exception
 */
function wooclap_validate_callback_url($callbackurl) {
    if (strpos($callbackurl, 'https://') === false) {
        $callbackurl = 'https://' . $callbackurl;
    }
    if (!filter_var($callbackurl, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
        throw new \moodle_exception('error-callback-is-not-url', 'wooclap');
    }
    return $callbackurl;
}

/**
 * Update mod_wooclap grades in the gradebook.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int $userid Update grade of specific user only, 0 means all participants.
 */
function wooclap_update_grades(stdClass $moduleinstance, int $userid = 0): void {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    if ($moduleinstance->grade == 0) {
        wooclap_grade_item_update($moduleinstance);
    } else if ($grades = wooclap_get_user_grades($moduleinstance, $userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        wooclap_grade_item_update($moduleinstance, $grades);
    } else {
        wooclap_grade_item_update($moduleinstance);
    }
}

/**
 * Update a wooclap grade.
 *
 * @param object $wooclap The wooclap activity.
 * @param int $userid The user id.
 * @param float $gradeval The grade value.
 * @param int $completionstatus The completion status.
 * Needed by {@see \grade_update_mod_grades()}.
 * @return bool
 * @throws dml_exception
 */
function wooclap_update_grade($wooclap, $userid, $gradeval, $completionstatus) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    $grade = new stdClass();
    $grade->userid = $userid;

    // Depending on the maximum grade value, we should adapt the grade
    // Wooclap grades are based on 100.

    // 1 - trying to fetch the max grade from the course itself
    $params = [
        'courseid' => $wooclap->course,
        'itemtype' => 'mod',
        'itemmodule' => 'wooclap',
        'iteminstance' => $wooclap->id,
        'itemnumber' => 0,
    ];
    if ($gradeitem = grade_item::fetch($params)) {
        $maxgrade = $gradeitem->grademax;
    }

    // 2 - if nothing defined, trying from the global configuration
    if (!$maxgrade) {
        $maxgrade = (int)get_config('core', 'gradepointdefault');
    }

    // 3 - else hardcode to 100
    if (!$maxgrade) {
        $maxgrade = 100;
    }

    $grade->rawgrade = ($gradeval * $maxgrade) / 100;

    $status = grade_update(
        'mod/wooclap',
        $wooclap->course,
        'mod',
        'wooclap',
        $wooclap->id,
        0,
        $grade,
        ['itemname' => $wooclap->name]
    );

    $record = $DB->get_record('wooclap_completion', ['wooclapid' => $wooclap->id, 'userid' => $userid], 'id');
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
            'wooclapid' => $wooclap->id,
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
 * Get the current time in ISO format.
 *
 * @return string
 */
function wooclap_get_isotime() {
    $date = new \DateTime("now", new \DateTimeZone("UTC"));
    return $date->format('Y-m-d\TH:i:s\Z');
}

/**
 * Show the Wooclap iframe.
 *
 * @param string $src The Wooclap link will be shown inside the iframe block
 * @param bool $nohtmlblock If true, it will show the content without the HTML
 *  block. Only the iframe. This value is usually defined as true by the
 *  observer.php:course_module_created method.
 *
 *  We have noticed that in some Moodle instances (e.g our Bitnami staging
 *  environment), the teacher is not redirected to the activity URL when clicking
 *  on "Save and display". Instead, they stay on "/course/modedit.php" and the
 *  iframe is injected onto that page.
 *
 *  To avoid having multiple <html /> element on the page, we have to add this
 *  parameter.
 * @return void
 */
function wooclap_frame_view($src, $nohtmlblock=false) {

    $iframe = '<iframe
            id="contentframe"
            height="100%" width="100%"
            style="margin: 0; padding: 0; border: 0; position: fixed; top: 0; left: 0;"
            src="' . $src . '"
            webkitallowfullscreen mozallowfullscreen allowfullscreen
          >
          </iframe>';

    if ($nohtmlblock) {
        echo $iframe;
    } else {
        echo '<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Wooclap</title>
</head>
<body>
' . $iframe . '
  </body>
  </html>';
    }
}

/**
 * Obtains the automatic completion state for this quizgame based on any conditions
 * in quizgame settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type COMPLETION_AND (true) or COMPLETION_OR (false)
 * - COMPLETION_AND: if multiple conditions are selected, the user must meet all of them.
 * - COMPLETION_OR: if multiple conditions are selected, any one of them is good enough to complete the activity.
 * @return bool - whether the user has completed the activity or not.
 * @throws dml_exception
 */
function wooclap_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    $wooclap = $DB->get_record('wooclap', ['id' => $cm->instance], '*', MUST_EXIST);

    if ($wooclap->customcompletion) {
        // Find the completion record for this user and this activity.
        // Any grade means they participated, so they get activity completion.
        return $DB->record_exists('wooclap_completion', ['wooclapid' => $wooclap->id, 'userid' => $userid]);
    }

    return $type;
}

/**
 * Create/update grade item for given wooclap activity
 *
 * @param object $wooclap The wooclap activity.
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int
 */
function wooclap_grade_item_update($wooclap, $grades = null) {
    global $CFG;
    if (!function_exists('grade_update')) {
        // We use a workaround for buggy PHP versions.
        require_once($CFG->libdir . '/gradelib.php');
    }
    if (!isset($wooclap->courseid)) {
        $wooclap->courseid = $wooclap->course;
    }

    $params = ['itemname' => $wooclap->name];

    if ($wooclap->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $wooclap->grade;
        $params['grademin']  = 0;
    } else if ($wooclap->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$wooclap->grade;
    } else {
        $params['gradetype'] = GRADE_TYPE_TEXT;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update(
        'mod/wooclap',
        $wooclap->course,
        'mod',
        'wooclap',
        $wooclap->id,
        0,
        $grades,
        $params
    );
}

/**
 * Add a get_coursemodule_info function in case to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param  stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 *
 * @see https://github.com/wooclap/moodle-mod_wooclap/issues/1#issuecomment-957577514
 */
function wooclap_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat';
    if (!$wooclap = $DB->get_record('wooclap', $dbparams, $fields)) {
        return false;
    }

    $info = new cached_cm_info();
    $info->name = $wooclap->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('wooclap', $wooclap, $coursemodule->id, false);
    }

    $url = new moodle_url('/mod/wooclap/view.php', ['id' => $coursemodule->id, 'redirect' => 1]);
    $fullurl = $url->out();
    $info->onclick = "window.open('$fullurl'); return false;";

    return $info;
}

/**
 * Get questions from database schema before the Moodle V4 version
 * Warning: From V4 version, questions table structure has changed
 *
 * @param int $quizid The quiz id
 * @return array List of questions from a quiz
 * @throws dml_exception
 */
function wooclap_load_questions_before_v4($quizid) {
    global $DB;

    // Fetch the quiz slots.
    $quizslots = $DB->get_records('quiz_slots', ['quizid' => $quizid]);
    // Create an array with all the question ids.
    $questionids = array_map(
        function ($elem) {
            return $elem->questionid;
        },
        $quizslots
    );
    // Get the list of questions for the quiz.
    $questions = $DB->get_records_list('question', 'id', $questionids);

    return $questions;
}

/**
 * Get questions from database schema after the Moodle V4 version
 * Warning: From V4 version, questions table structure has changed
 *
 * @param int $quizid The quiz id
 * @return array List of questions from a quiz
 * @throws dml_exception
 */
function wooclap_load_questions_for_v4($quizid) {
    global $DB;

    $questions = $DB->get_records_sql('
                        SELECT
                            q.*,
                            qbe.questioncategoryid AS category
                        FROM
                            {quiz_slots} qs
                                INNER JOIN {question_references} qr
                                    ON  qs.id = qr.itemid
                                    AND qr.component = :component
                                    AND qr.questionarea = :questionarea
                                INNER JOIN {question_bank_entries} qbe
                                    ON qr.questionbankentryid = qbe.id
                                INNER JOIN {question_versions} qv
                                    ON qbe.id = qv.questionbankentryid
                                    AND qv.version  = (
                                                        SELECT MAX(version)
                                                        FROM {question_versions}
                                                        WHERE  questionbankentryid = qv.questionbankentryid
                                                    )
                                INNER JOIN {question} q
                                    ON qv.questionid = q.id
                        WHERE
                            qs.quizid = :quizid',
                        [
                            'component' => 'mod_quiz',
                            'questionarea' => 'slot',
                            'quizid' => $quizid,
                        ]
                    );
    return $questions;
}

/**
 * Function to read all questions for quiz into big array
 *
 * @param int $quiz The quiz id
 * @param bool $export Whether to export the questions or not
 * @return array
 * @throws coding_exception
 * @throws dml_exception
 */
function wooclap_get_questions_quiz($quiz, $export = true) {

    $branch = get_config('moodle', 'branch');
    $questions = null;

    // Get the list of questions for the quiz.
    if (strnatcmp($branch, '400') == -1) {
        // When Moodle version is < v4.
        $questions = wooclap_load_questions_before_v4($quiz);
    } else {
        // When Moodle version is >= v4.
        $questions = wooclap_load_questions_for_v4($quiz);
    }

    // Iterate through questions, getting stuff we need.
    $qresults = [];

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

/**
 * Check if the callback url is safe and known
 *
 * @param  string $callbackurl The callback url to check
 * @return bool
 */
function wooclap_is_valid_callback_url($callbackurl) {
    $baseurl = trim(get_config('wooclap', 'baseurl'), '/');
    return $callbackurl != null && strpos($callbackurl, $baseurl) === 0;
}

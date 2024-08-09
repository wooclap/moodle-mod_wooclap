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
 * @package mod_wooclap
 * @copyright  2018 CBlue sprl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/mod/wooclap/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or.
$wid = optional_param('w', 0, PARAM_INT); // Wooclap ID.

if (isset($wid) && $wid > 0) {
    // Two ways to specify the module.
    $wooclap = $DB->get_record('wooclap', ['id' => $wid], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('wooclap', $wooclap->id, $wooclap->course, false, MUST_EXIST);
} else if (isset($id) && $id > 0) {
    $cm = get_coursemodule_from_id('wooclap', $id, 0, false, MUST_EXIST);
    $wooclap = $DB->get_record('wooclap', ['id' => $cm->instance], '*', MUST_EXIST);
}

if (is_object($cm) && is_object($wooclap)) {
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

    $PAGE->set_cm($cm, $course); // Set's up global $COURSE.
    $context = context_module::instance($cm->id);
    $PAGE->set_context($context);

    require_login($course, false, $cm);
    require_capability('mod/wooclap:view', $context);

    // Add event management here.
    $event = \mod_wooclap\event\course_module_viewed::create(array(
        'objectid' => $wooclap->id,
        'context' => $context,
    ));
    $event->add_record_snapshot('course', $PAGE->course);
    $event->add_record_snapshot($cm->modname, $wooclap);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    $url = new moodle_url('/mod/wooclap/view.php', ['id' => $cm->id]);
    $PAGE->set_url($url);

    // View Wooclap edit form in a frame.
    if (isset($USER)) {
        $ts = wooclap_get_isotime();
        try {
            $accesskeyid = get_config('wooclap', 'accesskeyid');
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $role = wooclap_get_role(context_course::instance($cm->course));
        $canedit = $role == 'teacher';

        wooclap_ask_consent_if_not_given($PAGE->url, $role);

        $authurl = $CFG->wwwroot
        . '/mod/wooclap/auth_wooclap.php?id='
        . $wooclap->id
        . '&course='
        . $cm->course
        . '&cm='
        . $cm->id;

        $reporturl = $CFG->wwwroot
        . '/mod/wooclap/report_wooclap_v3.php?cm='
        . $cm->id;

        $courseurl = $CFG->wwwroot
        . '/course/view.php?id='
        . $cm->course;

        $datatoken = [
            'accessKeyId' => $accesskeyid,
            'authUrl' => $authurl,
            'canEdit' => $canedit,
            'courseUrl' => $courseurl,
            'moodleUsername' => $USER->username,
            'reportUrl' => $reporturl,
            'ts' => $ts,
            'version' => get_config('mod_wooclap')->version,
            'wooclapEventSlug' => $wooclap->linkedwooclapeventslug,
        ];
        $token = wooclap_generate_token('JOINv3?' . wooclap_http_build_query($datatoken));

        $dataframe = [
            'accessKeyId' => $accesskeyid,
            'authUrl' => $authurl,
            'canEdit' => $canedit,
            'courseUrl' => $courseurl,
            'displayName' => $USER->firstname . ' ' . $USER->lastname,
            // Only add the email if the user has consented.
            'email' => $SESSION->hasConsented ? $USER->email : '',
            'firstName' => $USER->firstname,
            'hasAccess' => wooclap_check_activity_user_access($cm->course, $cm->id, $USER->id),
            'lastName' => $USER->lastname,
            'moodleUsername' => $USER->username,
            'reportUrl' => $reporturl,
            'role' => $role,
            'token' => $token,
            'ts' => $ts,
            'version' => get_config('mod_wooclap')->version,
            'wooclapEventSlug' => $wooclap->linkedwooclapeventslug,
        ];

        wooclap_frame_view($wooclap->editurl . '?' . wooclap_http_build_query($dataframe));
    }
} else {
    throw new \moodle_exception('error-noeventid', 'wooclap');
}

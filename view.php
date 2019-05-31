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
 * @package mod_wooclap
 * @copyright  2018 CBlue sprl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once $CFG->libdir . '/completionlib.php';
require_once $CFG->dirroot . '/mod/wooclap/lib.php';

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
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
        $ts = get_isotime();
        try {
            $accesskeyid = wooclap_get_accesskeyid();
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        // If user has the teacher role, we send the author id to Wooclap,
        // so that the teacher interface is displayed in the Wooclap iframe.
        $role = wooclap_get_role(context_course::instance($cm->course));
        $wooclapuserid = $role === 'teacher' ? $wooclap->authorid : $USER->id;

        $data_token = [
            'accessKeyId' => $accesskeyid,
            'id' => $wooclap->id,
            'moodleUserId' => $wooclapuserid,
            'ts' => $ts,
        ];
        $token = wooclap_generate_token('JOIN?' . wooclap_http_build_query($data_token));

        $data_frame = [
            'id' => $wooclap->id,
            'moodleUserId' => $wooclapuserid,
            'displayName' => $USER->firstname . ' ' . $USER->lastname,
            'firstName' => $USER->firstname,
            'lastName' => $USER->lastname,
            'hasAccess' => wooclap_check_activity_user_access($cm->course, $cm->id, $USER->id),
            'email' => $USER->email,
            'role' => $role,
            'accessKeyId' => $accesskeyid,
            'ts' => $ts,
            'token' => $token,
        ];

        wooclap_frame_view($wooclap->editurl . '?' . wooclap_http_build_query($data_frame));
    }
} else {
    print_error('error-noeventid', 'wooclap');
}

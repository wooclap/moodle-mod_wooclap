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
 * Endpoint to rename a Moodle activity.
 *
 * @package    mod_wooclap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No login check is expected here because this script is called from wooclap
// ...and we authenticate request with a token.

// @codingStandardsIgnoreLine
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/wooclap/lib.php');

// Data required to rename the activity.
$cmid = required_param('cmid', PARAM_INT);
$name = required_param('name', PARAM_TEXT);

// Data required to authenticate the request.
$ts = required_param('ts', PARAM_TEXT);
$token = required_param('token', PARAM_TEXT);

$datatoken = [
    'accessKeyId' => get_config('wooclap', 'accesskeyid'),
    'cmid' => $cmid,
    'name' => $name,
    'ts' => $ts,
];
$tokencalc = wooclap_generate_token('RENAME?' . wooclap_http_build_query($datatoken));

try {
    if ($token !== $tokencalc) {
        throw new \moodle_exception('error-couldnotrename', 'wooclap');
        header("HTTP/1.0 403");
    }

    // Get the activity from the database.
    $cm = get_coursemodule_from_id('wooclap', $cmid, 0, false, MUST_EXIST);
    $instance = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);

    // Update the activity name.
    $instance->name = $name;
    $DB->update_record($cm->modname, $instance);

    // Also update the name in the grade book, if it exists.
    $gradeitem = $DB->get_record('grade_items', ['iteminstance' => $cm->instance, 'itemmodule' => $cm->modname], '*', MUST_EXIST);
    if ($gradeitem) {
        $gradeitem->itemname = $name;
        $DB->update_record('grade_items', $gradeitem);
    }

    // Clear the course cache for the change to be visible immediately.
    rebuild_course_cache($cm->course, true);
} catch (Exception $exc) {
    throw new \moodle_exception('error-couldnotrename', 'wooclap');
    header("HTTP/1.0 400");
}

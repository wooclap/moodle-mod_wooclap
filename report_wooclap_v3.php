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
 * Completion and grade update endpoint
 *
 * @package    mod_wooclap
 * @copyright  2018 Cblue sprl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No login check is expected here because this script is called from wooclap
// ...and we authenticate request with a token.

// @codingStandardsIgnoreLine
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/wooclap/lib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');

$cmid = required_param('cm', PARAM_INT);
$username = required_param('moodleUsername', PARAM_TEXT);
$completion = required_param('completion', PARAM_TEXT);
$score = required_param('score', PARAM_FLOAT);
$accesskeyid = required_param('accessKeyId', PARAM_TEXT);
$ts = required_param('ts', PARAM_TEXT);
$token = required_param('token', PARAM_TEXT);

try {
    $datatoken = [
        'accessKeyId' => get_config('wooclap', 'accesskeyid'),
        'completion' => $completion,
        'moodleUsername' => $username,
        'score' => $score,
        'ts' => $ts,
    ];
    $tokencalc = wooclap_generate_token('REPORTv3?' . wooclap_http_build_query($datatoken));

    if ($token === $tokencalc) {
        if ($completion == 'passed') {
            $completionparam = COMPLETION_COMPLETE_PASS;
        } else if ($completion == 'incomplete') {
            $completionparam = COMPLETION_INCOMPLETE;
        } else {
            $completionparam = COMPLETION_COMPLETE_FAIL;
        }

        $cm = get_coursemodule_from_id('wooclap', $cmid);
        $course = get_course($cm->course);
        $wooclapinstance = wooclap_get_instance($cm->instance);

        // Find user from username.
        $userdb = $DB->get_record('user', ['username' => $username], 'id', MUST_EXIST);

        $gradestatus = wooclap_update_grade($wooclapinstance, $userdb->id, $score, $completionparam);

        $completion = new completion_info($course);
        $completion->update_state($cm, $completionparam, $userdb->id);
    } else {
        throw new \moodle_exception('error-invalidtoken', 'wooclap');
        header("HTTP/1.0 403");
    }
} catch (Exception $e) {
    throw new \moodle_exception('error-couldnotupdatereport', 'wooclap');
}

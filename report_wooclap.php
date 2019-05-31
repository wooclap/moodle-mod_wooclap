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
// ...and we authenticate request with a token
// @codingStandardsIgnoreLine
require_once __DIR__ . '/../../config.php';
require_once $CFG->dirroot . '/mod/wooclap/lib.php';
require_once $CFG->dirroot . '/lib/completionlib.php';

$cmid = required_param('cm', PARAM_INT);
$userid = required_param('moodleUserId', PARAM_INT);
$completion = required_param('completion', PARAM_TEXT);
$score = required_param('score', PARAM_FLOAT);
$accessKeyId = required_param('accessKeyId', PARAM_TEXT);
$ts = required_param('ts', PARAM_TEXT);
$token = required_param('token', PARAM_TEXT);
$callback = optional_param('callbackUrl', '', PARAM_URL);

try {
    $data_token = [
        'accessKeyId' => wooclap_get_accesskeyid(),
        'completion' => $completion,
        'moodleUserId' => $userid,
        'score' => $score,
        'ts' => $ts,
    ];
    $token_calc = wooclap_generate_token('REPORT?' . wooclap_http_build_query($data_token));

    if ($token === $token_calc) {
        if ($completion == 'passed') {
            $completion_param = COMPLETION_COMPLETE_PASS;
        } else if ($completion == 'incomplete') {
            $completion_param = COMPLETION_INCOMPLETE;
        } else {
            $completion_param = COMPLETION_COMPLETE_FAIL;
        }

        $cm = get_coursemodule_from_id('wooclap', $cmid);
        $course = get_course($cm->course);
        $wooclapinstance = wooclap_get_instance($cm->instance);

        $gradestatus = wooclap_update_grade($wooclapinstance, $userid, $score, $completion_param);
        if ($gradestatus == GRADE_UPDATE_OK) {
            $callback_message = get_string('gradeupdateok', 'wooclap');
        } else {
            $callback_message = get_string('gradeupdatefailed', 'wooclap');
        }

        $completion = new completion_info($course);
        $completion->update_state($cm, $completion_param, $userid);
    } else {
        print_error('error-invalidtoken', 'wooclap');
        header("HTTP/1.0 403");
    }
    if ($callback) {
        redirect($callback, $callback_message);
    }
} catch (Exception $e) {
    print_error('error-couldnotupdatereport', 'wooclap');
    header("HTTP/1.0 500");
}

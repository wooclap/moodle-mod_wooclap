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

require_once(__DIR__ . '/../../../../config.php');

/**
 * Define all the backup steps that will be used by the backup_wooclap_activity_task
 */

/**
 * Define the complete wooclap structure for backup, with file and id annotations
 */
class backup_wooclap_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $wooclap = new backup_nested_element('wooclap', ['id'], [
            /*"course",*/"name", "intro",
            "introformat", "editurl", "quiz",
            "authorid", "customcompletion", "timecreated",
            "timemodified", "wooclapeventid",
            "linkedwooclapeventslug", "grade",
        ]);

        $completions = new backup_nested_element('completions');

        $completion = new backup_nested_element('completion', ['id'], [
            "wooclapid", "userid", "completionstatus", "grade", "timecreated", "timemodified"]);

        // Build the tree.
        $wooclap->add_child($completions);
        $completions->add_child($completion);

        // Define sources.
        $wooclap->set_source_table('wooclap', ['id' => backup::VAR_ACTIVITYID]);

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $completion->set_source_table('wooclap_completion', ['wooclapid' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        $wooclap->annotate_ids('user', 'authorid');
        $completion->annotate_ids('user', 'userid');

        // Define file annotations.
        $wooclap->annotate_files('mod_wooclap', 'intro', null, $contextid = null); // This file area does not have an itemid.

        // Return the root element (wooclap), wrapped into standard activity structure.
        return $this->prepare_activity_structure($wooclap);
    }
}

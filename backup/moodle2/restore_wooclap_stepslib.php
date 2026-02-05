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
 * Structure step to restore one wooclap activity
 */
class restore_wooclap_activity_structure_step extends restore_activity_structure_step {
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('wooclap', '/activity/wooclap');
        if ($userinfo) {
            $paths[] = new restore_path_element('wooclap_completion', '/activity/wooclap/completions/completion');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_wooclap($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();

        // Insert the wooclap record.
        $newitemid = $DB->insert_record('wooclap', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_wooclap_completion($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->wooclapid = $this->get_new_parentid('wooclap');

        $newitemid = $DB->insert_record('wooclap_completion', $data);
        $this->set_mapping('wooclap_completion', $oldid, $newitemid);
    }

    protected function after_execute() {
        // Add choice related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_wooclap', 'intro', null);
    }
}

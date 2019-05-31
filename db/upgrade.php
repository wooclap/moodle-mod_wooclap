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

// More info: https://docs.moodle.org/dev/Upgrade_API .

defined('MOODLE_INTERNAL') || die;

function xmldb_wooclap_upgrade($oldversion) {
    global $CFG, $DB;

    require_once $CFG->libdir . '/db/upgradelib.php';

    $dbman = $DB->get_manager();

    if ($oldversion < 2018080207) {

        $table = new xmldb_table('wooclap');
        $field = new xmldb_field('customcompletion', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018080207, 'wooclap');
    }
    if ($oldversion < 2019041300) {
        // https://docs.moodle.org/dev/XMLDB_creating_new_DDL_functions
        $table = new xmldb_table('wooclap');
        $field = new xmldb_field('wooclapeventid', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2019041300, 'wooclap');
    }

    return true;
}

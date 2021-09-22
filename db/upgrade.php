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
    global $CFG, $DB, $OUTPUT;

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

    if ($oldversion < 2020121400) {
        // V2 Upgrade.

        $table = new xmldb_table('wooclap');
        $fieldslug = new xmldb_field('linkedwooclapeventslug', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null);

        if (!$dbman->field_exists($table, $fieldslug)) {
            $dbman->add_field($table, $fieldslug);
        }

        // Upgrade existing wooclap activity records.
        $allwooclaprecords = $DB->get_records('wooclap');
        foreach ($allwooclaprecords as $activity) {
            if (!$activity->linkedwooclapeventslug) {
                $regexmatches = '';
                $slugregex = '/^(.*)api\/moodle(\/v\d+)?\/events\/(.*)$/i';
                if (preg_match($slugregex, $activity->editurl, $regexmatches)) {
                    $baseurl = $regexmatches[1];
                    $isv2orhigher = $regexmatches[2];
                    $eventslug = $regexmatches[3];
                    if ($isv2orhigher === '') {
                        $activity->editurl = $baseurl . 'api/moodle/v2/events/' . $eventslug;
                        $activity->linkedwooclapeventslug = $eventslug;
                        $DB->update_record('wooclap', $activity);
                    }
                }
            }
        }

        upgrade_mod_savepoint(true, 2020121400, 'wooclap');
    }

    if ($oldversion < 2021060100) {
        // PART 1 of the V3 upgrade.
        // Perform the two V3_UPGRADE_STEPs with Wooclap
        // so that Wooclap can use the username as identifier instead of the ids.
        // - V3_UPGRADE_STEP_1 will return the list of moodle user ids that have a wooclap account.
        // - V3_UPGRADE_STEP_2 will send a mapping from those ids to the moodle usernames to Wooclap.

        try {
            $accesskeyid = get_config('wooclap', 'accesskeyid');
            $secretaccesskey = get_config('wooclap', 'secretaccesskey');
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }
        // Check that plugin is configured.
        if ( !empty($accesskeyid) && !empty($secretaccesskey)) {
            mod_wooclap_v3_upgrade();
        } else {
            $warn = "Access key and/or secret key are missing for Wooclap plugin. Then considering that plugin is not used. Remote Wooclap upgrade datas steps not executed.";
            echo $OUTPUT->notification($warn, 'notifyproblem');
        }
        // PART 2 of the V3 upgrade.
        // Upgrade existing wooclap activity records
        // editUrl of existing activities must be updated /v2/ -> /v3/.
        $allwooclaprecords = $DB->get_records('wooclap');

        foreach ($allwooclaprecords as $activity) {
            $regexmatches = '';
            $slugregex = '/^(.*)api\/moodle(\/v\d+)?\/events\/(.*)$/i';

            if (preg_match($slugregex, $activity->editurl, $regexmatches)) {
                $baseurl = $regexmatches[1];
                $apiversion = $regexmatches[2];
                $eventslug = $regexmatches[3];

                if ($apiversion == '' || $apiversion == '/v2') {
                    $activity->editurl = $baseurl . 'api/moodle/v3/events/' . $eventslug;
                    $DB->update_record('wooclap', $activity);
                }
            }
        }

        upgrade_mod_savepoint(true, 2021060100, 'wooclap');
    }

    return true;
}
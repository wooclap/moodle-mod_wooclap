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

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $defaultbaseurl = 'https://app.wooclap.com';
    $settings->add(new admin_setting_heading('wooclap/config', get_string('wooclapsettings', 'wooclap'), ''));
    $settings->add(new admin_setting_configtext_with_maxlength(
        'wooclap/accesskeyid',
        get_string('accesskeyid', 'wooclap'),
        get_string('accesskeyid-description', 'wooclap'),
        '',
        PARAM_TEXT,
        50,
        128
    ));
    $settings->add(new admin_setting_configtext_with_maxlength(
        'wooclap/secretaccesskey',
        get_string('secretaccesskey', 'wooclap'),
        get_string('secretaccesskey-description', 'wooclap'),
        '',
        PARAM_TEXT,
        50,
        128
    ));
    $settings->add(new admin_setting_configtext_with_maxlength(
        'wooclap/baseurl',
        get_string('baseurl', 'wooclap'),
        get_string('baseurl-description', 'wooclap'),
        $defaultbaseurl,
        PARAM_URL,
        50,
        256
    ));
    $settings->add(new admin_setting_configcheckbox(
        'wooclap/showconsentscreen',
        get_string('showconsentscreen', 'wooclap'),
        get_string('showconsentscreen-description', 'wooclap'),
        false
    ));

    if (class_exists('mod_wooclap_test_connection')) {
        $settings->add(new mod_wooclap_test_connection('wooclap/testconnection', get_string('testconnection', 'wooclap'), ''));
    }

}

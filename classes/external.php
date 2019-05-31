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
 *
 * @package    mod_wooclap
 * @copyright  2018 Cblue sprl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once $CFG->libdir . '/externallib.php';

class wooclap_external extends external_api {

    /**
     * @return external_function_parameters
     * @throws coding_exception
     */
    public static function auth_user_parameters() {
        return new external_function_parameters(
            [
                'username' => new external_value(
                    core_user::get_property_type('username'), 'User name'
                ),
                'password' => new external_value(
                    core_user::get_property_type('password'), 'User Password'
                ),
            ]
        );
    }

    /**
     * Confirm a user account.
     *
     * @param  string $username user name
     * @param  string $password password
     * @return array warnings and success status (true if the user was confirmed,
     *               false if he was not confirmed)
     * @throws moodle_exception
     */
    public static function auth_user($username, $password) {
        $warnings = [];
        $params = self::validate_parameters(
            self::auth_user_parameters(),
            [
                'username' => $username,
                'password' => $password,
            ]
        );

        $authUser = authenticate_user_login($username, $password);

        if (is_object($authUser)) {
            $success = true;
        } else {
            throw new moodle_exception('invalidconfirmdata');
        }

        $result = [
            'success' => $success,
            'warnings' => $warnings,
        ];
        return $result;
    }

    /**
     * Describes the confirm_user return value.
     *
     * @return external_single_structure
     */
    public static function auth_user_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'True if the user was confirmed, false if he was not confirmed'),
                'warnings' => new external_warnings(),
            ]
        );
    }
}

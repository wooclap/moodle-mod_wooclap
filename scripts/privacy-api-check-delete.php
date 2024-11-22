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
 * Helper utility to check for deletion of user data.
 *
 * @copyright 2018 Cblue sprl
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   mod_wooclap
 */

// How to use this script?
// https://docs.moodle.org/dev/Privacy_API/Utilities .

define('CLI_SCRIPT', true);
require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");

list($options, $unrecognized) = cli_get_params(
    [
        'username' => '',
        'userid' => '',
    ],
    []
);

$user = null;
$username = $options['username'];
$userid = $options['userid'];

if (!empty($options['username'])) {
    $user = \core_user::get_user_by_username($options['username']);
} else if (!empty($options['userid'])) {
    $user = \core_user::get_user($options['userid']);
}

while (empty($user)) {
    if (!empty($username)) {
        echo "Unable to find a user with username '{$username}'.\n";
        echo "Try again.\n";
    } else if (!empty($userid)) {
        echo "Unable to find a user with userid '{$userid}'.\n";
        echo "Try again.\n";
    }
    $username = readline("Username: ");
    $user = \core_user::get_user_by_username($username);
}

echo "Processing delete for " . fullname($user) . "\n";

\core\session\manager::init_empty_session();
\core\session\manager::set_user($user);

$manager = new \core_privacy\manager();

$approvedlist = new \core_privacy\local\request\contextlist_collection($user->id);

$trace = new text_progress_trace();
$contextlists = $manager->get_contexts_for_userid($user->id, $trace);
foreach ($contextlists as $contextlist) {
    $approvedlist->add_contextlist(new \core_privacy\local\request\approved_contextlist(
        $user,
        $contextlist->get_component(),
        $contextlist->get_contextids()
    ));
}

$manager->delete_data_for_user($approvedlist, $trace);

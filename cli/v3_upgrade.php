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
 * CLI script to manually perform the upgrade to v3 of the API for the
 * Moodle plugin.
 *
 * https://docs.moodle.org/dev/CLI_scripts
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/mod/wooclap/locallib.php');

$usage = "Manually perform the v3 API upgrade for the Moodle plugin if not performed automatically during the plugin upgrade.
This upgrade is necessary to be able to use plugin versions >=2021062500.
To be able to perform this upgrade, you should have the config parameters accesskeyid, baseurl and secretaccesskey defined.

Usage:
    # php v3_upgrade.php

Options:
    -h --help             Print this help.
    -v, --verbose         Print verbose progress information
";

// Get the CLI options.
list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'verbose' => false,
], [
    'h' => 'help',
    'v' => 'verbose',
]);

// Print an error if some parameters were not recognized.
list($options, $unrecognized) = cli_get_params(['verbose' => false, 'help' => false], ['v' => 'verbose', 'h' => 'help']);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

// Print help message.
if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}

// Handle `verbose` parameter.
if (empty($options['verbose'])) {
    $trace = new null_progress_trace();
} else {
    $trace = new text_progress_trace();
}

// Perform the actual upgrade.
$result = mod_wooclap_v3_upgrade();

cli_writeln('Migration executed successfully.');

$trace->finished();

exit($result);

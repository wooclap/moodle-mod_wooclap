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
 * Consent screen
 *
 * @package    mod_wooclap
 * @copyright  Wooclap SA 2020
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/wooclap/lib.php');

global $SESSION, $USER;

$showconsentscreen = get_config('wooclap', 'showconsentscreen');

$hasconsentedquery = optional_param('hasConsented', null, PARAM_BOOL);
$redirecturl = optional_param('redirectUrl', null, PARAM_URL);

if (isset($hasconsentedquery)) {
    $SESSION->hasConsented = $hasconsentedquery;
}

// Once $SESSION->hasConsented has been set, we can redirect the user back to
// the right page.
if (isset($SESSION->hasConsented)) {
    // If there's a $redirectUrl, use that URL.
    if (!empty($redirecturl)) {
        redirect($redirecturl);
    }

    // Otherwise, use wooclap_redirect_auth which will redirect the
    // user back to Wooclap with the correct query string parameters.
    wooclap_redirect_auth($USER->id);
}

// We generate the URLs for the buttons which will allow us to set the session
// variable for `$hasConsented` via the query string.
$baseurl = new moodle_url('/mod/wooclap/wooclap_consent_screen.php', [
    'redirectUrl' => $redirecturl,
]);
$noconsenturl = new moodle_url($baseurl, ['hasConsented' => 0]);
$yesconsenturl = new moodle_url($baseurl, ['hasConsented' => 1]);

$template = include('./wooclap_consent_screen.tpl.php');
echo $template;

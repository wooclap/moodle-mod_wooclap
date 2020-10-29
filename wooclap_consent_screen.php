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
 * Consent screen
 *
 * @package    mod_wooclap
 * @copyright  Wooclap SA 2020
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once $CFG->dirroot . '/mod/wooclap/lib.php';

global $SESSION, $USER;

$showConsentScreen = get_config('wooclap', 'showconsentscreen');

$hasConsentedQuery = optional_param('hasConsented', null, PARAM_BOOL);
$redirectUrl = optional_param('redirectUrl', null, PARAM_URL);

if (isset($hasConsentedQuery)) {
    $SESSION->hasConsented = $hasConsentedQuery;
}

// Once $SESSION->hasConsented has been set, we can redirect the user back to
// the right page.
if (isset($SESSION->hasConsented)) {
    // If there's a $redirectUrl, use that URL
    if (!empty($redirectUrl)) {
        redirect($redirectUrl);
    }

    // Otherwise, use wooclap_redirect_auth which will redirect the
    // user back to Wooclap with the correct query string parameters.
    wooclap_redirect_auth($USER->id);
}

// We generate the URLs for the buttons which will allow us to set the session
// variable for `$hasConsented` via the query string.
$baseUrl = new moodle_url('/mod/wooclap/wooclap_consent_screen.php', [
    'redirectUrl' => $redirectUrl,
]);
$noConsentUrl = new moodle_url($baseUrl, ['hasConsented' => 0]);
$yesConsentUrl = new moodle_url($baseUrl, ['hasConsented' => 1]);
?>

<!doctype html>
<html class="consent-screen">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./css/consent-screen.css" rel="stylesheet" />
</head>

<body class="wrapper">
  <div class="modal">
    <img class="logo" src="./images/logo.jpg">
    <p class="text"><?php echo get_string('consent-screen:description', 'wooclap'); ?></p>
    <p class="text"><?php echo get_string('consent-screen:explanation', 'wooclap'); ?></p>
    <div class="buttons-wrapper">
      <a class="button plain" href="<?php echo $noConsentUrl ?>"><?php echo get_string('consent-screen:disagree', 'wooclap'); ?></a>
      <a class="button" href="<?php echo $yesConsentUrl ?>"><?php echo get_string('consent-screen:agree', 'wooclap'); ?></a>
    </div>
  </div>
</body>

</html>

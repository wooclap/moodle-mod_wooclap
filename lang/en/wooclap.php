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
 * This file contains en_utf8 translation of the Wooclap module
 *
 * @package mod_wooclap
 * @copyright  20018 CBlue sprl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['modulename'] = 'Wooclap';
$string['modulenameplural'] = 'Wooclap';
$string['modulename_help'] = 'This module provides a Wooclap interactive platform integration to Moodle';
$string['pluginname'] = 'Wooclap';
$string['pluginadministration'] = 'Wooclap administration';
$string['wooclapname'] = 'Name';
$string['wooclapintro'] = 'Description';
$string['quiz'] = 'Import a Moodle quiz';
$string['wooclapeventid'] = 'Duplicate a Wooclap event';
$string['wooclapsettings'] = 'Settings';
$string['testconnection'] = 'Test Connection';
$string['pingOK'] = 'PING OK';
$string['pingNOTOK'] = 'PING NOT OK';
$string['secretaccesskey'] = 'API Key (secretAccessKey)';
$string['secretaccesskey-description'] = 'Secret access key used to communicate with Wooclap. Should start with \'sk.\'.';
$string['accesskeyid'] = 'Platform Id (accessKeyId)';
$string['accesskeyid-description'] = 'Access key used to communicate with Wooclap. Should start with \'ak.\'.';
$string['baseurl'] = 'Base URL';
$string['baseurl-description'] = 'This is for debugging or testing only. Only change this value at the request of the Wooclap support team.';
$string['nowooclap'] = 'There are no wooclap instances';
$string['gradeupdateok'] = 'Grade update successful';
$string['gradeupdatefailed'] = 'Grade update not successful';
$string['customcompletion'] = 'Completion state updated only by Wooclap';
$string['customcompletiongroup'] = 'Wooclap custom completion';

$string['privacy:metadata:wooclap_server'] = 'In order to integrate with Wooclap, user data needs to be exchanged with it.';
$string['privacy:metadata:wooclap_server:userid'] = 'The userid is sent from Moodle to allow you to access your data on Wooclap.';

$string['error-noeventid'] = 'Could not determine event id';
$string['error-auth-nosession'] = 'Missing session in auth';
$string['error-callback-is-not-url'] = 'Callback parameter is not a valid url';
$string['error-couldnotredirect'] = 'Could not redirect';
$string['error-couldnotloadevents'] = 'Could not load user Wooclap events';
$string['error-couldnotupdatereport'] = 'Could not update report';
$string['error-couldnotauth'] = 'Could not get user or course during auth';
$string['error-invalidtoken'] = 'Invalid token';
$string['error-invalidjoinurl'] = 'Invalid join URL';
$string['error-missingparameters'] = 'Missing parameters';

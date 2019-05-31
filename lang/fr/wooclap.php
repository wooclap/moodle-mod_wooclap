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
$string['modulename_help'] = 'Cette activité permet d\'intégrer la plateforme interactive Wooclap à Moodle';
$string['pluginname'] = 'Wooclap';
$string['pluginadministration'] = 'Wooclap administration';
$string['wooclapname'] = 'Nom de l\'activité';
$string['wooclapintro'] = 'Description de l\'activité';
$string['quiz'] = 'Importer un quiz Moodle';
$string['wooclapeventid'] = 'Dupliquer un événement Wooclap';
$string['wooclapsettings'] = 'Paramètres';
$string['testconnection'] = 'Tester la Connexion';
$string['pingOK'] = 'PING OK';
$string['pingNOTOK'] = 'PING NOT OK';
$string['secretaccesskey'] = 'Clé API (secretAccessKey)';
$string['secretaccesskey-description'] = 'Clé secrète utilisée pour communiquer avec la plateforme Wooclap. Doit commencer par \'sk.\'.';
$string['accesskeyid'] = 'Identifiant de plateforme (accessKeyId)';
$string['accesskeyid-description'] = 'Clé d\'accès utilisée pour communiquer avec la plateforme Wooclap. Doit commencer par \'ak.\'.';
$string['baseurl'] = 'Url du webservice';
$string['baseurl-description'] = 'Sert uniquement au débogage ou au test. Ne modifiez cette valeur que si demandé par le support Wooclap.';
$string['nowooclap'] = 'Il n\'y a pas d\'instance wooclap';
$string['gradeupdateok'] = 'Mise à jour du grade effectuée avec succès';
$string['gradeupdatefailed'] = 'La mise à jour du grade a échoué';
$string['customcompletion'] = 'Suivi d\'achèvement mis à jour uniquement par Wooclap';
$string['customcompletiongroup'] = 'Conditions de suivi d\'achèvement Wooclap';

$string['privacy:metadata:wooclap_server'] = 'Nous échangeons certaines données utilisateur avec Wooclap pour mieux intégrer leurs services.';
$string['privacy:metadata:wooclap_server:userid'] = 'Votre userid Moodle est envoyé pour que vous puissiez accéder à vos données sur Wooclap.';

$string['error-noeventid'] = 'Impossible de déterminer l\'id de l\'événement';
$string['error-auth-nosession'] = 'Session manquante lors de la connexion';
$string['error-callback-is-not-url'] = 'Le paramètre de callback n\'est pas une url valide';
$string['error-couldnotredirect'] = 'Impossible d\'effectuer la redirection';
$string['error-couldnotloadevents'] = 'Impossible de charger les événements Wooclap';
$string['error-couldnotupdatereport'] = 'Impossible de mettre à jour le rapport';
$string['error-couldnotauth'] = 'Impossible d\'obtenir l\'utilisateur ou le cours durant l\'authentication.';
$string['error-invalidtoken'] = 'Token invalide';
$string['error-invalidjoinurl'] = 'Join URL invalide';
$string['error-missingparameters'] = 'Paramètres manquants';

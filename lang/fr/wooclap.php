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
$string['pluginadministration'] = 'Administration Wooclap';
$string['wooclapname'] = 'Nom de l\'activité';
$string['wooclapintro'] = 'Description de l\'activité';
$string['modulenamepluralformatted'] = 'Liste des activités Wooclap';
$string['quiz'] = 'Importer un quiz Moodle';
$string['importquiz_help'] = 'Tous les types de questions des tests Moodle ne sont pas pris en charge sur Wooclap. Cliquez [ici](https://docs.google.com/spreadsheets/d/1qNfegWe99EBQD2Sv2HEDD2i2cC1OVM-x1H9E2ZWliA4/edit?gid=0#gid=0) pour en savoir plus sur la compatibilité des questions entre les deux plateformes.';
$string['wooclapeventid'] = 'Dupliquer un événement Wooclap';

// Settings.
$string['wooclapsettings'] = 'Paramètres';
$string['testconnection'] = 'Tester la Connexion';
$string['pingOK'] = 'Connexion établie avec Wooclap';
$string['pingNOTOK'] = 'La connexion n\'a pas pu être établie avec Wooclap. Veuillez vérifier les paramètres de configuration.';
$string['secretaccesskey'] = 'Clé API (secretAccessKey)';
$string['secretaccesskey-description'] = 'Clé secrète utilisée pour communiquer avec la plateforme Wooclap. Doit commencer par \'sk.\'.';
$string['accesskeyid'] = 'Identifiant de plateforme (accessKeyId)';
$string['accesskeyid-description'] = 'Clé d\'accès utilisée pour communiquer avec la plateforme Wooclap. Doit commencer par \'ak.\'.';
$string['baseurl'] = 'URL du webservice';
$string['baseurl-description'] = 'Sert uniquement au débogage ou au test. Ne modifiez cette valeur que si demandé par le support Wooclap.';

$string['nowooclap'] = 'Il n\'y a pas d\'instance Wooclap';
$string['customcompletion'] = 'Suivi d\'achèvement mis à jour uniquement par Wooclap';
$string['customcompletion_help'] = 'Si cette option est active, l\'activité est considérée comme terminée lorsqu\'un élève a répondu à au moins une question Wooclap.';
$string['customcompletiongroup'] = 'Conditions de suivi d\'achèvement Wooclap';

// Consent screen.
$string['showconsentscreen'] = 'Afficher l\'écran de consentement ?';
$string['showconsentscreen-description'] = 'Si cette option est active, Wooclap demandera aux participants leur consentement avant de récupérer leur adresse email.';
$string['consent-screen:description'] = '<b>Wooclap</b> rend les étudiants acteurs de leur apprentissage.';
$string['consent-screen:explanation'] = 'Pour le bon fonctionnement de certaines opérations, dont l\'envoi d\'un rapport personnalisé à la fin d\'une session, Wooclap demande votre adresse email. Elle ne sera jamais utilisée à des fins marketing. Cliquez sur "J\'accepte" pour partager votre adresse email avec Wooclap, ou "Je refuse" pour continuer sans les fonctionnalités avancées.';
$string['consent-screen:agree'] = 'J\'accepte';
$string['consent-screen:disagree'] = 'Je refuse';

// Capabilities.
$string['wooclap:view'] = 'Accéder à une activité Wooclap';
$string['wooclap:addinstance'] = 'Ajouter une activité Wooclap à un cours';

$string['privacy:metadata:wooclap_server'] = 'Nous échangeons certaines données utilisateur avec Wooclap pour mieux intégrer leurs services.';
$string['privacy:metadata:wooclap_server:userid'] = 'Votre identifiant Moodle est envoyé pour que vous puissiez accéder à vos données sur Wooclap.';

$string['error-noeventid'] = 'Impossible de déterminer l\'identifiant de l\'événement';
$string['error-auth-nosession'] = 'Session manquante lors de la connexion';
$string['error-callback-is-not-url'] = 'L\'URL de retour (callback) n\'est pas une URL valide';
$string['error-couldnotredirect'] = 'Impossible d\'effectuer la redirection';
$string['error-couldnotloadevents'] = 'Impossible de charger les événements Wooclap';
$string['error-couldnotupdatereport'] = 'Impossible de mettre à jour le rapport';
$string['error-couldnotauth'] = 'Impossible d\'obtenir l\'utilisateur ou le cours durant l\'authentication';
$string['error-invalidtoken'] = 'Le valeur du paramètre "token" est invalide';
$string['error-during-quiz-import'] = 'Le test ne peut pas être converti en questions Wooclap car il ne contient que des questions non compatibles avec Wooclap.';
$string['error-invalidjoinurl'] = 'L\'URL pour rejoindre l\'événement est invalide';
$string['error-missingparameters'] = 'Paramètres manquants';
$string['error-reportdeprecated'] = 'report_wooclap.php n\'est plus supporté. Veuiller plutôt utiliser report_wooclap_v3.php.';
$string['error-invalid-callback-url'] = 'L\'URL de retour (callback) ne correspondant au domaine défini dans la configuration.';

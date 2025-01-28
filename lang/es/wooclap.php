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
$string['modulename_help'] = 'Esta actividad permite integrar la plataforma interactiva Wooclap en Moodle';
$string['pluginname'] = 'Wooclap';
$string['pluginadministration'] = 'Administración de Wooclap';
$string['wooclapname'] = 'Nombre de la actividad';
$string['wooclapintro'] = 'Descripción de la actividad';
$string['modulenamepluralformatted'] = 'Lista de actividades de Wooclap';
$string['quiz'] = 'Importar un cuestionario de Moodle';
$string['importquiz_help'] = 'No todos los tipos de preguntas de los cuestionarios de Moodle son compatibles con Wooclap. Haz clic [aquí](https://docs.google.com/spreadsheets/d/1qNfegWe99EBQD2Sv2HEDD2i2cC1OVM-x1H9E2ZWliA4/edit?gid=0#gid=0) para obtener más información sobre la compatibilidad de las preguntas entre las dos plataformas.';
$string['wooclapeventid'] = 'Duplicar un evento de Wooclap';

// Configuración.
$string['wooclapsettings'] = 'Configuración';
$string['testconnection'] = 'Probar la conexión';
$string['pingOK'] = 'Conexión establecida con Wooclap';
$string['pingNOTOK'] = 'No se pudo establecer la conexión con Wooclap. Por favor, verifica los parámetros de configuración.';
$string['secretaccesskey'] = 'Clave API (secretAccessKey)';
$string['secretaccesskey-description'] = 'Clave secreta utilizada para comunicarse con la plataforma Wooclap. Debe comenzar con "sk.".';
$string['accesskeyid'] = 'ID de acceso a la plataforma (accessKeyId)';
$string['accesskeyid-description'] = 'Clave de acceso utilizada para comunicarse con la plataforma Wooclap. Debe comenzar con "ak.".';
$string['baseurl'] = 'URL del servicio web';
$string['baseurl-description'] = 'Se utiliza únicamente para depuración o pruebas. Modifica este valor solo si es solicitado por el soporte de Wooclap.';
$string['showconsentscreen'] = '¿Mostrar la pantalla de consentimiento?';
$string['showconsentscreen-description'] = 'Si esta opción está habilitada, Wooclap pedirá a los participantes su consentimiento antes de recopilar su dirección de correo electrónico.';

$string['nowooclap'] = 'No hay instancias de Wooclap';
$string['customcompletion'] = 'Seguimiento de finalización actualizado solo por Wooclap';
$string['customcompletion_help'] = 'Si esta opción está habilitada, la actividad se considera completada cuando un estudiante responde al menos una pregunta de Wooclap.';
$string['customcompletiongroup'] = 'Condiciones de seguimiento de finalización de Wooclap';

// Pantalla de consentimiento.
$string['consent-screen:description'] = '<b>Wooclap</b> convierte a los estudiantes en protagonistas de su aprendizaje.';
$string['consent-screen:explanation'] = 'Para que algunas funciones puedan funcionar, como el envío de un informe personalizado al final de una sesión, Wooclap solicita tu dirección de correo electrónico. Nunca será utilizada con fines de marketing. Haz clic en "Estoy de acuerdo" para compartir tu dirección de correo electrónico con Wooclap, o en "No estoy de acuerdo" para continuar sin las funciones adicionales.';
$string['consent-screen:agree'] = 'Estoy de acuerdo';
$string['consent-screen:disagree'] = 'No estoy de acuerdo';

// Capacidades.
$string['wooclap:view'] = 'Acceder a una actividad Wooclap';
$string['wooclap:addinstance'] = 'Agregar una actividad Wooclap a un curso';

$string['privacy:metadata:wooclap_server'] = 'Intercambiamos algunos datos de usuario con Wooclap para integrar mejor sus servicios.';
$string['privacy:metadata:wooclap_server:userid'] = 'Se envía tu ID de Moodle para que puedas acceder a tus datos en Wooclap.';

$string['error-noeventid'] = 'No se pudo determinar el ID del evento';
$string['error-auth-nosession'] = 'Falta la sesión durante la autenticación';
$string['error-callback-is-not-url'] = 'La URL de retorno (callback) no es válida';
$string['error-couldnotredirect'] = 'No se pudo realizar la redirección';
$string['error-couldnotloadevents'] = 'No se pudieron cargar los eventos de Wooclap';
$string['error-couldnotupdatereport'] = 'No se pudo actualizar el informe';
$string['error-couldnotauth'] = 'No se pudo obtener el usuario o el curso durante la autenticación';
$string['error-couldnotperformv3upgradestep1'] = 'No se pudo realizar el Paso 1 de la Actualización a V3. Asegúrate de que el accesskeyid, el baseurl y el secretaccesskey estén configurados en los ajustes del plugin.';
$string['error-couldnotperformv3upgradestep2'] = 'No se pudo realizar el Paso 2 de la Actualización a V3';
$string['error-reportdeprecated'] = 'report_wooclap.php ya no es compatible. Utiliza report_wooclap_v3.php en su lugar.';
$string['error-invalidtoken'] = 'El valor del parámetro "token" no es válido';
$string['error-during-quiz-import'] = 'El cuestionario no se puede convertir en preguntas de Wooclap porque solo contiene preguntas no compatibles con Wooclap.';
$string['error-invalidjoinurl'] = 'La URL para unirse al evento no es válida';
$string['error-missingparameters'] = 'Faltan parámetros';
$string['error-invalid-callback-url'] = 'La URL de retorno (callback) no coincide con el dominio definido en la configuración.';

$string['warn-missing-config-during-upgrade-to-v3'] = 'Para ejecutar la migración, el accesskeyid, el baseurl y el secretaccesskey deben estar configurados en los ajustes. La migración se omitirá por ahora: puedes ejecutarla más tarde a través del script cli/v3_upgrade.php. Nota: si deseas usar el plugin, esta migración es obligatoria.';

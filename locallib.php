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

// More info: https://docs.moodle.org/dev/Upgrade_API.

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/wooclap/classes/wooclap_curl.php');
require_once($CFG->dirroot . '/mod/wooclap/lib.php');

/**
 * Perform v3 upgrade with wooclap server
 * @throws dml_exception
 * @throws moodle_exception
 * TODO check, if possible, if V3 upgrade already performed on remote Wooclap
 */
function mod_wooclap_v3_upgrade() {
    global $DB;

    $curl = new wooclap_curl();
    $headers = [];
    $headers[0] = "Content-Type: application/json";
    $headers[1] = sprintf("X-Wooclap-PluginVersion: %s", get_config('mod_wooclap')->version);
    $curl->setHeader($headers);

    $ts = wooclap_get_isotime();

    try {
        $accesskeyid = get_config('wooclap', 'accesskeyid');
        $version = get_config('mod_wooclap')->version;
        $baseurl = get_config('wooclap', 'baseurl');
    } catch (Exception $exc) {
        echo $exc->getMessage();
    }

    $baseurl = trim($baseurl, '/');

    // STEP 1.
    $v3upgradestep1url = sprintf("%s/api/moodle/v3/upgrade-step-1", $baseurl);
    $step1datatoken = [
        'accessKeyId' => $accesskeyid,
        'ts' => $ts,
        'version' => $version,
    ];

    $curldatastep1 = new StdClass();
    $curldatastep1->accessKeyId = $accesskeyid;
    $curldatastep1->ts = $ts;
    $curldatastep1->token = wooclap_generate_token(
        'V3_UPGRADE_STEP_1?' . wooclap_http_build_query($step1datatoken)
    );
    $curldatastep1->version = $version;

    $response = $curl->get(
        $v3upgradestep1url . '?' . wooclap_http_build_query($curldatastep1)
    );
    $curlinfo = $curl->info;

    if ($response && is_array($curlinfo) && $curlinfo['http_code'] == 200) {
        // STEP 2.
        $idstousernamesmapping = [];

        foreach (json_decode($response) as $moodleuserid) {
            $user = $DB->get_record(
                'user',
                ['id' => $moodleuserid]
            );

            $idstousernamesmapping[$moodleuserid] = $user->username;
        }

        $jsonmapping = json_encode($idstousernamesmapping, JSON_UNESCAPED_UNICODE);

        $v3upgradestep2url = sprintf("%s/api/moodle/v3/upgrade-step-2", $baseurl);
        $step2datatoken = [
            'accessKeyId' => $accesskeyid,
            'idsToUsernamesMapping' => $jsonmapping,
            'ts' => $ts,
            'version' => $version,
        ];

        $curldatastep2 = new StdClass();
        $curldatastep2->accessKeyId = $accesskeyid;
        $curldatastep2->idsToUsernamesMapping = $jsonmapping;
        $curldatastep2->ts = $ts;
        $curldatastep2->token = wooclap_generate_token(
            'V3_UPGRADE_STEP_2?' . wooclap_http_build_query($step2datatoken)
        );
        $curldatastep2->version = $version;

        $response = $curl->post(
            $v3upgradestep2url,
            json_encode($curldatastep2, JSON_UNESCAPED_UNICODE)
        );
        $curlinfo = $curl->info;

        if (!$response || !is_array($curlinfo) || $curlinfo['http_code'] != 200) {
            throw new \moodle_exception('error-couldnotperformv3upgradestep2', 'wooclap');
        }
    } else {
        throw new \moodle_exception('error-couldnotperformv3upgradestep1', 'wooclap');
    }
}

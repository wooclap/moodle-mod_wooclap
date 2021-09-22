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

// More info: https://docs.moodle.org/dev/Upgrade_API .

defined('MOODLE_INTERNAL') || die;

/**
 * Perform v3 upgrade with wooclap server
 * @throws dml_exception
 * @throws moodle_exception
 * TODO check, if possible, if V3 upgrade already performed on remote Wooclap
 */
function mod_wooclap_v3_upgrade(wooclap_curl $curl){
    global $DB;
    $curl = new wooclap_curl();
    $headers = [];
    $headers[0] = "Content-Type: application/json";
    $headers[1] = "X-Wooclap-PluginVersion: " . get_config('mod_wooclap')->version;
    $curl->setHeader($headers);
    $ts = get_isotime();
    try {
        $accesskeyid = get_config('wooclap', 'accesskeyid');
        $version = get_config('mod_wooclap')->version;
        $baseurl = get_config('wooclap', 'baseurl');
    } catch (Exception $exc) {
        echo $exc->getMessage();
    }
    $hastrailingslash = substr($baseurl, -1) === '/';
    // STEP 1.
    $v3upgradestep1url = $baseurl . ($hastrailingslash ? '' : '/') . 'api/moodle/v3/upgrade-step-1';
    $step1datatoken = [
        'accessKeyId' => $accesskeyid,
        'ts' => $ts,
        'version' => $version,
    ];

    $curl_data_step1 = new StdClass;
    $curl_data_step1->accessKeyId = $accesskeyid;
    $curl_data_step1->ts = $ts;
    $curl_data_step1->token = wooclap_generate_token(
        'V3_UPGRADE_STEP_1?' . wooclap_http_build_query($step1datatoken)
    );
    $curl_data_step1->version = $version;

    $response = $curl->get(
        $v3upgradestep1url . '?' . wooclap_http_build_query($curl_data_step1)
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

        $jsonmapping = json_encode($idstousernamesmapping);

        $v3upgradestep2url = $baseurl . ($hastrailingslash ? '' : '/') . 'api/moodle/v3/upgrade-step-2';
        $step2datatoken = [
            'accessKeyId' => $accesskeyid,
            'idsToUsernamesMapping' => $jsonmapping,
            'ts' => $ts,
            'version' => $version,
        ];

        $curl_data_step2 = new StdClass;
        $curl_data_step2->accessKeyId = $accesskeyid;
        $curl_data_step2->idsToUsernamesMapping = $jsonmapping;
        $curl_data_step2->ts = $ts;
        $curl_data_step2->token = wooclap_generate_token(
            'V3_UPGRADE_STEP_2?' . wooclap_http_build_query($step2datatoken)
        );
        $curl_data_step2->version = $version;

        $response = $curl->post(
            $v3upgradestep2url, json_encode($curl_data_step2)
        );
        $curlinfo = $curl->info;

        if (!$response || !is_array($curlinfo) || $curlinfo['http_code'] != 200) {
            print_error('error-couldnotperformv3upgradestep2', 'wooclap');
        }
    } else {
        print_error('error-couldnotperformv3upgradestep1', 'wooclap');
    }
}

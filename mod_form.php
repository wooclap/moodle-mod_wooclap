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
 * @package mod_wooclap
 * @copyright  2018 CBlue sprl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot . '/course/moodleform_mod.php';
require_once $CFG->dirroot . '/mod/wooclap/lib.php';

class mod_wooclap_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $DB, $COURSE, $USER;

        $mform = &$this->_form;

        // Add Name input.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '48']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule(
            'name', get_string('maximumchars', '', 255),
            'maxlength', 255, 'client'
        );

        // Add Description input.
        $this->standard_intro_elements(get_string('wooclapintro', 'wooclap'));
        $mform->setAdvanced('introeditor');

        // Show Description
        // Display the label to the right of the checkbox so it looks better
        // ...and matches rest of the form.
        if ($mform->elementExists('showdescription')) {
            $coursedesc = $mform->getElement('showdescription');
            if (!empty($coursedesc)) {
                $coursedesc->setText(' ' . $coursedesc->getLabel());
                $coursedesc->setLabel('&nbsp');
            }
        }

        // Add Quiz dropdown.
        $quizid = 0;
        if (isset($this->_cm)) {
            $wooclapid = $this->_cm->instance;
            if ($wooclapid) {
                $wooclap = $DB->get_record('wooclap', ['id' => $wooclapid]);
                $quizid = $wooclap->quiz;
            }
        }
        $quizz_db = $DB->get_records('quiz', ['course' => $COURSE->id]);
        $quizz = [];
        $quizz[0] = get_string('none');
        foreach ($quizz_db as $quiz_db) {
            $quizz[$quiz_db->id] = $quiz_db->name;
        }
        $mform->addElement('select', 'quiz', get_string('quiz', 'wooclap'), $quizz);
        $mform->setType('quiz', PARAM_INT);
        if ($quizid > 0) {
            $mform->setDefault('quiz', $quizid);
        }

        // Fetch a list of the user's Wooclap events from the Wooclap API
        // ...so that the user can choose to copy an existing event.
        $ts = get_isotime();
        try {
            $accesskeyid = get_config('wooclap', 'accesskeyid');
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $eventsListUrl = wooclap_get_events_list_url();
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }

        $data_token = [
            'accessKeyId' => $accesskeyid,
            'moodleUserId' => intval($USER->id),
            'ts' => $ts,
        ];

        $curl_data = new StdClass;
        $curl_data->moodleUserId = intval($USER->id);
        $curl_data->accessKeyId = $accesskeyid;
        $curl_data->ts = $ts;
        $curl_data->token = wooclap_generate_token(
            'EVENTS_LIST?' . wooclap_http_build_query($data_token)
        );

        $curl = new wooclap_curl();
        $headers = [];
        $headers[0] = "Content-Type: application/json";
        $headers[1] = "X-Wooclap-PluginVersion: " . get_config('mod_wooclap')->version;
        $curl->setHeader($headers);
        $response = $curl->get(
            $eventsListUrl . '?' . wooclap_http_build_query($curl_data)
        );
        $curlinfo = $curl->info;

        $wooclap_events = [];
        $wooclap_events['none'] = get_string('none');
        if ($response && is_array($curlinfo) && $curlinfo['http_code'] == 200) {
            foreach (json_decode($response) as $w_event) {
                $wooclap_events[$w_event->_id] = $w_event->name;
            }

        } else {
            print_error('error-couldnotloadevents', 'wooclap');
        }

        $mform->addElement(
            'select',
            'wooclapeventid',
            get_string('wooclapeventid', 'wooclap'),
            $wooclap_events
        );
        $mform->setType('wooclapeventid', PARAM_TEXT);
        $mform->setDefault('wooclapeventid', 'none');

        // Set default options.
        $this->standard_coursemodule_elements();

        $this->apply_admin_defaults();

        $this->add_action_buttons();
    }

    /**
     * Add elements for setting the custom completion rules.
     *
     * @category completion
     * @return array List of added element names, or names of wrapping group elements.
     * @throws coding_exception
     */
    public function add_completion_rules() {

        $mform = $this->_form;

        $group = [
            $mform->createElement(
                'checkbox',
                'customcompletion',
                ' ',
                get_string('customcompletion', 'wooclap')
            ),
        ];
        $mform->setType('customcompletion', PARAM_BOOL);
        $mform->addGroup(
            $group,
            'customcompletiongroup',
            get_string('customcompletiongroup', 'wooclap'),
            [' '],
            false
        );
        $mform->disabledIf('customcompletion', 'completion', 'in', [0, 1]);

        return ['customcompletiongroup'];
    }

    /**
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return ($data['customcompletion'] != 0);
    }

    /**
     * @param array $default_values
     */
    public function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        if (empty($default_values['customcompletion'])) {
            $default_values['customcompletion'] = 1;
        }
        $default_values['completion'] = 2;
    }
}

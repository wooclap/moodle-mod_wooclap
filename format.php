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
 * Export wooclap quiz as Moodle XML.
 *
 * @package    qformat_wooclap
 * @copyright  2018 Cblue sprl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/format/xml/format.php');

class qformat_wooclap extends qformat_xml {
    /**
     * Do the export
     * @return mixed|stored_file|string
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function exportprocess($checkcapabilities = true) {
        global $DB;

        // Get the questions (from database) in this category.
        // Only get q's with no parents (no cloze subquestions specifically).
        if ($this->category) {
            $questions = get_questions_category($this->category, true);
        } else {
            $questions = $this->questions;
        }

        $count = 0;

        // Results are first written into string (and then to a file).
        // So create/initialize the string here.
        $expout = "";

        // Track which category questions are in.
        // If it changes we will record the category change in the output.
        // File if selected, 0 means that it will get printed before the 1st question.
        $trackcategory = 0;

        // Iterate through questions.
        foreach ($questions as $question) {
            // Used by file api.
            $contextid = $DB->get_field(
                'question_categories',
                'contextid',
                ['id' => $question->category]
            );
            $question->contextid = $contextid;

            // Do not export hidden questions.
            if (!empty($question->hidden)) {
                continue;
            }

            // Do not export random questions.
            if ($question->qtype == 'random') {
                continue;
            }

            // Check if we need to record category change.
            if ($this->cattofile) {
                if ($question->category != $trackcategory) {
                    $trackcategory = $question->category;
                    $categoryname = $this->get_category_path(
                        $trackcategory,
                        $this->contexttofile
                    );

                    // Create 'dummy' question for category export.
                    $dummyquestion = new stdClass();
                    $dummyquestion->qtype = 'category';
                    $dummyquestion->category = $categoryname;
                    $dummyquestion->name = 'Switch category to ' . $categoryname;
                    $dummyquestion->id = 0;
                    $dummyquestion->questiontextformat = '';
                    $dummyquestion->contextid = 0;
                    $expout .= $this->writequestion($dummyquestion) . "\n";
                }
            }

            // Export the question displaying message.
            $count++;

            if (question_has_capability_on($question, 'view', $question->category)) {
                $expout .= $this->writequestion($question, $contextid) . "\n";
            }
        }

        // Final pre-process on exported data.
        $expout = $this->presave_process($expout);
        return $expout;
    }
}

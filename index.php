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
 * This script lists the Wooclap activities in a course.
 * Any user can access this script.
 *
 * @package mod_wooclap
 * @copyright  2018 CBlue sprl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/wooclap/lib.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_login($course);
$PAGE->set_pagelayout('incourse');

$params = [
    'context' => context_course::instance($course->id),
];
$event = \mod_wooclap\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/wooclap/index.php', ['id' => $course->id]);
$pagetitle = strip_tags(
    $course->shortname . ': ' . get_string('modulenameplural', 'wooclap')
);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Print the main part of the page.
echo $OUTPUT->heading(get_string("modulenamepluralformatted", "wooclap"));

// Get all the appropriate data.
if (!$wooclaps = get_all_instances_in_course('wooclap', $course)) {
    notice(
        get_string('nowooclap', 'wooclap'),
        '../../course/view.php?id=$course->id'
    );
    die;
}

// We print the list of instances.
$timenow = time();
$strname = get_string('name');
$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_' . $course->format);
    $table->head = [$strsectionname, $strname];
    $table->align = ['center', 'left'];
} else {
    $table->head = [$strname];
}

foreach ($wooclaps as $wooclap) {
    if (!$wooclap->visible) {
        // Show dimmed if the mod is hidden.
        $link = "<a class=\"dimmed\" href=\"view.php?id=$wooclap->coursemodule\">$wooclap->name</a>";
    } else {
        // Show normal if the mod is visible.
        $link = "<a href=\"view.php?id=$wooclap->coursemodule\">$wooclap->name</a>";
    }

    if ($usesections) {
        $table->data[] = [get_section_name($course, $wooclap->section), $link];
    } else {
        $table->data[] = [$link];
    }
}

echo '<br>';

echo html_writer::table($table);

echo $OUTPUT->footer();

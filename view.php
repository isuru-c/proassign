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
 * Prints a particular instance of proassign
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_proassign
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace proassign with the name of your module and remove this line.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... proassign instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('proassign', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $proassign  = $DB->get_record('proassign', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $proassign  = $DB->get_record('proassign', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $proassign->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('proassign', $proassign->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_proassign\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $proassign);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/proassign/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($proassign->name));
$PAGE->set_heading(format_string($course->fullname));

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('proassign-'.$somevar);
 */

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($proassign->intro) {
    echo $OUTPUT->box(format_module_intro('proassign', $proassign, $cm->id), 'generalbox mod_introbox', 'proassignintro');
}

// Replace the following lines with you own code.
echo $OUTPUT->heading('Yay! It works!');

// Finish the page.
echo $OUTPUT->footer();

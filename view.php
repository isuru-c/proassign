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
require_once($CFG->dirroot . '/mod/proassign/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID

if ($id) {
    $cm         = get_coursemodule_from_id('proassign', $id, 0, false, MUST_EXIST); // Course module
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST); // Course ralated to this instant
    $proassign  = $DB->get_record('proassign', array('id' => $cm->instance), '*', MUST_EXIST); // Proassign instant
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$PAGE->set_url('/mod/proassign/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($proassign->name));
$PAGE->set_heading(format_string($course->fullname));

$context = context_module::instance($cm->id);

$proassign = new proassign($context, $cm, $course);

$action = optional_param('action', '', PARAM_TEXT);


echo $proassign->view(optional_param('action', '', PARAM_TEXT));

//echo $OUTPUT->header();

//echo $OUTPUT->box(format_module_intro('proassign', $proassign, $cm->id), 'generalbox mod_introbox', 'proassignintro');

// Replace the following lines with you own code.
//echo $OUTPUT->heading('Yay! It works!' . $action);
//print_r($action);

// Finish the page.
//echo $OUTPUT->footer();

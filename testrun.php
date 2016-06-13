<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
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

$PAGE->set_url('/mod/proassign/submission.php', array('id' => $cm->id));
$PAGE->set_title(format_string($proassign->name));
$PAGE->set_heading(format_string($course->fullname));

//$userid = optional_param('userid',FALSE,PARAM_INT);

$context = context_module::instance($cm->id);
$params = array('id' => $cm->instance);
$instance = $DB->get_record('proassign', $params, '*', MUST_EXIST);

$PAGE->set_cm($cm, $course, $instance);
$PAGE->set_context($context);

$proassign = new proassign($context, $cm, $course);

echo $proassign->view_testrun_page(null);

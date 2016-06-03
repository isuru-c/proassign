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
 * The main proassign configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_proassign
 * @copyright  Isuru Chandima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/proassign/locallib.php');

/**
 * Module instance settings form
 *
 * @package    mod_proassign
 * @copyright  Isuru Chandima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_proassign_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB, $COURSE, $PAGE;

        $mform = $this->_form;

		// General header
		
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', 'Assignment name', array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');		
		$this->standard_intro_elements('Description');		
		
		// Submission period header
		
		$mform->addElement('header', 'submissionperiod', 'Submission period');
        $secondsday=24*60*60;
        $now = time();
        $inittime = round($now / $secondsday) * $secondsday+5*60;
        $endtime = $inittime + (8*$secondsday) - 5*60;
        // startdate
        $mform->addElement('date_time_selector', 'startdate', 'Start date', array('optional'=>true));
        $mform->setDefault('startdate', 0);
        $mform->setAdvanced('startdate');
        // duedate
        $mform->addElement('date_time_selector', 'duedate', 'Due date', array('optional'=>true));
        $mform->setDefault('duedate', $endtime);

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}

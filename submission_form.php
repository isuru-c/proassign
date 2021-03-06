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

/**
 * @version        $Id: submission_form.php,v 1.15 2013-06-10 08:14:31 juanca Exp $
 * @package mod_vpl. Submission form definition
 * @copyright    2012 Juan Carlos Rodríguez-del-Pino
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author        Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/proassign/locallib.php');


class mod_proassign_submission_form extends moodleform {
	
    protected $proassign;
	
    function getInternalForm(){
        return $this->_form;
    }
	
    function __construct($page, $proassign){
        $this->proassign =$proassign;
        parent::__construct($page);
    }
	
    function definition(){
    	global $CFG;
		
		
    
		$mform =& $this->_form;
        
		$data = $this->get_data();
		
		$mform->addElement('header', 'headersubmission', 'Submission');
        //Identification info
        $mform->addElement('hidden','id');
        $mform->setType('id', PARAM_INT);
		$mform->addElement('hidden','state');
        $mform->setType('state', PARAM_INT);
        //$mform->addElement('hidden','userid',0);
        //$mform->setType('userid', PARAM_INT);
        //Comments
		$mform->addElement('static', 'I', 'Important', 'Do not use file submission yet. It is not completed yet');
        $mform->addElement('textarea', 'code', 'Enter your solution', array('cols'=>'100', 'rows'=>6));
        $mform->setType('code', PARAM_TEXT);

        //Files upload
        $instance = $this->proassign->get_instance();
        $mform->addElement('filepicker', 'file', 'File submission');
        $this->add_action_buttons(TRUE,get_string('submit'));
    }
}


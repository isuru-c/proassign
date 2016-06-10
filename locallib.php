<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/mod/assign/mod_form.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');
require_once($CFG->dirroot . '/mod/proassign/renderable.php');
require_once($CFG->dirroot . '/mod/proassign/renderer.php');
require_once($CFG->dirroot . '/mod/proassign/submission_form.php');
require_once($CFG->libdir . '/eventslib.php');
require_once($CFG->libdir . '/portfolio/caller.php');

class proassign{
	
	
    private $instance;
	private $gradeitem;
	private $context;
	private $course;
	private $adminconfig;
	private $output;
    private $coursemodule;
    private $cache;
    private $returnaction = 'view';
    private $returnparams = array();
    private static $modulename = null;
    private static $modulenameplural = null;
    private $markingworkflowstates = null;
    private $showonlyactiveenrol = null;
    private $useridlistid = null;
    private $participants = array();
    private $usersubmissiongroups = array();
    private $usergroups = array();
    private $sharedgroupmembers = array();
	
	public function __construct($coursemodulecontext, $coursemodule, $course) {
        global $SESSION;

        $this->context = $coursemodulecontext;
        $this->course = $course;
        $this->coursemodule = cm_info::create($coursemodule);
        if (!isset($SESSION->mod_proassign_useridlist)) {
            $SESSION->mod_proassign_useridlist = [];
        }
    }
	
	public function set_course(stdClass $course) {
        $this->course = $course;
    }

	public function get_course_module() {
        if ($this->coursemodule) {
            return $this->coursemodule;
        }
        if (!$this->context) {
            return null;
        }

        if ($this->context->contextlevel == CONTEXT_MODULE) {
            $modinfo = get_fast_modinfo($this->get_course());
            $this->coursemodule = $modinfo->get_cm($this->context->instanceid);
            return $this->coursemodule;
        }
        return null;
    }
	
	public function get_context() {
        return $this->context;
    }
	
	public function get_course() {
        global $DB;

        if ($this->course) {
            return $this->course;
        }

        if (!$this->context) {
            return null;
        }
        $params = array('id' => $this->get_course_context()->instanceid);
        $this->course = $DB->get_record('course', $params, '*', MUST_EXIST);

        return $this->course;
    }
	
	public function get_useridlist_key_id() {
        return $this->useridlistid;
    }

	public function get_instance() {
        global $DB;
        if ($this->instance) {
            return $this->instance;
        }
        if ($this->get_course_module()) {
            $params = array('id' => $this->get_course_module()->instance);
            $this->instance = $DB->get_record('proassign', $params, '*', MUST_EXIST);
        }
        if (!$this->instance) {
            throw new coding_exception('Improper use of the assignment class. Cannot load the assignment record.');
        }
        return $this->instance;
    }
	
	public function view($action='') {
		
		$out = '';
        $mform = null;
        $notices = array();
        $nextpageparams = array();
		
		if (!empty($this->get_course_module()->id)) {
            $nextpageparams['id'] = $this->get_course_module()->id;
        }
		
		$returnparams = array('rownum'=>optional_param('rownum', 0, PARAM_INT),
                              'useridlistid' => optional_param('useridlistid', $this->get_useridlist_key_id(), PARAM_ALPHANUM));
        //$this->register_return_link($action, $returnparams);


        if ($action == 'redirect') {
            $nextpageurl = new moodle_url('/mod/proassign/view.php', $nextpageparams);
            redirect($nextpageurl);
            return;
        } else if($action == 'testcases'){
			$out .= $this->view_test_cases();
		} else if($action == 'newtestcase'){
			$out .= $this->new_test_cases();
		} else if($action == 'savetestcase'){
			$out .= $this->save_test_cases();
		} else if($action == 'viewsubmission'){
			$out .= $this->view_submission_page();
		}
		else {
            $out .= $this->view_main_page();
        }

        return $out;		
		
	}	
	
	protected function view_main_page() {
        global $CFG, $DB, $USER, $PAGE;

        $instance = $this->get_instance();
        $out = '';
        $postfix = '';
		
        $out .= $this->get_renderer()->render(new proassign_header($instance, $this->get_context(), $this->show_intro(), 
																   $this->get_course_module()->id, '', '', $postfix));

		if($this->can_manage_assignment()){
			
		}
		
		
        if ($this->can_view_grades()) {
            
        }

		
        if ($this->can_view_submission($USER->id)) {
            //$out .= $this->view_student_summary($USER, true);
        }
		
        $out .= $this->view_footer();

        return $out;
    }
	
	protected function view_footer() {
        if (!PHPUNIT_TEST) {
            return $this->get_renderer()->render_footer();
        }
        return '';
    }
	
	protected function view_test_cases(){
		global $CFG, $DB, $USER, $PAGE;
		
		$instance = $this->get_instance();
		$out = '';
		
		$out .= $this->get_renderer()->render(new proassign_test_case($instance, $this->get_context(), $this->get_course_module()->id, 
																	  $this->can_manage_assignment()));
		
		$out .= $this->view_footer();
		
		return $out;		
	}
	
	protected function new_test_cases(){
		global $CFG, $DB, $USER, $PAGE;
		
		$instance = $this->get_instance();
		$out = '';
		
		$out .= $this->get_renderer()->render(new proassign_new_test_case($instance, $this->get_context(), $this->get_course_module()->id, 
																	  $this->can_manage_assignment()));
		
		$out .= $this->view_footer();
		
		return $out;		
	}
	
	protected function save_test_cases(){
		global $CFG, $DB, $USER, $PAGE;
		
		$instance = $this->get_instance();
		$out = '';
		
		$out .= $this->get_renderer()->render(new proassign_test_case($instance, $this->get_context(), $this->get_course_module()->id, 
																	  $this->can_manage_assignment()));
		
		$out .= $this->view_footer();
		
		print_r(optional_param('Mouse', 0, PARAM_TEXT));
		
		return $out;		
	}
	
	public function can_manage_assignment() {
        if (!has_any_capability(array('mod/proassign:manage'), $this->context)) {
            return false;
        }

        return true;
    }
	
	public function can_view_grades() {
        if (!has_any_capability(array('mod/proassign:viewgrades', 'mod/proassign:grade'), $this->context)) {
            return false;
        }

        return true;
    }
	
	public function can_view_submission($userid) {
        global $USER;

        if (!$this->is_active_user($userid) && !has_capability('moodle/course:viewsuspendedusers', $this->context)) {
            return false;
        }
        if (has_any_capability(array('mod/proassign:viewgrades', 'mod/proassign:grade'), $this->context)) {
            return true;
        }
        if (!is_enrolled($this->get_course_context(), $userid)) {
            return false;
        }
        if ($userid == $USER->id && has_capability('mod/proassign:submit', $this->context)) {
            return true;
        }
        return false;
    }
	
	public function is_active_user($userid) {
        return !in_array($userid, get_suspended_userids($this->context, true));
    }
		
	public function get_submission_plugins() {
        return $this->submissionplugins;
    }
	
	public function get_return_action() {
        global $PAGE;

        $params = $PAGE->url->params();

        if (!empty($params['action'])) {
            return $params['action'];
        }
        return '';
    }
	
	public function get_return_params() {
        global $PAGE;

        $params = $PAGE->url->params();
        unset($params['id']);
        unset($params['action']);
        return $params;
    }
		
	public function get_course_context() {
        if (!$this->context && !$this->course) {
            throw new coding_exception('Improper use of the assignment class. ' .
                                       'Cannot load the course context.');
        }
        if ($this->context) {
            return $this->context->get_course_context();
        } else {
            return context_course::instance($this->course->id);
        }
    }
	
    public function get_renderer() {
        global $PAGE;
        if ($this->output) {
            return $this->output;
        }		
        $this->output = $PAGE->get_renderer('mod_proassign');
        return $this->output;
    }
	
	public function show_intro() {
		return true;
        if ($this->get_instance()->alwaysshowdescription ||
                time() > $this->get_instance()->allowsubmissionsfromdate) {
            return true;
        }
        return false;
    }
			
	public function view_submission_page() {
        global $CFG, $USER, $DB, $PAGE, $COURSE, $OUTPUT;

        $PAGE->set_pagelayout('incourse');
		$PAGE->set_heading($this->get_instance()->name);
        echo $OUTPUT->header();
		
		$heading = format_string($this->get_instance()->name, false, array('context' => $this->get_course_context()));
        echo $OUTPUT->heading($heading);
		
		$id = $this->get_course_module()->id;

		echo $this->render_header_links($id);
		
		echo $OUTPUT->container_start('submission');
		echo '</br>';
        echo $OUTPUT->heading('Submission', 4);
        echo $OUTPUT->box_start('submission');
		
		$proassign = $this->get_instance()->id;
		$userid = $USER->id;
		
		/*****************************************************************************************************************/
		
		// Admin reagon
		
		if($this->can_manage_assignment()){
			
			echo "Submission summery of students</br>";
			
			$course_id = $COURSE->id;
			
			$table = new html_table();
			
			echo html_writer::table($table);
			
			$OUTPUT->box_end();	
			$OUTPUT->container_end();
			echo $this->view_footer();
			return;
		}
		
		/*****************************************************************************************************************/
		
		// Student region
		
		// Check if the user has submiited earlier
		
		$sql = "SELECT * FROM mdl_proassign_submission WHERE proassign=" . $proassign . " AND userid=" . $userid;
		$data = $DB->get_record_sql($sql, null );
		
		if($data){
			
			/**************************************************************************************************************/
			
			// Sabmission have been made, show submission states
			
			
			$textsubmission = "No";
			$filesubmission = "No";
			if($data->textsubmission){
				$textsubmission = "Yes";
			}
			if($data->filesubmission){
				$filesubmission = "Yes";	
			}
			
			$timestamp = $data->datesubmitted;
			$datesubmitted = date('Y-m-d H:i:s a', $timestamp);
			
			$marks = "Not yet graded";
			
			$table = new html_table();
			
			echo "</br><b>Assignment has been submitted </b> </br></br>";
			
			$this->add_table_row($table, 'Submitted date', $datesubmitted);
			$this->add_table_row($table, 'Text submission', $textsubmission);
			$this->add_table_row($table, 'File submission', $filesubmission);
			$this->add_table_row($table, 'Marks', $marks);
						
			echo html_writer::table($table);
			
		}else{
			$mform = new mod_proassign_submission_form('submission.php', $this);

			if ($mform->is_cancelled()){
				
				$OUTPUT->box_end();	
				$OUTPUT->container_end();
				echo $this->view_footer();
				return;
			}

			$form_data = $mform->get_data();

			$submitted = false;
			$error='';
			$sql = '';
			
			/**************************************************************************************************************************/

			// Submission saving region
			
			if ($form_data){
				$raw_POST_size = strlen(file_get_contents("php://input"));
				if($_SERVER['CONTENT_LENGTH'] != $raw_POST_size){
					$error="NOT SAVED (Http POST error: CONTENT_LENGTH expected ".$_SERVER['CONTENT_LENGTH']." found $raw_POST_size)";
					notice($error,vpl_mod_href('forms/submission.php','id',$id,'userid',$userid),$vpl->get_course());
					die;
				}

				$name = trim($mform->get_new_filename('file'));
				$data = $mform->get_file_content('file');

				$filesubmission = 0;
				$filename = ' ';

				if($data !== false && $name !== false ){
					$ext = strtolower (pathinfo ($name, PATHINFO_EXTENSION));
					if(in_array($ext, Array('jar','zip','jpg','gif'))){
						$data = chunk_split(base64_encode($data));
						$name .= '.b64';
					}else{
						if($data != ''){
							$encode = mb_detect_encoding($data, 'UNICODE, UTF-16, UTF-8, ISO-8859-1',true);
							if($encode > ''){ //If code detected
								$data = iconv($encode,'UTF-8',$data);
							}
						}
					}
					$file = array('name' => $name, 'data' => $data);
					$filename = $name . '_' . $userid . '_' . time();
					$filesubmission = 1;
				}else{
					$filesubmission = 0;
				}

				$error='';

				$textcode = $form_data->code;
				$textsubmission = 1;
				if($textcode == ''){
					$textsubmission = 0;
				}

				$submissiondata = new stdClass();
				$submissiondata->proassign = $proassign;
				$submissiondata->userid = $userid;
				$submissiondata->datesubmitted = time();
				$submissiondata->textsubmission = $textsubmission;
				$submissiondata->textcode = $textcode;
				$submissiondata->filesubmission = $filesubmission;
				$submissiondata->filename = $filename;

				$sql = 'INSERT INTO mdl_proassign_submission (proassign, userid, datesubmitted, textsubmission, textcode, filesubmission, filename) VALUES (';
				$sql .= $this->get_instance()->id . ',' . $userid . ',' . time() . ',' . $textsubmission . ',"' . $textcode . '",' . $filesubmission . ',"' . $filename . '")';

				//print_r($sql);

				if(!$DB->execute($sql, null)){
					$error="Submission saving failed";
				}

				/*$submissionid = $DB->insert_record('proassign_submission', $submissiondata , TRUE);
				if(!$submissionid){
					$error="Submission saving failed";
				}*/
				//Save files

				if($filesubmission){
					$data_directory = $CFG->dataroot . '/proassign/submission';

					$fgm = new file_group_process($filename, $data_directory);
					$fgm->addFile($filename, $data);
					/*$submission->remove_grade();
					//if no submitted by grader and not group activity
					//remove near submmissions
					if($submittedby == ''){
						$this->delete_overflow_submissions($userid);
					}*/
				}

				$submitted = true;
			}

			if($submitted){
				echo $error;
			}else{
				$data = new stdClass();
				$data->id = $id;
				$mform->set_data($data);
				$mform->display();
			}
		}
		
		// Saving region ends
		
		/**************************************************************************************************************************/
		
		$OUTPUT->box_end();	
		$OUTPUT->container_end();
		echo $this->view_footer();


    }
	
	private function add_table_row(html_table $table, $col1, $col2) {
        $row = new html_table_row();
		
        $cell1 = new html_table_cell($col1);
        $cell2 = new html_table_cell($col2);
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
	}
	
	public function view_submitted_page(){
		
	}
	
	public function render_header_links($id){
		global $OUTPUT;
		
		$out = '';
		
		$out .= $OUTPUT->container_start('testcaselinks');
        $urlparams = array('id' => $id, 'action'=>'');
        $url = new moodle_url('/mod/proassign/view.php', $urlparams);
        $out .= $OUTPUT->action_link($url, 'Assignment');
        $out .= $OUTPUT->container_end();
		
		$out .= $OUTPUT->container_start('testcaselinks');
        $urlparams = array('id' => $id, 'action'=>'testcases');
        $url = new moodle_url('/mod/proassign/view.php', $urlparams);
        $out .= $OUTPUT->action_link($url, 'Test cases');
        $out .= $OUTPUT->container_end();
		
		$out .= $OUTPUT->container_start('testcaselinks');
        $urlparams = array('id' => $id);
        $url = new moodle_url('/mod/proassign/submission.php', $urlparams);
        $out .= $OUTPUT->action_link($url, 'Submission');
        $out .= $OUTPUT->container_end();
		
		return $out;
	}
	
}

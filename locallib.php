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
    private $submissionplugins;
    private $feedbackplugins;
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
				
		{
		if ($action == 'savesubmission') {
            $action = 'editsubmission';
            if ($this->process_save_submission($mform, $notices)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'view';
            }
        } else if ($action == 'editprevioussubmission') {
            $action = 'editsubmission';
            if ($this->process_copy_previous_attempt($notices)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'editsubmission';
            }
        } else if ($action == 'lock') {
            $this->process_lock_submission();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'addattempt') {
            $this->process_add_attempt(required_param('userid', PARAM_INT));
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'reverttodraft') {
            $this->process_revert_to_draft();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'unlock') {
            $this->process_unlock_submission();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'setbatchmarkingworkflowstate') {
            $this->process_set_batch_marking_workflow_state();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'setbatchmarkingallocation') {
            $this->process_set_batch_marking_allocation();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'confirmsubmit') {
            $action = 'submit';
            if ($this->process_submit_for_grading($mform, $notices)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'view';
            } else if ($notices) {
                $action = 'viewsubmitforgradingerror';
            }
        } else if ($action == 'submitotherforgrading') {
            if ($this->process_submit_other_for_grading($mform, $notices)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'grading';
            } else {
                $action = 'viewsubmitforgradingerror';
            }
        } else if ($action == 'gradingbatchoperation') {
            $action = $this->process_grading_batch_operation($mform);
            if ($action == 'grading') {
                $action = 'redirect';
                $nextpageparams['action'] = 'grading';
            }
        } else if ($action == 'submitgrade') {
            if (optional_param('saveandshownext', null, PARAM_RAW)) {
                // Save and show next.
                $action = 'grade';
                if ($this->process_save_grade($mform)) {
                    $action = 'redirect';
                    $nextpageparams['action'] = 'grade';
                    $nextpageparams['rownum'] = optional_param('rownum', 0, PARAM_INT) + 1;
                    $nextpageparams['useridlistid'] = optional_param('useridlistid', $this->get_useridlist_key_id(), PARAM_ALPHANUM);
                }
            } else if (optional_param('nosaveandprevious', null, PARAM_RAW)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'grade';
                $nextpageparams['rownum'] = optional_param('rownum', 0, PARAM_INT) - 1;
                $nextpageparams['useridlistid'] = optional_param('useridlistid', $this->get_useridlist_key_id(), PARAM_ALPHANUM);
            } else if (optional_param('nosaveandnext', null, PARAM_RAW)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'grade';
                $nextpageparams['rownum'] = optional_param('rownum', 0, PARAM_INT) + 1;
                $nextpageparams['useridlistid'] = optional_param('useridlistid', $this->get_useridlist_key_id(), PARAM_ALPHANUM);
            } else if (optional_param('savegrade', null, PARAM_RAW)) {
                // Save changes button.
                $action = 'grade';
                if ($this->process_save_grade($mform)) {
                    $action = 'redirect';
                    $nextpageparams['action'] = 'savegradingresult';
                }
            } else {
                // Cancel button.
                $action = 'redirect';
                $nextpageparams['action'] = 'grading';
            }
        } else if ($action == 'quickgrade') {
            $message = $this->process_save_quick_grades();
            $action = 'quickgradingresult';
        } else if ($action == 'saveoptions') {
            $this->process_save_grading_options();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'saveextension') {
            $action = 'grantextension';
            if ($this->process_save_extension($mform)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'grading';
            }
        } else if ($action == 'revealidentitiesconfirm') {
            $this->process_reveal_identities();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        }
		}
		
		$returnparams = array('rownum'=>optional_param('rownum', 0, PARAM_INT),
                              'useridlistid' => optional_param('useridlistid', $this->get_useridlist_key_id(), PARAM_ALPHANUM));
        $this->register_return_link($action, $returnparams);

        // Now show the right view page.
        if ($action == 'redirect') {
            $nextpageurl = new moodle_url('/mod/proassign/view.php', $nextpageparams);
            redirect($nextpageurl);
            return;
        } else if ($action == 'savegradingresult') {
            $message = get_string('gradingchangessaved', 'assign');
            $outpot .= $this->view_savegrading_result($message);
        } else if ($action == 'quickgradingresult') {
            $mform = null;
            $outpot .= $this->view_quickgrading_result($message);
        } else if ($action == 'grade') {
            $outpot .= $this->view_single_grade_page($mform);
        } else if ($action == 'viewpluginassignfeedback') {
            $outpot .= $this->view_plugin_content('assignfeedback');
        } else if ($action == 'viewpluginassignsubmission') {
            $outpot .= $this->view_plugin_content('assignsubmission');
        } else if ($action == 'editsubmission') {
            $outpot .= $this->view_edit_submission_page($mform, $notices);
        } else if ($action == 'grading') {
            $outpot .= $this->view_grading_page();
        } else if ($action == 'downloadall') {
            $outpot .= $this->download_submissions();
        } else if ($action == 'submit') {
            $outpot .= $this->check_submit_for_grading($mform);
        } else if ($action == 'grantextension') {
            $outpot .= $this->view_grant_extension($mform);
        } else if ($action == 'revealidentities') {
            $outpot .= $this->view_reveal_identities_confirm($mform);
        } else if ($action == 'plugingradingbatchoperation') {
            $outpot .= $this->view_plugin_grading_batch_operation($mform);
        } else if ($action == 'viewpluginpage') {
             $outpot .= $this->view_plugin_page();
        } else if ($action == 'viewcourseindex') {
             $outpot .= $this->view_course_index();
        } else if ($action == 'viewbatchsetmarkingworkflowstate') {
             $outpot .= $this->view_batch_set_workflow_state($mform);
        } else if ($action == 'viewbatchmarkingallocation') {
            $outpot .= $this->view_batch_markingallocation($mform);
        } else if ($action == 'viewsubmitforgradingerror') {
            $out .= $this->view_error_page(get_string('submitforgrading', 'assign'), $notices);
        } else if($action == 'testcases'){
			$out .= $this->view_test_cases();
		} else {
            $out .= $this->view_main_page();
        }

        return $out;		
		
	}	
	
	protected function view_main_page() {
        global $CFG, $DB, $USER, $PAGE;

        $instance = $this->get_instance();
        $out = '';
        $postfix = '';
		
        $out .= $this->get_renderer()->render(new proassign_header($instance, $this->get_context(), $this->show_intro(), $this->get_course_module()->id, '', '', $postfix));

		if($this->can_manage_assignment()){
			
		}
		
		
        if ($this->can_view_grades()) {
            $draft = 'draft';
            $submitted = 'submitted';

            // Group selector will only be displayed if necessary.
            $currenturl = new moodle_url('/mod/proassign/view.php', array('id' => $this->get_course_module()->id));
            $out .= groups_print_activity_menu($this->get_course_module(), $currenturl->out(), true);

            $activitygroup = groups_get_activity_group($this->get_course_module());

            
                // The active group has already been updated in groups_print_activity_menu().
                $countparticipants = $this->count_participants($activitygroup);
                /*$summary = new proassign_grading_summary($countparticipants,
                                                      $instance->submissiondrafts,
                                                      $this->count_submissions_with_status($draft),
                                                      $this->is_any_submission_plugin_enabled(),
                                                      $this->count_submissions_with_status($submitted),
                                                      $instance->cutoffdate,
                                                      $instance->duedate,
                                                      $this->get_course_module()->id,
                                                      $this->count_submissions_need_grading(),
                                                      null,
                                                      false);
                $out .= $this->get_renderer()->render($summary);*/
            
        }
		
        $grade = $this->get_user_grade($USER->id, false);
        $submission = $this->get_user_submission($USER->id, false);
		
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
		
		$out .= $this->get_renderer()->render(new proassign_test_case($instance, $this->get_context(), $this->get_course_module()->id, $this->can_manage_assignment()));
		
		$out .= $this->view_footer();
		
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
	
	public function get_user_grade($userid, $create, $attemptnumber=-1) {
        global $DB, $USER;

        // If the userid is not null then use userid.
        if (!$userid) {
            $userid = $USER->id;
        }
        $submission = null;

        $params = array('assignment'=>$this->get_instance()->id, 'userid'=>$userid);
        if ($attemptnumber < 0 || $create) {
            // Make sure this grade matches the latest submission attempt.
            $submission = $this->get_user_submission($userid, true);
            if ($submission) {
                $attemptnumber = $submission->attemptnumber;
            }
        }

        if ($attemptnumber >= 0) {
            $params['attemptnumber'] = $attemptnumber;
        }

        $grades = $DB->get_records('proassign_grades', $params, 'attemptnumber DESC', '*', 0, 1);

        if ($grades) {
            return reset($grades);
        }
        if ($create) {
            $grade = new stdClass();
            $grade->assignment   = $this->get_instance()->id;
            $grade->userid       = $userid;
            $grade->timecreated = time();
            // If we are "auto-creating" a grade - and there is a submission
            // the new grade should not have a more recent timemodified value
            // than the submission.
            if ($submission) {
                $grade->timemodified = $submission->timemodified;
            } else {
                $grade->timemodified = $grade->timecreated;
            }
            $grade->grade = -1;
            $grade->grader = $USER->id;
            if ($attemptnumber >= 0) {
                $grade->attemptnumber = $attemptnumber;
            }

            $gid = $DB->insert_record('proassign_grades', $grade);
            $grade->id = $gid;
            return $grade;
        }
        return false;
    }	
	
	public function get_user_submission($userid, $create, $attemptnumber=-1) {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }
        // If the userid is not null then use userid.
        $params = array('assignment'=>$this->get_instance()->id, 'userid'=>$userid);
        if ($attemptnumber >= 0) {
            $params['attemptnumber'] = $attemptnumber;
        }

        // Only return the row with the highest attemptnumber.
        $submission = null;
        $submissions = $DB->get_records('proassign_submission', $params, 'attemptnumber DESC', '*', 0, 1);
        if ($submissions) {
            $submission = reset($submissions);
        }

        if ($submission) {
            return $submission;
        }
        if ($create) {
            $submission = new stdClass();
            $submission->assignment   = $this->get_instance()->id;
            $submission->userid       = $userid;
            $submission->timecreated = time();
            $submission->timemodified = $submission->timecreated;
            $submission->status = 'new';
            if ($attemptnumber >= 0) {
                $submission->attemptnumber = $attemptnumber;
            } else {
                $submission->attemptnumber = 0;
            }
            // Work out if this is the latest submission.
            $submission->latest = 0;
            $params = array('assignment'=>$this->get_instance()->id, 'userid'=>$userid);
            if ($attemptnumber == -1) {
                // This is a new submission so it must be the latest.
                $submission->latest = 1;
            } else {
                // We need to work this out.
                $result = $DB->get_records('proassign_submission', $params, 'attemptnumber DESC', 'attemptnumber', 0, 1);
                $latestsubmission = null;
                if ($result) {
                    $latestsubmission = reset($result);
                }
                if (empty($latestsubmission) || ($attemptnumber > $latestsubmission->attemptnumber)) {
                    $submission->latest = 1;
                }
            }
            if ($submission->latest) {
                // This is the case when we need to set latest to 0 for all the other attempts.
                $DB->set_field('proassign_submission', 'latest', 0, $params);
            }
            $sid = $DB->insert_record('proassign_submission', $submission);
            return $DB->get_record('proassign_submission', array('id' => $sid));
        }
        return false;
    }	
	
	public function view_student_summary($user, $showlinks) {
        global $CFG, $DB, $PAGE;

        $instance = $this->get_instance();
        $grade = $this->get_user_grade($user->id, false);
        //$flags = $this->get_user_flags($user->id, false);
        $submission = $this->get_user_submission($user->id, false);
        $o = '';

        $teamsubmission = null;
        $submissiongroup = null;
        $notsubmitted = array();

        if ($this->can_view_submission($user->id)) {
            $showedit = $showlinks &&
                        ($this->is_any_submission_plugin_enabled()) &&
                        $this->can_edit_submission($user->id);

            //$gradelocked = ($flags && $flags->locked) || $this->grading_disabled($user->id, false);
			$gradelocked = $this->grading_disabled($user->id, false);

            // Grading criteria preview.
            $gradingmanager = get_grading_manager($this->context, 'mod_proassign', 'submissions');
            $gradingcontrollerpreview = '';
            if ($gradingmethod = $gradingmanager->get_active_method()) {
                $controller = $gradingmanager->get_controller($gradingmethod);
                if ($controller->is_form_defined()) {
                    $gradingcontrollerpreview = $controller->render_preview($PAGE);
                }
            }

            $showsubmit = ($showlinks && $this->submissions_open($user->id));
            $showsubmit = ($showsubmit && $this->show_submit_button($submission, $teamsubmission, $user->id));

            $extensionduedate = null;
            /*if ($flags) {
                $extensionduedate = $flags->extensionduedate;
            }*/
            $viewfullnames = has_capability('moodle/site:viewfullnames', $this->get_course_context());

            $gradingstatus = $this->get_grading_status($user->id);
            //$usergroups = $this->get_all_groups($user->id);
            $submissionstatus = new proassign_submission_status($instance->allowsubmissionsfromdate,
                                                              $instance->alwaysshowdescription,
                                                              $submission,
                                                              null,
                                                              $teamsubmission,
                                                              $submissiongroup,
                                                              $notsubmitted,
                                                              $this->is_any_submission_plugin_enabled(),
                                                              $gradelocked,
                                                              $this->is_graded($user->id),
                                                              $instance->duedate,
                                                              $instance->cutoffdate,
                                                              $this->get_submission_plugins(),
                                                              $this->get_return_action(),
                                                              $this->get_return_params(),
                                                              $this->get_course_module()->id,
                                                              $this->get_course()->id,
                                                              proassign_submission_status::STUDENT_VIEW,
                                                              $showedit,
                                                              $showsubmit,
                                                              $viewfullnames,
                                                              $extensionduedate,
                                                              $this->get_context(),
                                                              $this->is_blind_marking(),
                                                              $gradingcontrollerpreview,
                                                              $instance->attemptreopenmethod,
                                                              $instance->maxattempts,
                                                              $gradingstatus,
                                                              $instance->preventsubmissionnotingroup,null);
                                                              //$usergroups);
            if (has_capability('mod/proassign:submit', $this->get_context(), $user)) {
                $o .= $this->get_renderer()->render($submissionstatus);
            }

            require_once($CFG->libdir.'/gradelib.php');
            require_once($CFG->dirroot.'/grade/grading/lib.php');

            $gradinginfo = grade_get_grades($this->get_course()->id,
                                        'mod',
                                        'proassign',
                                        $instance->id,
                                        $user->id);

            $gradingitem = null;
            $gradebookgrade = null;
            if (isset($gradinginfo->items[0])) {
                $gradingitem = $gradinginfo->items[0];
                $gradebookgrade = $gradingitem->grades[$user->id];
            }

            // Check to see if all feedback plugins are empty.
            $emptyplugins = true;
            if ($grade) {
                foreach ($this->get_feedback_plugins() as $plugin) {
                    if ($plugin->is_visible() && $plugin->is_enabled()) {
                        if (!$plugin->is_empty($grade)) {
                            $emptyplugins = false;
                        }
                    }
                }
            }

            if ($this->get_instance()->markingworkflow && $gradingstatus != ASSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
                $emptyplugins = true; // Don't show feedback plugins until released either.
            }

            $cangrade = has_capability('mod/proassign:grade', $this->get_context());
            // If there is a visible grade, show the summary.
            if ((!is_null($gradebookgrade->grade) || !$emptyplugins)
                    && ($cangrade || !$gradebookgrade->hidden)) {

                $gradefordisplay = null;
                $gradeddate = null;
                $grader = null;
                $gradingmanager = get_grading_manager($this->get_context(), 'mod_proassign', 'submissions');

                // Only show the grade if it is not hidden in gradebook.
                if (!is_null($gradebookgrade->grade) && ($cangrade || !$gradebookgrade->hidden)) {
                    if ($controller = $gradingmanager->get_active_controller()) {
                        $menu = make_grades_menu($this->get_instance()->grade);
                        $controller->set_grade_range($menu, $this->get_instance()->grade > 0);
                        $gradefordisplay = $controller->render_grade($PAGE,
                                                                     $grade->id,
                                                                     $gradingitem,
                                                                     $gradebookgrade->str_long_grade,
                                                                     $cangrade);
                    } else {
                        $gradefordisplay = $this->display_grade($gradebookgrade->grade, false);
                    }
                    $gradeddate = $gradebookgrade->dategraded;
                    if (isset($grade->grader)) {
                        $grader = $DB->get_record('user', array('id'=>$grade->grader));
                    }
                }

                $feedbackstatus = new assign_feedback_status($gradefordisplay,
                                                      $gradeddate,
                                                      $grader,
                                                      $this->get_feedback_plugins(),
                                                      $grade,
                                                      $this->get_course_module()->id,
                                                      $this->get_return_action(),
                                                      $this->get_return_params());

                $o .= $this->get_renderer()->render($feedbackstatus);
            }

            $allsubmissions = $this->get_all_submissions($user->id);

            if (count($allsubmissions) > 1) {
                $allgrades = $this->get_all_grades($user->id);
                $history = new assign_attempt_history($allsubmissions,
                                                      $allgrades,
                                                      $this->get_submission_plugins(),
                                                      $this->get_feedback_plugins(),
                                                      $this->get_course_module()->id,
                                                      $this->get_return_action(),
                                                      $this->get_return_params(),
                                                      false,
                                                      0,
                                                      0);

                $o .= $this->get_renderer()->render($history);
            }

        }
        return $o;
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
	
	protected function is_graded($userid) {
        $grade = $this->get_user_grade($userid, false);
        if ($grade) {
            return ($grade->grade !== null && $grade->grade >= 0);
        }
        return false;
    }
	
	public function is_blind_marking() {
        return $this->get_instance()->blindmarking && !$this->get_instance()->revealidentities;
    }
	
	protected function get_all_submissions($userid) {
        global $DB, $USER;

        // If the userid is not null then use userid.
        if (!$userid) {
            $userid = $USER->id;
        }

        $params = array();

		
            // Params to get the user submissions.
            $params = array('assignment'=>$this->get_instance()->id, 'userid'=>$userid);
        

        // Return the submissions ordered by attempt.
        $submissions = $DB->get_records('proassign_submission', $params, 'attemptnumber ASC');

        return $submissions;
    }
	
	public function get_grading_status($userid) {
        /*if ($this->get_instance()->markingworkflow) {
            $flags = $this->get_user_flags($userid, false);
            if (!empty($flags->workflowstate)) {
                return $flags->workflowstate;
            }
            return ASSIGN_MARKING_WORKFLOW_STATE_NOTMARKED;
        } else {*/
            $attemptnumber = optional_param('attemptnumber', -1, PARAM_INT);
            $grade = $this->get_user_grade($userid, false, $attemptnumber);

            if (!empty($grade) && $grade->grade !== null && $grade->grade >= 0) {
                return 'graded';
            } else {
                return 'notgraded';
            }
        //}
    }
	
	public function submissions_open($userid = 0, $skipenrolled = false, $submission = false, $flags = false, $gradinginfo = false) {
        global $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $time = time();
        $dateopen = true;
        $finaldate = false;
        if ($this->get_instance()->cutoffdate) {
            $finaldate = $this->get_instance()->cutoffdate;
        }

        /*if ($flags === false) {
            $flags = $this->get_user_flags($userid, false);
        }
        if ($flags && $flags->locked) {
            return false;
        }*/

        // User extensions.
        if ($finaldate) {
            if ($flags && $flags->extensionduedate) {
                // Extension can be before cut off date.
                if ($flags->extensionduedate > $finaldate) {
                    $finaldate = $flags->extensionduedate;
                }
            }
        }

        if ($finaldate) {
            $dateopen = ($this->get_instance()->allowsubmissionsfromdate <= $time && $time <= $finaldate);
        } else {
            $dateopen = ($this->get_instance()->allowsubmissionsfromdate <= $time);
        }

        if (!$dateopen) {
            return false;
        }

        // Now check if this user has already submitted etc.
        if (!$skipenrolled && !is_enrolled($this->get_course_context(), $userid)) {
            return false;
        }
        // Note you can pass null for submission and it will not be fetched.
        if ($submission === false) {
			
                $submission = $this->get_user_submission($userid, false);
            
        }
        if ($submission) {

            if ($this->get_instance()->submissiondrafts && $submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                // Drafts are tracked and the student has submitted the assignment.
                return false;
            }
        }

        // See if this user grade is locked in the gradebook.
        if ($gradinginfo === false) {
            $gradinginfo = grade_get_grades($this->get_course()->id,
                                            'mod',
                                            'assign',
                                            $this->get_instance()->id,
                                            array($userid));
        }
        if ($gradinginfo &&
                isset($gradinginfo->items[0]->grades[$userid]) &&
                $gradinginfo->items[0]->grades[$userid]->locked) {
            return false;
        }

        return true;
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
	
	protected function show_submit_button($submission = null, $teamsubmission = null, $userid = null) {
        if ($teamsubmission) {
            if ($teamsubmission->status === 'submitted') {
                // The assignment submission has been completed.
                return false;
            } else if ($this->submission_empty($teamsubmission)) {
                // There is nothing to submit yet.
                return false;
            } else if ($submission && $submission->status === 'submitted') {
                // The user has already clicked the submit button on the team submission.
                return false;
            } else if (
                !empty($this->get_instance()->preventsubmissionnotingroup)
                && $this->get_submission_group($userid) == false
            ) {
                return false;
            }
        } else if ($submission) {
            if ($submission->status === 'submitted') {
                // The assignment submission has been completed.
                return false;
            } else if ($this->submission_empty($submission)) {
                // There is nothing to submit.
                return false;
            }
        } else {
            // We've not got a valid submission or team submission.
            return false;
        }
        // Last check is that this instance allows drafts.
        return $this->get_instance()->submissiondrafts;
    }
	
	public function submission_empty($submission) {
        $allempty = true;

        foreach ($this->submissionplugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                if (!$allempty || !$plugin->is_empty($submission)) {
                    $allempty = false;
                }
            }
        }
        return $allempty;
    }
	
	public function grading_disabled($userid, $checkworkflow=true) {
        global $CFG;
        if ($checkworkflow && $this->get_instance()->markingworkflow) {
            $grade = $this->get_user_grade($userid, false);
            $validstates = $this->get_marking_workflow_states_for_current_user();
            if (!empty($grade) && !empty($grade->workflowstate) && !array_key_exists($grade->workflowstate, $validstates)) {
                return true;
            }
        }
        $gradinginfo = grade_get_grades($this->get_course()->id,
                                        'mod',
                                        'proassign',
                                        $this->get_instance()->id,
                                        array($userid));
        if (!$gradinginfo) {
            return false;
        }

        if (!isset($gradinginfo->items[0]->grades[$userid])) {
            return false;
        }
        $gradingdisabled = $gradinginfo->items[0]->grades[$userid]->locked ||
                           $gradinginfo->items[0]->grades[$userid]->overridden;
        return $gradingdisabled;
    }
	
	public function get_user_flags($userid, $create) {
        global $DB, $USER;

        // If the userid is not null then use userid.
        if (!$userid) {
            $userid = $USER->id;
        }

        $params = array('assignment'=>$this->get_instance()->id, 'userid'=>$userid);

        $flags = $DB->get_record('proassign_user_flags', $params);

        if ($flags) {
            return $flags;
        }
        if ($create) {
            $flags = new stdClass();
            $flags->assignment = $this->get_instance()->id;
            $flags->userid = $userid;
            $flags->locked = 0;
            $flags->extensionduedate = 0;
            $flags->workflowstate = '';
            $flags->allocatedmarker = 0;

            // The mailed flag can be one of 3 values: 0 is unsent, 1 is sent and 2 is do not send yet.
            // This is because students only want to be notified about certain types of update (grades and feedback).
            $flags->mailed = 2;

            $fid = $DB->insert_record('proassign_user_flags', $flags);
            $flags->id = $fid;
            return $flags;
        }
        return false;
    }	
		
	public function register_return_link($action, $params) {
        global $PAGE;
        $params['action'] = $action;
        $currenturl = $PAGE->url;

        $currenturl->params($params);
        $PAGE->set_url($currenturl);
    }
	
	protected function has_visible_attachments() {
        return ($this->count_attachments() > 0);
    }
	
	protected function count_attachments() {

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->get_context()->id, 'mod_proassign', ASSIGN_INTROATTACHMENT_FILEAREA,
                        0, 'id', false);

        return count($files);
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
	
	public function count_participants($currentgroup) {
        return count($this->list_participants($currentgroup, true));
    }
	
	public function list_participants($currentgroup, $idsonly) {

        if (empty($currentgroup)) {
            $currentgroup = 0;
        }

        $key = $this->context->id . '-' . $currentgroup . '-' . $this->show_only_active_users();
        if (!isset($this->participants[$key])) {
            $users = get_enrolled_users($this->context, 'mod/proassign:submit', $currentgroup, 'u.*', null, null, null,
                    $this->show_only_active_users());

            $cm = $this->get_course_module();
            $info = new \core_availability\info_module($cm);
            $users = $info->filter_user_list($users);

            $this->participants[$key] = $users;
        }

        if ($idsonly) {
            $idslist = array();
            foreach ($this->participants[$key] as $id => $user) {
                $idslist[$id] = new stdClass();
                $idslist[$id]->id = $id;
            }
            return $idslist;
        }
        return $this->participants[$key];
    }
	
	public function show_only_active_users() {
        global $CFG;

        if (is_null($this->showonlyactiveenrol)) {
            $defaultgradeshowactiveenrol = !empty($CFG->grade_report_showonlyactiveenrol);
            $this->showonlyactiveenrol = get_user_preferences('grade_report_showonlyactiveenrol', $defaultgradeshowactiveenrol);

            if (!is_null($this->context)) {
                $this->showonlyactiveenrol = $this->showonlyactiveenrol ||
                            !has_capability('moodle/course:viewsuspendedusers', $this->context);
            }
        }
        return $this->showonlyactiveenrol;
    }
	
	public function count_submissions_with_status($status) {
        global $DB;

        $currentgroup = groups_get_activity_group($this->get_course_module(), true);
        list($esql, $params) = get_enrolled_sql($this->get_context(), 'mod/proassign:submit', $currentgroup, true);

        $params['assignid'] = $this->get_instance()->id;
        $params['assignid2'] = $this->get_instance()->id;
        $params['submissionstatus'] = $status;

        
            $sql = 'SELECT COUNT(s.userid)
                        FROM {proassign_submission} s
                        JOIN(' . $esql . ') e ON e.id = s.userid
                        WHERE
                            s.latest = 1 AND
                            s.assignment = :assignid AND
                            s.timemodified IS NOT NULL AND
                            s.status = :submissionstatus';

        

        return $DB->count_records_sql($sql, $params);
    }
	
	public function is_any_submission_plugin_enabled() {
        if (!isset($this->cache['any_submission_plugin_enabled'])) {
            $this->cache['any_submission_plugin_enabled'] = false;
            foreach ($this->submissionplugins as $plugin) {
                if ($plugin->is_enabled() && $plugin->is_visible() && $plugin->allow_submissions()) {
                    $this->cache['any_submission_plugin_enabled'] = true;
                    break;
                }
            }
        }

        return $this->cache['any_submission_plugin_enabled'];

    }	
	
	public function count_submissions_need_grading() {
        global $DB;

        $currentgroup = groups_get_activity_group($this->get_course_module(), true);
        list($esql, $params) = get_enrolled_sql($this->get_context(), 'mod/proassign:submit', $currentgroup, true);

        $params['assignid'] = $this->get_instance()->id;
        $params['submitted'] = 'submitted';

        $sql = 'SELECT COUNT(s.userid)
                   FROM {proassign_submission} s
                   LEFT JOIN {assign_grades} g ON
                        s.assignment = g.assignment AND
                        s.userid = g.userid AND
                        g.attemptnumber = s.attemptnumber
                   JOIN(' . $esql . ') e ON e.id = s.userid
                   WHERE
                        s.latest = 1 AND
                        s.assignment = :assignid AND
                        s.timemodified IS NOT NULL AND
                        s.status = :submitted AND
                        (s.timemodified >= g.timemodified OR g.timemodified IS NULL OR g.grade IS NULL)';

        return $DB->count_records_sql($sql, $params);
    }
}

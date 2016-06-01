12<?php

defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot . '/mod/proassign/renderable.php');
require_once($CFG->dirroot . '/mod/proassign/renderer.php');

class proassign{
	
	/** @var stdClass the assignment record that contains the global settings for this assign instance */
    private $instance;

    /** @var stdClass the grade_item record for this assign instance's primary grade item. */
    private $gradeitem;

    /** @var context the context of the course module for this assign instance
     *               (or just the course if we are creating a new one)
     */
    private $context;

    /** @var stdClass the course this assign instance belongs to */
    private $course;

    /** @var stdClass the admin config for all assign instances  */
    private $adminconfig;

    /** @var assign_renderer the custom renderer for this module */
    private $output;

    /** @var cm_info the course module for this assign instance */
    private $coursemodule;

    /** @var array cache for things like the coursemodule name or the scale menu -
     *             only lives for a single request.
     */
    private $cache;

    /** @var array list of the installed submission plugins */
    private $submissionplugins;

    /** @var array list of the installed feedback plugins */
    private $feedbackplugins;

    /** @var string action to be used to return to this page
     *              (without repeating any form submissions etc).
     */
    private $returnaction = 'view';

    /** @var array params to be used to return to this page */
    private $returnparams = array();

    /** @var string modulename prevents excessive calls to get_string */
    private static $modulename = null;

    /** @var string modulenameplural prevents excessive calls to get_string */
    private static $modulenameplural = null;

    /** @var array of marking workflow states for the current user */
    private $markingworkflowstates = null;

    /** @var bool whether to exclude users with inactive enrolment */
    private $showonlyactiveenrol = null;

    /** @var string A key used to identify userlists created by this object. */
    private $useridlistid = null;

    /** @var array cached list of participants for this assignment. The cache key will be group, showactive and the context id */
    private $participants = array();

    /** @var array cached list of user groups when team submissions are enabled. The cache key will be the user. */
    private $usersubmissiongroups = array();

    /** @var array cached list of user groups. The cache key will be the user. */
    private $usergroups = array();

    /** @var array cached list of IDs of users who share group membership with the user. The cache key will be the user. */
    private $sharedgroupmembers = array();
	
	
	public function __construct($coursemodulecontext, $coursemodule, $course) {
        global $SESSION;

        $this->context = $coursemodulecontext;
        $this->course = $course;

        // Ensure that $this->coursemodule is a cm_info object (or null).
        $this->coursemodule = cm_info::create($coursemodule);

        // Temporary cache only lives for a single request - used to reduce db lookups.
        $this->cache = array();

        $this->submissionplugins = $this->load_plugins('proassignsubmission');
        $this->feedbackplugins = $this->load_plugins('proassignfeedback');
		
        // Extra entropy is required for uniqid() to work on cygwin.
        $this->useridlistid = clean_param(uniqid('', true), PARAM_ALPHANUM);

        if (!isset($SESSION->mod_proassign_useridlist)) {
            $SESSION->mod_proassign_useridlist = [];
        }
    }
	
	protected function load_plugins($subtype) {
        global $CFG;
        $result = array();

        $names = core_component::get_plugin_list($subtype);

        foreach ($names as $name => $path) {
            if (file_exists($path . '/locallib.php')) {
                require_once($path . '/locallib.php');

                $shortsubtype = substr($subtype, strlen('assign'));
                $pluginclass = 'assign_' . $shortsubtype . '_' . $name;

                $plugin = new $pluginclass($this, $name);

                if ($plugin instanceof assign_plugin) {
                    $idx = $plugin->get_sort_order();
                    while (array_key_exists($idx, $result)) {
                        $idx +=1;
                    }
                    $result[$idx] = $plugin;
                }
            }
        }
        ksort($result);
        return $result;
    }
	
	public function view($action='') {
		
		$outpot = '';
        $mform = null;
        $notices = array();
        $nextpageparams = array();
		
		if (!empty($this->get_course_module()->id)) {
            $nextpageparams['id'] = $this->get_course_module()->id;
        }
				
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
		
		$returnparams = array('rownum'=>optional_param('rownum', 0, PARAM_INT),
                              'useridlistid' => optional_param('useridlistid', $this->get_useridlist_key_id(), PARAM_ALPHANUM));
        $this->register_return_link($action, $returnparams);

        // Now show the right view page.
        if ($action == 'redirect') {
            $nextpageurl = new moodle_url('/mod/assign/view.php', $nextpageparams);
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
            $outpot .= $this->view_error_page(get_string('submitforgrading', 'assign'), $notices);
        } else {
            $outpot .= $this->view_submission_page();
        }

        return $outpot;
		
		
	}
	
	
	
	protected function view_submission_page() {
        global $CFG, $DB, $USER, $PAGE;

        $instance = $this->get_instance();

        $out = '';

        $postfix = '';
		
        /*if ($this->has_visible_attachments()) {
            $postfix = $this->render_area_files('mod_proassign', ASSIGN_INTROATTACHMENT_FILEAREA, 0);
        }*/
		
        $out .= $this->get_renderer()->render(new proassign_header($instance, $this->get_context(), $this->show_intro(), $this->get_course_module()->id, '', '', $postfix));

        // Display plugin specific headers.
        /*$plugins = array_merge($this->get_submission_plugins(), $this->get_feedback_plugins());
        foreach ($plugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                $o .= $this->get_renderer()->render(new assign_plugin_header($plugin));
            }
        }*/

        if ($this->can_view_grades()) {
            $draft = 'draft';
            $submitted = 'submitted';

            // Group selector will only be displayed if necessary.
            $currenturl = new moodle_url('/mod/proassign/view.php', array('id' => $this->get_course_module()->id));
            $out .= groups_print_activity_menu($this->get_course_module(), $currenturl->out(), true);

            $activitygroup = groups_get_activity_group($this->get_course_module());

            if ($instance->teamsubmission) {
                $defaultteammembers = $this->get_submission_group_members(0, true);
                $warnofungroupedusers = (count($defaultteammembers) > 0 && $instance->preventsubmissionnotingroup);

                $summary = new proassign_grading_summary($this->count_teams($activitygroup),
                                                      $instance->submissiondrafts,
                                                      $this->count_submissions_with_status($draft),
                                                      $this->is_any_submission_plugin_enabled(),
                                                      $this->count_submissions_with_status($submitted),
                                                      $instance->cutoffdate,
                                                      $instance->duedate,
                                                      $this->get_course_module()->id,
                                                      $this->count_submissions_need_grading(),
                                                      $instance->teamsubmission,
                                                      $warnofungroupedusers);
                $out .= $this->get_renderer()->render($summary);
            } else {
                // The active group has already been updated in groups_print_activity_menu().
                $countparticipants = $this->count_participants($activitygroup);
                $summary = new proassign_grading_summary($countparticipants,
                                                      $instance->submissiondrafts,
                                                      $this->count_submissions_with_status($draft),
                                                      $this->is_any_submission_plugin_enabled(),
                                                      $this->count_submissions_with_status($submitted),
                                                      $instance->cutoffdate,
                                                      $instance->duedate,
                                                      $this->get_course_module()->id,
                                                      $this->count_submissions_need_grading(),
                                                      $instance->teamsubmission,
                                                      false);
                $out .= $this->get_renderer()->render($summary);
            }
        }
		
		
        $grade = $this->get_user_grade($USER->id, false);
		print_r("Here");
        $submission = $this->get_user_submission($USER->id, false);
		
        if ($this->can_view_submission($USER->id)) {
            $out .= $this->view_student_summary($USER, true);
        }
		
        $out .= $this->view_footer();

        \mod_proassign\event\submission_status_viewed::create_from_proassign($this)->trigger();

        return $o;
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
        $params = array('assignment'=>$this->get_instance()->id, 'userid'=>$userid, 'groupid'=>0);
        if ($attemptnumber >= 0) {
            $params['attemptnumber'] = $attemptnumber;
        }

        // Only return the row with the highest attemptnumber.
        $submission = null;print("dssdf");
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
            $submission->status = ASSIGN_SUBMISSION_STATUS_NEW;
            if ($attemptnumber >= 0) {
                $submission->attemptnumber = $attemptnumber;
            } else {
                $submission->attemptnumber = 0;
            }
            // Work out if this is the latest submission.
            $submission->latest = 0;
            $params = array('assignment'=>$this->get_instance()->id, 'userid'=>$userid, 'groupid'=>0);
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
        $flags = $this->get_user_flags($user->id, false);
        $submission = $this->get_user_submission($user->id, false);
        $o = '';

        $teamsubmission = null;
        $submissiongroup = null;
        $notsubmitted = array();
        if ($instance->teamsubmission) {
            $teamsubmission = $this->get_group_submission($user->id, 0, false);
            $submissiongroup = $this->get_submission_group($user->id);
            $groupid = 0;
            if ($submissiongroup) {
                $groupid = $submissiongroup->id;
            }
            $notsubmitted = $this->get_submission_group_members_who_have_not_submitted($groupid, false);
        }

        if ($this->can_view_submission($user->id)) {
            $showedit = $showlinks &&
                        ($this->is_any_submission_plugin_enabled()) &&
                        $this->can_edit_submission($user->id);

            $gradelocked = ($flags && $flags->locked) || $this->grading_disabled($user->id, false);

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
            if ($flags) {
                $extensionduedate = $flags->extensionduedate;
            }
            $viewfullnames = has_capability('moodle/site:viewfullnames', $this->get_course_context());

            $gradingstatus = $this->get_grading_status($user->id);
            $usergroups = $this->get_all_groups($user->id);
            $submissionstatus = new assign_submission_status($instance->allowsubmissionsfromdate,
                                                              $instance->alwaysshowdescription,
                                                              $submission,
                                                              $instance->teamsubmission,
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
                                                              assign_submission_status::STUDENT_VIEW,
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
                                                              $instance->preventsubmissionnotingroup,
                                                              $usergroups);
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
	
	public function register_return_link($action, $params) {
        global $PAGE;
        $params['action'] = $action;
        $currenturl = $PAGE->url;

        $currenturl->params($params);
        $PAGE->set_url($currenturl);
    }
	
	public function get_instance() {
        global $DB;
        if ($this->instance) {
            return $this->instance;
        }
        if ($this->get_course_module()) {
            $params = array('id' => $this->get_course_module()->instance);
            $this->instance = $DB->get_record('assign', $params, '*', MUST_EXIST);
        }
        if (!$this->instance) {
            throw new coding_exception('Improper use of the assignment class. ' .
                                       'Cannot load the assignment record.');
        }
        return $this->instance;
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
        if ($this->get_instance()->alwaysshowdescription ||
                time() > $this->get_instance()->allowsubmissionsfromdate) {
            return true;
        }
        return false;
    }
	
	public function can_view_grades() {
        // Permissions check.
        if (!has_any_capability(array('mod/proassign:viewgrades', 'mod/proassign:grade'), $this->context)) {
            return false;
        }

        return true;
    }
	
}

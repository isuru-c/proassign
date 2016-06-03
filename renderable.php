<?php


defined('MOODLE_INTERNAL') || die();

class proassign_header implements renderable {
	
    public $proassign = null;
    public $context = null;
    public $showintro = false;
    public $coursemoduleid = 0;
    public $subpage = '';
    public $preface = '';
    public $postfix = '';

    public function __construct(stdClass $proassign, $context, $showintro, $coursemoduleid, $subpage='', $preface='', $postfix='') {
        $this->proassign = $proassign;
        $this->context = $context;
        $this->showintro = $showintro;
        $this->coursemoduleid = $coursemoduleid;
        $this->subpage = $subpage;
        $this->preface = $preface;
        $this->postfix = $postfix;
    }
}

class proassign_test_case implements renderable {
	
	public $proassign = null;
    public $context = null;
    public $coursemoduleid = 0;
	public $editmode = false;
	
	public function __construct(stdClass $proassign, $context, $coursemoduleid, $editmode) {
        $this->proassign = $proassign;
        $this->context = $context;
        $this->coursemoduleid = $coursemoduleid;
		$this->editmode = $editmode;
    }
}

class proassign_new_test_case implements renderable {
	
	public $proassign = null;
    public $context = null;
    public $coursemoduleid = 0;
	public $editmode = false;
	
	public function __construct(stdClass $proassign, $context, $coursemoduleid, $editmode) {
        $this->proassign = $proassign;
        $this->context = $context;
        $this->coursemoduleid = $coursemoduleid;
		$this->editmode = $editmode;
    }
}


class proassign_submit_for_grading_page implements renderable {
    /** @var array $notifications is a list of notification messages returned from the plugins */
    public $notifications = array();
    /** @var int $coursemoduleid */
    public $coursemoduleid = 0;
    /** @var moodleform $confirmform */
    public $confirmform = null;

    public function __construct($notifications, $coursemoduleid, $confirmform) {
        $this->notifications = $notifications;
        $this->coursemoduleid = $coursemoduleid;
        $this->confirmform = $confirmform;
    }

}



class proassign_gradingmessage implements renderable {
    /** @var string $heading is the heading to display to the user */
    public $heading = '';
    /** @var string $message is the message to display to the user */
    public $message = '';
    /** @var int $coursemoduleid */
    public $coursemoduleid = 0;
    /** @var int $gradingerror should be set true if there was a problem grading */
    public $gradingerror = null;

    public function __construct($heading, $message, $coursemoduleid, $gradingerror = false, $page = null) {
        $this->heading = $heading;
        $this->message = $message;
        $this->coursemoduleid = $coursemoduleid;
        $this->gradingerror = $gradingerror;
        $this->page = $page;
    }

}



class proassign_form implements renderable {
    /** @var moodleform $form is the edit submission form */
    public $form = null;
    /** @var string $classname is the name of the class to proassign to the container */
    public $classname = '';
    /** @var string $jsinitfunction is an optional js function to add to the page requires */
    public $jsinitfunction = '';

    public function __construct($classname, moodleform $form, $jsinitfunction = '') {
        $this->classname = $classname;
        $this->form = $form;
        $this->jsinitfunction = $jsinitfunction;
    }

}



class proassign_user_summary implements renderable {
    /** @var stdClass $user suitable for rendering with user_picture and fullname(). */
    public $user = null;
    /** @var int $courseid */
    public $courseid;
    /** @var bool $viewfullnames */
    public $viewfullnames = false;
    /** @var bool $blindmarking */
    public $blindmarking = false;
    /** @var int $uniqueidforuser */
    public $uniqueidforuser;
    /** @var array $extrauserfields */
    public $extrauserfields;
    /** @var bool $suspendeduser */
    public $suspendeduser;


    public function __construct(stdClass $user,
                                $courseid,
                                $viewfullnames,
                                $blindmarking,
                                $uniqueidforuser,
                                $extrauserfields,
                                $suspendeduser = false) {
        $this->user = $user;
        $this->courseid = $courseid;
        $this->viewfullnames = $viewfullnames;
        $this->blindmarking = $blindmarking;
        $this->uniqueidforuser = $uniqueidforuser;
        $this->extrauserfields = $extrauserfields;
        $this->suspendeduser = $suspendeduser;
    }
}



class proassign_feedback_plugin_feedback implements renderable {
    /** @var int SUMMARY */
    const SUMMARY                = 10;
    /** @var int FULL */
    const FULL                   = 20;

    /** @var proassign_submission_plugin $plugin */
    public $plugin = null;
    /** @var stdClass $grade */
    public $grade = null;
    /** @var string $view */
    public $view = self::SUMMARY;
    /** @var int $coursemoduleid */
    public $coursemoduleid = 0;
    /** @var string returnaction The action to take you back to the current page */
    public $returnaction = '';
    /** @var array returnparams The params to take you back to the current page */
    public $returnparams = array();

    public function __construct(proassign_feedback_plugin $plugin,
                                stdClass $grade,
                                $view,
                                $coursemoduleid,
                                $returnaction,
                                $returnparams) {
        $this->plugin = $plugin;
        $this->grade = $grade;
        $this->view = $view;
        $this->coursemoduleid = $coursemoduleid;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
    }

}



class proassign_submission_plugin_submission implements renderable {
    /** @var int SUMMARY */
    const SUMMARY                = 10;
    /** @var int FULL */
    const FULL                   = 20;

    /** @var proassign_submission_plugin $plugin */
    public $plugin = null;
    /** @var stdClass $submission */
    public $submission = null;
    /** @var string $view */
    public $view = self::SUMMARY;
    /** @var int $coursemoduleid */
    public $coursemoduleid = 0;
    /** @var string returnaction The action to take you back to the current page */
    public $returnaction = '';
    /** @var array returnparams The params to take you back to the current page */
    public $returnparams = array();

    public function __construct(proassign_submission_plugin $plugin,
                                stdClass $submission,
                                $view,
                                $coursemoduleid,
                                $returnaction,
                                $returnparams) {
        $this->plugin = $plugin;
        $this->submission = $submission;
        $this->view = $view;
        $this->coursemoduleid = $coursemoduleid;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
    }
}


class proassign_feedback_status implements renderable {

    /** @var stding $gradefordisplay the student grade rendered into a format suitable for display */
    public $gradefordisplay = '';
    /** @var mixed the graded date (may be null) */
    public $gradeddate = 0;
    /** @var mixed the grader (may be null) */
    public $grader = null;
    /** @var array feedbackplugins - array of feedback plugins */
    public $feedbackplugins = array();
    /** @var stdClass proassign_grade record */
    public $grade = null;
    /** @var int coursemoduleid */
    public $coursemoduleid = 0;
    /** @var string returnaction */
    public $returnaction = '';
    /** @var array returnparams */
    public $returnparams = array();
	
    public function __construct($gradefordisplay,
                                $gradeddate,
                                $grader,
                                $feedbackplugins,
                                $grade,
                                $coursemoduleid,
                                $returnaction,
                                $returnparams) {
        $this->gradefordisplay = $gradefordisplay;
        $this->gradeddate = $gradeddate;
        $this->grader = $grader;
        $this->feedbackplugins = $feedbackplugins;
        $this->grade = $grade;
        $this->coursemoduleid = $coursemoduleid;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
    }
}



class proassign_submission_status implements renderable {
    /** @var int STUDENT_VIEW */
    const STUDENT_VIEW     = 10;
    /** @var int GRADER_VIEW */
    const GRADER_VIEW      = 20;

    /** @var int allowsubmissionsfromdate */
    public $allowsubmissionsfromdate = 0;
    /** @var bool alwaysshowdescription */
    public $alwaysshowdescription = false;
    /** @var stdClass the submission info (may be null) */
    public $submission = null;
    /** @var boolean teamsubmissionenabled - true or false */
    public $teamsubmissionenabled = false;
    /** @var stdClass teamsubmission the team submission info (may be null) */
    public $teamsubmission = null;
    /** @var stdClass submissiongroup the submission group info (may be null) */
    public $submissiongroup = null;
    /** @var array submissiongroupmemberswhoneedtosubmit list of users who still need to submit */
    public $submissiongroupmemberswhoneedtosubmit = array();
    /** @var bool submissionsenabled */
    public $submissionsenabled = false;
    /** @var bool locked */
    public $locked = false;
    /** @var bool graded */
    public $graded = false;
    /** @var int duedate */
    public $duedate = 0;
    /** @var int cutoffdate */
    public $cutoffdate = 0;
    /** @var array submissionplugins - the list of submission plugins */
    public $submissionplugins = array();
    /** @var string returnaction */
    public $returnaction = '';
    /** @var string returnparams */
    public $returnparams = array();
    /** @var int courseid */
    public $courseid = 0;
    /** @var int coursemoduleid */
    public $coursemoduleid = 0;
    /** @var int the view (STUDENT_VIEW OR GRADER_VIEW) */
    public $view = self::STUDENT_VIEW;
    /** @var bool canviewfullnames */
    public $canviewfullnames = false;
    /** @var bool canedit */
    public $canedit = false;
    /** @var bool cansubmit */
    public $cansubmit = false;
    /** @var int extensionduedate */
    public $extensionduedate = 0;
    /** @var context context */
    public $context = 0;
    /** @var bool blindmarking - Should we hide student identities from graders? */
    public $blindmarking = false;
    /** @var string gradingcontrollerpreview */
    public $gradingcontrollerpreview = '';
    /** @var string attemptreopenmethod */
    public $attemptreopenmethod = 'none';
    /** @var int maxattempts */
    public $maxattempts = -1;
    /** @var string gradingstatus */
    public $gradingstatus = '';
    /** @var bool preventsubmissionnotingroup */
    public $preventsubmissionnotingroup = 0;
    /** @var array usergroups */
    public $usergroups = array();

    public function __construct($allowsubmissionsfromdate,
                                $alwaysshowdescription,
                                $submission,
                                $teamsubmissionenabled,
                                $teamsubmission,
                                $submissiongroup,
                                $submissiongroupmemberswhoneedtosubmit,
                                $submissionsenabled,
                                $locked,
                                $graded,
                                $duedate,
                                $cutoffdate,
                                $submissionplugins,
                                $returnaction,
                                $returnparams,
                                $coursemoduleid,
                                $courseid,
                                $view,
                                $canedit,
                                $cansubmit,
                                $canviewfullnames,
                                $extensionduedate,
                                $context,
                                $blindmarking,
                                $gradingcontrollerpreview,
                                $attemptreopenmethod,
                                $maxattempts,
                                $gradingstatus,
                                $preventsubmissionnotingroup,
                                $usergroups) {
        $this->allowsubmissionsfromdate = $allowsubmissionsfromdate;
        $this->alwaysshowdescription = $alwaysshowdescription;
        $this->submission = $submission;
        $this->teamsubmissionenabled = $teamsubmissionenabled;
        $this->teamsubmission = $teamsubmission;
        $this->submissiongroup = $submissiongroup;
        $this->submissiongroupmemberswhoneedtosubmit = $submissiongroupmemberswhoneedtosubmit;
        $this->submissionsenabled = $submissionsenabled;
        $this->locked = $locked;
        $this->graded = $graded;
        $this->duedate = $duedate;
        $this->cutoffdate = $cutoffdate;
        $this->submissionplugins = $submissionplugins;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
        $this->coursemoduleid = $coursemoduleid;
        $this->courseid = $courseid;
        $this->view = $view;
        $this->canedit = $canedit;
        $this->cansubmit = $cansubmit;
        $this->canviewfullnames = $canviewfullnames;
        $this->extensionduedate = $extensionduedate;
        $this->context = $context;
        $this->blindmarking = $blindmarking;
        $this->gradingcontrollerpreview = $gradingcontrollerpreview;
        $this->attemptreopenmethod = $attemptreopenmethod;
        $this->maxattempts = $maxattempts;
        $this->gradingstatus = $gradingstatus;
        $this->preventsubmissionnotingroup = $preventsubmissionnotingroup;
        $this->usergroups = $usergroups;
    }
}


class proassign_attempt_history implements renderable {

    /** @var array submissions - The list of previous attempts */
    public $submissions = array();
    /** @var array grades - The grades for the previous attempts */
    public $grades = array();
    /** @var array submissionplugins - The list of submission plugins to render the previous attempts */
    public $submissionplugins = array();
    /** @var array feedbackplugins - The list of feedback plugins to render the previous attempts */
    public $feedbackplugins = array();
    /** @var int coursemoduleid - The cmid for the proassignment */
    public $coursemoduleid = 0;
    /** @var string returnaction - The action for the next page. */
    public $returnaction = '';
    /** @var string returnparams - The params for the next page. */
    public $returnparams = array();
    /** @var bool cangrade - Does this user have grade capability? */
    public $cangrade = false;
    /** @var string useridlistid - Id of the useridlist stored in cache, this plus rownum determines the userid */
    public $useridlistid = 0;
    /** @var int rownum - The rownum of the user in the useridlistid - this plus useridlistid determines the userid */
    public $rownum = 0;

    public function __construct($submissions,
                                $grades,
                                $submissionplugins,
                                $feedbackplugins,
                                $coursemoduleid,
                                $returnaction,
                                $returnparams,
                                $cangrade,
                                $useridlistid,
                                $rownum) {
        $this->submissions = $submissions;
        $this->grades = $grades;
        $this->submissionplugins = $submissionplugins;
        $this->feedbackplugins = $feedbackplugins;
        $this->coursemoduleid = $coursemoduleid;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
        $this->cangrade = $cangrade;
        $this->useridlistid = $useridlistid;
        $this->rownum = $rownum;
    }
}






class proassign_plugin_header implements renderable {
    /** @var proassign_plugin $plugin */
    public $plugin = null;

    public function __construct(proassign_plugin $plugin) {
        $this->plugin = $plugin;
    }
}


class proassign_grading_summary implements renderable {
    /** @var int participantcount - The number of users who can submit to this proassignment */
    public $participantcount = 0;
    /** @var bool submissiondraftsenabled - Allow submission drafts */
    public $submissiondraftsenabled = false;
    /** @var int submissiondraftscount - The number of submissions in draft status */
    public $submissiondraftscount = 0;
    /** @var bool submissionsenabled - Allow submissions */
    public $submissionsenabled = false;
    /** @var int submissionssubmittedcount - The number of submissions in submitted status */
    public $submissionssubmittedcount = 0;
    /** @var int submissionsneedgradingcount - The number of submissions that need grading */
    public $submissionsneedgradingcount = 0;
    /** @var int duedate - The proassignment due date (if one is set) */
    public $duedate = 0;
    /** @var int cutoffdate - The proassignment cut off date (if one is set) */
    public $cutoffdate = 0;
    /** @var int coursemoduleid - The proassignment course module id */
    public $coursemoduleid = 0;
    /** @var boolean teamsubmission - Are team submissions enabled for this proassignment */
    public $teamsubmission = false;
    /** @var boolean warnofungroupedusers - Do we need to warn people that there are users without groups */
    public $warnofungroupedusers = false;

    public function __construct($participantcount,
                                $submissiondraftsenabled,
                                $submissiondraftscount,
                                $submissionsenabled,
                                $submissionssubmittedcount,
                                $cutoffdate,
                                $duedate,
                                $coursemoduleid,
                                $submissionsneedgradingcount,
                                $teamsubmission,
                                $warnofungroupedusers) {
        $this->participantcount = $participantcount;
        $this->submissiondraftsenabled = $submissiondraftsenabled;
        $this->submissiondraftscount = $submissiondraftscount;
        $this->submissionsenabled = $submissionsenabled;
        $this->submissionssubmittedcount = $submissionssubmittedcount;
        $this->duedate = $duedate;
        $this->cutoffdate = $cutoffdate;
        $this->coursemoduleid = $coursemoduleid;
        $this->submissionsneedgradingcount = $submissionsneedgradingcount;
        $this->teamsubmission = $teamsubmission;
        $this->warnofungroupedusers = $warnofungroupedusers;
    }
}


class proassign_course_index_summary implements renderable {
    /** @var array proassignments - A list of course module info and submission counts or statuses */
    public $proassignments = array();
    /** @var boolean usesections - Does this course format support sections? */
    public $usesections = false;
    /** @var string courseformat - The current course format name */
    public $courseformatname = '';

    public function __construct($usesections, $courseformatname) {
        $this->usesections = $usesections;
        $this->courseformatname = $courseformatname;
    }

    public function add_proassign_info($cmid, $cmname, $sectionname, $timedue, $submissioninfo, $gradeinfo) {
        $this->proassignments[] = array('cmid'=>$cmid,
                               'cmname'=>$cmname,
                               'sectionname'=>$sectionname,
                               'timedue'=>$timedue,
                               'submissioninfo'=>$submissioninfo,
                               'gradeinfo'=>$gradeinfo);
    }


}


class proassign_files implements renderable {
    /** @var context $context */
    public $context;
    /** @var string $context */
    public $dir;
    /** @var MoodleQuickForm $portfolioform */
    public $portfolioform;
    /** @var stdClass $cm course module */
    public $cm;
    /** @var stdClass $course */
    public $course;

    public function __construct(context $context, $sid, $filearea, $component) {
        global $CFG;
        $this->context = $context;
        list($context, $course, $cm) = get_context_info_array($context->id);
        $this->cm = $cm;
        $this->course = $course;
        $fs = get_file_storage();
        $this->dir = $fs->get_area_tree($this->context->id, $component, $filearea, $sid);

        $files = $fs->get_area_files($this->context->id,
                                     $component,
                                     $filearea,
                                     $sid,
                                     'timemodified',
                                     false);

        if (!empty($CFG->enableportfolios)) {
            require_once($CFG->libdir . '/portfoliolib.php');
            if (count($files) >= 1 && !empty($sid) &&
                    has_capability('mod/proassign:exportownsubmission', $this->context)) {
                $button = new portfolio_add_button();
                $callbackparams = array('cmid' => $this->cm->id,
                                        'sid' => $sid,
                                        'area' => $filearea,
                                        'component' => $component);
                $button->set_callback_options('proassign_portfolio_caller',
                                              $callbackparams,
                                              'mod_proassign');
                $button->reset_formats();
                $this->portfolioform = $button->to_html(PORTFOLIO_ADD_TEXT_LINK);
            }

        }

        $this->preprocess($this->dir, $filearea, $component);
    }

    public function preprocess($dir, $filearea, $component) {
        global $CFG;
        foreach ($dir['subdirs'] as $subdir) {
            $this->preprocess($subdir, $filearea, $component);
        }
        foreach ($dir['files'] as $file) {
            $file->portfoliobutton = '';
            if (!empty($CFG->enableportfolios)) {
                require_once($CFG->libdir . '/portfoliolib.php');
                $button = new portfolio_add_button();
                if (has_capability('mod/proassign:exportownsubmission', $this->context)) {
                    $portfolioparams = array('cmid' => $this->cm->id, 'fileid' => $file->get_id());
                    $button->set_callback_options('proassign_portfolio_caller',
                                                  $portfolioparams,
                                                  'mod_proassign');
                    $button->set_format_by_file($file);
                    $file->portfoliobutton = $button->to_html(PORTFOLIO_ADD_ICON_LINK);
                }
            }
            $path = '/' .
                    $this->context->id .
                    '/' .
                    $component .
                    '/' .
                    $filearea .
                    '/' .
                    $file->get_itemid() .
                    $file->get_filepath() .
                    $file->get_filename();
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", $path, true);
            $filename = $file->get_filename();
            $file->fileurl = html_writer::link($url, $filename);
        }
    }
}

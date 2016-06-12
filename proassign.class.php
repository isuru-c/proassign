<?php

require_once dirname(__FILE__).'/filegroup.class.php';
require_once dirname(__FILE__).'/lib.php';

class mod_proassign{
	

    protected $cm;
    protected $course;
    protected $instance;
    protected $required_fgm;
    protected $execution_fgm;

	function __construct($id, $a=null) {
        global $OUTPUT;
        global $DB;
        if($id){
            if (! $this->cm = get_coursemodule_from_id(PROASSIGN,$id)) {
                print_error('invalidcoursemodule');
            }
            if (! $this->course = $DB->get_record("course", array("id" => $this->cm->course))) {
                print_error('unknowncourseidnumber','',$this->cm->course);
            }
            if (! $this->instance = $DB->get_record(PROASSIGN, array("id" => $this->cm->instance))) {
                print_error('module instance id unknow');
            }
        }else{
            if (! $this->instance = $DB->get_record(PROASSIGN, array("id" => $a))) {
                print_error('module instance id unknow');
            }
            if (! $this->course = $DB->get_record("course", array("id" => $this->instance->course))) {
                print_error('unknowncourseidnumber','',$this->instance->course);
            }
            if (! $this->cm = get_coursemodule_from_instance(PROASSIGN, $this->instance->id, $this->course->id)) {
                echo $OUTPUT->box(get_string('invalidcoursemodule').' PROASSIGN id='.$a);
                //Don't stop on error. This let delete a corrupted course.
            }
        }
        $this->required_fgm = null;
        $this->execution_fgm = null;
    }

	function print_view_tabs($active){
        //TODO refactor using functions
        global $CFG, $USER, $DB;
        $active = basename($active);
        $cmid=$this->cm->id;
        $userid = optional_param('userid',NULL,PARAM_INT);
        $copy = optional_param('privatecopy',false,PARAM_INT);
        $viewer = $this->has_capability(VPL_VIEW_CAPABILITY);
        $submiter = $this->has_capability(VPL_SUBMIT_CAPABILITY);
        $similarity = $this->has_capability(VPL_SIMILARITY_CAPABILITY);
        $grader = $this->has_capability(VPL_GRADE_CAPABILITY);
        $manager = $this->has_capability(VPL_MANAGE_CAPABILITY);
        $example = $this->instance->example;
        if(!$userid || !$grader || $copy){
            $userid = $USER->id;
        }
        $level2=$grader||$manager||$similarity;

        $strdescription = get_string('description',VPL);
        $strsubmission = get_string('submission',VPL);
        $stredit = get_string('edit',VPL);
        $strsubmissionview = get_string('submissionview',VPL);
        $maintabs = array();
        $tabs= array();
        $baseurl = $CFG->wwwroot.'/mod/'.VPL.'/';
        $href = vpl_mod_href('view.php','id',$cmid,'userid',$userid);
        $viewtab = new tabobject('view.php',$href,$strdescription,$strdescription);
        if($level2){
            if($viewer){
                $maintabs[]=$viewtab;
            }
            $strsubmissionslist = get_string('submissionslist',VPL);
            $href = vpl_mod_href('views/submissionslist.php','id',$cmid);
            $maintabs[]= new tabobject('submissionslist.php',$href,$strsubmissionslist,$strsubmissionslist);
            //similarity
            if($similarity){
                if($active == 'listwatermark.php' ||
                    $active == 'similarity_form.php' ||
                    $active == 'listsimilarity.php'){
                    $tabname=$active;
                }else{
                    $tabname = 'similarity';
                }
                $strsubmissionslist = get_string('similarity',VPL);
                $href = vpl_mod_href('similarity/similarity_form.php','id',$cmid);
                $maintabs[]= new tabobject($tabname,$href,$strsubmissionslist,$strsubmissionslist);
            }
            //test
            if($grader||$manager){
                if($userid == $USER->id){
                    $text = get_string('test',VPL);
                }else{
                    $user = $DB->get_record('user', array('id'=>$userid));
                    if($this->is_group_activity()){
                        $text = get_string('group').' ';
                    }
                    else{
                        $text = get_string('user').' ';
                    }
                    $text .= $this->fullname($user,false);
                }
                if($active == 'submission.php' ||
                    $active == 'edit.php' ||
                    $active == 'submissionview.php' ||
                    $active == 'gradesubmission.php' ||
                    $active == 'previoussubmissionslist.php'){
                    $tabname=$active;
                }else{
                    $tabname = 'test';
                }
                $href = vpl_mod_href('forms/submissionview.php','id',$cmid,'userid',$userid);
                $maintabs[]= new tabobject($tabname,$href,$text,$text);
            }
        }
        switch($active){
            case 'view.php':
                if($level2){
                    print_tabs(array($maintabs,$tabs),$active);
                    return;
                }
            case 'submission.php':
            case 'edit.php':
            case 'submissionview.php':
            case 'gradesubmission.php':
            case 'previoussubmissionslist.php':
                require_once('vpl_submission.class.php');
                $subinstance = $this->last_user_submission($userid);
                if($subinstance !== false){
                    $submission = new mod_vpl_submission($this,$subinstance);
                }
                if($viewer && ! $level2){
                    $tabs[] = $viewtab;
                }
                if($manager || ($grader && $USER->id == $userid)
                 || (!$grader && $submiter && $this->is_submit_able()
                     && !$this->instance->restrictededitor && !$example)){
                    $href = vpl_mod_href('forms/submission.php','id',$cmid,'userid',$userid);
                    $tabs[]= new tabobject('submission.php',$href,$strsubmission,$strsubmission);
                }
                if($manager || ($grader && $USER->id == $userid) || (!$grader && $submiter && $this->is_submit_able())){
                    $href = vpl_mod_href('forms/edit.php','id',$cmid,'userid',$userid);
                    if($example && $this->instance->run){
                        $stredit = get_string('run',VPL);
                    }
                    $tabs[]= new tabobject('edit.php',$href,$stredit,$stredit);
                }
                if(!$example){
                    $href = vpl_mod_href('forms/submissionview.php','id',$cmid,'userid',$userid);
                    $tabs[]= new tabobject('submissionview.php',$href,$strsubmissionview,$strsubmissionview);
                    if($grader && $this->get_grade() !=0 && $subinstance
                       && ($subinstance->dategraded==0 || $subinstance->grader==$USER->id || $subinstance->grader==0)){
                        $href = vpl_mod_href('forms/gradesubmission.php','id',$cmid,'userid',$userid);
                        $text=get_string('grade');
                        $tabs[]= new tabobject('gradesubmission.php',$href,$text,$text);
                    }
                    if($subinstance && ($grader || $similarity) ){
                        $strlistprevoiussubmissions = get_string('previoussubmissionslist',VPL);
                        $href = vpl_mod_href('views/previoussubmissionslist.php','id',$cmid,'userid',$userid);
                        $tabs[]= new tabobject('previoussubmissionslist.php',$href,$strlistprevoiussubmissions,$strlistprevoiussubmissions);
                    }
                }
                //Show user picture if this activity require password
                if(!isset($user) && $this->instance->password >''){
                    $user = $DB->get_record('user',array('id'=>$userid));
                }
                if(isset($user)){
                    echo '<div style="position:absolute; right:50px; z-index:50;">';
                    echo $this->user_picture($user);
                    echo '</div>';
                }
                if($level2){
                    print_tabs(array($maintabs,$tabs),$active);
                    return;
                }
                else{
                    print_tabs(array($tabs),$active);
                    return;
                }

            break;
            case 'submissionslist.php':
                print_tabs(array($maintabs),$active);
            return;
            case 'listwatermark.php':
            case 'similarity_form.php':
            case 'listsimilarity.php':
                if( $similarity ){
                    $href = vpl_mod_href('similarity/similarity_form.php','id',$cmid);
                    $string=get_string('similarity',VPL);
                    $tabs[]= new tabobject('similarity_form.php',$href,$string,$string);
                    if($active == 'listsimilarity.php'){
                        $string=get_string('listsimilarity',VPL);
                        $tabs[]= new tabobject('listsimilarity.php','',$string,$string);
                    }
                    $href = vpl_mod_href('similarity/listwatermark.php','id',$cmid);
                    $string=get_string('listwatermarks',VPL);
                    $tabs[]= new tabobject('listwatermark.php',$href,$string,$string);
                    $href = vpl_mod_href('views/downloadallsubmissions.php','id',$cmid);
                    $string=get_string('downloadallsubmissions',VPL);
                    $tabs[]= new tabobject('downloadallsubmissions.php',$href,$string,$string);
                }
                print_tabs(array($maintabs,$tabs),$active);
            break;
        }
    }
}
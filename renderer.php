<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/proassign/locallib.php');

class mod_proassign_renderer extends plugin_renderer_base {

    public function render_proassign_header(proassign_header $header) {
		global $USER, $DB;
		
        $out = '';

        if ($header->subpage) {
            $this->page->navbar->add($header->subpage);
        }

        $this->page->set_title('Programming Assignment');
        $this->page->set_heading($this->page->course->fullname);

        $out .= $this->output->header();
        $heading = format_string($header->proassign->name, false, array('context' => $header->context));
        $out .= $this->output->heading($heading);
        if ($header->preface) {
            $out .= $header->preface;
        }
		
		// Show links
		
		$out .= $this->render_header_links($header->coursemoduleid);

		$proassign = $header->proassign;

        $out .= $this->output->box_start('generalbox boxaligncenter', 'intro');
        
		$out .= '</br><b>Description</b></br></br>';
		$out .= format_module_intro('proassign', $header->proassign, $header->coursemoduleid);
		
		$out .= '</br></br><b>Submission details</b></br>';
        
		$table = new html_table();
		
		$proassign_id = $proassign->id;
		$user_id = $USER->id;
		
		$sql = "SELECT * FROM mdl_proassign_submission WHERE proassign=" . $proassign_id . " AND userid=" . $user_id;
		$data = $DB->get_record_sql($sql, null );
		
		if($data){
			$submission_state = "Assignment has been submitted";
		}else{
			$submission_state = "No attempt yet";
		}
		
		$name = $proassign->name;
		$start_date = date('Y-m-d h:i:s a', $proassign->startdate);
		$due_date = date('Y-m-d h:i:s a', $proassign->duedate);
		
		if($data){
			$marks = "Not yet grade";
		}else{
			$marks = "--";	
		}
		
		$sql = "SELECT grade1, grade2, grade3 FROM mdl_proassign_grades WHERE submission=" . $data->id;
		$gra_data = $DB->get_record_sql($sql, null );
				
		if($gra_data){
			$marks = $gra_data->grade1 + $gra_data->grade2 + $gra_data->grade3;
		}else{
			$marks = "Not yet graded";
		}
		
		$this->add_table_row2($table, "Submission state", $submission_state);
		$this->add_table_row2($table, "Assignment name", $name);
		$this->add_table_row2($table, "Start date", $start_date);
		$this->add_table_row2($table, "Due date", $due_date);
		$this->add_table_row2($table, "Marks", $marks);
		
		$out .= html_writer::table($table);
		
		$out .= $this->output->box_end();
        
		
        return $out;
    }
	
	private function add_table_row2(html_table $table, $col1, $col2) {
        $row = new html_table_row();
		
        $cell1 = new html_table_cell($col1);
        $cell2 = new html_table_cell($col2);
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
    }
	
	public function render_header_links($id){
		
		$out = "<style>";
		$out .= "ul { list-style-type: none; margin: 0; padding: 0;}";
		$out .= "li {display: inline;}";
		$out .= "</style>";
		
		$out = "<style>";
		$out .= ".ulul { list-style-type: none; margin: 0; padding: 0; overflow: hidden; border: 1px solid #e7e7e7;}"; // background-color: #f3f3f3; };";
		$out .= ".lili { display: inline; float: left;}"; // float: left; 
		$out .= ".lia { display: block; display: inline; color: #666; text-align: center; padding: 14px 16px; text-decoration: none; }";
		$out .= "li a:hover:not(.active) { background-color: #ddd; }";
		$out .= "li a.active { color: white; background-color: #4CAF50;}";
		$out .= "</style>";
		
		$out .= "<div>";
		$out .= "<ul class='ulul'>";
		
		$link = "/moodle/mod/proassign/view.php?id={$id}";
		$out .= "<li class='lili'><a class='lia' href='$link'>Assignment   </a></li>";
		
		$link = "/moodle/mod/proassign/view.php?id={$id}&action=testcases";
		$out .= "<li class='lili'><a class='lia' href='$link'>Test cases </a></li>";
				
		$link = "/moodle/mod/proassign/testrun.php?id={$id}&action=testrun";
		$out .= "<li class='lili'><a class='lia' href='$link'>Test run</a></li>";
		
		$link = "/moodle/mod/proassign/submission.php?id={$id}&action=testcases";
		$out .= "<li class='lili'><a class='lia' href='$link'>Submission </a></li>";
		
		$out .= "</ul>";
		$out .= "</div>";
		
		return $out;
		
		$out = '';
		
		$out .= $this->output->container_start('testcaselinks');
        $urlparams = array('id' => $id, 'action'=>'');
        $url = new moodle_url('/mod/proassign/view.php', $urlparams);
        $out .= $this->output->action_link($url, 'Assignment');
        $out .= $this->output->container_end();
		
		$out .= $this->output->container_start('testcaselinks');
        $urlparams = array('id' => $id, 'action'=>'testcases');
        $url = new moodle_url('/mod/proassign/view.php', $urlparams);
        $out .= $this->output->action_link($url, 'Test cases');
        $out .= $this->output->container_end();
		
		$out .= $this->output->container_start('testcaselinks');
        $urlparams = array('id' => $id);
        $url = new moodle_url('/mod/proassign/submission.php', $urlparams);
        $out .= $this->output->action_link($url, 'Submission');
        $out .= $this->output->container_end();
		
		return $out;
	}
	
	public function render_proassign_test_case(proassign_test_case $test_case){
		global $DB;
		
		$out = '';
		
		$this->page->set_title('Programming Assignment');
        $this->page->set_heading($this->page->course->fullname);
		
		$out .= $this->output->header();
        $heading = format_string($test_case->proassign->name, false, array('context' => $test_case->context));
        $out .= $this->output->heading($heading);
		
		$out .= $this->render_header_links($test_case->coursemoduleid);
		
		$out .= '</br>All the test cases of ' . $test_case->proassign->name . ' are listed here.</br>';
		
		
		

		$out .= $this->output->container_start('testcasesummary');
        $out .= $this->output->heading('Test case summary', 3);
        $out .= $this->output->box_start('boxaligncenter testcasesummarytable');
		
		$t = new html_table();        
		$this->add_table_row($t, 'start', null, null, null, null);
		
		//$sql = 'SELECT COUNT(id) FROM mdl_proassign_testcases WHERE proassign = 1';
		
        //$count = $DB->count_records_sql($sql, null);
		
		$data = $DB->get_record_sql('SELECT * FROM mdl_proassign WHERE id = ' . $test_case->proassign->id, null);
		
		if($data->use1==0 && $data->use2==0 && $data->use3==0){
			$out .= html_writer::table($t);
			$out .= '<i>There is no any test case yet..</i></br>';
		}
		else{
			for($i=1; $i<4; $i=$i+1){
				$u = 'use'.$i;
				if($data->use1==1){
					$name = '#testcase' . $i;
					$evaluating = 'Yes';
					$x = 'mark'.$i;
					$mark = $data->$x;
					$y = 'visible'.$i;
					$visible = 'No';
					if($data->$y==1){
						$visible = 'Yes';
					}
					$link = '';				
					$this->add_table_row($t, $name, $evaluating, $mark, $visible, $link);
				}
			}
			$out .= html_writer::table($t);			
		}		
		
        $out .= $this->output->box_end();	
		$out .= $this->output->container_end();
		
		if($test_case->editmode){
			$out .= $this->output->container_start('testcaseheader');
			$urlparams = array('id' => $test_case->coursemoduleid, 'action'=>'newtestcase');
			$url = new moodle_url('/mod/proassign/view.php', $urlparams);
			$out .= '</br></br>';
			$out .= $this->output->action_link($url, 'Add new test case');
			$out .= $this->output->container_end();
		}
		
		return $out;
	}
	
	public function render_proassign_new_test_case(proassign_new_test_case $test_case){
		global $DB, $CFG;
		
		$out = '';
		
		$this->page->set_title('Programming Assignment');
        $this->page->set_heading($this->page->course->fullname);
		
		$out .= $this->output->header();
        $heading = format_string($test_case->proassign->name, false, array('context' => $test_case->context));
        $out .= $this->output->heading($heading);
		
		$out .= 'Adding a new test case.</br>';

		$out .= $this->output->container_start('testcasesummary');
		
		$id = $test_case->coursemoduleid;
		
		$url = '/moodle/mod/proassign/view.php';
		
		$out .= '<form action="' . $url . '">';
		$out .= '<input type="hidden" name="id" value=' . $id . '>';
		$out .= '<input type="hidden" name="action" value="savetestcase">';
  		$out .= 'First name:<br>';
  		$out .= '<input type="text" name="firstname" value="Mickey"><br>';
  		$out .= 'Last name:<br>';
 		$out .= '<input type="text" name="lastname" value="Mouse"><br><br>';
  		$out .= '<input type="submit" name="submit" value="Submit">';
		$out .= '</form>';
	
		$out .= $this->output->container_end();
		
		return $out;
	}	
	
	public function render_proassign_submission(proassign_submission $submission){
		$out = '';
		
		return $out;
	}
	
	private function add_table_row(html_table $table, $test_case_name, $evaluating, $marks, $visible, $link) {
        $row = new html_table_row();
		
		if($test_case_name == 'start'){
			$cell1 = new html_table_cell("Test case name");
        	$cell2 = new html_table_cell("Evaluating");
        	$cell3 = new html_table_cell("Marks");
        	$cell4 = new html_table_cell("Visibility");
        	$cell5 = new html_table_cell("Action");
			$row->cells = array($cell1, $cell2, $cell3, $cell4, $cell5);
			$table->data[] = $row;
			return;
		}
		
        $cell1 = new html_table_cell($test_case_name);
        $cell2 = new html_table_cell($evaluating);
        $cell3 = new html_table_cell($marks);
        $cell4 = new html_table_cell($visible);
        $cell5 = new html_table_cell($link);
        $row->cells = array($cell1, $cell2, $cell3, $cell4, $cell5);
        $table->data[] = $row;
    }
	
	public function render_footer() {
        return $this->output->footer();
    }
	
}
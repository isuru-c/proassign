<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/proassign/locallib.php');

class mod_proassign_renderer extends plugin_renderer_base {

    public function render_proassign_header(proassign_header $header) {
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

        if ($header->showintro) {
            $out .= $this->output->box_start('generalbox boxaligncenter', 'intro');
            $out .= format_module_intro('proassign', $header->proassign, $header->coursemoduleid);
            $out .= $header->postfix;
            $out .= $this->output->box_end();
        }
		
		$out .= $this->output->container_start('testcaselinks');
        $urlparams = array('id' => $header->coursemoduleid, 'action'=>'testcases');
        $url = new moodle_url('/mod/proassign/view.php', $urlparams);
        $out .= $this->output->action_link($url, 'Test cases');
        $out .= $this->output->container_end();

        return $out;
    }
	
	public function render_proassign_test_case(proassign_test_case $test_case){
		$out = '';
		
		$this->page->set_title('Programming Assignment');
        $this->page->set_heading($this->page->course->fullname);
		
		$out .= $this->output->header();
        $heading = format_string($test_case->proassign->name, false, array('context' => $test_case->context));
        $out .= $this->output->heading($heading);
		
		$out .= 'All the test cases of ' . $test_case->proassign->name . ' are listed here.</br>';
				
		$out .= $this->output->container_start('testcaseheader');
        $urlparams = array('id' => $test_case->coursemoduleid, 'action'=>'');
        $url = new moodle_url('/mod/proassign/view.php', $urlparams);
        $out .= $this->output->action_link($url, 'Back to assignment');
        $out .= $this->output->container_end();
		
		return $out;
	}
	
	public function render_proassign_grading_summary(proassign_grading_summary $summary) {
        // Create a table for the data.
        $o = '';
        $o .= $this->output->container_start('gradingsummary');
        $o .= $this->output->heading(get_string('gradingsummary', 'assign'), 3);
        $o .= $this->output->box_start('boxaligncenter gradingsummarytable');
        $t = new html_table();

        // Status.
        if ($summary->teamsubmission) {
            if ($summary->warnofungroupedusers) {
                $o .= $this->output->notification(get_string('ungroupedusers', 'assign'));
            }

            $this->add_table_row_tuple($t, 'Number of teams',
                                       $summary->participantcount);
        } else {
            $this->add_table_row_tuple($t, 'Number of participants',
                                       $summary->participantcount);
        }

        // Drafts count and dont show drafts count when using offline assignment.
        if ($summary->submissiondraftsenabled && $summary->submissionsenabled) {
            $this->add_table_row_tuple($t, 'Number of draft submissions', $summary->submissiondraftscount);
        }

        // Submitted for grading.
        if ($summary->submissionsenabled) {
            $this->add_table_row_tuple($t, 'Number of submitted assignments', $summary->submissionssubmittedcount);
            if (!$summary->teamsubmission) {
                $this->add_table_row_tuple($t, 'Number of submissions need grading', $summary->submissionsneedgradingcount);
            }
        }

        $time = time();
        if ($summary->duedate) {
            // Due date.
            $duedate = $summary->duedate;
            $this->add_table_row_tuple($t, get_string('duedate', 'assign'), userdate($duedate));

            // Time remaining.
            $due = '';
            if ($duedate - $time <= 0) {
                $due = 'Assignment due';
            } else {
                $due = format_time($duedate - $time);
            }
            $this->add_table_row_tuple($t, 'Time remaining', $due);

            if ($duedate < $time) {
                $cutoffdate = $summary->cutoffdate;
                if ($cutoffdate) {
                    if ($cutoffdate > $time) {
                        $late = get_string('latesubmissionsaccepted', 'assign', userdate($summary->cutoffdate));
                    } else {
                        $late = 'No more submissions accepted';
                    }
                    $this->add_table_row_tuple($t, 'Late submissions', $late);
                }
            }

        }

        // All done - write the table.
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        // Link to the grading page.
        $o .= $this->output->container_start('submissionlinks');
        $urlparams = array('id' => $summary->coursemoduleid, 'action'=>'grading');
        $url = new moodle_url('/mod/proassign/view.php', $urlparams);
        $o .= $this->output->action_link($url, 'View grading');
        $o .= $this->output->container_end();

        // Close the container and insert a spacer.
        $o .= $this->output->container_end();

        return $o;
    }
	
	public function render_proassign_submission_status(proassign_submission_status $status) {
        $o = '';
        $o .= $this->output->container_start('submissionstatustable');
        $o .= $this->output->heading('Submission status', 3);
        $time = time();

        if ($status->allowsubmissionsfromdate &&
                $time <= $status->allowsubmissionsfromdate) {
            $o .= $this->output->box_start('generalbox boxaligncenter submissionsalloweddates');
            if ($status->alwaysshowdescription) {
                $date = userdate($status->allowsubmissionsfromdate);
               // $o .= get_string('allowsubmissionsfromdatesummary', 'assign', $date);
				$o .= "Allow submissions from date";
            } else {
                $date = userdate($status->allowsubmissionsfromdate);
                //$o .= get_string('allowsubmissionsanddescriptionfromdatesummary', 'assign', $date);
				$o .= 'Allow submissions and description from date summary';
            }
            $o .= $this->output->box_end();
        }
        $o .= $this->output->box_start('boxaligncenter submissionsummarytable');

        $t = new html_table();

        if ($status->teamsubmissionenabled) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('submissionteam', 'assign'));
            $group = $status->submissiongroup;
            if ($group) {
                $cell2 = new html_table_cell(format_string($group->name, false, $status->context));
            } else if ($status->preventsubmissionnotingroup) {
                if (count($status->usergroups) == 0) {
                    $cell2 = new html_table_cell(
                        html_writer::span(get_string('noteam', 'assign'), 'alert alert-error')
                    );
                } else if (count($status->usergroups) > 1) {
                    $cell2 = new html_table_cell(
                        html_writer::span(get_string('multipleteams', 'assign'), 'alert alert-error')
                    );
                }
            } else {
                $cell2 = new html_table_cell(get_string('defaultteam', 'assign'));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if ($status->attemptreopenmethod != 'none') {
            $currentattempt = 1;
            if (!$status->teamsubmissionenabled) {
                if ($status->submission) {
                    $currentattempt = $status->submission->attemptnumber + 1;
                }
            } else {
                if ($status->teamsubmission) {
                    $currentattempt = $status->teamsubmission->attemptnumber + 1;
                }
            }

            $row = new html_table_row();
            $cell1 = new html_table_cell('Attempt number');
            $maxattempts = $status->maxattempts;
            if ($maxattempts == ASSIGN_UNLIMITED_ATTEMPTS) {
                $message = get_string('currentattempt', 'assign', $currentattempt);
            } else {
                $message = get_string('currentattemptof', 'assign', array('attemptnumber'=>$currentattempt,
                                                                          'maxattempts'=>$maxattempts));
            }
            $cell2 = new html_table_cell($message);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('submissionstatus', 'assign'));
        if (!$status->teamsubmissionenabled) {
            if ($status->submission && $status->submission->status != 'new') {
                $statusstr = get_string('submissionstatus_' . $status->submission->status, 'assign');
                $cell2 = new html_table_cell($statusstr);
                $cell2->attributes = array('class'=>'submissionstatus' . $status->submission->status);
            } else {
                if (!$status->submissionsenabled) {
                    $cell2 = new html_table_cell(get_string('noonlinesubmissions', 'assign'));
                } else {
                    $cell2 = new html_table_cell(get_string('noattempt', 'assign'));
                }
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        } else {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('submissionstatus', 'assign'));
            $group = $status->submissiongroup;
            if (!$group && $status->preventsubmissionnotingroup) {
                $cell2 = new html_table_cell(get_string('nosubmission', 'assign'));
            } else if ($status->teamsubmission && $status->teamsubmission->status != 'new') {
                $teamstatus = $status->teamsubmission->status;
                $submissionsummary = get_string('submissionstatus_' . $teamstatus, 'assign');
                $groupid = 0;
                if ($status->submissiongroup) {
                    $groupid = $status->submissiongroup->id;
                }

                $members = $status->submissiongroupmemberswhoneedtosubmit;
                $userslist = array();
                foreach ($members as $member) {
                    $urlparams = array('id' => $member->id, 'course'=>$status->courseid);
                    $url = new moodle_url('/user/view.php', $urlparams);
                    if ($status->view == assign_submission_status::GRADER_VIEW && $status->blindmarking) {
                        $userslist[] = $member->alias;
                    } else {
                        $fullname = fullname($member, $status->canviewfullnames);
                        $userslist[] = $this->output->action_link($url, $fullname);
                    }
                }
                if (count($userslist) > 0) {
                    $userstr = join(', ', $userslist);
                    $formatteduserstr = get_string('userswhoneedtosubmit', 'assign', $userstr);
                    $submissionsummary .= $this->output->container($formatteduserstr);
                }

                $cell2 = new html_table_cell($submissionsummary);
                $cell2->attributes = array('class'=>'submissionstatus' . $status->teamsubmission->status);
            } else {
                $cell2 = new html_table_cell(get_string('nosubmission', 'assign'));
                if (!$status->submissionsenabled) {
                    $cell2 = new html_table_cell(get_string('noonlinesubmissions', 'assign'));
                } else {
                    $cell2 = new html_table_cell(get_string('nosubmission', 'assign'));
                }
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Is locked?
        if ($status->locked) {
            $row = new html_table_row();
            $cell1 = new html_table_cell();
            $cell2 = new html_table_cell(get_string('submissionslocked', 'assign'));
            $cell2->attributes = array('class'=>'submissionlocked');
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Grading status.
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('gradingstatus', 'assign'));

        if ($status->gradingstatus == 'graded' ||
            $status->gradingstatus == 'notgraded') {
            $cell2 = new html_table_cell(get_string($status->gradingstatus, 'assign'));
        } else {
            $gradingstatus = 'markingworkflowstate' . $status->gradingstatus;
            $cell2 = new html_table_cell(get_string($gradingstatus, 'assign'));
        }
        if ($status->gradingstatus == 'graded' ||
            $status->gradingstatus == 'released') {
            $cell2->attributes = array('class' => 'submissiongraded');
        } else {
            $cell2->attributes = array('class' => 'submissionnotgraded');
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        $submission = $status->teamsubmission ? $status->teamsubmission : $status->submission;
        $duedate = $status->duedate;
        if ($duedate > 0) {
            // Due date.
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('duedate', 'assign'));
            $cell2 = new html_table_cell(userdate($duedate));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            if ($status->view == proassign_submission_status::GRADER_VIEW) {
                if ($status->cutoffdate) {
                    // Cut off date.
                    $row = new html_table_row();
                    $cell1 = new html_table_cell(get_string('cutoffdate', 'assign'));
                    $cell2 = new html_table_cell(userdate($status->cutoffdate));
                    $row->cells = array($cell1, $cell2);
                    $t->data[] = $row;
                }
            }

            if ($status->extensionduedate) {
                // Extension date.
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('extensionduedate', 'assign'));
                $cell2 = new html_table_cell(userdate($status->extensionduedate));
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
                $duedate = $status->extensionduedate;
            }

            // Time remaining.
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timeremaining', 'assign'));
            if ($duedate - $time <= 0) {
                if (!$submission ||
                        $submission->status != 'submitted') {
                    if ($status->submissionsenabled) {
                        $overduestr = get_string('overdue', 'assign', format_time($time - $duedate));
                        $cell2 = new html_table_cell($overduestr);
                        $cell2->attributes = array('class'=>'overdue');
                    } else {
                        $cell2 = new html_table_cell(get_string('duedatereached', 'assign'));
                    }
                } else {
                    if ($submission->timemodified > $duedate) {
                        $latestr = get_string('submittedlate',
                                              'assign',
                                              format_time($submission->timemodified - $duedate));
                        $cell2 = new html_table_cell($latestr);
                        $cell2->attributes = array('class'=>'latesubmission');
                    } else {
                        $earlystr = get_string('submittedearly',
                                               'assign',
                                               format_time($submission->timemodified - $duedate));
                        $cell2 = new html_table_cell($earlystr);
                        $cell2->attributes = array('class'=>'earlysubmission');
                    }
                }
            } else {
                $cell2 = new html_table_cell(format_time($duedate - $time));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Show graders whether this submission is editable by students.
        if ($status->view == proassign_submission_status::GRADER_VIEW) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('editingstatus', 'assign'));
            if ($status->canedit) {
                $cell2 = new html_table_cell(get_string('submissioneditable', 'assign'));
                $cell2->attributes = array('class'=>'submissioneditable');
            } else {
                $cell2 = new html_table_cell(get_string('submissionnoteditable', 'assign'));
                $cell2->attributes = array('class'=>'submissionnoteditable');
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Grading criteria preview.
        if (!empty($status->gradingcontrollerpreview)) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('gradingmethodpreview', 'assign'));
            $cell2 = new html_table_cell($status->gradingcontrollerpreview);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Last modified.
        if ($submission) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timemodified', 'assign'));

            if ($submission->status != 'new') {
                $cell2 = new html_table_cell(userdate($submission->timemodified));
            } else {
                $cell2 = new html_table_cell('-');
            }

            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            if (!$status->teamsubmission || $status->submissiongroup != false || !$status->preventsubmissionnotingroup) {
                foreach ($status->submissionplugins as $plugin) {
                    $pluginshowsummary = !$plugin->is_empty($submission) || !$plugin->allow_submissions();
                    if ($plugin->is_enabled() &&
                        $plugin->is_visible() &&
                        $plugin->has_user_summary() &&
                        $pluginshowsummary
                    ) {

                        $row = new html_table_row();
                        $cell1 = new html_table_cell($plugin->get_name());
                        $displaymode = assign_submission_plugin_submission::SUMMARY;
                        $pluginsubmission = new assign_submission_plugin_submission($plugin,
                            $submission,
                            $displaymode,
                            $status->coursemoduleid,
                            $status->returnaction,
                            $status->returnparams);
                        $cell2 = new html_table_cell($this->render($pluginsubmission));
                        $row->cells = array($cell1, $cell2);
                        $t->data[] = $row;
                    }
                }
            }
        }

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        // Links.
        if ($status->view == proassign_submission_status::STUDENT_VIEW) {
            if ($status->canedit) {
                if (!$submission || $submission->status == 'new') {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $o .= $this->output->single_button(new moodle_url('/mod/assign/view.php', $urlparams),
                                                       get_string('addsubmission', 'assign'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('editsubmission_help', 'assign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                } else if ($submission->status == 'reopened') {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid,
                                       'action' => 'editprevioussubmission',
                                       'sesskey'=>sesskey());
                    $o .= $this->output->single_button(new moodle_url('/mod/assign/view.php', $urlparams),
                                                       get_string('addnewattemptfromprevious', 'assign'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('addnewattemptfromprevious_help', 'assign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $o .= $this->output->single_button(new moodle_url('/mod/assign/view.php', $urlparams),
                                                       get_string('addnewattempt', 'assign'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('addnewattempt_help', 'assign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                } else {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $o .= $this->output->single_button(new moodle_url('/mod/assign/view.php', $urlparams),
                                                       get_string('editsubmission', 'assign'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('editsubmission_help', 'assign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                }
            }

            if ($status->cansubmit) {
                $urlparams = array('id' => $status->coursemoduleid, 'action'=>'submit');
                $o .= $this->output->box_start('generalbox submissionaction');
                $o .= $this->output->single_button(new moodle_url('/mod/assign/view.php', $urlparams),
                                                   get_string('submitassignment', 'assign'), 'get');
                $o .= $this->output->box_start('boxaligncenter submithelp');
                $o .= get_string('submitassignment_help', 'assign');
                $o .= $this->output->box_end();
                $o .= $this->output->box_end();
            }
        }

        $o .= $this->output->container_end();
        return $o;
    }
	
	public function render_footer() {
        return $this->output->footer();
    }

	
	private function add_table_row_tuple(html_table $table, $first, $second) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell2 = new html_table_cell($second);
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
    }
	
}
<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/proassign/locallib.php');

class mod_proassign_renderer extends plugin_renderer_base {

    public function render_proassign_header(proassign_header $header) {
        $o = '';

        if ($header->subpage) {
            $this->page->navbar->add($header->subpage);
        }

        $this->page->set_title('Programming Assignment');
        $this->page->set_heading($this->page->course->fullname);

        $o .= $this->output->header();
        $heading = format_string($header->proassign->name, false, array('context' => $header->context));
        $o .= $this->output->heading($heading);
        if ($header->preface) {
            $o .= $header->preface;
        }

        if ($header->showintro) {
            $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
            $o .= format_module_intro('proassign', $header->proassign, $header->coursemoduleid);
            $o .= $header->postfix;
            $o .= $this->output->box_end();
        }

        return $o;
    }
	
	public function render_assign_grading_summary(proassign_grading_summary $summary) {
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

            $this->add_table_row_tuple($t, get_string('numberofteams', 'assign'),
                                       $summary->participantcount);
        } else {
            $this->add_table_row_tuple($t, get_string('numberofparticipants', 'assign'),
                                       $summary->participantcount);
        }

        // Drafts count and dont show drafts count when using offline assignment.
        if ($summary->submissiondraftsenabled && $summary->submissionsenabled) {
            $this->add_table_row_tuple($t, get_string('numberofdraftsubmissions', 'assign'),
                                       $summary->submissiondraftscount);
        }

        // Submitted for grading.
        if ($summary->submissionsenabled) {
            $this->add_table_row_tuple($t, get_string('numberofsubmittedassignments', 'assign'),
                                       $summary->submissionssubmittedcount);
            if (!$summary->teamsubmission) {
                $this->add_table_row_tuple($t, get_string('numberofsubmissionsneedgrading', 'assign'),
                                           $summary->submissionsneedgradingcount);
            }
        }

        $time = time();
        if ($summary->duedate) {
            // Due date.
            $duedate = $summary->duedate;
            $this->add_table_row_tuple($t, get_string('duedate', 'assign'),
                                       userdate($duedate));

            // Time remaining.
            $due = '';
            if ($duedate - $time <= 0) {
                $due = get_string('assignmentisdue', 'assign');
            } else {
                $due = format_time($duedate - $time);
            }
            $this->add_table_row_tuple($t, get_string('timeremaining', 'assign'), $due);

            if ($duedate < $time) {
                $cutoffdate = $summary->cutoffdate;
                if ($cutoffdate) {
                    if ($cutoffdate > $time) {
                        $late = get_string('latesubmissionsaccepted', 'assign', userdate($summary->cutoffdate));
                    } else {
                        $late = get_string('nomoresubmissionsaccepted', 'assign');
                    }
                    $this->add_table_row_tuple($t, get_string('latesubmissions', 'assign'), $late);
                }
            }

        }

        // All done - write the table.
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        // Link to the grading page.
        $o .= $this->output->container_start('submissionlinks');
        $urlparams = array('id' => $summary->coursemoduleid, 'action'=>'grading');
        $url = new moodle_url('/mod/assign/view.php', $urlparams);
        $o .= $this->output->action_link($url, get_string('viewgrading', 'assign'));
        $o .= $this->output->container_end();

        // Close the container and insert a spacer.
        $o .= $this->output->container_end();

        return $o;
    }
	
}
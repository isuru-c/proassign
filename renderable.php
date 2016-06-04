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

class proassign_submission implements renderable {
	
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



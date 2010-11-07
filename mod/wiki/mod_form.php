<?php
require_once ('moodleform_mod.php');

class mod_wiki_mod_form extends moodleform_mod {

	function definition() {

		global $CFG, $COURSE, $WIKI_TYPES, $USER;
		$mform    =& $this->_form;

        if (!empty($this->_instance)) {
            $queryobject = new stdClass();
            $queryobject->id = $this->_instance;
            //$wikihasentries = wiki_has_entries($queryobject);
        } else {
            $wikihasentries=false;
        }
//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

		$mform->addElement('text', 'name', get_string('name'), array('size'=>'40'));
		$mform->setType('name', PARAM_NOTAGS);
		$mform->addRule('name', null, 'required', null, 'client');

		$mform->addElement('htmleditor', 'intro', get_string('summary'), array('rows' => '24'));
		$mform->setType('intro', PARAM_RAW);
		$mform->addRule('intro', null, 'required', null, 'client');
        $mform->setHelpButton('intro', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');

		$mform->addElement('hidden', 'introformat');
		$mform->setDefault('introformat', '1');

		$mform->addElement('text', 'pagename', get_string('firstpage', 'wiki'), array('size' => '40'));
		$mform->setType('pagename', PARAM_NOTAGS);
		$mform->setDefault('pagename', get_string('firstpage', 'wiki'));
        $mform->addRule('pagename', null, 'required', null, 'client');

// --------------------------------- OPTIONALS ------------------------------
		$mform->addElement('header', 'optional', get_string('optional', 'form'));

		$mform->addElement('checkbox', 'teacherdiscussion', get_string('teacherprivileges', 'wiki'), get_string('teacherdiscussion','wiki'));
		$mform->setHelpButton('teacherdiscussion', array('privileges', get_string('takeeffect', 'wiki'), 'wiki'));
		$mform->setAdvanced('teacherdiscussion');
		$mform->setDefault('teacherdiscussion', 1);

		$mform->addElement('checkbox', 'editable', get_string('studentprivileges', 'wiki'), get_string('studenteditable','wiki'));
		$mform->setHelpButton('editable', array('privileges', get_string('takeeffect', 'wiki'), 'wiki'));
		$mform->setDefault('editable', 1);
		$mform->setAdvanced('editable');
		$mform->addElement('checkbox', 'editanothergroup', null, get_string('anothergroupeditable','wiki'));
		$mform->setAdvanced('editanothergroup');
		$mform->addElement('checkbox', 'editanotherstudent', null, get_string('anotherstudenteditable','wiki'));
		$mform->setAdvanced('editanotherstudent');
		$mform->addElement('checkbox', 'attach', null, get_string('studentattach','wiki'));
		$mform->setAdvanced('attach');
		$mform->addElement('checkbox', 'restore', null, get_string('studentrestore','wiki'));
		$mform->setAdvanced('restore');
		$mform->addElement('checkbox', 'studentdiscussion', null, get_string('studentdiscussion','wiki'));
		$mform->setAdvanced('studentdiscussion');
		$mform->addElement('checkbox', 'listofteachers', null, get_string('listofteachers','wiki'));
		$mform->setAdvanced('listofteachers');

		$editoroptions = array("htmleditor" => get_string('htmleditor','wiki'),
			"nwiki" => get_string('nwiki','wiki'),
			"dfwiki" => get_string('dfwiki','wiki'),
			"ewiki" => get_string('ewiki','wiki'));
		$mform->addElement('select', 'editor', get_string('editor', 'wiki'), $editoroptions);
		$mform->setHelpButton('editor', array('editors', get_string('helpeditors', 'wiki'), 'wiki'));
		$mform->setAdvanced('editor');

		$mform->addElement('text', 'editorrows', get_string('numberofrows', 'wiki'));
		$mform->setDefault('editorrows', 40);
		$mform->setAdvanced('editorrows');

		$mform->addElement('text', 'editorcols', get_string('numberofcols', 'wiki'));
		$mform->setDefault('editorcols', 60);
		$mform->setAdvanced('editorcols');

		$mform->addElement('checkbox', 'votemode', get_string('votes', 'wiki'), get_string('activatevotes','wiki'));
		$mform->setAdvanced('votemode');

//-------------------------------------------------------------------------------
        // Wiki grades
        require_once ($CFG->dirroot.'/mod/wiki/grades/grades.lib.php');

        $evaluationoptions = array(0 => get_string('noeval','wiki'),
                                   1 => get_string('techereval','wiki'),
                                   2 => get_string('alleval','wiki'));

        $mform->addElement('select', 'evaluation', get_string('evaluations', 'wiki'), $evaluationoptions);
        $mform->setAdvanced('evaluation');

        // Get the scales from this course and also the site-wide ones.
        $scales = wiki_grade_get_scales_from_course($COURSE->id, $USER->id);

        if (isset($scales)) {
            foreach ($scales as $scale)
                $scoreoptions[$scale->id] = $scale->scale;
        }
        else
            $scoreoptions = array("noscale" => get_string('eval_noscale','wiki'));

        $mform->addElement('select', 'notetype', get_string('score', 'wiki'), $scoreoptions);
        $mform->setAdvanced('notetype');
        // End Wiki grades
//-------------------------------------------------------------------------------

		$wikigroupmode = array (get_string('studentsingroup','wiki'),
                                        get_string('separatestudents','wiki'),
                                        get_string('visiblestudents','wiki'));
		$mform->addElement('select', 'studentmode', get_string('groupstudentmode', 'wiki'), $wikigroupmode);
		$mform->setAdvanced('studentmode');

//-------------------------------------------------------------------------------
		$this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();

	}
}
?>

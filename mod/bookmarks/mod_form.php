<?php
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_bookmarks_mod_form extends moodleform_mod {

    function definition() {

		global $CFG, $COURSE;

		$mform    =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('bookmarksname', 'bookmarks'), array('size'=>'64'));
	    $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

		$mform->addElement('htmleditor', 'intro', get_string('bookmarksintro', 'bookmarks'),array('rows'=>'22'));
        $mform->setType('intro', PARAM_RAW);
        $mform->addRule('intro', get_string('required'), 'required', null, 'client');
        $mform->setHelpButton('intro', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');


        $features = new stdClass;
        $features->groups = true;
        $features->groupings = true;
        $features->groupmembersonly = true;
        $this->standard_coursemodule_elements($features);

		$this->add_action_buttons();
    }
}

?>
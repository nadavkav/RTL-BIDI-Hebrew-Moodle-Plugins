<?php // $Id: mod_form.php,v 1.9.2.1 2008/10/25 23:28:46 skodak Exp $
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_accordion_mod_form extends moodleform_mod {

	function definition() {

		$mform    =& $this->_form;

		//		$mform->addElement('htmleditor', 'title', get_string('accordtitle', 'accord'), array('size'=>'44'));
		$mform->addElement('text', 'title', get_string('accordtitle', 'accordion'),array('size'=>'64'));
		$mform->setType('title', PARAM_RAW);
		$mform->addRule('title', get_string('required'), 'required', null, 'client');
//        $mform->setHelpButton('title', array('questions', 'richtext'), false, 'editorhelpbutton');
// New Accordian Title

		$mform->addElement('htmleditor', 'content', get_string('accordcontent', 'accordion'), array('rows'=>'24'));
		$mform->setType('content', PARAM_RAW);
		$mform->addRule('content', get_string('required'), 'required', null, 'client');
	        $mform->setHelpButton('content', array('questions', 'richtext'), false, 'editorhelpbutton');


		$this->standard_hidden_coursemodule_elements();

        $mform->addElement('modvisible', 'visible', get_string('visible'));

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();

	}

}
?>

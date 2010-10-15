<?php // $Id: mod_form.php,v 1.2 2008/04/11 00:00:08 mudrd8mz Exp $

/**
 * This file defines the main stampcoll module form
 *
 * See http://docs.moodle.org/en/Development:lib/formslib.php
 */

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once ($CFG->dirroot.'/lib/filelib.php');

class mod_stampcoll_mod_form extends moodleform_mod {

	function definition() {

        global $CFG;
		global $COURSE;
		$mform    =& $this->_form;

//-- General --------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
    /// name 
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'60'));
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', null, 'required', null, 'client');
    /// text (description)
    	$mform->addElement('htmleditor', 'text', get_string('description'));
		$mform->setType('text', PARAM_RAW);
		//$mform->addRule('text', get_string('required'), 'required', null, 'client');
        $mform->setHelpButton('text', array('writing', 'richtext'), false, 'editorhelpbutton');
    /// introformat
        $mform->addElement('format', 'introformat', get_string('format'));
//-- Stamp Collection------------------------------------------------------------
        $mform->addElement('header', 'stampcollection', get_string('modulename', 'stampcoll'));
    /// stampimage
        make_upload_directory("$COURSE->id");    // Just in case
        $images = array();
        $coursefiles = get_directory_list("$CFG->dataroot/$COURSE->id", $CFG->moddata);
        foreach ($coursefiles as $filename) {
            if (mimeinfo("icon", $filename) == "image.gif") {
                $images["$filename"] = $filename;
            }
        }
        $mform->addElement('select', 'image', get_string('stampimage', 'stampcoll'), 
                                    array_merge(array(''=>get_string('default')), $images),'a','b','c','d');
        $mform->addElement('static', 'stampimageinfo', '', get_string('stampimageinfo', 'stampcoll') );
    /// displayzero
        $mform->addElement('selectyesno', 'displayzero', get_string('displayzero', 'stampcoll'));
        $mform->setDefault('displayzero', 0);
 
//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
		$this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

	}
}

?>

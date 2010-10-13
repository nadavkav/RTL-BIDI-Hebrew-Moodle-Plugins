<?php

require_once $CFG->libdir.'/formslib.php';

class scorm_upload_form extends moodleform {
	function definition() {
		global $CFG, $USER,$COURSE;
		$mform =& $this->_form;
		
        $mform->addElement('header', 'general', "Import");
        
        // the upload manager is used directly in entry processing, moodleform::save_files() is not used yet
        $this->set_upload_manager(new upload_manager('attachment', true, false, $COURSE, false, 0, true, true, false));
        
        $mform->addElement('file', 'attachment', get_string("file", "block_exabis_eportfolio"));
        
        $this->add_action_buttons();

		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);
		$mform->setDefault('courseid', 0);
	}
}

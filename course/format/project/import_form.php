<?php  // $Id: import_form.php,v 1.00 2008/4/1 12:00:00 Akio Ohnishi Exp $

require_once($CFG->libdir.'/formslib.php');

// コースを選択するフォーム
class course_import_section_form_course extends moodleform {

    function definition() {

        global $CFG;
        $mform    =& $this->_form;
        $text = $this->_customdata['text'];
        $options = $this->_customdata['options'];
        $courseid = $this->_customdata['courseid'];
        $tosection = $this->_customdata['tosection'];
        $mform->addElement('header', 'general', '');//fill in the data depending on page params
        //later using set_data
        $mform->addElement('select', 'fromcourse', $text, $options);

        // buttons
        $submit_string = get_string('usethiscourse');
        $this->add_action_buttons(false, $submit_string);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'section');
        $mform->setType('section', PARAM_INT);
        
        $mform->setConstants(array('id'=> $courseid, 'section'=> $tosection));

    }

    function validation($data, $files) {
        return parent::validation($data, $files);
    }
}

// セクションを選択するフォーム
class course_import_section_form_section extends moodleform {

    function definition() {

        global $CFG;
        $mform    =& $this->_form;
        $text = $this->_customdata['text'];
        $options = $this->_customdata['options'];
        $courseid = $this->_customdata['courseid'];
        $tosection = $this->_customdata['tosection'];
        $fromcourse = $this->_customdata['fromcourse'];
        $fullname = $this->_customdata['fullname'];
        
        $mform->addElement('header', 'general', $fullname);//fill in the data depending on page params
        $mform->addElement('select', 'fromsection', $text, $options);
        
        $mform->addElement('text', 'newdirectoryname', get_string('newdirectoryname', 'format_project'), 'size="30"');
        
        // buttons
        $submit_string = get_string('usethissection','format_project');
        $this->add_action_buttons(false, $submit_string);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'section');
        $mform->setType('section', PARAM_INT);
        
        $mform->addElement('hidden', 'fromcourse');
        $mform->setType('fromcourse', PARAM_INT);
        
        $mform->setConstants(array('id'=> $courseid, 'section'=> $tosection, 'fromcourse'=> $fromcourse));

    }

    function validation($data, $files) {
        return parent::validation($data, $files);
    }
}

// アップロードフォーム
class course_import_section_form_upload extends moodleform {

    function definition() {

        global $CFG, $USER;
        $mform    =& $this->_form;
        $maxuploadsize = $this->_customdata['maxuploadsize'];
        $strimportfile = get_string("importfile",'format_project');

        $this->set_upload_manager(new upload_manager('userfile', true, false, '', false, $maxuploadsize, true, true));
        //$this->set_max_file_size('', $maxuploadsize);

        $mform->addElement('header', 'general', '');//fill in the data depending on page params
        //later using set_data
        // buttons

        $mform->addElement('file', 'userfile', '');
        $mform->setHelpButton('userfile', array('attachment', get_string('attachment', 'forum'), 'forum'));

        $mform->addElement('text', 'newdirectoryname', get_string('newdirectoryname', 'format_project'), 'size="30"');
        
        $this->add_action_buttons(false, $strimportfile);

    }
    function get_import_name(){
        if ($this->is_submitted() and $this->is_validated()) {
            // return the temporary filename to process
            return $this->_upload_manager->files['userfile']['tmp_name'];
        }else{
            return  NULL;
        }
    }
}

?>

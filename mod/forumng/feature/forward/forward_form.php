<?php
require_once($CFG->libdir.'/formslib.php');

class mod_forumng_forward_form extends moodleform {

    function definition() {

        global $CFG, $USER;
        $mform = $this->_form;

        // Informational paragraph
        $a = (object)array(
            'email' => $USER->email, 
            'fullname' => fullname($USER, true));
        $mform->addElement('static', '', '',
            get_string('forward_info_' . 
                ($this->_customdata->onlyselected ? 'selected' : 'all'),
                'forumng', $a));

        // Email address
        $mform->addElement('text', 'email', get_string('forward_email', 'forumng'),
            array('size'=>48));
        $mform->setType('email', PARAM_RAW);
        $mform->setHelpButton('email', array('forward_email', 
            get_string('forward_email', 'forumng'), 'forumng'));
        $mform->addRule('email', get_string('required'), 'required', null, 
            'client');

        // CC me
        $mform->addElement('checkbox', 'ccme', get_string('forward_ccme', 'forumng'));

        // Email subject
        $mform->addElement('text', 'subject', get_string('subject', 'forumng'),
            array('size'=>48));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('maximumchars', '', 255),
            'maxlength', 255, 'client');
        $mform->addRule('subject', get_string('required'),
            'required', null, 'client');
        $mform->setDefault('subject', $this->_customdata->subject);

        // Special field just to tell javascript that we're trying to use the
        // html editor
        $mform->addElement('hidden', 'tryinghtmleditor',
            can_use_html_editor() ? 1 : 0);

        // Email message
        $mform->addElement('htmleditor', 'message',
            get_string('forward_intro', 'forumng'), array('cols'=>50, 'rows'=> 15));
        $mform->setType('message', PARAM_RAW);
        $mform->setHelpButton('message', array('reading', 'writing',
            'questions', 'richtext'), false, 'editorhelpbutton');

        // Message format
        $mform->addElement('format', 'format', get_string('format'));

        // Hidden fields
        if ($this->_customdata->postids) {
            foreach($this->_customdata->postids as $postid) {
                $mform->addElement('hidden', 'selectp' . $postid, 1);
            }
        } else {
            $mform->addElement('hidden', 'all', 1);
        }
        $mform->addElement('hidden', 'd', $this->_customdata->discussionid);
        $mform->addElement('hidden', 'clone', $this->_customdata->cloneid);
        $mform->addElement('hidden', 'postselectform', 1);

        $this->add_action_buttons(true, get_string('forward', 'forumng'));
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (isset($data['email'])) {
            $emails = preg_split('~[; ]+~', $data['email']);
            if (count($emails) < 1) {
                $errors['email'] = get_string('invalidemails', 'forumng');
            } else {
                foreach ($emails as $email) {
                    if (!validate_email($email)) {
                        $errors['email'] = get_string('invalidemails', 'forumng');
                        break;
                    }
                }
            }
        }
        return $errors;
    }
}
?>

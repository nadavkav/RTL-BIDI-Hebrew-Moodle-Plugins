<?php

require_once($CFG->libdir.'/formslib.php');

class mod_forumng_splitpost_form extends moodleform {

    function definition() {

        global $CFG;
        $mform =& $this->_form;

        $mform->addElement('static', 'whatever', '',
            get_string('splitinfo', 'forumng').'<br />');

        $mform->addElement('text', 'subject',
            get_string('subject', 'forumng'), 'size="48"');
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('maximumchars', '', 255),
            'maxlength', 255, 'client');
        $mform->addRule('subject', get_string('required'),
             'required', null, 'client');

        $this->add_action_buttons(true, get_string('splitpostbutton', 'forumng'));

        // Hidden fields
        foreach($this->_customdata as $param => $value) {
            $mform->addElement('hidden', $param, $value);
        }
    }
}
?>

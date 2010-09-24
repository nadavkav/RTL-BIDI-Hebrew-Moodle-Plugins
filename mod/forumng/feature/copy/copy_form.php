<?php

require_once($CFG->libdir.'/formslib.php');

class mod_forumng_copy_form extends moodleform {

    function definition() {

        global $CFG;
        $mform =& $this->_form;

        $mform->addElement('static', 'whatever', '',
            get_string('copy_info', 'forumng').'<br />');

        $mform->addElement('checkbox', 'hidelater',
            '', get_string('hidelater', 'forumng'));

        $this->add_action_buttons(true, get_string('copy_begin', 'forumng'));

        // Hidden fields
        foreach($this->_customdata as $param => $value) {
            $mform->addElement('hidden', $param, $value);
        }
    }
}
?>

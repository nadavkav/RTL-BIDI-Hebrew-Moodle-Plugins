<?php

require_once($CFG->libdir.'/formslib.php');

class mod_lightboxgallery_comment_form extends moodleform {

    function definition() {

        $mform =& $this->_form;

        $straddcomment = get_string('addcomment', 'lightboxgallery');

        $mform->addElement('htmleditor', 'comment', $straddcomment, array('cols' => 85, 'rows' => 18));
        $mform->addRule('comment', get_string('required'), 'required', null, 'client');
        $mform->setType('comment', PARAM_RAW);

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, $straddcomment);

    }
}
?>

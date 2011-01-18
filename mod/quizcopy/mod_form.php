<?php //$Id: mod_form.php,v 1.2.2.3 2009/03/19 12:23:11 mudrd8mz Exp $

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_quizcopy_mod_form extends moodleform_mod {

    function definition() {
        global $COURSE;
        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $options = array();
        if ($quizes = get_coursemodules_in_course('quiz', $COURSE->id)) {
            foreach ($quizes as $quiz) {
                $options[$quiz->id] = $quiz->name;
            }
        }
        $mform->addElement('select', 'quiz', get_string('originalquiz', 'quizcopy'), $options);
        $mform->addRule('quiz', null, 'required', null, 'client');

    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('quizcopyname', 'quizcopy'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_hidden_coursemodule_elements();
        $this->add_action_buttons();
    }
}

?>

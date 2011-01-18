<?php
/**
 * Represents a alert.
 * @see forum_discussion
 * @see moodleform
 * @author Ray Guo
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @copyright Copyright 2009 The Open University
 */
require_once($CFG->libdir.'/formslib.php');

class mod_forumng_alert_form extends moodleform {

    function definition() {

        global $CFG, $USER;
        $mform = $this->_form;

        // Add all the check boxes
        $mform->addElement('static', 'alert_intro', '',
            get_string('alert_info', 'forumng'));

        $checkboxarray=array();

        $checkboxarray[] =& $mform->createElement('checkbox', 'alert_condition1',
            '', get_string('alert_condition1', 'forumng'));

        $checkboxarray[] =& $mform->createElement('checkbox', 'alert_condition2',
            '', get_string('alert_condition2', 'forumng'));

        $checkboxarray[] =& $mform->createElement('checkbox', 'alert_condition3',
            '', get_string('alert_condition3', 'forumng'));

        $checkboxarray[] =& $mform->createElement('checkbox', 'alert_condition4',
            '', get_string('alert_condition4', 'forumng'));

        $checkboxarray[] =& $mform->createElement('checkbox', 'alert_condition5',
            '', get_string('alert_condition5', 'forumng'));

        $checkboxarray[] =& $mform->createElement('checkbox', 'alert_condition6',
            '', get_string('alert_condition6', 'forumng'));

        $mform->addGroup($checkboxarray, get_string('alert_reasons', 'forumng'), get_string('alert_reasons', 'forumng'), '<br />', false);

        //plain text field
        $mform->addElement('textarea','alert_conditionmore',
                get_string('alert_conditionmore', 'forumng'), array('cols'=>50,
                    'rows'=> 15));

        $mform->setType('alert_conditionmore', PARAM_RAW);
        $mform->setHelpButton('alert_conditionmore', array('reading', 'writing',
                'questions', 'richtext'), false, 'editorhelpbutton');

        $mform->addElement('static', '', '',
            get_string('alert_reporterinfo', 'forumng'));

        $mform->addElement('static', '', '',
            get_string('alert_reporterdetail', 'forumng', $this->_customdata));

        //Add submit and cancel buttons
        $this->add_action_buttons(true, get_string('alert_submit', 'forumng'));

        //Add postid as hidden field
        $mform->addElement('hidden', 'p', $this->_customdata->postid);
        $mform->addElement('hidden', 'clone', $this->_customdata->cloneid);
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        //Error if all fields are empty
        if (empty($data['alert_condition1']) && empty($data['alert_condition2']) && empty($data['alert_condition3']) &&
            empty($data['alert_condition4']) && empty($data['alert_condition5']) && empty($data['alert_condition6']) &&
            empty($data['alert_conditionmore'])) {
            $errors['alert_intro'] = get_string('invalidalert', 'forumng');
        }

            if (empty($data['alert_condition1']) && empty($data['alert_condition2']) && empty($data['alert_condition3']) &&
            empty($data['alert_condition4']) && empty($data['alert_condition5']) && empty($data['alert_condition6']) &&
            !empty($data['alert_conditionmore'])) {
            $errors['alert_intro'] = get_string('invalidalertcheckbox', 'forumng');
        }

        return $errors;
    }
}
?>

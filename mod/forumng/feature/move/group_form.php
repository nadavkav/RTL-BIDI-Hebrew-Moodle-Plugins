<?php
require_once($CFG->libdir.'/formslib.php');

class mod_forumng_group_form extends moodleform {

    function definition() {

        global $CFG, $USER;
        $mform = $this->_form;
        $forum = $this->_customdata->targetforum;

        // Informational paragraph
        $mform->addElement('static', '', '',
            get_string('move_group_info', 'forumng', $forum->get_name()));

        // Get list of allowed groups
        $groups = $this->_customdata->groups;
        $mform->addElement('select', 'group', get_string('group'), $groups);
        reset($groups);
        $mform->setDefault('group', key($groups));

        // Hidden fields
        $mform->addElement('hidden', 'd', $this->_customdata->discussionid);
        $mform->addElement('hidden', 'clone', $this->_customdata->cloneid);
        $mform->addElement('hidden', 'target', $forum->get_course_module_id());

        $this->add_action_buttons(true, get_string('move'));
    }
}
?>

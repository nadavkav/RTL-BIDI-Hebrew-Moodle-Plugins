<?php //$Id: user_message_form.php,v 1.2.2.2 2010/01/13 07:56:20 rwijaya Exp $

require_once($CFG->libdir.'/formslib.php');

class user_courselist_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header', 'general', get_string('message', 'message'));


        //$mform->addElement('htmleditor', 'messagebody', get_string('messagebody'), array('rows'=>15, 'cols'=>60));
        //$mform->addRule('messagebody', '', 'required', null, 'client');
        //$mform->setHelpButton('messagebody', array('writing', 'reading', 'questions', 'richtext'), false, 'editorhelpbutton');
        //$mform->addElement('format', 'format', get_string('format'));

        $courses = get_courses();
        foreach($courses as $course) {
          //$mform->addElement('checkbox','courseid',get_string('course')." ".$course->shortname);
          $courselist[$course->id] = $course->shortname;
        }

        //$select = &$mform->addElement('select', 'colors', get_string('colors'), array('red', 'blue', 'green'), $attributes);
        //$select->setMultiple(true);

        $select = &$mform->addElement('select', 'courses', get_string('courses'), $courselist, 'size="15"');
        $select->setMultiple(true);

        //$mform->addElement('group', 'coursesgrp', get_string('courses'), $select , ' ', false);

        $allroles = array();
        if ($roles = get_all_roles()) {
          foreach ($roles as $role) {
            $rolename = strip_tags(format_string($role->name, true));
            $allroles[$role->id] = $rolename;
          }
        }

        $mform->addElement('select', 'role', get_string('roles'), $allroles);
        $mform->setDefault('role', 'Student');

        $this->add_action_buttons();
    }
}
?>
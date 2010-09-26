<?php  // $Id: duration_form.php,v 1.3.2.2 2009/02/23 19:22:42 dlnsk Exp $

require_once($CFG->libdir.'/formslib.php');

class mod_attforblock_duration_form extends moodleform {

    function definition() {

        global $CFG;
        $mform    =& $this->_form;
        $course        = $this->_customdata['course'];
        $cm            = $this->_customdata['cm'];
        $modcontext    = $this->_customdata['modcontext'];
        $ids		   = $this->_customdata['ids'];
        $mform->addElement('header', 'general', get_string('changeduration','attforblock'));
        $mform->addElement('static', 'count', get_string('countofselected','attforblock'), count(explode('_', $ids)));

        for ($i=0; $i<=23; $i++) {
            $hours[$i] = sprintf("%02d",$i);
        }
        for ($i=0; $i<60; $i+=5) {
            $minutes[$i] = sprintf("%02d",$i);
        }
        $durselect[] =& MoodleQuickForm::createElement('select', 'hours', '', $hours);
        $durselect[] =& MoodleQuickForm::createElement('select', 'minutes', '', $minutes, false, true);
        $mform->addGroup($durselect, 'durtime', get_string('newduration','attforblock'), array(' '), true);
        $mform->addElement('hidden', 'ids', $ids);
       	$mform->addElement('hidden', 'id', $cm->id);
        $mform->addElement('hidden', 'action', 'changeduration');
        $mform->setDefaults(array('durtime' => array('hours'=>0, 'minutes'=>0)));

        //-------------------------------------------------------------------------------
        // buttons
        $submit_string = get_string('update', 'attforblock');
        $this->add_action_buttons(true, $submit_string);
    }
}

class mod_attforblock_teacher_form extends moodleform {

    function definition() {

        global $CFG;
        $mform    =& $this->_form;
        $course        = $this->_customdata['course'];
        $cm            = $this->_customdata['cm'];
        $modcontext    = $this->_customdata['modcontext'];
        $ids	       = $this->_customdata['ids'];
        $mform->addElement('header', 'general', get_string('changeteacher','attforblock'));
        $mform->addElement('static', 'count', get_string('countofselected','attforblock'), count(explode('_', $ids)));
        //  add a teacher field to the form
        $teachersgroup = array();
        $options = array('0' => '');
        $teachers = get_records('attendance_teachers', '', 'teacher ASC', 'teacher');
        foreach($teachers as $teacher) {
            $options[$teacher->teacher] = $teacher->teacher;}
            $teachersgroup[] =& $mform->createElement('select', 'steacher', get_string('teacher', 'attforblock'), $options,  array('size' => 1,'class' => 'pool', 'style' => 'width:200px;'));
            $teachersgroup[] =& $mform->createElement('text', 'hteacher', get_string('newteacher', 'attforblock'), 'size="20"');
            $mform->addGroup($teachersgroup, 'teachersgroup', 'Teacher', array(' '), false);
            $mform->addElement('hidden', 'ids', $ids);
            $mform->addElement('hidden', 'id', $cm->id);
            $mform->addElement('hidden', 'action', 'changeteacher');
            //-------------------------------------------------------------------------------
            // buttons
            $submit_string = get_string('update', 'attforblock');
            $this->add_action_buttons(true, $submit_string);
    }
}

class mod_attforblock_group_form extends moodleform {

    function definition() {

        global $CFG;
        $mform    =& $this->_form;
        $course        = $this->_customdata['course'];
        $cm            = $this->_customdata['cm'];
        $modcontext    = $this->_customdata['modcontext'];
        $ids	       = $this->_customdata['ids'];
        $mform->addElement('header', 'general', get_string('changegroup','attforblock'));
        $mform->addElement('static', 'count', get_string('countofselected','attforblock'), count(explode('_', $ids)));
        //  add a group field to the form
        $groupsgroup = array();
        $options = array('0' => '');
        $groups = get_records('groups', '', 'name ASC', 'name');
        foreach($groups as $group) {
            $options[$group->id] = $group->name;}
            $groupsgroup[] =& $mform->createElement('select', 'sgroup', get_string('group'), $options,  array('size' => 1,'class' => 'pool', 'style' => 'width:200px;'));
            $groupsgroup[] =& $mform->createElement('text', 'hgroup', get_string('newgroup', 'attforblock'), 'size="20"');
            $mform->addGroup($groupsgroup, 'groupsgroup', 'Group', array(' '), false);
            $mform->addElement('hidden', 'ids', $ids);
            $mform->addElement('hidden', 'id', $cm->id);
            $mform->addElement('hidden', 'action', 'changegroup');
            //-------------------------------------------------------------------------------
            // buttons
            $submit_string = get_string('update', 'attforblock');
            $this->add_action_buttons(true, $submit_string);
    }
}


class mod_attforblock_description_form extends moodleform {

    function definition() {

        global $CFG;
        $mform    =& $this->_form;
        $course        = $this->_customdata['course'];
        $cm            = $this->_customdata['cm'];
        $modcontext    = $this->_customdata['modcontext'];
        $ids	       = $this->_customdata['ids'];
        $mform->addElement('header', 'general', get_string('changedescription','attforblock'));
        $mform->addElement('static', 'count', get_string('countofselected','attforblock'), count(explode('_', $ids)));

        //  add a description field to the form
        $mform->addElement('text', 'sdescription', get_string('description', 'attforblock'), 'size="48"');
        $mform->setType('sdescription', PARAM_TEXT);
        $mform->addRule('sdescription', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        $mform->addElement('hidden', 'ids', $ids);
       	$mform->addElement('hidden', 'id', $cm->id);
        $mform->addElement('hidden', 'action', 'changedescription');
        //-------------------------------------------------------------------------------
        // buttons
        $submit_string = get_string('update', 'attforblock');
        $this->add_action_buttons(true, $submit_string);
    }
}

class mod_attforblock_subject_form extends moodleform {

    function definition() {

        global $CFG;
        $mform    =& $this->_form;
        $course        = $this->_customdata['course'];
        $cm            = $this->_customdata['cm'];
        $modcontext    = $this->_customdata['modcontext'];
        $ids	       = $this->_customdata['ids'];
        $mform->addElement('header', 'general', get_string('changesubject','attforblock'));
        $mform->addElement('static', 'count', get_string('countofselected','attforblock'), count(explode('_', $ids)));

        //  add a subject field to the form
        $subjectsgroup = array();
        $options = array('0' => '');
        $subjects = get_records('attendance_subjects', '', 'subject ASC', 'subject');
        foreach($subjects as $subject) {
            $options[$subject->subject] = $subject->subject;}
            $subjectsgroup[] =& $mform->createElement('select', 'ssubject', get_string('subject', 'attforblock'), $options,  array('size' => 1,'class' => 'pool', 'style' => 'width:200px;'));
            $subjectsgroup[] =& $mform->createElement('text', 'hsubject', get_string('newsubject', 'attforblock'), 'size="20"');
            $mform->addGroup($subjectsgroup, 'subjectsgroup', 'Subject', array(' '), false);
            $mform->addElement('hidden', 'ids', $ids);
            $mform->addElement('hidden', 'id', $cm->id);
            $mform->addElement('hidden', 'action', 'changesubject');
            //-------------------------------------------------------------------------------
            // buttons
            $submit_string = get_string('update', 'attforblock');
            $this->add_action_buttons(true, $submit_string);
    }
}


class mod_attforblock_sessiontitle_form extends moodleform {

    function definition() {

        global $CFG;
        $mform    =& $this->_form;
        $course        = $this->_customdata['course'];
        $cm            = $this->_customdata['cm'];
        $modcontext    = $this->_customdata['modcontext'];
        $ids	       = $this->_customdata['ids'];
        $mform->addElement('header', 'general', get_string('changesessiontitle','attforblock'));
        $mform->addElement('static', 'count', get_string('countofselected','attforblock'), count(explode('_', $ids)));

        //  add a sessiontitle field to the form
        $sessiontitlesgroup = array();
        $options = array('0' => '');
        $sessiontitles = get_records('attendance_sessiontitles', '', 'sessiontitle ASC', 'sessiontitle');
        foreach($sessiontitles as $sessiontitle) {
            $options[$sessiontitle->sessiontitle] = $sessiontitle->sessiontitle;}
            $sessiontitlesgroup[] =& $mform->createElement('select', 'ssessiontitle', get_string('sessiontitle', 'attforblock'), $options,  array('size' => 1,'class' => 'pool', 'style' => 'width:200px;'));
            $sessiontitlesgroup[] =& $mform->createElement('text', 'hsessiontitle', get_string('newsessiontitle', 'attforblock'), 'size="20"');
            $mform->addGroup($sessiontitlesgroup, 'sessiontitlesgroup', 'sessiontitle', array(' '), false);
            $mform->addElement('hidden', 'ids', $ids);
            $mform->addElement('hidden', 'id', $cm->id);
            $mform->addElement('hidden', 'action', 'changesessiontitle');
            //-------------------------------------------------------------------------------
            // buttons
            $submit_string = get_string('update', 'attforblock');
            $this->add_action_buttons(true, $submit_string);
    }
}
?>
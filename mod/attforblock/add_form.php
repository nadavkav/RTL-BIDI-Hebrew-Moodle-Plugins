<?php  // $Id: add_form.php,v 1.1.2.2 2009/02/23 19:22:42 dlnsk Exp $

require_once($CFG->libdir.'/formslib.php');

class mod_attforblock_add_form extends moodleform {

    function definition() {

        global $CFG;
        $mform = & $this->_form;
        $course = $this->_customdata['course'];
        $cm = $this->_customdata['cm'];
        $modcontext = $this->_customdata['modcontext'];
        $mform->addElement('header', 'general', get_string('addsession','attforblock'));//fill in the data depending on page params
        //later using set_data
        $mform->addElement('checkbox', 'addmultiply', '', get_string('createmultiplesessions','attforblock'));
        $mform->setHelpButton('addmultiply', array('createmultiplesessions', get_string('createmultiplesessions','attforblock'), 'attforblock'));
        $mform->addElement('date_time_selector', 'sessiondate', get_string('sessiondate','attforblock'));

        for ($i=0; $i<=23; $i++) {
            $hours[$i] = sprintf("%02d",$i);
        }
        for ($i=0; $i<60; $i+=5) {
            $minutes[$i] = sprintf("%02d",$i);
        }
        $durtime = array();
        $durtime[] =& MoodleQuickForm::createElement('select', 'hours', get_string('hour', 'form'), $hours, false, true);
        $durtime[] =& MoodleQuickForm::createElement('select', 'minutes', get_string('minute', 'form'), $minutes, false, true);
        $mform->addGroup($durtime, 'durtime', get_string('duration','attforblock'), array(' '), true);
        $mform->addElement('date_selector', 'sessionenddate', get_string('sessionenddate','attforblock'));
        $mform->disabledIf('sessionenddate', 'addmultiply', 'notchecked');
        $sdays = array();
        if ($CFG->calendar_startwday === '0') { //week start from sunday
            $sdays[] =& MoodleQuickForm::createElement('checkbox', 'Sun', '', get_string('sunday','calendar'));
        }
        $sdays[] =& MoodleQuickForm::createElement('checkbox', 'Mon', '', get_string('monday','calendar'));
        $sdays[] =& MoodleQuickForm::createElement('checkbox', 'Tue', '', get_string('tuesday','calendar'));
        $sdays[] =& MoodleQuickForm::createElement('checkbox', 'Wed', '', get_string('wednesday','calendar'));
        $sdays[] =& MoodleQuickForm::createElement('checkbox', 'Thu', '', get_string('thursday','calendar'));
        $sdays[] =& MoodleQuickForm::createElement('checkbox', 'Fri', '', get_string('friday','calendar'));
        $sdays[] =& MoodleQuickForm::createElement('checkbox', 'Sat', '', get_string('saturday','calendar'));
        if ($CFG->calendar_startwday !== '0') { //week start from sunday
            $sdays[] =& MoodleQuickForm::createElement('checkbox', 'Sun', '', get_string('sunday','calendar'));
        }
        $mform->addGroup($sdays, 'sdays', get_string('sessiondays','attforblock'), array(' '), true);
        $mform->disabledIf('sdays', 'addmultiply', 'notchecked');
        $period = array(1=>1,2,3,4,5,6,7,8);
        $periodgroup = array();
        $periodgroup[] =& MoodleQuickForm::createElement('select', 'period', '', $period, false, true);
        $periodgroup[] =& MoodleQuickForm::createElement('static', 'perioddesc', '', get_string('week','attforblock'));
        $mform->addGroup($periodgroup, 'periodgroup', get_string('period','attforblock'), array(' '), false);
        $mform->disabledIf('periodgroup', 'addmultiply', 'notchecked');
        //  add a Groups field to the form from either a drop down list or a text field for new names
        $selectgroups = array();
        $options = array('0' => '');
        if(count_records_select('groups', 'courseid = '.$course->id)) {	// check if groups exist
            $groups = get_records_select('groups');
            foreach($groups as $group) {
                $options[$group->id] = $group->name;
            }
        }
        $selectgroups[] =& $mform->createElement('select', 'sgroup', get_string('group'), $options, array('size' => 1,'class' => 'pool', 'style' => 'width:200px;'));
        $selectgroups[] =& $mform->createElement('text', 'hgroup', get_string('addnew', 'attforblock'), 'size="20"');
        $mform->addGroup($selectgroups, 'sessiongroup', get_string('group'), ' '.get_string('addnew', 'attforblock'), false);
        //  add a session title field to the form from either a drop down list or a text field for new names
        $sessionsgroup =array();
        $options = array('0' => '');
        if(count_records_select('attendance_sessiontitles')) {	// check if session titles exist
            $sessiontitles = get_sessiontitles($course->id, false);
            foreach($sessiontitles as $sessiontitle) {
                $options[$sessiontitle->sessiontitle] = $sessiontitle->sessiontitle;
            }
        }
        $sessionsgroup[] =& $mform->createElement('select', 'ssessiontitle', get_string('sessiontitle', 'attforblock'), $options, array('size' => 1,'class' => 'pool', 'style' => 'width:200px;'));
        $sessionsgroup[] =& $mform->createElement('text', 'hsessiontitle', get_string('newsessiontitle', 'attforblock'), 'size="20"');
        $mform->addGroup($sessionsgroup, 'sessiongroup', get_string('sessiontitle', 'attforblock'), ' '.get_string('addnew', 'attforblock'), false);
        //  add a subject field to the form
        $subjectgroup = array();
        $options = array('0' => '');
        if(count_records_select('attendance_subjects')) {	// check if subjects exist
            $subjects = get_subjects($course->id, false);
            foreach($subjects as $subject) {
                $options[$subject->subject] = $subject->subject;
            }
        }
        $subjectgroup[] =& $mform->createElement('select', 'ssubject', get_string('subject', 'attforblock'), $options,  array('size' => 1,'class' => 'pool', 'style' => 'width:200px;'));
        $subjectgroup[] =& $mform->createElement('text', 'hsubject', get_string('newteacher', 'attforblock'), 'size="20"');
        $mform->addGroup($subjectgroup, 'subjectgroup', get_string('subject', 'attforblock'), ' '.get_string('addnew', 'attforblock'), false);
        //  add a teacher field to the form
        $teachersgroup = array();
        $options = array('0' => '');
        if(count_records_select('attendance_teachers')) {	// check if teachers exist
            $teachers = get_teachers($course->id, false);
            foreach($teachers as $teacher) {
                $options[$teacher->teacher] = $teacher->teacher;
            }
        }
        $teachersgroup[] =& $mform->createElement('select', 'steacher', get_string('teacher', 'attforblock'), $options,  array('size' => 1,'class' => 'pool', 'style' => 'width:200px;'));
        $teachersgroup[] =& $mform->createElement('text', 'hteacher', get_string('newteacher', 'attforblock'), 'size="20"');
        $mform->addGroup($teachersgroup, 'teachersgroup', get_string('teacher', 'attforblock'), ' '.get_string('addnew', 'attforblock'), false);
        //  add a description field to the form
        $mform->addElement('text', 'sdescription', get_string('description', 'attforblock'), 'size="48"');
        $mform->setType('sdescription', PARAM_TEXT);
        $mform->addRule('sdescription', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        //  add hidden buttons for course module id and action type
        $submit_string = get_string('addsession', 'attforblock');
        $this->add_action_buttons(false, $submit_string);
        //  hidden elements
        $mform->addElement('hidden', 'id', $cm->id);
        $mform->addElement('hidden', 'action', 'add');
    }
}
?>
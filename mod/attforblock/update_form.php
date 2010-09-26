<?php
// $Id: update_form.php,v 1.3.2.2 2009/02/23 19:22:42 dlnsk Exp $
require_once ($CFG->libdir.'/formslib.php');
class mod_attforblock_update_form extends moodleform {
    function definition() {
        global $CFG;
        $mform = & $this->_form;
        $course = $this->_customdata['course'];
        $cm = $this->_customdata['cm'];
        $modcontext = $this->_customdata['modcontext'];
        $sessionid = $this->_customdata['sessionid'];
        if (!$att = get_record('attendance_sessions', 'id', $sessionid)) {
            error('No such session in this course');
        }
        $mform->addElement('header', 'general', get_string('changesession', 'attforblock'));
        $mform->setHelpButton('general', array (
            'changesession',
            get_string('changesession', 'attforblock'),
            'attforblock'
        ));
        $mform->addElement('static', 'olddate', get_string('olddate', 'attforblock'), userdate($att->sessdate, get_string('strftimedmyhm', 'attforblock')));
        $mform->addElement('date_time_selector', 'sessiondate', get_string('newdate', 'attforblock'));
        for ($i = 0; $i <= 23; $i++) {
            $hours[$i] = sprintf("%02d", $i);
        }
        for ($i = 0; $i < 60; $i += 5) {
            $minutes[$i] = sprintf("%02d", $i);
        }
        $durselect[] = & MoodleQuickForm :: createElement('select', 'hours', '', $hours);
        $durselect[] = & MoodleQuickForm :: createElement('select', 'minutes', '', $minutes, false, true);
        $mform->addGroup($durselect, 'durtime', get_string('duration', 'attforblock'), array (
            ' '
        ), true);
        //  add a session title field to the form from either a drop down list or a text field for new names
        $sessionsgroup = array ();
        $options = array (
            '0' => ''
        );
        if (count_records_select('attendance_sessiontitles')) { // check if session titles exist
            $sessiontitles = get_sessiontitles($course->id, false);
            foreach ($sessiontitles as $sessiontitle) {
                $options[$sessiontitle->sessiontitle] = $sessiontitle->sessiontitle;
            }
        }
        $sessionsgroup[] = & $mform->createElement('select', 'ssessiontitle', get_string('sessiontitle', 'attforblock'), $options, array (
            'size' => 1,
            'class' => 'pool',
            'style' => 'width:200px;'
        ));
        $sessionsgroup[] = & $mform->createElement('text', 'hsessiontitle', get_string('newsessiontitle', 'attforblock'), 'size="20"');
        $mform->addGroup($sessionsgroup, 'sessiongroup', 'Session Title', ' '.get_string('addnew', 'attforblock'), false);

        //  add a session title field to the form from either a drop down list or a text field for new names
        $groupsgroup = array ();
        $options = array (
            '0' => ''
        );
        if (count_records_select('groups')) { // check if groups exist for this course
            $groups = get_records_select('groups');
            foreach ($groups as $group) {
                $options[$group->id] = $group->name;
            }
        }
        $groupsgroup[] = & $mform->createElement('select', 'sgroup', get_string('group'), $options, array (
            'size' => 1,
            'class' => 'pool',
            'style' => 'width:200px;'
        ));
        $groupsgroup[] = & $mform->createElement('text', 'hgroup', get_string('newgroup', 'attforblock'), 'size="20"');
        $mform->addGroup($groupsgroup, 'groupgroup', 'Group', ' '.get_string('addnew', 'attforblock'), false);
        
        //  add a subject field to the form
        $subjectgroup = array ();
        $options = array (
            '0' => ''
        );
        if (count_records_select('attendance_subjects')) { // check if subjects exist
            $subjects = get_subjects($course->id, false);
            foreach ($subjects as $subject) {
                $options[$subject->subject] = $subject->subject;
            }
        }
        $subjectgroup[] = & $mform->createElement('select', 'ssubject', get_string('subject', 'attforblock'), $options, array (
            'size' => 1,
            'class' => 'pool',
            'style' => 'width:200px;'
        ));
        $subjectgroup[] = & $mform->createElement('text', 'hsubject', get_string('newteacher', 'attforblock'), 'size="20"');
        $mform->addGroup($subjectgroup, 'subjectgroup', 'Subject', ' '.get_string('addnew', 'attforblock'), false);
        //  add a teacher field to the form
        $teachersgroup = array ();
        $options = array (
            '0' => ''
        );
        if (count_records_select('attendance_teachers')) { // check if teachers exist
            $teachers = get_teachers($course->id, false);
            foreach ($teachers as $teacher) {
                $options[$teacher->teacher] = $teacher->teacher;
            }
        }
        $teachersgroup[] = & $mform->createElement('select', 'steacher', get_string('teacher', 'attforblock'), $options, array (
            'size' => 1,
            'class' => 'pool',
            'style' => 'width:200px;'
        ));
        $teachersgroup[] = & $mform->createElement('text', 'hteacher', get_string('newteacher', 'attforblock'), 'size="20"');
        $mform->addGroup($teachersgroup, 'teachersgroup', 'Teacher', ' '.get_string('addnew', 'attforblock'), false);
        //  add a description field to the form
        $mform->addElement('text', 'sdescription', get_string('description', 'attforblock'), 'size="48"');
        $mform->setType('sdescription', PARAM_TEXT);
        $mform->addRule('sdescription', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        $dhours = floor($att->duration / HOURSECS);
        $dmins = floor(($att->duration - $dhours * HOURSECS) / MINSECS);
        $mform->setDefaults(array (
            'sessiondate' => $att->sessdate,
            'durtime' => array (
            'hours' => $dhours,
            'minutes' => $dmins
            ),
            'sdescription' => $att->description,
            'ssubject' => $att->subject,
            'steacher' => $att->teacher,
            'ssessiontitle' => $att->sessiontitle
        ));
        //-------------------------------------------------------------------------------
        // buttons
        $submit_string = get_string('update', 'attforblock');
        $this->add_action_buttons(true, $submit_string);
        $mform->addElement('hidden', 'id', $cm->id);
        $mform->addElement('hidden', 'sessionid', $sessionid);
        $mform->addElement('hidden', 'action', 'update');
    }
}
?>
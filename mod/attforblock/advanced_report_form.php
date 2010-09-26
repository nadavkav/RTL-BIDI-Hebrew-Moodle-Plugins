<?php  // $Id: add_form.php,v 1.1.2.2 2009/02/23 19:22:42 dlnsk Exp $

require_once($CFG->libdir.'/formslib.php');

class mod_attforblock_report_form extends moodleform {

    function definition() {
        global $CFG, $USER, $currentgroup, $students, $context, $sort;
        $mform    =& $this->_form;
        $course        = $this->_customdata['course'];
        $cm            = $this->_customdata['cm'];
        $modcontext    = $this->_customdata['modcontext'];
        //	Add date selectors for from and to dates
        $mform->addElement('date_selector', 'fdatefrom', get_string('datefrom','attforblock'));
        $mform->addElement('date_selector', 'fdateto', get_string('dateto','attforblock'));
        //	add radio buttons to select the report type
        $reporttype = array();
        $reporttype[] = &MoodleQuickForm::createElement('radio', 'reporttype', '', get_string('all'), 'all', '');
        $reporttype[] = &MoodleQuickForm::createElement('radio', 'reporttype', '', get_string('summaryonly','attforblock'),'summary', '');
        $reporttype[] = &MoodleQuickForm::createElement('radio', 'reporttype', '', get_string('detailonly','attforblock'), 'detailed', '');
        $mform->addGroup($reporttype, 'reporttype', get_string('reporttype','attforblock'), array(' '), false);
        $mform->setDefault('reporttype', 'all');
        //  add a course select element to the form
        $courselist = array('-1' => 'All');
        $courses = get_my_courses($USER->id, 'fullname ASC, sortorder ASC,visible DESC', '*', false, 21);
        foreach($courses as $course) {
            $courselist[$course->id] = $course->fullname;
        }
        $mform->addElement('select', 'coursemenu', get_string('course'), $courselist,  array('size' => 1,'class' => 'pool', 'style' => 'width:220px;'));

        //  add a group select element to the form
        $grouplist = array('-1' => 'All');
        $groups = get_records('groups');
        foreach($groups as $group) {
            $grouplist[$group->id] = $group->name;
        }
        $mform->addElement('select', 'groupmenu', get_string('group'), $grouplist,  array('size' => 1,'class' => 'pool', 'style' => 'width:220px;'));

        if ($currentgroup) {
            $students = get_users_by_capability($context, 'moodle/legacy:student', '', "u.$sort ASC", '', '', $currentgroup, '', false);
        } else {
            $students = get_users_by_capability($context, 'moodle/legacy:student', '', "u.$sort ASC", '', '', '', '', false);
        }
        //	add a student select element to the form
        $studentlist = array();
        $studentlist[0] = get_string('all');
        foreach($students as $student) {
            $studentlist[$student->id] =fullname($student);
        }
        $mform->addElement('select', 'studentmenu', get_string('student', 'attforblock'), $studentlist,  array('size' => 1,'class' => 'pool', 'style' => 'width:220px;'));
        //  add a subject select element to the form
        $subjectlist = array('-1' => 'All');
        if(count_records_select('attendance_subjects')) {	// check if subjects exist
            $subjects = get_subjects($course->id, true);
            foreach($subjects as $subject) {
                $subjectlist[$subject->subject] = $subject->subject;}
        }
        $mform->addElement('select', 'subjectmenu', get_string('subject', 'attforblock'), $subjectlist,  array('size' => 1,'class' => 'pool', 'style' => 'width:220px;'));
        //  add a teacher select element to the form
        $teacherlist = array('-1' => 'All');
        if(count_records_select('attendance_teachers')) {	// check if teachers exist
            $teachers = get_teachers($course->id, true);
            foreach($teachers as $teacher) {
                $teacherlist[$teacher->teacher] = $teacher->teacher;}
        }
        $mform->addElement('select', 'teachermenu', get_string('teacher', 'attforblock'), $teacherlist,  array('size' => 1,'class' => 'pool', 'style' => 'width:220px;'));
        //	Define the options of the drop down menu for make up notes and sicknote
        $optionlist = array(
            'all' => get_string('all'),
            'notrequired' => get_string('notrequired', 'attforblock'),
            'outstanding' => get_string('outstanding', 'attforblock'),
            'submitted' => get_string('submitted', 'attforblock'),
            'cleared' => get_string('cleared', 'attforblock'));
        $mform->addElement('select', 'makeupnotemenu', get_string('makeupnote', 'attforblock'), $optionlist,  array('size' => 1,'class' => 'pool', 'style' => 'width:220px;'));
        $mform->addElement('select', 'sicknotemenu', get_string('sicknote', 'attforblock'), $optionlist,  array('size' => 1,'class' => 'pool', 'style' => 'width:220px;'));
        //  	add a status select element to the form
        $statuslist = array('-1' => 'All');
        if(count_records_select('attendance_statuses')) {	// check if statuses exist
            $courses = get_my_courses($USER->id, 'fullname ASC, sortorder ASC,visible DESC', '*', false, 21);
            foreach($courses as $course) {
                //$statuslist['-1'.$course->id] = $course->fullname;
                $statuses = get_statuses($course->id);  // add a check to see if all courses or only one has been selected:  get_statuses for the course selected and get_status for all courses
                foreach($statuses as $status) {
                    $statuslist[$status->id] = $course->fullname.' - * '.$status->description;
                }
            }
        }
        $select = $mform->addElement('select', 'statusmenu', get_string('status', 'attforblock'), $statuslist,  array('size' => 1,'class' => 'pool', 'style' => 'width:220px;'));
        //	add radio buttons to select the sort order
        $sortmenu = array();
        $sortmenu[] = &MoodleQuickForm::createElement('radio', 'sortmenu', '', get_string('firstname'), 'firstname', '');
        $sortmenu[] = &MoodleQuickForm::createElement('radio', 'sortmenu', '', get_string('lastname'), 'lastname', '');
        $mform->addGroup($sortmenu, 'yesno', get_string('sortby','attforblock'), array(' '), false);
        $mform->setDefault('sortmenu', 'lastname');
        $lastquery = get_records('attendance_report_query');
        if ($lastquery) {
            foreach ($lastquery as $field) {
                $mform->setDefaults(array(
                    'fdatefrom' => $field->datefrom,
                    'fdateto' => $field->dateto,
                    'coursemenu' => $field->course,
                    'studentmenu' => $field->student,
                    'teachermenu' => $field->teacher,
                    'subjectmenu' => $field->subject,
                    'makeupnotemenu' => $field->makeupnote,
                    'sicknotemenu' => $field->sicknote,
                    'statusmenu' => $field->status,
                    'yesno' => $field->sortby,
                    'reporttype' => $field->reporttype
                ));
            }
        }
        //  add hidden buttons for course module id and action type
        $submit_string = get_string('update');
        $this->add_action_buttons(false, $submit_string);
        //  hidden elements
        $mform->addElement('hidden', 'id', $cm->id);
        $mform->addElement('hidden', 'action', 'update');
    }
}
?>
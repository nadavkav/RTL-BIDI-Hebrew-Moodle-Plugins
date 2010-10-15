<?php
require_once($CFG->dirroot."/question/export_form.php");
class question_export__good_questions_form extends question_export_form {
    function definition() {
        $mform    =& $this->_form;
        $qcreate   = $this->_customdata['qcreate'];
        if ($qcreate->graderatio != 100){
    
    //--------------------------------------------------------------------------------
            $mform->addElement('header','exportselection', get_string('exportselection','qcreate'));
    
            $menu = make_grades_menu($qcreate->grade);
            unset($menu[0]);
            $menu += array(0 => get_string('allquestions','qcreate'));
            $mform->addElement('select','betterthangrade', get_string('betterthangrade','qcreate'), $menu);
            $mform->setDefault('betterthangrade', 0);
        }
        $mform->addElement('header','exportnaming', get_string('exportnaming','qcreate'));

        $cbarray3=array();
        $cbarray3[] = &MoodleQuickForm::createElement('checkbox', 'naming[other]', '', get_string('specifictext', 'qcreate'));
        $cbarray3[] = &MoodleQuickForm::createElement('text', 'naming[othertext]');
        $mform->addGroup($cbarray3, 'naming3', '', array(' '), false);
        $mform->disabledIf('naming3', 'naming[other]');

        $cbarray1=array();
        $cbarray1[] = &MoodleQuickForm::createElement('checkbox', 'naming[firstname]', '', get_string('firstname'));
        $cbarray1[] = &MoodleQuickForm::createElement('checkbox', 'naming[lastname]', '', get_string('lastname'));
        $cbarray1[] = &MoodleQuickForm::createElement('checkbox', 'naming[username]', '', get_string('username', 'qcreate'));
        $mform->addGroup($cbarray1, 'naming1', '', array(' '), false);
        
        $cbarray2=array();
        $cbarray2[] = &MoodleQuickForm::createElement('checkbox', 'naming[activityname]', '', get_string('activityname', 'qcreate'));
        $cbarray2[] = &MoodleQuickForm::createElement('checkbox', 'naming[timecreated]', '', get_string('timecreated', 'qcreate'));
        $mform->addGroup($cbarray2, 'naming2', '', array(' '), false);


        parent::definition();



    }
}
?>

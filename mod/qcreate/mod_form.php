<?php //$Id

/**
 * This file defines de main qcreate configuration form
 * It uses the standard core Moodle (>1.8) formslib. For
 * more info about them, please visit:
 *
 * http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * The form must provide support for, at least these fields:
 *   - name: text element of 64cc max
 *
 * Also, it's usual to use these fields:
 *   - intro: one htmlarea element to describe the activity
 *            (will be showed in the list of activities of
 *             qcreate type (index.php) and in the header
 *             of the qcreate main page (view.php).
 *   - introformat: The format used to write the contents
 *             of the intro field. It automatically defaults
 *             to HTML when the htmleditor is used and can be
 *             manually selected if the htmleditor is not used
 *             (standard formats are: MOODLE, HTML, PLAIN, MARKDOWN)
 *             See lib/weblib.php Constants and the format_text()
 *             function for more info
 */

require_once ('moodleform_mod.php');
require_once ($CFG->libdir.'/questionlib.php');


class mod_qcreate_mod_form extends moodleform_mod {
    var $requireds;

    function definition() {

        global $COURSE;
        $mform    =& $this->_form;

        $this->requireds = get_records('qcreate_required', 'qcreateid', $this->_instance, 'qtype', 'qtype, no, id');


//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));
    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
    /// Adding the optional "intro" and "introformat" pair of fields
        $mform->addElement('htmleditor', 'intro', get_string('intro', 'qcreate'));
        $mform->setType('intro', PARAM_RAW);
        $mform->addRule('intro', get_string('required'), 'required', null, 'client');
        $mform->setHelpButton('intro', array('writing', 'richtext'), false, 'editorhelpbutton');

        $mform->addElement('format', 'introformat', get_string('format'));

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'timinghdr', get_string('timing', 'form'));
        $mform->addElement('date_time_selector', 'timeopen', get_string('open', 'qcreate'), array('optional'=>true));
        $mform->setHelpButton('timeopen', array('timeopen', get_string('open', 'qcreate'), 'qcreate'));

        $mform->addElement('date_time_selector', 'timeclose', get_string('close', 'qcreate'), array('optional'=>true));
        $mform->setHelpButton('timeclose', array('timeopen', get_string('close', 'qcreate'), 'qcreate'));

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'gradeshdr', get_string('grading', 'qcreate'));
        $gradeoptions = array();
        $gradeoptions[0] = get_string('nograde');
        for ($i=100; $i>=1; $i--) {
            $gradeoptions[$i] = $i;
        }
        $mform->addElement('select', 'grade', get_string('grade'), $gradeoptions);
        $mform->setDefault('grade', 100);
        $mform->setHelpButton('grade', array('grade', get_string('grade'), 'qcreate'));

        $graderatiooptions = array();
        foreach (array(100, 90, 80, 67, 60, 50, 40, 33, 30, 20, 10, 0)
                                 as $graderatiooption){
            $a = new object();
            $a->automatic = ($graderatiooption).'%';
            $a->manual = (100 - ($graderatiooption)).'%';
            $graderatiooptions[$graderatiooption] = get_string('graderatiooptions', 'qcreate', $a);
        }
        $mform->addElement('select', 'graderatio', get_string('graderatio', 'qcreate'), $graderatiooptions);
        $mform->setDefault('graderatio', 50);
        $mform->setHelpButton('graderatio', array('automaticmanualgrading', get_string('graderatio', 'qcreate'), 'qcreate'));

        $allowedgroup = array();
        $allowedgroup[] =& $mform->createElement('checkbox', "ALL", '', get_string('allowall', 'qcreate'));
        $mform->setDefault("allowed[ALL]", 1);
        $qtypemenu = question_type_menu();
        foreach ($qtypemenu as $qtype => $qtypestring){
            $allowedgroup[] =& $mform->createElement('checkbox', "$qtype", '', $qtypestring);
        }
        $mform->addGroup($allowedgroup, 'allowed', get_string('allowedqtypes', 'qcreate'));
        $mform->disabledIf('allowed', "allowed[ALL]", 'checked');
        $mform->setHelpButton('allowed', array('gradedquestiontypes', get_string('allowedqtypes', 'qcreate'), 'qcreate'));

        for ($i= 1; $i<=20; $i++){
            $noofquestionsmenu[$i] = $i;
        }
        $mform->addElement('select', 'totalrequired', get_string('noofquestionstotal', 'qcreate'), $noofquestionsmenu);
        $mform->setHelpButton('totalrequired', array('totalquestionsgraded', get_string('noofquestionstotal', 'qcreate'), 'qcreate'));


//-------------------------------------------------------------------------------
        $repeatarray=array();
        $repeatarray[] =& $mform->createElement('header', 'addminimumquestionshdr', get_string('addminimumquestionshdr', 'qcreate'));
        $qtypeselect = array(''=>get_string('selectone', 'qcreate')) + $qtypemenu;
        $repeatarray[] =& $mform->createElement('select', 'qtype', get_string('qtype', 'qcreate'), $qtypeselect);
        $repeatarray[] =& $mform->createElement('select', 'minimumquestions', get_string('minimumquestions', 'qcreate'), $noofquestionsmenu);
        $requiredscount = count($this->requireds);
        $repeats = $this->requireds ? $requiredscount+2 : 4;
        $repeats = $this->repeat_elements($repeatarray, $repeats, array(), 'minrepeats', 'addminrepeats', 2);

        for ($i=0; $i<$repeats; $i++) {
            $mform->setHelpButton("qtype[$i]", array('questiontype', get_string('qtype', 'qcreate'), 'qcreate'));
            $mform->setHelpButton("minimumquestions[$i]", array('minimumquestions', get_string('minimumquestions', 'qcreate'), 'qcreate'));
            $mform->disabledIf("minimumquestions[$i]", "qtype[$i]", 'eq', '');
        }
//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'studentaccessheader', get_string('studentaccessheader', 'qcreate'));
        $studentqaccessmenu = array(0=>get_string('studentaccessaddonly', 'qcreate'),
                                1=>get_string('studentaccesspreview', 'qcreate'),
                                2=>get_string('studentaccesssaveasnew', 'qcreate'),
                                3=>get_string('studentaccessedit', 'qcreate'));
        $mform->addElement('select', 'studentqaccess', get_string('studentqaccess', 'qcreate'), $studentqaccessmenu);
        $mform->setHelpButton('studentqaccess', array('studentquestionaccess', get_string('studentqaccess', 'qcreate'), 'qcreate'));

//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

    }

    function data_preprocessing(&$default_values){
        $i = 0;
        if ($this->requireds){
            foreach ($this->requireds as $qtype => $required){
                $default_values["minimumquestions[$i]"] = $required->no;
                $default_values["qtype[$i]"] = $qtype;
                $i++;
            }
        }
        if (isset($default_values['allowed'])){
            $enabled = explode(',', $default_values['allowed']);
            $qtypemenu = question_type_menu();
            foreach (array_keys($qtypemenu) as $qtype){
                $default_values["allowed[$qtype]"] = (array_search($qtype, $enabled)!==FALSE)?1:0;
            }
            $default_values["allowed[ALL]"] = (array_search('ALL', $enabled)!==FALSE)?1:0;
        }

    }

    function validation($data){
        $errors = array();
        if (!isset($data['allowed'])){
            $errors['allowed']=get_string('needtoallowatleastoneqtype', 'qcreate');
        }
        $qtypemenu = question_type_menu();
        $totalrequired = 0;
        if (isset($data['qtype'])){
            foreach ($data['qtype'] as $key => $qtype){
                if ($qtype!=''){
                    $chkqtypes[$key] = $qtype;
                    $keysforthisqtype = array_keys($chkqtypes);
                    if (count(array_keys($data['qtype'], $qtype)) > 1){
                        $errors["qtype[$key]"]=get_string('morethanonemin', 'qcreate', $qtypemenu[$qtype]);

                    } elseif (!isset($data['allowed'][$qtype]) && !isset($data['allowed']['ALL'])){
                        $errors['allowed']=get_string('needtoallowqtype', 'qcreate', $qtypemenu[$qtype]);
                        $errors["qtype[$key]"]=get_string('needtoallowqtype', 'qcreate', $qtypemenu[$qtype]);
                    }
                    $totalrequired += $data['minimumquestions'][$key];
                }

            }
        }
        if ($totalrequired > $data['totalrequired']){
            $errors['totalrequired']=get_string('totalrequiredislessthansumoftotalsforeachqtype', 'qcreate');
        }
        if (isset($data['allowed']['ALL']) && (count($data['allowed']) > 1)){
            $errors['allowed']=get_string('allandother', 'qcreate');
        }
        if (($data['timeclose'] !=0) && ($data['timeopen'] !=0) && ($data['timeclose'] <= $data['timeopen'])){
            $errors['timeopen']=get_string('openmustbemorethanclose', 'qcreate');
        }

        return $errors;
    }
}

?>

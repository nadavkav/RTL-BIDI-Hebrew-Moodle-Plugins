<?php  //$Id: edit_form.php,v 1.3 2009/05/04 21:13:33 mchurch Exp $

require_once($CFG->dirroot.'/course/edit_form.php');

class course_fn_edit_form extends course_edit_form {

    function definition() {
        $extraonly  = optional_param('extraonly', 0, PARAM_INT);    // full settings or only the extra ones.
        if (!$extraonly) {
            parent::definition();
        }
        $this->definition2($extraonly);
    }

    function definition2($extraonly) {
        global $USER, $CFG, $COURSE;

        $mform    =& $this->_form;

/// form definition with new course defaults
//--------------------------------------------------------------------------------
        if (!empty($this->params)) {
            foreach ($this->params as $param => $value) {
                $mform->addElement('hidden', $param, $value);
            }
        }

        // the upload manager is used directly in post precessing, moodleform::save_files() is not used yet
        $this->set_upload_manager(new upload_manager('logo', true, false, $this->_customdata['course'], false, $CFG->maxbytes, true, true));

        $mform->addElement('header','FN Course Tabs', 'FN Course Tabs');

        $mform->addElement('hidden','extraonly', $extraonly);

        $label = get_string('mainheading', 'format_fn');
        $mform->addElement('text','mainheading', $label,'maxlength="254" size="50"');
        $mform->setHelpButton('mainheading', array('mainheading', $label), true);
        $mform->setDefault('mainheading', get_string('defaultmainheading', 'format_fn'));
        $mform->setType('mainheading', PARAM_MULTILANG);

        $label = get_string('topicheading', 'format_fn');
        $mform->addElement('text','topicheading', $label,'maxlength="254" size="50"');
        $mform->setHelpButton('topicheading', array('topicheading', $label), true);
        $mform->setDefault('topicheading', get_string('defaulttopicheading', 'format_fn'));
        $mform->setType('topicheading', PARAM_MULTILANG);

        $mform->addElement('header','FN Activity Tracking', 'FN Activity Tracking');

        $choices["0"] = get_string("hide");
        $choices["1"] = get_string("show");
        $label = get_string('activitytracking', 'format_fn');
        $mform->addElement('select', 'activitytracking', $label, $choices);
        $mform->setHelpButton('activitytracking', array('activitytracking', $label), true);
        $mform->setDefault('activitytracking', '0');

        $mform->addElement('text','defreadconfirmmess', get_string('defreadconfirmmess', 'format_fn'), 'maxlength="254" size="50"');
        $mform->setHelpButton('defreadconfirmmess', array('text', get_string('helptext')), true);
        $mform->setDefault('defreadconfirmmess', get_string('defaultdefreadconfirmmess', 'format_fn'));
        $mform->setType('defreadconfirmmess', PARAM_RAW);

        $mform->addElement('header','FN Other', 'FN Other');

        unset($choices);
        $choices["0"] = get_string("hide");
        $choices["1"] = get_string("show");
        $label = get_string('showsection0', 'format_fn');
        $mform->addElement('select', 'showsection0', $label, $choices);
        $mform->setHelpButton('showsection0', array('showsection0', $label), true);
        $mform->setDefault('showsection0', '0');

        unset($choices);
        $choices['0'] = get_string("no");
        $choices['1'] = get_string("yes");
        $label = get_string('showonlysection0', 'format_fn');
        $mform->addElement('select', 'showonlysection0', $label, $choices);
        $mform->setHelpButton('showsection0', array('showonlysection0', $label), true);
        $mform->setDefault('showonlysection0', '0');

        unset($choices);
        $choices["-1"] = 'none';
        for ($i = 0; $i <= $this->_customdata['course']->numsections; $i++) {
            $choices["$i"] = 'Section '.$i;
        }
        $label = get_string('expforumsec', 'format_fn');
        $mform->addElement('select', 'expforumsec', $label, $choices);
        $mform->setHelpButton('expforumsec', array('expforumsec', $label), true);
        $mform->setDefault('expforumsec', '-1');

       unset($choices);
       $choices["0"] = get_string("no");
       $choices["1"] = get_string("yes");
       $label = get_string('usesitegroups', 'format_fn');
       $mform->addElement('select', 'usesitegroups', $label, $choices);
       $mform->setHelpButton('usesitegroups', array('usesitegroups', $label), true);
       $mform->setDefault('usesitegroups', '0');

       if (isset($CFG->sitegroupsmode) && ($CFG->sitegroupsmode == GRSITEGROUPS_G8REGGROUPS)) {
           $label = get_string('usesitegroupreg', 'format_fn');
           $mform->addElement('select', 'usesitegroupreg', $label, $choices);
           $mform->setHelpButton('usesitegroupreg', array('usesitegroupreg', $label), true);
           $mform->setDefault('usesitegroupreg', '0');
       }

       $mform->addElement('header','FN Header', 'FN Header');

       if (!empty($this->_customdata['course']->logo)) {
           $link = link_to_popup_window ('/file.php/'.$this->_customdata['course']->id.'/'.
                                         $this->_customdata['course']->logo, 'popup',
                                         $this->_customdata['course']->logo, 400, 500, 'Logo', 'none', true);
           $link = get_string('usinglogo', 'format_fn', $link);
           $checked = array('checked' => 'checked');
       } else {
           $link = get_string('notusinglogo', 'format_fn');
           $checked = null;
       }
       $mform->addElement('checkbox', 'uselogo', get_string('uselogo', 'format_fn'), '');

       $label = get_string('uploadlogo', 'format_fn');
       $mform->addElement('choosecoursefile', 'logo', $label, array('courseid'=>$COURSE->id));
       $mform->disabledIf('logo', 'uselogo');

       $label = get_string('showhelpdoc', 'format_fn');
       $selectgrp = array();
       $selectgrp[] = &MoodleQuickForm::createElement('checkbox', 'showhelpdoc', null, $label);
       $mform->addGroup($selectgrp, 'showhelpdoc', $label, ' ', false);
       $mform->setDefault('showhelpdoc', 0);

       $label = get_string('showclassforum', 'format_fn');
       $selectgrp = array();
       $selectgrp[] = &MoodleQuickForm::createElement('checkbox', 'showclassforum', null, $label);
       $mform->addGroup($selectgrp, 'showclassforum', $label, ' ', false);
       $mform->setDefault('showclassforum', 0);

       $label = get_string('showclasschat', 'format_fn');
       $selectgrp = array();
       $selectgrp[] = &MoodleQuickForm::createElement('checkbox', 'showclasschat', null, $label);
       $mform->addGroup($selectgrp, 'showclasschat', $label, ' ', false);
       $mform->setDefault('showclasschat', 0);

       $label = get_string('showgallery', 'format_fn');
       $selectgrp = array();
       $selectgrp[] = &MoodleQuickForm::createElement('checkbox', 'showgallery', null, $label);
       $mform->addGroup($selectgrp, 'showgallery', $label, ' ', false);
       $mform->setDefault('showgallery', 0);

       unset($choices);
       $choices['everyone'] = get_string('everyone', 'format_fn');
       $choices['allusers'] = get_string('allusers', 'format_fn');
       $choices['courseparticipants'] = get_string('courseparticipants', 'format_fn');
       $label = get_string('mycourseblockdisplay', 'format_fn');
       $mform->addElement('select', 'mycourseblockdisplay', $label, $choices);
       $mform->setHelpButton('mycourseblockdisplay', array('mycourseblockdisplay', $label), true);
       $mform->setDefault('mycourseblockdisplay', 'everyone');

       unset($choices);
       $choices["0"] = get_string("no");
       $choices["1"] = get_string("yes");
       $label = get_string('usemandatory', 'format_fn');
       $mform->addElement('select', 'usemandatory', $label, $choices);
       $mform->setHelpButton('usemandatory', array('usemandatory', $label), true);
       $mform->setDefault('usemandatory', '0');

        $mform->addElement('hidden', 'id', $this->_customdata['course']->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'formatsettings', '1');
        $mform->setType('formatsettings', PARAM_INT);

        /// Remove the already in place submit buttons and put them back at the end.
        if (!$extraonly) {
            $mform->removeElement('buttonar');
        }
        $this->add_action_buttons();

    }


/// perform some extra moodle validation
    function validation($data, $files){
		return true; // i was getting some errors ??? not sure ? (nadavkav)
        if (empty($data->extraonly)) {
            $errors = parent::validation($data, $files);
            if (0 == count($errors)){
                return true;
            } else {
                return $errors;
            }
        }
        return true;
    }

/// Handle any specific No Submit Buttons
    function no_submit_button_pressed() {
        global $CFG;

        $data = $this->_form->exportValues();

        if (isset($data['deletelogo']) && !empty($data['id']) && !empty($data['logofile'])) {
        /// If delete logo was pressed...
            $logo = $CFG->dataroot.'/'.$data['id'].'/'.$data['logofile'];
            if (unlink($logo)) {
                set_field('course_config_fn', 'value', '', 'courseid', $data['id'], 'variable', 'logo');
                $this->_customdata['course']->logo = '';
                $link = get_string('notusinglogo', 'format_fn');
                $dbgrp = $this->_form->getElement('dbgrp');
                $elements = $dbgrp->getElements();
                foreach (array_keys($elements) as $key) {
                    if ('dbuttont' == $dbgrp->getElementName($key)) {
                        $element =& $elements[$key];
                        $element->setValue($link);
                        break;
                    }
                }
            }
        }

        return parent::no_submit_button_pressed();
    }
}
?>

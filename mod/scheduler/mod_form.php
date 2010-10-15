<?php

/** 
* This view allows checking deck states
* 
* @package mod-scheduler
* @category mod
* @author Valery Fremaux
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*/

/**
* Requires and includes 
*/

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

/**
* overrides moodleform for test setup
*/
class mod_scheduler_mod_form extends moodleform_mod {

	function definition() {

	    global $CFG, $COURSE;
	    $mform    =& $this->_form;
	  
	    $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
	    $mform->setType('name', PARAM_CLEANHTML);
	    $mform->addRule('name', null, 'required', null, 'client');

	    $mform->addElement('htmleditor', 'description', get_string('description'));
	    $mform->setType('description', PARAM_RAW);
	    $mform->setHelpButton('description', array('writing', 'richtext'), false, 'editorhelpbutton');

	    $mform->addElement('text', 'staffrolename', get_string('staffrolename', 'scheduler'), array('size'=>'48'));
	    $mform->setType('name', PARAM_CLEANHTML);
	    $mform->setHelpButton('staffrolename', array('staffrolename', get_string('staffrolename', 'scheduler'), 'scheduler'));
	
	    $modeoptions['onetime'] = get_string('oneatatime', 'scheduler');
	    $modeoptions['oneonly'] = get_string('oneappointmentonly', 'scheduler');
	    $mform->addElement('select', 'schedulermode', get_string('mode', 'scheduler'), $modeoptions);
	    $mform->setHelpButton('schedulermode', array('appointmentmode', get_string('mode', 'scheduler'), 'scheduler'));

	    $reuseguardoptions[24] = 24 . ' ' . get_string('hours');
	    $reuseguardoptions[48] = 48 . ' ' . get_string('hours');
	    $reuseguardoptions[72] = 72 . ' ' . get_string('hours');
	    $reuseguardoptions[96] = 96 . ' ' . get_string('hours');
	    $reuseguardoptions[168] = 168 . ' ' . get_string('hours');
	    $mform->addElement('select', 'reuseguardtime', get_string('reuseguardtime', 'scheduler'), $reuseguardoptions);
	    $mform->setHelpButton('reuseguardtime', array('reuseguardtime', get_string('reuseguardtime', 'scheduler'), 'scheduler'));

	    $mform->addElement('text', 'defaultslotduration', get_string('defaultslotduration', 'scheduler'), array('size'=>'2'));
	    $mform->setType('defaultslotduration', PARAM_INT);
	    $mform->setHelpButton('defaultslotduration', array('defaultslotduration', get_string('defaultslotduration', 'scheduler'), 'scheduler'));
        $mform->setDefault('defaultslotduration', 15);

        $mform->addElement('modgrade', 'grade', get_string('grade'));
        $mform->setDefault('grade', 100);
        
        $yesno[0] = get_string('no');
        $yesno[1] = get_string('yes');
	    $mform->addElement('select', 'allownotifications', get_string('notifications', 'scheduler'), $yesno);
	    $mform->setHelpButton('allownotifications', array('notifications', get_string('notifications', 'scheduler'), 'scheduler'));

        $gradingstrategy[MEAN_GRADE] = get_string('meangrade', 'scheduler');
        $gradingstrategy[MAX_GRADE] = get_string('maxgrade', 'scheduler');
	    $mform->addElement('select', 'gradingstrategy', get_string('gradingstrategy', 'scheduler'), $gradingstrategy);
	    $mform->setHelpButton('gradingstrategy', array('gradingstrategy', get_string('gradingstrategy', 'scheduler'), 'scheduler'));

        $features = new stdClass;
        $features->groups = true;
        $features->groupings = true;
        $features->groupmembersonly = true;
        $this->standard_coursemodule_elements($features);

        $this->add_action_buttons();
    }

}

?>
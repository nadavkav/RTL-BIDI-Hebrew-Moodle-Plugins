<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: mod_form.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once("$CFG->dirroot/mod/webquestscorm/lib.php");


class mod_webquestscorm_mod_form extends moodleform_mod {
 

    function definition() {

	
        global $CFG, $WEBQUESTSCORM_SHOWRESULTS, $WEBQUESTSCORM_PUBLISH, $WEBQUESTSCORM_DISPLAY,$COURSE;

        $mform    =& $this->_form;

//-------------------------------------------------------------------------------

	if (empty($mform->name)) {
        	$mform->name = "";
    	}
    	if (empty($mform->course)) {
        	$mform->name = "";
    	}
    	if (empty($mform->intro)) {
        	$mform->intro = "";
    	}
    	if (empty($mform->resubmit)) {
        	$mform->resubmit = 0;
    	}
    	if (empty($mform->maxbytes)) {
        	$mform->maxbytes = $CFG->assignment_maxbytes;
    	}
    	if (empty($mform->emailteachers)) {
        	$mform->emailteachers = '';
    	}
    	$mform->grade='';
    	$mform->timeavailable='';
    	$mform->timedue='';
    	$mform->preventlate='';


        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('webquestscormname', 'webquestscorm'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
 	  $mform->addElement('modgrade', 'grade', get_string('grade'));
        $mform->setDefault('grade', 100);

 	  $mform->addElement('date_time_selector', 'timeavailable', get_string('availabledate', 'webquestscorm'), array('optional'=>true));
        $mform->setDefault('timeavailable', time());
        $mform->addElement('date_time_selector', 'timedue', get_string('duedate', 'webquestscorm'), array('optional'=>true));
        $mform->setDefault('timedue', time()+7*24*3600);

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $mform->addElement('select', 'preventlate', get_string('preventlate', 'assignment'), $ynoptions);
        $mform->setDefault('preventlate', 0);


//-------------------------------------------------------------------------------
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
	  $mform->addElement('header', 'typedesc', get_string("uploadfiles","webquestscorm"));
        $mform->addElement('select', 'resubmit', get_string("allowresubmit", "webquestscorm"), $ynoptions);
        $mform->setHelpButton('resubmit', array('resubmit', get_string('allowresubmit', 'webquestscorm'), 'webquestscorm'));
        $mform->setDefault('resubmit', 0);

        $mform->addElement('select', 'emailteachers', get_string("emailteachers", "webquestscorm"), $ynoptions);
        $mform->setHelpButton('emailteachers', array('emailteachers', get_string('emailteachers', 'webquestscorm'), 'webquestscorm'));
        $mform->setDefault('emailteachers', 0);

        $webquestscorms = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $webquestscorms[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $mform->addElement('select', 'maxbytes', get_string('maximumsize', 'assignment'), $webquestscorms);
        $mform->setDefault('maxbytes', $CFG->assignment_maxbytes);
  /*     
	  $mform->addElement('hidden', 'course', $mform->course);
	  $mform->addElement('hidden', "sesskey",$mform->sesskey);
	  $mform->addElement('hidden', 'coursemodule', $mform->coursemodule);
	  $mform->addElement('hidden', 'section', $mform->section);
	  $mform->addElement('hidden', 'module', $mform->module);
	  $mform->addElement('hidden', 'modulename', $mform->modulename);
	  $mform->addElement('hidden', 'instance', $mform->instance);
	  $mform->addElement('hidden', 'mode', $mform->mode);
*/
	  $mform->addElement('hidden', 'template', "topblue.css");

//-------------------------------------------------------------------------------
        $features = new stdClass;
        $features->groups = true;
        $features->groupings = true;
        $features->groupmembersonly = true;
        $features->gradecat = false;
        $this->standard_coursemodule_elements($features);

//-------------------------------------------------------------------------------
        $this->add_action_buttons();
	}


    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
?>

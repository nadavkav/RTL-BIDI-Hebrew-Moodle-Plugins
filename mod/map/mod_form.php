<?php

/**
 * mod_form.php
 * @package map
 * @author Ted Bowman <ted@tedbow.com>
 * @version 0.2
 * form for map instance
 *
 */
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_map_mod_form extends moodleform_mod {

	function definition() {
		 
		global $CFG;
		$mform    =& $this->_form;

		$mform->addElement('header', 'general', get_string('general', 'form'));
			
			$mform->addElement('text', 'name', get_string('mapname', 'map'), array('size'=>'64'));
			$mform->setType('name', PARAM_TEXT);
			$mform->addRule('name', null, 'required', null, 'client');
		//the google map key must have been entered with the module settings for maps to work
		if(map_config_ok()){
			//-------------------------------------------------------------------------------
			

			$mform->addElement('htmleditor', 'text', get_string('maptext', 'map'));
			$mform->setType('text', PARAM_RAW);
			$mform->addRule('text', null, 'required', null, 'client');
			$mform->setHelpButton('text', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');

			$mform->addElement('format', 'format', get_string('format'));

			
			$menuoptions=array();
			$menuoptions[0] = get_string('no');
			$menuoptions[1] = get_string('yes');

			$mform->addElement('header', 'configuration', get_string('configuration', 'map'));
			if(!isset($CFG->map_provider) || $CFG->map_provider == "choose"){
				$mform->addElement('select','provider',get_string('mapprovider','map'), map_get_working_provider_array());
			}
			$mform->addElement('select','studentlocations',get_string('studentlocations','map'), $menuoptions);
			$mform->setDefault('studentlocations',"0");
			$mform->addElement('select', 'requireok', get_string('requireok', 'map'), $menuoptions);

			$mform->addElement('select','extralocations',get_string('extralocations','map'), $menuoptions);
			$menuoptions[0] = get_string('shownoaddress','map');
			$menuoptions[1] = get_string('showaddressonly','map');
			$menuoptions[2] = get_string('showaddressandpoint','map');
			$mform->addElement('select','showaddress4extra',get_string('showaddress4extra','map'), $menuoptions);
			//$mform->setHelpButton('limitanswers', array('limit', get_string('limit', 'choice'), 'choice'));

			//-------------------------------------------------------------------------------
			$this->standard_coursemodule_elements();
			//-------------------------------------------------------------------------------
			$this->add_action_buttons();
		}else{
			//don't allow adding/editing of maps without key
			$mform->setElementError('name',get_string("badconfig","map"));
		}
	
	}

}
?>
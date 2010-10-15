<?php //$Id: mod_form.php,v 1.0 2009/09/28 matbury Exp $

/*
*    Copyright (C) 2009  Matt Bury - matbury@gmail.com - http://matbury.com/
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* Creates instance of SWF activity module
* Adapted from mod_form.php template by Jamie Pratt
*
* By Matt Bury - http://matbury.com/ - matbury@gmail.com
* @licence http://www.gnu.org/copyleft/gpl.html GNU Public Licence
*
* DB Table name (mdl_)swf
*
* REQUIRED PARAMETERS:
* @param swfurl
* @param width
* @param height
* @param version
*
* LEARNING INTERACTION DATA PARAMETERS:
* @param interaction
* @param xmlurl
* @param flashvar1
* @param flashvar2
* @param flashvar3
* @param grading
*
* OPTIONAL PARAMETERS:
* @param apikey
* @param play
* @param loopswf
* @param menu
* @param quality
* @param scale
* @param salign
* @param wmode
* @param bgcolor
* @param devicefont
* @param seamlesstabbing
* @param allowfullscreen
* @param allowscriptaccess
* @param allownetworking
* @param align
* @param skin
* @param configxml
* 
*/

require_once ('moodleform_mod.php');

class mod_swf_mod_form extends moodleform_mod {

	function definition() {

		global $COURSE;
		$mform    =& $this->_form;

//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are shown
        $mform->addElement('header', 'general', get_string('general', 'form'));
    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('swfname', 'swf'), array('size'=>'64'));
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', null, 'required', null, 'client');
    /// Adding the optional "intro" and "introformat" pair of fields
    	$mform->addElement('htmleditor', 'intro', get_string('swfintro', 'swf'));
		$mform->setType('intro', PARAM_RAW);
		$mform->addRule('intro', get_string('required'), 'required', null, 'client');
        $mform->setHelpButton('intro', array('writing', 'richtext'), false, 'editorhelpbutton');

        $mform->addElement('format', 'introformat', get_string('format'));

//-------------------------------------------------------------------------------
	
	// Example from: http://docs.moodle.org/en/Development:lib/formslib.php_Form_Definition
	// REQUIRED header
	$mform->addElement('header', 'swfrequired', get_string('swfrequired', 'swf'));
	
	//swfurl - SWF file select/upload
	$mform->addElement('choosecoursefile', 'swfurl', get_string('swfurl', 'swf'), array('courseid'=>$COURSE->id));
	$mform->addRule('swfurl', get_string('required'), 'required', null, 'client');
	$mform->setHelpButton('swfurl', array('swf_swfurl', get_string('swfurl', 'swf'), 'swf'));
	
	//width
	$mform->addElement('text', 'width', get_string('width', 'swf'), array('size'=>'9'));
	$mform->addRule('width', get_string('required'), 'required', null, 'client');
	$mform->setHelpButton('width', array('swf_width', get_string('width', 'swf'), 'swf'));
	$mform->setDefault('width', '900');
	
	//height
	$mform->addElement('text', 'height', get_string('height', 'swf'), array('size'=>'9'));
	$mform->addRule('height', get_string('required'), 'required', null, 'client');
	$mform->setHelpButton('height', array('swf_height', get_string('height', 'swf'), 'swf'));
	$mform->setDefault('height', '480');
	
	//version
	$mform->addElement('text', 'version', get_string('version', 'swf'), array('size'=>'9'));
	$mform->addRule('version', get_string('required'), 'required', null, 'client');
	$mform->setHelpButton('version', array('swf_version', get_string('version', 'swf'), 'swf'));
	$mform->setDefault('version', '9.0.115');
	
//----------------------------------------------------------------------------------------
	// OPTIONAL PARAMETERS
	
	// AMF header ----------------------------------------------------------------------------- 
	$mform->addElement('header', 'amf', get_string('amf', 'swf'));
	/*
	//amftable (AMF)
	$swf_tables = swf_get_course_tables(); // in mod/swf/lib.php
	$mform->addElement('select', 'amftable', get_string('amftable', 'swf'), $swf_tables);
	$mform->setHelpButton('amftable', array('swf_amftable', get_string('amftable', 'swf'), 'swf'));*/
	//interaction (AMF)
	$swf_interactions = swf_get_interactions($COURSE->id); // in mod/swf/lib.php
	$mform->addElement('select', 'interaction', get_string('interactions', 'swf'), swf_get_interactions($COURSE->id));
	$mform->setHelpButton('interaction', array('swf_interaction', get_string('interactions', 'swf'), 'swf'));
	
	// XML header ----------------------------------------------------------------------------- 
	$mform->addElement('header', 'xml', get_string('xml', 'swf'));
	//xmlurl
	$mform->addElement('choosecoursefile', 'xmlurl', get_string('xmlurl', 'swf'), array('courseid'=>$COURSE->id));
	$mform->setHelpButton('xmlurl', array('swf_xmlurl', get_string('xmlurl', 'swf'), 'swf'));
	
	// FlashVars header -----------------------------------------------------------------------
	$mform->addElement('header', 'flashvars', get_string('flashvars', 'swf'));
	//attributes for flashvars text areas
	$flashvars_att = 'wrap="virtual" rows="3" cols="57"';
	//flashvar1
	$mform->addElement('textarea', 'flashvar1', get_string("flashvar1", "swf"), $flashvars_att);
	$mform->setHelpButton('flashvar1', array('swf_flashvars', get_string('flashvar1', 'swf'), 'swf'));
	//flashvar2
	$mform->addElement('textarea', 'flashvar2', get_string("flashvar2", "swf"), $flashvars_att);
	$mform->setHelpButton('flashvar2', array('swf_flashvars', get_string('flashvar2', 'swf'), 'swf'));
	//flashvar3
	$mform->addElement('textarea', 'flashvar3', get_string("flashvar3", "swf"), $flashvars_att);
	$mform->setHelpButton('flashvar3', array('swf_flashvars', get_string('flashvar3', 'swf'), 'swf'));
	
	// Grading header -----------------------------------------------------------------------
	$mform->addElement('header', 'gradeweight', get_string('grading', 'swf'));
	// grading
	$mform->addElement('select', 'grading', get_string('grading', 'swf'), swf_list_grading());
	$mform->setDefault('grading', '100');
	$mform->setHelpButton('grading', array('swf_grading', get_string('grading', 'swf'), 'swf'));

	
//----------------------------------------------------------------------------------------
	// Advanced header
	$mform->addElement('header', 'advanced', get_string('advanced', 'swf'));
	
	// skin
	$mform->addElement('select', 'skin', get_string('skin', 'swf'), swf_list_skins());
	$mform->setHelpButton('skin',  array('swf_skin', get_string('skin', 'swf'), 'swf'));
	$mform->setDefault('skin', 'middle');
	$mform->setAdvanced('skin');
	
	//apikey
	$mform->addElement('text', 'apikey', get_string('apikey', 'swf'), array('size'=>'75'));
	$mform->setHelpButton('apikey',  array('swf_apikey', get_string('apikey', 'swf'), 'swf'));
	$mform->setAdvanced('apikey');
	
	// align
	$mform->addElement('select', 'align', get_string('align', 'swf'), swf_list_align());
	$mform->setHelpButton('align',  array('swf_align', get_string('align', 'swf'), 'swf'));
	$mform->setDefault('align', 'middle');
	$mform->setAdvanced('align');
	
	//play
	$mform->addElement('select', 'play', get_string('play', 'swf'), swf_list_truefalse());
	$mform->setHelpButton('play', array('swf_play', get_string('play', 'swf'), 'swf'));
	$mform->setDefault('play', 'true');
	$mform->setAdvanced('play');
	
	//loop
	$mform->addElement('select', 'loopswf', get_string('loop', 'swf'), swf_list_truefalse());
	$mform->setHelpButton('loopswf', array('swf_loop', get_string('loop', 'swf'), 'swf'));
	$mform->setDefault('loopswf', 'true');
	$mform->setAdvanced('loopswf');

	//menu
	$mform->addElement('select', 'menu', get_string('menu', 'swf'), swf_list_truefalse());
	$mform->setHelpButton('menu', array('swf_menu', get_string('menu', 'swf'), 'swf'));
	$mform->setDefault('menu', 'true');
	$mform->setAdvanced('menu');
	
	//quality
	$mform->addElement('select', 'quality', get_string('quality', 'swf'), swf_list_quality());
	$mform->setHelpButton('quality', array('swf_quality', get_string('quality', 'swf'), 'swf'));
	$mform->setDefault('quality', 'best');
	$mform->setAdvanced('quality');
	
	//scale
	$mform->addElement('select', 'scale', get_string('scale', 'swf'), swf_list_scale());
	$mform->setHelpButton('scale', array('swf_scale', get_string('scale', 'swf'), 'swf'));
	$mform->setDefault('scale', 'noscale');
	$mform->setAdvanced('scale');
	
	//salign
	$mform->addElement('select', 'salign', get_string('salign', 'swf'), swf_list_salign());
	$mform->setHelpButton('salign', array('swf_salign', get_string('salign', 'swf'), 'swf'));
	$mform->setDefault('salign', 'tl');
	$mform->setAdvanced('salign');
	
	//wmode
	$mform->addElement('select', 'wmode', get_string('wmode', 'swf'), swf_list_wmode());
	$mform->setHelpButton('wmode', array('swf_wmode', get_string('wmode', 'swf'), 'swf'));
	$mform->setDefault('wmode', 'opaque');
	$mform->setAdvanced('wmode');
	
	//bgcolor
	$mform->addElement('text', 'bgcolor', get_string('bgcolor', 'swf'), array('size'=>'20'));
	$mform->setHelpButton('bgcolor', array('swf_bgcolor', get_string('bgcolor', 'swf'), 'swf'));
	$mform->setDefault('bgcolor', '');
	$mform->setAdvanced('bgcolor');
	
	//devicefont
	$mform->addElement('select', 'devicefont', get_string('devicefont', 'swf'), swf_list_truefalse());
	$mform->setHelpButton('devicefont', array('swf_devicefont', get_string('devicefont', 'swf'), 'swf'));
	$mform->setDefault('devicefont', '');
	$mform->setAdvanced('devicefont');
	
	//seamlesstabbing
	$mform->addElement('select', 'seamlesstabbing', get_string('seamlesstabbing', 'swf'), swf_list_truefalse());
	$mform->setHelpButton('seamlesstabbing', array('swf_seamlesstabbing', get_string('seamlesstabbing', 'swf'), 'swf'));
	$mform->setDefault('seamlesstabbing', 'true');
	$mform->setAdvanced('seamlesstabbing');
	
	//allowfullscreen
	$mform->addElement('select', 'allowfullscreen', get_string('allowfullscreen', 'swf'), swf_list_truefalse());
	$mform->setHelpButton('allowfullscreen', array('swf_allowfullscreen', get_string('allowfullscreen', 'swf'), 'swf'));
	$mform->setDefault('allowfullscreen', 'false');
	$mform->setAdvanced('allowfullscreen');
	
	//allowscriptaccess
	$mform->addElement('select', 'allowscriptaccess', get_string('allowscriptaccess', 'swf'), swf_list_allowscriptaccess());
	$mform->setHelpButton('allowscriptaccess', array('swf_allowscriptaccess', get_string('allowscriptaccess', 'swf'), 'swf'));
	$mform->setDefault('allowscriptaccess', 'sameDomain');
	$mform->setAdvanced('allowscriptaccess');
	
	//allownetworking
	$mform->addElement('select', 'allownetworking', get_string('allownetworking', 'swf'), swf_list_allownetworking());
	$mform->setHelpButton('allownetworking', array('swf_allownetworking', get_string('allownetworking', 'swf'), 'swf'));
	$mform->setDefault('allownetworking', 'internal');
	$mform->setAdvanced('allownetworking');
	
	//configxml - Configuration XML file select/upload
	$mform->addElement('choosecoursefile', 'configxml', get_string('configxml', 'swf'), array('courseid'=>$COURSE->id));
	$mform->setHelpButton('configxml', array('swf_configxml', get_string('configxml', 'swf'), 'swf'));
	

// ------------------------------------------------------------------------------

//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
		$this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

	}
}

?>

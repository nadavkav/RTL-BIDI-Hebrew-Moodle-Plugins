<?php
require_once ('moodleform_mod.php');
global $CFG;
global $COURSE;

$LASTCONF = mysql_fetch_assoc(mysql_query("SELECT		*
									   	   FROM			".$CFG->prefix."econsole
										   WHERE		course = ".$COURSE->id."
									       ORDER BY 	timecreated
										   DESC
										   LIMIT		1
									      "));
global $LASTCONF;

class mod_econsole_mod_form extends moodleform_mod {

	function definition() {
		global $LASTCONF;
		global $COURSE;
		$mform    =& $this->_form;
//-------------------------------------------------------------------------------
        //Add the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));
        //Add the standard "name" field
        $mform->addElement('text', 'name', get_string('econsolename', 'econsole'), array('size'=>'64'));
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', null, 'required', null, 'client');
		//Content
    	$mform->addElement('htmleditor', 'content', get_string('econsolecontent', 'econsole'), array('cols'=>85, 'rows'=>30));
		$mform->setType('content', PARAM_RAW);
		$mform->addRule('content', get_string('required'), 'required', null, 'client');
		//diving
//        $mform->addElement('advcheckbox', 'diving', '', get_string('econsolediving', 'econsole'));
		//Appearance
//-------------------------------------------------------------------------------
//      $mform->setAdvanced('showlesson');
        $mform->addElement('header', 'appearance', get_string('econsoleappearance', 'econsole'));	
		//Get themes
		$dirs = scandir("../mod/econsole/theme");
		foreach($dirs as $key => $value){
			if(is_dir("../mod/econsole/theme/".$value) && ($value != ".") && ($value != "..")){
				$themes[$value] = $value;
			}
		}
        $mform->addElement('select', 'theme', get_string('econsoletheme', 'econsole'), $themes);
		$theme = $LASTCONF["theme"] == "" ? "default" : $LASTCONF["theme"];
	    $mform->setDefault('theme', $theme);
		$mform->addRule('theme', null, 'required', null, 'client');
		//Unit
    	$mform->addElement('text', 'unitstring', get_string('econsoleheader', 'econsole')." 1");
		$mform->setHelpButton('unitstring', array('unitstring', html_entity_decode(get_string('econsoleheader', 'econsole'), ENT_QUOTES, "UTF-8")." 1", 'econsole'));		
		$mform->setType('unitstring', PARAM_TEXT);
		$unitstring = $LASTCONF["unitstring"] == "" ? html_entity_decode(get_string('econsoleunit', 'econsole'), ENT_QUOTES, "UTF-8") : $LASTCONF["unitstring"];
		$mform->setDefault('unitstring', $unitstring);
		$mform->addRule('unitstring', get_string('required'), 'required', null, 'client');	
		//Show
        $mform->addElement('advcheckbox', 'showunit', '', get_string('econsoleshow', 'econsole'));
		$showunit = $LASTCONF["showunit"] == "" ? "1" : $LASTCONF["showunit"];		
		$mform->setDefault('showunit', $showunit);
		//Lesson
    	$mform->addElement('text', 'lessonstring', get_string('econsoleheader', 'econsole')." 2");
		$mform->setHelpButton('lessonstring', array('lessonstring', html_entity_decode(get_string('econsoleheader', 'econsole'), ENT_QUOTES, "UTF-8")." 2	", 'econsole'));			
		$mform->setType('lessonstring', PARAM_TEXT);
		$lessonstring = $LASTCONF["lessonstring"] == "" ? html_entity_decode(get_string('econsolelesson', 'econsole'), ENT_QUOTES, "UTF-8") : $LASTCONF["lessonstring"];		
		$mform->setDefault('lessonstring', $lessonstring);
		$mform->addRule('lessonstring', get_string('required'), 'required', null, 'client');
		//Show
		$mform->addElement('advcheckbox', 'showlesson', '', get_string('econsoleshow', 'econsole'));
		$showlesson = $LASTCONF["showlesson"] == "" ? "1" : $LASTCONF["showlesson"];				
		$mform->setDefault('showlesson', $showlesson);
        $mform->addElement('choosecoursefile', 'imagebartop', get_string('econsoleimagetop', 'econsole'));	
		$mform->setHelpButton('imagebartop', array('imagebartop', get_string('econsoleimagetop', 'econsole'), 'econsole'));		
		//$mform->setHelpButton('imagebartop', array('/course/scales.php?id='. $COURSE->id .'&amp;list=true', get_string('econsolemore', 'econsole'), 'econsole', 400, 500, get_string('econsoleimagetophelp', 'econsole'), 'none', false), 'link_to_popup_window');
        $mform->setDefault('imagebartop', $LASTCONF["imagebartop"]);		
        $mform->addElement('choosecoursefile', 'imagebarbottom', get_string('econsoleimagebottom', 'econsole'));	
		$mform->setHelpButton('imagebarbottom', array('imagebarbottom', get_string('econsoleimagebottom', 'econsole'), 'econsole'));
		//$mform->setHelpButton('imagebarbottom', array('/course/scales.php?id='. $COURSE->id .'&amp;list=true', get_string('econsolemore', 'econsole'), 'econsole', 400, 500, get_string('econsoleimagebottomhelp', 'econsole'), 'none', false), 'link_to_popup_window');
        $mform->setDefault('imagebarbottom', $LASTCONF["imagebarbottom"]);
		//Advanced
//-------------------------------------------------------------------------------
      	$mform->addElement('header', 'advanced', get_string('econsoleadvanced', 'econsole'));
		//Plugins
		$mform->addElement('advcheckbox', 'glossary', '', get_string('econsoleglossary', 'econsole'));
		$mform->setAdvanced('glossary');
		$glossary = $LASTCONF["glossary"] == "" ? "1" : $LASTCONF["glossary"];
		$mform->setDefault('glossary', $glossary);
		$mform->addElement('advcheckbox', 'journal', '', get_string('econsolejournal', 'econsole'));
		$mform->setAdvanced('journal');
		$journal = $LASTCONF["journal"] == "" ? "1" : $LASTCONF["journal"];
		$mform->setDefault('journal', $journal);
		$mform->addElement('advcheckbox', 'chat', '', get_string('econsolechat', 'econsole'));
		$mform->setAdvanced('chat');
		$chat = $LASTCONF["chat"] == "" ? "1" : $LASTCONF["chat"];
		$mform->setDefault('chat', $chat);				
		$mform->addElement('advcheckbox', 'forum', '', get_string('econsoleforum', 'econsole'));
		$mform->setAdvanced('forum');
		$forum = $LASTCONF["forum"] == "" ? "1" : $LASTCONF["forum"];
		$mform->setDefault('forum', $forum);
		$mform->addElement('advcheckbox', 'choice', '', get_string('econsolechoice', 'econsole'));
		$mform->setAdvanced('choice');
		$choice = $LASTCONF["choice"] == "" ? "1" : $LASTCONF["choice"];
		$mform->setDefault('choice', $choice);		
		$mform->addElement('advcheckbox', 'wiki', '', get_string('econsolewiki', 'econsole'));
		$mform->setAdvanced('wiki');
		$wiki = $LASTCONF["wiki"] == "" ? "1" : $LASTCONF["wiki"];
		$mform->setDefault('wiki', $wiki);		
		$mform->addElement('advcheckbox', 'assignment', '', get_string('econsoleassignment', 'econsole'));
		$mform->setAdvanced('assignment');
		$assignment = $LASTCONF["assignment"] == "" ? "1" : $LASTCONF["assignment"];
		$mform->setDefault('assignment', $assignment);																	
		$mform->addElement('advcheckbox', 'quiz', '', get_string('econsolequiz', 'econsole'));
		$mform->setAdvanced('quiz');
		$quiz = $LASTCONF["quiz"] == "" ? "1" : $LASTCONF["quiz"];
		$mform->setDefault('quiz', $quiz);				
		//URLs
    	$mform->addElement('text', 'url1name', get_string('econsolename', 'econsole'));
		$mform->setAdvanced('url1name');
		$mform->setDefault('url1name', $LASTCONF["url1name"]);
    	$mform->addElement('text', 'url1', get_string('econsoleurl', 'econsole'));
		$mform->setAdvanced('url1');	
		$mform->setDefault('url1', $LASTCONF["url1"]);
    	$mform->addElement('text', 'url2name', get_string('econsolename', 'econsole'));
		$mform->setAdvanced('url2name');
		$mform->setDefault('url2name', $LASTCONF["url2name"]);
    	$mform->addElement('text', 'url2', get_string('econsoleurl', 'econsole'));
		$mform->setAdvanced('url2');
		$mform->setDefault('url2', $LASTCONF["url2"]);
    	$mform->addElement('text', 'url3name', get_string('econsolename', 'econsole'));
		$mform->setAdvanced('url3name');
		$mform->setDefault('url3name', $LASTCONF["url3name"]);
    	$mform->addElement('text', 'url3', get_string('econsoleurl', 'econsole'));
		$mform->setAdvanced('url3');
		$mform->setDefault('url3', $LASTCONF["url3"]);
    	$mform->addElement('text', 'url4name', get_string('econsolename', 'econsole'));
		$mform->setAdvanced('url4name');
		$mform->setDefault('url4name', $LASTCONF["url4name"]);
    	$mform->addElement('text', 'url4', get_string('econsoleurl', 'econsole'));
		$mform->setAdvanced('url4');
		$mform->setDefault('url4', $LASTCONF["url4"]);	
    	$mform->addElement('text', 'url5name', get_string('econsolename', 'econsole'));
		$mform->setAdvanced('url5name');
		$mform->setDefault('url5name', $LASTCONF["url5name"]);
    	$mform->addElement('text', 'url5', get_string('econsoleurl', 'econsole'));
		$mform->setAdvanced('url5');
		$mform->setDefault('url5', $LASTCONF["url5"]);		
    	$mform->addElement('text', 'url6name', get_string('econsolename', 'econsole'));
		$mform->setAdvanced('url6name');
		$mform->setDefault('url6name', $LASTCONF["url6name"]);
    	$mform->addElement('text', 'url6', get_string('econsoleurl', 'econsole'));
		$mform->setAdvanced('url6');
		$mform->setDefault('url6', $LASTCONF["url6"]);						
//-------------------------------------------------------------------------------
        //Add standard elements, common to all modules
		$this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        //Add standard buttons, common to all modules
        $this->add_action_buttons();
	}
}
?>

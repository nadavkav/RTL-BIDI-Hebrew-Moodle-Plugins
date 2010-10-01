<?php //$Id: mod_form.php,v 1.2.2.3 2009/03/19 12:23:11 mudrd8mz Exp $

/**
 * This file defines the main hotquestion configuration form
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
 *             hotquestion type (index.php) and in the header
 *             of the hotquestion main page (view.php).
 *   - introformat: The format used to write the contents
 *             of the intro field. It automatically defaults
 *             to HTML when the htmleditor is used and can be
 *             manually selected if the htmleditor is not used
 *             (standard formats are: MOODLE, HTML, PLAIN, MARKDOWN)
 *             See lib/weblib.php Constants and the format_text()
 *             function for more info
 */

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_hotquestion_mod_form extends moodleform_mod {

    function definition() {

        global $COURSE;
        $mform =& $this->_form;

//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('hotquestionname', 'hotquestion'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

    /// Adding the required "intro" field to hold the description of the instance
        $mform->addElement('htmleditor', 'intro', get_string('hotquestionintro', 'hotquestion'));
        $mform->setType('intro', PARAM_RAW);
        $mform->addRule('intro', get_string('required'), 'required', null, 'client');
        $mform->setHelpButton('intro', array('writing', 'richtext'), false, 'editorhelpbutton');

    /// Adding "introformat" field
        $mform->addElement('format', 'introformat', get_string('format'));

    /// Adding 'anonymouspost' field
        $mform->addElement('selectyesno', 'anonymouspost', get_string('allowanonymouspost', 'hotquestion'));
        $mform->setDefault('anonymouspost', '1');

//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $features->groups = false;
        $features->groupings = false;
        $features->groupmembersonly = false;
        $features->outcomes = false;
        $features->gradecat = false;
        $features->idnumber = false;
        $this->standard_coursemodule_elements($features);
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

    }
}

//Form for submitting question
class hotquestion_form extends moodleform {
    var $anonymouspost;

    function hotquestion_form($anonymouspost) {
        $this->anonymouspost = $anonymouspost;
        parent::moodleform();
    }
	
	function definition() {
		global $CFG, $cm;

		$mform =& $this->_form;
		$mform->addElement('textarea', 'question', get_string("inputquestion", "hotquestion"), 'wrap="virtual" rows="3" cols="50"');
        $mform->setType('question', PARAM_TEXT);
		$mform->addElement('hidden', 'id', $cm->id);
		
        $submitgroup = array();
        $submitgroup[] =& $mform->createElement('submit', 'submitbutton', get_string('post'));
        if ($this->anonymouspost) {
            $submitgroup[] =& $mform->createElement('checkbox', 'anonymous', '', get_string('displayasanonymous', 'hotquestion'));
        }
        $mform->addGroup($submitgroup);

    }
}
?>

<?php
require_once ('moodleform_mod.php');
require_once(dirname(__FILE__).'/ouwiki.php');

class mod_ouwiki_mod_form extends moodleform_mod {

	function definition() {
        global $CFG;
        $mform    =& $this->_form;

        // Name and summary
        $mform->addElement('text', 'name', get_string('name'),array('size'=>'50'));// size change (nadavkav patch)
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement('htmleditor', 'summary', get_string('summary'),array('rows'=>'25')); // size change (nadavkav patch)
        $mform->setType('summary', PARAM_CLEAN);
        $mform->setHelpButton('summary', array('summary', get_string('summary', 'ouwiki'), 'ouwiki'));

        // Subwikis
        $subwikisoptions = array();
        $subwikisoptions[OUWIKI_SUBWIKIS_SINGLE] = get_string('subwikis_single','ouwiki');
        $subwikisoptions[OUWIKI_SUBWIKIS_GROUPS] = get_string('subwikis_groups','ouwiki');
        $subwikisoptions[OUWIKI_SUBWIKIS_INDIVIDUAL] = get_string('subwikis_individual','ouwiki');
        $mform->addElement('select', 'subwikis', get_string("subwikis", "ouwiki"), $subwikisoptions);
        $mform->setHelpButton('subwikis', array("subwikis", get_string("subwikis", "ouwiki"), "ouwiki"));

        // Commenting
        $commentoptions = array('default' => get_string('default'),
                        'none' => get_string('nocommentsystem', 'ouwiki'),
                        'annotations' => get_string('annotationsystem', 'ouwiki'),
                        'persection' => get_string('persectionsystem', 'ouwiki'),
                        'both' => get_string('bothcommentsystems', 'ouwiki'));
        $mform->addElement('select', 'commenting', get_string('commenting', 'ouwiki'), $commentoptions);
        $mform->setHelpButton('commenting', array("commenting", get_string('commenting', 'ouwiki'), "ouwiki"));

        // Editing timeout
        $timeoutoptions = array();
        $timeoutoptions[0] = get_string('timeout_none','ouwiki');
        $timeoutoptions[15*60] = get_string('numminutes', '', 15);
        $timeoutoptions[30*60] = get_string('numminutes', '', 30);
        $timeoutoptions[60*60] = get_string('numminutes', '', 60);
        $timeoutoptions[120*60] = get_string('numhours', '', 2);
        $timeoutoptions[240*60] = get_string('numhours', '', 4);
        $timeoutoptions[3*60] = '3 minutes [TEMP]';
        $mform->addElement('select', 'timeout', get_string("timeout", "ouwiki"), $timeoutoptions);
        $mform->setHelpButton('timeout', array("timeout", get_string("timeout", "ouwiki"), "ouwiki"));

        // Read-only controls
        $mform->addElement('date_selector', 'editbegin', get_string('editbegin','ouwiki'),array('optional'=>true));
        $mform->setHelpButton('editbegin', array('editbeginend', get_string('editbegin'),'ouwiki'));
        $mform->addElement('date_selector', 'editend', get_string('editend','ouwiki'),array('optional'=>true));
        $mform->setHelpButton('editend', array('editbeginend', get_string('editend'),'ouwiki'));

        if(empty($this->_cm)) {
            // Template (only on creation)
            global $COURSE;
            $mform->addElement('file', 'template', get_string('template','ouwiki'));
            $mform->setHelpButton('template', array("template", get_string("template", "ouwiki"), "ouwiki"));
        } else {
            // TODO Print template details
        }

        // Standard stuff
        $this->standard_coursemodule_elements((object)array("groupings"=>true,"groups"=>true,"groupmembersonly"=>true));

        if(class_exists('ouflags')) {
            // insitu editing
            global $COURSE;
            if(has_capability('local/course:revisioneditor', get_context_instance(CONTEXT_COURSE, $COURSE->id), null, false)) {
                include_once($CFG->dirroot.'/local/insitu/lib.php');
                oci_mod_setup_form($mform, $this, FALSE);
            }
        }

        // Don't show group mode selector because it is implied by the above
        $this->add_action_buttons();
	}

    function add_completion_rules() {
        $mform =& $this->_form;

        $group=array();
        $group[] =& $mform->createElement('checkbox', 'completionpagesenabled', ' ', get_string('completionpages','ouwiki'));
        $group[] =& $mform->createElement('text', 'completionpages', ' ', array('size'=>3));
        $mform->setType('completionpages',PARAM_INT);
        $mform->addGroup($group, 'completionpagesgroup', get_string('completionpagesgroup','ouwiki'), array(' '), false);
        $mform->setHelpButton('completionpagesgroup', array('completion', get_string('completionpageshelp', 'ouwiki'), 'ouwiki'));
        $mform->disabledIf('completionpages','completionpagesenabled','notchecked');

        $group=array();
        $group[] =& $mform->createElement('checkbox', 'completioneditsenabled', ' ', get_string('completionedits','ouwiki'));
        $group[] =& $mform->createElement('text', 'completionedits', ' ', array('size'=>3));
        $mform->setType('completionedits',PARAM_INT);
        $mform->addGroup($group, 'completioneditsgroup', get_string('completioneditsgroup','ouwiki'), array(' '), false);
        $mform->setHelpButton('completioneditsgroup', array('completion', get_string('completioneditshelp', 'ouwiki'), 'ouwiki'));
        $mform->disabledIf('completionedits','completioneditsenabled','notchecked');

        return array('completionpagesgroup','completioneditsgroup');
    }

    function completion_rule_enabled($data) {
        return
            ((!empty($data['completionpagesenabled']) && $data['completionpages']!=0)) ||
            ((!empty($data['completioneditsenabled']) && $data['completionedits']!=0));
    }

    function get_data() {
        $data=parent::get_data();
        if(!$data) {
            return false;
        }
        // Turn off completion settings if the checkboxes aren't ticked
        $autocompletion=!empty($data->completion) && $data->completion==COMPLETION_TRACKING_AUTOMATIC;
        if(empty($data->completionpagesenabled) || !$autocompletion) {
            $data->completionpages=0;
        }
        if(empty($data->completioneditsenabled) || !$autocompletion) {
            $data->completionedits=0;
        }
        return $data;
    }

    function data_preprocessing(&$default_values){
        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $default_values['completionpagesenabled']=
            !empty($default_values['completionpages']) ? 1 : 0;
        if(empty($default_values['completionpages'])) {
            $default_values['completionpages']=1;
        }
        $default_values['completioneditsenabled']=
            !empty($default_values['completionedits']) ? 1 : 0;
        if(empty($default_values['completionedits'])) {
            $default_values['completionedits']=1;
        }
    }

}
?>

<?php  // $Id: mod_form.php,v 1.1 2009/12/21 01:01:26 michaelpenne Exp $
/**
 * Form to define a new instance of this module or edit an 
 * existing instance.  It is used from /course/modedit.php.
 *
 * @version $Id: mod_form.php,v 1.1 2009/12/21 01:01:26 michaelpenne Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package pagemenu
 **/

require_once('moodleform_mod.php');
require_once($CFG->dirroot.'/mod/pagemenu/locallib.php');

class mod_pagemenu_mod_form extends moodleform_mod {

    function definition() {
        $mform =& $this->_form;

    /// Our general settings
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'30'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('select', 'render', get_string('menurenderstyle', 'pagemenu'), pagemenu_get_renderer_menu());
        $mform->setDefault('render', 'list');

        $mform->addElement('checkbox', 'displayname', get_string('displayname', 'pagemenu'));
        $mform->setHelpButton('displayname', array('displayname', get_string('displayname', 'pagemenu'), 'pagemenu'));

        $mform->addElement('checkbox', 'useastab', get_string('useastab', 'pagemenu'));
        $mform->setHelpButton('useastab', array('useastab', get_string('useastab', 'pagemenu'), 'pagemenu'));

        $mform->addElement('text', 'taborder', get_string('taborder', 'pagemenu'), array('size'=>'4'));
        $mform->setDefault('taborder', 0);
        $mform->setType('taborder', PARAM_INT);
        $mform->addRule('taborder', null, 'required', null, 'client');
        $mform->addRule('taborder', null, 'numeric', null, 'client');
        $mform->setHelpButton('taborder', array('taborder', get_string('taborder', 'pagemenu'), 'pagemenu'));

    /// Standard mod elements
        $features = new object();
        $features->groups = false;
        $features->idnumber = false;
        $features->gradecat = false;
        $features->outcomes = false;

        $this->standard_coursemodule_elements($features);

    /// Buttons
        $this->add_action_buttons();
    }

    function definition_after_data() {
        $mform =& $this->_form;

    /// Once form is submitted, check to make sure our checkboxes are set to something
        if ($this->is_submitted()) {
            $values = &$mform->_submitValues;

            foreach (array('useastab', 'displayname') as $key) {
                if (!isset($values[$key])) {
                    $values[$key] = 0;
                }
            }
        }
    }
}
?>
<?php // $Id: lock.php,v 1.1 2009/12/21 01:00:30 michaelpenne Exp $
/**
 * Page lock editing form
 *
 * @author Mark Nielsen
 * @version $Id: lock.php,v 1.1 2009/12/21 01:00:30 michaelpenne Exp $
 * @package format_page
 **/

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/course/format/page/plugin/lock.php');

class format_page_lock_form extends moodleform {

    function definition() {
        global $COURSE;

        $mform =& $this->_form;
        $locks = format_page_lock::get_locks();

        if (empty($this->_customdata)) {
            // Defaults
            $this->_customdata                = array();
            $this->_customdata['showprereqs'] = 1;
            $this->_customdata['visible']     = 1;
            $this->_customdata['locks']       = array();
        }

        $mform->addElement('hidden', 'id', $COURSE->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'lock');
        $mform->setType('action', PARAM_SAFEDIR);

        $mform->addElement('hidden', 'page', required_param('page', PARAM_INT));
        $mform->setType('page', PARAM_INT);

        $mform->addElement('header', 'general', get_string('general'));
        $mform->addElement('selectyesno', 'showprereqs', get_string('showprereqs', 'format_page'));
        $mform->setDefault('showprereqs', $this->_customdata['showprereqs']);
        $mform->setHelpButton('showprereqs', array('showprereqs', get_string('showprereqs', 'format_page'), 'format_page'));

        $mform->addElement('selectyesno', 'visible', get_string('visiblewhenlocked', 'format_page'));
        $mform->setDefault('visible', $this->_customdata['visible']);
        $mform->setHelpButton('visible', array('visible', get_string('visible', 'format_page'), 'format_page'));

        
        if (empty($this->_customdata['locks'])) {
            $mform->hardFreeze(array('visible', 'showprereqs'));
        }

        // Allow each lock to add editing form elements
        foreach ($locks as $lock) {
            $lock->edit_form($mform, $this->_customdata['locks']);
        }

        $mform->addElement('header', 'addlock', get_string('addlock', 'format_page'));

        // Allow each lock to include add lock form elements
        foreach ($locks as $lock) {
            $lock->add_form($mform, $this->_customdata['locks']);
        }

        $this->add_action_buttons();
    }
}
?>
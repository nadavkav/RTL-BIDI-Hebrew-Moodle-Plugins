<?php // $Id: edit_form.php,v 1.1 2009/12/21 01:01:25 michaelpenne Exp $
/**
 * Add link item form or
 * if a link object is passed then
 * print the edit form for that single
 * link
 *
 * @author Mark Nielsen
 * @version $Id: edit_form.php,v 1.1 2009/12/21 01:01:25 michaelpenne Exp $
 * @package pagemenu
 **/

require_once($CFG->libdir.'/formslib.php');

class mod_pagemenu_edit_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'a');
        $mform->setType('a', PARAM_INT);

        if ($this->_customdata !== NULL) {
            // Print edit form for a single link type
            $mform->addElement('hidden', 'linkid', $this->_customdata->link->id);
            $mform->setType('linkid', PARAM_INT);

            $mform->addElement('hidden', 'action', 'edit');
            $mform->setType('action', PARAM_ALPHA);

            $mform->addElement('header', $this->_customdata->type, $this->_customdata->get_name());

            $this->_customdata->edit_form_add($mform);

            $this->add_action_buttons();
        } else {

			$mform->addElement('static','','',get_string('addinfo', 'pagemenu') );

            // Print add form for all link types
            foreach (pagemenu_get_link_classes() as $link) {
                if ($link->is_enabled()) {
                    $mform->addElement('header', $link->type, '');  // No title
                    $link->edit_form_add($mform);
                }
            }

            $this->add_action_buttons(false, get_string('addlinks', 'pagemenu'));
        }
    }
}
?>
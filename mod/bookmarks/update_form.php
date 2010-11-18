<?php
require_once ($CFG->libdir.'/formslib.php');

class mod_bookmarks_update_form extends moodleform {

    function definition() {

		$mform    =& $this->_form;

		$mform->addElement('text', 'name', get_string('name', 'bookmarks'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

		$mform->addElement('text', 'description', get_string('description', 'bookmarks'), array('size'=>'64'));
        $mform->setType('description', PARAM_TEXT);

		$mform->addElement('text', 'url', get_string('url', 'bookmarks'), array('size'=>'64'));
        $mform->setType('url', PARAM_TEXT);
        $mform->addRule('url', null, 'required', null, 'client');
		$regex = '/^(http|https):\/\/([a-z0-9-]\.+)*/i';
		$mform->addRule('url',get_string('validurl','bookmarks'), 'regex', $regex, 'client');

		$mform->addElement('hidden', 'itemid', null, array('size'=>'64'));

		$mform->addElement('hidden', 'cmid', 'cmid', array('size'=>'64'));
        $mform->setType('cmid', PARAM_TEXT);

		$mform->addElement('text', 'tags', get_string('tags', 'bookmarks'), array('size'=>'64'));
        $mform->setType('tags', PARAM_TEXT);

		$this->add_action_buttons();
    }



}

?>
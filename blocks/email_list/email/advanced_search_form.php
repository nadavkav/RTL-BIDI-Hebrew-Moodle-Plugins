<?php
/**
 * Class for advanced search form
 *
 * @author Toni Mas
 * @version 1.0
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2008 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 * 		                   http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 **/
global $CFG;

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/blocks/email_list/email/lib.php');

class advanced_search_form extends moodleform {
	// Define the form
    function definition () {
        global $CFG, $USER, $COURSE;

        $mform =& $this->_form;

        /// Print the required moodle fields first
        $mform->addElement('header', 'matches', get_string('messagematches', 'block_email_list'));

        // And or Or.
        $radiobuttons = array();
        $radiobuttons[] = &MoodleQuickForm::createElement('radio', 'connector', get_string('matchanyquery', 'block_email_list'), get_string('matchanyquery', 'block_email_list'), 'OR');
        $radiobuttons[] = &MoodleQuickForm::createElement('radio', 'connector', get_string('matchaallquery', 'block_email_list'), get_string('matchallquery', 'block_email_list'), 'AND');
        $mform->addGroup($radiobuttons, 'messagematches', '', '  ', false);
        $mform->setDefault('connector', 'AND');


		// Words
		$mform->addElement('header', 'words', get_string('search', 'search'));

		$mform->addElement('text', 'to', get_string('to', 'block_email_list'), 'maxlength="254" size="60"');
		$mform->setType('to', PARAM_TEXT);

		$mform->addElement('text', 'from', get_string('from', 'block_email_list'), 'maxlength="254" size="60"');
		$mform->setType('from', PARAM_TEXT);

		$mform->addElement('text', 'subject', get_string('subject', 'block_email_list'), 'maxlength="254" size="60"');
		$mform->setType('subject', PARAM_TEXT);

		$mform->addElement('text', 'body', get_string('body', 'block_email_list'), 'maxlength="254" size="60"');
		$mform->setType('body', PARAM_TEXT);

		// Folders
		$mform->addElement('header', 'folder', get_string('messagefolders', 'block_email_list'));

        // Get my root folders
		$folders = email_get_root_folders($USER->id, false);

		if ( ! empty($folders) ) {

			$choose = array();

			// Get courses
			foreach ($folders as $folder) {
				$choose[] = &MoodleQuickForm::createElement('checkbox', $folder->id, $folder->name, $folder->name);

				// Now, get all subfolders it
				$subfolders = email_get_subfolders($folder->id);

				// If subfolders
				if ( $subfolders ) {
					foreach ( $subfolders as $subfolder ) {
						$choose[] = &MoodleQuickForm::createElement('checkbox', $subfolder->id, $subfolder->name, $subfolder->name);
					}
				}
			}

			$mform->addGroup($choose, 'folders', get_string('folders', 'block_email_list'), ' <br /> ');
			$mform->setDefault('folders['.$folders[0]->id.']', true);
			$mform->addRule('folders', get_string('nosearchfolders', 'block_email_list'), 'required', null, 'server');
		}

		$mform->addElement('hidden', 'courseid', $COURSE->id);

        // buttons
        $this->add_action_buttons(true, get_string('search', 'search'));
    }
}
?>

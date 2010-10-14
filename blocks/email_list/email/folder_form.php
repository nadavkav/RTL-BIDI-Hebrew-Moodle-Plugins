<?php

/**
 * Class of folder form.
 *
 * @author Toni Mas
 * @version 1.0
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2006 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 * 		                   http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 **/

global $CFG;

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/blocks/email_list/email/lib.php');

class folder_form extends moodleform {

    // Define the form
    function definition () {
        global $CFG, $USER;

        $mform =& $this->_form;

        // Get customdata
		$action          = $this->_customdata['action'];
		$courseid		 = $this->_customdata['course'];
		$folderid		 = $this->_customdata['id'];

        /// Print the required moodle fields first
        $mform->addElement('header', 'moodle', get_string('folder', 'block_email_list'));

        $mform->addElement('text', 'name', get_string('namenewfolder', 'block_email_list'));
		$mform->setDefault('name', '');
		$mform->addRule('name', get_string('nofolder', 'block_email_list'), 'required', null, 'client');


		// Get root folders
		$folders = email_get_my_folders($USER->id, $courseid, true, true);

		// Get inbox, there default option on menu
		$inbox = email_get_root_folder($USER->id, EMAIL_INBOX);

		$menu = array();

		// Insert into menu, only name folder
		foreach ($folders as $key => $foldername) {
			$menu[$key] = $foldername;
		}

		if ( $parent = email_get_parent_folder($folderid) ) {
			$parentid = $parent->id;
		} else {
			$parentid = 0;
		}

        // Select parent folder
        $mform->addElement('select', 'parentfolder', get_string('linkto', 'block_email_list'), $menu);
		$mform->setDefault('parentfolder', $parentid);

		$mform->addElement('hidden', 'gost');

		if ( $preference = get_record('email_preference', 'userid', $USER->id) ) {
			if ( $preference->marriedfolders2courses ) {
				// Get my courses
				$mycourses = get_my_courses($USER->id);

				$courses = array();
				// Prepare array
				foreach ( $mycourses as $mycourse ) {
					(strlen($mycourse->fullname) > 60) ? $course = substr($mycourse->fullname, 0, 60). ' ...' : $course = $mycourse->fullname;
					$courses[$mycourse->id] = $course;
				}
				$mform->addElement('select', 'foldercourse', get_string('course'), $courses);
				$mform->setDefault('foldercourse', $courseid);
			}
		}

        /// Add some extra hidden fields
        $mform->addElement('hidden', 'course', $courseid);
		$mform->addElement('hidden', 'oldname');
		$mform->addElement('hidden', 'id');
		$mform->addElement('hidden', 'action', $action);

        // buttons
        $this->add_action_buttons();
    }

    function definition_after_data() {

		global $USER;

    	// Drop actualfolder if it proceding...
    	$mform    =& $this->_form;


    	// Get parentfolder
    	$parentfolder =& $mform->getElementValue('parentfolder');

    	// Get (actual) folderid
    	$folderid =& $mform->getElementValue('id');

    	// Drop element.
    	$mform->removeElement('parentfolder');

    	// Get root folders
		$folders = email_get_my_folders($USER->id, $mform->getElementValue('course'), true, true);

		// Get inbox, there default option on menu
		$inbox = email_get_root_folder($USER->id, EMAIL_INBOX);

		$menu = array();

		// Insert into menu, only name folder
		foreach ($folders as $key => $foldername) {
			if ( $key != $folderid ) {
				$menu[$key] = $foldername;
			}
		}

        // Select parent folder
        $select = &MoodleQuickForm::createElement('select', 'parentfolder', get_string('linkto', 'block_email_list'), $menu);
        $mform->insertElementBefore($select, 'gost');
		$mform->setDefault('parentfolder', $parentfolder);

    }
}

?>
<?php
/**
 * Parent class for folders.
 *
 * @author Toni Mas
 * @version 1.0.1
 * @uses $CFG
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2008 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 * 		                   http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 **/

class folder {

    function folder() {
    }

    /**
	 * This functions created news folders
	 *
	 * @param object $folder Fields of new folder
	 * @param int $parentfolder Parent folder
	 * @return boolean Success/Fail
	 * @todo Finish documenting this function
	 **/
	function newfolder($folder, $parentfolder) {

		// Add actual time
		$folder->timecreated = time();

		// Make sure course field is not null			Thanks Ann.
		if ( ! isset( $folder->course) ) {
			$folder->course = 0;
		}

		// Insert record
		if (! $folder->id = insert_record('email_folder', $folder)) {
			return false;
		}

		// Prepare subfolder
		$subfolder = new stdClass();
		$subfolder->folderparentid = $parentfolder;
		$subfolder->folderchildid  = $folder->id;

		// Insert record reference
		if (! insert_record('email_subfolder', $subfolder)) {
			return false;
		}

		add_to_log($folder->userid, "email", "add subfolder", "$folder->name");

		return true;
	}

}
?>
<?php

/**
* Controller for admin settings
* students in their course
* @package block_file_manager
* @category block
* @author
* 
*/

if ($action == 'update'){
	$userchanges = NULL;
	$userchanges->usertype = $userinttype;
	if ($tab == 'files') {
		$userchanges->maxupload = $maxbytes;
		$userchanges->maxdir = $maxdir;
	} elseif ($tab == 'sharing') {
		$userchanges->sharetoany = $sharetoany;			
		$userchanges->allowsharing = $allowsharing;
	} elseif ($tab == 'security') {
		$userchanges->enable_fmanager = $enablefmanager;
	}
	fm_process_admin_settings($userchanges, $tab);

	echo "<center>";
	print_simple_box_start();
	echo get_string('msgrecordsupdated', 'block_file_manager');
	print_simple_box_end();
	echo "</center>";
} 

?>
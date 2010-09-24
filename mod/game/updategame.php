<?php

    require("../../config.php");
    require_once("lib.php");

    require_login();

	$update = optional_param('update', PARAM_INT); 
	
	$return = optional_param('return', PARAM_ALPHA); 
	$sesskey = required_param('sesskey', PARAM_ALPHANUM); 
	$action = optional_param('action', PARAM_ALPHA); 

	if( array_key_exists( 'gamekind', $_GET)){
		$gamekind = $_GET[ 'gamekind'];
	}else
		$gamekind = '';
	
	
	if( array_key_exists( 'sourcemodule', $_GET)){
		$sourcemodule = $_GET[ 'sourcemodule'];
	}else
		$sourcemodule = '';
	$section = optional_param('section', PARAM_INT); 
	
	if( array_key_exists( 'glossaryid', $_GET)){
		$glossaryid = $_GET[ 'glossaryid'];
	}else
		$glossaryid = 0;
	

	if (! $cm = get_record("course_modules", "id", $update)) {
		error("Course Module ID was incorrect id=$update");
	}	
	if (! $course = get_record("course", "id", $cm->course)) {
		error("Course is misconfigured id=$cm->course");
	}

	if( $action == 'delete'){
		
		$attemptid = required_param('attemptid', PARAM_INT); 
		
		ondeleteattempt($cm,  $return, $attemptid, $update, $sesskey);
	}else
	{
		onupdategame($cm, $gamekind, $sourcemodule, $glossaryid, $update, $sesskey);
	}
	
	function ondeleteattempt( $cm,   $return, $attemptid, $update, $sesskey)
	{
		global $CFG;
		
		$attempt = get_record_select( 'game_attempts', 'id='.$attemptid);
		$game = get_record_select( 'game', 'id='.$attempt->gameid);
				
		switch( $game->gamekind)
		{
		case 'bookquiz':
			delete_records( 'game_bookquiz_chapters', 'attemptid', $attemptid);
			break;
		}
		delete_records( 'game_queries', 'attemptid', $attemptid);
		delete_records( 'game_attempts', 'id', $attemptid);
		
		$url = $CFG->wwwroot."/course/mod.php?update=$update";
		$url .= "&return=true";
		$url .= "&sesskey=$sesskey";
		redirect( $url);
	}
	
	function onupdategame( $cm, $gamekind, $sourcemodule, $glossaryid, $update, $sesskey)
	{
		global $USER, $CFG;
		
		$updrec->id = $cm->instance;
		if( $gamekind != ''){
			$updrec->gamekind = $gamekind;
		}
		if( $sourcemodule != ''){
			$updrec->sourcemodule = $sourcemodule;
		}
		if( $glossaryid != 0){
			$updrec->glossaryid = $glossaryid;
		}
		
		if (!update_record("game", $updrec)){
			error("Update game: not updated");
		}

		$url = $CFG->wwwroot."/course/mod.php?update=$update";
		$url .= "&return=true";
		$url .= "&sesskey=$sesskey&reset=1";
		redirect( $url);
	}

    

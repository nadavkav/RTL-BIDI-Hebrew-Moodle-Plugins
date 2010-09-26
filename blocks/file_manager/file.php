<?php

    /**
     * file.php - Used to fetch a file from the File Manager's personal space directory
	 *			  Implements a few additional security features pertaining to a
	 *			  DMS and file sharing.
     *
     * This script file fetches files from the directory dataroot/file_manager/users/user->id
     * Syntax:   file.php?cid=$course->id&fileid=$fileid
     *
     * @uses $CFG
     * @uses FORMAT_HTML
     * @uses FORMAT_MOODLE
     * @author Martin Dougiamas
	 * @mod by: Michael Avelar
     * @version $Id: file.php,v 1.4 2009/03/29 23:21:41 danmarsden Exp $
     * @package moodlecore
     */
     
	global $CFG, $USER;
    require_once('../../config.php');
    require_once('../../lib/filelib.php');
	require_once('lib.php');
	
	$fileid = required_param('fileid', PARAM_INT);	//
	$groupid = optional_param('groupid', "0", PARAM_INT);
	$cid = required_param('cid', PARAM_INT);		//
		
	$lifetime = 0;		// Try to prevent image caching for IE
    
    // disable moodle specific debug messages
    disable_debugging();

    if (!$course = get_record("course", "id", $cid) ) {
        error("That's an invalid course id", "view.php?id=$cid");
    }
  // security: login to course if necessary
    if ($course->id != SITEID) {
        require_login($course->id);
    } else {
        require_login();
    }

	// Checks if user is owner of file, if not, checks if file is shared to them...if not...errors are displayed
	if (!fm_user_can_view_file($course->id, $fileid, $groupid)) {
		error(get_string("errnoviewfile",'block_file_manager'));
	}
	$filerec = get_record('fmanager_link', "id", $fileid);
	
	$filename = $filerec->link;
	if ($groupid == 0) {
		$pathinfo = fm_get_user_dir_space($filerec->owner);
	} else {
		$pathinfo = fm_get_group_dir_space($groupid);
	}
	if ($tmpfolder = fm_get_folder_path($filerec->folder, true, $groupid)) {
		$pathinfo = $pathinfo.$tmpfolder;
    }

    if (is_dir($pathinfo)) {
        if (file_exists($pathinfo.'/index.html')) {
            $pathinfo = rtrim($pathinfo, '/').'/index.html';
            $args[] = 'index.html';
        } else if (file_exists($pathinfo.'/index.htm')) {
            $pathinfo = rtrim($pathinfo, '/').'/index.htm';
            $args[] = 'index.htm';
        } else if (file_exists($pathinfo.'/Default.htm')) {
            $pathinfo = rtrim($pathinfo, '/').'/Default.htm';
            $args[] = 'Default.htm';
        } else {
            // security: do not return directory node!
            not_found($course->id);
        }
    }
	
    $pathname = $CFG->dataroot."/".$pathinfo."/".$filename;
    // check that file exists
    if (!file_exists($pathname)) {
        not_found($course->id);
    }

    // ========================================
    // finally send the file
    // ========================================
    session_write_close(); // unlock session during fileserving
    send_file($pathname, $filename, $lifetime, !empty($CFG->filteruploadedfiles));

    function not_found($courseid) {
        global $CFG;
        header('HTTP/1.0 404 not found');
        error(get_string('filenotfound', 'error'). " ($pathname)", $CFG->wwwroot.'/course/view.php?id='.$courseid); //this is not displayed on IIS??
    }
    exit;
?>
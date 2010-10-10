<?php 
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: uploadTasks.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/
    require_once("locallib.php");
    
    global $USER;
    $submissionsinstance->view_dates();

    $filecount = $submissionsinstance->count_user_files($USER->id);

    if ($submission = $submissionsinstance->get_submission()) {
        if ($submission->timemarked) {
            $submissionsinstance->view_feedback();
        }
        if ($filecount) {
            print_simple_box($submissionsinstance->print_user_files( $USER->id, true), 'center');
        } 
    }
    if (has_capability('mod/webquestscorm:preview', $submissionsinstance->context) && (!has_capability('mod/webquestscorm:manage',$submissionsinstance->context)) && $submissionsinstance->isopen() && (!$filecount || $submissionsinstance->wqresubmit || !$submission->timemarked)) {

				$submissionsinstance->view_upload_form();
    }   
?>

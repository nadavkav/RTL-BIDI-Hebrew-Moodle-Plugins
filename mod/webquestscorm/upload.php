<?php  
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: upload.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/

    require_once("../../config.php");
    require_once("locallib.php");
    global $CFG, $USER;

    require_once ("submissions.class.php"); 
    
    $cmid = required_param('cmid');  
    
    $submissionsinstance = new submissions($cmid);   

    require_login($submissionsinstance->course->id, false, $submissionsinstance->cm);    

    require_capability('mod/webquestscorm:submit', $submissionsinstance->context);
    webquestscorm_print_header($submissionsinstance->wqname, 'uploadTasks', $submissionsinstance->course,  $submissionsinstance->cm);

    $filecount = $submissionsinstance->count_user_files($USER->id);
    $submission = $submissionsinstance->get_submission($USER->id);
    if ($submissionsinstance->isopen() && (!$filecount || $submissionsinstance->wqresubmit || !$submission->timemarked)) {
        if ($submission = $submissionsinstance->get_submission($USER->id)) {
            //TODO: change later to ">= 0", to prevent resubmission when graded 0
            if (($submission->grade > 0) and !$submissionsinstance->wqresubmit) {
                notify(get_string('alreadygraded', 'webquestscorm'));
            }
        }

        $dir = $submissionsinstance->file_area_name($USER->id);

        require_once($CFG->dirroot.'/lib/uploadlib.php');
        $um = new upload_manager('newfile',true,false,$submissionsinstance->course,false,$submissionsinstance->wqmaxbytes);
        if ($um->process_file_uploads($dir)) {
            $newfile_name = $um->get_new_filename();
            if ($submission) {
                $submission->timemodified = time();
                $submission->numfiles     = 1;
                $submission->submissioncomment = addslashes($submission->submissioncomment);
                unset($submission->data1);  // Don't need to update this.
                unset($submission->data2);  // Don't need to update this.
                if (update_record("webquestscorm_submissions", $submission)) {
                    add_to_log($submissionsinstance->course->id, 'webquestscorm', 'upload', 'view.php?cmid='.$submissionsinstance->cm->id, $submissionsinstance->wqid, $submissionsinstance->cm->id);
                    $submissionsinstance->email_teachers($submission);
                    print_heading(get_string('uploadedfile'));
                } else {
                    notify(get_string("uploadfailnoupdate", "webquestscorm"));
                }
            } else {
                $newsubmission = $submissionsinstance->prepare_new_submission($USER->id);
                $newsubmission->timemodified = time();
                $newsubmission->numfiles = 1;
                if (insert_record('webquestscorm_submissions', $newsubmission)) {
                    add_to_log($submissionsinstance->course->id, 'webquestscorm', 'upload', 
                            'view.php?cmid='.$submissionsinstance->cm->id, $submissionsinstance->wqid, $submissionsinstance->cm->id);
                    $submissionsinstance->email_teachers($newsubmission);
                    print_heading(get_string('uploadedfile'));
                } else {
                    notify(get_string("uploadnotregistered", "webquestscorm", $newfile_name) );
                }
            }
        }
    } else { 
        notify(get_string("uploaderror", "webquestscorm")); //submitting not allowed!
    }

    print_continue('editsubmissions.php?cmid='.$submissionsinstance->cm->id."&element=uploadTasks");
    print_footer();

?>

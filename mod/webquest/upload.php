<?php  // $Id: upload.php,v 1.3 2007/09/09 09:00:20 stronk7 Exp $
//file taken from Workshop module and son lines changed
    require("../../config.php");
    require("lib.php");
    require("locallib.php");

    $id = required_param('id', PARAM_INT);          // CM ID


    if (! $cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }
    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }
    if (! $webquest= get_record("webquest", "id", $cm->instance)) {
        error("Course module is incorrect");
    }

    require_login($course->id, false, $cm);

    $strwebquests = get_string('modulenameplural', 'webquest');
    $strwebquest = get_string('modulename', 'webquest');
    $strsubmission = get_string('submission', 'webquest');

    print_header_simple(format_string($webquest->name)." : $strsubmission", "",
                 "<a href=\"index.php?id=$course->id\">$strwebquests</a> ->
                  <a href=\"view.php?a=$webquest->id\">".format_string($webquest->name,true)."</a> -> $strsubmission",
                  "", "", true);
    $timenow = time();
    $form = data_submitted("nomatch"); // POST may come from two forms
    // don't be picky about not having a title
    if (!$title = $form->title) {
        $title = get_string("untitled", "webquest");
    }

    if ($webquest->teamsmode){
        $userid = $USER->id;
        if ($team = get_record("webquest_team_members","webquestid",$webquest->id,"userid",$userid)){
            $submissionuserid = $team->teamid;
        }
    }else {
        $submissionuserid = $USER->id;
    }

    if (isstudent($course->id)) {
        if ($submissions = get_records("webquest_submissions", "webquestid",$webquest->id,"userid",$submissionuserid)) {
            // returns all submissions, newest on first
            foreach ($submissions as $submission) {
                if ($submission->timecreated > $timenow - $CFG->maxeditingtime) {
                    // ignore this new submission
                    redirect("view.php?id=$cm->id");
                    print_footer($course);
                    exit();
                }
            }
        }
    }

    // get the current set of submissions
    $submissions = get_records("webquest_submissions", "webquestid",$webquest->id,"userid",$submissionuserid);
    // add new submission record
    $newsubmission->webquestid  = $webquest->id;
    $newsubmission->userid      = $submissionuserid;
    $newsubmission->title       = clean_param($title, PARAM_CLEAN);
    $newsubmission->description = trim(clean_param($form->description, PARAM_CLEAN));
    $newsubmission->timecreated = $timenow;


    if (!$newsubmission->id = insert_record("webquest_submissions", $newsubmission)) {
        error("Failed to save submission");
    }

    // do something about the attachments, if there are any
    if ($webquest->nattachments) {
        require_once($CFG->dirroot.'/lib/uploadlib.php');
        $um = new upload_manager(null, false,false,$course,false,$webquest->maxbytes);
        if ($um->preprocess_files()) {
            $dir = webquest_file_area_name($webquest, $newsubmission);
            if ($um->save_files($dir)) {
                add_to_log($course->id, "webquest", "submit", "view.php?id=$cm->id", "$webquest->id", "$cm->id");
                redirect("view.php?id=$cm->id&amp;action=evaluation",get_string("uploadsuccess", "webquest"));
            }
        // um will take care of printing errors.
        }
    }else {
        add_to_log($course->id, "webquest", "submit", "view.php?id=$cm->id", "$webquest->id", "$cm->id");
        redirect("view.php?id=$cm->id&amp;action=evaluation",get_string("submitted", "webquest"));
    }

    print_footer($course);

?>


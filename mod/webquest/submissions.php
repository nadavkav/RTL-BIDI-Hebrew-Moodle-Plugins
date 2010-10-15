<?php // $Id: submissions.php,v 1.4 2007/09/09 09:00:20 stronk7 Exp $
    require("../../config.php");
    require("lib.php");
    require("locallib.php");



    $id          = required_param('id', PARAM_INT);    // Course Module ID
    $action      = optional_param('action', '', PARAM_ALPHA);
    $sid         = optional_param('sid', 0, PARAM_INT); //submission id
    //$order       = optional_param('order', 'name', PARAM_ALPHA);
    $title       = optional_param('title', '', PARAM_CLEAN);
    //$nentries    = optional_param('nentries', '', PARAM_ALPHANUM);
    $description = optional_param('description', '', PARAM_CLEAN);

    $timenow = time();

    // get some useful stuff...
    if (! $cm = get_coursemodule_from_id('webquest', $id)) {
        error("Course Module ID was incorrect");
    }
    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }
    if (! $webquest = get_record("webquest", "id", $cm->instance)) {
        error("Course module is incorrect");
    }
    require_login($course->id, false, $cm);

    $strwebquests = get_string("modulenameplural", "webquest");
    $strwebquest  = get_string("modulename", "webquest");
    $strsubmission = get_string("submission", "webquest");

    // ... print the header and...
    print_header_simple(format_string($webquest->name), "",
                 "<a href=\"index.php?id=$course->id\">$strwebquests</a> ->
                  <a href=\"view.php?id=$cm->id\">".format_string($webquest->name,true)."</a> -> $strsubmission",
                  "", "", true);

    if ($action == 'showsubmission' ) {

        if (empty($sid)) {
            error("submission id missing");
        }

        $submission = get_record("webquest_submissions", "id", $sid);
        $title = '"'.$submission->title.'" ';
        if (isteacher($course->id)) {
            if ($webquest->teamsmode == 0){
                $user = get_record("user","id",$submission->userid);
                $name = fullname($user, false);
                $by = "<a href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$webquest->course\">$name</a>";
            }else{
                $team = get_record("webquest_teams","id",$submission->userid);
                $name = $team->name;
                $by = get_string("team","webquest")." :<a href=\"teams.php?id=$cm->id&amp;teamid=$team->id&amp;action=members\">$name</a>";
            }
           $title .= get_string("by","webquest")." ".$by;
        }
        print_heading($title);
        echo '<center>'.get_string('submitted', 'webquest').': '.userdate($submission->timecreated).'</center><br />';
        webquest_print_submission($webquest,$submission);
        print_continue($_SERVER['HTTP_REFERER'].'#sid='.$submission->id);
    }

    elseif ($action == 'editsubmission' ) {
        if (empty($sid)) {
            error("Submission id missing");
        }
        $usehtmleditor = can_use_html_editor();

        $submission = get_record("webquest_submissions", "id", $sid);
        print_heading(get_string("editsubmission", "webquest"));
        if ($webquest->teamsmode){
            $userid = get_record("webquest_team_members","teamid",$submission->userid,"userid",$USER->id);
            if ($submission->userid <> $userid->teamid) {
                error("Wrong user id");
            }
        }else{
            if ($submission->userid <> $USER->id) {
                error("Wrong user id");
            }
        }
        if ($submission->timecreated < ($timenow - $CFG->maxeditingtime)) {
            error(get_string('notallowed', 'webquest'));
        }
        ?>
        <form name="editform" enctype="multipart/form-data" action="submissions.php" method="post">
        <input type="hidden" name="action" value="updatesubmission" />
        <input type="hidden" name="id" value="<?php echo $cm->id ?>" />
        <input type="hidden" name="sid" value="<?php echo $sid ?>" />
        <center>
        <table cellpadding="5" border="1">
        <?php
        echo "<tr valign=\"top\"><td><b>". get_string("title", "webquest").":</b>\n";
        echo "<input type=\"text\" name=\"title\" size=\"60\" maxlength=\"100\" value=\"$submission->title\" />\n";
        echo "</td></tr><tr><td><b>".get_string("submission", "webquest").":</b><br />\n";
        print_textarea($usehtmleditor, 25,70, 630, 400, "description", $submission->description);
        use_html_editor("description");
        echo "</td></tr>\n";
        if ($webquest->nattachments) {
            $filearea = webquest_file_area_name($webquest, $submission);
            if ($basedir = webquest_file_area($webquest, $submission)) {
                if ($files = get_directory_list($basedir)) {
                    echo "<tr><td><b>".get_string("attachments", "webquest").
                        "</b><div align=\"right\"><input type=\"button\" value=\"".get_string("removeallattachments",
                        "webquest")."\" onclick=\"document.editform.action.value='removeattachments';
                        document.editform.submit();\"/></div></td></tr>\n";
                    $n = 0;
                    foreach ($files as $file) {
                        $n++;
                        $icon = mimeinfo("icon", $file);
                        if ($CFG->slasharguments) {
                            $ffurl = "file.php/$filearea/$file";
                        } else {
                            $ffurl = "file.php?file=/$filearea/$file";
                        }
                        echo "<tr><td>".get_string("attachment", "webquest")." $n: <img src=\"$CFG->pixpath/f/$icon\"
                            height=\"16\" width=\"16\" border=\"0\" alt=\"File\" />".
                            "&nbsp;<a target=\"uploadedfile\" href=\"$CFG->wwwroot/$ffurl\">$file</a></td></tr>\n";
                    }
                    unset($n);
                } else {
                    echo "<tr><td><b>".get_string("noattachments", "webquest")."</b></td></tr>\n";
                }
            }
            echo "<tr><td>\n";
            require_once($CFG->dirroot.'/lib/uploadlib.php');
            for ($i=0; $i < $webquest->nattachments; $i++) {
                $iplus1 = $i + 1;
                $tag[$i] = get_string("newattachment", "webquest")." $iplus1:";
            }
            upload_print_form_fragment($webquest->nattachments,null,$tag,false,null,$course->maxbytes,
                $webquest->maxbytes,false);
            echo "</td></tr>\n";
        }

        echo "</table>\n";
        echo "<input type=\"submit\" value=\"".get_string("submitassignment", "webquest")."\" />\n";
        echo "</center></form>\n";
    }


    elseif ($action == 'updatesubmission') {

        if (empty($sid)) {
            error("Update submission: submission id missing");
        }
        $submission = get_record("webquest_submissions", "id", $sid);
        if ($webquest->teamsmode){
            $userid = get_record("webquest_team_members","teamid",$submission->userid,"userid",$USER->id);
            $userid = $userid->teamid;
        }else{
            $userid = $USER->id;
        }

        // students are only allowed to update their own submission and only up to the deadline
        if (!((isteacher($course->id))or
               (($userid == $submission->userid) and ($timenow < $webquest->submissionend)
                   and ($timenow < ($submission->timecreated + $CFG->maxeditingtime))))) {
            error("You are not authorized to update your submission");
        }
        // check existence of title
        if (empty($title)) {
            $title = get_string("notitle", "webquest");
        }
        set_field("webquest_submissions", "title", $title, "id", $submission->id);
        set_field("webquest_submissions", "description", trim($description), "id", $submission->id);
        set_field("webquest_submissions", "timecreated", $timenow, "id", $submission->id);
        if ($webquest->nattachments) {
            require_once($CFG->dirroot.'/lib/uploadlib.php');
            $um = new upload_manager(null,false,false,$course,false,$webquest->maxbytes);
            if ($um->preprocess_files()) {
                $dir = webquest_file_area_name($webquest, $submission);
                if ($um->save_files($dir)) {
                    add_to_log($course->id, "webquest", "newattachment", "view.php?id=$cm->id", "$webquest->id");
                    print_string("uploadsuccess", "webquest");
                }
                // upload manager will print errors.
            }
        }
        redirect("view.php?id=$cm->id&amp;action=evaluation", get_string("wellsaved","webquest"));
    }

    elseif ($action == 'removeattachments' ) {

        $form = data_submitted();

        if (empty($form->sid)) {
            error("Update submission: submission id missing");
        }
        $submission = get_record("webquest_submissions", "id", $form->sid);
        if ($webquest->teamsmode){
            $userid = get_record("webquest_team_members","teamid",$submission->userid,"userid",$USER->id);
            $userid = $userid->teamid;
        }else{
            $userid = $USER->id;
        }
        // students are only allowed to remove their own attachments and only up to the deadline
        if (!((isteacher($course->id))or
               (($userid == $submission->userid) and ($timenow < $webquest->submissionend)
                   and ($timenow < ($submission->timecreated + $CFG->maxeditingtime))))) {
            error("You are not authorized to delete these attachments");
        }
        // amend title... just in case they were modified
        // check existence of title
        if (empty($form->title)) {
            notify(get_string("notitle", "webquest"));
        } else {
            set_field("webquest_submissions", "title", $form->title, "id", $submission->id);
            set_field("webquest_submissions", "description", trim($form->description), "id", $submission->id);
        }
        print_string("removeallattachments", "webquest");
        webquest_delete_submitted_files($webquest, $submission);
        add_to_log($course->id, "webquest", "removeattachments", "view.php?id=$cm->id", "submission $submission->id");
        redirect("submissions.php?id=$cm->id&amp;action=editsubmission&amp;sid=$sid");
    }

    elseif ($action == 'confirmdelete' ) {
        notice_yesno(get_string("confirmsubmissiondelete","webquest", get_string("submission", "webquest")),
             "submissions.php?action=delete&amp;id=$cm->id&amp;sid=$sid", "view.php?id=$cm->id&amp;action=evaluation");
    }

    elseif ($action == 'assess') {

        $submission = get_record("webquest_submissions", "id", $sid);
        // there can be an assessment record (for teacher submissions), if there isn't...
        if (!$assessments = get_records("webquest_grades", "sid", $submission->id)) {
                $graded = false;
                $submission->grade = -1; // set impossible grade
                $submission->timegraded = 0;
                if (!update_record("webquest_submissions", $submission)) {
                    error("Could not insert webquest assessment!");
                }
                // if it's the teacher and the webquest is error banded set all the elements to Yes
                if (isteacher($course->id) and ($webquest->gradingstrategy == 2)) {
                    $graded = true;
                    for ($i =0; $i < $webquest->ntasks; $i++) {
                        unset($task);
                        $task->webquestid = $webquest->id;
                        $task->sid = $submission->id;
                        $task->taskno = $i;
                        $task->feedback = '';
                        $task->grade = 1;
                        if (!$task->id = insert_record("webquest_grades", $task)) {
                            error("Could not insert Webquest grade!");
                        }
                    }
                    // now set the adjustment
                    unset($task);
                    $i = $webquest->ntasks;
                    $task->webquestid = $webquest->id;
                    $task->sid = $submission->id;
                    $task->taskno = $i;
                    $task->grade = 0;
                    if (!$task->id = insert_record("webquest_grades", $task)) {
                        error("Could not insert Webquest grade!");
                    }
                }
        }else {
            $graded = true;
        }
        print_heading_with_help(get_string("assessthissubmission", "webquest"), "grading", "webquest");
        $redirect = "view.php?id=$cm->id&amp;action=evaluation";
        // show assessment and allow changes
        webquest_print_assessment($webquest, $graded, true, true, $redirect,$sid);
    }

    elseif($action == 'delete'){
        $submission = get_record("webquest_submissions", "id", $sid);
        if ($webquest->teamsmode){
            $userid = get_record("webquest_team_members","teamid",$submission->userid,"userid",$USER->id);
            $userid = $userid->teamid;
        }else{
            $userid = $USER->id;
        }
        if (!((isteacher($course->id))or
               (($userid = $submission->userid) and ($timenow < $webquest->submissionend)
                   and ($timenow < ($submission->timecreated + $CFG->maxeditingtime))))) {
            error("You are not authorized to delete submission");
        }
        if (count_records("webquest_grades","sid",$submission->id)){
            if(!delete_records("webquest_grades","sid",$submission->id)){
                error("Could not delete grades for this submission");
            }
        }
        webquest_delete_submitted_files($webquest, $submission);
        if (!delete_records("webquest_submissions", "id", $sid)){
            error("Could not delete submission");
        }
        redirect("view.php?id=$cm->id&amp;action=evaluation");
    }

 print_footer($course);
?>
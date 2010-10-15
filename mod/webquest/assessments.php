<?php  // $Id: assessments.php,v 1.3 2007/09/09 09:00:16 stronk7 Exp $
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");

    $action         = required_param('action', PARAM_ALPHA);
    $id             = optional_param('id', 0, PARAM_INT);    // Course Module ID
    $a      = optional_param('a', '', PARAM_ALPHA);
    $userid         = optional_param('userid', 0, PARAM_INT);
    $sid            = optional_param('sid', 0, PARAM_INT); // submission id
    $taskno      = optional_param('taskno', -1, PARAM_INT);


    $timenow = time();
    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $webquest = get_record("webquest", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $webquest = get_record("webquest", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $webquest->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("webquest", $webquest->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id, false, $cm);
    add_to_log($course->id, "webquest", "assessments ".$action, "view.php?id=$cm->id", "$webquest->id");

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    $strwebquests = get_string("modulenameplural", "webquest");
    $strwebquest  = get_string("modulename", "webquest");
    $strassessments = get_string("assessments", "webquest");

    // ... print the header and...
    print_header_simple(format_string($webquest->name), "",
                 "<a href=\"index.php?id=$course->id\">$strwebquests</a> ->
                  <a href=\"view.php?id=$cm->id\">".format_string($webquest->name,true)."</a> -> $strassessments",
                "", "", true);
    if ($action == 'updateassessment') {
        if (!isteacher($course->id)){
            error("Only teachers can look at this page");
        }
        if (empty($sid)) {
            error("Webquest Submission id missing");
        }else {
            if (!$submission = get_record("webquest_submissions", "id", $sid)) {
                error ("Submission record not found");
            }
        }


        $tasksraw = get_records("webquest_tasks", "webquestid", $webquest->id, "taskno ASC");
        if (count($tasksraw) < $webquest->ntasks) {
            print_string("noteonassignmenttasks", "webquest");
        }
        if ($tasksraw) {
            foreach ($tasksraw as $task) {
                $tasks[] = $task;
            }
        } else {
            $tasks = null;
        }
        delete_records("webquest_grades", "sid",  $submission->id);

        $form = data_submitted('nomatch');

        switch ($webquest->gradingstrategy) {
            case 0:
                for ($i = 0; $i < $webquest->ntasks; $i++) {
                    unset($task);
                    $task->webquestid = $webquest->id;
                    $task->sid = $submission->id;
                    $task->taskno = $i;
                    $task->feedback = clean_param($form->{"feedback_$i"}, PARAM_CLEAN);
                    if (!$task->id = insert_record("webquest_grades", $task)) {
                        error("Could not insert webquest grade!");
                    }
                }
                $grade = 0;
                break;

            case 1:
                foreach ($form->grade as $key => $thegrade) {
                    unset($task);
                    $task->webquestid = $webquest->id;
                    $task->sid = $submission->id;
                    $task->taskno = $key;
                    $task->feedback   = clean_param($form->{"feedback_$key"}, PARAM_CLEAN);
                    $task->grade = $thegrade;
                    if (!$task->id = insert_record("webquest_grades", $task)) {
                        error("Could not insert webquest grade!");
                        }
                    }
                $rawgrade=0;
                $totalweight=0;
                foreach ($form->grade as $key => $grade) {
                    $maxscore = $tasks[$key]->maxscore;
                    $weight = $WEBQUEST_EWEIGHTS[$tasks[$key]->weight];
                    if ($weight > 0) {
                        $totalweight += $weight;
                    }
                    $rawgrade += ($grade / $maxscore) * $weight;
                }
                $grade = 100.0 * ($rawgrade / $totalweight);
                break;

            case 2:
                $error = 0.0;
                for ($i =0; $i < $webquest->ntasks; $i++) {
                    unset($task);
                    $task->webquestid = $webquest->id;
                    $task->sid = $submission->id;
                    $task->taskno = $i;
                    $task->feedback   = $form->{"feedback_$i"};
                    $task->grade = clean_param($form->grade[$i], PARAM_CLEAN);
                    if (!$task->id = insert_record("webquest_grades", $task)) {
                        error("Could not insert webquest grade!");
                    }
                    if (empty($form->grade[$i])){
                        $error += $WEBQUEST_EWEIGHTS[$tasks[$i]->weight];
                    }
                }
                unset($task);
                $i = $webquest->ntasks;
                $task->webquestid = $webquest->id;
                $task->sid = $submission->id;
                $task->taskno = $i;
                $task->grade = $form->grade[$i];
                if (!$task->id = insert_record("webquest_grades", $task)) {
                    error("Could not insert webquest grade!");
                }
                $grade = ($tasks[intval($error + 0.5)]->maxscore + $form->grade[$i]) * 100 / $webquest->grade;
                if ($grade < 0) {
                    $grade = 0;
                } elseif ($grade > 100) {
                    $grade = 100;
                }
                echo "<b>".get_string("weightederrorcount", "webquest", intval($error + 0.5))."</b>\n";
                break;

            case 3:
                unset($task);
                $task->webquestid = $webquest->id;
                $task->sid = $submission->id;
                $task->taskno = 0;
                $task->grade = $form->grade[0];
                if (!$task->id = insert_record("webquest_grades", $task)) {
                    error("Could not insert webquest grade!");
                }
                unset($task);
                $task->webquestid = $webquest->id;
                $task->sid = $submission->id;
                $task->taskno = 1;
                $task->grade = $form->grade[1];
                if (!$task->id = insert_record("webquest_grades", $task)) {
                    error("Could not insert webquest grade!");
                }
                $grade = ($tasks[$form->grade[0]]->maxscore + $form->grade[1]);
                break;

            case 4:
                foreach ($form->grade as $key => $thegrade) {
                    unset($task);
                    $task->webquestid = $webquest->id;
                    $task->sid = $submission->id;
                    $task->taskno = clean_param($key, PARAM_INT);
                    $task->feedback = clean_param($form->{"feedback_$key"}, PARAM_CLEAN);
                    $task->grade = $thegrade;
                    if (!$task->id = insert_record("webquest_grades", $task)) {
                        error("Could not insert webquest grade!");
                    }
                }
                $rawgrade=0;
                $totalweight=0;
                foreach ($form->grade as $key => $grade) {
                    $maxscore = $tasks[$key]->maxscore;
                    $weight = $WEBQUEST_EWEIGHTS[$tasks[$key]->weight];
                    if ($weight > 0) {
                        $totalweight += $weight;
                    }
                    $rawgrade += ($grade / $maxscore) * $weight;
                }
                $grade = 100.0 * ($rawgrade / $totalweight);
                break;

        }

        set_field("webquest_submissions", "timegraded", $timenow, "id", $submission->id);


        set_field("webquest_submissions", "grade", $grade, "id", $submission->id);

        if (!empty($form->generalcomment)) {
            set_field("webquest_submissions", "gradecomment", clean_param($form->generalcomment, PARAM_CLEAN), "id", $submission->id);
        }

        add_to_log($course->id, "webquest", "assess",
                "assessments.php?id=3&sid=$submission->id&amp,action=viewassesment", "$submission->id", "$cm->id");

        if (!$returnto = $form->returnto) {
            $returnto = "view.php?id=$cm->id";
        }

        if ($webquest->gradingstrategy) {
            redirect($returnto, get_string("thegradeis", "webquest").": ".
                    number_format($grade * $webquest->grade / 100, 2).
                    " (".get_string("maximumgrade")." ".number_format($webquest->grade).")");
        }
        else {
            redirect($returnto);
        }
    }

    else if($action == 'deletegrade'){
        if (!isteacher($course->id)){
            error("Only teachers can look at this page");
        }
        notice_yesno(get_string("suretodelgrade","webquest"),
             "assessments.php?action=confirmdelgrade&amp;id=$id&amp;sid=$sid", "view.php?id=$id&amp;action=evaluation");
    }

    else if($action == 'confirmdelgrade'){
        if (empty($sid)) {
            error("Webquest Submission id missing");
        }else {
            if (!$submission = get_record("webquest_submissions", "id", $sid)) {
                error ("Submission record not found");
            }
        }
        if(!delete_records("webquest_grades","sid",$submission->id)){
            error("could not delete assessment");
        }else {
            $submission->gradecomment = '';
            $submission->timegraded = 0;
            $submission->grade = 0 ;
            if (!update_record("webquest_submissions",$submission)){
                error("Could not delete assessment");
            }
        }
        unset($submission);
        redirect("view.php?id=$cm->id&amp;action=evaluation");
    }

    elseif ($action == 'viewassesment'){
        $redirect = "view.php?id=$cm->id&amp;action=evaluation";
        webquest_print_assessment($webquest, true, false, false, $redirect,$sid);
        print_continue($redirect);
    }

    print_footer($course);

?>
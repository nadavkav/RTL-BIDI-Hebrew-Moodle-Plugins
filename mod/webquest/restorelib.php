<?php

    function webquest_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;


        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            $info = $data->info;

            $webquest->course = $restore->course_id;
            $webquest->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $webquest->description = backup_todb($info['MOD']['#']['DESCRIPTION']['0']['#']);
            $webquest->process = backup_todb($info['MOD']['#']['PROCESS']['0']['#']);
            $webquest->conclussion = backup_todb($info['MOD']['#']['CONCLUSSION']['0']['#']);
            $webquest->taskdescription = backup_todb($info['MOD']['#']['TASKDESCRIPTION']['0']['#']);
            $webquest->nattachments = backup_todb($info['MOD']['#']['NATTACHMENTS']['0']['#']);
            //$webquest->format = backup_todb($info['MOD']['#']['FORMAT']['0']['#']);
            $webquest->gradingstrategy = backup_todb($info['MOD']['#']['GRADINGSTRATEGY']['0']['#']);
            $webquest->maxbytes = backup_todb($info['MOD']['#']['MAXBYTES']['0']['#']);
            $webquest->submissionstart = backup_todb($info['MOD']['#']['SUBMISSIONSTART']['0']['#']);
            $webquest->submissionend = backup_todb($info['MOD']['#']['SUBMISSIONEND']['0']['#']);
            $webquest->grade = backup_todb($info['MOD']['#']['GRADE']['0']['#']);
            $webquest->teamsmode = backup_todb($info['MOD']['#']['TEAMSMODE']['0']['#']);
            $webquest->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);
            $webquest->ntasks = backup_todb($info['MOD']['#']['NTASKS']['0']['#']);
            $webquest->usepassword = backup_todb($info['MOD']['#']['USEPASSWORD']['0']['#']);
            $webquest->password = backup_todb($info['MOD']['#']['PASSWORD']['0']['#']);

            $newid = insert_record ("webquest",$webquest);

            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","webquest")." \"".format_string(stripslashes($webquest->name),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                backup_putid($restore->backup_unique_code,$mod->modtype,$mod->id, $newid);
                $status = webquest_tasks_restore_mods($newid,$info,$restore);
                $status = webquest_resources_restore_mods($newid,$info,$restore);
                if ($webquest->teamsmode){
                    $status = webquest_teams_restore_mods($mod->id,$newid,$info,$restore);
                }
                if (restore_userdata_selected($restore,'webquest',$mod->id)) {
                    $status = webquest_submissions_restore_mods ($mod->id, $newid,$info,$restore);
                }
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

function webquest_resources_restore_mods($webquest_id,$info,$restore) {

        global $CFG;

        $status = true;
    if(isset($info['MOD']['#']['WQRESOURCES']['0']['#']['RES'])){
        $resources = $info['MOD']['#']['WQRESOURCES']['0']['#']['RES'];

        for($i = 0; $i < sizeof($resources); $i++) {
            $res_info = $resources[$i];
            $res->webquestid = $webquest_id;
            $res->name = backup_todb($res_info['#']['NAME']['0']['#']);
            $res->description = backup_todb($res_info['#']['DESCRIPTION']['0']['#']);
            $res->path = backup_todb($res_info['#']['PATH']['0']['#']);
            $res->resno = backup_todb($res_info['#']['RESNO']['0']['#']);

            $status = insert_record ("webquest_resources",$res);

            if (($i+1) % 10 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 200 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }
    }
    return $status;
}





function webquest_tasks_restore_mods($webquest_id,$info,$restore) {

    global $CFG;

    $status = true;
    if(isset($info['MOD']['#']['TASKS']['0']['#']['TASK'])){
        $tasks = $info['MOD']['#']['TASKS']['0']['#']['TASK'];

        for($i = 0; $i < sizeof($tasks); $i++) {
            $task_info = $tasks[$i];

            $task->webquestid = $webquest_id;
            $task->taskno = backup_todb($task_info['#']['TASKNO']['0']['#']);
            $task->description = backup_todb($task_info['#']['DESCRIPTION']['0']['#']);
            $task->scale = backup_todb($task_info['#']['SCALE']['0']['#']);
            $task->maxscore = backup_todb($task_info['#']['MAXSCORE']['0']['#']);
            $task->weight = backup_todb($task_info['#']['WEIGHT']['0']['#']);
            $task->stddev = backup_todb($task_info['#']['STDDEV']['0']['#']);
            $task->totalassessments = backup_todb($task_info['#']['TOTALASSESSMENTS']['0']['#']);

            $newid = insert_record ("webquest_tasks",$task);

            //Do some output
            if (($i+1) % 10 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 200 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                $status = webquest_rubrics_restore_mods($webquest_id,$task->taskno,$task_info,$restore);
            } else {
                $status = false;
            }
        }
    }
    return $status;
}



function webquest_rubrics_restore_mods($webquest_id,$taskno,$info,$restore) {

    global $CFG;

    $status = true;


    if (isset($info['#']['RUBRICS']['0']['#']['RUBRIC'])) {
        $rubrics = $info['#']['RUBRICS']['0']['#']['RUBRIC'];

        for($i = 0; $i < sizeof($rubrics); $i++) {
            $rub_info = $rubrics[$i];

            $rubric->webquestid = $webquest_id;
            $rubric->taskno = $taskno;
            $rubric->rubricno = backup_todb($rub_info['#']['RUBRICNO']['0']['#']);
            $rubric->description = backup_todb($rub_info['#']['DESCRIPTION']['0']['#']);

            $newid = insert_record ("webquest_rubrics",$rubric);

            //Do some output
            if (($i+1) % 10 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 200 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if (!$newid) {
                $status = false;
            }
        }
    }
    return $status;
}



function webquest_teams_restore_mods($old_webquest_id, $new_webquest_id,$info,$restore) {

    global $CFG;

    $status = true;

    $teams = $info['MOD']['#']['TEAMS']['0']['#']['TEAM'];

    for($i = 0; $i < sizeof($teams); $i++) {
        $team_info = $teams[$i];

        $oldid = backup_todb($team_info['#']['ID']['0']['#']);

        $team->webquestid = $new_webquest_id;
        $team->name = backup_todb($team_info['#']['NAME']['0']['#']);
        $team->description = backup_todb($team_info['#']['DESCRIPTION']['0']['#']);

        $newid = insert_record ("webquest_teams",$team);

        //Do some output
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
            backup_flush(300);
        }

        if ($newid) {
            backup_putid($restore->backup_unique_code,"webquest_teams",$oldid,$newid);
            if (restore_userdata_selected($restore,'webquest',$old_webquest_id)) {
                $status = webquest_team_members_restore_mods ($new_webquest_id, $newid,$team_info,$restore);
            }
        }else{
            $status = false;
        }
    }
    return $status;
}



function webquest_team_members_restore_mods ($new_webquest_id, $new_team_id,$info,$restore){
    global $CFG;

    $status = true;

    if(isset($info['#']['MEMBERS']['0']['#']['MEMBER'])){

        $members = $info['#']['MEMBERS']['0']['#']['MEMBER'];
        for($i = 0; $i < sizeof($members); $i++) {
            $men_info = $members[$i];
            $olduserid = backup_todb($men_info['#']['USERID']['0']['#']);

            $member->webquestid = $new_webquest_id;
            $member->teamid = $new_team_id;
            $member->userid = backup_todb($men_info['#']['USERID']['0']['#']);

            $user = backup_getid($restore->backup_unique_code,"user",$olduserid);
            if ($user) {
                $member->userid = $user->new_id;
            }
            $newid = insert_record ("webquest_team_members",$member);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if (!$newid) {
                $status = false;
            }
        }
    }
    return $status;
}



function webquest_submissions_restore_mods($old_webquest_id, $new_webquest_id,$info,$restore) {

    global $CFG;

    $status = true;


    if (isset($info['MOD']['#']['SUBMISSIONS']['0']['#']['SUBMISSION'])){
        $submissions = $info['MOD']['#']['SUBMISSIONS']['0']['#']['SUBMISSION'];

        for($i = 0; $i < sizeof($submissions); $i++) {
            $sub_info = $submissions[$i];

            $oldid = backup_todb($sub_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($sub_info['#']['USERID']['0']['#']);

            $submission->webquestid = $new_webquest_id;
            $submission->userid = backup_todb($sub_info['#']['USERID']['0']['#']);
            $submission->title = backup_todb($sub_info['#']['TITLE']['0']['#']);
            $submission->timecreated = backup_todb($sub_info['#']['TIMECREATED']['0']['#']);
            $submission->mailed = backup_todb($sub_info['#']['MAILED']['0']['#']);
            $submission->description = backup_todb($sub_info['#']['DESCRIPTION']['0']['#']);
            $submission->grade = backup_todb($sub_info['#']['GRADE']['0']['#']);
            $submission->timegraded = backup_todb($sub_info['#']['TIMEGRADED']['0']['#']);
            $submission->gradecomment = backup_todb($sub_info['#']['GRADECOMMENT']['0']['#']);

            $webquest = get_record("webquest","id",$new_webquest_id);
            if (!$webquest->teamsmode){
                $user = backup_getid($restore->backup_unique_code,"user",$olduserid);
                if ($user) {
                    $submission->userid = $user->new_id;
                }
            }else{
                $team = backup_getid($restore->backup_unique_code,"webquest_teams",$olduserid);
                if ($team){
                    $submission->userid = $team->new_id;
                }
            }

            $newid = insert_record ("webquest_submissions",$submission);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                backup_putid($restore->backup_unique_code,"webquest_submissions",$oldid,
                             $newid);

                $status = webquest_restore_files ($oldid, $newid,$restore);
                if ($status) {
                    $status = webquest_grades_restore_mods ($new_webquest_id, $newid,$sub_info,$restore);
                }
            } else {
                $status = false;
            }
        }
    }
    return $status;
}


function webquest_grades_restore_mods($new_webquest_id, $new_sub_id,$info,$restore) {

    global $CFG;

    $status = true;

    if (isset($info['#']['GRADES']['0']['#']['GRADE'])) {
        $grades = $info['#']['GRADES']['0']['#']['GRADE'];

        for($i = 0; $i < sizeof($grades); $i++) {
            $gra_info = $grades[$i];

            $grade->webquestid = $new_webquest_id;
            $grade->sid = $new_sub_id;
            $grade->taskno = backup_todb($gra_info['#']['TASKNO']['0']['#']);
            $grade->feedback = backup_todb($gra_info['#']['FEEDBACK']['0']['#']);
            $grade->grade = backup_todb($gra_info['#']['GRADE_VALUE']['0']['#']);

            $newid = insert_record ("webquest_grades",$grade);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if (!$newid) {
                $status = false;
            }
        }
    }
    return $status;
}

    //This function copies the webquest related info from backup temp dir to course moddata folder,
    //creating it if needed and recoding everything (submission_id)
    function webquest_restore_files ($oldsubmiss, $newsubmiss, $restore) {

        global $CFG;

        $status = true;
        $todo = false;
        $moddata_path = "";
        $webquest_path = "";
        $temp_path = "";

        //First, we check to "course_id" exists and create is as necessary
        //in CFG->dataroot
        $dest_dir = $CFG->dataroot."/".$restore->course_id;
        $status = check_dir_exists($dest_dir,true);

        //Now, locate course's moddata directory
        $moddata_path = $CFG->dataroot."/".$restore->course_id."/".$CFG->moddata;

        //Check it exists and create it
        $status = check_dir_exists($moddata_path,true);

        //Now, locate webquest directory
        if ($status) {
            $webquest_path = $moddata_path."/webquest";
            //Check it exists and create it
            $status = check_dir_exists($webquest_path,true);
        }

        //Now locate the temp dir we are gong to restore
        if ($status) {
            $temp_path = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code.
                         "/moddata/webquest/".$oldsubmiss;
            //Check it exists
            if (is_dir($temp_path)) {
                $todo = true;
            }
        }

        //If todo, we create the neccesary dirs in course moddata/webquest
        if ($status and $todo) {
            //First this webquest id
            $this_webquest_path = $webquest_path."/".$newsubmiss;
            $status = check_dir_exists($this_webquest_path,true);
            //And now, copy temp_path to this_webquest_path
            $status = backup_copy_file($temp_path, $this_webquest_path);
        }
        return $status;
    }

    //Return a content decoded to support interactivities linking. Every module
    //should have its own. They are called automatically from
    //webquest_decode_content_links_caller() function in each module
    //in the restore process
    function webquest_decode_content_links ($content,$restore) {

        global $CFG;

        $result = $content;

        //Link to the list of webquests

        $searchstring='/\$@(WEBQUESTINDEX)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$content,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {

            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course id)
                $rec = backup_getid($restore->backup_unique_code,"course",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(WEBQUESTINDEX)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/webquest/index.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/webquest/index.php?id='.$old_id,$result);
                }
            }
        }

        //Link to webquest view by moduleid

        $searchstring='/\$@(WEBQUESTPVIEWBYID)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$result,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {

            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course_modules id)
                $rec = backup_getid($restore->backup_unique_code,"course_modules",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(WEBQUESTVIEWBYID)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/webquest/view.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/webquest/view.php?id='.$old_id,$result);
                }
            }
        }

        return $result;
    }

    //This function makes all the necessary calls to xxxx_decode_content_links()
    //function in each module, passing them the desired contents to be decoded
    //from backup format to destination site/course in order to mantain inter-activities
    //working in the backup/restore process. It's called from restore_decode_content_links()
    //function in restore process
    function webquest_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

        //Process every description,process,taskdescription,conclussion from WEBQUEST
        if ($webquests = get_records_sql ("SELECT *
                                           FROM {$CFG->prefix}webquest w
                                           WHERE w.course = $restore->course_id")) {

            $i = 0;
            foreach ($webquests as $webquest) {
                $i++;$todo = false;
                $content = $webquest->description;
                $result = restore_decode_content_links_worker($content,$restore);
                if ($result != $content) {
                    $webquest->description = addslashes($result);
                    if ($CFG->debug>7) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                    $todo = true;
                }
                $content = $webquest->process;
                $result = restore_decode_content_links_worker($content,$restore);
                if ($result != $content) {
                    $webquest->process = addslashes($result);
                    if ($CFG->debug>7) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                    $todo = true;
                }
                $content = $webquest->conclussion;
                $result = restore_decode_content_links_worker($content,$restore);
                if ($result != $content) {
                    $webquest->process = addslashes($result);
                    if ($CFG->debug>7) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                    $todo = true;
                }
                $content = $webquest->taskdescription;
                $result = restore_decode_content_links_worker($content,$restore);
                if ($result != $content) {
                    $webquest->process = addslashes($result);
                    if ($CFG->debug>7) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                    $todo = true;
                }
                if($todo){
                    $status = update_record("webquest",$webquest);
                }
                //Do some output
                if (($i+1) % 5 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 100 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }
        }

        return $status;
    }

?>

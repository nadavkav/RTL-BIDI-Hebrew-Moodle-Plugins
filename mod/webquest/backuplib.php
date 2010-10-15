<?php

function webquest_backup_mods($bf,$preferences) {

    global $CFG;

    $status = true;

    $webquests = get_records ("webquest","course",$preferences->backup_course,"id");
    if ($webquests) {
        foreach ($webquests as $webquest) {
            if (backup_mod_selected($preferences,'webquest',$webquest->id)) {
                $status = webquest_backup_one_mod($bf,$preferences,$webquest);
            }
        }
    }

    return $status;
}

function webquest_backup_one_mod($bf,$preferences,$webquest) {

    $status = true;

    if (is_numeric($webquest)) {
        $webquest = get_record('webquest','id',$webquest);
    }
    $instanceid = $webquest->id;


    fwrite ($bf,start_tag("MOD",3,true));
    fwrite ($bf,full_tag("ID",4,false,$webquest->id));
    fwrite ($bf,full_tag("MODTYPE",4,false,"webquest"));
    fwrite ($bf,full_tag("NAME",4,false,$webquest->name));
    fwrite ($bf,full_tag("DESCRIPTION",4,false,$webquest->description));
    fwrite ($bf,full_tag("PROCESS",4,false,$webquest->process));
    fwrite ($bf,full_tag("CONCLUSSION",4,false,$webquest->conclussion));
    fwrite ($bf,full_tag("TASKDESCRIPTION",4,false,$webquest->taskdescription));
    fwrite ($bf,full_tag("NATTACHMENTS",4,false,$webquest->nattachments));
    //fwrite ($bf,full_tag("FORMAT",4,false,$webquest->format));
    fwrite ($bf,full_tag("GRADINGSTRATEGY",4,false,$webquest->gradingstrategy));
    fwrite ($bf,full_tag("MAXBYTES",4,false,$webquest->maxbytes));
    fwrite ($bf,full_tag("SUBMISSIONSTART",4,false,$webquest->submissionstart));
    fwrite ($bf,full_tag("SUBMISSIONEND",4,false,$webquest->submissionend));
    fwrite ($bf,full_tag("GRADE",4,false,$webquest->grade));
    fwrite ($bf,full_tag("TEAMSMODE",4,false,$webquest->teamsmode));
    fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$webquest->timemodified));
    fwrite ($bf,full_tag("NTASKS",4,false,$webquest->ntasks));
    fwrite ($bf,full_tag("USEPASSWORD",4,false,$webquest->usepassword));
    fwrite ($bf,full_tag("PASSWORD",4,false,$webquest->password));


    $status = backup_webquest_tasks($bf,$preferences,$webquest->id);
    $status = backup_webquest_resources($bf,$preferences,$webquest->id);
    if ($webquest->teamsmode){
        $status = backup_webquest_teams($bf,$preferences,$webquest->id);
    }


    //if we've selected to backup users info
    if (backup_userdata_selected($preferences,'webquest',$webquest->id)) {
        $status = backup_webquest_submissions($bf,$preferences,$webquest->id);
        $status = backup_webquest_files_instance($bf,$preferences,$webquest->id);
    }

    $status =fwrite ($bf,end_tag("MOD",3,true));

    return $status;
}

function backup_webquest_resources ($bf,$preferences,$webquest) {

    global $CFG;

    $status = true;

    $webquest_resources = get_records("webquest_resources","webquestid",$webquest,"id");
    if ($webquest_resources) {
        $status =fwrite ($bf,start_tag("WQRESOURCES",4,true));
        foreach ($webquest_resources as $res) {
            $status =fwrite ($bf,start_tag("RES",5,true));
            fwrite ($bf,full_tag("NAME",6,false,$res->name));
            fwrite ($bf,full_tag("DESCRIPTION",6,false,$res->description));
            fwrite ($bf,full_tag("PATH",6,false,$res->path));
            fwrite ($bf,full_tag("RESNO",6,false,$res->resno));
            $status = fwrite ($bf,end_tag("RES",5,true));
        }
        $status =fwrite ($bf,end_tag("WQRESOURCES",4,true));
    }
    return $status;
}


function backup_webquest_tasks ($bf,$preferences,$webquest) {

    global $CFG;

    $status = true;

    $webquest_tasks = get_records("webquest_tasks","webquestid",$webquest,"id");
    //If there is webquest_tasks
    if ($webquest_tasks) {
        $status =fwrite ($bf,start_tag("TASKS",4,true));
        //Iterate over each task
        foreach ($webquest_tasks as $task) {
            //Start task
            $status =fwrite ($bf,start_tag("TASK",5,true));
            fwrite ($bf,full_tag("TASKNO",6,false,$task->taskno));
            fwrite ($bf,full_tag("DESCRIPTION",6,false,$task->description));
            fwrite ($bf,full_tag("SCALE",6,false,$task->scale));
            fwrite ($bf,full_tag("MAXSCORE",6,false,$task->maxscore));
            fwrite ($bf,full_tag("WEIGHT",6,false,$task->weight));
            fwrite ($bf,full_tag("STDDEV",6,false,$task->stddev));
            fwrite ($bf,full_tag("TOTALASSESSMENTS",6,false,$task->totalassessments));
            $status = backup_webquest_rubrics($bf,$preferences,$webquest,$task->taskno);
            $status = fwrite ($bf,end_tag("TASK",5,true));
        }
        //Write end tag
        $status =fwrite ($bf,end_tag("TASKS",4,true));
    }
    return $status;
}


function backup_webquest_rubrics ($bf,$preferences,$webquest,$taskno) {

    global $CFG;

    $status = true;

    $webquest_rubrics = get_records_sql("SELECT * from {$CFG->prefix}webquest_rubrics r
                                         WHERE r.webquestid = '$webquest' and r.taskno = '$taskno'
                                         ORDER BY r.taskno");

    //If there is webquest_rubrics
    if ($webquest_rubrics) {
        $status =fwrite ($bf,start_tag("RUBRICS",6,true));
        foreach ($webquest_rubrics as $rubric) {
            $status =fwrite ($bf,start_tag("RUBRIC",7,true));
            fwrite ($bf,full_tag("DESCRIPTION",8,false,$rubric->description));
            fwrite ($bf,full_tag("RUBRICNO",8,false,$rubric->rubricno));
            $status =fwrite ($bf,end_tag("RUBRIC",7,true));
        }
        $status =fwrite ($bf,end_tag("RUBRICS",6,true));
    }
    return $status;
}


Function backup_webquest_submissions ($bf,$preferences,$webquest) {

    global $CFG;

    $status = true;

    $webquest_submissions = get_records("webquest_submissions","webquestid",$webquest,"id");
    //If there is submissions
    if ($webquest_submissions) {
        $status =fwrite ($bf,start_tag("SUBMISSIONS",4,true));
        foreach ($webquest_submissions as $submission) {
            $status =fwrite ($bf,start_tag("SUBMISSION",5,true));
            fwrite ($bf,full_tag("ID",6,false,$submission->id));
            fwrite ($bf,full_tag("USERID",6,false,$submission->userid));
            fwrite ($bf,full_tag("TITLE",6,false,$submission->title));
            fwrite ($bf,full_tag("TIMECREATED",6,false,$submission->timecreated));
            fwrite ($bf,full_tag("MAILED",6,false,$submission->mailed));
            fwrite ($bf,full_tag("DESCRIPTION",6,false,$submission->description));
            fwrite ($bf,full_tag("GRADE",6,false,$submission->grade));
            fwrite ($bf,full_tag("TIMEGRADED",6,false,$submission->timegraded));
            fwrite ($bf,full_tag("GRADECOMMENT",6,false,$submission->gradecomment));

            $status = backup_webquest_grades($bf,$preferences,$webquest,$submission->id);
            $status =fwrite ($bf,end_tag("SUBMISSION",5,true));
        }
        $status =fwrite ($bf,end_tag("SUBMISSIONS",4,true));
    }
    return $status;
}


function backup_webquest_grades ($bf,$preferences,$webquest,$submissionid) {

    global $CFG;

    $status = true;


    $webquest_grades = get_records_sql("SELECT * from {$CFG->prefix}webquest_grades g
                                          WHERE g.webquestid = '$webquest' and g.sid = '$submissionid'
                                          ORDER BY g.taskno");

    //If there is webquest_grades
    if ($webquest_grades) {
        $status =fwrite ($bf,start_tag("GRADES",6,true));
        foreach ($webquest_grades as $grade) {
            $status =fwrite ($bf,start_tag("GRADE",7,true));
            fwrite ($bf,full_tag("TASKNO",8,false,$grade->taskno));
            fwrite ($bf,full_tag("FEEDBACK",8,false,$grade->feedback));
            fwrite ($bf,full_tag("GRADE_VALUE",8,false,$grade->grade));
            $status =fwrite ($bf,end_tag("GRADE",7,true));
        }
        $status =fwrite ($bf,end_tag("GRADES",6,true));
    }
    return $status;
}


//Backup webquest files because we've selected to backup user info
//and files are user info's level
function backup_webquest_files($bf,$preferences) {

    global $CFG;

    $status = true;

    //First we check to moddata exists and create it as necessary
    //in temp/backup/$backup_code  dir
    $status = check_and_create_moddata_dir($preferences->backup_unique_code);
    //Now copy the webquest dir
    if ($status) {
        if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/webquest")) {
            $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/webquest/",
                                       $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/webquest/");
        }
    }

    return $status;

}


function backup_webquest_files_instance($bf,$preferences,$webquest) {
        global $CFG;

        $status = true;

    //First we check to moddata exists and create it as necessary
    //in temp/backup/$backup_code  dir
    $webquest_submissions = get_records("webquest_submissions","webquestid",$webquest,"id");
    $status = check_and_create_moddata_dir($preferences->backup_unique_code);
    $status = check_dir_exists($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/webquest/",true);
    if (($status) and ($webquest_submissions)) {
        foreach ($webquest_submissions as $submission) {
            if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/webquest/".$submission->id)) {
                $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/webquest/".$submission->id,
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/webquest/".$submission->id);
            }
        }
    }

    return $status;
}


function backup_webquest_teams($bf,$preferences,$webquest){
    global $CFG;

    $status = true;

    $webquest_teams = get_records("webquest_teams","webquestid",$webquest,"id");
    if($webquest_teams){
        $status =fwrite ($bf,start_tag("TEAMS",4,true));

        foreach($webquest_teams as $team){
            $status =fwrite ($bf,start_tag("TEAM",5,true));
            fwrite ($bf,full_tag("ID",6,false,$team->id));
            fwrite ($bf,full_tag("NAME",6,false,$team->name));
            fwrite ($bf,full_tag("DESCRIPTION",6,false,$team->description));
            // If  user data selecte then backup team members
            if (backup_userdata_selected($preferences,'webquest',$webquest)){
                $status = backup_webquest_team_members($bf,$preferences,$webquest,$team->id);
            }
            $status =fwrite ($bf,end_tag("TEAM",5,true));
        }
        $status =fwrite ($bf,end_tag("TEAMS",4,true));
    }
    return $status;
}

function backup_webquest_team_members($bf,$preferences,$webquest,$team){
    global $CFG;
    $status = true;

    $team_members = get_records_sql("SELECT * from {$CFG->prefix}webquest_team_members m
                                        WHERE m.webquestid = '$webquest' and m.teamid = '$team'
                                        ORDER BY m.id");

    if($team_members){
        $status = fwrite ($bf,start_tag("MEMBERS",6,true));

        foreach($team_members as $member){
            $status = fwrite($bf,start_tag("MEMBER",7,true));
            fwrite ($bf,full_tag("USERID",8,false,$member->userid));
            $status  = fwrite($bf,end_tag("MEMBER",7,true));
        }
        $status = fwrite ($bf,end_tag("MEMBERS",6,true));
    }
    return $status;
}


function webquest_check_backup_mods_instances($instance,$backup_unique_code) {
    //First the course data
    $info[$instance->id.'0'][0] = $instance->name;
    $info[$instance->id.'0'][1] = '';
    //Now, if requested, the user_data
    if (!empty($instance->userdata)) {
        $info[$instance->id.'1'][0] = get_string("submission","webquest");
        if ($ids = webquest_submission_ids_by_instance ($instance->id)) { 
            $info[$instance->id.'1'][1] = count($ids);
        } else {
            $info[$instance->id.'1'][1] = 0;
        }
        $info[$instance->id.'2'][0] = get_string("teams","webquest");
        if ($ids = webquest_team_ids_by_instance($instance->id)){
            $info[$instance->id.'2'][1] = $ids;
        }else{
            $info[$instance->id.'2'][1] = 0;
        }
    }
    return $info;
}


function webquest_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
    if (!empty($instances) && is_array($instances) && count($instances)) {
        $info = array();
        foreach ($instances as $id => $instance) {
            $info += webquest_check_backup_mods_instances($instance,$backup_unique_code);
        }
        return $info;
    }
    ///Course Data
    $info[0][0] = get_string("modulenameplural","webquest");
    if ($ids = workshop_ids ($course)) {
        $info[0][1] = count($ids);
    } else {
        $info[0][1] = 0;
    }

    ///User data if is  necesary
    if ($user_data) {
        $info[1][0] = get_string("submissions","webquest");
        if ($ids = webquest_submission_ids_by_course ($course)) { 
            $info[1][1] = count($ids);
        } else {
            $info[1][1] = 0;
        }
        $info[2][0] = get_string("teams","webquest");
        if ($ids = webquest_team_ids_by_course($course)){
            $info[2][1] = count($ids);
        }else{
            $info[2][1] = 0;
        }
    }
    return $info;
}


function webquest_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of webquest
        $buscar="/(".$base."\/mod\/webquest\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@WEBQUESTINDEX*$2@$',$content);

        //Link to webquest view by moduleid
        $buscar="/(".$base."\/mod\/webquest\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@WEBQUESTVIEWBYID*$2@$',$result);

        return $result;
}


function webquest_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT w.id, w.course
                                 FROM {$CFG->prefix}webquest w
                                 WHERE w.course = '$course'");
}

    //Returns an array of webquest_submissions id
function webquest_submission_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT s.id , s.webquestid
                                 FROM {$CFG->prefix}webquest_submissions s,
                                      {$CFG->prefix}webquest w
                                 WHERE w.course = '$course' AND
                                       s.webquestid = w.id");
}

function webquest_submission_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT s.id , s.webquestid
                                 FROM {$CFG->prefix}webquest_submissions s
                                 WHERE s.webquestid = $instanceid");
}


function webquest_team_ids_by_course($course){
    global $CFG;

    return get_record_sql("SELECT m.id , m.webquestid
                                 FROM {$CFG->prefix}webquest_teams m,
                                      {$CFG->prefix}webquest w
                                 WHERE w.course = '$course' AND
                                       m.webquestid = w.id");

}


function webquest_team_ids_by_instance($instanceid){
    global $CFG;

    return count_records("webquest_teams","webquestid",$instanceid);
}

?>
<?php  // $Id: lib.php,v 1.5 2007/09/09 09:00:19 stronk7 Exp $

/// Library of functions and constants for module webquest

require_once($CFG->libdir.'/filelib.php');

function webquest_add_instance($webquest) {
/// Given an object containing all the necessary data,
/// (defined by the form in mod.html) this function
/// will create a new instance and return the id number
/// of the new instance.


    $webquest->timemodified = time();
    //Encode password if necessary
    if (!empty($webquest->password)){
       $webquest->password = md5($webquest->password);
    }  else unset($webquest->password);

    $webquest->submissionstart = make_timestamp($webquest->submissionstartyear,
            $webquest->submissionstartmonth, $webquest->submissionstartday, $webquest->submissionstarthour,
            $webquest->submissionstartminute);
    $webquest->submissionend = make_timestamp($webquest->submissionendyear,
            $webquest->submissionendmonth, $webquest->submissionendday, $webquest->submissionendhour,
            $webquest->submissionendminute);

    if (!webquest_check_dates($webquest)) {
        return get_string('invaliddates', 'webquest');
    }else{
        return insert_record("webquest", $webquest);
    }
}


function webquest_update_instance($webquest) {

    $webquest->timemodified = time();
    $webquest->id = $webquest->instance;

    //Encode password if necessary
    if (!empty($webquest->password)){
       $webquest->password = md5($webquest->password);
    } else unset($webquest->password);
    $webquest->submissionstart = make_timestamp($webquest->submissionstartyear,
            $webquest->submissionstartmonth, $webquest->submissionstartday, $webquest->submissionstarthour,
            $webquest->submissionstartminute);
    $webquest->submissionend = make_timestamp($webquest->submissionendyear,
            $webquest->submissionendmonth, $webquest->submissionendday, $webquest->submissionendhour,
            $webquest->submissionendminute);
     if (!webquest_check_dates($webquest)){
       return get_string('invaliddates', 'webquest');
     }

    return update_record("webquest", $webquest);
}


function webquest_delete_instance($id) {
/// Given an ID of an instance of this module,
/// delete the instance and any data that depends on it.
    global $CFG;

    if (! $webquest = get_record("webquest", "id", "$id")) {
        return false;
    }

    $result = true;

    if (! delete_records("webquest", "id", "$webquest->id")) {
        $result = false;
    }
    if (!delete_records("webquest_resources", "webquestid", "$webquest->id")){
        $result = false;
    }
    if (!delete_records("webquest_tasks", "webquestid", "$webquest->id")){
        $result = false;
    }
    if (!delete_records("webquest_rubrics", "webquestid", "$webquest->id")){
        $result = false;
    }
    if (!delete_records("webquest_grades", "webquestid", "$webquest->id")){
        $result = false;
    }
    if (!delete_records("webquest_teams", "webquestid", "$webquest->id")){
        $result = false;
    }
    if (!delete_records("webquest_team_members", "webquestid", "$webquest->id")){
        $result = false;
    }
    if ($submissions = get_records("webquest_submissions", "webquestid", "$webquest->id")){
        foreach ($submissions as $submission){
            $dirpath = "$CFG->dataroot/$webquest->course/$CFG->moddata/webquest/$submission->id";
            fulldelete($dirpath);
        }
    }
    if (!delete_records("webquest_submissions", "webquestid", "$webquest->id")){
        $result = false;
    }
    return $result;
}

function webquest_user_outline($course, $user, $mod, $webquest) {
/// Return a small object with summary information about what a
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description

    return $return;
}

function webquest_user_complete($course, $user, $mod, $webquest) {
/// Print a detailed representation of what a  user has done with
/// a given particular instance of this module, for user activity reports.

    return true;
}

function webquest_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity
/// that has occurred in webquest activities and print it out.
/// Return true if there was output, or false is there was none.

    global $CFG;

    return false;  //  True if anything was printed, otherwise false
}

function webquest_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such
/// as sending out mail, toggling flags etc ...

    global $CFG;

    return true;
}

function webquest_grades($webquestid) {

    $return = null;
    if ($webquest = get_record("webquest","id",$webquestid)){
        if ($webquest->gradingstrategy > 0){
            if(!$webquest->teamsmode){
                if ($students = get_course_students($webquest->course)){
                    foreach ($students as $student) {
                        $submission = get_record("webquest_submissions","webquestid",$webquest->id,"userid",$student->id);
                        if (count_records("webquest_grades","sid",$submission->id)){
                            $grade = number_format($submission->grade * $webquest->grade / 100);
                        }else{
                            $grade = null;
                        }
                        $return->grades[$student->id] = $grade;
                    }
                }
            }else{
                if ($students = get_course_students($webquest->course)){
                    if($submissionsraw = get_records("webquest_submissions","webquestid",$webquest->id)){
                        require_once("locallib.php");
                        foreach($submissionsraw as $submission){
                            if (count_records("webquest_grades","sid",$submission->id)){
                                $grade = number_format($submission->grade * $webquest->grade / 100);
                            }else{
                                $grade = null;
                            }
                            if($membersid = webquest_get_team_members($submission->userid)){
                                foreach($membersid as $memberid){
                                    $return->grades[$memberid] = $grade;
                                }
                            }
                        }
                    }
                }
            }
            $return->maxgrade = $webquest->grade;
        }
    }
    return $return;
}

function webquest_get_participants($webquestid) {
//Must return an array of user records (all data) who are participants
//for a given instance of webquest. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.

    return false;
}

function webquest_scale_used ($webquestid,$scaleid) {
//This function returns if a scale is being used by one webquest
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.

    $return = false;

    //$rec = get_record("webquest","id","$WebQuestid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other WebQuest functions go here.
function webquest_check_dates($webquest) {
    // allow submission and assessment to start on the same date and to end on the same date
    // but enforce non-empty submission period and non-empty assessment period.
    return ($webquest->submissionstart < $webquest->submissionend);
}


?>
<?php

/**
* @package brainstorm
* @author Valery Fremaux / 1.8
* @date 05/01/2008
*
* A special controller for switching phases in sequential mode
*/
/**************************************** save a gradeset ***********************************/
if ($action == 'savegrade'){
    $userid = required_param('for', PARAM_INT);

    /// remove old records
    delete_records('brainstorm_grades', 'brainstormid', $brainstorm->id, 'userid', $userid);

    if ($brainstorm->singlegrade){
        $grade = required_param('grade', PARAM_INT);
        $graderecord->brainstormid = $brainstorm->id;
        $graderecord->userid = $userid;
        $graderecord->grade = $grade;
        $graderecord->gradeitem = 'single';
        $graderecord->timeupdated = time();
        if (!insert_record('brainstorm_grades', $graderecord)){
            error("Could not insert grade");
        }
    }
    else{ // record dissociated grade
        $graderecord->brainstormid = $brainstorm->id;
        $graderecord->userid = $userid;
        $graderecord->timeupdated = time();

        if ($brainstorm->seqaccesscollect){
            $participategrade = optional_param('participate', '', PARAM_INT);
            $graderecord->grade = $participategrade;
            $graderecord->gradeitem = 'participate';
            if (!insert_record('brainstorm_grades', $graderecord)){
                error("Could not insert grade");
            }
        }

        if ($brainstorm->seqaccessprepare){
            $preparegrade = optional_param('prepare', '', PARAM_INT);
            $graderecord->grade = $preparegrade;
            $graderecord->gradeitem = 'prepare';
            if (!insert_record('brainstorm_grades', $graderecord)){
                error("Could not insert grade");
            }
        }

        if ($brainstorm->seqaccessorganize){
            $organizegrade = optional_param('organize', '', PARAM_INT);
            $graderecord->grade = $organizegrade;
            $graderecord->gradeitem = 'organize';
            if (!insert_record('brainstorm_grades', $graderecord)){
                error("Could not insert grade");
            }
        }

        if ($brainstorm->seqaccessorganize){
            $feedbackgrade = optional_param('feedback', '', PARAM_INT);
            $graderecord->grade = $feedbackgrade;
            $graderecord->gradeitem = 'feedback';
            if (!insert_record('brainstorm_grades', $graderecord)){
                error("Could not insert grade");
            }
        }
    }

    $teacherfeedback = addslashes(optional_param('teacherfeedback', '', PARAM_CLEANHTML));
    $feedbackformat = addslashes(optional_param('feedbackformat', 0, PARAM_INT));
    $userdatarecord = get_record('brainstorm_userdata', 'brainstormid', $brainstorm->id, 'userid', $userid);
    unset($userdatarecord->report);
    unset($userdatarecord->reportformat);
    $userdatarecord->feedback = $teacherfeedback;
    $userdatarecord->feedbackformat = $feedbackformat;

    if (!update_record('brainstorm_userdata', $userdatarecord)){
		// first time user is getting a grade (maybe?)
		$userdatarecord->brainstormid = $brainstorm->id;
		$userdatarecord->userid= $userid;
		if (!insert_record('brainstorm_userdata', $userdatarecord)){
			error("Could not INSERT a new user feedback record");
		} //else continue;
        //error("Could not UPDATE user feedback record");
    }
}

/**************************************** delete an assessment ***********************************/
if ($action == 'deletegrade'){
    $userid = required_param('for', PARAM_INT);
    delete_records('brainstorm_grades', 'brainstormid', $brainstorm->id, 'userid', $userid);
}

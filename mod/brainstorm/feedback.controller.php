<?php

/********************************  Asked for collecting form *******************************/
if ($action == 'editreport'){
    $form->cmid = $cm->id;
    $form->report = get_field('brainstorm_userdata', 'report', 'brainstormid', $brainstorm->id, 'userid', $USER->id);
    $form->reportformat = get_field('brainstorm_userdata', 'reportformat', 'brainstormid', $brainstorm->id, 'userid', $USER->id);
    include 'report.html';
    return -1;
}
if ($action == 'doreport'){
    $report = addslashes(required_param('report', PARAM_CLEANHTML));
    $reportformat = required_param('reportformat', PARAM_INT);
    
    $oldrecord = get_record('brainstorm_userdata', 'brainstormid', $brainstorm->id, 'userid', $USER->id);
    if ($oldrecord){
        $oldrecord->report = $report;
        $oldrecord->reportformat = $reportformat;
        $oldrecord->timeupdated = time();
        if (!update_record('brainstorm_userdata', $oldrecord)){
            error("Could not update report");
        }
    }
    else{
        $newrecord->brainstormid = $brainstorm->id;
        $newrecord->userid = $USER->id;
        $newrecord->report = $report;
        $newrecord->reportformat = $reportformat;
        $newrecord->feedback = '';
        $newrecord->feedbackformat = 0;
        $newrecord->timeupdated = time();
        if (!insert_record('brainstorm_userdata', $newrecord)){
            error("Could not insert report");
        }
    }
}
?>
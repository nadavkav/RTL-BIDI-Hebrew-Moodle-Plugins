<?php  // $Id: overview.php,v 1.3 2008/12/01 13:18:25 jamiesensei Exp $
/**
 * This page prints an overview of a particular instance of qcreate for someone with grading permission
 *
 * @author
 * @version $Id: overview.php,v 1.3 2008/12/01 13:18:25 jamiesensei Exp $
 * @package qcreate
 **/


    require_once("../../config.php");
    require_once($CFG->dirroot.'/mod/qcreate/lib.php');
    require_once($CFG->dirroot.'/mod/qcreate/locallib.php');
    require_once($CFG->dirroot . '/question/editlib.php');
    
    
    list($thispageurl, $contexts, $cmid, $cm, $qcreate, $pagevars) = question_edit_setup('questions', true);
    $qcreate->cmidnumber = $cm->id;
    require_capability('mod/qcreate:grade', get_context_instance(CONTEXT_MODULE, $cm->id));
    
    $requireds = get_records('qcreate_required', 'qcreateid', $qcreate->id, 'qtype', 'qtype, no, id');


    $modulecontext = get_context_instance(CONTEXT_MODULE, $cm->id);


    require_login($COURSE->id);
    
    require_capability('mod/qcreate:grade', $modulecontext);

    add_to_log($COURSE->id, "qcreate", "overview", "overview.php?id=$cm->id", "$qcreate->id");

/// Print the page header
    $strqcreates = get_string("modulenameplural", "qcreate");
    $strqcreate  = get_string("modulename", "qcreate");

    $navlinks = array();
    $navlinks[] = array('name' => $strqcreates, 'link' => "index.php?id=$COURSE->id", 'type' => 'activity');
    $navlinks[] = array('name' => format_string($qcreate->name), 'link' => '', 'type' => 'activityinstance');

    $navigation = build_navigation($navlinks);

    print_header_simple(format_string($qcreate->name), "", $navigation, "", "", true,
                  update_module_button($cm->id, $COURSE->id, $strqcreate), navmenu($COURSE, $cm));
    $mode = 'overview';
    
    include('tabs.php');

    print_box(format_text($qcreate->intro, $qcreate->introformat), 'generalbox', 'intro');

    $qcreatetime = new object();
    $qcreatetime->timeopen = userdate($qcreate->timeopen); 
    $qcreatetime->timeclose = userdate($qcreate->timeclose); 
/*    if ($qcreate->timeopen == 0 AND $qcreate->timeclose ==0 ){
        $timestring = get_string('timenolimit', 'qcreate');
    } else if ($qcreate->timeopen != 0 AND $qcreate->timeclose ==0 ) {
        $timestring = get_string('timeopen', 'qcreate', $qcreatetime);
        
    } else if ($qcreate->timeopen == 0 AND $qcreate->timeclose !=0 ) {
        $timestring = get_string('timeclose', 'qcreate', $qcreatetime);
    } else {
        $timestring = get_string('timeopenclose', 'qcreate', $qcreatetime);
    }*/
    $timestring = qcreate_time_status($qcreate);
    
    if ($qcreate->graderatio == 100){
        $gradestring = get_string('gradeallautomatic', 'qcreate');
    } else if ($qcreate->graderatio == 0){
        $gradestring = get_string('gradeallmanual', 'qcreate');
    } else {
        $gradeobj = new object();
        $gradeobj->automatic = $qcreate->graderatio;
        $gradeobj->manual = 100 - $qcreate->graderatio;
        $gradestring = get_string('grademixed', 'qcreate', $gradeobj);
    }
        
    echo '<div class="mdl-align">';
    echo '<p>'.$timestring.'</p>';
    echo '<p><strong>'.get_string('totalgrade', 'qcreate').'</strong> : '.$qcreate->grade.'</p>';
    echo '<p><strong>'.get_string('graderatio', 'qcreate').'</strong> : '.$gradestring.'</p>';
    echo '</div>';


    qcreate_teacher_overview($requireds, $qcreate);

    /// Finish the page
    print_footer($COURSE);
?>

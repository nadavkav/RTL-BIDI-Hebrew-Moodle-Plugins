<?php  // $Id: view.php,v 1.10 2008/12/01 13:18:25 jamiesensei Exp $
/**
 * This page prints a particular instance of qcreate
 *
 * @author
 * @version $Id: view.php,v 1.10 2008/12/01 13:18:25 jamiesensei Exp $
 * @package qcreate
 **/

/// (Replace qcreate with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");


    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // qcreate ID
    $delete  = optional_param('delete', 0, PARAM_INT);  // question id to delete
    $confirm  = optional_param('confirm', 0, PARAM_BOOL);  

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $qcreate = get_record("qcreate", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $qcreate = get_record("qcreate", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $qcreate->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("qcreate", $qcreate->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }
    $qcreate->cmidnumber = $cm->id;

    $requireds = get_records('qcreate_required', 'qcreateid', $qcreate->id, 'qtype', 'qtype, no, id');

    $thisurl = new moodle_url($CFG->wwwroot.'/mod/qcreate/view.php');
    $thisurl->params(array('id'=>$cm->id));

    $modulecontext = get_context_instance(CONTEXT_MODULE, $cm->id);



    //modedit.php forwards to this page after creating coursemodule record.
    //this is the first chance we get to set capabilities in the newly created
    //context.
    qcreate_student_q_access_sync($qcreate, $modulecontext, $course);


    require_login($course->id);
    
    if (has_capability('mod/qcreate:grade', $modulecontext)){
        redirect($CFG->wwwroot.'/mod/qcreate/edit.php?cmid='.$cm->id);
    }


/// Print the page header
    $strqcreates = get_string("modulenameplural", "qcreate");
    $strqcreate  = get_string("modulename", "qcreate");

    $navlinks = array();
    $navlinks[] = array('name' => $strqcreates, 'link' => "index.php?id=$course->id", 'type' => 'activity');
    $navlinks[] = array('name' => format_string($qcreate->name), 'link' => '', 'type' => 'activityinstance');

    $navigation = build_navigation($navlinks);

    $headerargs = array(format_string($qcreate->name), "", $navigation, "", "", true,
                  update_module_button($cm->id, $course->id, $strqcreate), navmenu($course, $cm));

    if (!$cats = get_categories_for_contexts($modulecontext->id)){
        //if it has not been made yet then make a default cat
        question_make_default_categories(array($modulecontext));
        $cats = get_categories_for_contexts($modulecontext->id);
    }
    $catsinctx = array();
    foreach ($cats as $catinctx){
        $catsinctx[]=$catinctx->id;
    }
    $catsinctxlist = join($catsinctx, ',');
    $cat = array_shift($cats);

    if ($delete && question_require_capability_on($delete, 'edit')){
        if ($confirm && confirm_sesskey()){
            if (!delete_records_select('question', "id = $delete AND category IN ($catsinctxlist)")){
                print_error('question_not_found');
            } else {
                qcreate_update_grades($qcreate, $USER->id);
                redirect($CFG->wwwroot.'/mod/qcreate/view.php?id='.$cm->id);
            }
        } else {
            call_user_func_array('print_header_simple', $headerargs);
            print_heading(get_string('delete'));
            notice_yesno(get_string('confirmdeletequestion', 'qcreate'), 'view.php', 'view.php',
                 array('id' => $cm->id, 'sesskey'=> sesskey(), 'confirm'=>1, 'delete'=>$delete), 
                 array('id' => $cm->id), 'POST', 'GET');
            print_footer('none');
            die;
        }
    }
    
    call_user_func_array('print_header_simple', $headerargs);
    add_to_log($course->id, "qcreate", "view", "view.php?id=$cm->id", "$qcreate->id");

    print_box(format_text($qcreate->intro, $qcreate->introformat), 'generalbox', 'intro');

    echo '<div class="mdl-align">';
    echo '<p>'.qcreate_time_status($qcreate).'</p>';
    echo '</div>';



    qcreate_required_q_list($requireds, $cat, $thisurl, $qcreate, $cm, $modulecontext);


    /// Finish the page
    print_footer($course);
?>

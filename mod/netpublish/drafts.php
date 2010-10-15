<?php // $Id: drafts.php,v 1.2 2007/04/27 09:10:51 janne Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id        = required_param('id',           PARAM_INT);  // module id
    $a         = optional_param('a',         0, PARAM_INT);  // module id
    $articleid = optional_param('articleid', 0, PARAM_INT);
    $statusid  = optional_param('statusid',  0, PARAM_INT);
    $tab       = optional_param('tab',       1, PARAM_INT);

    if ($id) {
        if (! $info = netpublish_get_record($id) ) {
            error("Course Module ID was incorrect");
        }
    } else {
        // Get all that I need using only one query
        if (! $info = netpublish_get_record($a) ) {
            error("Course Module ID was incorrect");
        }
    }

    // Construct objects used in Moodle
    netpublish_set_std_classes ($cm, $course, $netpublish, $info);
    unset($info);

$cm = get_coursemodule_from_instance('netpublish',$cm->instance);

    require_login($course->id);
    $modcontext    = get_context_instance(CONTEXT_MODULE, $cm->id);
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

    //$isteacher = has_capability('moodle/legacy:editingteacher', $context);
    //$isstudent = has_capability('moodle/legacy:student', $context);

    if ( !has_capability('moodle/legacy:editingteacher', $coursecontext) and
         !has_capability('moodle/legacy:teacher', $coursecontext) and
         !has_capability('moodle/legacy:student', $coursecontext) ) {
        error("Only memebers of this course can view pending articles!",
              sprintf("%s/course/view.php?id=%d", $CFG->wwwroot, $course->id));
    }

    if ($data = data_submitted()) {

        // Check rights
        $article = get_record("netpublish_articles","id", $articleid);
        $rights  = netpublish_get_rights($article->rights);
        $canedit = false;

        $redirect = sprintf("%s/mod/netpublish/drafts.php?id=%d", $CFG->wwwroot, $cm->id);

        if ($article->publishid != $cm->instance) {
            error("You cannot change other netpublishes article status!",
                  $CFG->wwwroot .'/mod/netpublish/view.php?id='. $cm->id);
        }

        if ( has_capability('moodle/legacy:editingteacher', $coursecontext) ) {
            $canedit = true;
        }

        if ($article->userid == $USER->id) {
            $canedit = true;
        }

        if ( !empty($rights[$USER->id]) ) {
            if ( $nperm->can_write($rights[$USER->id])) {
                $canedit = true;
            }
        }

        if (!$canedit) {
            error("You dont have permissions to change this article's status!", $redirect);
        }

        $data->id       = $articleid;
        $data->statusid = $statusid;

        if (!has_capability('moodle/legacy:editingteacher', $coursecontext) && $data->statusid == 4) {
            $strerror = get_string("unauthorizedstatus","netpublish");
            error($strerror, $redirect);
        }

        if ($data->statusid == 4) {
            $data->timepublished = time();
        }

        if (!update_record("netpublish_articles", $data)) {
            error("Couldn't update articles status!", $redirect);
        }
    }

    $sort = (has_capability('moodle/legacy:editingteacher', $coursecontext)) ? 1 : 0;

    $articles = netpublish_get_pending_articles($cm->instance, $sort);
//echo " cm->instance = $cm->instance ";
//print_r($articles );
    $strpublishes = get_string("modulenameplural","netpublish");
    $strpublish   = get_string("modulename","netpublish");
    $strpending   = get_string("pendingarticles","netpublish");

    $publish      = false;

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    if (has_capability('moodle/legacy:editingteacher', $coursecontext)) {
        $publish = true;
    }

    $icons        = $CFG->wwwroot .'/pix/t';
    $editbutton   = '<img src="'. $icons .'/edit.gif" alt="edit" title="edit" />';
    $deletebutton = '<img src="'. $icons .'/delete.gif" alt="delete" title="delete" />';

    $navigation .= " <a href=\"index.php?id=$course->id\">$strpublishes</a> -> ";
    $navigation .= "<a href=\"view.php?id=$cm->id\">$netpublish->name</a> -> ";

/*    print_header("$course->shortname: $netpublish->name", "$course->fullname",
                 "$navigation $strpending",
                 "", "", true, "");
*/
    //print_heading($strpending);

/// Print header.
    $navigation = build_navigation('', $cm);
    print_header_simple(format_string($netpublish->name), "",
                 $navigation, "", "", true, $buttontext, navmenu($course, $cm));

    print_simple_box_start("center", "100%");
    include_once('drafts.html.php');
    print_simple_box_end();
    print_footer($course);
?>
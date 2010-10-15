<?php // $Id: preview.php,v 1.2 2007/04/27 09:10:51 janne Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id       = required_param('id',         PARAM_INT); // module id
    $a        = optional_param('a',       0, PARAM_INT); // module id
    $article  = optional_param('article', 0, PARAM_INT); // article id
    $statusid = optional_param('status',  4, PARAM_INT); // Status id of published article

    if ($id) {
        // Get all that I need using only one query

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
    netpublish_set_std_classes ($cm, $course, $mod, $info);
    unset($info);

    require_login($course->id);

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $strauthor       = get_string("by","netpublish");
    $strpublished    = get_string("published","netpublish");
    $strnotpublished = get_string("notpublished","netpublish");
    $strcreated      = get_string("created","netpublish");
    $strmodified     = get_string("modified");
    $strpublishes    = get_string("modulenameplural","netpublish");
    $strpreview      = get_string("preview","netpublish");
    $strpending      = get_string("pendingarticles","netpublish");

    $objarticle = netpublish_get_article($article, $statusid);

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $navigation .= "<a href=\"index.php?id=$course->id\">$strpublishes</a> -> ";
    $navigation .= "<a href=\"view.php?id=$cm->id\">$mod->name</a> -> ";
    $navigation .= "<a href=\"drafts.php?id=$cm->id\">$strpending</a> -> $strpreview";

    $icons        = $CFG->wwwroot .'/pix/t';
    $editbutton   = '<img src="'. $icons .'/edit.gif" alt="edit" title="edit" />';
    $deletebutton = '<img src="'. $icons .'/delete.gif" alt="delete" title="delete" />';

    print_header_simple("$course->shortname: $mod->name", "$course->fullname",
                 "$navigation ");
    print_simple_box_start("center", "100%");
    include_once('preview.html');
    print_simple_box_end();
    print_footer($course);
?>
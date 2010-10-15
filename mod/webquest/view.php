<?php  // $Id: view.php,v 1.4 2007/09/09 09:00:21 stronk7 Exp $

/// This page prints a particular instance of webquest

    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");

    $id      = required_param('id', PARAM_INT);    // Course Module ID, or
    $a       = optional_param('a', '', PARAM_ALPHA);
    $action  = optional_param('action', '', PARAM_ALPHA);     ///action to view the instance.

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

    require_login($course->id);

    add_to_log($course->id, "webquest", "view ".$action, "view.php?id=$cm->id", "$webquest->id");

    $straction = ($action) ? '-> '.get_string($action, 'webquest') : '';
/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    $strwebquests = get_string("modulenameplural", "webquest");
    $strwebquest  = get_string("modulename", "webquest");

    print_header("$course->shortname: $webquest->name", "$course->fullname",
                "$navigation <a href=index.php?id=$course->id>$strwebquests</a> -> $webquest->name",
                "", "", true, update_module_button($cm->id, $course->id, $strwebquest),
                navmenu($course, $cm));

/// Print the main part of the page

    if (isteacher($course->id)){
        if (empty($action)){
            if (count_records("webquest_tasks", "webquestid", $webquest->id) >= $webquest->ntasks) {
                $action = "introduction";
            }else{
                redirect("tasks.php?action=edittasks&id=$cm->id");
            }
        }
    }else if (!isguest()){
        if (!$cm->visible){
            notice(get_string("activityiscurrentlyhidden"));
        }else if(empty($action)){
            $action = "introduction";
        }
    }elseif (isguest){ // he is a guest. Not allowed
        $action = 'notavailable';
    }


    if($action == 'notavailable'){
        notice(get_string("notavailable"));
    }

    $table->head[0] = format_text(get_string("pages","webquest"));
    $table->wrap[0] = 'nowrap';

    if($action == 'introduction') {
        $data[0] = "<b>".get_string("intro","webquest")."</b>";
    }else{
        $data[0] = "<b><a href=\"view.php?id=$cm->id&amp;action=introduction\">".get_string("intro","webquest")."</b>";
    }
    $table->data[0]= $data;

    if($action =='tasks'){
        $data[0] = "<b>".get_string("tasks","webquest")."</b>";
    }else{
        $data[0] = "<b><a href=\"view.php?id=$cm->id&amp;action=tasks\">".get_string("tasks","webquest")."</b>";
    }
    $table->data[1]= $data;

    if ($action == 'process'){
        $data[0] = "<b>".get_string("process","webquest")."</b>";
    }else{
        $data[0] = "<b><a href=\"view.php?id=$cm->id&amp;action=process\">".get_string("process","webquest")."</b>";
    }
    $table->data[2] = $data;

    if ($action == 'conclussion'){
        $data[0] = "<b>".get_string("conclussion","webquest")."</b>";
    }else{
        $data[0] = "<b><a href=\"view.php?id=$cm->id&amp;action=conclussion\">".get_string("conclussion","webquest")."</b>";
    }
    $table->data[3] = $data;

    if ($action == 'evaluation'){
        $data[0] = "<b>".get_string("evaluation","webquest")."</b>";
    }else{
        $data[0] = "<b><a href=\"view.php?id=$cm->id&amp;action=evaluation\">".get_string("evaluation","webquest")."</b>";
    }$table->data[4] = $data;

    if($action == 'teams'){
        $data[0] = "<b>".get_string("teams","webquest")."</b>";
    }else{
        $data[0] = "<b><a href=\"view.php?id=$cm->id&amp;action=teams\">".get_string("teams","webquest")."</b>";
    }
    $table->data[5] = $data;

  //// Now let´s print the page ////
    echo "<table width = \"100%\">";
    echo "<tr>";
    echo "<td width =\"16%\" valign =\"top\">";
    print_table($table);
    echo "</td>";
    echo "<td valign=\"top\">";
    if ($action == 'introduction'){
        webquest_print_intro($webquest);
        if (isteacher($cm->course)){
            echo ("<b><a href=\"editpages.php?id=$cm->id&amp;action=editdescription\">".get_string("editintro", 'webquest')."</a></b>");
        }
    }else if ($action =='tasks'){
        webquest_print_tasks($webquest,$cm);
    }else if ($action == 'process'){
        webquest_print_process($webquest);
        if (isteacher($cm->course)){
            echo ("<b><a href=\"editpages.php?id=$cm->id&amp;action=editprocess\">".get_string("editprocess", 'webquest')."</a></b>");
            webquest_print_editresources($webquest,$cm);
            echo ("<b><a href=\"resources.php?id=$cm->id&amp;action=editres\">".
            get_string("insertresources", 'webquest')."</a></b>");
        }else{
            webquest_print_resources($webquest);
        }
    }else if ($action == 'conclussion'){
        webquest_print_conclussion($webquest);
        if (isteacher($cm->course)){
            echo ("<b><a href=\"editpages.php?id=$cm->id&amp;action=editconclussion\">".get_string("editconclussion", 'webquest')."</a></b>");
        }
    }else if ($action == 'evaluation'){
        webquest_print_evaluation($webquest,$USER->id,$cm);
    }else if ($action == 'teams'){
        webquest_print_teams($webquest,$cm,$USER->id);
    }
    echo "</td>";
    echo "</tr>";
    echo "</table>";
    print_footer($course);

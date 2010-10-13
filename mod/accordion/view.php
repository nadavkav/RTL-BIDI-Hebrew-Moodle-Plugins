<?php  // $Id: view.php,v 1.4.8.1 2007/10/10 21:09:29 iarenaza Exp $

    require_once("../../config.php");

    $id = optional_param('id',0,PARAM_INT);    // Course Module ID, or
    $l = optional_param('l',0,PARAM_INT);     // Label ID

    if ($id) {
        if (! $cm = get_coursemodule_from_id('accordion', $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
    
        if (! $accord = get_record("accordion", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $accord = get_record("accordion", "id", $l)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $accordion->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("accordion", $accordion->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    redirect("$CFG->wwwroot/course/view.php?id=$course->id");

?>

<?php
    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
	if($id)
	{
        if (! $mindmap = get_record("mindmap", "id", $id)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $mindmap->course)) {
            error("Course is misconfigured");
        }
    }

    require_login($course->id);
    
    
    echo $mindmap->xmldata;


<?php
    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
	if($id)
	{
        if (! $mindmap = get_record("mindmap", "id", $id)) {
            error("Course module is incorrect");
        }
    }


    require_login($course->id);
    
    
    if(isset($_POST['mindmap']))
    {
	   	if((!empty($USER->id) && $mindmap->userid == $USER->id) || $mindmap->editable == '1' )
	   	{
	   		
	   		$xml = $_POST['mindmap'];
	   		if(get_magic_quotes_gpc())
	   		{
				$xml = stripslashes($xml);
			}
	   		
			$new = new stdClass();
			$new->id = $id;
			$new->xmldata = $xml;
			update_record('mindmap', $new);
		}
	}
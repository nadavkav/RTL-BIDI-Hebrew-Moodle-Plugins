<?php  // $Id: view.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * This page prints a particular instance of bookmarks
 * 
 * @author 
 * @version $Id: view.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
 * @package bookmarks
 **/

/// (Replace bookmarks with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");

	require_once("locallib.php");
	
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $url = optional_param('url','', PARAM_TEXT);
    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
    
        if (! $bookmarks = get_record("bookmarks", "id", $cm->instance)) {
            error("Course module is incorrect");
        }
		
		if (empty($url)){
			error('Link was incorrect');
		}

    }
	
    require_login($course->id);

    add_to_log($course->id, "bookmarks", "link", "link.php?id=$cm->id", "$bookmarks->id", $cm->id);

	bookmarks_inc_hits($url);
	redirect($url);


	
/// Finish the page
?>
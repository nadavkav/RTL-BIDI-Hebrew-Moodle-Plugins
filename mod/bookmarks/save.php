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
    $a  = optional_param('a', 0, PARAM_INT);  // bookmarks ID
    
	$name = optional_param('name', '', PARAM_TEXT);
	$description = optional_param('description','', PARAM_TEXT);
	$url = optional_param('url','', PARAM_TEXT);
	$tags = optional_param('tags','', PARAM_TEXT);
	$cancel = optional_param('cancel','',PARAM_TEXT);
	$itemid = optional_param('itemid', null, PARAM_INT);
	$deletetag = optional_param('tag',null, PARAM_TEXT);
	
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

    }

    require_login($course->id);

    add_to_log($course->id, "bookmarks", "save", "save.php?id=$cm->id", "$bookmarks->id", $cm->id);

/// Print header.
	$navlinks = array();
	$navlinks[] = array('name' => get_string('modulenameplural','bookmarks'), 'link' => $CFG->wwwroot.'/mod/bookmarks/index.php?id='.$course->id, 'type' => 'activity');
	$navlinks[] = array('name' => format_string($bookmarks->name), 'link' => "view.php", 'type' => 'activityinstance');
	    
	$navigation = build_navigation($navlinks);
	    
	print_header_simple(format_string($bookmarks->name), "",
		$navigation, "", "", true, update_module_button($cm->id, $course->id, get_string("modulename", "bookmarks")), navmenu($course, $cm));

/// Print the main part of the page

	if (empty($cancel)){
		$context = get_context_instance(CONTEXT_MODULE,$cm->id);
		
		require_capability('mod/bookmarks:additem',$context);

		if (isset($deletetag)){
			bookmarks_untag($itemid,$deletetag);	
			redirect('edit.php?id='.$id.'&item='.$itemid, get_string('deletingtag', 'bookmarks'), 3);
		} elseif (isset($itemid)){
			bookmarks_update_bookmark($itemid, $name, $description, $url, $tags);
			redirect("view.php?id=$id", get_string('saving', 'bookmarks'), 3);
		} else{
			bookmarks_add_bookmark($name, $description, $url, $tags, $bookmarks->id);
			redirect("view.php?id=$id", get_string('saving', 'bookmarks'), 3);
		}
		
	} else {
		redirect("view.php?id=$id", get_string('canceled', 'bookmarks'), 3);
	}
	

	
/// Finish the page
    print_footer($course);
?>
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
    
	$itemid = optional_param('item',null,PARAM_TEXT);

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

    add_to_log($course->id, "bookmarks", "del", "del.php?id=$cm->id", "$bookmarks->id", $cm->id);

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

/// Print header.
	$navlinks = array();
	$navlinks[] = array('name' => get_string('modulenameplural','bookmarks'), 'link' => $CFG->wwwroot.'/mod/bookmarks/index.php?id='.$course->id, 'type' => 'activity');
	$navlinks[] = array('name' => format_string($bookmarks->name), 'link' => "view.php?id=$cm->id", 'type' => 'activityinstance');
	$navlinks[] = array('name' => get_string('delete'));
	    
	$navigation = build_navigation($navlinks);
	    
	print_header_simple(format_string($bookmarks->name), "",
		$navigation, "", "", true, update_module_button($cm->id, $course->id, get_string("modulename", "bookmarks")), navmenu($course, $cm));

/// Print the main part of the page

    $context = get_context_instance(CONTEXT_MODULE,$cm->id);
	
	require_capability('mod/bookmarks:deleteitem',$context);
	
	if (isset($itemid)){
		$item = bookmarks_get_item($itemid);

		if ($USER->id == $item->userid && $item->bookmarksid == $bookmarks->id){
			bookmarks_delete_item($item);
		}
		else {
			notice ("Error ocurred");			
		}	
	} else{
		notice("Item not specified");
	}

	redirect("view.php?id=$id", get_string('deleting', 'bookmarks'), 3);

	
/// Finish the page
    print_footer($course);
?>

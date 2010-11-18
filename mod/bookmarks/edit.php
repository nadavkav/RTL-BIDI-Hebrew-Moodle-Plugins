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

    add_to_log($course->id, "bookmarks", "edit", "edit.php?id=$cm->id", "$bookmarks->id", $cm->id);

/// Print header.
	$navlinks = array();
	$navlinks[] = array('name' => get_string('modulenameplural','bookmarks'), 'link' => $CFG->wwwroot.'/mod/bookmarks/index.php?id='.$course->id, 'type' => 'activity');
	$navlinks[] = array('name' => format_string($bookmarks->name), 'link' => "view.php?id=$cm->id", 'type' => 'activityinstance');
	$navlinks[] = array('name' => get_string('edit'));
	    
	$navigation = build_navigation($navlinks);
	    
	print_header_simple(format_string($bookmarks->name), "",
		$navigation, "", "", true, update_module_button($cm->id, $course->id, get_string("modulename", "bookmarks")), navmenu($course, $cm));
/// Print the main part of the page

    $context = get_context_instance(CONTEXT_MODULE,$cm->id);
	
	require_capability('mod/bookmarks:additem',$context);
	echo '<div class="middle">';	
	require_js($CFG->wwwroot.'/mod/bookmarks/tags.js');
	if (isset($itemid)){
		require_once ("update_form.php");
		$form = & new mod_bookmarks_update_form("save.php?id=$id");


		$item = bookmarks_get_item($itemid);
		if ($USER->id == $item->userid){
			$data = new stdClass();
			$data->cmid = $cm->id;
			$data->itemid = $item->id;
			$data->name = $item->name;
			$data->description = $item->description;
			$link = bookmarks_get_link($item->linkid);
			$data->url = $link->url;
			$tags = bookmarks_get_item_tags($item->id);
			if (!empty($tags)){
				foreach ($tags as $tag){
					$data->tags .= $tag->name.',';
				}		
			}
			
			$form->set_data($data);
			$form->display();
		} else {
			notice (get_string('notyours','bookmarks'));
		}
	} else {

		require_once ("edit_form.php");
	
		$form = & new mod_bookmarks_edit_form("save.php?id=$id");
		$form->display();

	}
	echo '</div>';
	bookmarks_print_tagcloud_block($bookmarks->id, 'edit');


	
/// Finish the page
	echo '<div class="footer">';
    print_footer($course);
	echo '</div>';
?>

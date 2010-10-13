<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 exabis internet solutions <info@exabis.at>
*  All rights reserved
*
*  You can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This module is based on the Collaborative Moodle Modules from
*  NCSA Education Division (http://www.ncsa.uiuc.edu)
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once dirname(__FILE__).'/inc.php';

$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param("action", "", PARAM_ALPHA);
$confirm = optional_param("confirm", "", PARAM_BOOL);
$backtype = optional_param('backtype', 'all', PARAM_ALPHA);

$backtype = block_exabis_eportfolio_check_item_type($backtype, true);

if (!confirm_sesskey()) {
	print_error("badsessionkey","block_exabis_eportfolio");    	
}


$context = get_context_instance(CONTEXT_SYSTEM);

require_login($courseid);
require_capability('block/exabis_eportfolio:use', $context);

if (! $course = get_record("course", "id", $courseid) ) {
   print_error("invalidcourseid","block_exabis_eportfolio");
}

if(!block_exabis_eportfolio_has_categories($USER->id)) {
	print_error("nocategories", "block_exabis_eportfolio", "view.php?courseid=" . $courseid);
}


$id = optional_param('id', 0, PARAM_INT);
if ($id) {
	if (!$existing = get_record('block_exabeporitem', 'id', $id, 'userid', $USER->id)) {
		print_error("wrong".$type."id", "block_exabis_eportfolio");
	}
} else {
	$existing  = false;
}


// read + check type
if ($existing)
	$type = $existing->type;
else
{
	$type = optional_param('type', 'all', PARAM_ALPHA);
	$type = block_exabis_eportfolio_check_item_type($type, false);
	if (!$type) {
		print_error("badtype", "block_exabis_eportfolio");    	
	}
}


$returnurl = $CFG->wwwroot.'/blocks/exabis_eportfolio/view_items.php?courseid='.$courseid."&type=".$backtype;

// delete item
if ($action == 'delete') {
	if (!$existing) {
		print_error("bookmarknotfound", "block_exabis_eportfolio");        
	}
	if (data_submitted() && $confirm && confirm_sesskey()) {
		block_exabis_eportfolio_do_delete($existing, $returnurl, $courseid);
		redirect($returnurl);
	} else {
		$optionsyes = array('id'=>$id, 'action'=>'delete', 'confirm'=>1, 'backtype'=>$backtype, 'sesskey'=>sesskey(), 'courseid'=>$courseid);
		$optionsno = array('userid'=>$existing->userid, 'courseid'=>$courseid, 'type'=>$backtype);

		block_exabis_eportfolio_print_header("bookmarks".block_exabis_eportfolio_get_plural_item_type($backtype), $action);
		// ev. noch eintrag anzeigen!!!
		//blog_print_entry($existing);
		echo '<br />';
		notice_yesno(get_string("delete".$type."confirm", "block_exabis_eportfolio"), 'item.php', 'view_items.php', $optionsyes, $optionsno, 'post', 'get');
		print_footer();
		die;
	}
}

if (in_array($action, array('moveup', 'movetop', 'movedown', 'movebottom'))) {

	if (!$existing) {
		print_error("bookmarknotfound", "block_exabis_eportfolio");        
	}

	// check ordering
	$query = "select i.id, i.type, i.sortorder".
		 " from {$CFG->prefix}block_exabeporitem i".
		 " where i.userid = $USER->id ORDER BY IF(sortorder>0,sortorder,99999)";

	$items = get_records_sql($query);

	// fix sort order if needed
	$i = 0;
	foreach ($items as $item) {
		$i++;
		if ($item->sortorder != $i) {
			$r = new object();
			$r->id = $item->id;
			$r->sortorder = $i;
			update_record('block_exabeporitem', $r);

			$item->sortorder = $i;
		}

		if ($item->id == $existing->id) {
			$existing->sortorder = $item->sortorder;
		}
	}

	
	$sort_to_item = false;

	if (in_array($action, array('movetop', 'movebottom'))) {
		if ($action == 'movebottom')
			$sort_to_item = end($items);
		else
			$sort_to_item = reset($items);
	} else {
		// on moving down search array backwards
		if ($action == 'movedown')
			$items = array_reverse($items);

		foreach ($items as $item) {
			if ($item->id == $existing->id)
				break;

			if (($backtype != $existing->type) || ($item->type == $existing->type))
				$sort_to_item = $item;
		}
	}

	if (!$sort_to_item) {
		print_error("bookmarknotfound", "block_exabis_eportfolio");        
	}


	if ($sort_to_item->sortorder > $existing->sortorder)
		$change_sort_others = -1;
	else
		$change_sort_others = 1;

	// update sorting other items that are between the 2
	$query = "update {$CFG->prefix}block_exabeporitem i set sortorder=sortorder+".$change_sort_others.
		 " where i.userid = $USER->id AND sortorder >= ".min($sort_to_item->sortorder, $existing->sortorder)." AND sortorder <= ".max($sort_to_item->sortorder, $existing->sortorder);
	execute_sql($query);

	// update sortorder of moved item
	$r = new object();
	$r->id = $existing->id;
	$r->sortorder = $sort_to_item->sortorder;
	update_record('block_exabeporitem', $r);

	redirect($returnurl);
	exit;
}


require_once("{$CFG->dirroot}/blocks/exabis_eportfolio/lib/item_edit_form.php");

$editform = new block_exabis_eportfolio_item_edit_form($_SERVER['REQUEST_URI'].'&type='.$type, Array('existing' => $existing, 'course' => $course, 'type' => $type, 'action'=> $action));

if ($editform->is_cancelled()){
	redirect($returnurl);
} else if ($editform->no_submit_button_pressed()) {
	die("nosubmitbutton");
	//no_submit_button_actions($editform, $sitecontext);
} else if ($fromform = $editform->get_data()){
	switch ($action) {
		case 'add':
			$fromform->type = $type;
			block_exabis_eportfolio_do_add($fromform, $editform, $returnurl, $courseid);
		break;

		case 'edit':
			if (!$existing) {
				print_error("bookmarknotfound", "block_exabis_eportfolio");	                
			}

			block_exabis_eportfolio_do_edit($fromform, $editform, $returnurl, $courseid);
		break;
		
		default:
			print_error("unknownaction", "block_exabis_eportfolio");	                	            
	}
	
	redirect($returnurl);
}

$strAction = "";
$extra_content = '';
// gui setup
$post = new stdClass();
switch ($action) {
	case 'add':
		$post->action       = $action;
		$post->courseid     = $courseid;
		
		$strAction = get_string('new');
		
		break;
	case 'edit':
		if (!$existing) {
			print_error("bookmarknotfound", "block_exabis_eportfolio");
		}
		$post->id           = $existing->id;
		$post->name         = $existing->name;
		$post->categoryid   = $existing->categoryid;
		$post->intro        = $existing->intro;
		$post->userid       = $existing->userid;
		$post->action       = $action;
		$post->courseid     = $courseid;
		$post->type         = $existing->type;

		$strAction = get_string('edit');

		if ($type == 'link') {
			$post->url = $existing->url;
		}
		elseif ($type == 'file') {
			$post->attachment   = $existing->attachment;

			$filearea = block_exabis_eportfolio_file_area_name($post);
			$ffurl = '';
			if ($CFG->slasharguments) {
				$ffurl = "{$CFG->wwwroot}/blocks/exabis_eportfolio/portfoliofile.php/$filearea/$post->attachment";
			} else {
				$ffurl = "{$CFG->wwwroot}/blocks/exabis_eportfolio/portfoliofile.php?file=/$filearea/$post->attachment";
			}

			$extra_content = "<div class='block_eportfolio_center'>\n";
			$extra_content .= print_box(block_exabis_eportfolio_print_file($ffurl, $post->attachment, $post->name), 'generalbox', '', true);
			$extra_content .= "</div>";
		}

		break;
	default :
		print_error("unknownaction", "block_exabis_eportfolio");	                	            
}


block_exabis_eportfolio_print_header("bookmarks".block_exabis_eportfolio_get_plural_item_type($backtype), $action);

$editform->set_data($post);
echo $extra_content;
$editform->display();

print_footer($course);



/**
 * Update item in the database
 */
function block_exabis_eportfolio_do_edit($post, $blogeditform,$returnurl, $courseid) {
    global $CFG, $USER;

    $post->timemodified = time();

    if (update_record('block_exabeporitem', $post)) {
        add_to_log(SITEID, 'bookmark', 'update', 'item.php?courseid='.$courseid.'&id='.$post->id.'&action=edit', $post->name);
    } else {
		print_error('updateposterror', 'block_exabis_eportfolio', $returnurl);
    }
}

/**
 * Write a new item into database
 */
function block_exabis_eportfolio_do_add($post, $blogeditform, $returnurl, $courseid) {
    global $CFG, $USER;

    $post->userid       = $USER->id;
    $post->timemodified = time();
    $post->courseid = $courseid;

    // Insert the new blog entry.
    if ($post->id = insert_record('block_exabeporitem', $post)) {
		if ($post->type == 'file') {
			$dir = block_exabis_eportfolio_file_area_name($post);
			if ($blogeditform->save_files($dir) && ($newfilename = $blogeditform->get_new_filename())) {
				set_field("block_exabeporitem", "attachment", $newfilename, "id", $post->id);
			}
		}

		add_to_log(SITEID, 'bookmark', 'add', 'item.php?courseid='.$courseid.'&id='.$post->id.'&action=add', $post->name);
    } else {
		print_error('addposterror', 'block_exabis_eportfolio', $returnurl);
    }

}

/**
 * Delete item from database
 */
function block_exabis_eportfolio_do_delete($post,$returnurl, $courseid) {

	// falls file mit dem item verknüpft ist, löschen
	block_exabis_eportfolio_file_remove($post);
	
	$status = delete_records('block_exabeporitem', 'id', $post->id);
    
    add_to_log(SITEID, 'blog', 'delete', 'item.php?courseid='.$courseid.'&id='.$post->id.'&action=delete&confirm=1', $post->name);

    if (!$status) {
		print_error('deleteposterror', 'block_exabis_eportfolio', $returnurl);
    }
}

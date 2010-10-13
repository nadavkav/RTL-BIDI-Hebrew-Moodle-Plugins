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
require_once dirname(__FILE__).'/lib/sharelib.php';
require_once dirname(__FILE__).'/lib/externlib.php';

$access = optional_param('access', 0, PARAM_TEXT);
$itemid = optional_param('itemid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$commentid = optional_param('commentid', 0, PARAM_INT);
$deletecomment = optional_param('deletecomment', 0, PARAM_INT);
$backtype = optional_param('backtype', 0, PARAM_TEXT);

require_login(0, true);

$item = block_exabis_eportfolio_get_item($itemid, $access);
if (!$item) {
	print_error("bookmarknotfound", "block_exabis_eportfolio");
}

if (!$user = get_record("user", "id", $item->userid)) {
	print_error("nouserforid", "block_exabis_eportfolio");
}

if ($item->access->page == 'view') {
	if ($item->access->request == 'intern') {
		block_exabis_eportfolio_print_header("views");
	} else {
		print_header(get_string("externaccess", "block_exabis_eportfolio"), get_string("externaccess", "block_exabis_eportfolio") . " " . fullname($user, $user->id));
	}
} elseif ($item->access->page == 'portfolio') {
	if ($item->access->request == 'intern') {
		if ($backtype && ($item->userid == $USER->id)) {
			block_exabis_eportfolio_print_header("bookmarks".block_exabis_eportfolio_get_plural_item_type($backtype));
		} else {
			block_exabis_eportfolio_print_header("sharedbookmarks");
		}
	} else {
		print_header(get_string("externaccess", "block_exabis_eportfolio"), get_string("externaccess", "block_exabis_eportfolio") . " " . fullname($user, $user->id));
	}
}

echo "<div class='block_eportfolio_center'>\n";

block_exabis_eportfolio_print_extern_item($item, $access);

if ($item->allowComments) {
	if($deletecomment == 1) {
		if (!confirm_sesskey()) {
			print_error("badsessionkey","block_exabis_eportfolio");	                
		}
		if(count_records("block_exabeporitemcomm", "id", $commentid, "userid", $USER->id, "itemid", $itemid) == 1) {
			delete_records("block_exabeporitemcomm", "id", $commentid, "userid", $USER->id, "itemid", $itemid);

			parse_str($_SERVER['QUERY_STRING'], $params);
			redirect($_SERVER['PHP_SELF'].'?'.http_build_query(array('deletecomment'=>null, 'commentid'=>null, 'sesskey'=>null)+(array)$params));
		}
		else {
			print_error("commentnotfound","block_exabis_eportfolio");	    	
			redirect($_SERVER['REQUEST_URI']);
		}
	}

	require_once("{$CFG->dirroot}/blocks/exabis_eportfolio/lib/item_edit_form.php");
	$commentseditform = new block_exabis_eportfolio_comment_edit_form();


	if ($commentseditform->is_cancelled());
	else if ($commentseditform->no_submit_button_pressed());
	else if ($fromform = $commentseditform->get_data()){
		switch ($action) {
			case 'add':
				block_exabis_eportfolio_do_add_comment($item, $fromform, $commentseditform);
				redirect($_SERVER['REQUEST_URI']);
			break;
		}
	}
	$newcomment = new stdClass();
	$newcomment->action = 'add';
	$newcomment->courseid = $COURSE->id;
	$newcomment->timemodified = time();
	$newcomment->itemid = $itemid;
	$newcomment->userid = $USER->id;
	$newcomment->access = $access;
	$newcomment->backtype = $backtype;

	block_exabis_eportfolio_show_comments($item);

	$commentseditform->set_data($newcomment);
	$commentseditform->_form->_attributes['action'] = $_SERVER['REQUEST_URI'];
	$commentseditform->display();


} elseif ($item->showComments) {
	block_exabis_eportfolio_print_extcomments($item->id);
}

if ($item->access->page == 'view') {
	$backlink = 'shared_view.php?access='.$item->access->parentAccess;
} else {
	// intern
	if ($item->userid == $USER->id) {
		$backlink = '';
	}
	$backlink = '';
	// extern.php?id=$id
}
if ($backlink) {
	echo "<br /><a href=\"{$CFG->wwwroot}/blocks/exabis_eportfolio/".$backlink."\">".get_string("back","block_exabis_eportfolio")."</a><br /><br />";
}

echo "</div>";
print_footer();

function block_exabis_eportfolio_show_comments($item) {
	global $CFG, $USER, $COURSE;

	$comments = get_records("block_exabeporitemcomm", "itemid", $item->id, 'timemodified DESC');
	
	if($comments) {
		foreach ($comments as $comment) {
			$stredit = get_string('edit');
			$strdelete = get_string('delete');

			$user = get_record('user', 'id', $comment->userid);

			echo '<table cellspacing="0" class="forumpost blogpost blog" width="100%">';

			echo '<tr class="header"><td class="picture left">';
			print_user_picture($comment->userid, SITEID, $user->picture);
			echo '</td>';

			echo '<td class="topic starter"><div class="author">';
			$fullname = fullname($user, $comment->userid);
			$by = new object();
			$by->name =  '<a href="'.$CFG->wwwroot.'/user/view.php?id='.
						$user->id.'&amp;course='.$COURSE->id.'">'.$fullname.'</a>';
			$by->date = userdate($comment->timemodified);
			print_string('bynameondate', 'forum', $by);

			if ($comment->userid == $USER->id) {
				echo ' - <a href="'.$_SERVER['REQUEST_URI'].'&commentid='.$comment->id.'&deletecomment=1&sesskey='.sesskey().'">' . get_string('delete') . '</a>';
			}
			echo '</div></td></tr>';

			echo '<tr><td class="left side">';

			echo '</td><td class="content">'."\n";
			
			echo format_text($comment->entry);
			
			echo '</td></tr></table>'."\n\n";
		}
	}
}
	
function block_exabis_eportfolio_do_add_comment($item, $post, $blogeditform) {
	global $CFG, $USER, $COURSE;

	$post->userid       = $USER->id;
	$post->timemodified = time();
	$post->course = $COURSE->id;

	// Insert the new blog entry.
	if (insert_record('block_exabeporitemcomm', $post)) {
		add_to_log(SITEID, 'exabis_eportfolio', 'add', 'view_item.php?type='.$item->type, $post->entry);
	} else {
		error('There was an error adding this post in the database');
	}
}

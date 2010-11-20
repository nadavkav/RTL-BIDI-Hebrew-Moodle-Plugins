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

function block_exabis_eportfolio_get_user_from_hash($hash)
{
	trigger_error('deprecated');
	if (! $hashrecord = get_record("block_exabeporuser", "user_hash", $hash) )
		return false;
	else
		return get_record("user", "id", $hashrecord->user_id);
}

function block_exabis_eportfolio_print_extern_item($item, $access) {
	global $CFG;

	print_heading(format_string($item->name));

	$box_content = '';

	if ($item->type == 'link') {
		$link = clean_param($item->url, PARAM_URL);
		$link_js = str_replace('http://', '', $link);

		if ($link) {
			$box_content .= '<p><a href="#" onclick="window.open(\'http://' . addslashes_js($link_js) . '\',\'validate\',\'width=620,height=450,scrollbars=yes,status=yes,resizable=yes,menubar=yes,location=yes\');return true;">' . $link . '</a></p>';
		}
	}
	elseif ($item->type == 'file') {
        if ($item->attachment) {
            $type = mimeinfo("type", $item->attachment);

			$ffurl = "{$CFG->wwwroot}/blocks/exabis_eportfolio/portfoliofile.php?access=".$access."&itemid=".$item->id;

            if (in_array($type, array('image/gif', 'image/jpeg', 'image/png'))) {    // Image attachments don't get printed as links
                $box_content .= "<img width=\"100%\" src=\"$ffurl\" alt=\"" . format_string($item->name) . "\" /><br/>";
            } else {
            	$box_content .= "<p>" . link_to_popup_window("$ffurl", 'popup', "$ffurl", $height=400, $width=500, format_string($item->name), 'none', true) . "</p>";
            }
        }
	}

	$box_content .= format_text($item->intro, FORMAT_HTML);

	print_box($box_content);
}


function block_exabis_eportfolio_print_extcomments($itemid) {
	$stredit = get_string('edit');
	$strdelete = get_string('delete');

	$comments = get_records("block_exabeporitemcomm", "itemid", $itemid, 'timemodified DESC');
	if(!$comments)
		return;

	foreach ($comments as $comment) {
		$user = get_record('user','id',$comment->userid);

		echo '<table cellspacing="0" class="forumpost blogpost blog" width="100%">';

		echo '<tr class="header"><td class="picture left">';
		print_user_picture($comment->userid, SITEID, $user->picture);
		echo '</td>';

		echo '<td class="topic starter"><div class="author">';
		$fullname = fullname($user, $comment->userid);
		$by = new object();
		$by->name = $fullname;
		$by->date = userdate($comment->timemodified);
		print_string('bynameondate', 'forum', $by);

		echo '</div></td></tr>';

		echo '<tr><td class="left side">';

		echo '</td><td class="content">'."\n";

		echo format_text($comment->entry);

		echo '</td></tr></table>'."\n\n";
	}
}

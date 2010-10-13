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

$access = optional_param('access', 0, PARAM_TEXT);

require_login(0, true);

if (!$view = block_exabis_eportfolio_get_view_from_access($access)) {
	print_error("viewnotfound", "block_exabis_eportfolio");
}

if (!$user = get_record("user", "id", $view->userid)) {
	print_error("nouserforid", "block_exabis_eportfolio");
}

$portfolioUser = get_record("block_exabeporuser", "user_id", $view->userid);

// read blocks
$query = "select b.*". // , i.*, i.id as itemid".
	 " FROM {$CFG->prefix}block_exabeporviewblock b".
	 // " LEFT JOIN {$CFG->prefix}block_exabeporitem i ON b.type='item' AND b.itemid=i.id".
	 " WHERE b.viewid = ".$view->id." ORDER BY b.positionx, b.positiony";

$blocks = get_records_sql($query);

// read columns
$columns = array();
foreach ($blocks as $block) {
	if (!isset($columns[$block->positionx]))
		$columns[$block->positionx] = array();

	if ($block->type == 'item') {
		if ($item = get_record("block_exabeporitem", "id", $block->itemid)) {
			$block->item = $item;
		} else {
			$block->type = 'text';
		}
	}
	$columns[$block->positionx][] = $block;
}




$CFG->stylesheets[] = dirname($_SERVER['PHP_SELF']).'/css/shared_view.css';

if ($view->access->request == 'intern') {
	block_exabis_eportfolio_print_header("views");
} else {
	print_header(get_string("externaccess", "block_exabis_eportfolio"), get_string("externaccess", "block_exabis_eportfolio") . " " . fullname($user, $user->id));
}



echo '<div id="view">';
echo '<table width="100%"><tr>';
$column_num = 0;
for ($column_i = 1; $column_i<=2; $column_i++) {
	if (!isset($columns[$column_i]))
		continue;
	$column_num++;

	echo '<td class="view-column view-column-'.$column_num.'" width="'.floor(100/count($columns)).'%" valign="top">';
	foreach ($columns[$column_i] as $block) {
		if ($block->type == 'item') {
			$item = $block->item;

			echo '<a class="view-item view-item-type-'.$item->type.'" href="shared_item.php?access=view/'.$access.'&itemid='.$item->id.'">';
			echo '<span class="view-item-header" title="'.$item->type.'">'.$item->name.'</span>';
			echo '<span class="view-item-text">'.$item->intro.'</span>';
			echo '<span class="view-item-link">'.block_exabis_eportfolio_get_string('show').'</span>';
			echo '</a>';
		} elseif ($block->type == 'personal_information') {
			echo '<div class="view-personal-information">'.$portfolioUser->description.'</div>';
		} elseif ($block->type == 'headline') {
			echo '<div class="header view-header">'.nl2br($block->text).'</div>';
		} else {
			// text
			echo '<div class="view-text">';
			echo $block->text;
			echo '</div>';
		}
	}
	echo '</td>';
}
echo '</tr></table>';
echo '</div>';

echo "<pre>";
/*
print_r($columns);
echo '<table cellspacing="0" class="forumpost blogpost blog" width="100%">';

echo '<tr class="header"><td class="picture left">';
print_user_picture($user->id, 0, $user->picture);
echo '</td>';

echo '<td class="topic starter"><div class="author">';
$by =  fullname($user, $user->id);
print_string('byname', 'moodle', $by);
echo '</div></td></tr>';

echo '<tr><td class="left side">';

echo '</td><td class="content">'."\n";

echo format_text($userpreferences->description, FORMAT_HTML);
echo '</td></tr></table>'."\n\n";
*/


echo "<br />";

echo "<div class='block_eportfolio_center'>\n";

echo "</div>\n";

print_footer();

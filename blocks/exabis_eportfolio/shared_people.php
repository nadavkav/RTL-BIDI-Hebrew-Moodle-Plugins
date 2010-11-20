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

$context = get_context_instance(CONTEXT_SYSTEM);

require_login($courseid);
require_capability('block/exabis_eportfolio:use', $context);

if (! $course = get_record("course", "id", $courseid) ) {
	error("That's an invalid course id");
}

block_exabis_eportfolio_print_header("sharedbookmarks");

/*
$strheader = get_string("sharedpersons", "block_exabis_eportfolio");

$navlinks = array();
$navlinks[] = array('name' => $strheader, 'link' => null, 'type' => 'misc');

$navigation = build_navigation($navlinks);
print_header_simple($strheader, '', $navigation, "", "", true);
*/


echo "<div class='block_eportfolio_center'>\n";

echo "<br />";

print_simple_box( text_to_html(get_string("explainingshared", "block_exabis_eportfolio")) , "center");

echo "<br />";

if (block_exabis_eportfolio_get_active_version() >= 3) {
	$all_shared_users = get_records_sql(
	"SELECT u.id, u.picture, u.firstname, u.lastname, COUNT(v.id) AS detail_count FROM {$CFG->prefix}user AS u".
	" JOIN {$CFG->prefix}block_exabeporview v ON u.id=v.userid".
	" LEFT JOIN {$CFG->prefix}block_exabeporviewshar vshar ON v.id=vshar.viewid AND vshar.userid={$USER->id}".
	" WHERE (v.shareall=1 OR vshar.userid IS NOT NULL)".
	" GROUP BY u.id");

	$detailLink = 'shared_views.php';
} else {
	$all_shared_users = get_records_sql(
	"SELECT u.id, u.picture, u.firstname, u.lastname, COUNT(i.id) AS detail_count FROM {$CFG->prefix}user AS u".
	" JOIN {$CFG->prefix}block_exabeporitem i ON u.id=i.userid".
	" LEFT JOIN {$CFG->prefix}block_exabeporitemshar ishar ON i.id=ishar.itemid AND ishar.userid={$USER->id}".
	" WHERE ((i.shareall=1 AND ishar.userid IS NULL) OR (i.shareall=0 AND ishar.userid IS NOT NULL))".
	" GROUP BY u.id");

	$detailLink = 'shared_portfolio.php';
}

/*
$all_shared_records = get_records_sql(
"SELECT i.id, i.userid, i.name, u.picture, u.firstname, u.lastname FROM {$CFG->prefix}block_exabeporitem i".
" JOIN {$CFG->prefix}user u ON u.id=i.userid".
" JOIN {$CFG->prefix}block_exabeporcate cat ON i.categoryid=cat.id".
" LEFT JOIN {$CFG->prefix}block_exabeporitemshar ishar ON i.id=ishar.itemid AND ishar.userid={$USER->id}".
" WHERE ((i.shareall=1 AND ishar.userid IS NULL) OR (i.shareall=0 AND ishar.userid IS NOT NULL))");
*/

echo "<pre style='width: 400px; text-align: left;'>";

if (is_array($all_shared_users)){
	echo "<table>";
	foreach($all_shared_users as $user) {
		echo "<tr>";
		echo "<td><a href=\"{$CFG->wwwroot}/blocks/exabis_eportfolio/".$detailLink."?courseid=$courseid&access=id/$user->id\">";

		print_user_picture($user->id, $courseid, $user->picture, 0, false, false);
		echo "</a>&nbsp;</td>";
		echo "<td>&nbsp;<a href=\"{$CFG->wwwroot}/blocks/exabis_eportfolio/".$detailLink."?courseid=$courseid&access=id/$user->id\">".fullname($user, $user->id)."</a></td>";
		echo '<td style="padding-left: 30px;">&nbsp;'.get_string('bookmarks', 'block_exabis_eportfolio').': '.$user->detail_count."</td>";

		echo "</tr>";
	}
	echo "</table>";
}


echo "</div>";
print_footer($course);

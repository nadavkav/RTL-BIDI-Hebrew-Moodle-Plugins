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

$courseid = required_param('courseid', PARAM_INT);
$sort = optional_param('sort', '', PARAM_RAW);
$access = optional_param('access', 0, PARAM_TEXT);

require_login($courseid);

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/exabis_eportfolio:use', $context);

if (! $course = get_record("course", "id", $courseid) ) {
	error("That's an invalid course id");
}

if (!$user = block_exabis_eportfolio_get_user_from_access($access)) {
	print_error("nouserforid", "block_exabis_eportfolio");
}

$parsedsort = block_exabis_eportfolio_parse_view_sort($sort);
$sql_sort = block_exabis_eportfolio_view_sort_to_sql($parsedsort);

$sort = $parsedsort[0].'.'.$parsedsort[1];

$sortkey = $parsedsort[0];

if ($parsedsort[1] == "desc") {
	$newsort = $sortkey.".asc";
} else {
	$newsort = $sortkey.".desc";
}
$sorticon = $parsedsort[1].'.gif';





block_exabis_eportfolio_print_header("sharedbookmarks");

$strheader = get_string("sharedbookmarks", "block_exabis_eportfolio");

/*
$navlinks = array();
$navlinks[] = array('name' => $strheader, 'link' => null, 'type' => 'misc');

$navigation = build_navigation($navlinks);
print_header_simple($strheader, '', $navigation, "", "", true);
*/

print_heading($strheader.": " . fullname($user, $user->id)) ;

echo "<div class='block_eportfolio_center'>\n";


$views = get_records_sql(
"SELECT v.*".
" FROM {$CFG->prefix}block_exabeporview v".
" LEFT JOIN {$CFG->prefix}block_exabeporviewshar vshar ON v.id=vshar.viewid AND vshar.userid={$USER->id}".
" WHERE (v.shareall=1 OR vshar.userid IS NOT NULL)".
" AND v.userid='{$user->id}'".
" $sql_sort");

if (!$views) {
	print_error("nouserforid", "block_exabis_eportfolio");
}

// print_simple_box(text_to_html(get_string("explainingviews", "block_exabis_eportfolio")) , "center");

echo "<br />";

$table = new stdClass();
$table->width = "100%";

$table->head = array();
$table->size = array();

// $table->align = array("CENTER","LEFT", "CENTER");

$table->size = array("20%", "47%", "33%");

$table->head['name'] = "<a href='{$CFG->wwwroot}/blocks/exabis_eportfolio/shared_views.php?access=$access&amp;courseid=$courseid&amp;sort=".
					($sortkey == 'name' ? $newsort : 'name') ."'>" . get_string("name", "block_exabis_eportfolio") . "</a>";
$table->size['name'] = "50%";

$table->head['timemodified'] = "<a href='{$CFG->wwwroot}/blocks/exabis_eportfolio/shared_views.php?access=$access&amp;courseid=$courseid&amp;sort=".
					($sortkey == 'timemodified' ? $newsort : 'timemodified.desc') ."'>" . get_string("date", "block_exabis_eportfolio") . "</a>";
$table->size['timemodified'] = "30%";

// add arrow to heading if available 
if (isset($table->head[$sortkey]))
	$table->head[$sortkey] .= "<img src=\"pix/$sorticon\" alt='".get_string("updownarrow", "block_exabis_eportfolio")."' />";


if ($views) {
	$lastcat = "";
	foreach ($views as $view) {

		$name = "<a href=\"{$CFG->wwwroot}/blocks/exabis_eportfolio/shared_view.php?courseid=$courseid&amp;access={$access}-{$view->id}\">" . format_string($view->name) . "</a>";

		/*
		if ($view->intro) {
			$view->intro = "<div class='block_eportfolio_italic'>" . format_text($item->intro, FORMAT_PLAIN) . "</div>";
			$name .= "<br /><table width=\"98%\"><tr><td>".format_text($item->intro, FORMAT_HTML)."</td></tr></table>";
		}
		*/

		$date = userdate($view->timemodified);
		$icons = "";
		$table->data[] = array($name, $date, $icons);
	}
	print_table($table);
} else {
	echo get_string("nobookmarksexternal", "block_exabis_eportfolio")."<br /><br />";
}



echo "<br /><br />";

echo "</div>";

print_footer($course);

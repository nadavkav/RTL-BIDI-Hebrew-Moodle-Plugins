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
$sort = optional_param('sort', '', PARAM_RAW);

$type = optional_param('type', 'all', PARAM_ALPHA);
$type = block_exabis_eportfolio_check_item_type($type, true);

// Needed for Translations
$type_plural = block_exabis_eportfolio_get_plural_item_type($type);


$strbookmarks = get_string("mybookmarks", "block_exabis_eportfolio");
$strheadline = get_string("bookmarks".$type_plural, "block_exabis_eportfolio");

$context = get_context_instance(CONTEXT_SYSTEM);

require_login($courseid);
require_capability('block/exabis_eportfolio:use', $context);

if (! $course = get_record("course", "id", $courseid) ) {
	error("That's an invalid course id");
}

block_exabis_eportfolio_print_header("bookmarks".$type_plural);

if (isset($USER->realuser)) {
	error("You can't access portfolios in 'Login As'-Mode.");
}

echo "<div class='block_eportfolio_center'>";
print_simple_box( text_to_html(get_string("explaining".$type,"block_exabis_eportfolio")) , "center");
echo "</div>";

$availablecategories = get_categories();
echo '<table><tr>';
echo '<td>'.get_string('filterbycategory','block_exabis_eportfolio').'</td><td><form id="frmfiltercategory" action="view_items.php" method="get">';
choose_from_menu($availablecategories,'filtercategory','',get_string('choose'),'document.getElementById(\'frmfiltercategory\').submit();');
echo '<input type="hidden" name="courseid" value="'.$courseid.'">';
//echo '<input type="submit" value="סינון">';
echo '</form></td>';
echo '<td><form id="frmfiltercategory" action="view_items.php" method="get">  ';
echo get_string('or','block_exabis_eportfolio');
echo '<input type="hidden" name="courseid" value="'.$courseid.'">';
echo '<input type="submit" value="'.get_string('allcategories','block_exabis_eportfolio').'">';
echo '</form></td>';
echo '</tr></table>';

$userpreferences = block_exabis_eportfolio_get_user_preferences();

if (!$sort && $userpreferences && isset($userpreferences->itemsort)) {
	$sort = $userpreferences->itemsort;
}

// check sorting
$parsedsort = block_exabis_eportfolio_parse_item_sort($sort);
$sort = $parsedsort[0].'.'.$parsedsort[1];

$sortkey = $parsedsort[0];

if ($parsedsort[1] == "desc") {
	$newsort = $sortkey.".asc";
} else {
	$newsort = $sortkey.".desc";
}
$sorticon = $parsedsort[1].'.gif';


block_exabis_eportfolio_set_user_preferences(array('itemsort'=>$sort));


$sql_sort = block_exabis_eportfolio_item_sort_to_sql($parsedsort);

if ($type == 'all') {
	$sql_type_where = '';
} else {
	$sql_type_where = " AND i.type='".$type."'";
}

if ($_GET['filtercategory']) {
  $sql_type_where .= ' AND i.categoryid = '.$_GET['filtercategory'].' ';
}

$query = "select i.*, ic.name AS cname, ic2.name AS cname_parent, c.fullname As coursename, COUNT(com.id) As comments".
	 " from {$CFG->prefix}block_exabeporitem i".
	 " join {$CFG->prefix}block_exabeporcate ic on i.categoryid = ic.id".
	 " left join {$CFG->prefix}block_exabeporcate ic2 on ic.pid = ic2.id".
	 " left join {$CFG->prefix}course c on i.courseid = c.id".
	 " left join {$CFG->prefix}block_exabeporitemcomm com on com.itemid = i.id".
	 " where i.userid = $USER->id $sql_type_where group by i.id, i.name, i.intro, i.timemodified, cname, cname_parent, coursename $sql_sort";

$items = get_records_sql($query);

if ($items) {
	
	$table = new stdClass();
	$table->width = "100%";

	$table->head = array();
	$table->size = array();

	$table->head['category'] = "<a href='{$CFG->wwwroot}/blocks/exabis_eportfolio/view_items.php?courseid=$courseid&amp;type=$type&amp;sort=".
						($sortkey == 'category' ? $newsort : 'category' ) ."'>" . get_string("category", "block_exabis_eportfolio") . "</a>";
	$table->size['category'] = "14";

	if ($type == 'all') {
		$table->head['type'] = "<a href='{$CFG->wwwroot}/blocks/exabis_eportfolio/view_items.php?courseid=$courseid&amp;type=$type&amp;sort=".
						($sortkey == 'type' ? $newsort : 'type') ."'>" . get_string("type", "block_exabis_eportfolio") . "</a>";
		$table->size['type'] = "14";
	}

	$table->head['name'] = "<a href='{$CFG->wwwroot}/blocks/exabis_eportfolio/view_items.php?courseid=$courseid&amp;type=$type&amp;sort=".
						($sortkey == 'name' ? $newsort : 'name') ."'>" . get_string("name", "block_exabis_eportfolio") . "</a>";
	$table->size['name'] = "30";

	$table->head['date'] = "<a href='{$CFG->wwwroot}/blocks/exabis_eportfolio/view_items.php?courseid=$courseid&amp;type=$type&amp;sort=".
						($sortkey == 'date' ? $newsort : 'date.desc') ."'>" . get_string("date", "block_exabis_eportfolio") . "</a>";
	$table->size['date'] = "20";

	$table->head[] = get_string("course","block_exabis_eportfolio");
	$table->size[] = "14";

	$table->head[] = get_string("comments","block_exabis_eportfolio");
	$table->size[] = "8";

	$table->head[] = '';
	$table->size[] = "10";

	// add arrow to heading if available 
	if (isset($table->head[$sortkey]))
		$table->head[$sortkey] .= "<img src=\"pix/$sorticon\" alt='".get_string("updownarrow", "block_exabis_eportfolio")."' />";


	$table->data = Array();
	$lastcat = "";

	$item_i = -1;
	$itemscnt = count($items);
	foreach ($items as $item) {
		$item_i++;

		$table->data[$item_i] = array();

		// set category
		if(is_null($item->cname_parent)) {
			$category = format_string($item->cname);
		}
		else {
      $arrow = right_to_left() ? " &lArr; " : " &rArr; "; // nadavkav patch rtl
			$category = format_string($item->cname_parent) . $arrow . format_string($item->cname);
		}
		if (($sortkey == "category") && ($lastcat == $category)) {
			$category = "";
		} else {
			$lastcat = $category;
		}
		$table->data[$item_i]['category'] = $category;

		if ($type == 'all') {
			$table->data[$item_i]['type'] = get_string($item->type, "block_exabis_eportfolio");
		}

		$table->data[$item_i]['name'] = "<a href=\"{$CFG->wwwroot}/blocks/exabis_eportfolio/shared_item.php?courseid=$courseid&access=portfolio/id/".$USER->id."&itemid=$item->id&backtype=".$type."\">" . format_string($item->name) . "</a>";
		if ($item->intro) {
			$table->data[$item_i]['name'] .= "<table width=\"98%\"><tr><td>".format_text($item->intro, FORMAT_HTML)."</td></tr></table>";
		}

		$table->data[$item_i]['date'] = userdate($item->timemodified);
		$table->data[$item_i]['course'] = $item->coursename;
		$table->data[$item_i]['comments'] = $item->comments;

		$icons = '';
		$icons .= '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/item.php?courseid='.$courseid.'&amp;id='.$item->id.'&amp;sesskey='.sesskey().'&amp;action=edit&backtype='.$type.'"><img src="'.$CFG->wwwroot.'/pix/t/edit.gif" class="iconsmall" alt="'.get_string("edit").'" /></a> ';
	
		$icons .= '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/item.php?courseid='.$courseid.'&amp;id='.$item->id.'&amp;sesskey='.sesskey().'&amp;action=delete&amp;confirm=1&backtype='.$type.'"><img src="'.$CFG->wwwroot.'/pix/t/delete.gif" class="iconsmall" alt="" . get_string("delete"). ""/></a> ';

		/*
		if ($parsedsort[0] == 'sortorder') {
			if ($item_i > 0) {
				$icons .= '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/item.php?courseid='.$courseid.'&amp;id='.$item->id.'&amp;sesskey='.sesskey().'&amp;action=movetop&backtype='.$type.'" title="'.get_string("movetop", "block_exabis_eportfolio").'"><img src="pix/movetop.gif" class="iconsmall" alt="'.get_string("movetop", "block_exabis_eportfolio").'"/></a> ';
				$icons .= '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/item.php?courseid='.$courseid.'&amp;id='.$item->id.'&amp;sesskey='.sesskey().'&amp;action=moveup&backtype='.$type.'" title="'.get_string("moveup").'"><img src="'.$CFG->wwwroot.'/pix/t/up.gif" class="iconsmall" alt="'.get_string("moveup").'"/></a> ';
			} else {
				$icons .= '<img src="'.$CFG->wwwroot.'/pix/spacer.gif" class="iconsmall" alt="" /> ';
				$icons .= '<img src="'.$CFG->wwwroot.'/pix/spacer.gif" class="iconsmall" alt="" /> ';
			}

			if ($item_i+1 < $itemscnt) {
				$icons .= '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/item.php?courseid='.$courseid.'&amp;id='.$item->id.'&amp;sesskey='.sesskey().'&amp;action=movedown&backtype='.$type.'" title="'.get_string("movedown").'"><img src="'.$CFG->wwwroot.'/pix/t/down.gif" class="iconsmall" alt="'.get_string("movedown").'"/></a> ';
				$icons .= '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/item.php?courseid='.$courseid.'&amp;id='.$item->id.'&amp;sesskey='.sesskey().'&amp;action=movebottom&backtype='.$type.'" title="'.get_string("movebottom", "block_exabis_eportfolio").'"><img src="pix/movebottom.gif" class="iconsmall" alt="'.get_string("movebottom", "block_exabis_eportfolio").'"/></a> ';
			}
			else {
				$icons .= '<img src="'.$CFG->wwwroot.'/pix/spacer.gif" class="iconsmall" alt="" /> ';
				$icons .= '<img src="'.$CFG->wwwroot.'/pix/spacer.gif" class="iconsmall" alt="" /> ';
			}
		}
		*/

		if (block_exabis_eportfolio_get_active_version() < 3) {
			if (has_capability('block/exabis_eportfolio:shareintern', $context)) {
				if( ($item->shareall == 1) ||
					($item->externaccess == 1) ||
				   (($item->shareall == 0) && (count_records('block_exabeporitemshar', 'itemid', $item->id, 'original', $USER->id) > 0))) {
					$icons .= '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/share_item.php?courseid='.$courseid.'&amp;itemid='.$item->id.'&backtype='.$type.'">'.get_string("strunshare", "block_exabis_eportfolio").'</a> ';
				}
				else {
					$icons .= '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/share_item.php?courseid='.$courseid.'&amp;itemid='.$item->id.'&backtype='.$type.'">'.get_string("strshare", "block_exabis_eportfolio").'</a> ';
				}
			}
		}

		$table->data[$item_i]['icons'] = $icons;
	}

	/*
	if ($parsedsort[0] != 'sortorder')
		echo '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/view_items.php?courseid='.$courseid.'&amp;&type='.$type.'&amp;sort=sortorder">'.get_string("userdefinedsort", "block_exabis_eportfolio").'</a>';
	*/

	print_table($table);
} else {
	echo get_string("nobookmarks".$type,"block_exabis_eportfolio");
}

echo "<div class='block_eportfolio_center'>";

echo "<form action=\"{$CFG->wwwroot}/blocks/exabis_eportfolio/item.php?backtype=$type\" method=\"post\">
		<fieldset>
		  <input type=\"hidden\" name=\"action\" value=\"add\"/>
		  <input type=\"hidden\" name=\"courseid\" value=\"$courseid\"/>
		  <input type=\"hidden\" name=\"sesskey\" value=\"" . sesskey() . "\" />";

if ($type != 'all')
{
	echo '<input type="hidden" name="type" value="'.$type.'" />';
	echo "<input type=\"submit\" value=\"" . get_string("new".$type, "block_exabis_eportfolio"). "\"/>";
}
else
{
	echo '<select name="type">';
	echo '<option value="link">'.get_string("link", "block_exabis_eportfolio")."</option>";
	echo '<option value="file">'.get_string("file", "block_exabis_eportfolio")."</option>";
	echo '<option value="note">'.get_string("note", "block_exabis_eportfolio")."</option>";
	echo '</select>';
	echo "<input type=\"submit\" value=\"" . get_string("new", "block_exabis_eportfolio"). "\"/>";
}

echo "</fieldset>
	  </form>";

echo "</div>";

print_footer($course);

function get_categories() {
  global $USER;

  $arrow = right_to_left() ? " &lArr; " : " &rArr; "; // nadavkav patch rtl

  $outercategories = get_records_select("block_exabeporcate", "userid='$USER->id' AND pid=0", "name asc");
  $categories = array();
  if ( $outercategories ) {
      foreach ( $outercategories as $curcategory ) {
        $categories[$curcategory->id] = format_string($curcategory->name);

    $inner_categories = get_records_select("block_exabeporcate", "userid='$USER->id' AND pid='$curcategory->id'", "name asc");
    if($inner_categories) {
          foreach ( $inner_categories as $inner_curcategory ) {

            $categories[$inner_curcategory->id] = format_string($curcategory->name) . $arrow . format_string($inner_curcategory->name);
          }
    }
      }
  } else {
      $categories[0] = get_string("nocategories","block_exabis_eportfolio");
  }
  return $categories;
}
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
$edit = optional_param('edit', 0, PARAM_INT);

$pid = optional_param('pid', '', PARAM_INT);
$name = optional_param('name', '', PARAM_TEXT);
$cataction = optional_param('cataction', '', PARAM_ALPHA);
$catconfirm = optional_param('catconfirm', 0, PARAM_INT);
$delid = optional_param('delid', 0, PARAM_INT);
$editid = optional_param('editid', 0, PARAM_INT);

$context = get_context_instance(CONTEXT_SYSTEM);

require_login($courseid);
require_capability('block/exabis_eportfolio:use', $context);


if (! $course = get_record("course", "id", $courseid) ) {
	error("That's an invalid course id");
}

block_exabis_eportfolio_print_header("categories");

if (isset($USER->realuser)) {
	error("You can't access portfolios in 'Login As'-Mode.");
}

echo '<div class="block_eportfolio_center">';

echo "<br />";

print_simple_box( text_to_html(get_string("explaincategories","block_exabis_eportfolio")) , "center");

echo '</div>';                               


if($cataction) {
	if($catconfirm) {
		if (!confirm_sesskey()) {
			error('Bad Session Key');
		}
		$newentry = new stdClass();
		$newentry->name = $name;
		$newentry->timemodified = time();
		$newentry->course = $courseid;
		$message = '';
		switch ( $cataction )
		{
			case "add":
				$newentry->userid = $USER->id;
				if($pid > 0)
					$newentry->pid = $pid;
				else
					$newentry->pid = 0;
					
				if (! $newentry->id = insert_record("block_exabeporcate", $newentry))
				{
					error("Could not insert this category");
				}
				else
				{
					add_to_log($courseid, "bookmark", "add category", "", $newentry->id);
					$message = get_string("categorysaved","block_exabis_eportfolio");
				}
			break;
			case "edit":
				if(($editid > 0) && ($editrecord = get_record("block_exabeporcate", "id", $editid, "userid", $USER->id)))
				{
					$newentry->id = $editid;
					print_simple_box_start("center","40%", "#ccffbb");
					?><div class="block_eportfolio_center"><form method="post" action="<?php echo $CFG->wwwroot; ?>/blocks/exabis_eportfolio/view_categories.php?courseid=<?php echo $courseid; ?>&amp;edit=1">
					  <fieldset>
					  <input type="text" name="name" value="<?php echo s($editrecord->name)?>" />
					  <input type="hidden" name="pid" value="<?php echo $editrecord->pid==0?"-1":$editrecord->pid;?>" />
					  <input type="hidden" name="courseid" value="<?php p($courseid);?>" />
					  <input type="hidden" name="cataction" value="editconfirm" />
					  <input type="submit" name="Submit" value="<?php echo get_string("change", "block_exabis_eportfolio")?>" />
					  <input type="hidden" name="catconfirm" value="1" />
					  <input type="hidden" name="sesskey" value="<?php echo sesskey()?>" />
					  <input type="hidden" name="editid" value="<?php echo $editrecord->id?>" />
					</fieldset>
				</form></div><?php
					print_simple_box_end();
					print_footer($course);
					exit;
				}
				else
				{
					error("Wrong id for edit");
				}
			break;
			case "editconfirm":
				$newentry->id = $editid;
				$newentry->userid = $USER->id;
				
				if($pid > 0)
					$newentry->pid = $pid;
				else
					$newentry->pid = 0;
				
				if(count_records("block_exabeporcate", "id", $newentry->id, "userid", $USER->id) == 1)
				{
					if (! update_record("block_exabeporcate", $newentry))
					{
						error("Could not update your categories");
					}
					else
					{
						add_to_log($courseid, "bookmark", "update category", "", $newentry->id);
						$message = get_string("categoryedited","block_exabis_eportfolio");
					}
				}
				else
				{
					error("Wrong id for edit");
				}
			break;
			case "delete":
				if($catconfirm==1) {
					$optionsyes = array('cataction'=>'delete', 'courseid' => $courseid, 'catconfirm'=>2, 'sesskey'=>sesskey(), 'delid'=>$delid, 'edit' => 1);
					$optionsno = array('courseid'=>$courseid, 'edit'=>1);
					
					$strbookmarks = get_string("mybookmarks", "block_exabis_eportfolio");
					$strcat = get_string("categories", "block_exabis_eportfolio");

					echo '<br />';
					notice_yesno(get_string("deletecategroyconfirm", "block_exabis_eportfolio"), 'view_categories.php', 'view_categories.php', $optionsyes, $optionsno, 'post', 'get');
					print_footer();
					die;
				}
				else if($catconfirm==2) {
					if($delid > 0)
					{
						$newentry->id = $delid;
						if(!delete_records('block_exabeporcate', 'id', $newentry->id, 'userid', $USER->id))
						{
							$message = "Could not delete your record";
						}
						else
						{
							if ($entries = get_records_select('block_exabeporitem', 'categoryid='.$delid, '', 'id')) {
								foreach ($entries as $entry) {
									delete_records('block_exabeporitemshar', 'itemid', $entry->id);
								}
							}
							delete_records('block_exabeporitem', 'categoryid', $delid);
							
							add_to_log($courseid, "bookmark", "delete category", "", $newentry->id);
							$message = get_string("categorydeleted","block_exabis_eportfolio");
						}
					}
					else
					{
						$message = "Wrong id for delete";
					}
				}
			break;
		}
		print_simple_box_start("center","40%", "#ccffbb");
		echo "<div class='block_eportfolio_center'>$message</div>";
		print_simple_box_end();
	}
}

//$categoriesform = new eportfolio_new_categorie_form();

if($edit == 1) {
	if (!confirm_sesskey()) {
		error('Bad Session Key');
	}
	echo '<div class="block_eportfolio_centerw"><table style="margin-left:auto;margin-right:auto;" border="0" cellspacing="5" cellpadding="5">';
	$outer_categories = get_records_select("block_exabeporcate","userid='$USER->id' AND pid=0", "name asc");
	if ( $outer_categories ) {
		
		echo '<tr><td class="block_eportfolio_bold">'.get_string("maincategory","block_exabis_eportfolio").'</td><td class="block_eportfolio_bold">'.get_string("subcategory","block_exabis_eportfolio").'</td></tr>';
		foreach ($outer_categories as $curcategory ) {
			$count_inner_categories = (int)count_records_select("block_exabeporcate", "userid='$USER->id' AND pid='$curcategory->id'");
			$inner_categories = get_records_select("block_exabeporcate", "userid='$USER->id' AND pid='$curcategory->id'", "name asc");
			echo '<tr>';
			echo '<td valign="top">';
			echo format_string($curcategory->name);
			echo '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/view_categories.php?cataction=edit&amp;sesskey=' . sesskey() . '&amp;catconfirm=1&amp;courseid='.$courseid.'&amp;editid='.$curcategory->id.'&amp;edit=1"><img src="'.$CFG->wwwroot.'/pix/i/edit.gif" width="16" height="16" alt="'.get_string("edit",'block_exabis_eportfolio').'" /></a>';
				
			if($count_inner_categories == 0) {
				echo '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/view_categories.php?cataction=delete&amp;sesskey=' . sesskey() . '&amp;catconfirm=1&amp;courseid='.$courseid.'&amp;delid='.$curcategory->id.'&amp;edit=1"><img src="'.$CFG->wwwroot.'/pix/t/delete.gif" width="11" height="11" alt="'.get_string("delete").'" /></a>';
			}
			echo '</td>';
			echo '<td valign="top">';
			if($inner_categories) {
				foreach ($inner_categories as $innercurcategory ) {
					echo format_string($innercurcategory->name);
					echo '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/view_categories.php?cataction=edit&amp;sesskey=' . sesskey() . '&amp;catconfirm=1&amp;courseid='.$courseid.'&amp;editid='.$innercurcategory->id.'&amp;edit=1"><img src="'.$CFG->wwwroot.'/pix/i/edit.gif" width="16" height="16" alt="'.get_string("edit",'block_exabis_eportfolio').'" /></a>';
					echo '<a href="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/view_categories.php?cataction=delete&amp;sesskey=' . sesskey() . '&amp;catconfirm=1&amp;courseid='.$courseid.'&amp;delid='.$innercurcategory->id.'&amp;edit=1"><img src="'.$CFG->wwwroot.'/pix/t/delete.gif" width="11" height="11" alt="'.get_string("delete").'" /></a>';
					echo '<br />';
				}
			}
			
			echo '<form method="post" action="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/view_categories.php?courseid='.$courseid.'&amp;edit=1">';
			echo '<fieldset>';
			echo '<input type="text" name="name" />';
			echo '<input type="hidden" name="pid" value="' . $curcategory->id . '" />';
			echo '<input type="hidden" name="courseid" value="'. $courseid .'" />';
			echo '<input type="hidden" name="cataction" value="add" />';
			echo '<input type="submit" name="Submit" value="'.get_string("new") .'" />';
			echo '<input type="hidden" name="catconfirm" value="1" />';
			echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
			echo '</fieldset>';
			echo '</form>';
			echo '</td>';
			echo '</tr>';
		}
	}
	echo '<tr>';
	echo '<td valign="top">'; 
	
	echo '<form method="post" action="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/view_categories.php?courseid='.$courseid.'&amp;edit=1">';
	echo '<fieldset>';
	echo '<input type="text" name="name" />';
	echo '<input type="hidden" name="pid" value="-1" />';
	echo '<input type="hidden" name="courseid" value="'. $courseid .'" />';
	echo '<input type="submit" name="Submit" value="'.get_string("new") .'" />';
	echo '<input type="hidden" name="cataction" value="add" />';
	echo '<input type="hidden" name="catconfirm" value="1" />';
	echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
	echo '</fieldset>';
	echo '</form>';
	echo '</td>';
	echo '<td valign="top"></td>';
	echo '</tr>';
	
	echo '<tr>';
	echo '<td valign="top" style="text-align:center" colspan="2"><form method="post" action="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/view_categories.php?courseid='.$courseid.'"><fieldset><input type="submit" name="submit" value="'.get_string("endedit", "block_exabis_eportfolio").'" /><input type="hidden" name="sesskey" value="' . sesskey() . '" /></fieldset></form></td>';
	echo '</tr>';
	echo '</table></div>';
}                 
else {            
	echo '<div class="block_eportfolio_categories">';
	$owncats=get_records_select("block_exabeporcate", "userid=$USER->id AND pid=0", "name ASC");
	if ($owncats) {
		echo "<ul>";
		foreach ($owncats as $owncat) {
			echo '<li>' . format_string($owncat->name);
			$innerowncats=get_records_select("block_exabeporcate", "userid=$USER->id AND pid='$owncat->id'", "name ASC");
			  if($innerowncats) {
				echo "<ul>";
				foreach ($innerowncats as $innerowncat) {
				  echo '<li>' . format_string($innerowncat->name) . '</li>';
				}
				echo "</ul>";
			  }
			  echo "</li>";
		}
		echo "</ul>";
	}

	echo '<div class="block_eportfolio_centerw">';
	
	echo '<form method="post" action="'.$CFG->wwwroot.'/blocks/exabis_eportfolio/view_categories.php?courseid='.$courseid.'&amp;edit=1">';
	echo '<fieldset>';
	echo '<input type="submit" name="submit" value="'.get_string("edit",'block_exabis_eportfolio').'" />';
	echo '<input type="hidden" name="edit" value="1" />';
	echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
	echo '<input type="hidden" name="courseid" value="' . $courseid . '" />';
	echo '</fieldset>';
	echo '</form>';
	echo '</div></div>';
}

print_footer($course);

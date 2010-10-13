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

require_once $CFG->libdir.'/filelib.php';

function block_exabis_eportfolio_get_active_version()
{
	global $CFG;
	return empty($CFG->block_exabis_eportfolio_active_version) ? 3 : $CFG->block_exabis_eportfolio_active_version;
}

// Creates a directory file name, suitable for make_upload_directory()
function block_exabis_eportfolio_file_area_name($entry) {
    return 'exabis_eportfolio/files/' . $entry->userid . '/' . $entry->id;
}

function block_exabis_eportfolio_file_area($entry) {
    return make_upload_directory( block_exabis_eportfolio_file_area_name($entry) );
}

/**
 * Remove the item directory (for the attachment) incl. contents if present
 * @param object $entry the entry object
 * return nothing. no idea what remove_dir returns :>
 */
function block_exabis_eportfolio_file_remove($entry) {
    global $CFG;
    return remove_dir($CFG->dataroot.'/'.block_exabis_eportfolio_file_area_name($entry));
}




// Deletes all the user files in the attachments area for a entry
// EXCEPT for any file named $exception
function block_exabis_eportfolio_delete_old_attachments($id, $entry, $exception="") {

    if ($basedir = block_exabis_eportfolio_file_area($entry)) {
        if ($files = get_directory_list($basedir)) {
            foreach ($files as $file) {
                if ($file != $exception) {
                    unlink("$basedir/$file");
                }
            }
        }
        if (!$exception) {  // Delete directory as well, if empty
            @rmdir("$basedir");
        }
    }
}

// not needed at all - at least at the moment
//function block_exabis_eportfolio_empty_directory($basedir) {
//	if ($files = get_directory_list($basedir)) {
//        foreach ($files as $file) {
//            unlink("$basedir/$file");
//        }
//    }
//}
 
function block_exabis_eportfolio_copy_attachments($entry, $newentry) {
/// Given a entry object that is being copied to bookmarkid,
/// this function checks that entry
/// for attachments, and if any are found, these are
/// copied to the new bookmark directory.

    global $CFG;

    $return = true;

    if ($entries = get_records_select("bookmark", "id = '{$entry->id}' AND attachment <> ''")) {
        foreach ($entries as $curentry) {
            $oldentry = new stdClass();
            $oldentry->id = $entry->id;
            $oldentry->userid = $entry->userid;
            $oldentry->name = $entry->name;
            $oldentry->category = $curentry->category;
            $oldentry->intro = $entry->intro;
            $oldentry->url = $entry->url;
            $oldentrydir = "$CFG->dataroot/".block_exabis_eportfolio_file_area_name($oldentry);
            if (is_dir($oldentrydir)) {

                $newentrydir = block_exabis_eportfolio_file_area($newentry);
                if (! copy("$oldentrydir/$newentry->attachment", "$newentrydir/$newentry->attachment")) {
                    $return = false;
                }
            }
        }
     }
    return $return;
}

function  block_exabis_eportfolio_move_attachments($entry, $bookmarkid, $id) {
/// Given a entry object that is being moved to bookmarkid,
/// this function checks that entry
/// for attachments, and if any are found, these are
/// moved to the new bookmark directory.

    global $CFG;

    $return = true;

    if ($entries = get_records_select("bookmark", "id = '$entry->id' AND attachment <> ''")) {
        foreach ($entries as $entry) {
            $oldentry = new stdClass();
            $newentry = new stdClass();
            $oldentry->id = $entry->id;
            $oldentry->name = $entry->name;
            $oldentry->userid = $entry->userid;
            $oldentry->category = $curentry->category;
            $oldentry->intro = $entry->intro;
            $oldentry->url = $entry->url;
            $oldentrydir = "$CFG->dataroot/".block_exabis_eportfolio_file_area_name($oldentry);
            if (is_dir($oldentrydir)) {
                $newentry = $oldentry;
                $newentry->bookmarkid = $bookmarkid;
                $newentrydir = "$CFG->dataroot/".block_exabis_eportfolio_file_area_name($newentry);
                if (! @rename($oldentrydir, $newentrydir)) {
                    $return = false;
                }
            }
        }
    }
    return $return;
}

function block_exabis_eportfolio_add_attachment($entry, $newfile, $id) {
// $entry is a full entry record, including course and bookmark
// $newfile is a full upload array from $_FILES
// If successful, this function returns the name of the file

    global $CFG;

    if (empty($newfile['name'])) {
        return "";
    }

    $newfile_name = clean_filename($newfile['name']);

    if (valid_uploaded_file($newfile)) {
        if (! $newfile_name) {
            notify("This file had a wierd filename and couldn't be uploaded");

        } else if (! $dir = block_exabis_eportfolio_file_area($entry)) {
            notify("Attachment could not be stored");
            $newfile_name = "";

        } else {
            if (move_uploaded_file($newfile['tmp_name'], "$dir/$newfile_name")) {
                chmod("$dir/$newfile_name", $CFG->directorypermissions);
                block_exabis_eportfolio_delete_old_attachments($entry, $newfile_name);
            } else {
                notify("An error happened while saving the file on the server");
                $newfile_name = "";
            }
        }
    } else {
        $newfile_name = "";
    }

    return $newfile_name;
}

function block_exabis_eportfolio_print_attachments($id, $entry, $return=NULL, $align="left") {
// if return=html, then return a html string.
// if return=text, then return a text-only string.
// otherwise, print HTML for non-images, and return image HTML
//     if attachment is an image, $align set its aligment.
    global $CFG;

    $newentry = $entry;

    $filearea = block_exabis_eportfolio_file_area_name($newentry);

    $imagereturn = "";
    $output = "";

    if ($basedir = block_exabis_eportfolio_file_area($newentry)) {
        if ($files = get_directory_list($basedir)) {
            $strattachment = get_string("attachment", "block_exabis_eportfolio");
            $strpopupwindow = get_string("popupwindow");
            foreach ($files as $file) {
                $icon = mimeinfo("icon", $file);
                if ($CFG->slasharguments) {
                    $ffurl = "file.php/$filearea/$file";
                } else {
                    $ffurl = "file.php?file=/$filearea/$file";
                }
                $image = "<img border=0 src=\"$CFG->wwwroot/files/pix/$icon\" height=16 width=16 alt=\"$strpopupwindow\">";

                if ($return == "html") {
                    $output .= "<a target=_image href=\"$CFG->wwwroot/$ffurl\">$image</a> ";
                    $output .= "<a target=_image href=\"$CFG->wwwroot/$ffurl\">$file</a><br />";
                } else if ($return == "text") {
                    $output .= "$strattachment $file:\n$CFG->wwwroot/$ffurl\n";

                } else {
                    if ($icon == "image.gif") {    // Image attachments don't get printed as links
                        $imagereturn .= "<br /><img src=\"$CFG->wwwroot/$ffurl\" align=$align>";
                    } else {
                        link_to_popup_window("/$ffurl", "attachment", $image, 500, 500, $strattachment);
                        echo "<a target=_image href=\"$CFG->wwwroot/$ffurl\">$file</a>";
                        echo "<br />";
                    }
                }
            }
        }
    }

    if ($return) {
        return $output;
    }

    return $imagereturn;
}

function block_exabis_eportfolio_has_categories($userid) {
	global $CFG;
	if(count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}block_exabeporcate WHERE userid='$userid' AND pid=0") > 0) {
		return true;
	}
	else {
		return false;
	}
}

function block_exabis_eportfolio_moodleimport_file_area_name($userid, $assignmentid, $courseid) {
    global $CFG;

    return $courseid.'/'.$CFG->moddata.'/assignment/'.$assignmentid.'/'.$userid;
}

    
function block_exabis_eportfolio_print_file($url, $filename, $alttext) {
	global $CFG;
	$icon = mimeinfo('icon', $filename);
    $type = mimeinfo('type', $filename);
    if (in_array($type, array('image/gif', 'image/jpeg', 'image/png'))) {    // Image attachments don't get printed as links
        return "<img src=\"$url\" alt=\"" . format_string($alttext) . "\" />";
    } else {
    	return '<p><img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />&nbsp;' . link_to_popup_window($url, 'popup', $filename, $height=400, $width=500, $alttext, 'none', true) . "</p>";
    }
}

/**
 * Print moodle header
 * @param string $item_identifier translation-id for this page
 * @param string $sub_item_identifier translation-id for second level if needed
 */
function block_exabis_eportfolio_print_header($item_identifier, $sub_item_identifier = null) {

	if (!is_string($item_identifier)) {
		echo 'noch nicht unterstützt';
	}

	global $CFG, $COURSE;

	$strbookmarks = get_string("mybookmarks", "block_exabis_eportfolio");

	// navigationspfad
	$navlinks = array();
	$navlinks[] = array('name' => $strbookmarks, 'link' => "view.php?courseid=".$COURSE->id, 'type' => 'title');
	$nav_item_identifier = $item_identifier;

	$icon = $item_identifier;
	$currenttab = $item_identifier;

	// haupttabs
	$tabs = array();
	$tabs[] = new tabobject('personal', $CFG->wwwroot.'/blocks/exabis_eportfolio/view.php?courseid='.$COURSE->id, get_string("personal", "block_exabis_eportfolio"), '', true);
	$tabs[] = new tabobject('categories', $CFG->wwwroot.'/blocks/exabis_eportfolio/view_categories.php?courseid='.$COURSE->id, get_string("categories", "block_exabis_eportfolio"), '', true);
	$tabs[] = new tabobject('bookmarks', $CFG->wwwroot.'/blocks/exabis_eportfolio/view_items.php?courseid='.$COURSE->id, get_string("bookmarks", "block_exabis_eportfolio"), '', true);
	if (block_exabis_eportfolio_get_active_version() >= 3) {
		$tabs[] = new tabobject('views', $CFG->wwwroot.'/blocks/exabis_eportfolio/views_list.php?courseid='.$COURSE->id, get_string("views", "block_exabis_eportfolio"), '', true);
	}
	$tabs[] = new tabobject('exportimport', $CFG->wwwroot.'/blocks/exabis_eportfolio/exportimport.php?courseid='.$COURSE->id, get_string("exportimport", "block_exabis_eportfolio"), '', true);
	$tabs[] = new tabobject('sharedbookmarks', $CFG->wwwroot.'/blocks/exabis_eportfolio/shared_people.php?courseid='.$COURSE->id, get_string("sharedbookmarks", "block_exabis_eportfolio"), '', true);

	// tabs für das untermenü
	$tabs_sub = array();
	// ausgewählte tabs für untermenüs
	$activetabsubs = Array();

	if (strpos($item_identifier, 'bookmarks') === 0) {
		$activetabsubs[] = $item_identifier;
		$currenttab = 'bookmarks';
		
		// untermenü tabs hinzufügen
		$tabs_sub['bookmarksall'] = new tabobject('bookmarksall', $CFG->wwwroot.'/blocks/exabis_eportfolio/view_items.php?courseid='.$COURSE->id,
									get_string("bookmarksall","block_exabis_eportfolio"), '', true);
		$tabs_sub['bookmarkslinks'] = new tabobject('bookmarkslinks', $CFG->wwwroot.'/blocks/exabis_eportfolio/view_items.php?courseid='.$COURSE->id.'&type=link',
									get_string("bookmarkslinks","block_exabis_eportfolio"), '', true);
		$tabs_sub['bookmarksfiles'] = new tabobject('bookmarksfiles', $CFG->wwwroot.'/blocks/exabis_eportfolio/view_items.php?courseid='.$COURSE->id.'&type=file',
									get_string("bookmarksfiles","block_exabis_eportfolio"), '', true);
		$tabs_sub['bookmarksnotes'] = new tabobject('bookmarksnotes', $CFG->wwwroot.'/blocks/exabis_eportfolio/view_items.php?courseid='.$COURSE->id.'&type=note',
									get_string("bookmarksnotes","block_exabis_eportfolio"), '', true);

		if ($sub_item_identifier) {
			$navlinks[] = array('name' => get_string($item_identifier, "block_exabis_eportfolio"), 'link' => $tabs_sub[$item_identifier]->link, 'type' => 'misc');

			$nav_item_identifier = $sub_item_identifier;
		}
	}
	elseif (strpos($item_identifier, 'exportimport') === 0) {
		$currenttab = 'exportimport';

		// unterpunkt?
		if ($tmp = substr($item_identifier, strlen($currenttab))) {
			$nav_item_identifier = $tmp;
		}

		if (strpos($nav_item_identifier, 'export') !== false)
			$icon = 'export';
		else
			$icon = 'import';
	}
	

	$item_name = get_string($nav_item_identifier, "block_exabis_eportfolio");
	if ($item_name[0] == '[')
		$item_name = get_string($nav_item_identifier);
	$navlinks[] = array('name' => $item_name, 'link' => null, 'type' => 'misc');
	
	$navigation = build_navigation($navlinks);
	print_header_simple($item_name, '', $navigation, "", "", true);

	// header
	print_heading("<img src=\"{$CFG->wwwroot}/blocks/exabis_eportfolio/pix/".$icon.".png\" width=\"16\" height=\"16\" alt='icon-$item_identifier' /> " . $strbookmarks.': '.$item_name);

	print_tabs(array($tabs, $tabs_sub), $currenttab, null, $activetabsubs);
}

function block_exabis_eportfolio_get_string($string)
{
	$translation = get_string($string, "block_exabis_eportfolio");
	if ($translation[0] == '[')
		$translation = get_string($string);
	return $translation;
}

function block_exabis_eportfolio_print_footer()
{
	global $COURSE;
	print_footer($COURSE);
}

/**
 * Parse user submitted item_type and return a correct type
 * @param string $type
 * @param boolean $all_allowd Is the type 'all' allowed? E.g. for Item-List
 * @return string correct type
 */
function block_exabis_eportfolio_check_item_type($type, $all_allowed) {
	if (in_array($type, Array('link', 'file', 'note')))
		return $type;
	else
		return $all_allowed ? 'all' : false;
}

/**
 * Convert item type to plural
 * @param string $type
 * @return string Plural. E.g. file->files, note->notes, all->all (has no plural)
 */
function block_exabis_eportfolio_get_plural_item_type($type) {
	return $type == 'all' ? $type : $type.'s';
}


/**
 * Parse user submitted item sorting and check if allowed/available!
 * @param $sort the sorting in a format like "category.desc"
 * @return Array(sortcolumn, asc|desc)
 */
function block_exabis_eportfolio_parse_sort($sort, array $allowedSorts, array $defaultSort = null)
{
	if (!is_array($sort))
		$sort = explode('.', $sort);

	$column = $sort[0];
	$order = isset($sort[1]) ? $sort[1] : '';

	if (!in_array($column, $allowedSorts)) {
		if ($defaultSort) {
			return $defaultSort;
		} else {
			return array(reset($allowedSorts), 'asc');
		}
	}

	// sortorder never desc allowed!
	if ($column == 'sortorder')
		return array($column, 'asc');

	if ($order != "desc")
		$order = "asc";

	return array($column, $order);
}

/**
 * Generate sql order by from user sorting
 * @param array|string $sort See function block_exabis_eportfolio_parse_item_sort
 * @return string Sql order by
 */
function block_exabis_eportfolio_sort_to_sql($sort)
{
	$sort = block_exabis_eportfolio_parse_sort($sort);

	$column = $sort[0];
	$order = $sort[1];

	$sql_sort = $column." ".$order;
}


function block_exabis_eportfolio_parse_item_sort($sort)
{
	return block_exabis_eportfolio_parse_sort($sort, array('date', 'name', 'category', 'type', 'sortorder'), array('date', 'desc'));
}
function block_exabis_eportfolio_item_sort_to_sql($sort)
{
	$sort = block_exabis_eportfolio_parse_item_sort($sort);

	$column = $sort[0];
	$order = $sort[1];

	if ($column ==  "date" ) {
		$sql_sort = "i.timemodified ".$order;
	} elseif ($column == "category") {
		$sql_sort = "cname ".$order.", i.timemodified";
	} else {
		$sql_sort = "i.".$column." ".$order.", i.timemodified";
	}

	return ' order by '.$sql_sort;
}


function block_exabis_eportfolio_parse_view_sort($sort)
{
	return block_exabis_eportfolio_parse_sort($sort, array('name', 'timemodified'));
}
function block_exabis_eportfolio_view_sort_to_sql($sort)
{
	$sort = block_exabis_eportfolio_parse_view_sort($sort);

	$column = $sort[0];
	$order = $sort[1];

	$sql_sort = "v.".$column." ".$order.", v.timemodified DESC";

	return ' order by '.$sql_sort;
}


function block_exabis_eportfolio_get_user_preferences_record($userid = null)
{
	if (is_null($userid)) {
		global $USER;
		$userid = $USER->id;
	}
	
	return get_record('block_exabeporuser', 'user_id', $userid);
}

function block_exabis_eportfolio_get_user_preferences($userid = null)
{
	if (is_null($userid)) {
		global $USER;
		$userid = $USER->id;
	}
	
	$userpreferences = block_exabis_eportfolio_get_user_preferences_record($userid);

	if (!$userpreferences || !$userpreferences->user_hash) {
        do {
        	$hash = substr(md5(uniqid(rand(), true)), 3, 8);
        } while(record_exists("block_exabeporuser", "user_hash", $hash));

		block_exabis_eportfolio_set_user_preferences($userid, array('user_hash' => $hash));

		$userpreferences = block_exabis_eportfolio_get_user_preferences_record($userid);
    }

	return $userpreferences;
}

function block_exabis_eportfolio_set_user_preferences($userid, $preferences = null)
{
	if (is_null($preferences) && (is_array($userid) || is_object($userid))) {
		global $USER;
		$preferences = $userid;
		$userid = $USER->id;
	}

	$newuserpreferences = new stdClass();

	if (is_object($preferences)) {
		$newuserpreferences = $preferences;
	} elseif (is_array($preferences)) {
		foreach ($preferences as $key=>$value) {
			$newuserpreferences->$key = $value;
		}
	} else {
		echo 'error #fjklfdsjkl';
	}

	if ($olduserpreferences = block_exabis_eportfolio_get_user_preferences_record($userid)) {
		$newuserpreferences->id = $olduserpreferences->id;
		update_record('block_exabeporuser', $newuserpreferences);
	}
	else {
		$newuserpreferences->user_id = $userid;
		insert_record("block_exabeporuser", $newuserpreferences);
	}
}

/**
 * moodle 1.8 compatibility:
 * backporting build_navigation, because it didn't exist in before 1.9
 */
if (!function_exists('build_navigation')) {
	function build_navigation($extranavlinks, $cm = null) {
		global $CFG, $COURSE;

		if (is_string($extranavlinks)) {
			if ($extranavlinks == '') {
				$extranavlinks = array();
			} else {
				$extranavlinks = array(array('name' => $extranavlinks, 'link' => '', 'type' => 'title'));
			}
		}

		$navlinks = array();

		// Course name, if appropriate.
		if (isset($COURSE) && $COURSE->id != SITEID) {
			$navlinks[] = array(
					'name' => format_string($COURSE->shortname),
					'link' => "$CFG->wwwroot/course/view.php?id=$COURSE->id",
					'type' => 'course');
		}

		//Merge in extra navigation links
		$navlinks = array_merge($navlinks, $extranavlinks);

		// Work out whether we should be showing the activity (e.g. Forums) link.
		// Note: build_navigation() is called from many places --
		// install & upgrade for example -- where we cannot count on the
		// roles infrastructure to be defined. Hence the $CFG->rolesactive check.
		if (!isset($CFG->hideactivitytypenavlink)) {
			$CFG->hideactivitytypenavlink = 0;
		}
		if ($CFG->hideactivitytypenavlink == 2) {
			$hideactivitylink = true;
		} else if ($CFG->hideactivitytypenavlink == 1 && $CFG->rolesactive &&
				!empty($COURSE->id) && $COURSE->id != SITEID) {
			if (!isset($COURSE->context)) {
				$COURSE->context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
			}
			$hideactivitylink = !has_capability('moodle/course:manageactivities', $COURSE->context);
		} else {
			$hideactivitylink = false;
		}

		//Construct an unordered list from $navlinks
		//Accessibility: heading hidden from visual browsers by default.
		$navigation = '';
		$lastindex = count($navlinks) - 1;
		$i = -1; // Used to count the times, so we know when we get to the last item.
		$first = true;

		foreach ($navlinks as $navlink) {
			$i++;
			$last = ($i == $lastindex);
			if (!is_array($navlink)) {
				continue;
			}
			if (!empty($navlink['type']) && $navlink['type'] == 'activity' && !$last && $hideactivitylink) {
				continue;
			}

			if (!$first) {
				$navigation .= " -> ";
			}
			if ((!empty($navlink['link'])) && !$last) {
				$navigation .= "<a onclick=\"this.target='$CFG->framename'\" href=\"{$navlink['link']}\">";
			}
			$navigation .= "{$navlink['name']}";
			if ((!empty($navlink['link'])) && !$last) {
				$navigation .= "</a>";
			}

			$first = false;
		}

		return $navigation;
	}
}


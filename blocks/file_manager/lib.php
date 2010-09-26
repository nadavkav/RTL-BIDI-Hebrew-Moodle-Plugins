<?PHP 

/****************************************************************************
* Filename:  lib.php
* Created:  Michael Avelar
* Edited:  Josh Abbott
* Created:  5/23/05
* Modified:  1/6/06
*  Purpose:  This file contains all the functions used within the file_manager 
*****************************************************************************/
/**************************** Organization **********************************/
// Link Types					// Defines link types
// Link Functions				// Deals with all file related features
// Folder Functions				// Deals with all folder related features
// Category Functions			// Deals with all category related features
// Shared Link Functions		// Deals with all shared links 
// Security Functions			// Has all functions that check for ownership/permissions/etc
// Database Functions			// Various db functions
// ZIP Functions				// Deals with zip archiving and related functions
// Print Functions				// (See print_lib.php) Functions to print tables/forms to screen etc.
// JS Print Functions			// (See print_lib.php) Prints short javascript functions
// Moodle Required Functions	// Required by moodle
/****************************************************************************/

require_once("$CFG->dirroot/lib/filelib.php");

/*************************** Link Types *************************************/
define('TYPE_FILE', 1);
define('TYPE_URL', 2);
define('TYPE_ZIP', 3);   
define('STYPE_FILE',0);
define('STYPE_CAT',1);
define('STYPE_FOLD',2);

/*************************** Owner Types *************************************/
/**
 * Owner refers to a user
 */
define('OWNERISUSER', 0);

/**
 * Owner refers to a group
 */
define('OWNERISGROUP', 1);

/*************************** Link Functions *********************************/
// Returns the link record
//	fm_get_user_link($linkid)
// Creates or Updates a link record
// 	fm_update_link($link, $linkid)
// Deletes references to a deleted category
//	fm_update_links_cats($catid)
// Uploads the specified file to the user's personal directory
//	fm_upload_file($fileurl, $linkrename, $rootdir)
// Deletes a file 
//	fm_remove_file($file)
/****************************************************************************/


/**
* @param int $linkid single link id
* @uses $USER
*/
function fm_get_user_link($linkid) {
	global $USER;

	if (!$linksrec = get_record('fmanager_link', "id", $linkid, 'owner', $USER->id, 'ownertype', OWNERISUSER)) {
		error(get_string('errnoviewfile','block_file_manager'));
	}
	return $linksrec;
}


/**
* @param int $linkid single link id
*/
function fm_get_group_link($linkid, $groupid) {
	if (!$linksrec = get_record('fmanager_link', "id", $linkid, 'owner', $groupid, 'ownertype', OWNERISGROUP)) {
		error(get_string('errnoviewfile','block_file_manager'));
	}
	return $linksrec;
}

/**
* @param object $link object containing updated info
* @param int $groupid groupid of the resource
* @param int $linkid id of link to be updated
* @param int $id 
* @param int $rootdir 
* @uses $USER
* @uses $CFG
*/
function fm_update_link($link, $groupid=0, $linkid = NULL, $id=1, $rootdir=0) {
	global $USER, $CFG;
	
	if ($linkid != NULL) {		// update a record
		$link->id = $linkid;
		$tmp = get_record('fmanager_link', 'id', $linkid);
		if ($tmp->type == TYPE_URL) {
			// Appends any url link with an http:// if it doesnt exist
			if (! preg_match("/^http:\/\//", $link->url)) {
				$link->url = "http://" . $link->url;
			} 
			$link->link = $link->url;
		}
		$link->timemodified = time();
		// File name was changed
		if ($tmp->link != $link->link) {
			if ($groupid == 0){
				$destinationfile = $CFG->dataroot."/".fm_get_user_dir_space().fm_get_folder_path($tmp->folder, false, $groupid)."/".$link->link;
				$sourcefile = $CFG->dataroot."/".fm_get_user_dir_space().fm_get_folder_path($tmp->folder, false, $groupid)."/".$tmp->link;
			} else {
				$destinationfile = $CFG->dataroot."/".fm_get_group_dir_space($groupid).fm_get_folder_path($tmp->folder, false, $groupid)."/".$link->link;
				$sourcefile = $CFG->dataroot."/".fm_get_group_dir_space($groupid).fm_get_folder_path($tmp->folder, false, $groupid)."/".$tmp->link;
			}
			if (file_exists($destinationfile)) {
				error(get_string('errfileexists','block_file_manager', $link->link),"link_manage.php?id=1&groupid=$groupid&linkid=$linkid&rootdir=$rootdir");
			}				
			if ($tmp->type != TYPE_URL && !@rename($sourcefile,$destinationfile)) {
				error(get_string('errnorename','block_file_manager'));
			}
		} 
		if (!update_record('fmanager_link', $link)) {
			error(get_string("errnoupdate",'block_file_manager'));
		}
	} else {
		if (isset($link->radioval) && $link->radioval == "file") {
			$link->type = TYPE_FILE;
		} elseif ($link->type != TYPE_ZIP) {
			$link->type = TYPE_URL;
			// Appends any url link with an http:// if it doesnt exist
			if (!preg_match("/^http:\/\//", $link->url)) {
				$link->url = "http://" . $link->url;
			} 
		}
		if ($groupid == 0){
			$link->owner = $USER->id; 
			$link->ownertype = OWNERISUSER;
		} else {
			$link->owner = $groupid;  
			$link->ownertype = OWNERISGROUP;
		}
        if (isset($link->url)) {
		    $link->link = $link->url;
        } else {
            $link->link = '';
        }
		$link->timemodified = time();
		if ($link->type == TYPE_ZIP) {
			$link->link = $link->name.".zip";
		}
		// Allows user to upload zip files
		if (substr($link->link,-4) == ".zip") {
			$link->type = TYPE_ZIP;
		}
		if (!insert_record('fmanager_link', $link)) {
			error(get_string("errnoinsert",'block_file_manager'));
		}
	}
	return true;
}

/**
* Removes the given category from all links that are in this category
* @param int $catid id of category being removed
*/
function fm_update_links_cats($catid) {
	// Changes all associated links to deleted cat to 0
	if ($linkcats = get_records('fmanager_link', "category", $catid)) {
		foreach ($linkcats as $lc) {		
			$lc->category = 0;
			if (!update_record('fmanager_link', $lc)) {
				error(get_string("errnoupdate",'block_file_manager'));
			}
		}
	}
}

/**
* Removes the given category from all folders that are in this category
* @param int $catid id of category being removed
*/
function fm_update_folders_cats($catid) {
	// Changes all associated links to deleted cat to 0
	if ($linkcats = get_records('fmanager_folders', "category", $catid)) {
		foreach ($linkcats as $lc) {		
			$lc->category = 0;
			if (!update_record('fmanager_folders', $lc)) {
				error(get_string("errnoupdate",'block_file_manager'));
			}
		}
	}
}

/**
* @param string $fileurl The file's location on the user's computer
* @param string $linkrename If file existed already, this will store the renamed file if the user chooses to rename
* @param string $rootdir target directory of uploaded file
*/
function fm_upload_file($fileurl, $linkrename, $rootdir=0, $groupid=0) {
	global $USER;
	
	if ($linkrename != NULL) {
		// renaming file and adding the extension
		$newfilename = clean_filename($linkrename.stristr($fileurl['name'], '.'));
	} else {
		$newfilename = clean_filename($fileurl['name']);
	}
	
	$folder = get_record('fmanager_folders', 'id', $rootdir);
	$folderlocation = ($folder) ? "{$folder->path}{$folder->name}" : '' ; 

    if (valid_uploaded_file($fileurl)) {
        if (!$newfilename) {
            error(get_string('errwierdfilename', 'block_file_manager', $fileurl['name']));
        } else if ($groupid == 0 && !$dir = make_upload_directory(fm_get_user_dir_space().$folderlocation)) {
            error(get_string('errnodir', 'block_file_manager'));
        } else if ($groupid != 0 && !$dir = make_upload_directory(fm_get_group_dir_space($groupid)."{$folder->path}{$folder->name}")) {
            error(get_string('errnodir', 'block_file_manager'));
        } else {
			if(file_exists("$dir/$newfilename")) {
				$newfilename = '';		// flags the link_manage that file existed already
			} else {
				if (move_uploaded_file($fileurl['tmp_name'], "$dir/$newfilename")) {
					chmod("$dir/$newfilename", 0755);
				} else {
					error(get_string('errserversave', 'block_file_manager'), "view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
				}
			}
        }
    } else {
		error(get_string('errwierdfilename', 'block_file_manager', $fileurl['name']));
    }

    return $newfilename;
}

/**
*
* @param object $file link entry that is being deleted
* @uses $CFG
*/
function fm_remove_file($file, $groupid) {
	global $CFG;
	
	if ($groupid == 0){
		$bdir = $CFG->dataroot."/".fm_get_user_dir_space();
	} else {
		$bdir = $CFG->dataroot."/".fm_get_group_dir_space($groupid);
	}

	// If there is no sub-folder
	if ($file->folder == 0) {		
		$bdir = $bdir."/".$file->link;
	} else {
		$bdir = $bdir."/".fm_get_folder_path($file->folder, false, $groupid)."/".$file->link;
	}
	
	if (!file_exists($bdir)) {		// If file isnt found for some reason...
		return get_string('msgfilenotfound', 'block_file_manager', $file->link);
	} else if (!@unlink($bdir)) {		// Deletes the attachment
		error(get_string("errnodeletefile",'block_file_manager'));
	} 
	return NULL;
}

/*************************** Folder Functions *******************************/
// Returns the location of the file_manager folder...just incase
// 	fm_get_root_dir()
// Returns the user's personal directory space
//	fm_get_user_dir_space()
// Returns the groups's personal directory space
//	fm_get_group_dir_space()
// Returns the folder entry given a fm_folders id
//	fm_get_folder_path($folderid, $notsecure)
// Deletes a folder and all subdirectories
//  fm_delete_folder($path)
// Moves specified file/link/folder into destination folder
//  fm_move_to_folder($c, $rootdir)
// Returns folder/file size
//  fm_get_size($path)
// Converts Bytes into more readable info
//  fm_readable_filesize($size)
/****************************************************************************/

/**
* returns subapp root directory
*/
function fm_get_root_dir() {
	return 'blocks/file_manager';
}

/**
* makes a standard relative path location for user files
* @param int $userid
* @uses $USER
*/
function fm_get_user_dir_space($userid = NULL) {
	global $USER;
	
	if ($userid == NULL) {
		$userid = $USER->id;
	}
	
	return "file_manager/users/$userid";
}

/**
* Returns the groups's personal directory space
* @param int $groupid The groupid of the group from which we want to get the directory space 
*/
function fm_get_group_dir_space($groupid) {
	
	if ($groupid == NULL){
		// ERROR
	} else {
		return "file_manager/groups/$groupid";
	}
}

/**
* make a complete folder path 
* @param int $folderid A fm_folders unique id number
* @param boolean $notsecure	flag to not check for ownership...(for shared files)
* @param int $groupid
* @uses $USER
*/
function fm_get_folder_path($folderid, $notsecure = false, $groupid=0) {
	global $USER;
	
	if ($folderid == 0) {
		return '';
	} else {
		// Ensure user owns the folder
		if ($notsecure == false && $groupid == 0) { fm_user_owns_folder($folderid); }
		if ($notsecure == false && $groupid != 0) { fm_group_owns_folder($folderid, $groupid); }
		if (!$retval = get_record('fmanager_folders', 'id', $folderid)) {
			return '';
		} 
		return $retval->path.$retval->name;
	}
}

/**
* recursively deletes a folder in database and physically
* @param object $dir folder database object
* @param $groupid
* @uses $CFG
* @uses $USER
*/
function fm_delete_folder($dir, $groupid) {
	global $CFG, $USER;
	
	// if the dir being deleted is a root dir (eg. has some dir's under it)
	if ($child = get_record('fmanager_folders', 'pathid', $dir->id)) {
		fm_delete_folder($child, $groupid);
	} 
	// Deletes all files/url links under folder
	if ($allrecs = get_records('fmanager_link', 'folder', $dir->id)) {
		foreach ($allrecs as $ar) {
			// a file
			if ($ar->type == TYPE_FILE || $ar->type == TYPE_ZIP) {
				fm_remove_file($ar, $groupid);
			} 
			// removes shared aspect
			delete_records('fmanager_shared', 'sharedlink', $ar->id);					
			// Delete link
			delete_records('fmanager_link', 'id', $ar->id);
		}
	}

	// delete shared to folder
	delete_records('fmanager_shared', 'sharedlink', $dir->id);	

	if ($groupid == 0) {
		if (!@rmdir($CFG->dataroot."/file_manager/users/".$USER->id.$dir->path.$dir->name."/")) {
			error(get_string('errnodeletefolder', 'block_file_manager'));
		}
	} else {
		if (!@rmdir($CFG->dataroot."/file_manager/groups/".$groupid.$dir->path.$dir->name."/")) {
			error(get_string('errnodeletefolder', 'block_file_manager'));
		}
	}
	delete_records('fmanager_folders', 'id', $dir->id);
	
}

/**
* @param string $c id of link/folder (folders are 'f-' appended with id)
* @param string $rootdir id of destination folder
* @uses $CFG
*/
function fm_move_to_folder($c, $rootdir, $groupid) {
	global $CFG;

	if ($c != 0 || substr($c,0,2) == 'f-') {
		if (substr($c,0,2) == 'f-') {
			$tmpfoldid = substr($c,2);
			$rec = get_record('fmanager_folders','id',$tmpfoldid);  // bug fix ? 
			$tmp = get_record('fmanager_folders','id',$rootdir);
			if ($groupid == 0){
				$destinationfile = $CFG->dataroot."/".fm_get_user_dir_space().fm_get_folder_path($rootdir, false, $groupid)."/".$rec->name."/";
				$sourcefile = $CFG->dataroot."/".fm_get_user_dir_space().fm_get_folder_path($rec->pathid, $groupid).$rec->path.$rec->name.'/';
			} else {
				$destinationfile = $CFG->dataroot."/".fm_get_group_dir_space($groupid).fm_get_folder_path($rootdir, false, $groupid)."/".$rec->name."/";
				$sourcefile = $CFG->dataroot."/".fm_get_group_dir_space($groupid).fm_get_folder_path($rec->pathid, $groupid).$rec->path.$rec->name.'/';
			}
			$rec->pathid = $rootdir;
			$rec->path = $tmp->path.$tmp->name."/";
			// Quick catch of moving a folder into itself
			if ($rootdir == $tmpfoldid) {
				print_simple_box_start("center","500","red");
				echo get_string('errsamefolder','block_file_manager', $tmp->name);
				print_simple_box_end();
			} else {
				if (!@rename($sourcefile,$destinationfile)) {
					// When moving a folder into itself...throws this error
					print_simple_box_start("center","500","red");
					echo get_string('errsamefolder','block_file_manager', $rec->name);
					print_simple_box_end();
				} else {
					if (!update_record('fmanager_folders',$rec)) {
						error(get_string('errnoupdate','block_file_manager'));
					}
				}
			}
		} else {
			$rec = get_record('fmanager_link',"id",$c);
			$exists = false;	// Does file exist?
			if ($rec->type == TYPE_FILE || $rec->type == TYPE_ZIP) {
				if ($groupid == 0){
					$destinationfile = $CFG->dataroot."/".fm_get_user_dir_space().fm_get_folder_path($rootdir, false, $groupid)."/".$rec->link;
					$sourcefile = $CFG->dataroot."/".fm_get_user_dir_space().fm_get_folder_path($rec->folder, false, $groupid)."/".$rec->link;
				} else {
					$destinationfile = $CFG->dataroot."/".fm_get_group_dir_space($groupid).fm_get_folder_path($rootdir, false, $groupid)."/".$rec->link;
					$sourcefile = $CFG->dataroot."/".fm_get_group_dir_space($groupid).fm_get_folder_path($rec->folder, false, $groupid)."/".$rec->link;
				}
				// Checks if file exists and prints warning, and doesnt change anything for that file only
				if (file_exists($destinationfile)) {
					print_simple_box_start("center",'500','red');
					echo get_string('errfileexists','block_file_manager', $rec->name);
					print_simple_box_end();
					$exists = true;
				} else {
					if (!@copy($sourcefile,$destinationfile)) {
						error(get_string('errcantmovefile','block_file_manager'));
					}
					if (!@unlink($sourcefile)) {
						error(get_string('errnodeletefile','block_file_manager'));
					}
				}
			}
			$rec->folder = $rootdir;
			if (!$exists && !update_record('fmanager_link',$rec)) {
				error(get_string('errnoupdate','block_file_manager'));
			}
		}
	}
}
	
/**
* gets the size of a folder/file. Calculates recursively the size of a directory tree.
* @param string $path path of folder/file
*/
function fm_get_size($path, $rawbytes = 0) {
	if (!is_dir($path)) {
		return fm_readable_filesize(@filesize($path));
	}
	$dir = opendir($path);
	$size = 0;
	while ($file = readdir($dir)) {
		if (is_dir($path."/".$file) && $file != "." && $file != "..") {
			$size += fm_get_size($path."/".$file, 1);
		} else {
			$size += @filesize($path."/".$file);
		}
	}
	if ($rawbytes == 1) {
		return $size;
	}
	if ($size == 0) {
		return "0 Bytes";
	} else {
		return fm_readable_filesize($size);
	}
}

/**
* formats the effective filesize of a file for nicer readability
* @param int $size
*/
function fm_readable_filesize($size) {
	$filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
	if ($size != 0) {
		return round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i];
	} else {
		return "0".$filesizename[0];
	}
}
/*************************** Category Functions *****************************/
// This either creates a new category or updates the existing one
// 	fm_update_category($name, $catid);	
// This takes a num or an array of nums and returns a catname or an array of catnames
// 	fm_get_user_categories($catid)			
// Returns an array of user's categories for choose_from_menu function
// 	fm_get_cat_list()
/****************************************************************************/

// $name 	= Name of category
// $catid 	= If present, then updating an existing
function fm_update_category($name, $catid = NULL, $groupid = 0) {
	global $USER;
	
	if ($catid != NULL) {
		$update->id = $catid;
		$update->name = $name;
		$update->timemodified = time();
		if (!update_record('fmanager_categories', $update)) {
			error(get_string("errnoupdate",'block_file_manager'));
		}
	} else {
		$new->name = $name;
		if ($groupid == 0){
			$new->owner = $USER->id;
			$new->ownertype = OWNERISUSER;
		} else {
			$new->owner = $groupid;
			$new->ownertype = OWNERISGROUP;
		}
		$new->timemodified = time();
		if (!insert_record('fmanager_categories', $new)) {
			error(get_string("errnoinsert",'block_file_manager'));
		}
	}
	return true;
}

/**
* get a category object array or a single category name
* @param mixed $catid if integer, get the category name for this id. If an array of ids, get the category object array
*/
function fm_get_user_categories($catid) {
	if (!is_array($catid)) {	
		if ($rec = get_record('fmanager_categories', 'id', $catid)){
		    return $rec->name;
		}
		return '';
	} else {
		$tmp = array();
		foreach ($catid as $cid) {
			$tmp[] = fm_get_user_categories($cid);
		}
		return $tmp;
	}
}

/**
* Gets the list of categories for the user or for the group
* @param $groupid Id of the group for which we need the categories'list. If 0 or NULL, then get the list of categories of the user
*/
function fm_get_cat_list($groupid=0) {
	global $USER;
	
	// $cats = array();
	$cats[0] = get_string('btnnoassigncat','block_file_manager');
	if ($groupid == 0){
		$ownertype = OWNERISUSER;
		$rs = get_recordset_select('fmanager_categories', "owner=$USER->id AND ownertype=$ownertype", 'name');
		$catsrec =  recordset_to_array($rs);	
	} else {
		$ownertype = OWNERISGROUP;
		$rs = get_recordset_select('fmanager_categories', "owner=$groupid AND ownertype=$ownertype", 'name');
		$catsrec =  recordset_to_array($rs);
	}
	if ($catsrec) {
    	foreach ($catsrec as $c) {
    		$cats[$c->id] = $c->name;
    	}
	}
	return $cats;
}

/************************* Shared Link Functions ****************************/
// Deletes all shared link entries associated with deleted link or with unsharing all
//	fm_update_shared_links($linkid)
// Returns an object of all links with the specified category (not including folders)
// 	fm_get_links_shared_by_cat($original, $catid)
// Returns an object of all folders with specified category
//	fm_get_folder_shared_by_cat($original,$catid)
// Returns an object of all links under specified folder
// 	fm_get_all_shared_by_folder($original, $foldid)
// Returns an object of all folders under specified folder
//  fm_get_all_sharedf_by_folder($original, $foldid)
/****************************************************************************/

// $linkid 		= id of deleted link
function fm_update_shared_links($linkid) {

	if ($sharedlinks = get_records('fmanager_shared', "sharedlink", $linkid)) {
		foreach ($sharedlinks as $sl) {		
			if (!delete_records('fmanager_shared', "id", $sl->id)) {
				error(get_string("errnoupdate",'block_file_manager'));
			}
		}
	}
}

// $linkid 		= id of deleted link
function fm_update_shared_cats($catid) {

	if ($sharedlinks = get_records('fmanager_shared', "sharedlink=$catid AND type", STYPE_CAT)) {
		foreach ($sharedlinks as $sl) {		
			if (!delete_records('fmanager_shared', "id", $sl->id)) {
				error(get_string("errnoupdate",'block_file_manager'));
			}
		}
	}
}

// $original	= owners id
// $catid		= category id num
function fm_get_links_shared_by_cat($original, $catid) {
	return get_records('fmanager_link',"owner = $original AND category",$catid);	
}
// $original 	= owners id
// $catid		= category id num
function fm_get_folder_shared_by_cat($original,$catid) {
	return get_records('fmanager_folders',"owner = $original AND category",$catid);

}
// $original 	= owners id
// $foldid 		= folder id num
function fm_get_all_shared_by_folder($original, $foldid) {
	$tmpvar = get_records('fmanager_link',"owner=$original AND folder",$foldid);

	return $tmpvar;	
}

// $original 	= owners id
// $foldid 		= folder id num
function fm_get_all_sharedf_by_folder($original, $foldid) {
	$tmpvar = get_records('fmanager_folders',"owner=$original AND pathid",$foldid);

	return $tmpvar;	
}
/**************************** Security Functions ****************************/
// Ensures the user can view shared files from specified user
// 	fm_user_has_shared($original, $link)
// Ensures the user can view the shared files 
//	fm_user_has_shared_ind($sid)
// Ensures the user can view the category for modification
// 	fm_user_owns_cat($catid)
// Ensures the user owns the link 
//	fm_user_owns_link($linkid)
// Ensures the user owns the folder 
// 	fm_user_owns_folder($folderid)
// Ensures the user can view the page according to settings set by admin
// 	fm_check_access_rights()
// Ensures user can view shared category
// 	fm_user_has_shared_cat($original, $catid)
// Ensures user can view shared folder
// 	fm_user_has_shared_folder($original, $foldid)
// Ensures user can view shared folder via category
//  fm_user_has_shared_folder_cat
// Ensures user can view a file from file.php (owner/shared/in a cat/under a folder)
//	fm_user_can_view_file($id, $fileid)
// Cleans checkbox array values
// fm_clean_checkbox_array($cb)
/****************************************************************************/

// $original = user who is sharing files to $USER
// $link 	 = link id of the file being checked
function fm_user_has_shared($original=0, $link=0, $ownertype) {
	global $USER, $CFG;

	if ($link == 0) {
		if (!$sharedfiles = (count_records('fmanager_shared', "owner", $original, "ownertype", $ownertype,"userid", $USER->id) + count_records('fmanager_shared', "owner", $original, "ownertype", $ownertype,"userid", 0))) {	
			error(get_string("errnoshared", 'block_file_manager'));
		}
	} else {
		if (!$sharedfiles = (count_records('fmanager_shared',"owner=$original AND ownertype", $ownertype,"userid",$USER->id,"sharedlink",$link) + count_records('fmanager_shared',"owner=$original AND ownertype", $ownertype,"userid",0,"sharedlink",$link))) {
			error(get_string("errnoshared",'block_file_manager'));
		}
	}
	return true;
}

// $sid		= Shared link id
function fm_user_has_shared_ind($sid) {
	global $USER;
	
	if (!$sharedfiles = count_records('fmanager_shared', 'id', $sid, 'userid', $USER->id)) {
		if (!count_records('fmanager_shared', 'id', $sid, 'userid', 0)) {
			error(get_string('errnoshared', 'block_file_manager'));
		}
	}
	return true;
}

/**
* @param int $catid	category id to check ownership on
* @uses USER
*/
function fm_user_owns_cat($catid) {
	global $USER;

	if ($catid == 0) {
		return true;
	}
	if (!$owncat = count_records('fmanager_categories', 'id', $catid, 'owner', $USER->id, "ownertype", OWNERISUSER)) {
		error(get_string('errdontowncat', 'block_file_manager'));
	} else {
		return true;
	}
}

/**
* @param int $catid	category id to check ownership on
* @param int $groupid	group id which is suppose to be the owner
*/
function fm_group_owns_cat($catid, $groupid) {
	if ($catid == 0) {
		return true;
	}
	if (!$owncat = count_records('fmanager_categories', 'id', $catid, 'owner', $groupid, "ownertype", OWNERISGROUP)) {
		error(get_string('errdontowncat', 'block_file_manager'));
	} else {
		return true;
	}
}

/**
* @param int $linkid id of the link to check ownership on
* @uses USER
*/
function fm_user_owns_link($linkid) {
	global $USER;
	
	if ($linkid == 0) {
		return true;
	}
	if (!$ownlink = count_records('fmanager_link', 'id', $linkid, 'owner', $USER->id, 'ownertype', OWNERISUSER)) {
		error(get_string('errdontownlink', 'block_file_manager'));
	} else {
		return true;
	}
}

/**
* @param int $linkid id of the link to check ownership on
* @param int $groupid id of the group that is supposed to be the owner
* 
*/
function fm_group_owns_link($linkid, $groupid) {
	
	if ($linkid == 0) {
		return true;
	}
	if (!$ownlink = count_records('fmanager_link', 'id', $linkid, 'owner', $groupid, 'ownertype', OWNERISGROUP)) {
		error(get_string('errdontownlink', 'block_file_manager'));
	} else {
		return true;
	}
}

/**
* @param int $folderid id of the folder to check ownership on
* @uses USER
*/
function fm_user_owns_folder($folderid) {
	global $USER;

	// Default directory...no folder selected
	if ($folderid == 0) {
		return true;
	}
	$ownertype = OWNERISUSER;
	if (!$ownfold = count_records('fmanager_folders', 'id', $folderid, 'owner', $USER->id, "ownertype", $ownertype)) {
		error(get_string('errdontownfolder', 'block_file_manager'));
	} else {
		return true;
	}
}

/**
* @param int $folderid id of the folder to check ownership on
* @param int $groupid id of the group that is supposed to be the owner
*/
function fm_group_owns_folder($folderid, $groupid) {
	// Default directory...no folder selected
	if ($folderid == 0) {
		return true;
	}
	$ownertype = OWNERISGROUP;
	if (!$ownfold = count_records('fmanager_folders', 'id', $folderid, 'owner', $groupid, "ownertype", $ownertype)) {
		error(get_string('errdontownfolder', 'block_file_manager'));
	} else {
		return true;
	}
}



/**
* @param int $id the course id
* @param boolean $chk1 flag : check if can share files
* @param boolean $chk2 flags to check 
*/
function fm_check_access_rights($id = 0, $chk1 = false, $chk2 = false) {
	$userinttype = fm_get_user_int_type();
	$tmp = get_record('fmanager_admin', 'usertype', $userinttype);
	if ($tmp->enable_fmanager == 0) {
		error(get_string('errfmandisabled', 'block_file_manager'), $SESSION->fromdiscussion);
	}
	// Checks if they can share files and if they can share from course 1
	if (($tmp->sharetoany == 0) && ($id == 1) && ($chk2 != false)) {
		error(get_string('errcantviewshared', 'block_file_manager', get_string('errfromthiscourse', 'block_file_manager')));
	}
	if (($chk1 != false) && ($tmp->allowsharing == 0)) {
		error(get_string('errcantviewshared' ,'block_file_manager'));
	}
}

/**
* @param int $original id of the person who is sharing
* @param int $categoryid id of the category being shared
* @uses USER, CFG
*/
function fm_user_has_shared_cat($original, $categoryid, $ownertype) {
	global $USER, $CFG;

	$select = "
	        ( userid = '0' AND 
	        owner = '{$original}' AND 
			ownertype = '{$ownertype}' AND 
	        type = '1' AND 
	        sharedlink = '{$categoryid}' ) OR 
	        ( userid = '{$USER->id}' AND 
	        owner = '{$original}' AND 
			ownertype = '{$ownertype}' AND 
	        type = '1' AND 
	        sharedlink = '{$categoryid}' )
	";
	if (!count_records_select('fmanager_shared',  $select)) {
		error(get_string('errnoviewcat', 'block_file_manager'));
	} else {
		return true;
	}
}

/**
* @param int $original id of the person who is sharing
* @param int $folderid id of the folder being shared
* @uses USER
*/
function fm_user_has_shared_folder($original, $folderid, $ownertype) {
	global $USER;
	
	if (fm_user_has_shared_folder_cat($original, $folderid, $ownertype)) {
		return true;
	} else {
		// Pulls out directly shared folder
		$select = "
		    ( userid = '0' AND 
		    owner = '{$original}' AND 
			ownertype = '{$ownertype}' AND 
		    type = '2' AND 
		    sharedlink = '{$folderid}' ) OR 
		    ( userid = '{$USER->id}' AND 
		    owner = '{$original}' AND 
			ownertype = '{$ownertype}' AND 
		    type = '2' AND 
		    sharedlink = '{$folderid}' )
		";
		if (!get_records_select('fmanager_shared', $select)) {
			// Checks if the folder is shared via a cascaded folder share
			$dummyvar = 1;
			$tmpid = $folderid;
			while($dummyvar) {
				$tmp = get_record('fmanager_folders', 'id', $tmpid);
				$select = "
				    ( userid = '0' AND 
				    owner = '{$original}' AND 
					ownertype = '{$ownertype}' AND 
				    type = '2' AND 
				    sharedlink = '{$tmp->pathid}' ) OR 
				    ( userid = '{$USER->id}' AND 
				    owner = '{$original}' AND 
					ownertype = '{$ownertype}' AND 
				    type = '2' AND 
				    sharedlink = '{$tmp->pathid}' )
				";
				if (get_records_select('fmanager_shared', $select)) {
					$dummyvar = 0;
				} else {
					$tmpid = $tmp->pathid;
					if ($tmpid == 0) {		// If root folder is reached and it isnt shared...check if shared via categories
						error(get_string('errnoviewfold', 'block_file_manager'));
					}
				}
			}
		} else {
			return true;
		}
	}
}

/**
* @param int $original id of the person who is sharing
* @param int $foldid id of the category being shared
* @uses USER
*/
function fm_user_has_shared_folder_cat($original, $folderid, $ownertype) {
	global $USER;
	
	// infinite loop possibility. Check this case
	while (1) {
		$folder = get_record('fmanager_folders', 'id', $folderid);
		$select = "
		     ( userid = '0' AND 
		     owner = '{$original}' AND 
			 ownertype = '{$ownertype}' AND 
		     type = '1' AND 
		     sharedlink = '{$folder->category}' ) OR 
		     ( userid = '{$USER->id}' AND 
		     owner = '{$original}' AND 
			 ownertype = '{$ownertype}' AND 
		     type = '1' AND 
		     sharedlink = '{$folder->category}' )
		";
		if (get_records_select('fmanager_shared', $select)) {
			$dummyvar = 0;
			return true;
		} else if ( $folder->pathid == 0 ) {
			return false;
		} else {
			$folderid = $folder->pathid;
		}
	}
	return false;
}

// $id 		= courseid
// $fileid	= id of file to be viewed
function fm_user_can_view_file($id, $fileid, $groupid) {
	global $USER, $CFG;
			
	// If they own the file, they can view
	if ($groupid == 0){
		$ownertype = OWNERISUSER;
		if (count_records('fmanager_link', 'id', $fileid, 'owner', $USER->id, 'ownertype', $ownertype)) {  
		return true;
		}	
	} else {
		$ownertype = OWNERISGROUP;
		if (count_records('fmanager_link', 'id', $fileid, 'owner', $groupid, 'ownertype', $ownertype)) {  
		return true;
		}	
	}
	
	
	// If file is shared to them or to everyone in the course they can view
	$select = " 
	    ( userid = '0' AND 
	    sharedlink = '{$fileid}' AND 
	    course = '{$id}' ) OR 
	    ( userid = '{$USER->id}' AND 
	    sharedlink = '{$fileid}' AND 
	    course = '{$id}'
	)";
	if (count_records_select('fmanager_shared', $select)) {
		return true;
	}
	if (!$sharedfile = get_record('fmanager_link', 'id', $fileid)) {
		return false;
	} 

	// If shared via shared/nested folder they can view
	$select = " 
	    ( userid = '0' AND 
	    type = '2' AND 
	    course = '{$id}' AND 
	    sharedlink = '{$sharedfile->folder}' ) OR 
	    ( userid = '{$USER->id}' AND 
	    type = '2' AND 
	    course = '{$id}' AND 
	    sharedlink = '{$sharedfile->folder}'
	)";
	if (count_records_select('fmanager_shared', $select)) {
		return true;
	} else {   
	    // File isnt under the main folder
		$tmp = get_record('fmanager_folders', 'id', $sharedfile->folder);
		if (isset($tmp->pathid)){
			$folderid = $tmp->pathid;
			while ($folderid) {	// While folder id isnt the root (0)
				$select = "
				    ( userid = '0' AND 
				    type = '2' AND 
				    course = '{$id}' AND 
				    sharedlink = '{$folderid}' ) OR 
				    ( userid = '{$USER->id}' AND 
				    type = '2' AND 
				    course = '{$id}' AND 
				    sharedlink = '{$folderid}'
			    )";
				if (count_records_select('fmanager_shared', $select)) {
					return true;
				}
				$tmp = get_record('fmanager_folders', 'id', $tmp->pathid);
				$folderid = $tmp->id;
			}
		}
	}

	// If file is shared via a shared category they can view
	$dummyvar = 1;
	$fid = $sharedfile->folder;
	if ($fid == 0) {
		$select = " 
		    ( userid = '0' AND 
		    type = '1' AND 
		    course = '{$id}' AND 
		    sharedlink = '{$sharedfile->category}' ) OR 
		    ( userid = '{$USER->id}' AND 
		    type = '1' AND 
		    course = '{$id}' AND 
		    sharedlink = '{$sharedfile->category}'
		)";
		if (count_records_select('fmanager_shared', $select)) {
			return true;
		} else {
			return false;
		}
	}
	while ($fid) {	
		$folder = get_record('fmanager_folders', 'id', $fid);
		
		$select = "
		    ( userid = '0' AND 
		    type = '1' AND 
		    course = '{$id}' AND 
		    sharedlink = '{$folder->category}' ) OR 
		    ( userid = '{$USER->id}' AND 
		    type = '1' AND 
		    course = '{$id}' AND 
		    sharedlink = '{$folder->category}' )
		";
		if (count_records_select('fmanager_shared', $select)) {
			return true;
		} else {
			$fid = $folder->pathid;
		}
	}
	// Otherwise, error is displayed
	return false;
}

/**
 * $cb 	= Checkbox array
 */
function fm_clean_checkbox_array() {
	$cb = optional_param('cb', array(), PARAM_RAW);
	//echo $cb;
	if (is_array($cb)) {
		$tmp = array();
		foreach($cb as $c) {
			if(substr($c,0,4) == 'fold') {
				$tmp[] = 'f-'.(int)substr($c, 4);
			} else if (substr($c,0,2) == "f-") {
				$tmp[] = 'f-'.(int)substr($c, 2);
			} else {
				$tmp[] = (int)$c;
			}
		}
		$cb = $tmp;
	} else {
		if (substr($cb, 0, 4) == 'fold') {
			$cb[] = 'f-'.(int)substr($cb, 4);
		} else if (substr($cb, 2) == 'f-') {
			$cb[] = 'f-'.(int)substr($cb, 2);
		} else {
			//$cb[] = (int)$cb;
			//$singlecb = NULL;
			//$cb[] = $singlecb;
		}
	}	
	// Removes 0 as default
	$nullify = false;
	foreach ($cb as $c) {
		if ($c == 0 && substr($c, 0, 2) != 'f-') {
			$nullify = true;
		} else {	
			$nullify = false;
		}
	}
	if ($nullify == true) {
		$cb = NULL;
	}
	
	return $cb;
}

/*************************** Database Functions *****************************/
// This function processes the submission of yes/no to the conf_delete form & returns strings
//	fm_process_del($delfrom, $fromid)
// This function deletes a shared attribute of a file for a user
// 	fm_del_shared($id)
// This func processes the submission from the admin_settings.php form
//	fm_process_admin_settings($entry)
// Returns the user's integer representation of their type (0=admin, 1=teacher, 2=student)
// 	fm_get_user_int_type()
/****************************************************************************/
	
// $delfrom		= table to delete record from
// $fromid		= id(s) of the record(s) to delete
function fm_process_del($delfrom, $fromid) {
	global $USER;
	
	if (isset($_POST['yesdel'])) {
		if (is_array($fromid)) {
			foreach($fromid as $id) {
				if (!delete_records($delfrom, 'id', $id)) {
					error(get_string('errnodelete', 'block_file_manager'));
				}
			} 
		} else {
			if (!delete_records($delfrom, 'id', $fromid)) {
				error(get_string('errnodelete', 'block_file_manager'));
			}
		}
		return true;
	} else if (isset($_POST['nodel'])) {
		return true;
	}
}

// $id 	= id of shared files to be removed
function fm_del_shared($ids) {
	global $USER;
	
	if (isset($_POST['yesdel'])) {
		if (is_array($ids)) {
			foreach($ids as $id) {
				// Ensures they can even view file to delete permission to view it
				if ($id != 0) {
					delete_records('fmanager_shared', 'id', $id);
				}
			}
		}
		return true;
	} else if (isset($_POST['nodel'])) {
		return true;
	}
}

// $entry 		= object that contains all form data ($entry->usertype and any data required)
function fm_process_admin_settings($entry) {
	// Deals with all processing from the files section of admin
	if (!$exist_record = get_record('fmanager_admin', 'usertype', $entry->usertype)) {
		$tmp = NULL;
		$tmp->usertype = $entry->usertype;
		if (!insert_record('fmanager_admin', $tmp)) {
			$err = NULL;
			$err->errtype = "insert";
			$err->forhwho ="user";
			error(get_string('errrecordmod', 'block_file_manager', $err), $_SERVER['HTTP_REFERER']);
		}
	}
	if ($exist_record = get_record('fmanager_admin', 'usertype', $entry->usertype)) {
		$entry->id = $exist_record->id;
		if (!update_record('fmanager_admin', $entry)) {
			$err = NULL;
			$err->errtype = 'update';
			$err->forwho = 'user';
			error(get_string('errrecordmod', 'block_file_manager', $err), $_SERVER['HTTP_REFERER']);
		}
	}
}

function fm_get_user_int_type() {
    $systemcontext = get_context_instance(CONTEXT_SYSTEM, 0);
	if (has_capability('moodle/site:doanything', $systemcontext)) {
		$usertype = 0;
	} else if (isteacherinanycourse('0', false)) {
		$usertype = 1;
	} else {
		$usertype = 2;
	}
	return $usertype;
}

/*************************** Zip Functions **********************************/
// 	fm_zip_files($originalfiles, $destination)
//  fm_unzip_file($zipfile, $destination = '', $showstatus = true)
// Reorders a list of unzipped objects with folders first 
//  fm_reorder_folders_list($obj1, $obj2)
//  fm_unzip_cleanfilename($p_event, &$p_header)
//  fm_unzip_show_status($list, $removepath)
// Shows contents of target zipped file
//  fm_view_zipped($file)
/****************************************************************************/
function fm_zip_files ($originalfiles, $destination, $zipname="zip01", $rootdir=0, $groupid=0) {
//Zip an array of files/dirs to a destination zip file
//Both parameters must be FULL paths to the files/dirs
// Modded for Myfiles 
// Michael Avelar 1/19/06

    global $CFG, $USER;

    //Extract everything from destination
    $path_parts = pathinfo(cleardoubleslashes($destination));
    $destpath = $path_parts['dirname'];       //The path of the zip file
    $destfilename = $path_parts['basename'];  //The name of the zip file
	// To put the zipped file into the current directory
	$destpath = $destpath."/".$destfilename;
	// To allow naming of zipfiles
	$destfilename = $zipname;
    //If no file, error
    if (empty($destfilename)) {
        return false;
    }

    //If no extension, add it
    if (empty($path_parts['extension'])) {
        $extension = 'zip';
        $destfilename = $destfilename.'.'.$extension;
    } else {
        $extension = $path_parts['extension'];    //The extension of the file
    }

    //Check destination path exists
    if (!is_dir($destpath)) {
        return false;
    }

    //Check destination path is writable. TODO!!

    //Clean destination filename
    $destfilename = clean_filename($destfilename);

    //Now check and prepare every file
    $files = array();
    $origpath = NULL;

    foreach ($originalfiles as $file) {  //Iterate over each file
        //Check for every file
        $tempfile = cleardoubleslashes($file); // no doubleslashes!
        //Calculate the base path for all files if it isn't set
        if ($origpath === NULL) {
            $origpath = rtrim(cleardoubleslashes(dirname($tempfile)), "/");
        }
        //See if the file is readable
        if (!is_readable($tempfile)) {  //Is readable
            continue;
        }
        //See if the file/dir is in the same directory than the rest
        if (rtrim(cleardoubleslashes(dirname($tempfile)), "/") != $origpath) {
            continue;
        }
        //Add the file to the array
        $files[] = $tempfile;
    }

    //Everything is ready:
    //    -$origpath is the path where ALL the files to be compressed reside (dir).
    //    -$destpath is the destination path where the zip file will go (dir).
    //    -$files is an array of files/dirs to compress (fullpath)
    //    -$destfilename is the name of the zip file (without path)

    //print_object($files);                  //Debug

    if (empty($CFG->zip)) {    // Use built-in php-based zip function

        include_once("$CFG->libdir/pclzip/pclzip.lib.php");
        $archive = new PclZip(cleardoubleslashes("$destpath/$destfilename"));
        if (($list = $archive->create($files, PCLZIP_OPT_REMOVE_PATH,$origpath) == 0)) {
            notice($archive->errorInfo(true));
            return false;
        }

    } else {                   // Use external zip program

        $filestozip = "";
        foreach ($files as $filetozip) {
            $filestozip .= escapeshellarg(basename($filetozip));
            $filestozip .= " ";
        }
        //Construct the command
        $separator = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? ' &' : ' ;';
        $command = 'cd '.escapeshellarg($origpath).$separator.
                    escapeshellarg($CFG->zip).' -r '.
                    escapeshellarg(cleardoubleslashes("$destpath/$destfilename")).' '.$filestozip;
        //All converted to backslashes in WIN
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = str_replace('/','\\',$command);
        }
        Exec($command);
    }
	// Adds an entry into myfiles api
	$newentry = NULL;
	if($groupid == 0){
		$newentry->owner = $USER->id;
	}else{
		$newentry->owner = $groupid;
	}
	$newentry->type = TYPE_ZIP;
	$newentry->folder = $rootdir;
	$newentry->category = 0;
	$newentry->name = substr($destfilename,0,-4);
	$newentry->description = '';
	$newentry->link = $destfilename;
	$newentry->timemodified = time();
	if (!fm_update_link($newentry,$groupid)) {
		error(get_string("errnoupdate",'block_file_manager'));
	}
    return true;
}

function fm_unzip_file ($zipfile, $destination = '', $showstatus = false, $currentdir=0, $groupid) {
//Unzip one zip file to a destination dir
//Both parameters must be FULL paths
//If destination isn't specified, it will be the
//SAME directory where the zip file resides.
// Modded for Myfiles 
// Michael Avelar 1/24/06 
    global $CFG, $USER;

    //Extract everything from zipfile
    $path_parts = pathinfo(cleardoubleslashes($zipfile));
    $zippath = $path_parts["dirname"];       //The path of the zip file
    $zipfilename = $path_parts["basename"];  //The name of the zip file
    $extension = $path_parts["extension"];    //The extension of the file

    //If no file, error
    if (empty($zipfilename)) {
        return false;
    }

    //If no extension, error
    if (empty($extension)) {
        return false;
    }

    //If no destination, passed let's go with the same directory
    if (empty($destination)) {
        $destination = $zippath;
    }

    //Clear $destination
    $destpath = rtrim(cleardoubleslashes($destination), "/");

    //Check destination path exists
    if (!is_dir($destpath)) {
        return false;
    }

    //Check destination path is writable. TODO!!

    //Everything is ready:
    //    -$zippath is the path where the zip file resides (dir)
    //    -$zipfilename is the name of the zip file (without path)
    //    -$destpath is the destination path where the zip file will uncompressed (dir)

    if (empty($CFG->unzip)) {    // Use built-in php-based unzip function

        include_once("$CFG->libdir/pclzip/pclzip.lib.php");
        $archive = new PclZip(cleardoubleslashes("$zippath/$zipfilename"));
        if (!$list = $archive->extract(PCLZIP_OPT_PATH, $destpath,
                                       PCLZIP_CB_PRE_EXTRACT, 'unzip_cleanfilename')) {
            notice($archive->errorInfo(true));
            return false;
        }

    } else {                     // Use external unzip program

        $separator = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? ' &' : ' ;';
        $redirection = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? '' : ' 2>&1';

        $command = 'cd '.escapeshellarg($zippath).$separator.
                    escapeshellarg($CFG->unzip).' -o '.
                    escapeshellarg(cleardoubleslashes("$zippath/$zipfilename")).' -d '.
                    escapeshellarg($destpath).$redirection;
        //All converted to backslashes in WIN
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = str_replace('/','\\',$command);
        }
        Exec($command,$list);
    }
	// Inserts unzipped objects into myfiles api
	$afolder = $currentdir;
	// Reorders list so that folders are inserted into database before links
	$list2 = array();
	foreach($list as $l) {
		if ($l['folder'] == 1) {
			$list2[] = $l;
		}
	}
	// Reorders folders list to extract them in order, so the database has the folder before
	// inserting stuff into that folder

	usort($list2, 'fm_reorder_folders_list');
	// Recompiles the completed list...with ordered folders first, then files
	foreach($list as $l) {
		if ($l['folder'] != 1) {
			$list2[] = $l;
		}
	}
	foreach($list2 as $l) {
		if ($l['folder'] == 1) {	// is an unzipped folder
			$newfolder = NULL;
			
			if($groupid ==0){
				$newfolder->owner = $USER->id;
			}else{
				$newfolder->owner = $groupid;
			}
			
			$tmp = explode('/',$l['stored_filename']);
			// compiles the path of the folder
			$tmppath = '/';
			foreach ($tmp as $t) {
				if ($t != '') {
					$tmppath .= $t."/";
				}
			}
			$tmp = array_reverse($tmp);
			$tmppath = substr($tmppath, 0, (strrpos($tmppath, $tmp[1])));
			$newfolder->name = $tmp[1];
			$newfolder->category = 0;
			if ($tmp[2] == NULL) {			// Folder is root folder
				$newfolder->path = '/';
				$newfolder->pathid = 0;
			} else {
				// Puts all folders in their place :)
				$pathtmp = '/';
				$count = count($tmp);
				while ($count > 3) {
					$pathtmp .= $tmp[$count - 1].'/';
					$count--;
				}
				$rec = get_record('fmanager_folders',"name",$tmp[2],"path",$pathtmp);
				$afolder = $rec->id;
				$newfolder->path = fm_get_folder_path($afolder, false, $groupid)."/";
				$newfolder->pathid = $afolder;
			}
			$newfolder->timemodified = time();
			if (!$afolder = insert_record('fmanager_folders',$newfolder)) {
				error(get_string('errnoinsert','block_file_manager'));
			}
		} else {
			$tmp = explode('/', $l['stored_filename']);
			$newlink = NULL;
			if($groupid ==0){	
				$newlink->owner = $USER->id;
			}else{
				$newlink->owner = $groupid;
			}
			$newlink->type = TYPE_FILE;	
			$rootlinkstr = '';
			// Compiles path string
			$count = count($tmp);
			$count = $count - 2;
			foreach($tmp as $t) {
				if ($count > 0) {
					if ($t != '') {
						$rootlinkstr .= '/'.$t;
					}
					$count--;
				}
			} 
			if (substr($rootlinkstr,0,1) != '/') {
				$rootlinkstr = '/'.$rootlinkstr;
			}
			if ($rootlinkstr != '/') {
				$rootlinkstr = $rootlinkstr.'/';
			}
			$tmp = array_reverse($tmp);
			if($groupid==0){
				$owner = $USER->id;
				$newlink->ownertype = 0;
			}else{
				$owner = $groupid;
				$newlink->ownertype = 1;
			}
			if (!$foldstr = get_record('fmanager_folders',"owner",$owner,"name",$tmp[1],"path",$rootlinkstr)) {
				$foldstr->id = $currentdir;
			}			
			
			$newlink->folder = $foldstr->id;
			$newlink->category = 0;
			$newlink->name = substr($tmp[0],0,-4);
			$newlink->description = '';
			$newlink->link = $tmp[0];
			$newlink->timemodified = time();
			if (!$alink = insert_record('fmanager_link',$newlink)) {
				error(get_string('errnoinsert','block_file_manager'));
			}
		}	
	}
    //Display some info about the unzip execution
    if ($showstatus) {
        fm_unzip_show_status($list,$destpath);
    }

    return $list;
}

// $list = list of the folders to be reordered
function fm_reorder_folders_list($obj1, $obj2) {
	$size1 = explode("/",$obj1['stored_filename']);
	$size2 = explode("/",$obj2['stored_filename']);

	if (count($size1) == count($size2)) {
		return 0;
	}
	if (count($size1) > count($size2)) {
		return 1;
	}
	if (count($size1) < count($size2)) {
		return -1;
	}
}

function fm_unzip_cleanfilename ($p_event, &$p_header) {
//This function is used as callback in unzip_file() function
//to clean illegal characters for given platform and to prevent directory traversal.
//Produces the same result as info-zip unzip.
    $p_header['filename'] = ereg_replace('[[:cntrl:]]', '', $p_header['filename']); //strip control chars first!
    $p_header['filename'] = ereg_replace('\.\.+', '', $p_header['filename']); //directory traversal protection
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $p_header['filename'] = ereg_replace('[:*"?<>|]', '_', $p_header['filename']); //replace illegal chars
        $p_header['filename'] = ereg_replace('^([a-zA-Z])_', '\1:', $p_header['filename']); //repair drive letter
    } else {
        //Add filtering for other systems here
        // BSD: none (tested)
        // Linux: ??
        // MacosX: ??
    }
    $p_header['filename'] = cleardoubleslashes($p_header['filename']); //normalize the slashes/backslashes
    return 1;
}

function fm_unzip_show_status ($list,$removepath) {
//This function shows the results of the unzip execution
//depending of the value of the $CFG->zip, results will be
//text or an array of files.

    global $CFG;

    if (empty($CFG->unzip)) {    // Use built-in php-based zip function
        $strname = get_string("name");
        $strsize = get_string("size");
        $strmodified = get_string("modified");
        $strstatus = get_string("status");
        echo "<table cellpadding=\"4\" cellspacing=\"2\" border=\"0\" width=\"640\">";
        echo "<tr><th class=\"header\" align=\"left\">$strname</th>";
        echo "<th class=\"header\" align=\"right\">$strsize</th>";
        echo "<th class=\"header\" align=\"right\">$strmodified</th>";
        echo "<th class=\"header\" align=\"right\">$strstatus</th></tr>";
        foreach ($list as $item) {
            echo "<tr>";
            $item['filename'] = str_replace(cleardoubleslashes($removepath).'/', "", $item['filename']);
            print_cell("left", $item['filename']);
            if (! $item['folder']) {
                print_cell("right", display_size($item['size']));
            } else {
                echo "<td>&nbsp;</td>";
            }
            $filedate  = userdate($item['mtime'], get_string("strftimedatetime"));
            print_cell("right", $filedate);
            print_cell("right", $item['status']);
            echo "</tr>";
        }
        echo "</table>";

    } else {                   // Use external zip program
        print_simple_box_start("center");
        echo "<pre>";
        foreach ($list as $item) {
            echo str_replace(cleardoubleslashes($removepath.'/'), '', $item).'<br />';
        }
        echo "</pre>";
        print_simple_box_end();
    }
}

// $file = object of target zip file
function fm_view_zipped($file, $groupid) {
	global $CFG, $USER;
	
	if ($file->folder == 0) {
		if ($groupid == 0){
			$ziploc = $CFG->dataroot."/".fm_get_user_dir_space();//."/".$file->link;
		} else {
			$ziploc = $CFG->dataroot."/".fm_get_group_dir_space($groupid);//."/".$file->link;
		}
	} else {
		if ($groupid == 0){
			$ziploc = $CFG->dataroot."/".fm_get_user_dir_space().fm_get_folder_path($file->folder, false, $goupid);//."/".$file->link;
		} else {
			$ziploc = $CFG->dataroot."/".fm_get_group_dir_space($groupid).fm_get_folder_path($file->folder, false, $goupid);//."/".$file->link;
		}
	}

	$filelist = array();
	$zip = zip_open($ziploc);
	if ($zip) {
		$count = 0;
		while ($zip_entry = zip_read($zip)) {
		   $filelist[$count]->name = zip_entry_name($zip_entry);
		   $filelist[$count]->actualsize = zip_entry_filesize($zip_entry);
		   $filelist[$count]->compsize = zip_entry_compressedsize($zip_entry);
		   $count++;
		}
		zip_close($zip);
	}
	return $filelist;
}

/*************************** Print Functions ********************************/
// Located within print_lib.php
/****************************************************************************/

/*************************** JS Print Functions *****************************/
// Located within print_lib.php
/****************************************************************************/



function shared_files_assignment_file_submission($entry, $assignment) {
/// Copies a file to the teacher's assignment directory for that course
	global $CFG, $USER;
	$pathnew = "$assignment->course/moddata/assignment/$assignment->id/$USER->id";
	$pathold = "$CFG->dataroot/shared_files/users/$entry->userid";

	make_upload_directory($pathnew);

	if (!copy("$pathold/$entry->attachment", "$CFG->dataroot/$pathnew/$entry->attachment")) {
		error ("Failed to copy file!", "view.php?id={$id}&rootdir={$rootdir}");
	}
}


// When called, outputs all groups and members of a course, using $listmembers and $listgroups defined in 
// call to get_list_members()
function show_visible_groups($course, $listmembers, $listgroups, $filerefphp) {	
	// Print out the selection boxes and fill with groups/members
	echo "<tr><td width=\"45%\" align=\"center\"><form name=\"form2\" id=\"form2\">
		<input type=\"hidden\" name=\"id\" value=\"$course->id\"><select name=\"groups\" size=\"15\" onChange=\"updateMembers(this)\" multiple>";
	if (!empty($listgroups)) {
		foreach ($listgroups as $id => $listgroup) {
			$selected = '';
			if ($id == $selectedgroup) {
			   $selected = 'selected="selected"';
			}
			echo "<option $selected value=\"$id\">$listgroup</option>";
		}
	}
	echo "</select></form></td>";
	$sharestring = get_string('share', 'block_shared_files');
	echo "<td width=\"10%\" align=\"center\"><form name=\"formx\" id=\"formx\" method=\"post\" action=\"$filerefphp\">";
	echo "<input type=\"hidden\" name=\"grpid\" value=\"\"><INPUT TYPE=\"hidden\" NAME=\"bookid\" VALUE=\"$bookid\"><input type=\"hidden\" name=\"id\" value=\"$course->id\"><input name=\"gshare\" type=\"submit\" value=\"$sharestring\"></form></td>";	
	echo "<td width=\"45%\" align=\"center\"><form name=\"form3\" id=\"form3\">
          <input type=\"hidden\" name=\"id\" value=\"$course->id\">
          <select name=\"members[]\" size=\"15\">";
    if (!empty($members)) {
    	foreach ($members as $id => $membername) {
        	echo "<option value=\"$id\">$membername</option>";
    	}
	}
    echo "</select></form></td></tr>";
}

// called from share & sharefile.php to populate arrays passed by reference to pop js lists
function get_list_members($course, &$nonmembers, &$listgroups)
{
/// First, get everyone into the nonmembers array
    if ($students = get_course_students($course->id)) {
        foreach ($students as $student) {
            $nonmembers[$student->id] = fullname($student, true);
        }
        unset($students);
    }

    if ($teachers = get_course_teachers($course->id)) {
        foreach ($teachers as $teacher) {
            $prefix = '- ';
            if (isteacheredit($course->id, $teacher->id)) {
                $prefix = '# ';
            }
            $nonmembers[$teacher->id] = $prefix.fullname($teacher, true);
        }
        unset($teachers);
    }

/// Pull out all the members into little arrays
	$groups = get_groups($course->id);
    if ($groups) {
        foreach ($groups as $group) {
            $countusers = 0;
            $listmembers[$group->id] = array();
            if ($groupusers = get_group_users($group->id)) {
                foreach ($groupusers as $groupuser) {
                    $listmembers[$group->id][$groupuser->id] = $nonmembers[$groupuser->id];
                    //unset($nonmembers[$groupuser->id]);
                    $countusers++;
                }
                natcasesort($listmembers[$group->id]);
            }
            $listgroups[$group->id] = $group->name." ($countusers)";
        }
        natcasesort($listgroups);
    }

    natcasesort($nonmembers);
	if (empty($selectedgroup)) {    // Choose the first group by default
        if (!empty($listgroups) && ($selectedgroup = array_shift(array_keys($listgroups)))) {
            $members = $listmembers[$selectedgroup];
        }
    } else {
        $members = $listmembers[$selectedgroup];
    }
	return $listmembers;
}

/*********************** Moodle Required Functions **************************/
//	upgrade_file_manager_db($continueto)  								
/****************************************************************************/

// This function upgrades the file_manager's tables, if necessary
// It's called from admin/index.php
function upgrade_file_manager_db($continueto) {
    
    global $CFG, $db;
	$fmdir = fm_get_root_dir();

    require_once ("$CFG->dirroot/$fmdir/version.php");  // Get code versions

    if (empty($CFG->file_manager_version)) {                  // file_manager has never been installed.
        $strdatabaseupgrades = get_string("databaseupgrades");
        print_header($strdatabaseupgrades, $strdatabaseupgrades, $strdatabaseupgrades, 
                         "", "", false, "&nbsp;", "&nbsp;");

        $db->debug=true;
        if (modify_database("$CFG->dirroot/$fmdir/db/$CFG->dbtype.sql")) {
            $db->debug = false;
            if (set_config("file_manager_version", $file_manager_version) and set_config("file_manager_release", $file_manager_release)) {
                notify(get_string("databasesuccess"), "green");
                print_continue($continueto);
                exit;
            } else {
                error("Upgrade of file_manager system failed! (Could not update version in config table)", "view.php?id={$id}&rootdir={$rootdir}");
            }
        } else {
            error("file_manager tables could NOT be set up successfully!", "view.php?id={$id}&rootdir={$rootdir}");
        }
    }

    if ($file_manager_version > $CFG->file_manager_version) {       // Upgrade tables
        $strdatabaseupgrades = get_string("databaseupgrades");
        print_header($strdatabaseupgrades, $strdatabaseupgrades, $strdatabaseupgrades);

        require_once ("$CFG->dirroot/$fmdir/db/$CFG->dbtype.php");

        $db->debug=true;
        if (file_manager_upgrade($CFG->file_manager_version)) {
            $db->debug=false;
            if (set_config("file_manager_version", $file_manager_version) and set_config("file_manager_release", $file_manager_release)) {
                notify(get_string("databasesuccess"), "green");
                notify(get_string("databaseupgradebackups", "", $file_manager_release));
                print_continue($continueto);
                exit;
            } else {
                error("Upgrade of file_manager system failed! (Could not update version in config table)", "view.php?id={$id}&rootdir={$rootdir}");
            }
        } else {
            $db->debug=false;
            error("Upgrade failed!  See file_manager/version.php", "view.php?id={$id}&rootdir={$rootdir}");
        }

    } else if ($file_manager_version < $CFG->file_manager_version) {
        notify("WARNING!!!  The code you are using ($file_manager_version) is OLDER than the version that made the current database ($CFG->file_manager_version)!");
    }
}		 

?>

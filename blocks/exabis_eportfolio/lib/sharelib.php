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

function block_exabis_eportfolio_get_external_view_url(stdClass $view)
{
	global $CFG, $USER;
	return $CFG->wwwroot.'/blocks/exabis_eportfolio/shared_view.php?access=hash/'.$USER->id.'-'.$view->hash;
}

function block_exabis_eportfolio_get_user_from_access($access)
{
	global $CFG, $USER;

	$accessPath = explode('/', $access);
	if (count($accessPath) != 2)
		return;
	
	if ($accessPath[0] == 'hash') {
		$hash = $accessPath[1];
		
		if (!$portfolioUser = get_record("block_exabeporuser", "user_hash", $hash)) {
			// no portfolio user with this hash
			return;
		}

		if (!$user = get_record("user", "id", $portfolioUser->user_id)) {
			// user not found
			return;
		}

		// keine rechte überprüfung, weil über den hash user immer erreichbar ist aber nur die geshareten items angezeigt werden
		// vielleicht in zukunft eine externaccess eingenschaft für den user einfügen?

		$user->access = new stdClass();
		$user->access->request = 'extern';
		return $user;
	} elseif ($accessPath[0] == 'id') {
		// guest not allowed
		// require exabis_eportfolio:use -> guest hasn't this right
		$context = get_context_instance(CONTEXT_SYSTEM);
		require_capability('block/exabis_eportfolio:use', $context);

		$userid = $accessPath[1];
		
		if (!$portfolioUser = get_record("block_exabeporuser", "user_id", $userid)) {
			// no portfolio user with this id
			return;
		}

		if (!$user = get_record("user", "id", $portfolioUser->user_id)) {
			// user not found
			return;
		}

		// no more checks needed

		$user->access = new stdClass();
		$user->access->request = 'intern';
		return $user;
	}
}


function block_exabis_eportfolio_get_view_from_access($access)
{
	global $CFG, $USER;

	if (block_exabis_eportfolio_get_active_version() < 3) {
		// only allowed since version 3
		return;
	}

	$accessPath = explode('/', $access);
	if (count($accessPath) != 2)
		return;

	$view = null;
	
	if ($accessPath[0] == 'hash') {
		$hash = $accessPath[1];
		$hash = explode('-', $hash);

		if (count($hash) != 2)
			return;

	    $userid = clean_param($hash[0], PARAM_INT);
	    $hash =  clean_param($hash[1], PARAM_ALPHANUM);
		//$userid = $hash[0];
		//$hash = $hash[1];

		if (empty($userid) || empty($hash)) {
			return;
		}

		if (!$view = get_record("block_exabeporview", "userid", $userid, "hash", $hash, "externaccess", 1)) {
			// view not found
			return;
		}

		$view->access = new stdClass();
		$view->access->request = 'extern';
	} elseif ($accessPath[0] == 'id') {
		// guest not allowed
		// require exabis_eportfolio:use -> guest hasn't this right
		$context = get_context_instance(CONTEXT_SYSTEM);
		require_capability('block/exabis_eportfolio:use', $context);

		$hash = $accessPath[1];
		$hash = explode('-', $hash);

		if (count($hash) != 2)
			return;
	
	    $userid = clean_param($hash[0], PARAM_INT);
	    $viewid =  clean_param($hash[1], PARAM_INT);
		//$userid = $hash[0];
		//$viewid = $hash[1];
		
		$view = get_record_sql("SELECT v.* FROM {$CFG->prefix}block_exabeporview v".
							" LEFT JOIN {$CFG->prefix}block_exabeporviewshar vshar ON v.id=vshar.viewid AND vshar.userid='".$USER->id."'".
							" WHERE v.userid='".$userid."' AND v.id='".$viewid."' AND".
							" ((v.userid='".$USER->id."')". // myself
							"  OR (v.shareall=1)". // shared all
							"  OR (v.shareall=0 AND vshar.userid IS NOT NULL))"); // shared for me

		if (!$view) {
			// view not found
			return;
		}

		$view->access = new stdClass();
		$view->access->request = 'intern';
	}
	
	return $view;
}

function block_exabis_eportfolio_get_item($itemid, $access)
{
	global $CFG, $USER;

	$itemid = clean_param($itemid, PARAM_INT);
	
	$item = null;
	if (preg_match('!^view/(.+)$!', $access, $matches)) {
		// in view mode

		if (!$view = block_exabis_eportfolio_get_view_from_access($matches[1])) {
			print_error("viewnotfound", "block_exabis_eportfolio");
		}
		if (!$viewblock = get_record("block_exabeporviewblock", "viewid", $view->id, "type", "item", "itemid", $itemid)) {
			// item not linked to view -> no rights
		}

		if (!$item = get_record("block_exabeporitem", "id", $itemid, "userid", $view->userid)) {
			// item not found
			return;
		}

		$item->access = $view->access;
		$item->access->page = 'view';

		// comments allowed?
		if ($item->access->request == 'extern') {
			$item->allowComments = false;
			$item->showComments = $view->externcomment;
			// TODO: comments anhand view einstellung zeigen
		} else {
			$item->allowComments = true;
			$item->showComments = true;
		}

	} elseif (preg_match('!^portfolio/(.+)$!', $access, $matches)) {
		// in user portfolio mode

		if (!$user = block_exabis_eportfolio_get_user_from_access($matches[1])) {
			return;
		}

		if ($user->access->request == 'extern') {
			if (!$item = get_record("block_exabeporitem", "id", $itemid, "userid", $user->id, "externaccess", 1)) {
				// item not found
				return;
			}
		} else {
			// intern

			$item = get_record_sql("SELECT i.* FROM {$CFG->prefix}block_exabeporitem i".
								" LEFT JOIN {$CFG->prefix}block_exabeporitemshar ishar ON i.id=ishar.itemid AND ishar.userid={$USER->id}".
								" WHERE i.id='".$itemid."' AND".
								" ((i.userid='".$USER->id."')". // myself
								"  OR (i.shareall=1 AND ishar.userid IS NULL)". // all and ishar not set?
								"  OR (i.shareall=0 AND ishar.userid IS NOT NULL))"); // nobody, but me

			if (!$item) {
				// item not found
				return;
			}
		}

		$item->access = $user->access;
		$item->access->page = 'portfolio';

		// comments allowed?
		if ($item->access->request == 'extern') {
			$item->allowComments = false;
			$item->showComments = $item->externcomment;
		} else {
			$item->allowComments = true;
			$item->showComments = true;
		}
	} else {
		return;
	}

	$item->access->access = $access;
	$item->access->parentAccess = substr($item->access->access, strpos($item->access->access, '/')+1);

	return $item;
}


function exabis_eportfolio_get_shareable_courses_with_users() {
	global $USER, $COURSE;

	$courses = array();

	// loop through all my courses
	foreach (get_my_courses($USER->id) as $dbCourse) {

		$course = array(
			'id' => $dbCourse->id,
			'fullname' => $dbCourse->fullname,
			'users' => array()
		);

		$context = get_context_instance(CONTEXT_COURSE, $dbCourse->id);
		$roles = get_roles_used_in_context($context);

		foreach ($roles as $role) {
			$users = get_role_users($role->id, $context, false, 'u.id, u.firstname, u.lastname');
			if (!$users) {
				continue;
			}

			foreach ($users as $user) {
				if ($user->id == $USER->id)
					continue;

				$course['users'][$user->id] = array(
					'id' => $user->id,
					'name' => $user->firstname.' '.$user->lastname,
					'rolename' => $role->name
				);
			}
		}

		$courses[$course['id']] = $course;
	}

	// move active course to first position
	if (isset($courses[$COURSE->id])) {
		$course = $courses[$COURSE->id];
		unset($courses[$COURSE->id]);
		array_unshift($courses, $course);
	}

	return $courses;
}

function get_extern_access($userid) {
	$userpreferences = block_exabis_eportfolio_get_user_preferences($userid);
   	return "extern.php?id={$userpreferences->user_hash}";
}

function print_js() {
    echo "<script type=\"text/javascript\">\n";
    echo "<!--\n";
    echo "function SetAllCheckBoxes(FormName, FieldName, CheckValue)\n";
    echo "{\n";
    echo "	if(!document.getElementById(FormName))\n";
    echo "		return;\n";
    echo "	var objCheckBoxes = document.getElementById(FormName).elements[FieldName];\n";
    echo "	if(!objCheckBoxes)\n";
    echo "		return;\n";
    echo "	var countCheckBoxes = objCheckBoxes.length;\n";
    echo "	if(!countCheckBoxes)\n";
    echo "		objCheckBoxes.checked = CheckValue;\n";
    echo "	else\n";
    echo "		// set the check value for all check boxes\n";
    echo "		for(var i = 0; i < countCheckBoxes; i++)\n";
    echo "			objCheckBoxes[i].checked = CheckValue;\n";
    echo "      if (CheckValue == true)\n";
    echo "              document.getElementById(FormName).selectall.value = \"1\";\n";
    echo "      else\n";
    echo "              document.getElementById(FormName).selectall.value = \"0\";\n";
    echo "}\n";
    echo "// -->\n";
    echo "</script>\n";
}

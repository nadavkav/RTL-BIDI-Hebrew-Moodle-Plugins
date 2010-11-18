<?php  // $Id: lib.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * Library of functions and constants for module bookmarks
 *
 * @author pigui
 * @version $Id: lib.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
 * @package bookmarks
 **/


require_once($CFG->dirroot. '/tag/lib.php');


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted bookmarks record
 **/
function bookmarks_add_instance($bookmarks) {

    $bookmarks->timemodified = time();

    # May have to add extra stuff in here #

    return insert_record("bookmarks", $bookmarks);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function bookmarks_update_instance($updatedbookmarks) {

    $bookmarks->timemodified = time();
    $bookmarks->id = $updatedbookmarks->instance;

    # May have to add extra stuff in here #
    $bookmarks->intro = $updatedbookmarks->intro; //addslashes($updatedbookmarks->intro); //(nadavkav) why we need that?
	$bookmarks->name = $updatedbookmarks->name; //addslashes($updatedbookmarks->name); //(nadavkav) why we need that?

    return update_record("bookmarks", $bookmarks);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function bookmarks_delete_instance($id) {

    if (! $bookmarks = get_record("bookmarks", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records("bookmarks", "id", "$bookmarks->id")) {
        $result = false;
    }

    return $result;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 **/
function bookmarks_user_outline($course, $user, $mod, $bookmarks) {
    return $return;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function bookmarks_user_complete($course, $user, $mod, $bookmarks) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in bookmarks activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function bookmarks_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function bookmarks_cron () {
    global $CFG;

    return true;
}

/**
 * Must return an array of grades for a given instance of this module,
 * indexed by user.  It also returns a maximum allowed grade.
 *
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $bookmarksid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function bookmarks_grades($bookmarksid) {
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of bookmarks. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $bookmarksid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function bookmarks_get_participants($bookmarksid) {
    return false;
}

/**
 * This function returns if a scale is being used by one bookmarks
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $bookmarksid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function bookmarks_scale_used ($bookmarksid,$scaleid) {
    $return = false;

    //$rec = get_record("bookmarks","id","$bookmarksid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other bookmarks functions go here.  Each of them must have a name that
/// starts with bookmarks_

/**
 *
 *
 */
function bookmarks_add_bookmark($name, $description, $url, $tags, $bookmarksid, $userid=null){

	global $USER;

	if ($userid == null){
		$userid = $USER->id;
	}

	// check for a bookmark with the same name
	$exists = count_records("bookmarks_items", 'userid', $userid, 'name', $name, 'bookmarksid', $bookmarksid);
	if ($exists != 0){
		notice(get_string('repeatedname','bookmarks'));
	}

	// check for a bookmark to the same link
	$reg = '/^(http|https):\/\/([a-z0-9-]\.+)*/i';
	$link='';
	if(preg_match($reg,$url)){
		$link = get_record('bookmarks_links', 'url', $url);
		if (!empty($link)){
			$exists = count_records("bookmarks_items", 'userid', $userid, 'linkid', $link->id);
			if ($exists != 0){
				notice(get_string('repeatedurl','bookmarks'));
			}
		}
	} else {
		notice(get_string('validurl', 'bookmarks'));
	}
	$item = new stdClass();
	$item->name = $name;
	$item->description = $description;
	$item->userid = $userid;
	$item->bookmarksid = $bookmarksid;
	if (!empty($link)){
		$item->linkid = $link->id;
	} else {
		if(!$item->linkid = bookmarks_add_link($url)){
			error("There was an error in link creation");
		}
	}

	if(!$itemid = insert_record('bookmarks_items', $item)){
		error("There was an error in bookmark creation");
	}

	return tag_set('bookmark',$itemid, explode(',',$tags));
}

/**
 *
 *
 */
function bookmarks_update_bookmark($itemid, $name, $description, $url, $tags, $userid=null) {
	global $USER;

	$item = bookmarks_get_item($itemid);

	if ($userid == null){
		$userid = $USER->id;
	}

	if ($userid =! $item->userid){
			notice(get_string('notyours','bookmarks'));
	}

	if ($item->name != $name){
		// check for a bookmark with the same name
		$exists = count_records("bookmarks_items", 'userid', $userid, 'name', $name,'bookmarksid', $item->bookmarksid);
		if ($exists != 0){
			notice(get_string('repeatedname','bookmarks'));
		}
	}
	// check for a bookmark to the same link
	$reg = '/^(http|https):\/\/([a-z0-9-]\.+)*/i';
	$link='';
	if(preg_match($reg,$url)){
		$link = get_record('bookmarks_links', 'url', $url);
		if (!empty($link)){
			if ($item->linkid != $link->id){
				$exists = count_records("bookmarks_items", 'userid', $userid, 'linkid', $link->id);

				if ($exists != 0){
					notice(get_string('repeatedurl','bookmarks'));
				}
			}
		}
	} else {
		notice(get_string('validurl', 'bookmarks'));
	}

	$item->name = $name;
	$item->description = $description;

	if (!empty($link)){
		$item->linkid = $link->id;
	} else {
		if(!$item->linkid = bookmarks_add_link($url)){
			error("There was an error in link creation");
		}
	}

	if(!$itemid = update_record('bookmarks_items', $item)){
		error("There was an error in bookmark creation");
	}

	return tag_set('bookmark',$item->id, explode(',',$tags));
}

/**
 *
 *
 *
 */
function bookmarks_delete_item($item){
	$tags = bookmarks_get_item_tags( $item->id);
	if (!empty($tags)){
		foreach ($tags as $tag){
			tag_delete_instance('bookmark',$item->id, $tag->id);
		}
	}
	return delete_records('bookmarks_items','id',$item->id);

}
/**
 *
 *
 */
function bookmarks_untag($itemid, $tagname){
	$tag = tag_get('name',$tagname);
	return tag_delete_instance('bookmark',$itemid, $tag->id);
}

/**
 *
 *
 */
function bookmarks_add_tag(){

}

/**
 *
 *
 */
function bookmarks_add_link($url){
	$link = new stdClass();
	$link->url = $url;
	return insert_record("bookmarks_links", $link);
}

/**
 *
 *
 *
 */
function bookmarks_get_link($id){
	return get_record('bookmarks_links', 'id', $id);

}

/**
 *
 *
 */
function bookmarks_inc_hits($url){

	$link = get_record('bookmarks_links', 'url', $url);
	if(!empty($link)){
		$link->hits = $link->hits +1;
	}

	return update_record("bookmarks_links", $link);

}

/**
 *
 *
 *
 */
function bookmarks_get_item_tags($id){
	return tag_get_tags('bookmark',$id);

}

/**
 *
 *
 */
function bookmarks_get_items($bookmarksid){
	return get_records('bookmarks_items', 'bookmarksid', $bookmarksid, 'name');
}

/**
 *
 *
 */
function bookmarks_get_item($id){
	return get_record('bookmarks_items', 'id', $id);
}


/**
 *
 *
 */
function bookmarks_get_items_by_user($userid, $bookmarksid){
	return get_records_select('bookmarks_items', "userid = $userid and bookmarksid = $bookmarksid", 'name');
}

/**
 *
 *
 */
function bookmarks_get_items_by_group($groupid, $bookmarksid){
	global $COURSE;

	$members = bookmarks_get_groupmembers(array($groupid));

	if (empty($members)){
		return false;
	}
	$select = 'userid IN ('.implode(',',$members).') and bookmarksid='.$bookmarksid;

	return get_records_select('bookmarks_items', $select, 'name');
}

/**
 *
 *
 *
 */
function bookmarks_search_by($criteria, $id){
	global $CFG, $USER, $COURSE;

	$elements = array();
	$queries = array();
	$queries['text'] = array();
	$queries['tag'] = array();
	$queries['url'] = array();
	$queries['inurl'] = array();

	$elements = explode(',', $criteria);
	foreach ($elements as $element){
		$element = trim($element);
		switch(substr($element,0,4)){
			case 'tag:':
				$queries['tag'][] = substr($element,4);
				break;
			case 'url:':
				$queries['url'][] = substr($element,4);
				break;
			default:
				switch(substr($element,0,6)){
					case 'inurl:':
						$queries['inurl'][] = substr($element,6);
						break;
					default:
						$queries['text'][] = $element;
				}

		}

	}

	$cm = get_coursemodule_from_instance('bookmarks',$id);

	$query = 'SELECT DISTINCT i.*
				FROM '.$CFG->prefix.'bookmarks_items i
				LEFT OUTER JOIN '.$CFG->prefix.'bookmarks_links l ON i.linkid = l.id
				LEFT OUTER JOIN '.$CFG->prefix.'tag_instance ti ON i.id = ti.itemid
				LEFT OUTER JOIN '.$CFG->prefix.'tag t ON ti.tagid = t.id
				WHERE i.bookmarksid = '.$id;
	if (!empty($queries['text'])){
		foreach ($queries['text'] as $text){
			$query .= ' AND (';
			$query .= " i.name LIKE '%".$text."%' OR";
			$query .= " i.description LIKE '%".$text."%'";
			$query .= ')';
		}

	}
	if (!empty($queries['tag'])){
		foreach ($queries['tag'] as $tag){
			$query .= ' AND ';
			$query .= "t.name = '".$tag."'";
		}
	}
	if (!empty($queries['inurl'])){
		foreach ($queries['inurl'] as $url){
			$query .= ' AND ';
			$query .= "l.url LIKE '%".$url."%'";
		}
	}
	if (!empty($queries['url'])){
		foreach ($queries['url'] as $url){
			$query .= ' AND ';
			$query .= "l.url = '".$url."'";
		}
	}
	if ($cm->gropumode = 1){
		$context = get_context_instance(CONTEXT_MODULE,$cm->id);
		$gids = bookmarks_get_user_groupids($USER->id, $COURSE->id);
		if ($gids){
			$userids = bookmarks_get_groupmembers($gids);
			$query .= 'AND i.userid IN('.implode(',', $userids).')';
		} elseif (!has_capability('mod/bookmarks:manage',$context)){
			$query .= 'AND i.userid ='. $USER->id;
		}

	}
	$query .= ' ORDER BY i.name';

	return get_records_sql($query);
}

/**
 *
 *
 *
 */
function bookmarks_count_all_bookmarks($url){
	global $CFG;

	$query = 'SELECT count(*)
				FROM '.$CFG->prefix.'bookmarks_items i LEFT OUTER JOIN '.$CFG->prefix.'bookmarks_links l
				ON i.linkid = l.id
				WHERE l.url=\''.$url.'\'';

	return count_records_sql($query);

}

/**
 *
 *
 *
 */
function bookmarks_count_all_accessible_bookmarks($url, $id, $userids=''){
	global $CFG;

	$query = 'SELECT count(*)
				FROM '.$CFG->prefix.'bookmarks_items i LEFT OUTER JOIN '.$CFG->prefix.'bookmarks_links l
				ON i.linkid = l.id
				WHERE l.url=\''.$url.'\' AND
					i.bookmarksid = '.$id;
	if (!empty($userids)){
		$query .= ' AND i.userid IN ('.$userids.')';
	}
	return count_records_sql($query);
}

/**
 *
 * @retrun @param array $tagcloud array of tag objects (fields: id, name, rawname, count and flag)
 */
function bookmarks_get_activity_tags($bookmarksid){
	global $CFG;

	$query = 'SELECT t.id, t.name, t.rawname, count(*) as count, t.flag
				FROM '.$CFG->prefix.'tag t LEFT OUTER JOIN '.$CFG->prefix.'tag_instance ins
						ON t.id = ins.tagid
					LEFT OUTER JOIN '.$CFG->prefix.'bookmarks_items it ON
						ins.itemid = it.id
				WHERE it.bookmarksid = '.$bookmarksid.' AND
					ins.itemtype = \'bookmark\'
				GROUP BY t.id, t.name, t.rawname;';
	return get_records_sql($query);

}

/**
 *
 * @retrun @param array $tagcloud array of tag objects (fields: id, name, rawname, count and flag)
 */
function bookmarks_get_user_tags($bookmarksid, $userid){
	global $CFG;
	$query = 'SELECT t.id, t.name, t.rawname, count(*) as count, t.flag
				FROM '.$CFG->prefix.'tag t LEFT OUTER JOIN '.$CFG->prefix.'tag_instance ins
						ON t.id = ins.tagid
					LEFT OUTER JOIN '.$CFG->prefix.'bookmarks_items it ON
						ins.itemid = it.id
				WHERE it.bookmarksid = '.$bookmarksid.' AND
					ins.itemtype = \'bookmark\' AND
					it.userid = '. $userid. '
				GROUP BY t.id, t.name, t.rawname;';

	return get_records_sql($query);

}

?>

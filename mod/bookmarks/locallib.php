<?php

/**
 *
 *
 */
function bookmarks_print_edit_section($cmid){
	global $CFG;


	echo '<div class="addbookmark">
			<a href='. $CFG->wwwroot .'/mod/bookmarks/edit.php?id='.$cmid.'>'. get_string('additem','bookmarks').'</a>
		 </div>';

}


/**
 *
 *
 *
 */
function bookmarks_print_bookmarks_section($bookmarksid){

	global $USER, $COURSE;

	$cm = get_coursemodule_from_instance('bookmarks', $bookmarksid);
	$items = bookmarks_get_items_by_user($USER->id, $bookmarksid);

	$userids = null;
	if ($cm->gropumode = 1){
		$context = get_context_instance(CONTEXT_MODULE,$cm->id);
		$gids = bookmarks_get_user_groupids($USER->id, $COURSE->id);
		if ($gids){
			$users = bookmarks_get_groupmembers($gids);
			$userids = implode(',', $users);
		}
	}

	if (empty($items)){
		print_string('noitems','bookmarks');
	} else {
		foreach ($items as $item){
			bookmarks_print_item($item, $cm->id, $userids,true);
		}
	}
}


function bookmarks_print_tabs($cmid, $action){

	$baseurl = 'view.php?id=' .$cmid. '&action=';
	$tabs = array();
	$rows = array();

	$rows[] = new tabobject('viewall', $baseurl.'viewall', get_string('allbookmarks','bookmarks'), 'viewall', false);
	$rows[] = new tabobject('viewmy' ,$baseurl.'viewmy', get_string('mybookmarks','bookmarks'), 'viewmy', false);

	$context = get_context_instance(CONTEXT_MODULE,$cmid);
	if(has_capability('mod/bookmarks:additem',$context)){
	  $rows[] = new tabobject('additem' ,'edit.php?id='.$cmid , get_string('additem','bookmarks'), 'additem', false);
	}
	$rows[] = new tabobject('search', $baseurl.'search', get_string('search','bookmarks'), 'search', false);
	$tabs[] = $rows;

	print_tabs($tabs, $action);

}


function bookmarks_print_content($id, $action){
	switch($action){

		case 'viewmy':
			bookmarks_print_my_bookmarks($id);
			break;
		case 'viewall':
			bookmarks_print_all_bookmarks($id);
			break;
		case 'search':
			bookmarks_print_search($id);
			break;

	}

}


/**
 *
 *
 *
 */
function bookmarks_print_my_bookmarks($id){

  $cm = get_coursemodule_from_instance('bookmarks', $id);
  $context = get_context_instance(CONTEXT_MODULE,$cm->id);

/*  if(has_capability('mod/bookmarks:additem',$context)){
    bookmarks_print_edit_section($cm->id);
  }*/
  bookmarks_print_bookmarks_section($id);

}

/**
 *
 *
 *
 */
function bookmarks_print_all_bookmarks($id){

	global $COURSE;
	$cm = get_coursemodule_from_instance('bookmarks', $id);

	switch ($cm->groupmode){

		case 0:
			bookmarks_print_all_bookmarks_nogroups($id, $cm->id);
			break;
		case 1:
			bookmarks_print_all_bookmarks_separategroups($id, $cm->id);
			break;
		case 2:
			bookmarks_print_all_bookmarks_visiblegroups($id, $cm->id);
			break;

	}

}

/**
 *
 *
 */
function bookmarks_print_all_bookmarks_nogroups($id, $cmid){
	$items = bookmarks_get_items($id);
	if (!empty($items)){
		foreach ($items as $item){
			bookmarks_print_item($item, $cmid);
		}
	} else {
		print_string('noitems', 'bookmarks');
	}

}

/**
 *
 *
 */
function bookmarks_print_all_bookmarks_separategroups($id, $cmid){
	global $COURSE, $USER;

	$context = get_context_instance(CONTEXT_MODULE,$cmid);

	// Check for a wrong course config
	$groups = groups_get_all_groups($COURSE->id);

	if (empty($groups)){
		notice(get_string('nogroupsset','bookmarks'));
	}

	// Set active group
	$gid = optional_param('gid',null, PARAM_INT);
	if(!isset($gid)){
		$usergroups = groups_get_all_groups($COURSE->id, $USER->id);
		if(!empty($usergroups)){
			$group = current($usergroups);
			$gid = $group->id;
		} else {
			$group = current($groups);
			$gid = $group->id;
		}
	} else {
		// Check if current user is member of $gid group (separate groups)
		if (bookmarks_check_group($gid)){
			if (!groups_is_member($gid) &&
					(!has_capability('moodle/site:accessallgroups',$context) ||
					!has_capability('mod/bookmarks:manage',$context))){

				notice(get_string('accessdenied','bookmarks'));
			}
		} else {
			error ('Incorrect group id','view.php?id='.$cmid);
		}

	}


	if(has_capability('moodle/site:accessallgroups',$context) || has_capability('mod/bookmarks:manage',$context)){
		bookmarks_print_group_select($cmid, $groups,$gid);
	}

	$items = bookmarks_get_items_by_group($gid, $id);
	$users = bookmarks_get_groupmembers(array($gid));
	$userids = implode(',', $users);

	if (!empty($items)){
		foreach ($items as $item){
			bookmarks_print_item($item, $cmid,$userids);
		}
	} else {
		print_string('noitems','bookmarks');
	}

}

/**
 *
 *
 */
function bookmarks_print_all_bookmarks_visiblegroups($id, $cmid){
	global $COURSE, $USER;

	// Check for a wrong course config
	$groups = groups_get_all_groups($COURSE->id);

	if (empty($groups)){
		notice(get_string('nogroupsset','bookmarks'));
	}

	// Set active group
	$gid = optional_param('gid',null, PARAM_INT);
	if(!isset($gid)){
		$usergroups = groups_get_all_groups($COURSE->id, $USER->id);
		if(!empty($usergroups)){
			$group = current($usergroups);
			$gid = $group->id;
		} else {
			$group = current($groups);
			$gid = $group->id;
		}
	} elseif (!bookmarks_check_group($gid)){
			// Check for a correct groupid
			error ('Incorrect group id','view.php?id='.$cmid);
	}

	$context = get_context_instance(CONTEXT_MODULE,$cmid);
	bookmarks_print_group_select($cmid, $groups,$gid);

	$items = bookmarks_get_items_by_group($gid, $id);
	if (!empty($items)){
		foreach ($items as $item){
			bookmarks_print_item($item, $cmid);
		}
	} else {
		print_string('noitems','bookmarks');
	}

}

/**
 *
 *
 *
 */
function bookmarks_print_item($item, $cmid, $userids='', $commands=false, $options=''){

	$link = bookmarks_get_link($item->linkid);
	echo '<div class="bookmark">
		<h3 class="name"><a class="previewlink" title="'.$link->url.'" href="link.php?id='.$cmid.'&url='.urlencode($link->url).'">'.$item->name.'</a></h3>';

	if ($commands){
		echo '<div class="commands">
				<a class="edit" href="edit.php?id='.$cmid.'&item='.$item->id.'">['.get_string('edit','forum').']</a> /
				<a class="rm" href="del.php?id='.$cmid.'&item='.$item->id.'">['.get_string('delete').']</a>
			</div>';
	}
	echo '<p class="notes">'.$item->description.'</p>';

	$tags = bookmarks_get_item_tags($item->id);
	if (!empty($tags)){
		echo '<div class="meta">';
		echo get_string('tags').':';
		foreach ($tags as $tag){
			echo '<a class="tag" href="view.php?id='.$cmid.'&action=search&query='.urlencode('tag:'.$tag->name).'">'.$tag->name.'</a>' ;
		}
		echo '</div>';
	}

	echo '<div class="meta">';
	if ($options == 'detailed'){
		$usr = get_user_info_from_db('id', $item->userid);
		echo get_string('savedby','bookmarks'). $usr->lastname.', '. $usr->firstname;
	} else {
		$cm = get_coursemodule_from_id('bookmarks',$cmid);
		$countall = bookmarks_count_all_bookmarks($link->url);
		$countpart = bookmarks_count_all_accessible_bookmarks($link->url, $cm->instance, $userids);

		if ($countall>1){
			echo get_string('savedby','bookmarks').$countall.' (<a href="view.php?id='.$cmid.'&action=search&query='.urlencode('url:'.$link->url).'&options=detailed">'.$countpart.' '.get_string('people','bookmarks').'</a>)';
		} else{
			echo get_string('savedby','bookmarks').$countall.' (<a href="view.php?id='.$cmid.'&action=search&query='.urlencode('url:'.$link->url).'&options=detailed">'.$countpart.' '.get_string('person','bookmarks').'</a>)';
		}
		echo ' \ '.get_string('seen','bookmarks').' '.$link->hits .' '.get_string('times','bookmarks');

	}
	echo '</div>';
	echo '</div>';

}

function bookmarks_print_group_select($cmid, $groups, $selected){

	if (!empty($groups)){
		echo '<div class="groupsselect">';
		$form = '<form method="GET" action="view.php">';
		$form .= '<select name="gid">';
		foreach ($groups as $group){
			$sel = '';
			if ( $group->id == $selected){
				print_string ('selectedgroup', 'bookmarks', $group->name);
				$sel = ' selected="selected"';
			}
			$form .= '<option'.$sel.' value="'.$group->id.'">'. $group->name. '</option>';
		}
		$form .= '</select>';
		$form .= '<input type="submit" value="'.get_string('changegroup','bookmarks').'"/>';
		$form .= '<input type="hidden" name="id" value="'.$cmid.'"/>';
		$form .= '<input type="hidden" name="action" value="viewall"/>';
		$form .= '</form>';
		echo $form;
		echo '</div>';
	} else{
		print_string('noitems','bookmarks');

	}

}

/**
 *
 *
 *
 */
function bookmarks_check_group($gid){
	global $COURSE;

	return record_exists('groups', 'id', $gid, 'courseid', $COURSE->id);

}

/**
 *
 *
 *
 */
function bookmarks_print_search($id){

	$cm = get_coursemodule_from_instance('bookmarks', $id);

	$query = optional_param('query',null,PARAM_TEXT);
	$options = optional_param('options',null,PARAM_TEXT);
	$querytext ='';
	$valuequery = null;
	if(!isset($query) || empty($query)){
		$query = null;
		$valuequery = '';
	} else {
		$valuequery = ' value="'.$query.'"';
		$querytext = '<p>'.get_string('searchingfor', 'bookmarks').$query.'</p>';
	}
	echo '<div class="searchform">';
	echo '<form action="view.php" method="GET">';
	echo '<input type="text" name="query"'.$valuequery.'/>';
	echo '<input type="submit" value="Search"/>';
	echo '<input type="hidden" name="id" value="'.$cm->id.'"/>';
	echo '<input type="hidden" name="action" value="search"/>';
	echo '</form>';


	echo $querytext;
	echo '</div>';
	if (isset($query)){
		$items = bookmarks_search_by($query, $id);
		if(!empty($items)){
			foreach ($items as $item){
				if ($options == 'detailed'){
					bookmarks_print_item($item, $cm->id,'',false,'detailed');
				} else {
					bookmarks_print_item($item, $cm->id);
				}
			}
		} else {
			echo '<p>'.get_string('noresults', 'bookmarks').'</p>';
		}
	}

}

/**
 *
 *
 *
 */
function bookmarks_get_user_groupids($userid, $courseid){

		$usergroups = groups_get_all_groups($courseid, $userid);

		if(!empty($usergroups)){
			$groups = array();
			foreach ($usergroups as $group){
				$groups[]= $group->id;
			}
			return $groups;
		} else {
			return false;
		}
}

/**
 *
 *
 *
 */
function bookmarks_get_groupmembers($groups){
	$userids = array();
	foreach ($groups as $groupid){
		$members = groups_get_members($groupid);
		foreach ($members as $member){
			$userids[] = $member->id;
		}

	}
	return $userids;
}

function bookmarks_print_tagcloud_block($bookmarksid, $options='view'){
	global $USER;
	echo '
		<div class="tagcloud">
			<div  id="inst50" class="block_tags sideblock">
				<div class="header">
					<div class="title">
						<h2>'.get_string('tags').'</h2>
					</div>
				</div>
				<div class="content">';
	$cm = get_coursemodule_from_instance('bookmarks',$bookmarksid);

	$action = optional_param('action', 'viewall', PARAM_TEXT);
	$tags = "";
	$action == 'view'; // fixed (by nadavkav)
	if ($action == 'viewmy'){
		$tags = bookmarks_get_user_tags($bookmarksid, $USER->id);
	} else{
		$tags = bookmarks_get_activity_tags($bookmarksid);
	}
	bookmarks_print_tag_cloud($tags,$cm->id,$options);

	echo'		</div>
			</div>
		</div>';


}

/**
 *
 *
 */
function bookmarks_print_tag_cloud($tagcloud, $cmid, $options='view', $shuffle=true, $max_size=180, $min_size=80, $return=false) {

    global $CFG;

    if (empty($tagcloud)) {
        return;
    }

    if ($shuffle) {
        shuffle($tagcloud);
    } else {
        ksort($tagcloud);
    }

    $count = array();
    foreach ($tagcloud as $key => $value){
        if(!empty($value->count)) {
            $count[$key] = log10($value->count);
        }
        else{
            $count[$key] = 0;
        }
    }

    $max = max($count);
    $min = min($count);

    $spread = $max - $min;
    if (0 == $spread) { // we don't want to divide by zero
        $spread = 1;
    }

    $step = ($max_size - $min_size)/($spread);

    $systemcontext   = get_context_instance(CONTEXT_SYSTEM);
    $can_manage_tags = has_capability('moodle/tag:manage', $systemcontext);

    //prints the tag cloud
    $output = '<ul id="tag-cloud-list">';
    foreach ($tagcloud as $key => $tag) {

        $size = $min_size + ((log10($tag->count) - $min) * $step);
        $size = ceil($size);

        $style = 'style="font-size: '.$size.'%"';
        $title = 'title="'.s(get_string('thingstaggedwith','tag', $tag)).'"';

        //highlight tags that have been flagged as inappropriate for those who can manage them
        $tagname = tag_display_name($tag);
		$href = 'href="'.$CFG->wwwroot.'/mod/bookmarks/view.php?id='.$cmid.'&action=search&query=tag'.urlencode(':'.$tagname).'"';
		$onclick = '';
		if($options == 'edit'){
			$onclick = 'class="clickable-label" onclick="selectTag(this); return false;"';
			$href = 'href="#"';
		}
        if ($tag->flag > 0 && $can_manage_tags) {
            $tagname =  '<span class="flagged-tag">' . tag_display_name($tag) . '</span>';
        }

        $tag_link = '<li><a '.$onclick.' '.$href.' '.$title.' '. $style .'>'.$tagname.'</a></li> ';

        $output .= $tag_link;

    }
    $output .= '</ul>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }

}
?>
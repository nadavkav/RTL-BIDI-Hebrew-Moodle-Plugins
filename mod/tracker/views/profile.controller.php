<?PHP

/**
* @package mod-tracker
* @category mod
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* Controller for all "profile" related views
*
* @usecase savequery (form)
* @usecase dosavequery
* @usecase viewquery
* @usecase editquery (form)
* @usecase updatequery
* @usecase deletequery
* @usecase register
* @usecase unregister
* @usecase editwatch (form)
* @usecase updatewatch
* @usecase saveprefs
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/tracker
}

/******************************** ask for a new search query **************************/
if ($action == 'savequery'){ // collects name and description on the way
	$fields = tracker_extractsearchparametersfrompost();
	$form->fields = serialize($fields); // serialize for passthru
	$form->action = 'dosaveasquery';
	$form->description = tracker_printsearchfields($fields);
    include $CFG->dirroot.'/mod/tracker/views/addaquery.html';
	return -1;
}
/******************************** saves a new search query **************************/
elseif ($action == 'dosaveasquery'){
    $query->format = required_param('format', PARAM_INT);
	$query->name = required_param('name', PARAM_TEXT);
	$query->description = addslashes(required_param('description', PARAM_CLEANHTML));

    if (empty($query->name)){
        $error->message = get_string('namecannotbeblank', 'tracker');
        $error->on = 'name';
        $errors[] = $error;
        $form->description = stripslashes($query->description);
        include $CFG->dirroot.'/mod/tracker/views/addaquery.html';
        return -1;
    }

	$fields = unserialize(stripslashes(required_param('fields', PARAM_RAW)));
	$query->trackerid = $tracker->id;
	if (! tracker_savesearchparameterstodb($query, $fields)){
		error ('Unable to save query as query', "view.php?a={$tracker->id}&amp;what=search");
	}
}
/******************************** ask for viewing a personal search query **************************/
elseif ($action == 'viewquery'){
    $fields = tracker_extractsearchparametersfromdb();
    include $CFG->dirroot.'/mod/tracker/views/editquery.html';
}
/******************************** ask for editing a personal search query **************************/
elseif ($action == 'editquery'){
    $form->queryid = required_param('queryid', PARAM_INT);
    $query = get_record('tracker_query', 'id', $form->queryid);
    $fields = tracker_extractsearchparametersfromdb($form->queryid);
    $form->name = $query->name;
    $form->checkdate = (empty($fields['datereported'])) ? false : true ;
    if (empty($fields['reportedby'])) $fields['reportedby'] = array();
    if (empty($fields['summary'])) $fields['summary'] = array();
    if (empty($fields['description'])) $fields['description'] = array();
    if (empty($fields['id'])) $fields['id'] = array();
    include $CFG->dirroot.'/mod/tracker/views/editquery.html';
    return -1;
}
/******************************** updates a personal search query **************************/
elseif ($action == 'updatequery'){
	$query->id = required_param('queryid', PARAM_INT);
	$fields = tracker_extractsearchparametersfrompost();
		
	$query->trackerid = $tracker->id;
	if(! tracker_savesearchparameterstodb($query, $fields)){
		error ('Unable to update query id" ' . $query->id, 'view.php?a={$tracker->id}&amp;page=myqueries');
	}
}
/******************************** deletes a personal search query **************************/
elseif ($action == 'deletequery'){
	$queryid = optional_param('queryid', '', PARAM_INT);
	if (! delete_records ('tracker_query', 'id', $queryid, 'trackerid', $tracker->id, 'userid', $USER->id)){
		error ("Cannot delete query id: " . $queryid);
	}
}
/******************************** register to an issue **************************/
elseif ($action == 'register'){
	$issueid = optional_param('issueid', '', PARAM_INT);
	if (!get_record('tracker_issuecc', 'trackerid', $tracker->id, 'issueid', $issueid, 'userid', $USER->id)){
	    $cc->trackerid = $tracker->id;
	    $cc->issueid = $issueid;
	    $cc->userid = $USER->id;
	    $cc->events = (isset($USER->trackerprefs->eventmask)) ? $USER->trackerprefs->eventmask : ALL_EVENTS ;
	    insert_record('tracker_issuecc', $cc);
	}
}
/******************************** unregister a watch on an issue **************************/
elseif ($action == 'unregister'){
	$issueid = required_param('issueid', PARAM_INT);
	$ccid = required_param('ccid', PARAM_INT);
	if (!delete_records ('tracker_issuecc', 'trackerid', $tracker->id, 'issueid', $issueid, 'userid', $ccid)){
		error ("Cannot delete carbon copy {$tracker->ticketprefix}{$issueid} for user : " . $ccid);
	}
}
/******************************** unregister all my watches **************************/
elseif ($action == 'unregisterall'){
	$userid = required_param('userid', PARAM_INT);
	if (! delete_records ('tracker_issuecc', 'trackerid', $tracker->id, 'userid', $userid)){
		error ("Cannot delete carbon copies for user : " . $userid);
	}
}
/************************** ask for editing the watchers configuration **************************/
elseif ($action == 'editwatch'){
	$ccid = optional_param('ccid', '', PARAM_INT);
	if (!$form = get_record('tracker_issuecc', 'id', $ccid)){
	    error("Cannot edit this watch");
	}
	$issue = get_record('tracker_issue', 'id', $form->issueid);
	$form->summary = $issue->summary;

	include "views/editwatch.html";
	return -1;
}
/********************************* update a watchers config for an issue **************************/
elseif ($action == 'updatewatch'){
	$cc->id = required_param('ccid', PARAM_INT);
	$open = optional_param('open', '', PARAM_INT);
	$resolving = optional_param('resolving', '', PARAM_INT);
	$waiting = optional_param('waiting', '', PARAM_INT);
	$testing = optional_param('testing', '', PARAM_INT);
	$resolved = optional_param('resolved', '', PARAM_INT);
	$abandonned = optional_param('abandonned', '', PARAM_INT);
	$oncomment = optional_param('oncomment', '', PARAM_INT);
	$cc->events = get_field('tracker_issuecc', 'events', 'id', $cc->id);
	if (is_numeric($open))
        $cc->events = ($open === 1) ? $cc->events | EVENT_OPEN : $cc->events & ~EVENT_OPEN ;
	if (is_numeric($resolving))
        $cc->events = ($resolving === 1) ? $cc->events | EVENT_RESOLVING : $cc->events & ~EVENT_RESOLVING ;
	if (is_numeric($waiting))
        $cc->events = ($waiting === 1) ? $cc->events | EVENT_WAITING : $cc->events & ~EVENT_WAITING ;
	if (is_numeric($testing))
        $cc->events = ($testing === 1) ? $cc->events | EVENT_TESTING : $cc->events & ~EVENT_TESTING ;
	if (is_numeric($resolved))
        $cc->events = ($resolved === 1) ? $cc->events | EVENT_RESOLVED : $cc->events & ~EVENT_RESOLVED ;
	if (is_numeric($abandonned))
        $cc->events = ($abandonned === 1) ? $cc->events | EVENT_ABANDONNED : $cc->events & ~EVENT_ABANDONNED ;
	if (is_numeric($oncomment))
        $cc->events = ($oncomment === 1) ? $cc->events | ON_COMMENT : $cc->events & ~ON_COMMENT ;

    if (!update_record('tracker_issuecc', $cc)){
        error("Could not update watch $ccid");
    }
}
/********************************* saves the user's preferences **************************/
elseif ($action == 'saveprefs'){
    $open = optional_param('open', 1, PARAM_INT);
    $resolving = optional_param('resolving', 1, PARAM_INT);
    $waiting = optional_param('waiting', 1, PARAM_INT);
    $testing = optional_param('testing', 1, PARAM_INT);
    $resolved = optional_param('resolved', 1, PARAM_INT);
    $abandonned = optional_param('abandonned', 1, PARAM_INT);
    $oncomment = optional_param('oncomment', 1, PARAM_INT);
    
    $pref->trackerid = $tracker->id;
    $pref->userid = $USER->id;
    $pref->name = 'eventmask';
    $pref->value = $open * EVENT_OPEN + $resolving * EVENT_RESOLVING + $waiting * EVENT_WAITING + $resolved * EVENT_RESOLVED + $abandonned * EVENT_ABANDONNED + $oncomment * ON_COMMENT + $testing * EVENT_TESTING;

    if (!$oldpref = get_record('tracker_preferences', 'trackerid', $tracker->id, 'userid', $USER->id, 'name', 'eventmask')){
        if (!insert_record('tracker_preferences', $pref)){
            error("Could not insert preference record");
        }
    } else {
        $pref->id = $oldpref->id;
        if (!update_record('tracker_preferences', $pref)){
            error("Could not update preference record");
        }
    }
}
?>
<?php

/**
* @package mod-tracker
* @category mod
* @author Valery Fremaux
* @date 02/12/2007
*
* Controller for all "view" related views
* 
* @usecase submitanissue
* @usecase updateanissue
* @usecase delete
* @usecase updatelist
* @usecase addcomment (form)
* @usecase doaddcomment
* @usecase usequery
* @usecase register
* @usecase unregister
* @usecase cascade
* @usecase raisepriority
* @usecase lowerpriority
* @usecase raisetotop
* @usecase lowertobottom
* @usecase askraise (form)
* @usecase doaskraise
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/tracker
}

/************************************* Submit an issue *****************************/
if ($action == 'submitanissue'){
	if (!$issue = tracker_submitanissue($tracker)){
	   error("Bad issue id");
    }
    print_simple_box_start('center', '80%', '', '', 'generalbox', 'bugreport');
    print_string('thanks', 'tracker');
    print_simple_box_end();
    print_continue("view.php?id={$cm->id}view=view&amp;screen=browse");
    
    // notify all admins
    if ($tracker->allownotifications){
        tracker_notify_submission($issue, $cm, $tracker);
    }
    
    return -1;
}
/************************************* update an issue *****************************/
elseif ($action == 'updateanissue'){
    $issue->id = required_param('issueid', PARAM_INT);
    $issue->status = required_param('status', PARAM_INT);
    $issue->assignedto = required_param('assignedto', PARAM_INT);
    $issue->summary = required_param('summary', PARAM_TEXT);
    $issue->description = addslashes(required_param('description', PARAM_CLEANHTML));
    $issue->format = required_param('format', PARAM_INT);
    $issue->datereported = required_param('datereported', PARAM_INT);
    $issue->resolution = addslashes(required_param('resolution', PARAM_CLEANHTML));
    $issue->resolutionformat = required_param('resolutionformat', PARAM_INT);
    $issue->trackerid = $tracker->id;

    // if ownership has changed, prepare logging
    $oldrecord = get_record('tracker_issue', 'id', $issue->id);
    
    if ($oldrecord->assignedto != $issue->assignedto){
        $ownership->trackerid = $tracker->id;
        $ownership->issueid = $oldrecord->id;
        $ownership->userid = $oldrecord->assignedto;
        $ownership->bywhomid = $oldrecord->bywhomid;
        $ownership->timeassigned = ($oldrecord->timeassigned) ? $oldrecord->timeassigned : time();
        if (!insert_record('tracker_issueownership', $ownership)){
            error ("Could not log old ownership");
        }        
    }    
    
    $issue->bywhomid = $USER->id;
    $issue->timeassigned = time();

    if (!update_record('tracker_issue', $issue)){
        error ("Could not update tracker issue");
    }

    // if not CCed, the assignee should be
    tracker_register_cc($tracker, $issue, $issue->assignedto);

    /// send state change notification
    if ($oldrecord->status != $issue->status){
        tracker_notifyccs_changestate($issue->id, $tracker);
    }

    tracker_clearelements($issue->id);    
    tracker_recordelements($issue);
    
    // TODO : process dependancies
    $dependancies = optional_param('dependancies', null, PARAM_INT);
    if (is_array($dependancies)){
        // cleanup previous depdendancies
        if (!delete_records('tracker_issuedependancy', 'childid', $issue->id)){
            error ("Could not delete old dependancies");
        }
        // install back new one
        foreach($dependancies as $dependancy){
            $dependancyrec->trackerid = $tracker->id;
            $dependancyrec->parentid = $dependancy;
            $dependancyrec->childid = $issue->id;
            $dependancyrec->comment = '';
            if (!insert_record('tracker_issuedependancy', $dependancyrec)){
                error ("Could not write dependancy record");
            }
        }
    }
}
/************************************* delete an issue record *****************************/
elseif ($action == 'delete'){
    $issueid = required_param('issueid', PARAM_INT);

    $maxpriority = get_field('tracker_issue', 'resolutionpriority', 'id', $issueid);

    delete_records('tracker_issue', 'id', $issueid);
    delete_records('tracker_issuedependancy', 'childid', $issueid);
    delete_records('tracker_issuedependancy', 'parentid', $issueid);
    delete_records('tracker_issueattribute', 'issueid', $issueid);
    delete_records('tracker_issuecomment', 'issueid', $issueid);
    delete_records('tracker_issueownership', 'issueid', $issueid);

    // lower priority of every issue above
    
    $sql = "
        UPDATE
            {$CFG->prefix}tracker_issue
        SET
            resolutionpriority = resolutionpriority - 1
        WHERE
            trackerid = $tracker->id AND
            resolutionpriority > $maxpriority
    ";

    execute_sql($sql);

    // todo : send notification to all cced

    delete_records('tracker_issuecc', 'issueid', $issueid);
}
/************************************* updating list and status *****************************/
elseif ($action == 'updatelist'){
	$keys = array_keys($_POST);							    // get the key value of all the fields submitted
	$statuskeys = preg_grep('/status./' , $keys);  	        // filter out only the status
	$assignedtokeys = preg_grep('/assignedto./' , $keys);  	// filter out only the assigned updating
	$newassignedtokeys = preg_grep('/assignedtoi./' , $keys);  // filter out only the new assigned
	foreach($statuskeys as $akey){
	    $issueid = str_replace('status', '', $akey);
	    $haschanged = optional_param('schanged'.$issueid, 0, PARAM_INT);
	    if ($haschanged){
    	    $issue->id = $issueid;
    	    $issue->status = required_param($akey, PARAM_INT);
    	    $oldstatus = get_field('tracker_issue', 'status', 'id', $issue->id);
    	    update_record('tracker_issue', $issue);    
    	    /// check status changing and send notifications
    	    if ($oldstatus != $issue->status){
        	    if ($tracker->allownotifications){
        	        tracker_notifyccs_changestate($issue->id, $tracker);
        	    }
        	}
        }
	}

	/// always add a record for history
	foreach($assignedtokeys as $akey){
	    $issueid = str_replace('assignedto', '', $akey);
	    // new ownership is triggered only when a change occured
	    $haschanged = optional_param('changed'.$issueid, 0, PARAM_INT);
	    if ($haschanged){
	        // save old assignement in history
            $oldassign = get_record('tracker_issue', 'id', $issueid);
            if ($oldassign->assignedto != 0){
                $ownership->trackerid = $tracker->id;
                $ownership->issueid = $issueid;
        	    $ownership->userid = $oldassign->assignedto;
        	    $ownership->bywhomid = $oldassign->bywhomid;
        	    $ownership->timeassigned = $oldassign->timeassigned;
        	    if (!insert_record('tracker_issueownership', $ownership)){
        	        notice ("Error saving ownership for issue $issueid");
        	    }
        	}

            // update actual assignement
    	    $issue->id = $issueid;
    	    $issue->bywhomid = $USER->id;
    	    $issue->timeassigned = time();
    	    $issue->assignedto = required_param($akey, PARAM_INT);
    	    tracker_register_cc($tracker, $issue, $issue->assignedto);
    	    if (!update_record('tracker_issue', $issue)){
    	        notice ("Error updating assignation for issue $issueid");
    	    }    	    

    	    if ($tracker->allownotifications){
    	        tracker_notifyccs_changeownership($issue->id, $tracker);
    	    }
    	}
	}

	/// reorder priority field and discard newly resolved or abandonned
	tracker_update_priority_stack($tracker);
}
/********************************* requires the add a comment form **************************/
elseif ($action == 'addacomment'){
    $form->issueid = required_param('issueid', PARAM_INT);
    include "views/addacomment.html";
    return -1;
}
/***************************************** add a comment ***********************************/
elseif ($action == 'doaddcomment'){
    $issueid = required_param('issueid', PARAM_INT);
    $comment->comment = addslashes(required_param('comment', PARAM_CLEANHTML));
    $comment->commentformat = required_param('commentformat', PARAM_INT);
    
    $comment->userid = $USER->id;
    $comment->trackerid = $tracker->id;
    $comment->issueid = $issueid;
    $comment->datecreated = time();
    if (!insert_record('tracker_issuecomment', $comment)){
        error ("Error writing comment");
    }

    if ($tracker->allownotifications){
        tracker_notifyccs_comment($issueid, $comment->comment, $tracker);
    }
    $issue = get_record('tracker_issue', 'id', $issueid);
	tracker_register_cc($tracker, $issue, $USER->id);
}
/************************************ reactivates a stored search *****************************/
elseif($action == 'usequery'){
    $queryid = required_param('queryid', PARAM_INT);
    $fields = tracker_extractsearchparametersfromdb($queryid);
}
/******************************* unregister administratively a user *****************************/
elseif ($action == 'unregister'){
	$issueid = required_param('issueid', PARAM_INT);
	$ccid = required_param('ccid', PARAM_INT);
	if (!delete_records ('tracker_issuecc', 'trackerid', $tracker->id, 'issueid', $issueid, 'userid', $ccid)){
		error ("Cannot delete carbon copy {$tracker->ticketprefix}{$issueid} for user : " . $ccid);
	}
}
elseif ($action == 'register'){
	$issueid = required_param('issueid', PARAM_INT);
	$ccid = required_param('ccid', PARAM_INT);
	$issue = get_record('tracker_issue', 'id', $issueid);
	tracker_register_cc($tracker, $issue, $ccid);
}
/******************************* copy an issue to a parent tracker *****************************/
elseif ($action == 'cascade'){
    global $USER;

	$issueid = required_param('issueid', PARAM_INT);
	$issue = get_record('tracker_issue', 'id', $issueid);
	$attributes = get_records('tracker_issueattribute', 'issueid', $issue->id);

	// remaps elementid to elementname for 
	tracker_loadelementsused($tracker, $used);
	if (!empty($attributes)){
    	foreach(array_keys($attributes) as $attkey){
    	    $attributes[$attkey]->elementname = @$used[$attributes[$attkey]->id]->name;
    	}
    }
    $issue->attributes = $attributes;

    // We get comments and make a single backtrack. There should not 
    // be usefull to bring along full user profile. We just want not
    // to loose usefull information the previous track collected.
	$comments = get_records('tracker_issuecomment', 'issueid', $issue->id);
    $track = '';
	if (!empty($comments)){
	    // collect userids
	    foreach($comments as $comment){
	        $useridsarray[] = $comment->userid;
	    }	    
	    $idlist = implode("','", $useridsarray);
	    $users = get_records_select('user', "id IN ('$idlist')", '', 'id, firstname, lastname');

        // make backtrack
        foreach($comments as $comment){
            $track .= get_string('commentedby', 'tracker').fullname($users[$comment->userid]).get_string('on', 'tracker').userdate($comment->datecreated);
            $track .= '<br/>';
            $track .= format_text($comment->comment, $comment->commentformat);
            $track .= '<hr width="60%"/>';
        }
	}
	$issue->comment = $track;
	
	// insert a backlink header in the content
	$olddescription = $issue->description;
	$issue->description = tracker_add_cascade_backlink($cm, $issue) . $issue->description;

    include_once $CFG->libdir."/pear/HTML/AJAX/JSON.php";
    include_once($CFG->dirroot.'/mod/tracker/rpclib.php');

    if (is_numeric($tracker->parent)){
        // tracker is local, use the rpc entry point anyway
        // emulate response
    	$result = tracker_rpc_post_issue($USER->username, $CFG->wwwroot, $tracker->parent, json_encode($issue));
    } else {
        // tracker is remote, make an RPC call

        list($remoteid, $mnet_host) = explode('@', $tracker->parent);

        // get network tracker properties
        include_once $CFG->dirroot.'/mnet/xmlrpc/client.php';
        
        $userroot = get_field('mnet_host', 'wwwroot', 'id', $USER->mnethostid);
        
        $rpcclient = new mnet_xmlrpc_client();
        $rpcclient->set_method('mod/tracker/rpclib.php/tracker_rpc_post_issue');
        $rpcclient->add_param($USER->username, 'string');
        $rpcclient->add_param($userroot, 'string');
        $rpcclient->add_param($remoteid, 'int');
        $rpcclient->add_param(json_encode($issue), 'string');
        
        $parent_mnet = new mnet_peer();
        $parent_mnet->set_wwwroot($mnet_host);
        
        if($rpcclient->send($parent_mnet)){
            $result = $rpcclient->response;
        } else {
            $result = null;
        }
                       
    }
    if ($result){
        $response = json_decode($result);
        if ($response->status == RPC_SUCCESS){
            $issue->status = TRANSFERED;
            $issue->followid = $response->followid;
            if (!update_record('tracker_issue', addslashes_recursive($issue))){
                error ("Could not update issue for cascade");
            }
        } else {
            error ("Error on remote side<br/>".$response->error);            
        }
    } else {
        error ("Error on sending cascade :<br/>".implode('<br/>', $rpcclient->error));
    }
}
/********************************* raises the priority of the issue **************************/
elseif ($action == 'raisepriority'){
    $issueid = required_param('issueid', PARAM_INT);
    $issue = get_record('tracker_issue', 'id', $issueid);
    $nextissue = get_record('tracker_issue', 'trackerid', $tracker->id, 'resolutionpriority', $issue->resolutionpriority + 1);
    if ($nextissue){
        $issue->resolutionpriority++;
        $nextissue->resolutionpriority--;
        update_record('tracker_issue', addslashes_recursive($issue));
        update_record('tracker_issue', addslashes_recursive($nextissue));
    }
	tracker_update_priority_stack($tracker);
}
/********************************* raises the priority at top of list **************************/
elseif ($action == 'raisetotop'){
    $issueid = required_param('issueid', PARAM_INT);
    $issue = get_record('tracker_issue', 'id', $issueid);
    $maxpriority = get_field('tracker_issue', 'resolutionpriority', 'id', $issueid);

    if ($issue->resolutionpriority != $maxpriority){
        // lower everyone above
        $sql = "
            UPDATE 
                {$CFG->dbprefix}tracker_issue
            SET 
                resolutionpriority = resolutionpriority - 1
            WHERE
                trackerid = $tracker->id AND
                resolutionpriority > $issue->resolutionpriority
        ";
        execute_sql($sql);
    
        // update to max priority
        $issue->resolutionpriority = $maxpriority;
        update_record('tracker_issue', addslashes_recursive($issue));
    }
	tracker_update_priority_stack($tracker);
}
/********************************* lowers the priority of the issue **************************/
elseif ($action == 'lowerpriority'){
    $issueid = required_param('issueid', PARAM_INT);
    $issue = get_record('tracker_issue', 'id', $issueid);
    if ($issue->resolutionpriority > 0){
        $nextissue = get_record('tracker_issue', 'trackerid', $tracker->id, 'resolutionpriority', $issue->resolutionpriority - 1);
        $issue->resolutionpriority--;
        $nextissue->resolutionpriority++;
        update_record('tracker_issue', addslashes_recursive($issue));
        update_record('tracker_issue', addslashes_recursive($nextissue));
    }
	tracker_update_priority_stack($tracker);
}
/********************************* raises the priority at top of list **************************/
elseif ($action == 'lowertobottom'){
    $issueid = required_param('issueid', PARAM_INT);
    $issue = get_record('tracker_issue', 'id', $issueid);

    if ($issue->resolutionpriority > 0){
        // raise everyone beneath
        $sql = "
            UPDATE 
                {$CFG->dbprefix}tracker_issue
            SET 
                resolutionpriority = resolutionpriority + 1
            WHERE
                trackerid = $tracker->id AND
                resolutionpriority < $issue->resolutionpriority
        ";
        execute_sql($sql);
    
        // update to min priority
        $issue->resolutionpriority = 0;
        update_record('tracker_issue', addslashes_recursive($issue));
    }
	tracker_update_priority_stack($tracker);
}
/****************** get some context for sending raising request ******************/
elseif ($action == 'askraise'){
     $issueid = required_param('issueid', PARAM_INT);

     include $CFG->dirroot.'/mod/tracker/views/raiserequest.html';
     
     return -1;
}
/****************** get some context for sending raising request ******************/
elseif ($action == 'doaskraise'){
     $issueid = required_param('issueid', PARAM_INT);
     $reason = required_param('reason', PARAM_TEXT);
     $urgent = required_param('urgent', PARAM_INT);
     
     $issue = get_record('tracker_issue', 'id', $issueid);

     tracker_notify_raiserequest($issue, $cm, $reason, $urgent, $tracker);
}

?>
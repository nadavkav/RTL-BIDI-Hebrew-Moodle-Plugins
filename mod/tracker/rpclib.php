<?PHP  // $Id: rpclib.php,v 1.3.2.8 2010/02/13 16:35:17 diml Exp $

/**
* @package mod-tracker
* @category mod
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* Library of functions for rpc remote calls at tracker. All complex
* variables transport are performed using JSON format. 
*/

include_once $CFG->libdir.'/pear/HTML/AJAX/JSON.php';

/**
* Constants
*
*/
if (!defined('RPC_SUCCESS')) {
    define ('RPC_SUCCESS', 200);
    define ('RPC_FAILURE', 500);
    define ('RPC_FAILURE_USER', 501);
}
/**
* checks an user has local identity and comes from a known host
* @param string $username the user's login
* @param string $remotehostroot the host he comes from
* @return a failure report if unchecked, null elsewhere.
*
*/
function tracker_rpc_check($username, $remotehostroot, &$localuser){

    // get local identity for user

    if (!$remotehost = get_record('mnet_host', 'wwwroot', $remotehostroot)){
        $response->status = RPC_FAILURE;
        $response->error = "Calling host is not registered. Check MNET configuration";
        return json_encode($response);
    }
    
    if (!$localuser = get_record_select('user', "username = '".addslashes($username)."' AND mnethostid = $remotehost->id AND deleted = 0")){
        $response->status = RPC_FAILURE_USER;
        $response->error = "Calling user has no local account. Register remote user first";
        return json_encode($response);
    }

    return null;
}

/*
* sends tracker information to remote caller. This is intended for
* administrative binding GUIs.
* @param int $trackerid
* @param boolean $nojson when true, avoids serializing through JSON syntax
* @return string a JSON encoded information structure.
*/
function tracker_rpc_get_infos($trackerid, $nojson = false){
    global $CFG;

    $tracker = get_record('tracker', 'id', "$trackerid");
    
    $query = "
        SELECT
            te.name,
            te.description,
            te.type 
        FROM
            {$CFG->prefix}tracker_element te,
            {$CFG->prefix}tracker_elementused teu
        WHERE
            te.id = teu.elementid AND
            teu.trackerid = {$trackerid}
    ";
    $elementused = get_records_sql($query);
    
    $tracker->elements = $elementused;

    if ($nojson){
        return $tracker;
    }

    return json_encode($tracker);
}

/*
* sends an array of available trackers. Returns only trackers
* the remote user has capability to manage. Note that this
* RPC call can be used locally.
* @param string $username the user's login
* @param string $remotehostroot the host he comes from
* @return a stub of instance descriptions
*/
function tracker_rpc_get_instances($username, $remotehostroot){
    global $CFG;
    
    if ($failedcheck = tracker_rpc_check($username, $remotehostroot, $localuser)) return $failedcheck;
    
    $response->status = RPC_SUCCESS;
    
    $trackers = get_records('tracker', '', '', 'name', 'id,name');
    if(!empty($trackers)){
        foreach($trackers as $id => $tracker){
            $cm = get_coursemodule_from_instance('tracker', $id);
            $modulecontext = get_context_instance(CONTEXT_MODULE, $cm->id);
            if (!has_capability('mod/tracker:report', $modulecontext, $localuser->id)){
                unset($trackers[$id]);
                $response->report[] = "ignoring tracker $id for capability reasons"; 
            }
        }
    }
    
    $response->trackers = $trackers;
    return json_encode($response);
}

/**
* remote post an entry in a tracker
* @param int $username the userame the post should come from
* @param string $remoteuserhostroot the userame the post should come from
* @param int $trackerid the local trackerid where to post
* @param string $remote_issue a JSON encoded variable containing all
* information about an issue.
* @return the local issue record id
*/
function tracker_rpc_post_issue($username, $remoteuserhostroot, $trackerid, $remote_issue){

    if ($failedcheck = tracker_rpc_check($username, $remoteuserhostroot, $localuser)) return $failedcheck;

    $response->status = RPC_SUCCESS;
    
    $issue = json_decode($remote_issue);

    // get additional data and cleanup the issue record for insertion
    if (isset($issue->attributes)){
        $attributes = clone($issue->attributes);
        unset($issue->attributes); // clears attributes so we have an issue record
    }

    $comment = $issue->comment;
    unset($issue->comment);

    unset($issue->id); // clears id, so it will be a new record
    $issue->trackerid = $trackerid;
    $issue->status = POSTED;
    $issue->reportedby = $localuser->id;
    $issue->assignedto = 0;
    $issue->bywhomid = 0;

    if (! $followid = insert_record('tracker_issue', addslashes_recursive($issue))){
        // TODO : error report
        $response->status = RPC_FAILURE;
        $response->error = "Remote error : Could not insert cascade issue record";
    }
    
    //TODO : rebind attributes and add them
    if (!empty($issue->attributes)){
        $tracker = get_record('tracker', 'id', "$trackerid");
        $used = tracker_getelementsused_by_name($tracker);
        foreach($issue->attributes as $attribute){
            // cleanup and crossmap attribute records
            $attribute->elementid = $used[$attribute->elementname]->id;
            unset($attribute->elementname);
            unset($attribute->id);
            $attribute->trackerid = $trackerid;
            $attribute->issueid = $followid;
            // don't really worry if it fails
            @insert_record('tracker_issueattribute', $attribute);
        }
    }
    
    // get comment track and add starting comment backtrace
    $issuecomment->trackerid = $trackerid;
    $issuecomment->issueid = $followid;
    $issuecomment->userid = $localuser->id;
    $issuecomment->comment = $comment;
    $issuecomment->commentformat = FORMAT_HTML;
    $issuecomment->datecreated = time();
    
    if (!insert_record('tracker_issuecomment', addslashes_recursive($issuecomment))){
        $response->status = RPC_FAILURE;
        $response->error = "Remote error : Could not insert cascade commment record";
    }

    $response->followid = $followid;

    return json_encode($response);
}

?>
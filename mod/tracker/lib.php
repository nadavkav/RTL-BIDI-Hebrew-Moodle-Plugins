<?PHP  // $Id: lib.php,v 1.1.10.7 2010/02/13 16:35:17 diml Exp $

/**
* @package mod-tracker
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* Library of functions and constants for module tracker
*/

include_once('classes/trackercategorytype/trackerelement.class.php');


/** 
* Given an object containing all the necessary data, 
* (defined by the form in mod.html) this function 
* will create a new instance and return the id number 
* of the new instance.
* @param object $tracker
*/
function tracker_add_instance($tracker) {

    $tracker->timemodified = time();

    return insert_record('tracker', $tracker);
}

/**
* Given an object containing all the necessary data, 
* (defined by the form in mod.html) this function 
* will update an existing instance with new data.
*/
function tracker_update_instance($tracker) {

    $tracker->timemodified = time();
    $tracker->id = $tracker->instance;

    return update_record('tracker', $tracker);
}

/**
* Given an ID of an instance of this module, 
* this function will permanently delete the instance 
* and any data that depends on it.  
*/
function tracker_delete_instance($id) {
    if (! $tracker = get_record('tracker', 'id', "$id")) {
        return false;
    }

    $result = true;

    /// Delete any dependent records here 
    delete_records('tracker_issue', 'trackerid', "$tracker->id");
    delete_records('tracker_elementused', 'trackerid', "$tracker->id");
    delete_records('tracker_query', 'trackerid', "$tracker->id");
    delete_records('tracker_issuedependancy', 'trackerid', "$tracker->id");
    delete_records('tracker_issueownership', 'trackerid', "$tracker->id");
    delete_records('tracker_issueattribute', 'trackerid', "$tracker->id");
    delete_records('tracker_issuecc', 'trackerid', "$tracker->id");
    delete_records('tracker_issuecomment', 'trackerid', "$tracker->id");

    return $result;
}

/**
* Return a small object with summary information about what a 
* user has done with a given particular instance of this module
* Used for user activity reports.
* $return->time = the time they did it
* $return->info = a short text description
*/
function tracker_user_outline($course, $user, $mod, $tracker) {

    return NULL;
}

/**
* Print a detailed representation of what a  user has done with 
* a given particular instance of this module, for user activity reports.
*/
function tracker_user_complete($course, $user, $mod, $tracker) {

    return NULL;
}

/**
* Given a course and a time, this module should find recent activity 
* that has occurred in tracker activities and print it out. 
* Return true if there was output, or false is there was none.
*/
function tracker_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;
    
    $sql = "
        SELECT
            t.name,
            t.ticketprefix,
            ti.id,
            ti.trackerid,
            ti.summary,
            ti.reportedby,
            ti.datereported
         FROM
            {$CFG->prefix}tracker t,
            {$CFG->prefix}tracker_issue ti
         WHERE
            t.id = ti.trackerid AND
            t.course = $course->id AND
            ti.datereported > $timestart
    ";
    $newstuff = get_records_sql($sql);
    if ($newstuff){
        foreach($newstuff as $anissue){
            echo "<span style=\"font-size:0.8em\">"; 
            echo get_string('modulename', 'tracker').': '.format_string($anissue->name).':<br/>';
            echo "<a href=\"{$CFG->wwwroot}/mod/tracker/view.php?a={$anissue->trackerid}&amp;view=view&amp;page=viewanissue&amp;issueid={$anissue->id}\">".shorten_text(format_string($anissue->summary), 20).'</a><br/>';
            echo '&nbsp&nbsp&nbsp<span class="trackersmalldate">'.userdate($anissue->datereported).'</span><br/>';
            echo "</span><br/>";
        }
        return true;
    }
    
    return false;  //  True if anything was printed, otherwise false 
}

/**
* Function to be run periodically according to the moodle cron
* This function searches for things that need to be done, such 
* as sending out mail, toggling flags etc ... 
*/
function tracker_cron () {

    global $CFG;

    return true;
}

/** 
* Must return an array of grades for a given instance of this module, 
* indexed by user.  It also returns a maximum allowed grade.
*
*    $return->grades = array of grades;
*    $return->maxgrade = maximum allowed grade;
*
*    return $return;
*/
function tracker_grades($trackerid) {

   return NULL;
}

/**
* Must return an array of user records (all data) who are participants
* for a given instance of tracker. Must include every user involved
* in the instance, independent of his role (student, teacher, admin...)
* See other modules as example.
*/
function tracker_get_participants($trackerid) {
    $resolvers = get_records('tracker_issueownership', 'trackerid', $trackerid, '', 'id,id');
    if(!$resolvers) $resolvers = array();
    $developers = get_records('tracker_issuecc', 'trackerid', $trackerid, '', 'id,id');
    if(!$developers) $developers = array();
    $reporters = get_records('tracker_issue', 'trackerid', $trackerid, '', 'reportedby,reportedby');
    if(!$reporters) $reporters = array();
    $admins = get_records('tracker_issueownership', 'trackerid', $trackerid, '', 'bywhomid,bywhomid');
    if(!$admins) $admins = array();
    $commenters = get_records('tracker_issuecomment', 'trackerid', $trackerid, '', 'userid,userid');
    if(!$commenters) $commenters = array();
    $participants = array_merge(array_keys($resolvers), array_keys($developers), array_keys($reporters), array_keys($admins));
    $participantlist = implode(',', array_unique($participants));
    
    if (!empty($participantlist)){
        return get_records_list('user', 'id', $participantlist);   
    }
    return array();
}

/*
* This function returns if a scale is being used by one tracker
* it it has support for grading and scales. Commented code should be
* modified if necessary. See forum, glossary or journal modules
* as reference.
*/
function tracker_scale_used ($trackerid, $scaleid) {
   
    $return = false;

    //$rec = get_record("tracker","id","$trackerid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

/**
*
*
*/
function tracker_install(){
    
    $result = true;

    if (!get_record('mnet_service', 'name', 'tracker_cascade')){
        $service->name = 'tracker_cascade';
        $service->description = get_string('transferservice', 'tracker');
        $service->apiversion = 1;
        $service->offer = 1;
        if (!$serviceid = insert_record('mnet_service', $service)){
            notify('Error installing tracker_cascade service.');
            $result = false;
        }
        
        $rpc->function_name = 'tracker_rpc_get_instances';
        $rpc->xmlrpc_path = 'mod/tracker/rpclib.php/tracker_rpc_get_instances';
        $rpc->parent_type = 'mod';  
        $rpc->parent = 'tracker';
        $rpc->enabled = 0; 
        $rpc->help = 'Get instances of available trackers for cascading.';
        $rpc->profile = '';
        if (!$rpcid = insert_record('mnet_rpc', $rpc)){
            notify('Error installing tracker_cascade RPC calls.');
            $result = false;
        }
        $rpcmap->serviceid = $serviceid;
        $rpcmap->rpcid = $rpcid;
        insert_record('mnet_service2rpc', $rpcmap);
        
        $rpc->function_name = 'tracker_rpc_get_infos';
        $rpc->xmlrpc_path = 'mod/tracker/rpclib.php/tracker_rpc_get_infos';
        $rpc->parent_type = 'mod';  
        $rpc->parent = 'tracker';
        $rpc->enabled = 0; 
        $rpc->help = 'Get information about one tracker.';
        $rpc->profile = '';
        if (!$rpcid = insert_record('mnet_rpc', $rpc)){
            notify('Error installing tracker_cascade RPC calls.');
            $result = false;
        }
        $rpcmap->rpcid = $rpcid;
        insert_record('mnet_service2rpc', $rpcmap);

        $rpc->function_name = 'tracker_rpc_post_issue';
        $rpc->xmlrpc_path = 'mod/tracker/rpclib.php/tracker_rpc_post_issue';
        $rpc->parent_type = 'mod';  
        $rpc->parent = 'tracker';
        $rpc->enabled = 0; 
        $rpc->help = 'Cascades an issue.';
        $rpc->profile = '';
        if (!$rpcid = insert_record('mnet_rpc', $rpc)){
            notify('Error installing tracker_cascade RPC calls.');
            $result = false;
        }
        $rpcmap->rpcid = $rpcid;
        insert_record('mnet_service2rpc', $rpcmap);
    }
    
    return $result;
}

/**
* a standard module API call for making some custom uninstall tasks 
*
*/
function tracker_uninstall(){
    
    $return = true;
    
    // delete all tracker related mnet services and MNET bindings
    $service = get_record('mnet_service', 'name', 'tracker_cascade');
    if ($service){
        delete_records('mnet_host2service', 'serviceid', $service->id);
        delete_records('mnet_service2rpc', 'serviceid', $service->id);
        delete_records('mnet_rpc', 'parent', 'tracker');
        delete_records('mnet_service', 'name', 'tracker_cascade');
    }
    
    return $return;
}
?>

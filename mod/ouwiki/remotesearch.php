<?php
/**
 * Provide federated-search access to wiki. This facility searches all
 * wikis that a user has access to.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */
global $DISABLESAMS;
$DISABLESAMS = true;
require_once('../../config.php');
require_once('../../blocks/ousearch/searchlib.php');

define('OUWIKI_MAXRESULTS',50);
define('OUWIKI_RESULTSPERPAGE',10);

// Security 
ousearch_require_remote_access();
 
// Get basic details
$username=required_param('username',PARAM_RAW);
$query=stripslashes(required_param('query',PARAM_RAW));
$first=optional_param('first',1,PARAM_INT);

// Locate user
$userid=get_field('user','id','username',$username);
if(!$userid) {
    error('No such user '.$username);
}

/**
 * Obtain list of all the modules of a given type to which the specified user
 * has access to a particular capability. (Usually the 'view' capability but
 * could be others.)
 * @param string $modulename Name of module e.g. 'ouwiki'
 * @param string $capability Name of capability e.g. 'mod/ouwiki:view'
 * @param int $userid ID of user
 * @param string $extracheck Additional SQL check e.g. 'cm.groupmode<>0'
 * @return array Array of course-module ID=>object containing ->id and ->course
 */
function get_all_accessible_modules_of_type($modulename,$capability,$userid=0,$extracheck='') {
    global $CFG,$USER;
    
    // Prepare values for use in query
    if(!$userid) {
        $userid=$USER->id;
    }
    $capability=addslashes($capability);
    $modulename=addslashes($modulename);
    $userid=(int)$userid;    
    $now=time();    
    if($extracheck) {
        $extracheck='AND '.$extracheck;
    }

    // This query obtains each permission for the given capability 
    // that applies, keeping track of the contextlevel (note that override
    // permissions apply at the contextlevel of the role, not at the contextlevel
    // of the override, and luckily it's easier to get that number).
     
    // Must use get_recordset not get_records as cm.id is not unique in this query.
    $rs=get_recordset_sql($query="
SELECT
    cm.id,cm.course,rc.permission,c2.contextlevel
FROM
    {$CFG->prefix}modules m
    INNER JOIN {$CFG->prefix}course_modules cm ON m.id=cm.module
    INNER JOIN {$CFG->prefix}context c ON c.instanceid=cm.id AND c.contextlevel=70
    INNER JOIN {$CFG->prefix}context c2 ON substring(c.path FOR char_length(c2.path)+1) = c2.path || '/'  
    INNER JOIN {$CFG->prefix}role_assignments ra ON ra.contextid=c2.id
    INNER JOIN {$CFG->prefix}role_capabilities rc ON rc.roleid=ra.roleid
WHERE
    m.name='$modulename'

    AND ra.userid=$userid 
    AND ra.hidden=0 
    AND ra.timestart<=$now 
    AND (ra.timeend=0 OR ra.timeend>$now)

    AND rc.capability='$capability'
    AND (rc.contextid=1 OR rc.contextid IN 
        (SELECT xc.id FROM {$CFG->prefix}context xc WHERE substring(xc.path FOR char_length(c2.path)+1) = c2.path || '/'))
    $extracheck
ORDER BY 
    cm.id
");

    // Combine the permissions to build this into a yes/no list of coursemodules.
    // Note that permissions are combined in the following manner:
    // * CAP_PROHIBIT anywhere results in no permission
    // * Only permissions at the most-specific (highest) contextlevel apply
    // * Permissions at this highest level are added together. A result more than
    //   0 means permitted.
    $coursemodules=array();
    $current=null;
    while($rec=rs_fetch_next_record($rs)) {
        if(!$current) {
            $current=$rec;
        } else if($rec->id===$current->id) {
            // Combine permissions.
            
            // If it's already on probibit, forget it, it's gone
            if($current->permission===CAP_PROHIBIT) {
                // Forget it, you can't un-prohibit
                continue;
            }
            if($rec->permission===CAP_PROHIBIT || $rec->contextlevel > $current->contextlevel) {
                // If this row's permission is prohibit, or this row is more specific than before,
                // then set it to this
                $current->permission=$rec->permission;
                $current->contextlevel=$rec->contextlevel;
            } else if($rec->contextlevel===$current->contextlevel) {
                // If at the same level, permissions are added up
                $current->permission+=$rec->permission;
            }            
            // Less specific contextlevels that weren't prohibit are ignored.            
        } else {
            if($current && $current->permission>0) {
                $coursemodules[$current->id]=$current;
            }
            $current=$rec;
        }
    }
    // Don't forget the last one
    if($current && $current->permission>0) {
        $coursemodules[$current->id]=$current;
    }
    rs_close($rs);
    return $coursemodules;
}

// Set up basic search with specified query
$search=new ousearch_search($query);

// Set up list of accessible groups and user ID
$groupids=array();
$rs=get_recordset_sql("
SELECT
  gm.groupid
FROM 
  {$CFG->prefix}groups_members gm
WHERE
  gm.userid=$userid  
");
while($rec=rs_fetch_next_record($rs)) {
    $groupids[]=$rec->id;
}
rs_close($rs);

$search->set_group_ids($groupids);
$search->set_user_id($userid);

// Get array of course-module info 
$accessible=get_all_accessible_modules_of_type('ouwiki','mod/ouwiki:view',$userid);
$search->set_coursemodule_array($accessible);

// Get exceptions where user can access all groups
$allgroups=get_all_accessible_modules_of_type('ouwiki','moodle/site:accessallgroups',$userid,'cm.groupmode<>0');
$search->set_group_exceptions($allgroups);

$results=$search->query(0,OUWIKI_MAXRESULTS);
ousearch_display_remote_results($results,$first,OUWIKI_RESULTSPERPAGE);

?>
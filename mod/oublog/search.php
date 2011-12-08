<?php
/**
 * Search results page.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package oublog
 *//** */
// This code tells OU authentication system to let the public access this page
// (subject to Moodle restrictions below and with the accompanying .sams file).
global $DISABLESAMS;
$DISABLESAMS = 'opt';

require_once('locallib.php');
require_once('../../blocks/ousearch/searchlib.php');

$id     = optional_param('id', 0, PARAM_INT);       // Course Module ID
$user   = optional_param('user', 0, PARAM_INT);     // User ID
$querytext=stripslashes(required_param('query',PARAM_RAW));
$querytexthtml=htmlspecialchars($querytext);

if ($id) {
    if (!$cm = get_coursemodule_from_id('oublog', $id)) {
        error("Course module ID was incorrect");
    }

    if (!$course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    if (!$oublog = get_record("oublog", "id", $cm->instance)) {
        error("Course module is incorrect");
    }
    $oubloguser->id = null;
    $oubloginstance = null;
    $oubloginstanceid = null;

} elseif ($user) {
    if (!$oubloguser = get_record('user', 'id', $user)) {
        error("User not found");
    }
    if (!list($oublog, $oubloginstance) = oublog_get_personal_blog($oubloguser->id)) {
        error("Course module is incorrect");
    }
    if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
        error("Course module ID was incorrect");
    }
    if (!$course = get_record("course", "id", $oublog->course)) {
        error("Course is misconfigured");
    }
    $oubloginstanceid = $oubloginstance->id;
} else {
    error("A required parameter is missing");
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
oublog_check_view_permissions($oublog, $context, $cm);

if ($oublog->global) {
    // Check this user is allowed to view the user's blog
    if($oublog->maxvisibility != OUBLOG_VISIBILITY_PUBLIC && isset($oubloguser)) {
        $usercontext = get_context_instance(CONTEXT_USER, $oubloguser->id);
        require_capability('mod/oublog:view', $usercontext);
    }
    $returnurl = $CFG->wwwroot . '/mod/oublog/view.php?user='.$user;
} else {
    $returnurl = $CFG->wwwroot . '/mod/oublog/view.php?id='.$id;
}

// Set up groups
$currentgroup = oublog_get_activity_group($cm, true);
$groupmode = oublog_get_activity_groupmode($cm, $course);
// Note I am not sure this check is necessary, maybe it is handled by
// oublog_get_activity_group? Or maybe more checks are needed? Not sure.
if($currentgroup===0 && $groupmode==SEPARATEGROUPS) {
    require_capability('moodle/site:accessallgroups',$context);
}

// Print the header
$stroublog      = get_string('modulename', 'oublog');
$strblogsearch  = get_string('searchthisblog', 'oublog');
$strblogssearch  = get_string('searchblogs', 'oublog');

$extranav=array('name'=>get_string('searchfor','block_ousearch',$querytext),'type'=>'misc');
if ($oublog->global) {
    if(!is_null($oubloginstance)) {
    	$name = $oubloginstance->name;
        $buttontext=<<<EOF
<form action="search.php" method="get"><div>
  <input type="hidden" name="user" value="{$oubloguser->id}"/>
  <input type="text" name="query" value="$querytexthtml"/>
  <input type="submit" value="{$strblogsearch}"/>
</div></form>
EOF;
    } else {
        $buttontext=<<<EOF
<form action="search.php" method="get"><div>
  <input type="hidden" name="id" value="{$cm->id}"/>
  <input type="text" name="query" value="$querytexthtml"/>
  <input type="submit" value="{$strblogssearch}"/>
</div></form>
EOF;
    }
    
    $navlinks = array();
    if(isset($name)){
        $navlinks[] = array('name' => fullname($oubloguser), 'link' => "../../user/view.php?id=$oubloguser->id", 'type' => 'misc');
        $navlinks[] = array('name' => format_string($oubloginstance->name), 'link' => $returnurl, 'type' => 'activityinstance');
    } else {
    	$navlinks[] = array('name' => format_string($oublog->name), 'link' => 'allposts.php', 'type' => 'activityinstance');
    }
    $navlinks[] = $extranav;
    $navigation = build_navigation($navlinks);
    print_header_simple(format_string($oublog->name), "", $navigation, "", oublog_get_meta_tags($oublog, $oubloginstance, $currentgroup, $cm), true,
                $buttontext, navmenu($course, $cm));

} else {
    $name = $oublog->name;

          $buttontext=<<<EOF
<form action="search.php" method="get"><div>
  <input type="hidden" name="id" value="{$cm->id}"/>
  <input type="text" name="query" value="$querytexthtml"/>
  <input type="submit" value="{$strblogsearch}"/>
</div></form>
EOF;

    $navlinks = array();
    $navlinks[] = $extranav;
    $navigation = build_navigation($navlinks, $cm);

    print_header_simple(format_string($oublog->name), "", $navigation, "", oublog_get_meta_tags($oublog, $oubloginstance, $currentgroup, $cm), true,
                  $buttontext, navmenu($course, $cm));
}

// Print Groups
groups_print_activity_menu($cm, $returnurl);

global $modulecontext,$personalblog;
$modulecontext=$context;
$personalblog=$oublog->global ? true : false;

/**
 * Function filters search results to exclude ones that don't meet the
 * visibility criterion.
 *
 * @param object $result Search result data
 */
function visibility_filter(&$result) {
    global $USER,$modulecontext,$personalblog;
    return oublog_can_view_post($result->data,$USER,$modulecontext,$personalblog);
}

// FINALLY do the actual query
$query=new ousearch_search($querytext);
$query->set_coursemodule($cm);
if($oublog->global && isset($oubloguser)) {
    $query->set_user_id($oubloguser->id);
}
if($groupmode && $currentgroup) {
    $query->set_group_id($currentgroup);
}
$query->set_filter('visibility_filter');

$searchurl='search.php?'.($oublog->global ? 'user='.$oubloguser->id : 'id='.$cm->id);

$foundsomething=ousearch_display_results($query,$searchurl);

if(!$foundsomething) {
    add_to_log($COURSE->id,'oublog','view searchfailure',
        $searchurl.'&query='.urlencode($querytext));
}

// Footer
print_footer();
?>


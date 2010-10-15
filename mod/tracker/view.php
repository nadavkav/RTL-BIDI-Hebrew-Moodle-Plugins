<?PHP  // $Id: view.php,v 1.1.10.8 2010/02/13 16:35:17 diml Exp $

/**
* @package mod-tracker
* @category mod
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* This page prints a particular instance of a tracker and handles
* top level interactions
*/

require_once("../../config.php");
require_once($CFG->dirroot."/mod/tracker/lib.php");
require_once($CFG->dirroot."/mod/tracker/locallib.php");

$usehtmleditor = false;
$editorfields = '';

/// Check for required parameters - Course Module Id, trackerID,

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // tracker ID
$issueid = optional_param('issueid', '', PARAM_INT);  // issue number

// PART OF MVC Implementation
$action = optional_param('what', '', PARAM_ALPHA);
$screen = optional_param('screen', '', PARAM_ALPHA);
$view = optional_param('view', '', PARAM_ALPHA);
// !PART OF MVC Implementation

if ($id) {
    if (! $cm = get_coursemodule_from_id('tracker', $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record('course', 'id', $cm->course)) {
        error("Course is misconfigured");
    }

    if (! $tracker = get_record('tracker', 'id', $cm->instance)) {
        error("Course module is incorrect");
    }
} else {
    if (! $tracker = get_record('tracker', 'id', $a)) {
        error("Course module is incorrect");
    }
    if (! $course = get_record('course', 'id', $tracker->course)) {
        error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("tracker", $tracker->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course->id);

add_to_log($course->id, 'tracker', "$view:$screen/$action", "view.php?id=$cm->id", "$tracker->id", $cm->id);

$usehtmleditor = can_use_html_editor();
$defaultformat = FORMAT_MOODLE;
tracker_loadpreferences($tracker->id, $USER->id);

/// Search controller - special implementation
// TODO : consider incorporing this controller back into standard MVC
if ($action == 'searchforissues'){
    $search = optional_param('search', null, PARAM_CLEANHTML);
    $saveasreport = optional_param('saveasreport', null, PARAM_CLEANHTML);

    if (!empty($search)){       //search for issues
        tracker_searchforissues($tracker, $cm->id);
    } elseif (!empty ($saveasreport)){        //save search as a report
        tracker_saveasreport($tracker->id);
    }
} elseif ($action == 'viewreport'){
    tracker_viewreport($tracker->id);
} elseif ($action == 'clearsearch'){
    if (tracker_clearsearchcookies($tracker->id)){
        $returnview = ($tracker->supportmode == 'bugtracker') ? 'browse' : 'mytickets' ;
        redirect("view.php?id={$cm->id}&amp;screen={$returnview}");
    }
}

$strtrackers = get_string('modulenameplural', 'tracker');
$strtracker  = get_string('modulename', 'tracker');

$navigation = build_navigation('', $cm);
print_header_simple(format_string($tracker->name), "",
                  $navigation, "", "", true,
                  update_module_button($cm->id, $course->id, $strtracker), navmenu($course, $cm));

/// integrate module specific stylesheets (calls an eventual theme override)
echo '<link rel="stylesheet" href="'.$CFG->themewww.'/'.current_theme().'/tracker.css" type="text/css" />';
echo '<link rel="stylesheet" href="'.$CFG->wwwroot.'/mod/tracker/styles.php" type="text/css" />';

// PART OF MVC Implementation
/// memorizes current view - typical session switch
if (!empty($view)){
    $_SESSION['currentview'] = $view;
} elseif (empty($_SESSION['currentview'])) {
    $_SESSION['currentview'] = 'reportanissue';
}
$view = $_SESSION['currentview'];

/// memorizes current screen - typical session switch
if (!empty($screen)){
    $_SESSION['currentscreen'] = $screen;
} elseif (empty($_SESSION['currentscreen'])) {
    $_SESSION['currentscreen'] = '';
}
$screen = $_SESSION['currentscreen'];
// !PART OF MVC Implementation

print_box_start('', 'tracker-view');

$totalissues = count_records_select('tracker_issue', "trackerid = {$tracker->id} AND status <> ".RESOLVED." AND status <> ".ABANDONNED);
$totalresolvedissues = count_records_select('tracker_issue', "trackerid = $tracker->id AND (status = ".RESOLVED." OR status = ".ABANDONNED.")");
/// Print tabs with options for user
$rows[0][] = new tabobject('reportanissue', "view.php?id={$cm->id}&amp;view=reportanissue", get_string('newissue', 'tracker'));
$rows[0][] = new tabobject('view', "view.php?id={$cm->id}&amp;view=view", get_string('view', 'tracker').' ('.$totalissues.' '.get_string('issues','tracker').')');
$rows[0][] = new tabobject('resolved', "view.php?id={$cm->id}&amp;view=resolved", get_string('resolvedplural', 'tracker').' ('.$totalresolvedissues.' '.get_string('issues','tracker').')');
$rows[0][] = new tabobject('profile', "view.php?id={$cm->id}&amp;view=profile", get_string('profile', 'tracker'));
if (has_capability('moodle/tracker:configure', $context)){
    $rows[0][] = new tabobject('admin', "view.php?id={$cm->id}&amp;view=admin", get_string('administration', 'tracker'));
}

/// submenus
$selected = null;
$activated = null;
switch ($view){
    case 'reportanissue':
        $screen = '';
        $selected = $view;
    	break;
    case 'view' :
        if (!preg_match("/mytickets|browse|search|viewanissue|editanissue/", $screen)) $screen = 'mytickets';
        $rows[1][] = new tabobject('mytickets', "view.php?id={$cm->id}&amp;view=view&amp;screen=mytickets", get_string('mytickets', 'tracker'));
        if (has_capability('mod/tracker:viewallissues', $context) || $tracker->supportmode == 'bugtracker'){
            $rows[1][] = new tabobject('browse', "view.php?id={$cm->id}&amp;view=view&amp;screen=browse", get_string('browse', 'tracker'));
        }
        $rows[1][] = new tabobject('search', "view.php?id={$cm->id}&amp;view=view&amp;screen=search", get_string('search', 'tracker'));
        break;
    case 'resolved' :
        if (!preg_match("/mytickets|browse/", $screen)) $screen = 'mytickets';
        $rows[1][] = new tabobject('mytickets', "view.php?id={$cm->id}&amp;view=resolved&amp;screen=mytickets", get_string('mytickets', 'tracker'));
        if (has_capability('mod/tracker:viewallissues', $context) || $tracker->supportmode == 'bugtracker'){
            $rows[1][] = new tabobject('browse', "view.php?id={$cm->id}&amp;view=resolved&amp;screen=browse", get_string('browse', 'tracker'));
        }
    break;
    case 'profile':
        if (!preg_match("/myprofile|mypreferences|mywatches|myqueries/", $screen)) $screen = 'myprofile';
        $rows[1][] = new tabobject('myprofile', "view.php?id={$cm->id}&amp;view=profile&amp;screen=myprofile", get_string('myprofile', 'tracker'));
        $rows[1][] = new tabobject('mypreferences', "view.php?id={$cm->id}&amp;view=profile&amp;screen=mypreferences", get_string('mypreferences', 'tracker'));
        $rows[1][] = new tabobject('mywatches', "view.php?id={$cm->id}&amp;view=profile&amp;screen=mywatches", get_string('mywatches', 'tracker'));
        $rows[1][] = new tabobject('myqueries', "view.php?id={$cm->id}&amp;view=profile&amp;screen=myqueries", get_string('myqueries', 'tracker'));
    break;
    case 'admin':
        if (!preg_match("/summary|manageelements|managenetwork/", $screen)) $screen = 'summary';
        $rows[1][] = new tabobject('summary', "view.php?id={$cm->id}&amp;view=admin&amp;screen=summary", get_string('summary', 'tracker'));
        $rows[1][] = new tabobject('manageelements', "view.php?id={$cm->id}&amp;view=admin&amp;screen=manageelements", get_string('manageelements', 'tracker'));
        $rows[1][] = new tabobject('managenetwork', "view.php?id={$cm->id}&amp;view=admin&amp;screen=managenetwork", get_string('managenetwork', 'tracker'));
        break;
    default:
}
if (!empty($screen)){
    $selected = $screen;
    $activated = array($view);
} else {
    $selected = $view;
}
print_container_start(true, 'mod-header');
print_tabs($rows, $selected, '', $activated);
print_container_end();

//=====================================================================
// Print the main part of the page
//
//=====================================================================

/// routing to appropriate view against situation
// echo "routing : $view:$screen:$action ";

if ($view == 'reportanissue'){
    if (has_capability('mod/tracker:report', $context)){
        include "views/issuereportform.html";
    } else {
        notice(get_string('youneedanaccount','tracker'), $CFG->wwwroot."/course/view.php?id={$course->id}");
    }
} elseif ($view == 'view'){
    $result = 0 ;
    if ($action != ''){
        $result = include "views/view.controller.php";
    }
    if ($result != -1){
        switch($screen){
            case 'mytickets':
                $resolved = 0;
                include "views/viewmyticketslist.php";
                break;
            case 'browse':
                if (!has_capability('mod/tracker:viewallissues', $context)){
                    error ('You do not have access to view all issues.');
                } else {
                    $resolved = 0;
                    include "views/viewissuelist.php";
                }
                break;
            case 'search':
                include "views/searchform.html";
                break;
            case 'viewanissue' :
                ///If user it trying to view an issue, check to see if user has privileges to view this issue
                if ($issueid != ''){
                    if (!has_capability('mod/tracker:seeissues', $context)){
                        error ('You do not have access to view this issue.');
                    } else {
                        include "views/viewanissue.html";
                    }
                } else {
                    redirect("view.php?id={$cm->id}&amp;page=browse");
                }
                break;
            case 'editanissue' :
                if ($issueid != ''){
                    if (!has_capability('mod/tracker:manage', $context)){
                        error ("You do not have access to edit this issue.");
                    } else {
                        include "views/editanissue.html";
                    }
                } else {
                    redirect("view.php?id={$cm->id}&amp;page=browse");
                }
                break;
        }
    }
} elseif ($view == 'resolved'){
    $result = 0 ;
    if ($action != ''){
        $result = include "views/view.controller.php";
    }
    if ($result != -1){
        switch($screen){
            case 'mytickets':
                $resolved = 1;
                include "views/viewmyticketslist.php";
                break;
            case 'browse':
                if (!has_capability('mod/tracker:viewallissues', $context)){
                    error ('You do not have access to view all issues.');
                } else {
                    $resolved = 1;
                    include "views/viewissuelist.php";
                }
                break;
        }
    }
} elseif ($view == 'admin') {
    $result = 0;
    if ($action != ''){
        $result = include "views/admin.controller.php";
    }
    if ($result != -1){
        switch($screen){
            case 'summary':
                include "views/admin_summary.html";
                break;
            case 'manageelements':
                include "views/admin_manageelements.html";
                break;
            case 'managenetwork':
                include "views/admin_mnetwork.html";
                break;
        }
    }
}
elseif ($view == 'profile'){
    $result = 0;
    if ($action != ''){
        $result = include "views/profile.controller.php";
    }
    if ($result != -1){
        switch($screen){
            case 'myprofile' :
                include "views/profile.html";
                break;
            case 'mypreferences' :
                include "views/mypreferences.html";
                break;
            case 'mywatches' :
                include "views/mywatches.html";
                break;
            case 'myqueries':
                include "views/myqueries.html";
                break;
        }
    }
} else {
    error("Error:  Cannot find action: " . $action);
}

print_box_end();

/// Finish the page
if (empty($nohtmleditorneeded) and $usehtmleditor) {
    use_html_editor($editorfields);
}
print_footer($course);
?>

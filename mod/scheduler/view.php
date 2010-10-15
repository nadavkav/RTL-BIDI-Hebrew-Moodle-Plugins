<?PHP  // $Id: view.php,v 1.19.10.7 2009/06/24 23:02:36 diml Exp $

    /**
    * This page prints a particular instance of scheduler and handles
    * top level interactions
    *
    * @package mod-scheduler
    * @category mod
    * @author Gustav Delius, Valery Fremaux > 1.8
    *
    */

    /**
    * Requires and includes
    */    
    require_once('../../config.php');
    require_once($CFG->dirroot.'/mod/scheduler/lib.php');
    require_once($CFG->dirroot.'/mod/scheduler/locallib.php');
        
    // common parameters
    $id = optional_param('id', '', PARAM_INT);    // Course Module ID, or
    $a = optional_param('a', '', PARAM_INT);     // scheduler ID
    $action = optional_param('what', 'view', PARAM_CLEAN); 
    $subaction = optional_param('subaction', '', PARAM_CLEAN);
    $page = optional_param('page', 'allappointments', PARAM_CLEAN);
    $offset = optional_param('offset', '');
    $usehtmleditor = false;
    $editorfields = '';
    
    if ($id) {
        if (! $cm = get_record('course_modules', 'id', $id)) {
            error('Course Module ID was incorrect');
        }
    
        if (! $course = get_record('course', 'id', $cm->course)) {
            error('Course is misconfigured');
        }
    
        if (! $scheduler = get_record('scheduler', 'id', $cm->instance)) {
            error('Course module is incorrect');
        }
    
    } else {
        if (! $scheduler = get_record('scheduler', 'id', $a)) {
            error('Course module is incorrect');
        }
        if (! $course = get_record('course', 'id', $scheduler->course)) {
            error('Course is misconfigured');
        }
        if (! $cm = get_coursemodule_from_instance('scheduler', $scheduler->id, $course->id)) {
            error('Course Module ID was incorrect');
        }
    }
    
    require_login($course->id);
        
    // echo " [$action:$subaction] "; //$$DEBUG$$
    
    add_to_log($course->id, 'scheduler', "$action:$subaction", "view.php?id={$cm->id}", $scheduler->id, $cm->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $groupmode = groupmode($COURSE, $cm);

/// Security trap if module is not visible

    if (!has_capability('moodle/course:viewhiddenactivities', $context) && !$cm->visible){
        error("The module was set to not visible. You cannot access this URL at the moment.");
    }

/// This is a pre-header selector for downloded documents generation

    if (has_capability('mod/scheduler:manage', $context) || isteacher($course->id, $USER->id)) {
        if (preg_match("/downloadexcel|downloadcsv|downloadods|dodownloadcsv/", $action)){
            include 'downloads.php';
        }
    }

/// Print the page header

    $strschedulers = get_string('modulenameplural', 'scheduler');
    $strscheduler  = get_string('modulename', 'scheduler');
    $strtime = get_string('time');
    $strdate = get_string('date', 'scheduler');
    $strstart = get_string('start', 'scheduler');
    $strend = get_string('end', 'scheduler');
    $strname = get_string('name');
    $strseen = get_string('seen', 'scheduler');
    $strnote = get_string('comments', 'scheduler');
    $strgrade = get_string('note', 'scheduler');
    $straction = get_string('action', 'scheduler');
    $strduration = get_string('duration', 'scheduler');
    $stremail = get_string('email');
    
    $navigation = build_navigation('', $cm);
    print_header_simple($scheduler->name, '',
        $navigation, '', '', true, update_module_button($cm->id, $course->id, $strscheduler), 
                  navmenu($course, $cm));

/// integrate module specific stylesheets overrides by theme

    echo '<link rel="stylesheet" href="'.$CFG->themewww.'/'.current_theme().'/scheduler.css" type="text/css" />';

/// route to screen
    
    // teacher side
    if (has_capability('mod/scheduler:manage', $context)) {
        if ($action == 'viewstatistics'){
            include 'viewstatistics.php';
        }
        elseif ($action == 'viewstudent'){
            include "viewstudent.php";
        }
        elseif ($action == 'downloads'){
            include "downloads.php";
        }
        elseif ($action == 'datelist'){
            include 'datelist.php';
        }
        else{
            include 'teacherview.php';
        }
    }
        
    // student side
    elseif (isstudent($course->id) || has_capability('mod/scheduler:appoint', $context)) { 
        include 'studentview.php';
    }
    // for guests
    else {
        echo "<br/>";
        print_simple_box(get_string('guestscantdoanything', 'scheduler'), 'center', '70%');
    }    

/// Finish the page

    if (empty($nohtmleditorneeded) and $usehtmleditor) {
        use_html_editor($editorfields);
    }
    print_footer($course);

?>
<?php  // $Id: index.php,v 1.1.2.1 2010/08/20 10:55:20 diml Exp $

    require_once('../../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once($CFG->libdir.'/excellib.class.php');
    // require_once('lib.php');
    
    $id         = required_param('id', PARAM_INT); // course id.
    $ashtml     = optional_param('ashtml', null, PARAM_BOOL);
    $asxls      = optional_param('asxls', null, PARAM_BOOL);
    $view       = optional_param('view', 'user', PARAM_ALPHA);
    
    if (!$course = get_record('course', 'id', $id)) {
        print_error('invalidcourse');
    }

    $navlinks[] = array(
                        'name' => format_string($course->fullname),
                        'link' => $CFG->wwwroot.'/course/view.php?id='.$course->id,
                        'type' => 'link');
    $navlinks[] = array(
                        'name' => get_string('trainingsessionsreport','report_trainingsessions'),
                        'link' => '',
                        'type' => 'title');

    $navigation = build_navigation($navlinks);

    require_login();

    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('coursereport/trainingsessions:view', $context);
    
    add_to_log($course->id, "course", "trainingreports view", "/course/report/trainingsessions/index.php?id=$course->id", $course->id);

    if (!$asxls){
        print_header(get_string('reports', 'report_trainingsessions'), get_string('reports', 'report_trainingsessions'), $navigation);    
        
        print_container_start();
    
        /// Print tabs with options for user
        $rows[0][] = new tabobject('user', "index.php?id={$course->id}&amp;view=user", get_string('user', 'report_trainingsessions'));
        $rows[0][] = new tabobject('course', "index.php?id={$course->id}&amp;view=course", get_string('course', 'report_trainingsessions'));
        
        print_tabs($rows, $view);
    }

    @ini_set('max_execution_time','3000');
    raise_memory_limit('250M');

    if (file_exists($CFG->dirroot."/course/report/trainingsessions/{$view}report.php")){
        include $CFG->dirroot."/course/report/trainingsessions/{$view}report.php";
    } else {
        print_error('non existing report view');
    }

    if (!$asxls){
        print_footer($course);
    }

?>

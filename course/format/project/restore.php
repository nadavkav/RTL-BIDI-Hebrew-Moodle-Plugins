<?php //$Id: restore.php,v 1.44.2.2 2007/12/29 20:29:00 poltawski Exp $
    //This script is used to configure and execute the restore proccess.

    // 初期設定
    //Units used
    require_once ("../../../config.php");
    
    require_once("$CFG->libdir/xmlize.php");
    require_once("$CFG->libdir/filelib.php");
    require_once("$CFG->dirroot/backup/lib.php");
    require_once("$CFG->dirroot/backup/restorelib.php");
    require_once("./lib.php");
    require_once("./converter.php");
    require_once("./restorelib.php");
    require_once("$CFG->dirroot/backup/bb/restore_bb.php");
    require_once("$CFG->libdir/blocklib.php");
    require_once("$CFG->libdir/wiki_to_markdown.php" );
    require_once("$CFG->libdir/adminlib.php");
    
    //Optional
    $id = required_param( 'id' );
    $section = required_param('section');
    $file = optional_param( 'file' );
    $cancel = optional_param( 'cancel' );
    $launch = optional_param( 'launch' );
    $newdirectoryname = optional_param('newdirectoryname');
    $method = optional_param( 'method' );
    $to = optional_param('to');
    
    $backup_unique_code = optional_param('backup_unique_code',0,PARAM_INT);

    //Check login
    require_login();


/// With method=manual, we come from the FileManager so we delete all the backup/restore/import session structures
    if ($method == 'manual') {
        if (isset($SESSION->course_header)) {
            unset ($SESSION->course_header);
        }
        if (isset($SESSION->info)) {
            unset ($SESSION->info);
        }
        if (isset($SESSION->backupprefs)) {
            unset ($SESSION->backupprefs);
        }
        if (isset($SESSION->restore)) {
            unset ($SESSION->restore);
        }
        if (isset($SESSION->import_preferences)) {
            unset ($SESSION->import_preferences);
        }
    }

    if (!$to && isset($SESSION->restore->restoreto) && isset($SESSION->restore->importing) && isset($SESSION->restore->course_id)) {
        $to = $SESSION->restore->course_id;
    }
    
    require_login($id);
    if (!has_capability('moodle/site:restore', get_context_instance(CONTEXT_COURSE, $id))) {
        if (empty($to)) {
            error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
        } else {
            if (!has_capability('moodle/site:restore', get_context_instance(CONTEXT_COURSE, $to))
                && !has_capability('moodle/site:import',  get_context_instance(CONTEXT_COURSE, $to))) {
                error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
            }
        }
    }

    //Check site
    if (!$site = get_site()) {
        error("Site not found!");
    }

    //Check necessary functions exists. Thanks to gregb@crowncollege.edu
    backup_required_functions();

// upgrade_backup_db() は複数回呼ぶとエラーになるのでここでは行わず SharingCart_Restore に任せる
//    //Check backup_version
//    if ($file) {
//        $linkto = "restore.php?id=".$id."&amp;file=".$file;
//    }
//    upgrade_backup_db($linkto);

    //Get strings
    if (empty($to)) {
        $strcourserestore = get_string("courserestore");
    } else {
        $strcourserestore = get_string("importdata");
    }
    $stradministration = get_string("administration");

// HTML
    //Define some globals for all the script
    //If no file has been selected from the FileManager, inform and end
    $navlinks = array();
    $navlinks[] = array('name' => $stradministration, 'link' => "$CFG->wwwroot/$CFG->admin/index.php", 'type' => 'misc');
    $navlinks[] = array('name' => $strcourserestore, 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);

    if (!$file) {
        print_header("$site->shortname: $strcourserestore", $site->fullname, $navigation);
        print_heading(get_string("nofilesselected"));
        print_continue("$CFG->wwwroot/$CFG->admin/index.php");
        print_footer();
        exit;
    }

    //If cancel has been selected, inform and end
    if ($cancel) {
        print_header("$site->shortname: $strcourserestore", $site->fullname, $navigation);
        print_heading(get_string("restorecancelled"));
        print_continue("$CFG->wwwroot/course/view.php?id=".$id);
        print_footer();
        exit;
    }

    //We are here, so me have a file.

    //Get and check course
    if (! $course = get_record("course", "id", $id)) {
        error("Course ID was incorrect (can't find it)");
    }
    
    //Print header
    if (has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM, SITEID))) {
        $navlinks[] = array('name' => basename($file), 'link' => null, 'type' => 'misc');
        $navigation = build_navigation($navlinks);

        print_header("$site->shortname: $strcourserestore", $site->fullname, $navigation);
    } else {
        $navlinks = array();
        $navlinks[] = array('name' => $course->shortname, 'link' => "$CFG->wwwroot/course/view.php?id=$course->id", 'type' => 'misc');
        $navlinks[] = array('name' => $strcourserestore, 'link' => null, 'type' => 'misc');
        $navigation = build_navigation($navlinks);
        print_header("$course->shortname: $strcourserestore", $course->fullname, $navigation);
    }
    //Print form
    print_heading("$strcourserestore".((empty($to) ? ': '.basename($file) : '')));
    print_simple_box_start('center');

    //Adjust some php variables to the execution of this script
    @ini_set("max_execution_time","3000");
    raise_memory_limit("192M");
    
    if (!$launch) {
        include_once("restore_precheck.php");
        
        // 内容確認画面
        if (!empty($SESSION->restore->importing)) {
            // set up all the config stuff and skip asking the user about it.
            restore_setup_for_check($SESSION->restore,$backup_unique_code);
            include_once("restore_execute.php");
        } else {
            // 内容のチェック
            include_once("restore_check.php");
            //To avoid multiple restore executions...
            $SESSION->cancontinue = true;
        }
    } elseif ($launch == 'execute') {
        // リストアの実行
        //Prevent multiple restore executions...
        if (empty($SESSION->cancontinue)) {
            error("Multiple restore execution not allowed!");
        }
        //Unset this for the future
        unset($SESSION->cancontinue);
        include_once("restore_execute.php");
    }    
    
    print_simple_box_end();

    //Print footer
    print_footer();

?>

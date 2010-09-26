<?php
//  adds or updates modules in a course using new formslib

    require_once("../../../config.php");
    require_once("../../lib.php");
    require_once(dirname(__FILE__) . '/lib.php');
    require_once($CFG->dirroot.'/lib/uploadlib.php');
    
    //require_login();

    // フォーム値を取得する
    $courseid = required_param('courseid', PARAM_INT);
    $sectionid = required_param('sectionid', PARAM_INT);
    $isregister = required_param('isregister', PARAM_INT);

    // コース情報の取得
    if (! $course = get_record("course", "id", $courseid) ) {
        echo('ng');
    }
    
    // セクションタイトル情報を取得
    if (! $sectiontitle = get_record("course_project_title", "sectionid", $sectionid)) {
        echo('ng');
    }
    
    // セクションデータを取得
    if (! $section = get_record("course_sections", "id", $sectionid)) {
        echo('ng');
    }

    // 保存するディレクトリの取得
    if (! $basedir = make_upload_directory($course->id)) {
        echo('ng');
    }
    
    // ファイルのアップロード処理
    $course->maxbytes = 0;  // We are ignoring course limits
    $um = new upload_manager('Filedata',false,false,$course,false,0);
    $dir = "$basedir/{$sectiontitle->directoryname}";
    if (!$um->process_file_uploads($dir)) {
        echo('ng');
    }

    // セクションにファイルを登録
    if ($isregister) {
        // モジュールを追加する
        if (! $module = get_record("modules", "name", 'resource')) {
            echo('ng');
        }

        //$context = get_context_instance(CONTEXT_COURSE, $course->id);
        //require_capability('moodle/course:manageactivities', $context);
        //require_login($course->id); // needed to setup proper $COURSE
        
        // resourceモジュールを読み込む
        require_once("$CFG->dirroot/mod/resource/mod_form.php");
        include_once("$CFG->dirroot/mod/resource/lib.php");

        // 
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        //require_capability('moodle/course:manageactivities', $context);
        
        // ディレクトリ名をチェック
        if ($sectiontitle->directoryname) {
            $dirname = $sectiontitle->directoryname . '/';
        } else {
            $dirname = '';
        }
        
        // フォーム操作を再現
        $fromform              = new StdClass();
        $fromform->type        = 'file';
        $fromform->name        = $_FILES['Filedata']['name'];
        $fromform->summary     = '';
        $fromform->reference   = $dirname . clean_filename($_FILES['Filedata']['name']);
        $fromform->windowpopup = 0;
        $fromform->visible     = 1;
        $fromform->course      = $course->id;
        $fromform->coursemodule = '';
        $fromform->section     = $section->section;
        $fromform->module      = $module->id;
        $fromform->modulename  = $module->name;
        $fromform->instance    = '';
        $fromform->add         = $module->name;
        $fromform->update      = 0;
        $fromform->return      = 0;
        $fromform->submitbutton = 'save';
        $returnfromfunc = resource_add_instance($fromform);
        
        if (!$returnfromfunc) {
            echo('ng');
        }
    
        if (!isset($fromform->groupmode)) { // to deal with pre-1.5 modules
            $fromform->groupmode = $course->groupmode;  /// Default groupmode the same as course
        }
    
        $fromform->instance = $returnfromfunc;
    
        // course_modules and course_sections each contain a reference
        // to each other, so we have to update one of them twice.
    
        if (! $fromform->coursemodule = add_course_module($fromform) ) {
            echo('ng');
        }
        if (! $sectionid = add_mod_to_section($fromform) ) {
            echo('ng');
        }
    
        if (! set_field("course_modules", "section", $sectionid, "id", $fromform->coursemodule)) {
            echo('ng');
        }
    
        if (!isset($fromform->visible)) {   // We get the section's visible field status
            $fromform->visible = get_field("course_sections","visible","id",$sectionid);
        }
        // make sure visibility is set correctly (in particular in calendar)
        set_coursemodule_visible($fromform->coursemodule, $fromform->visible);
    
        add_to_log($course->id, "course", "add mod",
                   "../mod/$fromform->modulename/view.php?id=$fromform->coursemodule",
                   "$fromform->modulename $fromform->instance");
        add_to_log($course->id, $fromform->modulename, "add",
                   "view.php?id=$fromform->coursemodule",
                   "$fromform->instance", $fromform->coursemodule);
           
        rebuild_course_cache($course->id);
    }

    echo('ok');
    exit;
?>

<?php // preliminary page to find a course to import data from & interface with the backup/restore functionality

// 初期設定
    require_once('../../../config.php');
    require_once('./lib.php');
    require_once($CFG->dirroot.'/course/lib.php');
    require_once($CFG->dirroot.'/backup/restorelib.php');
    require_once('./restorelib.php');

    // フォーム値
    $id               = required_param('id');      // course id to import TO
    $tosection        = required_param('section'); // section number to import TO
    $overwrite        = optional_param('overwrite');
    $fromcourse       = optional_param('fromcourse');
    $fromsection      = optional_param('fromsection');
    $filename         = optional_param('filename');     // バックアップ後のファイル名
    $newdirectoryname = clean_filename(optional_param('newdirectoryname'));
    
    // 表示文字列
    $strimportsection = get_string('importsection','format_project');

   // 各コースのデータ取得
    if (! ($course = get_record("course", "id", $id)) ) {
        error("That's an invalid course id");
    }

    if (!$site = get_site()){
        error("Couldn't get site course");
    }

    require_login($course->id);
    $tocontext = get_context_instance(CONTEXT_COURSE, $id);
    if ($fromcourse) {
        $fromcontext = get_context_instance(CONTEXT_COURSE, $fromcourse);
    }
    $syscontext = get_context_instance(CONTEXT_SYSTEM, SITEID);

    if (!has_capability('moodle/course:manageactivities', $tocontext)) {
        error("You need do not have the required permissions to import activities to this course");
    }

    // if we're not a course creator , we can only import from our own courses.
    if (has_capability('moodle/course:create', $syscontext)) {
        $creator = true;
    }
    
    // ディレクトリの既存チェック用変数
    $isexistingdirectory = false;
    // ディレクトリ名入力チェック
    $isemptyname = false;
    
    // バックアップ完了時の処理
    if ($fromsection && $from = get_record('course', 'id', $fromcourse)) {
        if (!has_capability('moodle/course:manageactivities', $fromcontext)) {
            error("You need to have the required permissions in the course you are importing data from, as well");
        }
        // ディレクトリ名チェック
        if (!$newdirectoryname) {
            $isemptyname = true;
            $fromsection = null;
        } else {
            if (!empty($filename) && file_exists($CFG->dataroot.'/'.$filename) && !empty($SESSION->import_preferences)) {
                
                //$restore = backup_to_restore_array($SESSION->import_preferences);
                function backup_to_restore_object($backup)
                {
                    if (is_object($backup)) {
                        $r = new stdClass;
                        foreach (get_object_vars($backup) as $k => $v) {
                            $k = str_replace('backup', 'restore', $k);
                            $r->$k = backup_to_restore_object($v);
                        }
                        return $r;
                    }
                    if (is_array($backup)) {
                        $r = array();
                        foreach ($backup as $k => $v) {
                            $k = str_replace('backup', 'restore', $k);
                            $r[$k] = backup_to_restore_object($v);
                        }
                        return $r;
                    }
                    return $backup;
                }
                $restore = backup_to_restore_object($SESSION->import_preferences);
                
                $restore->restoreto = 1;
                $restore->course_id = $id;
                $restore->importing = 1; // magic variable so we know that we're importing rather than just restoring.
                
                $SESSION->restore = $restore;
                redirect($CFG->wwwroot.'/course/format/project/restore.php?file='.$filename.'&id='.$id.'&section='.$tosection.'&newdirectoryname='.$newdirectoryname);
            } else {
                // 既存セクションタイトルのチェック
                if (project_format_check_directoryname($id,$newdirectoryname)) {
                    redirect($CFG->wwwroot.'/course/format/project/backup.php?id='.$fromcourse.'&section='.$fromsection.'&to='.$course->id.'&tosection='.$tosection.'&newdirectoryname='.$newdirectoryname);
                } else {
                    $isexistingdirectory = true;
                }
            }
        }
    }
    
    
    // HTMLヘッダの生成
    $navlinks = array();
    $navlinks[] = array('name' => get_string('import'),
                        'link' => "$CFG->wwwroot/course/import.php?id=$course->id",
                        'type' => 'misc');
    $navlinks[] = array('name' => $strimportsection, 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);

    print_header("$course->shortname: $strimportsection", $course->fullname, $navigation);

    if (!defined('MOODLE_INTERNAL')) {
        die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
    }

    $syscontext = get_context_instance(CONTEXT_SYSTEM, SITEID);

    // if we're not a course creator , we can only import from our own courses.
    if (has_capability('moodle/course:create', $syscontext)) {
        $creator = true;
    }

    $strimport = get_string("importdata");

    $tcourseids = '';

    if ($teachers = get_user_capability_course('moodle/course:update')) {
        foreach ($teachers as $teacher) {
            //if ($teacher->id != $course->id && $teacher->id != SITEID){
            if ($teacher->id != SITEID) {
                $tcourseids .= $teacher->id.',';
            }
        }
    }

    print_heading(get_string("importsection",'format_project'));
    
    // quick forms
    include_once('import_form.php');

    if (!$fromcourse) {
        // 私が担当したコース
        $taught_courses = array();
        if (!empty($tcourseids)) {
            $tcourseids = substr($tcourseids,0,-1);
            $taught_courses = get_records_list('course', 'id', $tcourseids);
        }
        if (!empty($creator)) {
            $cat_courses = get_courses($course->category);
        } else {
            $cat_courses = array();
        }

        $options = array();
        foreach ($taught_courses as $tcourse) {
            //if ($tcourse->id != $course->id && $tcourse->id != SITEID){
            if ($tcourse->format == 'project') {
                $options[$tcourse->id] = format_string($tcourse->fullname);
            }
        }

        if (empty($options) && empty($creator)) {
            notify(get_string('courseimportnotaught'));
            return; // yay , this will pass control back to the file that included or required us.
        }

        $mform_post = new course_import_section_form_course($CFG->wwwroot.'/course/format/project/import.php', array('options'=>$options, 'courseid' => $course->id, 'tosection' => $tosection, 'text'=> get_string('coursestaught')));
        $mform_post ->display();
    
        // 同じカテゴリのコース
        unset($options);
        $options = array();
        
        foreach ($cat_courses as $ccourse) {
            //if ($ccourse->id != $course->id && $ccourse->id != SITEID) {
            if ($tcourse->format == 'project') {
                $options[$ccourse->id] = format_string($ccourse->fullname);
            }
        }
        $cat = get_record("course_categories","id",$course->category);

        if (count($options) > 0) {
            $mform_post = new course_import_section_form_course($CFG->wwwroot.'/course/format/project/import.php', array('options'=>$options, 'courseid' => $course->id, 'tosection' => $tosection, 'text' => get_string('coursescategory')));
            $mform_post ->display();
        }
        
        // ファイルのアップロード
        $mform_post = new course_import_section_form_upload($CFG->wwwroot.'/course/format/project/import_upload.php?id='.$id.'&section='.$tosection, array('maxuploadsize'=>get_max_upload_file_size()));
        $mform_post ->display();
    } else if ($fromcourse && !$fromsection || $isexistingdirectory) {
        // ディレクトリ名が重複しているときの注意
        if ($isexistingdirectory) {
            notify(get_string('directoryalreadyexist', 'format_project', $newdirectoryname));
        }
        // ディレクトリ名が入力されていないときの表示
        if ($isemptyname) {
            notify(get_string('directorynameempty', 'format_project'));
        }
        
        // セクションタイトルの読み込み
        $options = array();
        $sections = get_all_sections($fromcourse);
        foreach ($sections as $section) {
            if ($sectiontitle = get_record('course_project_title', 'sectionid', $section->id)) {
                // セクションの選択
                $options[$section->section] = $sectiontitle->directoryname;
            }
        }
        // 各コースのデータ取得
        if (! ($fromcourseobject = get_record("course", "id", $fromcourse)) ) {
            error("That's an invalid from course id");
        }

        $mform_post = new course_import_section_form_section($CFG->wwwroot.'/course/format/project/import.php', array('options'=>$options, 'courseid' => $course->id, 'tosection' => $tosection, 'fromcourse' => $fromcourse, 'text' => get_string('sectionselect','format_project'), 'fullname' => $fromcourseobject->fullname));
        $mform_post ->display();
    }
    
    if (!empty($table)) {
        print_table($table);
    }

    print_footer();
?>

<?php // preliminary page to find a course to import data from & interface with the backup/restore functionality

// 初期設定
    require_once('../../../config.php');
    require_once($CFG->dirroot.'/course/lib.php');
    require_once($CFG->dirroot.'/backup/restorelib.php');
    require_once('./restorelib.php');

    // フォーム値
    $id               = required_param('id');      // course id to import TO
    $tosection        = required_param('section'); // section number to import TO
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
    if ($fromcourse = optional_param('fromcourse')) {
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
    print_heading(get_string("importsection",'format_project'));
    
    // アップロード処理
    require_once($CFG->dirroot.'/lib/uploadlib.php');
    $um = new upload_manager('userfile',false,false,null,false,0);
    $dir = $to_zip_file = $CFG->dataroot."/".$id.'/backupdata/';
    if ($um->process_file_uploads($dir)) {
        $filename = $id.'/backupdata/'.$um->files['userfile']['name'];
        redirect($CFG->wwwroot.'/course/format/project/restore.php?file='.$filename.'&id='.$id.'&section='.$tosection.'&newdirectoryname='.$newdirectoryname);
    }

    
    if (!empty($table)) {
        print_table($table);
    }

    print_footer();
?>

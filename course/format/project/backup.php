<?php 
    //$Id: backup.php,v 1.00 20080401 12:00:00 Akio Ohnishi Exp $
    //This script is used to configure and execute the backup proccess.

require_once '../../../config.php';
require_once '../../lib.php';
require_once './lib.php';
require_once './backuplib.php';


$course_id = required_param('id');
$section_i = required_param('section');
$to_course_id = optional_param('to');
$to_section_i = optional_param('tosection');

$newdirectoryname = optional_param('newdirectoryname');
$launch = optional_param('launch');
$cancel = optional_param('cancel');

// キャンセル
if ($cancel) {
    redirect($CFG->wwwroot.'/course/view.php?id='.$course_id, get_string('backupcancelled'));
    exit;
}

if (file_exists($shared_lib = $CFG->dirroot.'/blocks/sharing_cart/shared/SharingCart_Backup.php')) {
    require_once $shared_lib;
} else {
    require_once dirname(__FILE__).'/shared/SharingCart_Backup.php';
}

try {
    $worker = new SharingCart_Backup($course_id, $section_i);

    //Get strings
    $str_sectionbackup  = get_string("sectionbackup", "format_project");
    $str_administration = get_string("administration");

    $course = $worker->getCourse();

    // HTMLの出力
    //$navlinks[] = array('name' => $course->fullname, 'link' => "$CFG->wwwroot/course/view.php?id=$course->id", 'type' => 'misc');
    $navlinks[] = array('name' => $str_sectionbackup, 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    print_header("$course->shortname: $str_sectionbackup", $course->fullname, $navigation);

    if (!$launch) {
        // バックアップチェックの実行
        include_once './backup_check.php';
    } elseif ($launch == 'execute') {
        // バックアップの実行
        include_once './backup_execute.php';
    }
    
    print_box_end();

    //Print footer
    print_footer();

} catch (SharingCart_CourseException $e) {
    //print_error('err_course_id', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

} catch (SharingCart_SectionException $e) {
    //print_error('err_section_id', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

} catch (SharingCart_ModuleException $e) {
    //print_error('err_module_id', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

} catch (SharingCart_Exception $e) {
    //print_error('err_backup', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

}

?>

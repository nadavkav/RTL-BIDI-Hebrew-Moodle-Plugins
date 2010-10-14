<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="rtl" lang="he" xml:lang="he">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>

<?php

require_once '../../config.php';

//error_reporting(E_ALL);

require_once './shared/SharingCart_Restore.php';
require_once './sharing_cart_table.php';

$sc_id      = required_param('id', PARAM_INT);
$course_id  = required_param('course', PARAM_INT);
$section_id = required_param('section', PARAM_INT);
$return_to  = urldecode(required_param('return'));

// 共有アイテムが存在するかチェック
$sharing_cart = sharing_cart_table::get_record_by_id($sc_id)
    or print_error('err_shared_id', 'block_sharing_cart', $return_to);

// 自分が所有する共有アイテムかチェック
$sharing_cart->user == $USER->id
    or print_error('err_capability', 'block_sharing_cart', $return_to);

// ZIPファイル名取得
$zip_name = $sharing_cart->file;

try {

    // リストアオブジェクト (※ $restore は Moodle グローバル変数として予約されているので使用不可)
    $worker = new SharingCart_Restore($course_id, $section_id);

    // サイレントモード
    $worker->setSilent();

    // 設定開始
    $worker->beginPreferences();

    // ZIPファイル名設定
    $worker->setZipName($zip_name);

    // 設定完了
    $worker->endPreferences();

    // リストア実行
    $worker->execute();

} catch (SharingCart_CourseException $e) {
    //print_error('err_course_id', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

} catch (SharingCart_SectionException $e) {
    //print_error('err_section_id', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

} catch (SharingCart_ModuleException $e) {
    //print_error('err_module_id', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

} catch (SharingCart_XmlException $e) {
    //print_error('err_invalid_xml', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

} catch (SharingCart_Exception $e) {
    //print_error('err_backup', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

}


if (headers_sent()) {
    print_continue($return_to);
} else {
    redirect($return_to);
}

?>


</body>
</html>
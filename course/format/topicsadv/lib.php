<?php  // $Id: lib.php,v 1.00 2008/4/1 12:00:00 Akio Ohnishi Exp $
/**
* Library of functions for the topicsadv course format.
*/

/**
 * セクション名を取得する
 *
 * @param    object    $course      // コースオブジェクト
 * @param    int    $sectionid       // course_sections のID  :タイトルの取得に必要
 * @param    int $sectionnumber   // sectionの連番  :存在しない場合のデフォルト名称に必要
 * @param   object &$mods         // モジュール配列
 * @return    string    セクションタイトル(存在しない場合は新規作成される)
 */
function topicsadv_format_get_title(&$course, $sectionid, $sectionnumber = 0) {

    global $CFG;

    // 引数のチェック
    if (!$sectionid) {
        return false;
    }

    // タイトルを取得
    if (! $sectiontitle = get_record('course_topicsadv_title', 'sectionid', $sectionid)) {
        // タイトルが取得できなかった(新規のプロジェクトフォーマットアクセス)
        // 新規でタイトルとフォルダ名を作成する
        $directoryname = sprintf('section%02d', $sectionnumber);

        // DBインサート用オブジェクトの生成
        $newtitle                = new stdClass();
        $newtitle->sectionid     = $sectionid;
        $newtitle->directoryname = $directoryname;

        if (!$newtitle->id = insert_record('course_topicsadv_title', $newtitle)) {
            error('Could not create titles. Project format database is not ready. Aceess admin notify.');
        } else {
            // 新規でタイトルを作成しました表示
            notify(get_string('createnewtitle', 'format_topicsadv'));

            // セクション内のモジュールのリンクを書き換える
            topicsadv_format_rename_section_links($course, $sectionid, $directoryname);
        }
        $sectiontitle =& $newtitle;
    }

    return $sectiontitle;
}

/**
 * セクション内のリンクを全て書き換える
 * ※convert.phpを読み込むこと
 *
 *
 * @param &object $course
 * @param int $sectionid
 * @param string $directoryname : 書き換え後のディレクトリ名(全てのリンクを書き換える)
 * @param bool $ismcopy : TRUE:ファイルをコピーする, FALSE:リンクの書き換えのみ
 * @param bool $ismessage : TRUE:処理メッセージを表示する, FALSE:処理メッセージを表示しない
 */
function topicsadv_format_rename_section_links(&$course, $sectionid, $directoryname, $iscopy = true, $ismessage = true) {
    global $CFG;
    $status = '';

    // セクション内の全てのモジュールを読み込む
    get_all_mods($course->id, &$mods, &$modnames, &$modnamesplural, &$modnamesused);

    // セクションの概要を追加
    $sectionobject = new stdClass;
    $sectionobject->modname = 'course_section';
    $sectionobject->section = $sectionid;
    $mods[] = $sectionobject;

    foreach ($mods as $mod) {
        // セクション内の確認
        if ($mod->section == $sectionid) {
            // 各モジュールのリンクを取得
            if (function_exists($mod->modname.'_get_links') && function_exists($mod->modname.'_rename_links')) {
                // コンテンツ内のリンク一覧を取得
                $func = $mod->modname.'_get_links';
                $links = $func($course, $mod);

                // リンクがある場合はリンクの張り替えを行う
                if (is_array($links)) {
                    // ディレクトリの作成
                    if ($iscopy) {
                        if (!check_dir_exists($CFG->dataroot.'/'.$course->id.'/'.$directoryname, true)) {
                            error("Can't create directory");
                        }
                    }

                    foreach ($links as $link) {
                        // 元ファイルのファイル名取得
                        $pathinfo = pathinfo($link);

                        if ($link != $course->id.'/'.$directoryname.'/'.$pathinfo['basename']) {
                            // モジュールファイルをコピーする
                            // 20080323空白が%20のときは変換する
                            $directoryname = preg_replace('|%20|', ' ', $directoryname);
                            $pathinfo['basename'] = preg_replace('|%20|', ' ', $pathinfo['basename']);

                            if ($iscopy && (file_exists($CFG->dataroot.'/'.$link) && !file_exists($CFG->dataroot.'/'.$course->id.'/'.$directoryname.'/'.$pathinfo['basename']))) {
                                copy($CFG->dataroot.'/'.$link, $CFG->dataroot.'/'.$course->id.'/'.$directoryname.'/'.$pathinfo['basename']);
                            }

                            // モジュールリンクを置換する
                            $func = $mod->modname.'_rename_links';
                            $func($course, $mod, $link, $course->id.'/'.$directoryname.'/'.$pathinfo['basename']);
                            if ($ismessage) {
                                $status .= '<li>' . $link.' --&gt; '.$course->id.'/'.$directoryname.'/'.$pathinfo['basename']. '</li>';
                            }
                        }
                    }
                }
            } else {
                // 注意事項の追加
                if ($ismessage) {
                    notify($mod->modfullname.get_string('notrename','format_topicsadv'));
                }
            }
        }
    }

    // 結果表示
    if ($status) {
        echo '<ul>' . $status . '</ul>';
    }

    // コースのキャッシュを再構築
    rebuild_course_cache($course->id, true);
}
/**
 * コース内のディレクトリ名の重複をチェックする
 *
 * @param int $courseid
 * @param string $directoryname
 * @param int $section : 上書きするセクション番号（上書きする場合は重複していてもOK）
 * @return bool - TRUE:重複していない, FALSE:重複している
 */
function topicsadv_format_check_directoryname($courseid,$directoryname,$sectionnumber = 0) {
    // コース内のセクション一覧の取得
    $sections = get_all_sections($courseid);

    // 各ディレクトリ名の検証
    foreach ($sections as $section) {
        if ($sectiontitle = get_record('course_topicsadv_title', 'sectionid', $section->id)) {
            if ($sectiontitle->directoryname == $directoryname && $section->section != $sectionnumber) {
                return false;
            }
        }
    }

    return true;
}

/**
 * ディレクトリが存在しているかチェック(新しくディレクトリは作らない)
 *
 * @param object $course
 * @param string $sectionid
 * @return bool TRUE:存在している, FALSE:存在していない
 */
function topicsadv_is_exist_directory($course,$sectionid)
{
    // ベースディレクトリ名の取得
    if (! $basedir = make_upload_directory("$course->id")) {
        error("The site administrator needs to fix the file permissions");
    }

    // セクションタイトルの取得
    if (! $sectiontitle = get_record('course_topicsadv_title', 'sectionid', $sectionid)) {
        error("Could not find section title");
    }

    // ディレクトリの確認
    return file_exists($basedir . '/' . $sectiontitle->directoryname . '/');
}
?>
<?php

    require_once '../../config.php';
    require_once './sharing_cart_table.php';
    require_once './lib.php';

    //error_reporting(E_ALL);

    require_login();

    $shared_id = optional_param('id', 0, PARAM_INT);
    $return_to = urldecode(required_param('return'));
    $move_to   = urldecode(required_param('to'));

    // 余分な空白は除去
    $move_to = trim($move_to);
    // Unicodeエスケープ文字をUTF-8にデコード
    $move_to = jsurldecode($move_to);
    // パスの階層構造から空の要素を除去 ("/foo//bar/" → "foo/bar")
    $move_to = implode('/', array_filter(explode('/', $move_to), 'strlen'));

    if (empty($shared_id)) {
        // id パラメータが空の場合はフォルダの移動 (ブロック側は未実装)
        $move_from = trim(urldecode(optional_param('from')));
        $move_from = jsurldecode($move_from);
        $move_from = addslashes($move_from);
        $move_to   = addslashes($move_to);
        $sql = "UPDATE {$CFG->prefix}sharing_cart SET tree = '$move_to'
                WHERE user = '$USER->id' AND tree = '$move_from'";
        execute_sql($sql, FALSE)
            or print_error('err_move', 'block_sharing_cart', $return_to);
    } else {
        // 共有アイテムが存在するかチェック
        $shared = sharing_cart_table::get_record_by_id($shared_id)
            or print_error('err_shared_id', 'block_sharing_cart', $return_to);

        // 自分が所有する共有アイテムかチェック
        $shared->user == $USER->id
            or print_error('err_capability', 'block_sharing_cart', $return_to);

        // アイテムをフォルダに移動
        $shared->tree = $move_to;
        sharing_cart_table::update_record($shared)
            or print_error('err_move', 'block_sharing_cart', $return_to);
    }

    // フォルダ表示状態のクッキーをリセット
    @setcookie('sharing_cart_folder', '');

    //if (headers_sent()) die;

    redirect($return_to);

?>
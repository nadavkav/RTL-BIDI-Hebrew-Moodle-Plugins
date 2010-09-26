<?php

    require_once '../../config.php';
    require_once './sharing_cart_table.php';

    //error_reporting(E_ALL);

    require_login();

    $shared_id = required_param('id', PARAM_INT);
    $return_to = urldecode(required_param('return'));
    $insert_to = urldecode(optional_param('to'));

    // 共有アイテムが存在するかチェック
    $shared = sharing_cart_table::get_record_by_id($shared_id)
        or print_error('err_shared_id', 'block_sharing_cart', $return_to);

    // 自分が所有する共有アイテムかチェック
    $shared->user == $USER->id
        or print_error('err_capability', 'block_sharing_cart', $return_to);

    // 挿入先アイテムIDからソート順を取得 (挿入先未指定＝最後尾へ)
    $dest_sort = 0;
    if (!empty($insert_to) and $target = get_record('sharing_cart',
            'id', $insert_to, 'tree', $shared->tree, 'user', $USER->id)) {
        $dest_sort = $target->sort;
    } else {
        $max_sort = get_field_sql(
            "SELECT MAX(sort) FROM {$CFG->prefix}sharing_cart
            WHERE user = '$USER->id' AND tree = '$shared->tree'");
        $dest_sort = $max_sort + 1;
    }

    // 挿入先以降のレコードの`sort`をインクリメントしてスペースを確保
    $sql = "UPDATE {$CFG->prefix}sharing_cart SET sort = sort + 1
            WHERE user = '$USER->id' AND tree = '$shared->tree'
            AND sort >= $dest_sort";
    execute_sql($sql, FALSE);

    // 目的のアイテムを移動
    $shared->sort = $dest_sort;
    sharing_cart_table::update_record($shared)
        or print_error('err_move', 'block_sharing_cart', $return_to);

    //if (headers_sent()) die;

    redirect($return_to);

?>
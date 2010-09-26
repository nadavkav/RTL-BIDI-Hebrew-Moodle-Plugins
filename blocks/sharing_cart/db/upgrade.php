<?php

function xmldb_block_sharing_cart_upgrade($oldversion = 0) {
    global $CFG, $THEME, $db;

    $result = true;

    if ($oldversion < 2009020300) {
        $result = execute_sql("ALTER TABLE `{$CFG->prefix}sharing_cart`
            ADD `file` VARCHAR(255) NOT NULL DEFAULT '' AFTER `time`");
        if ($result) {
            require_once dirname(__FILE__).'../sharing_cart_table.php';
            if ($shared_items = get_records('sharing_cart')) {
                foreach ($shared_items as $shared_item) {
                    $shared_item->file = sharing_cart_table::gen_zipname($shared_item->time);
                    update_record('sharing_cart', $shared_item);
                }
            }
        }
    }

    if ($oldversion < 2009040600) {
        $result = execute_sql("CREATE TABLE `{$CFG->prefix}sharing_cart_plugins` (
            `id`     INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `plugin` VARCHAR(32)      NOT NULL,
            `user`   INT(10) UNSIGNED NOT NULL,
            `data`   TEXT             NOT NULL
        )");
    }

    return $result;
}

?>
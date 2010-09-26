<?php  //$Id: upgrade.php,v 1.1.2.2 2008/04/16 22:44:22 arborrow Exp $

// This file keeps track of upgrades to 
// the birthday block
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_block_birthday_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($result && $oldversion < 2008041601) { //New version in version.php
        // attempt to cleanup any previous data stored in the mdl_config table, data is now stored in mdl_config_plugins
        // first get the data if it exists
        $fieldname = get_config(NULL,'block_birthday_fieldname'); 
        $dateformat = get_config(NULL,'block_birthday_dateformat');
        $visible = get_config(NULL,'block_birthday_visible');
        // then delete the data from mdl_config
        $result = (set_config('block_birthday_fieldname',NULL) && set_config('block_birthday_dateformat',NULL) && set_config('block_birthday_visible',NULL));
        // finally if there is data, then add to mdl_config_plugin
        if (!empty($fieldname)) {
            set_config('block_birthday_fieldname',$fieldname,'block/birthday');
        } 
        if (!empty($dateformat)) {
            set_config('block_birthday_dateformat',$dateformat,'block/birthday');
        }
        if (!empty($visible)) {
            set_config('block_birthday_visible',$visible,'block/birthday');
        }
    }

    return $result;
}

?>

#!/usr/bin/env php
<?php
/**
 * Migrate the old yui-base "course_menu" to the new yui_menu
 *
 * Should be run from the command line on the server. If you need
 * to run it from the web server, temporarily comment out the line below.
 */


if ('cli'!==php_sapi_name()) {
    // remove this bit to run from web server
    die("this must be run from the command-line\n");
}

error_reporting(E_ALL);
header('Content-Type: text/plain');
require_once dirname(dirname(dirname(dirname(__FILE__)))).'/config.php';

/*
* Only migrate from old course_menu if it's been removed and it's the
* right version. We don't want to clober other menus with conflicting
* names.
*/
if (!$cm = get_record('block', 'name', 'course_menu', 'version', '2007071200')) {
    die("Can't find course_menu version 2007071200 to migrate from\n");
}
if (!$ym = get_record('block', 'name', 'yui_menu')) {
    die("yui_menu has not been installed yet\n");
}
if (file_exists($CFG->dirroot.'/blocks/course_menu')) {
    die("Please remove the course_menu code from the blocks directory first\n");
}

$modmap = array('forum'=>'mod_forum', 'quiz'=>'mod_quiz',
    'assignment'=>'mod_assignment', 'blogmenu'=>'mod_journal',
    'classmates'=>'participants');

$menus = get_records('block_instance', 'blockid', $cm->id);
if (!$menus) $menus = array();
$success = true;

foreach ($menus as $menu) {
    // convert configdata to new format
    if ($config = unserialize(base64_decode($menu->configdata))) {            
        $nconfig = new stdClass();
        // idx => order_x for ordering
        $i = 0;
        while (1) {
            $item = 'id'.$i;
            if (!isset($config->$item)) break;
            $nitem = 'order_'.$i;
            $nconfig->$nitem = $config->$item;
            unset($config->$item);
            $i++;
        }
        // everything else should start with item_
        // activity modules prefixed with item_mod_
        foreach ($config as $name=>$val) {
            if (isset($modmap[$name])) {
                $name = $modmap[$name];
            }
            $name = 'item_'.$name;
            $nconfig->$name = $val;
        }
        $menu->configdata = base64_encode(serialize($nconfig));
    }
    // migrate to yui_menu
    $menu->blockid = $ym->id;
    // apply changes, nothing should contain special sql chars
    if (!update_record('block_instance', $menu)) {
        trigger_error("Error updating block_instance {$menu->id}");
    } else {
        echo "Migrated block {$menu->id}\n";
    }
}

if (get_records('block_instance', 'blockid', $cm->id)) {
    print "course_menu blocks still exists, not removing old menu\n";
} else {
    delete_records('block', 'id', $cm->id);
    print "removed course_menu block from database \n";
}

?>
<?php

/**
 * Given a list of module names, prints out a warning message. The last message to be printed before performing the action
 */
function ma_delete_confirm($modnames) {
    $string = "";
    $string .= "<table width=100%>";
    $string .= "<tr><td>".get_string('delete_confirm', 'block_massaction')."</td></tr>";
    foreach ($modnames as $modname) {
        $string .= "<tr><td><b>$modname</b></td></tr>";
    }
    $string .= "</table>";
    return $string;
}
	
/**
 * Given a list of module names, prints out a warning message. The first message to be printed before performing the action
 */	
function ma_delete_continue($modnames) {
    $string = "";
    $string .= "<table width=100%>";
    $string .= "<tr><td>".get_string('delete_continue', 'block_massaction')."</td></tr>";
    foreach ($modnames as $modname) {
        $string .= "<tr><td><b>$modname</b></td></tr>";
    }
    $string .= "</table>";
    return $string;	
}

function ma_delete_execute($modids) {

    global $CFG;

    $in_list = "(".join(", ", $modids).")";

    $sql = "SELECT cm.id as coursemodule, cm.section, cm.course, mo.name as modulename, instance
        FROM {$CFG->prefix}course_modules cm
        LEFT JOIN {$CFG->prefix}modules mo on mo.id=cm.module
        WHERE cm.id IN $in_list";
    $records = get_records_sql($sql);
    $courses = array();

    $message = '';

    if ($records) {
        foreach ($records as $id => $record) {
            $modlib = "$CFG->dirroot/mod/$record->modulename/lib.php";
            $courses[] = $record->course;
            if (file_exists($modlib)) {
                include_once($modlib);
            } else {
                $message .= "This module is missing important code! ($modlib)&nbsp;&nbsp;";
                continue;
            }
            $deleteinstancefunction = $record->modulename."_delete_instance";
            if (! $deleteinstancefunction($record->instance)) {
                $message .= "Could not delete the $record->modulename (instance)&nbsp;&nbsp;";
                continue;
            }
            if (! delete_course_module($record->coursemodule)) {
                $message .= "Could not delete the $record->modulename (coursemodule)&nbsp;&nbsp;";
                continue;
            }
            if (! delete_mod_from_section($record->coursemodule, "$record->section")) {
                $message .= "Could not delete the $record->modulename from that section&nbsp;&nbsp;";
                continue;
            } 
        }
    }
    return $message;
}

?>

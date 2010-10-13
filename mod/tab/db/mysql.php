<?php // $Id: mysql.php,v 1.8 2006/10/26 22:39:13 stronk7 Exp $

// THIS FILE IS DEPRECATED!  PLEASE DO NOT MAKE CHANGES TO IT!
//
// IT IS USED ONLY FOR UPGRADES FROM BEFORE MOODLE 1.7, ALL 
// LATER CHANGES SHOULD USE upgrade.php IN THIS DIRECTORY.

function tab_upgrade($oldversion) {

/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

    global $CFG;

    if ($oldversion < 2008071159) {
        table_column("tab", "", "course", "integer", "10", "unsigned", "0", "not null", "id");
    }

    if ($oldversion < 2008071159) {
        modify_database("", "INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('tab', 'add', 'quiz', 'name');");
        modify_database("", "INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('tab', 'update', 'quiz', 'name');");
    }

    if ($oldversion < 2008071159) { //DROP first
        execute_sql("ALTER TABLE {$CFG->prefix}tab DROP INDEX course;",false);
        modify_database('','ALTER TABLE prefix_tab ADD INDEX course (course);');
    }

    //////  DO NOT ADD NEW THINGS HERE!!  USE upgrade.php and the lib/ddllib.php functions.

    return true;

}

?>

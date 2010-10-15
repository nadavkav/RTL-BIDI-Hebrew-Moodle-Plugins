<?php  // $Id: postgres7.php,v 1.1 2008/07/24 01:48:12 arborrow Exp $

// THIS FILE IS DEPRECATED!  PLEASE DO NOT MAKE CHANGES TO IT!
//
// IT IS USED ONLY FOR UPGRADES FROM BEFORE MOODLE 1.7, ALL 
// LATER CHANGES SHOULD USE upgrade.php IN THIS DIRECTORY.

// PostgreSQL commands for upgrading this question type

function qtype_algebra_upgrade($oldversion=0) {
    global $CFG;

    //////  DO NOT ADD NEW THINGS HERE!!  USE upgrade.php and the lib/ddllib.php functions.

    return true;
}

?>

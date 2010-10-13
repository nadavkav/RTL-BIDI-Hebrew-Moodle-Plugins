<?php // $Id: postgres7.php,v 3.0 2008/08/13 00:00:00 gibson Exp $

function nanogong_upgrade($oldversion) {

// This function does anything necessary to upgrade
// older versions to match current functionality

    global $CFG;

    if ($oldversion < 2008081100) {
        execute_sql(" ALTER TABLE `{$CFG->prefix}nanogong` ADD `maxmessages` integer NOT NULL default '0' AFTER `message` ");
        execute_sql(" ALTER TABLE `{$CFG->prefix}nanogong` ADD `color` varchar(7) AFTER `message` ");
        execute_sql(" ALTER TABLE `{$CFG->prefix}nanogong_message` ADD `title` varchar(255) NOT NULL default '' AFTER `groupid` ");
        execute_sql(" ALTER TABLE `{$CFG->prefix}nanogong_message` ADD `commentedby` integer AFTER `comments` ");
        execute_sql(" ALTER TABLE `{$CFG->prefix}nanogong_message` ADD `timeedited` integer AFTER `timestamp` ");
    }

    return true;
}

?>

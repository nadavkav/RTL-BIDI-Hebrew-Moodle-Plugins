<?php // $Id: mysql.php,v 1.1.2.2 2009/03/18 16:45:47 mchurch Exp $

function elluminate_upgrade($oldversion) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

    global $CFG;

    $result = true;

    if ($oldversion < 2006062100) {
        $result = modify_database('', "
            CREATE TABLE `prefix_elluminate` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `course` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                `creator` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                `name` VARCHAR(64) NOT NULL DEFAULT '',
                `description` TEXT NOT NULL DEFAULT '',
                `meetingid` VARCHAR(20) NOT NULL DEFAULT 0,
                `private` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                `grade` INT(10) NOT NULL DEFAULT '0',
                `timemodified` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                PRIMARY KEY  (`id`),
                KEY `meetingid` (`meetingid`)
            )
        ");

        if ($result) {
            $result = modify_database('', "
                CREATE TABLE `prefix_elluminate_recordings` (
                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `meetingid` VARCHAR(20) NOT NULL DEFAULT '',
                    `recordingid` VARCHAR(30) NOT NULL DEFAULT '',
                    `created` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                    PRIMARY KEY `id` (`id`),
                    KEY `meetingid` (`meetingid`),
                    KEY `recordingid` (`recordingid`)
                )
            ");
        }

        if ($result) {
            $result = modify_database('', "
                CREATE TABLE `prefix_elluminate_users` (
                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `userid` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                    `elm_id` VARCHAR(20) NOT NULL DEFAULT '',
                    `elm_password` VARCHAR(10) NOT NULL DEFAULT '',
                    `timecreated` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                    PRIMARY KEY `id` (`id`),
                    KEY `userid` (`userid`),
                    KEY `elm_id` (`elm_id`)
                )
            ");
        }

        if ($result) {
            $result = modify_database('', "
                CREATE TABLE `prefix_elluminate_attendance` (
                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `userid` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                    `elluminateid` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                    `grade` INT(11) NOT NULL DEFAULT '0',
                    `timemodified` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                    PRIMARY KEY `id` (`id`),
                    KEY `userid_elluminateid` (`userid`, `elluminateid`)
                )
            ");
        }
/*
        if ($result) {
            $result = modify_database('', "
                CREATE TABLE `prefix_event_reminder` (
                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `event` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                    `type` INT(1) UNSIGNED NOT NULL DEFAULT '0',
                    `timedelta` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                    `timeinterval` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                    `timeend` INT(10) UNSIGNED NOT NULL DEFAULT '0',
                    PRIMARY KEY(`id`)
                );
            ");
        }
*/
    }

    if ($oldversion < 2006062101) {
        $result = table_column('elluminate_users', '', 'elm_username', 'VARCHAR', '50', '', '',  'NOT NULL', 'elm_id');
    }

    if ($oldversion < 2006062102) {
        $result = table_column('elluminate', '', 'seats', 'INTEGER', '10', 'UNSIGNED', '0',  'NOT NULL', 'meetingid');
    }

    return $result;
}

?>

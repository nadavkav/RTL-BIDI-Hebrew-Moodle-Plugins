<?php // $Id: postgres7.php,v 1.1.2.2 2009/03/18 16:45:48 mchurch Exp $

function elluminate_upgrade($oldversion) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

    global $CFG;

    $result = true;

    if ($oldversion < 2006062100) {
        $result = modify_database('', "
            CREATE TABLE `prefix_elluminate` (
                `id` SERIAL PRIMARY KEY,
                `course` INTEGER NOT NULL DEFAULT '0',
                `creator` INTEGER NOT NULL DEFAULT '0',
                `name` VARCHAR(64) NOT NULL DEFAULT '',
                `description` TEXT NOT NULL DEFAULT '',
                `meetingid` VARCHAR(20) NOT NULL DEFAULT 0,
                `private` TINYINT(1) NOT NULL DEFAULT 0,
                `grade` INTEGER NOT NULL DEFAULT '0',
                `timemodified` INTEGER NOT NULL DEFAULT '0',
            )
        ");

        if ($result) {
            $result = modify_database('', 'CREATE INDEX prefix_elluminate_idx ON prefix_elluminate(`id`)');
        }

        if ($result) {
            $result = modify_database('', 'CREATE INDEX prefix_elluminate_meetingid_idx ON prefix_elluminate(`meetingid`)');
        }

        if ($result) {
            $result = modify_database('', "
                CREATE TABLE `prefix_elluminate_recordings` (
                    `id` SERIAL PRIMARY KEY,
                    `meetingid` VARCHAR(20) NOT NULL DEFAULT '',
                    `recordingid` VARCHAR(30) NOT NULL DEFAULT '',
                    `created` INTEGER NOT NULL DEFAULT '0'
                )
            ");
        }

        if ($result) {
            $result = modify_database('', 'CREATE INDEX prefix_elluminate_recordings_idx ON prefix_elluminate_recordings(`id`)');
        }

        if ($result) {
            $result = modify_database('', 'CREATE INDEX prefix_elluminate_recordings_meeting_recording_idx ON prefix_elluminate_recordings(`meetingid`, `recordingid`)');
        }

        if ($result) {
            $result = modify_database('', "
                CREATE TABLE `prefix_elluminate_users` (
                    `id` SERIAL PRIMARY KEY,
                    `userid` INTEGER NOT NULL DEFAULT '0',
                    `elm_id` VARCHAR(20) NOT NULL DEFAULT '',
                    `elm_password` VARCHAR(10) NOT NULL DEFAULT '',
                    `timecreated` INTEGER NOT NULL DEFAULT '0'
                )
            ");
        }

        if ($result) {
            $result = modify_database('', 'CREATE INDEX prefix_elluminate_users_idx ON prefix_elluminate_users(`id`)');
        }

        if ($result) {
            $result = modify_database('', 'CREATE INDEX prefix_elluminate_users_user_idx ON prefix_elluminate_users(`userid`, `elm_id`)');
        }

        if ($result) {
            $result = modify_database('', "
                CREATE TABLE `prefix_elluminate_attendance` (
                    `id` SERIAL PRIMARY KEY,
                    `userid` INTEGER NOT NULL DEFAULT '0',
                    `elluminateid` INTEGER NOT NULL DEFAULT '0',
                    `grade` INTEGER NOT NULL DEFAULT '0',
                    `timemodified` INTEGER NOT NULL DEFAULT '0'
                )
            ");
        }

        if ($result) {
            $result = modify_database('', 'CREATE INDEX prefix_elluminate_attendance_idx ON prefix_elluminate_attendance(`id`)');
        }

        if ($result) {
            $result = modify_database('', 'CREATE INDEX prefix_elluminate_attendance_meeting_idx ON prefix_elluminate_attendance(`elluminateid`)');
        }
/*
        if ($result) {
            $result = modify_database('', "
                CREATE TABLE `prefix_event_reminder` (
                    `id` SERIAL PRIMARY KEY,
                    `event` INTEGER NOT NULL DEFAULT '0',
                    `type` INTEGER NOT NULL DEFAULT '0',
                    `timedelta` INTEGER NOT NULL DEFAULT '0',
                    `timeinterval` INTEGER NOT NULL DEFAULT '0',
                    `timeend` INTEGER NOT NULL DEFAULT '0'
                );
            ");
        }

        if ($result) {
            $result = modify_database('', 'CREATE INDEX prefix_event_reminder_idx ON prefix_event_reminder(`id`)');
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

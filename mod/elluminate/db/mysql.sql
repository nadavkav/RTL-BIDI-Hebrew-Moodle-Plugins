
-- 
-- Table structure for table `prefix_elluminate`
-- 

CREATE TABLE `prefix_elluminate` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `course` INT(10) UNSIGNED NOT NULL DEFAULT '0',
    `creator` INT(10) UNSIGNED NOT NULL DEFAULT '0',
    `name` VARCHAR(64) NOT NULL DEFAULT '',
    `description` TEXT NOT NULL DEFAULT '',
    `meetingid` VARCHAR(20) NOT NULL DEFAULT '0',
    `seats` INTEGER(10) NOT NULL DEFAULT '0',
    `private` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `grade` INT(10) NOT NULL DEFAULT '0',
    `timemodified` INT(10) UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY  (`id`),
    KEY `meetingid` (`meetingid`)
) COMMENT='Holds meeting data for each course module.';


--
-- Table structure for table `prefix_elluminate_recordings`
--

CREATE TABLE `prefix_elluminate_recordings` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `meetingid` VARCHAR(20) NOT NULL DEFAULT '',
    `recordingid` VARCHAR(30) NOT NULL DEFAULT '',
    `created` INT(10) UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY `id` (`id`),
    KEY `meetingid` (`meetingid`),
    KEY `recordingid` (`recordingid`)
) COMMENT='Holds info about recorded meetings.';


--
-- Table structure for table `prefix_elluminate_users`
--

CREATE TABLE `prefix_elluminate_users` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `userid` INT(10) UNSIGNED NOT NULL DEFAULT '0',
    `elm_id` VARCHAR(20) NOT NULL DEFAULT '',
    `elm_username` VARCHAR(50) NOT NULL DEFAULT '',
    `elm_password` VARCHAR(10) NOT NULL DEFAULT '',
    `timecreated` INT(10) UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY `id` (`id`),
    KEY `userid` (`userid`),
    KEY `elm_id` (`elm_id`),
    KEY `elm_username` (`elm_username`)
) COMMENT='Holds mapping between ELM users and Moodle users.';


--
-- Table structure for table `prefix_elluminate_attendance`
--

CREATE TABLE `prefix_elluminate_attendance` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `userid` INT(10) UNSIGNED NOT NULL DEFAULT '0',
    `elluminateid` INT(10) UNSIGNED NOT NULL DEFAULT '0',
    `grade` INT(11) NOT NULL DEFAULT '0',
    `timemodified` INT(10) UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY `id` (`id`),
    KEY `userid_elluminateid` (`userid`, `elluminateid`)
) COMMENT='Holds attendance data for meetings keeping track of attendance.';

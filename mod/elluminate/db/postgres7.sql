
CREATE TABLE `prefix_elluminate` (
    `id` SERIAL PRIMARY KEY,
    `course` INTEGER NOT NULL DEFAULT '0',
    `creator` INTEGER NOT NULL DEFAULT '0',
    `name` VARCHAR(64) NOT NULL DEFAULT '',
    `description` TEXT NOT NULL DEFAULT '',
    `meetingid` VARCHAR(20) NOT NULL DEFAULT '0',
    `seats` INTEGER NOT NULL DEFAULT '0',
    `private` TINYINT(1) NOT NULL DEFAULT '0',
    `grade` INTEGER NOT NULL DEFAULT '0',
    `timemodified` INTEGER NOT NULL DEFAULT '0',
);

CREATE INDEX prefix_elluminate_idx ON prefix_elluminate(`id`);
CREATE INDEX prefix_elluminate_meetingid_idx ON prefix_elluminate(`meetingid`);


CREATE TABLE `prefix_elluminate_recordings` (
    `id` SERIAL PRIMARY KEY,
    `meetingid` VARCHAR(20) NOT NULL DEFAULT '',
    `recordingid` VARCHAR(30) NOT NULL DEFAULT '',
    `created` INTEGER NOT NULL DEFAULT '0'
);

CREATE INDEX prefix_elluminate_recordings_idx ON prefix_elluminate_recordings(`id`);
CREATE INDEX prefix_elluminate_recordings_meeting_recording_idx ON prefix_elluminate_recordings(`meetingid`, `recordingid`);


CREATE TABLE `prefix_elluminate_users` (
    `id` SERIAL PRIMARY KEY,
    `userid` INTEGER NOT NULL DEFAULT '0',
    `elm_id` VARCHAR(20) NOT NULL DEFAULT '',
    `elm_username` VARCHAR(50) NOT NULL DEFAULT '',
    `elm_password` VARCHAR(10) NOT NULL DEFAULT '',
    `timecreated` INTEGER NOT NULL DEFAULT '0'
);

CREATE INDEX prefix_elluminate_users_idx ON prefix_elluminate_users(`id`);
CREATE INDEX prefix_elluminate_users_user_idx ON prefix_elluminate_users(`userid`, `elm_username`, `elm_id`);


CREATE TABLE `prefix_elluminate_attendance` (
    `id` SERIAL PRIMARY KEY,
    `userid` INTEGER NOT NULL DEFAULT '0',
    `elluminateid` INTEGER NOT NULL DEFAULT '0',
    `grade` INTEGER NOT NULL DEFAULT '0',
    `timemodified` INTEGER NOT NULL DEFAULT '0'
);

CREATE INDEX prefix_elluminate_attendance_idx ON prefix_elluminate_attendance(`id`);
CREATE INDEX prefix_elluminate_attendance_meeting_idx ON prefix_elluminate_attendance(`elluminateid`);

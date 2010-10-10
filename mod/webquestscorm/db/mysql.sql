#
# Table structure for table `webquestscorm`
#

CREATE TABLE `prefix_webquestscorm` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `course` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `grade` int(10) NOT NULL default '0',
  `timeavailable` int(10) unsigned NOT NULL default '0',
	`timedue` int(10) unsigned NOT NULL default '0',
	`dueenable` int(10) unsigned NOT NULL default '0',
	`dueyear` int(10) unsigned NOT NULL default '0',
	`duemonth` int(10) unsigned NOT NULL default '0',
	`dueday` int(10) unsigned NOT NULL default '0',
	`duehour` int(10) unsigned NOT NULL default '0',
	`dueminute` int(10) unsigned NOT NULL default '0',
	`availableenable` int(10) unsigned NOT NULL default '0',
	`availableyear` int(10) unsigned NOT NULL default '0',
	`availablemonth` int(10) unsigned NOT NULL default '0',
	`availableday` int(10) unsigned NOT NULL default '0',
	`availablehour` int(10) unsigned NOT NULL default '0',
	`availableminute` int(10) unsigned NOT NULL default '0',	
	`preventlate` tinyint(2) unsigned NOT NULL default '0',  
	`maxbytes` int(10) unsigned NOT NULL default '100000',
	`resubmit` tinyint(2) unsigned NOT NULL default '0',
	`emailteachers` tinyint(2) unsigned NOT NULL default '0',
  `template` varchar(20) default '',
  `introduction` text NOT NULL default '',
  `task` text NOT NULL default '',	  
  `process` text NOT NULL default '',
  `evaluation` text NOT NULL default '',
  `conclusion` text NOT NULL default '',
  `credits` text NOT NULL default '',
  `timemodified` int(10) unsigned NOT NULL default '0',  
  PRIMARY KEY  (`id`),
  KEY `course` (`course`)
) COMMENT='Defines webquestscorm';
# --------------------------------------------------------

#
# Table structure for table `webquestscorm_submissions`
#


CREATE TABLE `prefix_webquestscorm_submissions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `webquestscorm` int(10) unsigned NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `timecreated` int(10) unsigned NOT NULL default '0',
  `timemodified` int(10) unsigned NOT NULL default '0',
  `numfiles` int(10) unsigned NOT NULL default '0',
  `data1` text NOT NULL default '',
  `data2` text NOT NULL default '',
  `grade` int(11) NOT NULL default '0',
  `submissioncomment` text NOT NULL default '',
  `format` tinyint(4) unsigned NOT NULL default '0',
  `teacher` int(10) unsigned NOT NULL default '0',
  `timemarked` int(10) unsigned NOT NULL default '0',
  `mailed` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `webquestscorm` (`webquestscorm`),
  KEY `userid` (`userid`),
  KEY `mailed` (`mailed`),
  KEY `timemarked` (`timemarked`)
) COMMENT='Info about submitted assignments';

INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('webquestscorm', 'view', 'webquestscorm', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('webquestscorm', 'add', 'webquestscorm', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('webquestscorm', 'update', 'webquestscorm', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('webquestscorm', 'view webquestscorm', 'webquestscorm', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('webquestscorm', 'upload', 'webquestscorm', 'name');

ALTER TABLE `mdl_webquestscorm_submissions`
  ADD CONSTRAINT `webquestscorm` FOREIGN KEY (`webquestscorm`) REFERENCES `mdl_webquestscorm` (`id`) ON DELETE CASCADE;



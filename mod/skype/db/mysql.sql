CREATE TABLE `prefix_skype` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `course` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default 'skype',
  `participants` varchar(10) NOT NULL default '0',
  `timemodified` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY course (course)
) COMMENT='Defines skypes';

INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('skype', 'add', 'skype', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('skype', 'update', 'skype', 'name');
ALTER TABLE prefix_skype ADD `description` TEXT NOT NULL ;

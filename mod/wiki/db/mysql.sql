# This file contains a complete database schema for all the
# tables used by this module, written in SQL

# It may also contain INSERT statements for particular data
# that may be used, especially new entries in the table log_display

CREATE TABLE `prefix_wiki` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `course` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `intro` text NOT NULL default '',
  `introformat` tinyint(2) unsigned NOT NULL default '0',
  `pagename` varchar(255) default NULL,
  `timemodified` int(10) NOT NULL default '0',
  `editable` tinyint(1) NOT NULL default '1',
  `attach` tinyint(1) NOT NULL default '0',
  `upload` tinyint(1) NOT NULL default '0',
  `restore` tinyint(1) NOT NULL default '0',
  `editor` varchar(40) NOT NULL default 'dfwiki',
  `groupmode` tinyint(1) NOT NULL default '0',
  `studentmode` tinyint(1) NOT NULL default '0',
  `teacherdiscussion` int(1) NOT NULL default '0',
  `studentdiscussion` int(1) NOT NULL default '0',
  `evaluation` varchar(40) default 'noeval',
  `notetype` varchar(40) default 'quant',
  `editanothergroup` tinyint(1) NOT NULL default '0',
  `editanotherstudent` tinyint(1) NOT NULL default '0',
  `votemode` tinyint(1) NOT NULL default '0',
  `listofteachers` tinyint(1) NOT NULL default '0',
  `editorrows` integer NOT NULL default '40',
  `editorcols` integer NOT NULL default '60',
  `wikicourse` int(10) unsigned NOT NULL default '0',
  `filetemplate` varchar(60) NULL default NULL,
  PRIMARY KEY  (`id`),
  KEY `course` (`course`)
) TYPE=MyISAM COMMENT='wiki table';

CREATE TABLE `prefix_wiki_pages` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pagename` VARCHAR(160) NOT NULL,
  `version` INTEGER UNSIGNED NOT NULL DEFAULT 0,
  `content` MEDIUMTEXT,
  `author` VARCHAR(100) DEFAULT 'wiki',
  `userid` int(10) unsigned NOT NULL default '0',
  `created` INTEGER UNSIGNED DEFAULT 0,
  `lastmodified` INTEGER UNSIGNED DEFAULT 0,
  `refs` MEDIUMTEXT,
  `hits` INTEGER UNSIGNED DEFAULT 0,
  `editable` tinyint(1) NOT NULL default '1',
  `highlight` tinyint(1) not null default 0,
  `dfwiki` int(10) unsigned NOT NULL,
  `editor` varchar(40) NOT NULL default 'dfwiki',
  `groupid` int(10) NOT NULL default '0',
  `ownerid` int(10) unsigned NOT NULL default '0',
  `evaluation` MEDIUMTEXT default NULL,
  PRIMARY KEY `id` (`id`),
  UNIQUE KEY `wiki_pages_uk` (`pagename`, `version`, `dfwiki`, `groupid`, `userid`, `ownerid`)
) TYPE=MyISAM COMMENT='holds the wiki pages';

CREATE TABLE `prefix_wiki_synonymous` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `syn` VARCHAR(160) NOT NULL,
  `original` VARCHAR(160) NOT NULL,
  `dfwiki` int(10) unsigned NOT NULL,
  `groupid` int(10) NOT NULL default '0',
  `ownerid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY `id` (`id`),
  UNIQUE KEY `wiki_synonymous_uk` (`syn`,`dfwiki`,`groupid`, `ownerid`)
) TYPE=MyISAM COMMENT='holds the synonymous of wiki pages';

CREATE TABLE `prefix_wiki_votes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pagename` varchar(160) NOT NULL,
  `version` int(10) unsigned NOT NULL default '0',
  `dfwiki` int(10) unsigned NOT NULL,
  `username` varchar(100) NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM COMMENT='holds the votes of wiki pages';

CREATE TABLE `prefix_wiki_locks` (
  `id` BIGINT(10) unsigned NOT NULL auto_increment,
  `wikiid` BIGINT(10) unsigned NOT NULL,
  `pagename` VARCHAR(160) NOT NULL DEFAULT '',
  `lockedby` BIGINT(10) unsigned NOT NULL DEFAULT 0,
  `lockedsince` BIGINT(10) unsigned NOT NULL DEFAULT 0,
  `lockedseen` BIGINT(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wiki_locks_uk` (`wikiid`,`pagename`)
) TYPE=MyISAM COMMENT='Stores editing locks on Wiki pages';


INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'add', 'wiki', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'update', 'wiki', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'view', 'wiki', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'view all', 'wiki', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'view page', 'wiki_pages', 'pagename');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'edt page', 'wiki_pages', 'pagename');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'save page', 'wiki_pages', 'pagename');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'info page', 'wiki_pages', 'pagename');

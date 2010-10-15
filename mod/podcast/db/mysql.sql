#
# Podcast
#

CREATE TABLE prefix_podcast (
  `id` int(10) unsigned NOT NULL auto_increment,
  `course` int(10) unsigned NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `author` varchar(255) NULL default '',  
  `intro` text NULL,
  `owner` varchar(255) NULL default '',
  `owner_email` varchar(255) NULL default '',
  `copyright` varchar(255) NULL default '',
  `lang` varchar(2) NULL default '',
  `pubdate` varchar(255) NULL default '',
  `image_url` varchar(255) NULL default '',
  `image_img` varchar(255) NULL default '',
  `category` varchar(255) NULL default '',
  `timemodified` int(10) unsigned NOT NULL default '0',
   PRIMARY KEY  (`id`)
) COMMENT='Podcast activity';
# --------------------------------------------------------


#
# Structure Podcast
#

CREATE TABLE prefix_podcast_structure (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_podcast` int(10) NOT NULL,
  `title` varchar(255) NULL default '',
  `lien` varchar(255) NULL default '',
  `intro` text NULL,
  `pubdate` varchar(255) NULL default '',
  `date_html` varchar(255) NULL default '',
  `duration` varchar(10) NULL default '',
  `length` int(10) NULL,
   PRIMARY KEY  (`id`)
) COMMENT='Podcast items';
# --------------------------------------------------------


#
# Data for the table `log_display`
#
INSERT INTO prefix_log_display (module,action,mtable,field) VALUES ('podcast', 'view', 'podcast', 'name');
INSERT INTO prefix_log_display (module,action,mtable,field) VALUES ('podcast', 'add', 'podcast', 'name');
INSERT INTO prefix_log_display (module,action,mtable,field) VALUES ('podcast', 'update', 'podcast', 'name');
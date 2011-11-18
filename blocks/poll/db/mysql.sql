CREATE TABLE prefix_block_poll (
 `id` int(11) NOT NULL auto_increment,
 `name` varchar(64) NOT NULL default '',
 `courseid` int (11) NOT NULL default 0,
 `questiontext` text NOT NULL default '',
 `eligible` enum('all', 'students', 'teachers') NOT NULL default 'all',
 `created` bigint(10) NOT NULL default 0,
  PRIMARY KEY (`id`)
) TYPE=MyISAM COMMENT='Contains polls for the poll block.';

CREATE TABLE prefix_block_poll_option (
 `id` int(11) NOT NULL auto_increment,
 `pollid` int(11) NOT NULL default 0,
 `optiontext` text NOT NULL default '',
  PRIMARY KEY (`id`)
) TYPE=MyISAM COMMENT='Contains options for each poll in the poll block.';

CREATE TABLE prefix_block_poll_response (
 `id` int(11) NOT NULL auto_increment,
 `pollid` int(11) NOT NULL default 0,
 `optionid` int(11) NOT NULL default 0,
 `userid` int(11) NOT NULL default 0,
 `submitted` bigint(10) NOT NULL default 0,
  PRIMARY KEY (`id`)
) TYPE=MyISAM COMMENT='Contains response info for each poll in the poll block.';

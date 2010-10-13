#
# Table structure for NanoGong activity
#

CREATE TABLE prefix_nanogong (
  id int(10) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  course int(10) unsigned NOT NULL default '0',
  message text NOT NULL default '',
  color varchar(7),
  maxmessages int(4) NOT NULL default '0',
  maxscore int(4) NOT NULL default '100',
  allowguestaccess int(1) NOT NULL default '0',
  timecreated int(10) unsigned NOT NULL default '0',
  timemodified int(10) unsigned NOT NULL default '0',
  PRIMARY KEY (id),
  UNIQUE KEY id (id)
) TYPE=MyISAM;

#
# Table structure for NanoGong message
#

CREATE TABLE prefix_nanogong_message (
  id int(10) unsigned NOT NULL auto_increment,
  nanogongid int(10) unsigned NOT NULL default '0',
  userid int(10) unsigned NOT NULL default '0',
  groupid int(10) unsigned NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  message text NOT NULL default '',
  path text NOT NULL default '',
  comments text NOT NULL default '',
  commentedby int(10) unsigned,
  score int(4) NOT NULL default '0',
  timestamp int(10) unsigned NOT NULL default '0',
  timeedited int(10) unsigned,
  locked int(1) NOT NULL default '0',
  PRIMARY KEY (id),
  UNIQUE KEY id (id),
  KEY nanogongid (nanogongid),
  KEY userid (userid),
  KEY groupid (groupid)
) TYPE=MyISAM;

#
# Table structure for NanoGong activity
#

CREATE TABLE prefix_nanogong (
  id SERIAL PRIMARY KEY,
  name varchar(255) NOT NULL default '',
  course integer NOT NULL default '0',
  message text NOT NULL default '',
  color varchar(7),
  maxmessages integer NOT NULL default '0',
  maxscore integer NOT NULL default '100',
  allowguestaccess smallint NOT NULL default '0',
  timecreated integer NOT NULL default '0',
  timemodified integer NOT NULL default '0'
);

CREATE INDEX prefix_nanogong_idx ON prefix_nanogong(course);

#
# Table structure for NanoGong message
#

CREATE TABLE prefix_nanogong_message (
  id SERIAL PRIMARY KEY,
  nanogongid integer NOT NULL default '0',
  userid integer NOT NULL default '0',
  groupid integer NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  message text NOT NULL default '',
  path text NOT NULL default '',
  comments text NOT NULL default '',
  commentedby integer,
  score integer NOT NULL default '0',
  timestamp integer NOT NULL default '0',
  timeedited integer,
  locked smallint NOT NULL default '0'
);

CREATE INDEX prefix_nanogong_message_nanogongid_idx ON prefix_nanogong_message (nanogongid);
CREATE INDEX prefix_nanogong_message_userid_idx ON prefix_nanogong_message (userid);
CREATE INDEX prefix_nanogong_message_groupid_idx ON prefix_nanogong_message (groupid);


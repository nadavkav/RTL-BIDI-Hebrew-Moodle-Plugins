CREATE TABLE prefix_netpublish (
  id int(10) unsigned NOT NULL auto_increment,
  course int(10) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default 'No Name',
  intro blob,
  timecreated int(10) unsigned NOT NULL default '0',
  timemodified int(10) unsigned NOT NULL default '0',
  maxsize tinyint(1) unsigned NOT NULL default '3',
  locktime int(10) unsigned NOT NULL default '0',
  published tinyint(1) unsigned NOT NULL default '0',
  fullpage tinyint(1) unsigned NOT NULL default '0',
  statuscount tinyint (3) unsigned NOT NULL default '0',
  scale int(10) NOT NULL default '0',
  titleimage varchar(255) NOT NULL default '',
  theme varchar(255) NOT NULL default '',
  title varchar(255) NOT NULL default '',
  frontpagecolumns tinyint (1) unsigned NOT NULL default '2',
  PRIMARY KEY  (id),
  KEY course (course),
  KEY published (published),
  KEY scale (scale)
);

CREATE TABLE prefix_netpublish_articles (
  id int(10) unsigned NOT NULL auto_increment,
  publishid int(10) unsigned NOT NULL default '0',
  sectionid int(10) unsigned NOT NULL default '0',
  userid int(10) NOT NULL default '0',
  teacherid int(10) unsigned NOT NULL default '0',
  prevarticle int(10) unsigned NOT NULL default '0',
  nextarticle int(10) unsigned NOT NULL default '0',
  authors varchar(64) default NULL,
  title varchar(255) NOT NULL default '0',
  intro blob,
  content mediumblob,
  timepublished int(10) unsigned default NULL,
  timecreated int(10) unsigned NOT NULL default '0',
  timemodified int(10) unsigned NOT NULL default '0',
  statusid tinyint(3) unsigned NOT NULL default '0',
  rights blob,
  sortorder int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY publishid (publishid,statusid)
);

CREATE TABLE prefix_netpublish_sections (
  id int(10) unsigned NOT NULL auto_increment,
  publishid int(10) unsigned NOT NULL default '0',
  parentid int(10) unsigned NOT NULL default '0',
  fullname varchar(128) NOT NULL default 'No Name',
  sortorder int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY publishid (publishid,parentid)
);

CREATE TABLE prefix_netpublish_status (
  id tinyint(3) unsigned NOT NULL auto_increment,
  name varchar(128) NOT NULL default 'No Status',
  PRIMARY KEY  (id)
);

CREATE TABLE prefix_netpublish_images (
  id int(11) unsigned NOT NULL auto_increment,
  course int(10) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '0',
  path varchar(255) NOT NULL default '0',
  mimetype varchar(150) NOT NULL default '0',
  size int(11) unsigned NOT NULL default '0',
  width int(10) unsigned NOT NULL default '0',
  height int(10) unsigned NOT NULL default '0',
  timemodified int(11) unsigned NOT NULL default '0',
  owner int(10) unsigned NOT NULL default '0',
  dir varchar(255),
  PRIMARY KEY  (id),
  KEY course (course),
  KEY dir (dir)
);

INSERT INTO prefix_netpublish_status VALUES (1,'draft');
INSERT INTO prefix_netpublish_status VALUES (2,'firstedit');
INSERT INTO prefix_netpublish_status VALUES (3,'finaledit');
INSERT INTO prefix_netpublish_status VALUES (4,'publish');
INSERT INTO prefix_netpublish_status VALUES (5,'hold');

CREATE TABLE prefix_netpublish_lock (
id int(10) unsigned NOT NULL auto_increment,
pageid int(10) unsigned NOT NULL default '0',
userid int(10) unsigned NOT NULL default '0',
lockstart int(11) unsigned NOT NULL default '0',
PRIMARY KEY  (id),
KEY pageid (pageid,userid)
);

CREATE TABLE prefix_netpublish_first_section_names (
id int(10) unsigned NOT NULL auto_increment,
publishid int(10) unsigned NOT NULL default '0',
name varchar(255) NOT NULL default '',
PRIMARY KEY  (id),
UNIQUE KEY publishid (publishid)
);

CREATE TABLE prefix_netpublish_status_records (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  articleid int (10) unsigned NOT NULL default '0',
  statusid tinyint (3) unsigned NOT NULL default '1',
  counter tinyint (3) unsigned NOT NULL default '0',
  PRIMARY KEY (id),
  UNIQUE KEY statrec_article_idx (articleid),
  KEY statrec_status_idx (statusid)
);

CREATE TABLE prefix_netpublish_grades (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  publishid int(10)unsigned NOT NULL default '0',
  userid int(10) unsigned NOT NULL default '0',
  grade int(10),
  PRIMARY KEY (id),
  KEY prefix_netpublish_grades_idx (publishid, userid)
);

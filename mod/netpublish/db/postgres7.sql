CREATE TABLE prefix_netpublish (
  id SERIAL,
  course INTEGER NOT NULL default '0',
  name VARCHAR(255) NOT NULL default 'No Name',
  intro TEXT,
  timecreated INTEGER NOT NULL default '0',
  timemodified INTEGER NOT NULL default '0',
  maxsize CHAR(1) NOT NULL default '3',
  locktime INTEGER NOT NULL default '0',
  published CHAR(1) NOT NULL default '0',
  fullpage CHAR(1) NOT NULL default '0',
  statuscount INT2 NOT NULL default '0',
  scale INTEGER NOT NULL default '0',
  PRIMARY KEY  (id)
);

CREATE INDEX prefix_netpublish_course_idx ON prefix_netpublish (course);
CREATE INDEX prefix_netpublish_published_idx ON prefix_netpublish (published);
CREATE INDEX prefix_netpublish_scale_idx ON prefix_netpublish (scale);

CREATE TABLE prefix_netpublish_articles (
  id SERIAL,
  publishid INTEGER NOT NULL default '0',
  sectionid INTEGER NOT NULL default '0',
  userid INTEGER NOT NULL default '0',
  teacherid INTEGER NOT NULL default '0',
  prevarticle INTEGER NOT NULL default '0',
  nextarticle INTEGER NOT NULL default '0',
  authors VARCHAR(64) default NULL,
  title VARCHAR(255) NOT NULL default 'No Title',
  intro TEXT,
  content TEXT,
  timepublished INTEGER default NULL,
  timecreated INTEGER NOT NULL default '0',
  timemodified INTEGER NOT NULL default '0',
  statusid INTEGER NOT NULL default '0',
  rights TEXT,
  sortorder INTEGER NOT NULL default '0',
  PRIMARY KEY  (id)
);

CREATE INDEX prefix_netpublish_articles_idx ON prefix_netpublish_articles (publishid, statusid);

CREATE TABLE prefix_netpublish_sections (
  id SERIAL,
  publishid INTEGER NOT NULL default '0',
  parentid INTEGER NOT NULL default '0',
  fullname VARCHAR(128) NOT NULL default 'No Name',
  sortorder INTEGER NOT NULL default '0',
  PRIMARY KEY  (id)
);

CREATE INDEX prefix_netpublish_sections_idx ON prefix_netpublish_sections (publishid, parentid);

CREATE TABLE prefix_netpublish_status (
  id SERIAL,
  name VARCHAR(128) NOT NULL default 'No Status',
  PRIMARY KEY  (id)
);

CREATE TABLE prefix_netpublish_images (
  id SERIAL,
  course INTEGER NOT NULL default '0',
  name VARCHAR(255) NOT NULL default '0',
  path VARCHAR(255) NOT NULL default '0',
  mimetype VARCHAR(150) NOT NULL default '0',
  size INTEGER NOT NULL default '0',
  width INTEGER NOT NULL default '0',
  height INTEGER NOT NULL default '0',
  timemodified INTEGER NOT NULL default '0',
  owner INTEGER default '0',
  dir VARCHAR(255),
  PRIMARY KEY  (id)
);

CREATE INDEX prefix_netpublish_images_idx ON prefix_netpublish_images (course);
CREATE INDEX prefix_netpublish_images_dir_idx ON prefix_netpublish_images (dir);

INSERT INTO prefix_netpublish_status VALUES (1,'draft');
INSERT INTO prefix_netpublish_status VALUES (2,'firstedit');
INSERT INTO prefix_netpublish_status VALUES (3,'finaledit');
INSERT INTO prefix_netpublish_status VALUES (4,'publish');
INSERT INTO prefix_netpublish_status VALUES (5,'hold');

CREATE TABLE prefix_netpublish_lock (
  id SERIAL,
  pageid INTEGER NOT NULL DEFAULT '0',
  userid INTEGER NOT NULL DEFAULT '0',
  lockstart INTEGER NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);

CREATE INDEX prefix_netpublish_lock_idx ON prefix_netpublish_lock (pageid, userid);

CREATE TABLE prefix_netpublish_first_section_names (
  id SERIAL,
  publishid INTEGER NOT NULL,
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY(id)
);

CREATE UNIQUE INDEX prefix_netpublish_first_section_names_idx ON prefix_netpublish_first_section_names (publishid);

CREATE TABLE prefix_netpublish_status_records (
  id SERIAL,
  articleid INTEGER NOT NULL default '0',
  statusid INT2 NOT NULL default '1',
  counter INT2 NOT NULL default '0',
  PRIMARY KEY (id)
);

CREATE UNIQUE INDEX prefix_netpublish_statrec_article_idx ON prefix_netpublish_status_records (articleid);
CREATE INDEX prefix_netpublish_statrec_status_idx ON prefix_netpublish_status_records (statusid);

CREATE TABLE prefix_netpublish_grades (
  id SERIAL,
  publishid INTEGER NOT NULL default '0',
  userid INTEGER NOT NULL default '0',
  grade INTEGER,
  PRIMARY KEY (id)
);

CREATE INDEX prefix_netpublish_grades_idx ON prefix_netpublish_grades (publishid, userid);

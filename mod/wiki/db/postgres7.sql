# This file contains a complete database schema for all the
# tables used by this module, written in SQL

# It may also contain INSERT statements for particular data
# that may be used, especially new entries in the table log_display

CREATE TABLE prefix_wiki (
  id SERIAL PRIMARY KEY,
  course integer  NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  intro text,
  introformat int2 NOT NULL default '0',
  pagename varchar(255) default NULL,
  timemodified integer NOT NULL default '0',
  editable integer NOT NULL default '1',
  attach integer NOT NULL default '0',
  upload integer NOT NULL default '0',
  restore integer NOT NULL default '0',
  editor varchar(40) NOT NULL default 'dfwiki',
  groupmode integer NOT NULL default '0',
  teacherdiscussion integer NOT NULL default '0',
  studentdiscussion integer NOT NULL default '0',
  evaluation varchar(40) default 'noeval',
  notetype varchar(40) default 'quant',
  votemode integer NOT NULL default '0',
  editorrows integer NOT NULL default '40',
  editorcols integer NOT NULL default '60',
  filetemplate varchar(60) NULL default NULL
);

CREATE INDEX prefix_wiki_course_idx ON prefix_wiki (course);

CREATE TABLE prefix_wiki_pages (
  id SERIAL PRIMARY KEY,
  pagename VARCHAR(160) NOT NULL,
  version INTEGER  NOT NULL DEFAULT '0',
  content TEXT,
  author VARCHAR(100) DEFAULT 'dfwiki',
  userid INTEGER  DEFAULT 0,
  created INTEGER  DEFAULT '0',
  lastmodified INTEGER  DEFAULT 0,
  refs TEXT,
  hits INTEGER DEFAULT '0',
  editable integer NOT NULL default '1',
  highlight integer NOT NULL default '0',
  dfwiki integer  NOT NULL default '0',
  editor varchar(40) NOT NULL default 'dfwiki',
  groupid integer  NOT NULL default '0',
  evaluation TEXT default NULL
);

CREATE UNIQUE INDEX prefix_wiki_pages_uk ON prefix_wiki_pages (pagename, version, dfwiki, groupid, userid) ;

CREATE TABLE prefix_wiki_synonymous (
  id SERIAL PRIMARY KEY,
  syn VARCHAR(160) NOT NULL default '',
  original VARCHAR(160) NOT NULL default '0',
  dfwiki integer UNIQUE NOT NULL default '0',
  groupid integer  NOT NULL default '0',
  userid integer  NOT NULL default '0'
);

CREATE TABLE `prefix_wiki_votes` (
  `id` SERIAL PRIMARY KEY,
  `pagename` varchar(160) NOT NULL,
  `version` integer NOT NULL default '0',
  `dfwiki` integer NOT NULL,
  `username` varchar(100) NOT NULL
);

CREATE UNIQUE INDEX prefix_wiki_synonymous_uk ON prefix_wiki_synonymous (syn, dfwiki, groupid, userid) ;

CREATE TABLE prefix_wiki_locks (
    id SERIAL PRIMARY KEY,
    wikiid INTEGER NOT NULL,
    pagename VARCHAR(160) NOT NULL DEFAULT '',
    lockedby INTEGER NOT NULL DEFAULT '0',
    lockedsince INTEGER NOT NULL DEFAULT '0',
    lockedseen INTEGER NOT NULL DEFAULT '0',
);

CREATE UNIQUE INDEX prefix_wiki_locks_uk ON prefix_wiki_locks (wikiid, pagename) ;

INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'add', 'wiki', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'update', 'wiki', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'view', 'wiki', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'view all', 'wiki', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'view page', 'wiki_pages', 'pagename');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'edt page', 'wiki_pages', 'pagename');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'save page', 'wiki_pages', 'pagename');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('wiki', 'info page', 'wiki_pages', 'pagename');

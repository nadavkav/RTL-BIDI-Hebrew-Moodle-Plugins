CREATE TABLE prefix_imagegallery (
  id int(10) unsigned NOT NULL auto_increment,
  course int(10) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  intro blob,
  maxbytes int(10) unsigned NOT NULL default '0',
  maxwidth int(10) unsigned NOT NULL default '0',
  maxheight int(10) unsigned NOT NULL default '0',
  allowstudentupload tinyint(1) unsigned NOT NULL default '0',
  imagesperpage int(10) NOT NULL default '10',
  timemodified int(10) unsigned NOT NULL default '0',
  requirelogin tinyint(1) unsigned NOT NULL default '0',
  resize tinyint(1) unsigned NOT NULL default '0',
  defaultcategory smallint UNSIGNED NOT NULL default '0',
  shadow tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY (id),
  KEY prefix_imagegallery_idx (course)
);

CREATE TABLE prefix_imagegallery_categories (
  id int(10) unsigned NOT NULL auto_increment,
  galleryid int(10) unsigned NOT NULL default '0',
  userid int(10) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  description blob,
  timecreated int(10) unsigned NOT NULL default '0',
  timemodified int(10) unsigned NOT NULL default '0',
  PRIMARY KEY (id),
  KEY prefix_imagegallery_categories_idx (galleryid, userid)
);

CREATE TABLE prefix_imagegallery_images (
  id int(10) unsigned NOT NULL auto_increment,
  galleryid int(10) unsigned NOT NULL default '0',
  categoryid int(10) unsigned NOT NULL default '0',
  userid int(10) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  size int(10) unsigned NOT NULL default '0',
  mime varchar(255) NOT NULL default '',
  width int(10) unsigned NOT NULL default '0',
  height int(10) unsigned NOT NULL default '0',
  path varchar(255) NOT NULL default '',
  description blob,
  timecreated int(10) unsigned NOT NULL default '0',
  timemodified int(10) unsigned NOT NULL default '0',
  PRIMARY KEY (id),
  KEY prefix_imagegallery_images_idx (galleryid, categoryid, userid)
);
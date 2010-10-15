CREATE TABLE prefix_imagegallery (
  id SERIAL,
  course INTEGER NOT NULL DEFAULT '0',
  name VARCHAR(255) NOT NULL DEFAULT '',
  intro TEXT,
  maxbytes INTEGER NOT NULL DEFAULT '0',
  maxwidth INTEGER NOT NULL DEFAULT '0',
  maxheight INTEGER NOT NULL DEFAULT '0',
  allowstudentupload CHAR(1) NOT NULL DEFAULT '0',
  imagesperpage INTEGER NOT NULL DEFAULT '10',
  timemodified INTEGER NOT NULL DEFAULT '0',
  requirelogin CHAR(1) NOT NULL DEFAULT '0',
  resize CHAR(1) NOT NULL DEFAULT '0',
  defaultcategory SMALLINT NOT NULL DEFAULT '0',
  shadow CHAR(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);

CREATE INDEX prefix_imagegallery_idx ON prefix_imagegallery (course);

CREATE TABLE prefix_imagegallery_categories (
  id SERIAL,
  galleryid INTEGER NOT NULL DEFAULT '0',
  userid INTEGER NOT NULL DEFAULT '0',
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  timecreated INTEGER NOT NULL DEFAULT '0',
  timemodified INTEGER NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);

CREATE INDEX prefix_imagegallery_categories_idx ON prefix_imagegallery_categories(galleryid, userid);

CREATE TABLE prefix_imagegallery_images (
  id SERIAL,
  galleryid INTEGER NOT NULL DEFAULT '0',
  categoryid INTEGER NOT NULL DEFAULT '0',
  userid INTEGER NOT NULL DEFAULT '0',
  name VARCHAR(255) NOT NULL DEFAULT '',
  size INTEGER NOT NULL DEFAULT '0',
  mime VARCHAR(255) NOT NULL DEFAULT '',
  width INTEGER NOT NULL DEFAULT '0',
  height INTEGER NOT NULL DEFAULT '0',
  path VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  timecreated INTEGER NOT NULL DEFAULT '0',
  timemodified INTEGER NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);

CREATE INDEX prefix_imagegallery_images_idx ON prefix_imagegallery_images(galleryid, categoryid, userid);

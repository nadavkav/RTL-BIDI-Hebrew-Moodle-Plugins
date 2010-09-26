#
# Table structure for table `prefix_fmanager`
#

# New table stores links to files and sites
# id 			= unique id
# owner 		= owner of link
# type 		= type of link (file/url)
# folder 		= folder location (for folder sharing)
# category 		= category (for category sharing)
# name	 	= name given to link
# description 	= description of link
# link 		= File attachment name or url
# time modified 	= Date last changed

CREATE TABLE prefix_fmanager_link (
	id INT(10) unsigned NOT NULL auto_increment,	
	owner INT(10) UNSIGNED NOT NULL default '0',	
    ownertype tinyint(3) unsigned NOT NULL COMMENT '0 for user, 1 for\r\ngroup',
 	type TINYINT UNSIGNED NOT NULL default '0',	
	folder INT(10) UNSIGNED default '0',		
	category INT(10) UNSIGNED NOT NULL default '0',	
	name VARCHAR(255) NOT NULL default '', 		
	description TEXT NOT NULL default '', 		
	link VARCHAR(255) NOT NULL default '',		
	timemodified INT(10) UNSIGNED NOT NULL default '0',
PRIMARY KEY (id)
) TYPE=MyISAM COMMENT='Stores users links to files/urls';
	
# Stores user defined categories
# id 		= unique id
# userid 	= owner id
# name	= name of category

CREATE TABLE prefix_fmanager_categories (
   	id INT(10) UNSIGNED NOT NULL auto_increment,
	owner INT(10) UNSIGNED NOT NULL default '0',
    ownertype tinyint(3) unsigned NOT NULL COMMENT '0 for user, 1 for\r\ngroup',
	name VARCHAR(255) NOT NULL default '',
	timemodified INT(10) UNSIGNED NOT NULL default '0',
PRIMARY KEY  (id)
) TYPE=MyISAM COMMENT='User defined categories';

# Stores user defined folder names/structure to allow folder sharing
# id 			= unique id
# owner 		= owner id
# name 		= name of folder
# category 		= category
# path	 	= entire sub-path to folder
# pathid 		= id of root folder
# timemodified	= time modified 

CREATE TABLE prefix_fmanager_folders (
	id INT(10) UNSIGNED NOT NULL auto_increment,
	owner INT(10) UNSIGNED NOT NULL default '0',
    ownertype tinyint(3) unsigned NOT NULL COMMENT '0 for user, 1 for\r\ngroup',
	name VARCHAR(255) NOT NULL default '',
	category INT(10) UNSIGNED NOT NULL default '0',
	path VARCHAR(255) NOT NULL default '',
	pathid INT(10) UNSIGNED NOT NULL default '0',
	timemodified INT(10) UNSIGNED NOT NULL default '0',
PRIMARY KEY (id)
) TYPE=MyISAM COMMENT='User created folders';

# Stores shared links/folders/cats (need to differentiate)
# 	[links = individual files/urls]
#	[folders = read folder subdir (dynamic sharing)]
#	[cats = read all files with applied cat (dynamic sharing)]
# id 			= unique id
# owner		= Owner of shared items id#
# course		= Course shared from (for student organization) (viewing shared split by course)
# type		= type of shared item (link(file/url)/folder/cat)
# sharedlink	= id of shared item
# userid		= User file is shared to
# viewed		= Flag tells if user has viewed the file yet

CREATE TABLE prefix_fmanager_shared (
	id INT(10) UNSIGNED NOT NULL auto_increment,
	owner INT(10) UNSIGNED NOT NULL default '0',
    ownertype tinyint(3) unsigned NOT NULL COMMENT '0 for user, 1 for\r\ngroup',
	course INT(10) UNSIGNED NOT NULL default '0',
	type TINYINT UNSIGNED NOT NULL default '0',
	sharedlink INT(10) UNSIGNED NOT NULL default '0',
	userid INT(10) UNSIGNED NOT NULL default '0',
	viewed TINYINT UNSIGNED NOT NULL default '0',
PRIMARY KEY  (id)
) TYPE=MyISAM COMMENT='Shared items information';

# Stores admin security/upload management settings/vars
# id 			= unique id
# usertype		= admin/teacher/student/guest [0-3]
# maxupload		= maximum file size for upload
# maxdir		= maximum directory size for storage
# enable_fmanager = enables the manager for a type of user (They still see shared files and admin settings)
# allowsharing    = Determines if a user can share any files or not
# sharetoany	= stores if a user can share to anyone from course 1
## uploadtype	= types of files allowed (via .ext) [0=all 1=(images,videos,txt,etc) 2=(images) 3=(video) 4=(music) 5=(custom) etc...]
## userview		= what users user can view 

CREATE TABLE prefix_fmanager_admin (
	id INT(10) UNSIGNED NOT NULL auto_increment,
	usertype TINYINT(3) UNSIGNED NOT NULL default '0',
	maxupload INT(10) UNSIGNED NOT NULL default '0',
	maxdir INT(10) UNSIGNED NOT NULL default '0',
	enable_fmanager TINYINT(1) UNSIGNED NOT NULL default '1',
	allowsharing TINYINT(1) UNSIGNED NOT NULL default '1',
	sharetoany TINYINT(1) UNSIGNED NOT NULL default '0',
PRIMARY KEY  (id)
) TYPE=MyISAM COMMENT='Stores students upload/directory sizes and more';

INSERT INTO prefix_log_display VALUES ('','fmanager', 'add', 'fmanager', 'name');
INSERT INTO prefix_log_display VALUES ('','fmanager', 'update', 'fmanager', 'name');
INSERT INTO prefix_log_display VALUES ('','fmanager', 'view', 'fmanager', 'name');

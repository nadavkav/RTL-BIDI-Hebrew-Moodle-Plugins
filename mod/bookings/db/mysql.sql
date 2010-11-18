
DROP TABLE IF EXISTS `prefix_bookings`;
CREATE TABLE prefix_bookings (
     `id` int(10) unsigned NOT NULL auto_increment,
     `course` int(10) NOT NULL,
     `userid` int(10) NOT NULL,
     `groupid` int(10) NOT NULL,
     `name` varchar(255) NOT NULL,
     `summary` text,
     `type`  varchar(255) NOT NULL,
     `itemid` int(10) NOT NULL,
     `enddate` int(10) NOT NULL,
     `startdate` int(10) NOT NULL,
     PRIMARY KEY (`id`),
     INDEX course (course)
  ) 
  COMMENT = 'Itemid will be link to item that is being scheduled';

DROP TABLE IF EXISTS `prefix_bookings_calendar`;
CREATE TABLE prefix_bookings_calendar (
     `id` int(10) unsigned NOT NULL auto_increment,
     `courseid` int(10) NOT NULL,
     `userid` int(10) NOT NULL,
     `bookingid` int(10) NOT NULL,
     `julday` int(10) NOT NULL,
     `day` tinyint(1) NOT NULL,
     `slot` tinyint(1) NOT NULL,
     `itemid` int(10) NOT NULL,
     `start` int(10) NOT NULL,
     `duration` int(10) NOT NULL,
     `eventtype` varchar(20) NOT NULL,
     `name`  varchar(100),
     `value` text,
     PRIMARY KEY (`id`),
     INDEX courseid (courseid),
     INDEX julday (julday),
     INDEX itemid (itemid)
  ) 
  COMMENT = 'booking and Timetable';

DROP TABLE IF EXISTS `prefix_bookings_item`;
CREATE TABLE prefix_bookings_item (
     `id` int(10) unsigned NOT NULL auto_increment,
     `name` varchar(100) NOT NULL,
     `type`  varchar(100) NOT NULL,
     `parent` int(10) default 0,
     PRIMARY KEY (`id`),
     INDEX name (name),
     INDEX parent (parent)
  ) 
  COMMENT = 'Items, can be containers (like rooms), or members (like a pc in a room)';

DROP TABLE IF EXISTS `prefix_bookings_item_property`;
CREATE TABLE prefix_bookings_item_property (
     `id` int(10) unsigned NOT NULL auto_increment,
     `itemid` int(10) NOT NULL,
     `name`  varchar(100) NOT NULL,
     `value` text NOT NULL,
     PRIMARY KEY (`id`),
     INDEX itemid (itemid)
  ) 
  COMMENT = 'Item properties, like seats for rooms or status for a PC';

INSERT INTO mdl_bookings_item VALUES (1,'room_template','room',0),
(2,'rom110','room',3),
(3,'Main','building',0),
(4,'building_template','building',0),
(5,'rom210','room',3),
(15,'examroom','room',3),
(25,'equipment_template','equipment',0),
(30,'film_template','film',0),
(32,'film','film',0),
(31,'magazine_template','magazine',0),
(33,'magazine','magazine',0),
(24,'examroom_template','examroom',0),
(26,'Beamer-1','equipment',0),
(27,'student-equipment_template','student-equipment',0),
(29,'Examroom-101','examroom',0),
(28,'Compass','student-equipment',0);

INSERT INTO mdl_bookings_item_property VALUES (1,1,'pc','textfield,16'),
(2,1,'seats','textfield,16,30'),
(3,1,'childtype','textfield,16,pc'),
(4,2,'childtype','pc'),
(5,2,'pc','25'),
(6,2,'seats','29'),
(18,2,'slots','8,9,10,11,12,13,14,15'),
(7,3,'Location','rogalandsgt 197'),
(8,4,'Location','textfield,20'),
(9,4,'childtype','textfield,16,room'),
(10,3,'childtype','room'),
(29,2,'scheduled','yes'),
(19,2,'days','Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'),
(13,2,'startsched','2005,1,3'),
(14,5,'childtype','pc'),
(15,5,'seats','30'),
(16,5,'pc','30'),
(24,1,'scheduled','textfield,16'),
(37,15,'childtype','pc'),
(30,2,'scheduled','yes'),
(25,5,'scheduled','yes'),
(26,2,'edit_group','teacher'),
(33,1,'edit_list','textfield,16'),
(35,1,'edit_group','textfield,16,teacher'),
(36,5,'edit_group','teacher'),
(38,15,'edit_group','student'),
(39,15,'scheduled','yes'),
(40,15,'seats','30'),
(41,15,'multiple','10'),
(86,31,'scheduled','textfield,16,yearly'),
(85,30,'edit_group','textfield,16,teachers'),
(72,25,'scheduled','textfield,16,weekly'),
(71,24,'scheduled','textfield,16,weekly'),
(70,24,'seats','textfield,16,30'),
(73,25,'edit_group','textfield,16,teachers'),
(74,26,'edit_group','teachers'),
(75,26,'scheduled','weekly'),
(84,30,'scheduled','textfield,16,yearly'),
(76,27,'scheduled','textfield,16,weekly'),
(77,27,'edit_group','textfield,16,students'),
(78,28,'edit_group','students'),
(79,28,'scheduled','weekly'),
(80,24,'multiple','textfield,16,30'),
(81,29,'multiple','30'),
(82,29,'scheduled','weekly'),
(83,29,'seats','30'),
(87,31,'edit_group','textfield,16,students'),
(88,32,'scheduled','yearly'),
(89,33,'scheduled','yearly'),
(90,32,'edit_group','teachers'),
(91,33,'edit_group','students');


<?php // $Id: mysql.php,v 1.1.14.1 2007/09/24 08:54:29 janne Exp $

function netpublish_upgrade($oldversion) {
/// This function does anything necessary to upgrade
/// older versions to match current functionality

    global $CFG;

    if ($oldversion < 2005020701) {

        $sql  = "ALTER TABLE {$CFG->prefix}netpublish ADD COLUMN maxsize ";
        $sql .= "TINYINT(1) NOT NULL DEFAULT '3'";
        execute_sql($sql);

        $sql  = "CREATE TABLE {$CFG->prefix}netpublish_images (";
        $sql .= "id int(11) unsigned NOT NULL auto_increment,";
        $sql .= "course int(10) unsigned NOT NULL default '0',";
        $sql .= "name varchar(255) NOT NULL default '0',";
        $sql .= "path varchar(255) NOT NULL default '0',";
        $sql .= "mimetype varchar(150) NOT NULL default '0',";
        $sql .= "size int(11) unsigned NOT NULL default '0',";
        $sql .= "width int(10) unsigned NOT NULL default '0',";
        $sql .= "height int(10) unsigned NOT NULL default '0',";
        $sql .= "timemodified int(11) unsigned NOT NULL default '0',";
        $sql .= "owner int(10) unsigned NOT NULL default '0',";
        $sql .= "PRIMARY KEY  (id),";
        $sql .= "KEY course (course)";
        $sql .= ")";

        execute_sql($sql);

    }

    if ($oldversion < 2005020702) {

        $sql  = "CREATE TABLE {$CFG->prefix}netpublish_lock (";
        $sql .= "id INT(10) unsigned NOT NULL AUTO_INCREMENT,";
        $sql .= "pageid INT(10) unsigned NOT NULL DEFAULT '0',";
        $sql .= "userid INT(10) unsigned NOT NULL DEFAULT '0',";
        $sql .= "lockstart INT(11) unsigned NOT NULL DEFAULT '0',";
        $sql .= "PRIMARY KEY(id),";
        $sql .= "KEY pageid (pageid, userid)";
        $sql .= ")";
        execute_sql($sql);

        $sql  = "ALTER TABLE {$CFG->prefix}netpublish ADD COLUMN locktime ";
        $sql .= "INT(10) NOT NULL default '0'";
        execute_sql($sql);

    }

    if ($oldversion < 2005040603) {

        $sql  = "ALTER TABLE {$CFG->prefix}netpublish ADD COLUMN published ";
        $sql .= "TINYINT(1) NOT NULL default '0'";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}netpublish ADD INDEX published (published)";
        execute_sql($sql);

    }

    if ($oldversion < 2005042200) {
        $sql  = "CREATE TABLE {$CFG->prefix}netpublish_first_section_names (";
        $sql .= "id int(10) unsigned NOT NULL auto_increment,";
        $sql .= "publishid int(10) unsigned NOT NULL default '0',";
        $sql .= "name varchar(255) NOT NULL default '',";
        $sql .= "PRIMARY KEY  (id),";
        $sql .= "UNIQUE KEY publishid (publishid)";
        $sql .= ")";
        execute_sql($sql);
    }

    if ($oldversion < 2005042601) {
        $sql  = "ALTER TABLE {$CFG->prefix}netpublish ADD COLUMN fullpage ";
        $sql .= "TINYINT(1) NOT NULL default '0'";
        execute_sql($sql);
    }

    if ($oldversion < 2005042900) {
        $sql  = "ALTER TABLE {$CFG->prefix}netpublish_articles ADD COLUMN prevarticle ";
        $sql .= "INT(10) unsigned NOT NULL default '0'";
        execute_sql($sql);

        $sql  = "ALTER TABLE {$CFG->prefix}netpublish_articles ADD COLUMN nextarticle ";
        $sql .= "INT(10) unsigned NOT NULL default '0'";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}netpublish_images ADD COLUMN dir VARCHAR(255)";
        execute_sql($sql);
        $sql = "ALTER TABLE {$CFG->prefix}netpublish_images ADD INDEX dir (dir)";
        execute_sql($sql);

    }

    if ($oldversion < 2005062200) {

        $sql  = "ALTER TABLE {$CFG->prefix}netpublish ";
        $sql .= "ADD COLUMN statuscount tinyint (3) unsigned NOT NULL default '0'";
        execute_sql($sql);

        $sql  = "CREATE TABLE {$CFG->prefix}netpublish_status_records (";
        $sql .= "  id int(10) unsigned NOT NULL AUTO_INCREMENT,";
        $sql .= "  articleid int (10) unsigned NOT NULL default '0',";
        $sql .= "  statusid tinyint (3) unsigned NOT NULL default '1',";
        $sql .= "  counter tinyint (3) unsigned NOT NULL default '0',";
        $sql .= "  PRIMARY KEY (id),";
        $sql .= "  UNIQUE KEY statrec_article_idx (articleid),";
        $sql .= "  KEY statrec_status_idx (statusid)";
        $sql .= ")";

        execute_sql($sql);

    }

    if ($oldversion < 2005072301) {

        $sql  = "ALTER TABLE {$CFG->prefix}netpublish ";
        $sql .= "ADD COLUMN scale int(10) NOT NULL default '0'";
        execute_sql($sql);

        $sql  = "ALTER TABLE {$CFG->prefix}netpublish ";
        $sql .= "ADD INDEX scale (scale)";
        execute_sql($sql);

    }

    if ($oldversion < 2005072302) {

        $sql  = "CREATE TABLE {$CFG->prefix}netpublish_grades (";
        $sql .= "  id int(10) unsigned NOT NULL AUTO_INCREMENT,";
        $sql .= "  publishid int(10) unsigned NOT NULL default '0',";
        $sql .= "  userid int(10) unsigned NOT NULL default '0',";
        $sql .= "  grade int(10),";
        $sql .= "  PRIMARY KEY (id),";
        $sql .= "  KEY {$CFG->prefix}netpublish_grades_idx (publishid, userid)";
        $sql .= ")";

        execute_sql($sql);
    }

    if ($oldversion < 2005080901) {

        $sql  = "ALTER TABLE {$CFG->prefix}netpublish_articles ";
        $sql .= "ADD COLUMN sortorder int(10) unsigned NOT NULL default '0'";

        execute_sql($sql);

    }

    if ( $oldversion < 2009083001 ) {
	$sql  = "ALTER TABLE {$CFG->prefix}netpublish ";
	$sql .= "ADD COLUMN titleimage varchar(255) NOT NULL default '0',";
	$sql .= "ADD COLUMN theme varchar(255) NOT NULL default '0',";
	$sql .= "ADD COLUMN title varchar(255) NOT NULL default '0',";
        $sql .= "ADD COLUMN frontpagecolumns tinyint(1) unsigned NOT NULL default '2'";

        execute_sql($sql);
    }

    return true;
}

?>

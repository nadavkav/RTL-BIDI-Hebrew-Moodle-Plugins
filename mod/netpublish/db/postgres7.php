<?php

function netpublish_upgrade($oldversion) {
/// This function does anything necessary to upgrade
/// older versions to match current functionality

    global $CFG;

    if ($oldversion < 2005020701) {

        $sql = "ALTER TABLE {$CFG->prefix}netpublish ADD COLUMN maxsize CHAR(1)";
        execute_sql($sql);
        $sql = "ALTER TABLE {$CFG->prefix}netpublish ALTER COLUMN maxsize SET DEFAULT '3'";
        execute_sql($sql);
        $sql = "UPDATE {$CFG->prefix}netpublish SET maxsize = '3' WHERE maxsize IS NULL";
        execute_sql($sql);
        $sql = "ALTER TABLE {$CFG->prefix}nepublish ALTER COLUMN maxsize SET NOT NULL";
        execute_sql($sql);


        $sql  = "CREATE TABLE {$CFG->prefix}netpublish_images (";
        $sql .= "id SERIAL,";
        $sql .= "course INTEGER NOT NULL default '0',";
        $sql .= "name VARCHAR(255) NOT NULL default '0',";
        $sql .= "path VARCHAR(255) NOT NULL default '0',";
        $sql .= "mimetype VARCHAR(150) NOT NULL default '0',";
        $sql .= "size INTEGER NOT NULL default '0',";
        $sql .= "width INTEGER NOT NULL default '0',";
        $sql .= "height INTEGER NOT NULL default '0',";
        $sql .= "timemodified INTEGER NOT NULL default '0',";
        $sql .= "owner INTEGER default '0',";
        $sql .= "PRIMARY KEY  (id)";
        $sql .= ")";
        execute_sql($sql);

        $sql = "CREATE INDEX {$CFG->prefix}netpublish_images_idx ON {$CFG->prefix}netpublish_images (course)";
        execute_sql($sql);

    }

    if ($oldversion < 2005020702) {

        $sql = "ALTER TABLE {$CFG->prefix}netpublish ADD COLUMN locktime INTEGER";
        execute_sql($sql);
        $sql = "ALTER TABLE {$CFG->prefix}netpublish ALTER COLUMN locktime SET DEFAULT '0'";
        execute_sql($sql);
        $sql = "UPDATE TABLE {$CFG->prefix}netpublish SET locktime = '0' WHERE locktime IS NULL";
        execute_sql($sql);
        $sql = "ALTER TABLE {$CFG->prefix}netpublish ALTER COLUMN locktime SET NOT NULL";
        execute_sql($sql);

        $sql  = "CREATE TABLE {$CFG->prefix}netpublish_lock (";
        $sql .= "id SERIAL,";
        $sql .= "pageid INTEGER NOT NULL DEFAULT '0',";
        $sql .= "userid INTEGER NOT NULL DEFAULT '0',";
        $sql .= "lockstart INTEGER NOT NULL DEFAULT '0',";
        $sql .= "PRIMARY KEY (id)";
        $sql .= ")";
        execute_sql($sql);

        $sql = "CREATE INDEX {$CFG->prefix}netpublish_lock_idx ON {$CFG->prefix}netpublish_lock (pageid, userid)";
        execute_sql($sql);

    }

    if ($oldversion < 2005040603) {

        $sql = "ALTER TABLE {$CFG->prefix}netpublish ADD COLUMN published CHAR(1)";
        execute_sql($sql);
        $sql = "ALTER TABLE {$CFG->prefix}netpublish ALTER COLUMN published SET DEFAULT '0'";
        execute_sql($sql);
        $sql = "UPDATE TABLE {$CFG->prefix}netpublish SET published = '0' WHERE published IS NULL";
        execute_sql($sql);
        $sql = "ALTER TABLE {$CFG->prefix}netpublish ALTER COLUMN published SET NOT NULL";
        execute_sql($sql);
        $sql = "CREATE INDEX {$CFG->prefix}netpublish_published_idx ON {$CFG->prefix}netpublish (published)";
        execute_sql($sql);
    }

    if ($oldversion < 2005042200) {
        $sql  = "CREATE TABLE {$CFG->prefix}netpublish_first_section_names (";
        $sql .= "id SERIAL,";
        $sql .= "publishid INTEGER NOT NULL,";
        $sql .= "name VARCHAR(255) NOT NULL,";
        $sql .= "PRIMARY KEY(id)";
        $sql .= ")";
        execute_sql($sql);

        $sql  = "CREATE UNIQUE INDEX {$CFG->prefix}netpublish_first_section_names_idx ON ";
        $sql .= "{$CFG->prefix}netpublish_first_section_names (publishid)";
        execute_sql($sql);
    }

    if ($oldversion < 2005042601) {
        $sql = "ALTER TABLE {$CFG->prefix}netpublish ADD COLUMN fullpage CHAR(1)";
        execute_sql($sql);
        $sql = "ALTER TABLE {$CFG->prefix}netpublish ALTER COLUMN fullpage SET DEFAULT '0'";
        execute_sql($sql);
        $sql = "UPDATE TABLE {$CFG->prefix}netpublish SET fullpage = '0' WHERE fullpage IS NULL";
        execute_sql($sql);
    }

    if ($oldversion < 2005042900) {
        $sql = "ALTER TABLE {$CFG->prefix}netpublish_articles ADD COLUMN prevarticle INTEGER";
        execute_sql($sql);
        $sql = "ALTER TABLE {$CFG->prefix}netpublish_articles ALTER COLUMN prevarticle SET DEFAULT '0'";
        execute_sql($sql);
        $sql = "UPDATE TABLE {$CFG->prefix}netpublish_articles SET prevarticle = '0' WHERE prevarticle IS NULL";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}netpublish_articles ADD COLUMN nextarticle INTEGER";
        execute_sql($sql);
        $sql = "ALTER TABLE {$CFG->prefix}netpublish_articles ALTER COLUMN nextarticle SET DEFAULT '0'";
        execute_sql($sql);
        $sql = "UPDATE TABLE {$CFG->prefix}netpublish_articles SET nextarticle = '0' WHERE nextarticle IS NULL";
        execute_sql($sql);
        $sql = "ALTER TABLE {$CFG->prefix}netpublish_images ADD COLUMN dir VARCHAR(255)";
        execute_sql($sql);
        $sql  = "CREATE INDEX {$CFG->prefix}netpublish_images_dir_idx ON ";
        $sql .= "{$CFG->prefix}netpublish_images (dir)";
        execute_sql($sql);
    }

    if ($oldversion < 2005062200) {

        $sql = "ALTER TABLE {$CFG->prefix}netpublish ADD COLUMN statuscount INT2";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}netpublish ALTER COLUMN statuscount SET DEFAULT '0'";
        execute_sql($sql);

        $sql = "UPDATE {$CFG->prefix}netpublish SET statuscount = '0' WHERE statuscount IS NULL";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}nepublish ALTER COLUMN statuscount SET NOT NULL";
        execute_sql($sql);

        $sql  = "CREATE TABLE {$CFG->prefix}netpublish_status_records (";
        $sql .= " id SERIAL,";
        $sql .= " articleid INTEGER NOT NULL default '0',";
        $sql .= " statusid INT2 NOT NULL default '1',";
        $sql .= " counter INT2 NOT NULL default '0',";
        $sql .= " PRIMARY KEY (id)";
        $sql .= ")";

        execute_sql($sql);

        $sql  = "CREATE UNIQUE INDEX {$CFG->prefix}netpublish_statrec_article_idx ON ";
        $sql .= "{$CFG->prefix}netpublish_status_records (articleid)";
        execute_sql($sql);

        $sql  = "CREATE INDEX {$CFG->prefix}netpublish_statrec_status_idx ON ";
        $sql .= "{$CFG->prefix}netpublish_status_records (statusid)";
        execute_sql($sql);

    }

    if ($oldversion < 2005072301) {

        $sql = "ALTER TABLE {$CFG->prefix}netpublish ADD COLUMN scale INTEGER";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}netpublish ALTER COLUMN scale SET DEFAULT '0'";
        execute_sql($sql);

        $sql = "UPDATE {$CFG->prefix}netpublish SET scale = '0' WHERE scale IS NULL";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}netpublish ALTER COLUMN scale SET NOT NULL";
        execute_sql($sql);

    }

    if ($oldversion < 2005072302) {

        $sql  = "CREATE TABLE {$CFG->prefix}netpublish_grades (";
        $sql .= "  id SERIAL,";
        $sql .= "  publishid INTEGER NOT NULL default '0',";
        $sql .= "  userid INTEGER NOT NULL default '0',";
        $sql .= "  grade INTEGER,";
        $sql .= "  PRIMARY KEY (id)";
        $sql .= ")";
        execute_sql($sql);

        $sql  = "CREATE INDEX {$CFG->prefix}netpublish_grades_idx ";
        $sql .= "ON {$CFG->prefix}netpublish_grades (publishid, userid)";
        execute_sql($sql);

    }

    if ($oldversion < 2005080901) {

        $sql = "ALTER TABLE {$CFG->prefix}netpublish_articles ADD COLUMN sortorder INTEGER";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}netpublish_articles ALTER COLUMN sortorder SET DEFAULT '0'";
        execute_sql($sql);

        $sql = "UPDATE {$CFG->prefix}netpublish_articles SET sortorder = '0' WHERE sortorder IS NULL";
        execute_sql($sql);

        $sql = "ALTER TABLE {$CFG->prefix}netpublish_articles ALTER COLUMN sortorder SET NOT NULL";
        execute_sql($sql);

    }

    if ( $oldversion < 2007092401 ) {
    }

    return true;
}

?>

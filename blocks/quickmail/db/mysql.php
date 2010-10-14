<?PHP  //$Id: mysql.php,v 1.3 2006/02/01 19:54:59 michaelpenne Exp $
//
// This file keeps track of upgrades to Moodle's
// blocks system.
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// Versions are defined by backup_version.php
//
// This file is tailored to MySQL

function email_upgrade($oldversion=0) {

    global $CFG;
    
    $result = true;
    
    if ($oldversion < 2005012800 && $result) {
        execute_sql(" create table ".$CFG->prefix."block_quickmail_log
                    ( id int(10) unsigned not null auto_increment,
                      courseid int(10) unsigned not null,
                      userid int(10) unsigned not null,
                      mailto text not null,
                      subject varchar(255) not null,
                      message text not null,
                      attachment varchar(255) not null,
                      format tinyint(3) unsigned not null default 1,
                      timesent int(10) unsigned not null,
                      PRIMARY KEY  (`id`)
                    )");
    }

    return $result;
}

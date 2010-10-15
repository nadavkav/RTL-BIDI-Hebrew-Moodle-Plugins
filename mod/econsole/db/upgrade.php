<?php  //$Id: upgrade.php,v 1.2.4.1 2008/05/01 20:38:48 skodak Exp $

// This file keeps track of upgrades to 
// the econsole module
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
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_econsole_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($result && $oldversion < 2009021701) {

    /// Define field format to be added to econsole
        $table = new XMLDBTable('econsole');
        $field = new XMLDBField('imagebartop');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '', 'theme');
        $field2 = new XMLDBField('imagebarbottom');
        $field2->setAttributes(XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '', 'imagebartop');		

    /// Launch add field format
        $result = $result && add_field($table, $field) && add_field($table, $field2);

    }

//===== 1.9.0 upgrade line ======//

    return $result;
}

?>

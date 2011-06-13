<?php  //$Id: upgrade.php,v 1.2 2007/08/08 22:36:54 stronk7 Exp $

// This file keeps track of upgrades to the mplayer module
//
// The commands in here will all be database-neutral, using the functions defined in lib/ddllib.php

function xmldb_mplayer_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

	//if ($result && $oldversion < 2009112500) { //New version in version.php
	//     $result = result of "/lib/ddllib.php" function calls
	//}

/// For each upgrade block, the file mplayer/version.php

/// To know more about how to write correct DB upgrade scripts it's
/// highly recommended to read information available at:
///   http://docs.moodle.org/en/Development:XMLDB_Documentation
/// and to play with the XMLDB Editor (in the admin menu) and its
/// PHP generation posibilities.

/// First example, some fields were added to the module on 20090900
    /*if ($result && $oldversion < 2009012800) {

    /// Define field course to be added to mplayer
        $table = new XMLDBTable('mplayer');
        $field = new XMLDBField('course');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'id');
    /// Launch add field course
        $result = $result && add_field($table, $field);

    /// Define field intro to be added to mplayer
        $table = new XMLDBTable('mplayer');
        $field = new XMLDBField('intro');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null, 'name');
    /// Launch add field intro
        $result = $result && add_field($table, $field);

    /// Define field introformat to be added to mplayer
        $table = new XMLDBTable('mplayer');
        $field = new XMLDBField('introformat');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'intro');
    /// Launch add field introformat
        $result = $result && add_field($table, $field);
    }*/

/// Second example, some hours later, the same day 20090901
/// two more fields and one index were added (note the increment
/// "01" in the last two digits of the version
    /*if ($result && $oldversion < 2009012801) {

    /// Define field timecreated to be added to mplayer
        $table = new XMLDBTable('mplayer');
        $field = new XMLDBField('timecreated');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'introformat');
    /// Launch add field timecreated
        $result = $result && add_field($table, $field);

    /// Define field timemodified to be added to mplayer
        $table = new XMLDBTable('mplayer');
        $field = new XMLDBField('timemodified');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timecreated');
    /// Launch add field timemodified
        $result = $result && add_field($table, $field);

    /// Define index course (not unique) to be added to mplayer
        $table = new XMLDBTable('mplayer');
        $index = new XMLDBIndex('course');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('course'));
    /// Launch add index course
        $result = $result && add_index($table, $index);
    }

/// Third example, the next day, 20090128 (with the trailing 00), some inserts were performed, related with the module
    if ($result && $oldversion < 2009012800) {
    /// Add some actions to get them properly displayed in the logs
        $rec = new stdClass;
        $rec->module = 'mplayer';
        $rec->action = 'add';
        $rec->mtable = 'mplayer';
        $rec->filed  = 'name';
    /// Insert the add action in log_display
        $result = insert_record('log_display', $rec);
    /// Now the update action
        $rec->action = 'update';
        $result = insert_record('log_display', $rec);
    /// Now the view action
        $rec->action = 'view';
        $result = insert_record('log_display', $rec);
    }*/
	
	return $result;
}
?>
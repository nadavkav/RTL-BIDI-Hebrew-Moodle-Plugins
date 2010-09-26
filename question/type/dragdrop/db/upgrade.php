<?php  //$Id: upgrade.php,v 1.1 2006/12/12 22:35:29 jmvedrine Exp $

// This file keeps track of upgrades to
// the dragdrop qtype plugin
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

function xmldb_qtype_dragdrop_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one
/// block of code similar to the next one. Please, delete
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

    // Add a field so that question authors can choose  in how many columns
    // the drag and drop media is arranged.
    if ($result && $oldversion < 2008060501) {
        $table = new XMLDBTable('question_dragdrop');
        $field = new XMLDBField('arrangemedia');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'feedbackmissed');
        $result = $result && add_field($table, $field);
    }

    // Add a field so that question authors can choose where to place the media
    // below or right beside the background.
    if ($result && $oldversion < 2008060502) {
        $table = new XMLDBTable('question_dragdrop');
        $field = new XMLDBField('placemedia');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'arrangemedia');
        $result = $result && add_field($table, $field);
    }

    return $result;
}

?>
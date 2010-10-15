<?php  // $Id: upgrade.php,v 1.1 2008/07/24 01:48:12 arborrow Exp $

// This file keeps track of upgrades to 
// the shortanswer qtype plugin
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

function xmldb_qtype_algebra_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

    // Add the field to store the string which is placed in front of the answer
    // box when the question is displayed
    if ($result && $oldversion < 2008061500) {
        $table = new XMLDBTable('question_algebra');
        $field = new XMLDBField('answerprefix');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, 
                              null, null, '', 'allowedfuncs');
        $result = $result && add_field($table, $field);
    }
    return $result;
}

?>

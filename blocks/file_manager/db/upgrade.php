<?php

// This file keeps track of upgrades to 
// the activity_modules block
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

function xmldb_block_file_manager_upgrade($oldversion=0) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

    $result = true;

    if ($result && $oldversion < 2008061700) {

    /// Define field ownertype to be added to fmanager_link
        $table = new XMLDBTable('fmanager_link');
        $field = new XMLDBField('ownertype');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'owner');

    /// Launch add field ownertype
        $result = $result && add_field($table, $field);

    /// Define field ownertype to be added to fmanager_categories
        $table = new XMLDBTable('fmanager_categories');
        $field = new XMLDBField('ownertype');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'owner');

    /// Launch add field ownertype
        $result = $result && add_field($table, $field);

    /// Define field ownertype to be added to fmanager_folders
        $table = new XMLDBTable('fmanager_folders');
        $field = new XMLDBField('ownertype');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'owner');

    /// Launch add field ownertype
        $result = $result && add_field($table, $field);

    /// Define field ownertype to be added to fmanager_shared
        $table = new XMLDBTable('fmanager_shared');
        $field = new XMLDBField('ownertype');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'owner');

    /// Launch add field ownertype
        $result = $result && add_field($table, $field);
    }
    return $result;
}

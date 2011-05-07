<?php  //$Id: upgrade.php,v 1.1 2008/11/06 17:39:11 tedbow Exp $

// This file keeps track of upgrades to 
// the map module
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

function xmldb_map_upgrade($oldversion=0) {



    $result = true;

    if ($result && $oldversion < 2008110601) {

    /// Define field provider to be added to map
        $table = new XMLDBTable('map');
        $field = new XMLDBField('provider');
        $field->setAttributes(XMLDB_TYPE_CHAR, '25', null, null, null, null, null, null, 'timemodified');

    /// Launch add field provider
        $result = $result && add_field($table, $field);
    }

    return $result;
}

?>

<?php  //$Id: upgrade.php,v 1.2.2.4 2009/05/24 14:48:00 ulcc Exp $



// This file keeps track of upgrades to 

// the target module

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



function xmldb_ilptarget_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;
	
	if ($result && $oldversion < 2008052906) {

    /// Define field name to be added to ilptarget_posts
        $table = new XMLDBTable('ilptarget_posts');
        $field = new XMLDBField('name');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'data2');

    /// Launch add field name
        $result = $result && add_field($table, $field);
    }
	
	if ($result && $oldversion < 2008052904) {
    /// Define field courserelated to be added to ilptarget_posts
        $table = new XMLDBTable('ilptarget_posts');
        $field = new XMLDBField('courserelated');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'course');

    /// Launch add field courserelated
        $result = $result && add_field($table, $field);
		
		$field = new XMLDBField('targetcourse');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'courserelated');

    /// Launch add field targetcourse
        $result = $result && add_field($table, $field);
    }
	
	if ($result && $oldversion < 2008052902) {
    /// Define field course to be added to ilptarget_posts
        $table = new XMLDBTable('ilptarget_posts');
        $field = new XMLDBField('course');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'setbyuserid');

    /// Launch add field course
        $result = $result && add_field($table, $field);
    }

    return $result;

}



?>


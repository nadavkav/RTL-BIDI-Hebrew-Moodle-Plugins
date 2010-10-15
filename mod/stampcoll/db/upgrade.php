<?php  //$Id: upgrade.php,v 1.3 2008/02/20 23:58:36 mudrd8mz Exp $

// This file keeps track of upgrades to 
// the stampcoll module
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

function xmldb_stampcoll_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($result && $oldversion < 2008021900) { 

    /// CONTRIB-288 Drop field "publish" from the table "stampcoll" and controll the access by capabilities
        if ($collections = get_records('stampcoll', 'publish', '0')) {
            // collections with publish set to STAMPCOLL_PUBLISH_NONE - prevent displaying from legacy:students
            foreach ($collections as $collection) {
                if ($cm = get_coursemodule_from_instance('stampcoll', $collection->id)) {
                    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
                    // find all roles with legacy:student
                    if ($studentroles = get_roles_with_capability('moodle/legacy:student', CAP_ALLOW)) {
                        foreach ($studentroles as $studentrole) {
                            // prevent students from viewing own stamps 
                            assign_capability('mod/stampcoll:viewownstamps', CAP_PREVENT, $studentrole->id, $context->id);
                        }
                    }
                }
            }
        }
        if ($collections = get_records('stampcoll', 'publish', '2')) {
            // collections with publish set to STAMPCOLL_PUBLISH_ALL - allow legacy:students to view others' stamps
            foreach ($collections as $collection) {
                if ($cm = get_coursemodule_from_instance('stampcoll', $collection->id)) {
                    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
                    // find all roles with legacy:student
                    if ($studentroles = get_roles_with_capability('moodle/legacy:student', CAP_ALLOW)) {
                        foreach ($studentroles as $studentrole) {
                            // allow students to view others' stamps 
                            assign_capability('mod/stampcoll:viewotherstamps', CAP_ALLOW, $studentrole->id, $context->id);
                        }
                    }
                }
            }
        }
        $table = new XMLDBTable('stampcoll');
        $field = new XMLDBField('publish');
        $result = $result && drop_field($table, $field);

    /// CONTRIB-289 Drop field "teachercancollect" in the table "mdl_stampcoll"
        if ($collections = get_records('stampcoll', 'teachercancollect', '1')) {
            // collections which allow teachers to collect stamps
            foreach ($collections as $collection) {
                if ($cm = get_coursemodule_from_instance('stampcoll', $collection->id)) {
                    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
                    // find all roles with legacy:teacher and legacy:editingteacher
                    // and allow them to collect stamps 
                    if ($teacherroles = get_roles_with_capability('moodle/legacy:teacher', CAP_ALLOW)) {
                        foreach ($teacherroles as $teacherrole) {
                            assign_capability('mod/stampcoll:collectstamps', CAP_ALLOW, $teacherrole->id, $context->id);
                        }
                    }
                    if ($teacherroles = get_roles_with_capability('moodle/legacy:editingteacher', CAP_ALLOW)) {
                        foreach ($teacherroles as $teacherrole) {
                            assign_capability('mod/stampcoll:collectstamps', CAP_ALLOW, $teacherrole->id, $context->id);
                        }
                    }
                }
            }
        }
        $table = new XMLDBTable('stampcoll');
        $field = new XMLDBField('teachercancollect');
        $result = $result && drop_field($table, $field);
    }


    if ($result && $oldversion < 2008022002) {

    /// Define field anonymous to be added to stampcoll
        $table = new XMLDBTable('stampcoll');
        $field = new XMLDBField('anonymous');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'displayzero');
        $result = $result && add_field($table, $field);

    /// Rename field comment on table stampcoll_stamps to text
        $table = new XMLDBTable('stampcoll_stamps');
        $field = new XMLDBField('comment');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'userid');
        $result = $result && rename_field($table, $field, 'text');

    /// Define field giver to be added to stampcoll_stamps
        $table = new XMLDBTable('stampcoll_stamps');
        $field = new XMLDBField('giver');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'userid');
        $result = $result && add_field($table, $field);

    /// Define key mdl_stampcoll_id_idx (unique) to be dropped form stampcoll
        $table = new XMLDBTable('stampcoll');
        $key = new XMLDBKey('mdl_stampcoll_id_idx');
        $key->setAttributes(XMLDB_KEY_UNIQUE, array('id'));
        $result = $result && drop_key($table, $key);

    /// Define index mdl_stampcoll_course_idx (not unique) to be dropped form stampcoll
        $table = new XMLDBTable('stampcoll');
        $index = new XMLDBIndex('mdl_stampcoll_course_idx');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('course'));
        $result = $result && drop_index($table, $index);

    /// Define index course (not unique) to be added to stampcoll
        $table = new XMLDBTable('stampcoll');
        $index = new XMLDBIndex('course');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('course'));
        $result = $result && add_index($table, $index);

    /// Define index mdl_stampcoll_stamps_userid_idx (not unique) to be dropped form stampcoll_stamps
        $table = new XMLDBTable('stampcoll_stamps');
        $index = new XMLDBIndex('mdl_stampcoll_stamps_userid_idx');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $result = $result && drop_index($table, $index);

    /// Define index mdl_stampcoll_stamps_stampcollid_idx (not unique) to be dropped form stampcoll_stamps
        $table = new XMLDBTable('stampcoll_stamps');
        $index = new XMLDBIndex('mdl_stampcoll_stamps_stampcollid_idx');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('stampcollid'));
        $result = $result && drop_index($table, $index);

    /// Define index userid (not unique) to be added to stampcoll_stamps
        $table = new XMLDBTable('stampcoll_stamps');
        $index = new XMLDBIndex('userid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));

    /// Launch add index userid
        $result = $result && add_index($table, $index);

    /// Define index giver (not unique) to be added to stampcoll_stamps
        $table = new XMLDBTable('stampcoll_stamps');
        $index = new XMLDBIndex('giver');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('giver'));

    /// Launch add index giver
        $result = $result && add_index($table, $index);    

    /// Define key mdl_stampcoll_stamps_id_idx (unique) to be dropped form stampcoll_stamps
        $table = new XMLDBTable('stampcoll_stamps');
        $key = new XMLDBKey('mdl_stampcoll_stamps_id_idx');
        $key->setAttributes(XMLDB_KEY_UNIQUE, array('id'));

    /// Launch drop key mdl_stampcoll_stamps_id_idx
        $result = $result && drop_key($table, $key);

    /// Define key stampcollid (foreign) to be added to stampcoll_stamps
        $table = new XMLDBTable('stampcoll_stamps');
        $key = new XMLDBKey('stampcollid');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('stampcollid'), 'stampcoll', array('id'));

    /// Launch add key stampcollid
        $result = $result && add_key($table, $key);


    }

    return $result;
}

?>

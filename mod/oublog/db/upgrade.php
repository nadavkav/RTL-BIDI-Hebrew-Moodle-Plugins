<?php

// This file keeps track of upgrades to
// the newmodule module
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

function xmldb_oublog_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($result && $oldversion < 2008022600) {

    /// Define field views to be added to oublog_instances
        $table = new XMLDBTable('oublog_instances');
        $field = new XMLDBField('views');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'accesstoken');

    /// Launch add field views
        $result = $result && add_field($table, $field);

        $table = new XMLDBTable('oublog');
        $field = new XMLDBField('views');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'global');

    /// Launch add field views
        $result = $result && add_field($table, $field);

    }

    if ($result && $oldversion < 2008022700) {

    /// Define field oublogid to be added to oublog_links
        $table = new XMLDBTable('oublog_links');
        $field = new XMLDBField('oublogid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'id');

    /// Launch add field oublogid
        $result = $result && add_field($table, $field);

    /// Define key oublog_links_oublog_fk (foreign) to be added to oublog_links
        $table = new XMLDBTable('oublog_links');
        $key = new XMLDBKey('oublog_links_oublog_fk');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('oublogid'), 'oublog', array('id'));

    /// Launch add key oublog_links_oublog_fk
        $result = $result && add_key($table, $key);

    /// Changing nullability of field oubloginstancesid on table oublog_links to null
        $table = new XMLDBTable('oublog_links');
        $field = new XMLDBField('oubloginstancesid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'oublogid');

    /// Launch change of nullability for field oubloginstancesid
        $result = $result && change_field_notnull($table, $field);
    }

    if ($result && $oldversion < 2008022701) {

    /// Define field sortorder to be added to oublog_links
        $table = new XMLDBTable('oublog_links');
        $field = new XMLDBField('sortorder');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'url');

    /// Launch add field sortorder
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2008030704) {
    /// Add search data
        require_once(dirname(__FILE__).'/../locallib.php');
        require_once(dirname(__FILE__).'/../lib.php');
        if(oublog_search_installed()) {
            global $db;
            $olddebug=$db->debug;
            $db->debug=false;
            print '<ul>';
            oublog_ousearch_update_all(true);
            print '</ul>';
            $db->debug=$olddebug;
        }
    }
    
    if ($result && $oldversion < 2008030707) {

    /// Define field lasteditedby to be added to oublog_posts
        $table = new XMLDBTable('oublog_posts');
        $field = new XMLDBField('lasteditedby');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'visibility');

    /// Launch add field lasteditedby
        $result = $result && add_field($table, $field);
        
    /// Transfer edit data to lasteditedby
        $result = $result && execute_sql("
UPDATE {$CFG->prefix}oublog_posts SET lasteditedby=(
    SELECT userid FROM {$CFG->prefix}oublog_edits WHERE {$CFG->prefix}oublog_posts.id=postid ORDER BY id DESC LIMIT 1 
) WHERE editsummary IS NOT NULL
        ");
        
    /// Define field editsummary to be dropped from oublog_posts
        $table = new XMLDBTable('oublog_posts');
        $field = new XMLDBField('editsummary');

    /// Launch drop field editsummary
        $result = $result && drop_field($table, $field);
    }    
    
    if ($result && $oldversion < 2008073000) {

    /// Define field completionposts to be added to oublog
        $table = new XMLDBTable('oublog');
        $field = new XMLDBField('completionposts');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '9', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'views');

    /// Launch add field completionposts
        $result = $result && add_field($table, $field);

    /// Define field completioncomments to be added to oublog
        $field = new XMLDBField('completioncomments');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '9', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'completionposts');

    /// Launch add field completioncomments
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2008121100) {
        // remove oublog:view from legacy:user roles
        $roles = get_roles_with_capability('moodle/legacy:user',CAP_ALLOW);
        foreach ($roles as $role) {
            $result = $result && unassign_capability('mod/oublog:view', $role->id);
        }
    }

    if ($result && $oldversion < 2009012600) {
        // Remove oublog:post and oublog:comment from legacy:user roles (if present)
        $roles = get_roles_with_capability('moodle/legacy:user',CAP_ALLOW);
        // Also from default user role if not already included
        if(!array_key_exists($CFG->defaultuserroleid,$roles)) {
            $roles[] = get_record('role', 'id', $CFG->defaultuserroleid);
        }
        
        print '<p><strong>Warning</strong>: The OU blog system capabilities 
            have changed (again) in order to fix bugs and clarify access control.
            The system will automatically remove the capabilities 
            <tt>mod/oublog:view</tt>, <tt>mod/oublog:post</tt>, and
            <tt>mod/oublog:comment</tt> from the following role(s):</p><ul>';
        foreach ($roles as $role) {
            print '<li>'.htmlspecialchars($role->name).'</li>';
            $result = $result && unassign_capability('mod/oublog:view', $role->id);
            $result = $result && unassign_capability('mod/oublog:post', $role->id);
            $result = $result && unassign_capability('mod/oublog:comment', $role->id);
        }
        print '</ul><p>On a default Moodle installation this is the correct 
            behaviour. Personal blog access is now controlled via the 
            <tt>mod/oublog:viewpersonal</tt> and 
            <tt>mod/oublog:contributepersonal</tt>
            capabilities. These capabilities have been added to the 
            authenticated user role and any equivalent roles.</p>
            <p>If in doubt, please examine your role configuration with regard
            to these <tt>mod/oublog</tt> capabilities.</p>';
    }

    if ($result && $oldversion < 2010031200) {

    /// Define field completionposts to be added to oublog
        $table = new XMLDBTable('oublog');
        $field = new XMLDBField('individual');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'completioncomments');

        /// Launch add field completioncomments
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010062400) {

    /// Define table oublog_comments_moderated to be created
        $table = new XMLDBTable('oublog_comments_moderated');

    /// Adding fields to table oublog_comments_moderated
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('postid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('message', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('timeposted', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('authorname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('ipaddress', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('approval', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timeset', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('secretkey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table oublog_comments_moderated
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('postid', XMLDB_KEY_FOREIGN, array('postid'), 'oublog_posts', array('id'));

    /// Adding indexes to table oublog_comments_moderated
        $table->addIndexInfo('ipaddress', XMLDB_INDEX_NOTUNIQUE, array('ipaddress'));

    /// Launch create table for oublog_comments_moderated
        $result = $result && create_table($table);

    /// Changing nullability of field userid on table oublog_comments to null
        $table = new XMLDBTable('oublog_comments');
        $field = new XMLDBField('userid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'postid');

    /// Launch change of nullability for field userid
        $result = $result && change_field_notnull($table, $field);

    /// Define field authorname to be added to oublog_comments
        $field = new XMLDBField('authorname');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null, 'timedeleted');

    /// Launch add field authorname
        $result = $result && add_field($table, $field);

    /// Define field authorip to be added to oublog_comments
        $field = new XMLDBField('authorip');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null, 'authorname');

    /// Launch add field authorip
        $result = $result && add_field($table, $field);

        /// Define field timeapproved to be added to oublog_comments
        $field = new XMLDBField('timeapproved');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'authorip');

    /// Launch add field timeapproved
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010062500) {
        // Change the 'allow comments' value to 2 on global blog, if it is
        // currently set to 1
        $result = $result && set_field('oublog', 'allowcomments', 2,
            'global', 1, 'allowcomments', 1);
    }

    if ($result && $oldversion < 2010063000) {

    /// Define index ipaddress (not unique) to be dropped form oublog_comments_moderated
        $table = new XMLDBTable('oublog_comments_moderated');
        $index = new XMLDBIndex('ipaddress');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('ipaddress'));

    /// Launch drop index authorip
        $result = $result && drop_index($table, $index);

    /// Rename field ipaddress on table oublog_comments_moderated to authorip
        $field = new XMLDBField('ipaddress');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'authorname');

    /// Launch rename field ipaddress 
        $result = $result && rename_field($table, $field, 'authorip');

    /// Define index authorip (not unique) to be added to oublog_comments_moderated
        $table = new XMLDBTable('oublog_comments_moderated');
        $index = new XMLDBIndex('authorip');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('authorip'));

    /// Launch add index authorip
        $result = $result && add_index($table, $index);
    }

    if ($result && $oldversion < 2010070101) {
        // Make cron start working - in some servers I found there was
        // 9999999999 in the lastcron field which caused it never to run; not
        // very helpful
        $result = $result && set_field('modules', 'lastcron', 1, 'name', 'oublog');
    }

    return $result;
}

?>
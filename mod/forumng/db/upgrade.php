<?php

function xmldb_forumng_upgrade($oldversion=0) {
    global $CFG, $THEME, $db;
    require_once($CFG->dirroot.'/mod/forumng/forum.php');

    $result = true;

/// And upgrade begins here. For each one, you'll need one
/// block of code similar to the next one. Please, delete
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }
    if ($result && $oldversion < 2009071703) {
    /// Add search data
        require_once(dirname(__FILE__).'/../lib.php');
        require_once(dirname(__FILE__).'/../forum.php');
        if(forum::search_installed()) {
            global $db;
            $olddebug=$db->debug;
            $db->debug=false;
            print '<ul>';
            forumng_ousearch_update_all(true);
            print '</ul>';
            $db->debug=$olddebug;
        }
    }
    if ($result && $oldversion < 2009102001) {

    /// Define table forumng_drafts to be created
        $table = new XMLDBTable('forumng_drafts');

    /// Adding fields to table forumng_drafts
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('forumid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('groupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('parentpostid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('subject', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->addFieldInfo('message', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('format', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('attachments', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('saved', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('options', XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null);

    /// Adding keys to table forumng_drafts
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->addKeyInfo('forumid', XMLDB_KEY_FOREIGN, array('forumid'), 'forumng', array('id'));
        $table->addKeyInfo('parentpostid', XMLDB_KEY_FOREIGN, array('parentpostid'), 'forumng_posts', array('id'));

    /// Launch create table for forumng_drafts
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2009110401) {

    /// Define field important to be added to forumng_posts
        $table = new XMLDBTable('forumng_posts');
        $field = new XMLDBField('important');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'deleteuserid');

    /// Launch add field important
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2009110600) {

    /// Define field subscribed to be added to forumng_subscriptions
        $table = new XMLDBTable('forumng_subscriptions');
        $field = new XMLDBField('subscribed');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'forumid');

    /// Launch add field subscribed
        $result = $result && add_field($table, $field);

    /// Changing the default of field subscribed on table forumng_subscriptions to drop it
        $table = new XMLDBTable('forumng_subscriptions');
        $field = new XMLDBField('subscribed');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'forumid');

    /// Launch change of default for field subscribed
        $result = $result && change_field_default($table, $field);

    }
    
    if ($result && $oldversion < 2009111001) {

    /// Define field reportingemail to be added to forumng
        $table = new XMLDBTable('forumng');
        $field = new XMLDBField('reportingemail');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null, 'attachmentmaxbytes');

    /// Launch add field reportingemail
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2009111002) {

    /// Define table forumng_flags to be created
        $table = new XMLDBTable('forumng_flags');

    /// Adding fields to table forumng_flags
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('postid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('flagged', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table forumng_flags
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->addKeyInfo('postid', XMLDB_KEY_FOREIGN, array('postid'), 'forumng_posts', array('id'));

    /// Launch create table for forumng_flags
        $result = $result && create_table($table);
    }
    
    if ($result && $oldversion < 2009120200) {
        // For OU version only, rebuild course cache - it is missing some of
        // the completion information
        if (class_exists('ouflags')) {
            rebuild_course_cache(0, true);
        }
    }
    
    if ($result && $oldversion < 2010020200) {
    /// Define field discussionid to be added to forumng_subscriptions
        $table = new XMLDBTable('forumng_subscriptions');
        $field = new XMLDBField('discussionid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'subscribed');

    /// Launch add field discussionid
        $result = $result && add_field($table, $field);
    }
 
    if ($result && $oldversion < 2010051300) {

    /// Define field removeafter to be added to forumng
        $table = new XMLDBTable('forumng');
        $field = new XMLDBField('removeafter');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'completionposts');

    /// Launch add field removeafter
        $result = $result && add_field($table, $field);

        $field = new XMLDBField('removeto');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'removeafter');

    /// Launch add field removeto
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010071900) {

    /// Define field shared to be added to forumng
        $table = new XMLDBTable('forumng');
        $field = new XMLDBField('shared');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'removeto');

    /// Launch add field shared
        $result = $result && add_field($table, $field);

    /// Define field originalcmid to be added to forumng
        $field = new XMLDBField('originalcmid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'shared');

    /// Launch add field originalcmid
        $result = $result && add_field($table, $field);

    /// Define key originalcmid (foreign) to be added to forumng
        $key = new XMLDBKey('originalcmid');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('originalcmid'), 'course_modules', array('id'));

    /// Launch add key originalcmid
        $result = $result && add_key($table, $key);
    }

    if ($result && $oldversion < 2010072100) {

    /// Define field clonecmid to be added to forumng_subscriptions
        $table = new XMLDBTable('forumng_subscriptions');
        $field = new XMLDBField('clonecmid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'discussionid');

    /// Launch add field clonecmid
        $result = $result && add_field($table, $field);

    /// Define key clonecmid (foreign) to be added to forumng_subscriptions
        $key = new XMLDBKey('clonecmid');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('clonecmid'), 'course_modules', array('id'));

    /// Launch add key clonecmid
        $result = $result && add_key($table, $key);
    }
    
    if ($result && $oldversion < 2010073000) {

    /// Define field groupid to be added to forumng_subscriptions
        $table = new XMLDBTable('forumng_subscriptions');
        $field = new XMLDBField('groupid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'clonecmid');

    /// Launch add field groupid
        $result = $result && add_field($table, $field);
    }
    if ($result && $oldversion < 2010073001) {
        $db->debug = false;
        forum::group_subscription_update(true);
        $db->debug = true;
    }
    return $result;
}

?>

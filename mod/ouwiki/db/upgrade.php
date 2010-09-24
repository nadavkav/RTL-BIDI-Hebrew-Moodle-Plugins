<?php
require_once(dirname(__FILE__).'/../ouwiki.php');

function xmldb_ouwiki_upgrade($oldversion=0) {

    global $CFG, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

    if($result && $oldversion < 2007022300) {
        error('Cannot upgrade OU wiki module - please delete it by going to <a href="modules.php">the modules section</a> then delete it. (Module is called Wiki.) Then let it install itself again.'); 
    }

    if($result && $oldversion < 2007041006) {
        // First fix some signed-ness, then upgrade database to support comment system
        $tw=new transaction_wrapper();

    /// Changing sign of field id on table ouwiki_subwikis to unsigned
        $table = new XMLDBTable('ouwiki_subwikis');
        $field = new XMLDBField('id');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null, null);

    /// Launch change of sign for field id
        $result = $result && change_field_unsigned($table, $field);        

    /// Changing sign of field id on table ouwiki_pages to unsigned
        $table = new XMLDBTable('ouwiki_pages');
        $field = new XMLDBField('id');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null, null);

    /// Launch change of sign for field id
        $result = $result && change_field_unsigned($table, $field);        

    /// Define key ouwiki_pages_fk_forumid (foreign) to be dropped form ouwiki_pages
        $table = new XMLDBTable('ouwiki_pages');
        $key = new XMLDBKey('ouwiki_pages_fk_forumid');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('forumid'), 'forum', array('id'));

    /// Launch drop key ouwiki_pages_fk_forumid
        $result = $result && drop_key($table, $key);
        
    /// Define field forumid to be dropped from ouwiki_pages
        $table = new XMLDBTable('ouwiki_pages');
        $field = new XMLDBField('forumid');

    /// Launch drop field forumid
        $result = $result && drop_field($table, $field);
        
    /// Changing sign of field id on table ouwiki_versions to unsigned
        $table = new XMLDBTable('ouwiki_versions');
        $field = new XMLDBField('id');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null, null);

    /// Launch change of sign for field id
        $result = $result && change_field_unsigned($table, $field);
        
    /// Changing sign of field id on table ouwiki_links to unsigned
        $table = new XMLDBTable('ouwiki_links');
        $field = new XMLDBField('id');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null, null);

    /// Launch change of sign for field id
        $result = $result && change_field_unsigned($table, $field);
        
    /// Changing sign of field id on table ouwiki_locks to unsigned
        $table = new XMLDBTable('ouwiki_locks');
        $field = new XMLDBField('id');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null, null);

    /// Launch change of sign for field id
        $result = $result && change_field_unsigned($table, $field);
        
        // Due to a bug or issues with xmldb/postgres - MDL-9271 - it lost all the primary keys!
        // If this part of the upgrade fails because the primary keys are still there then that's
        // ok.
        /* XMLDB doesn't let you add primary keys! MDL-9272. Otherwise the code would be as follows:
        $table = new XMLDBTable('ouwiki_subwikis');
        $key = new XMLDBKey('primary');
        $key->setAttributes(XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && add_key($table, $key);
        $table = new XMLDBTable('ouwiki_pages');
        $key = new XMLDBKey('primary');
        $key->setAttributes(XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && add_key($table, $key);
        $table = new XMLDBTable('ouwiki_versions');
        $key = new XMLDBKey('primary');
        $key->setAttributes(XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && add_key($table, $key);
        $table = new XMLDBTable('ouwiki_links');
        $key = new XMLDBKey('primary');
        $key->setAttributes(XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && add_key($table, $key);
        $table = new XMLDBTable('ouwiki_locks');
        $key = new XMLDBKey('primary');
        $key->setAttributes(XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && add_key($table, $key);
        */
        
        // This is Postgres-only
        $result &= execute_sql("ALTER TABLE {$CFG->prefix}ouwiki_subwikis ADD CONSTRAINT {$CFG->prefix}ouwisubw_id_pk PRIMARY KEY(id)");
        $result &= execute_sql("ALTER TABLE {$CFG->prefix}ouwiki_pages ADD CONSTRAINT {$CFG->prefix}ouwipage_id_pk PRIMARY KEY(id)");
        $result &= execute_sql("ALTER TABLE {$CFG->prefix}ouwiki_versions ADD CONSTRAINT {$CFG->prefix}ouwivers_id_pk PRIMARY KEY(id)");
        $result &= execute_sql("ALTER TABLE {$CFG->prefix}ouwiki_links ADD CONSTRAINT {$CFG->prefix}ouwilink_id_pk PRIMARY KEY(id)");
        $result &= execute_sql("ALTER TABLE {$CFG->prefix}ouwiki_locks ADD CONSTRAINT {$CFG->prefix}ouwilock_id_pk PRIMARY KEY(id)");
        

    /// Define table ouwiki_sections to be created
        $table = new XMLDBTable('ouwiki_sections');

    /// Adding fields to table ouwiki_sections
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('pageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('xhtmlid', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->addFieldInfo('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table ouwiki_sections
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('ouwiki_sections_fk_pageid', XMLDB_KEY_FOREIGN, array('pageid'), 'ouwiki_pages', array('id'));

    /// Launch create table for ouwiki_sections
        $result = $result && create_table($table);        
        
        
    /// Define table ouwiki_comments to be created
        $table = new XMLDBTable('ouwiki_comments');

    /// Adding fields to table ouwiki_comments
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('pagesectionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('title', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->addFieldInfo('xhtml', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('timeposted', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('deleted', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table ouwiki_comments
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('oucontent_comments_pagesectionid_fk', XMLDB_KEY_FOREIGN, array('pagesectionid'), 'ouwiki_sections', array('id'));
        $table->addKeyInfo('oucontent_comments_userid_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

    /// Launch create table for ouwiki_comments
        $result = $result && create_table($table);
        
        if($result) {
            $tw->commit();
        } else {
            $tw->rollback();
        }
    }    
    if ($result && $oldversion < 2007041007) {

    /// Changing nullability of field title on table ouwiki_sections to not null
        $table = new XMLDBTable('ouwiki_sections');
        $field = new XMLDBField('title');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null, 'xhtmlid');

    /// Launch change of nullability for field title
        $result = $result && change_field_notnull($table, $field);
    }
    if($result && $oldversion < 2007041103) {
        $result&=ouwiki_argh_fix_default('subwikis');
        $result&=ouwiki_argh_fix_default('pages');
        $result&=ouwiki_argh_fix_default('versions');
        $result&=ouwiki_argh_fix_default('links');
        $result&=ouwiki_argh_fix_default('locks');
    }
    if ($result && $oldversion < 2007041700) {
    /// Define key oucontent_comments_sectionid_fk (foreign) to be dropped form ouwiki_comments
        $table = new XMLDBTable('ouwiki_comments');
        $key = new XMLDBKey('oucontent_comments_pagesectionid_fk');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('pagesectionid'), 'ouwiki_sections', array('id'));

    /// Launch drop key oucontent_comments_pagesectionid_fk
        $result = $result && drop_key($table, $key);

    /// Rename field pagesectionid on table ouwiki_comments to sectionid
        $table = new XMLDBTable('ouwiki_comments');
        $field = new XMLDBField('pagesectionid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'id');

    /// Launch rename field pagesectionid
        $result = $result && rename_field($table, $field, 'sectionid');

    /// Define key ouwiki_comments_sectionid_fk (foreign) to be added to ouwiki_comments
        $table = new XMLDBTable('ouwiki_comments');
        $key = new XMLDBKey('ouwiki_comments_sectionid_fk');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('sectionid'), 'ouwiki_sections', array('id'));

    /// Launch add key oucontent_comments_sectionid_fk
        $result = $result && add_key($table, $key);    }
    if ($result && $oldversion == 2007041700) {
    /// Define key oucontent_comments_sectionid_fk (foreign) to be dropped form ouwiki_comments
        $table = new XMLDBTable('ouwiki_comments');
        $key = new XMLDBKey('oucontent_comments_pagesectionid_fk');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('pagesectionid'), 'ouwiki_sections', array('id'));

    /// Launch drop key oucontent_comments_pagesectionid_fk
        $result = $result && drop_key($table, $key);

    /// Define key ouwiki_comments_sectionid_fk (foreign) to be added to ouwiki_comments
        $table = new XMLDBTable('ouwiki_comments');
        $key = new XMLDBKey('ouwiki_comments_sectionid_fk');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('sectionid'), 'ouwiki_sections', array('id'));

    /// Launch add key oucontent_comments_sectionid_fk
        $result = $result && add_key($table, $key);    
    }
    if ($result && $oldversion < 2007041701) {

    /// Define key oucontent_comments_userid_fk (foreign) to be dropped form ouwiki_comments
        $table = new XMLDBTable('ouwiki_comments');
        $key = new XMLDBKey('oucontent_comments_userid_fk');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

    /// Launch drop key oucontent_comments_userid_fk
        $result = $result && drop_key($table, $key);   
        
    /// Define key ouwiki_comments_userid_fk (foreign) to be added to ouwiki_comments
        $table = new XMLDBTable('ouwiki_comments');
        $key = new XMLDBKey('ouwiki_comments_userid_fk');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

    /// Launch add key ouwiki_comments_userid_fk
        $result = $result && add_key($table, $key);
    }
    
    if ($result && $oldversion < 2007102900) {

    /// Define field magic to be added to ouwiki_subwikis
        $table = new XMLDBTable('ouwiki_subwikis');
        $field = new XMLDBField('magic');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '16', XMLDB_UNSIGNED, null, null, null, null, null, 'userid');

    /// Launch add field magic
        $result = $result && add_field($table, $field);
        
    /// Set up all existing field values
        $rs = get_recordset('ouwiki_subwikis','','','','id');
        while($rec=rs_fetch_next_record($rs)) {
            $magicnumber=ouwiki_generate_magic_number();            
            set_field('ouwiki_subwikis','magic',$magicnumber,'id',$rec->id);
        }
        rs_close($rs);    
        
    /// Changing nullability of field magic on table ouwiki_subwikis to not null
        $field->setAttributes(XMLDB_TYPE_INTEGER, '16', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'userid');

    /// Launch change of nullability for field magic
        $result = $result && change_field_notnull($table, $field);        
    }    
    
    if ($result && $oldversion < 2008073000) {

    /// Define field completionpages to be added to ouwiki
        $table = new XMLDBTable('ouwiki');
        $field = new XMLDBField('completionpages');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '9', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'editend');

    /// Launch add field completionpages
        $result = $result && add_field($table, $field);
    
    /// Define field completionedits to be added to ouwiki
        $field = new XMLDBField('completionedits');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '9', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'completionpages');

    /// Launch add field completionedits
        $result = $result && add_field($table, $field);
    }    

    if ($result && $oldversion < 2008100600) {

    /// Define field deletedat to be added to ouwiki_versions
        $table = new XMLDBTable('ouwiki_versions');
        $field = new XMLDBField('deletedat');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'changeprevsize');

    /// Launch add field deletedat (provided field does not already exist)
        if (!field_exists($table, $field)) {
            $result = $result && add_field($table, $field);
        }
    }

    if ($result && $oldversion < 2010022300) {
        // Add ouwiki delete page capability to admin role if it exists
        // Check whether necessary
        $name = 'Administrator';
        $shortname = 'admin';
        if (($role = get_record('role', 'name', $name)) ||
            ($role = get_record('role', 'shortname', $shortname))) {
            if ($sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID)) {
    
                // Check role has delete page capability (assign if not)
                $cap = 'mod/ouwiki:deletepage';
                if (!($rcap = get_record('role_capabilities', 'roleid', $role->id,
                                                              'capability', $cap,
                                                              'contextid', $sitecontext->id))) {
                    assign_capability($cap, CAP_ALLOW, $role->id, $sitecontext->id);
                }
            }
        }
    }

    if ($result && $oldversion < 2009120801) {
        $tw=new transaction_wrapper();
        
    /// Define table ouwiki_annotations to be created
        $table = new XMLDBTable('ouwiki_annotations');

    /// Adding fields to table ouwiki_annotations
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('pageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('content', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table ouwiki_annotations
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for ouwiki_annotations - if it does not already exist (extra precaution)
        if(!table_exists($table)) {
            $result = $result && create_table($table);
        }
        
    /// Define field locked to be added to ouwiki_pages
        $table = new XMLDBTable('ouwiki_pages');
        $field = new XMLDBField('locked');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', 'currentversionid');

    /// Launch add field locked - if it does not already exist (extra precaution)
        if(!field_exists($table, $field)) {
            $result = $result && add_field($table, $field);
        }
            
        if($result) {
            $tw->commit();
        } else {
            $tw->rollback();
        }
	}
	
    if ($result && $oldversion < 2010022300) {

    /// Define field commenting to be added to ouwiki
        $table = new XMLDBTable('ouwiki');
        $field = new XMLDBField('commenting');
        $field->setAttributes(XMLDB_TYPE_CHAR, '20', null, null, null, null, null, 'default', 'completionedits');

    /// Launch add field commenting - if it does not already exist (extra precaution)
        if(!field_exists($table, $field)) {
            $result = $result && add_field($table, $field);
        }
    }

    if ($result && $oldversion < 2010042201) {

    /// Define key subwikiid (foreign) to be added to ouwiki_pages
        $table = new XMLDBTable('ouwiki_pages');
        $key = new XMLDBKey('subwikiid');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('subwikiid'), 'ouwiki_subwikis', array('id'));

    /// Launch add key subwikiid
        if (!index_exists($table, $key)) {
            $result = $result && add_key($table, $key);
        }
    }

    return $result;
}


function ouwiki_argh_fix_default($name) {
    $result=true;
    global $CFG;
    $table=$CFG->prefix.'ouwiki_'.$name;
    $result&=execute_sql("ALTER TABLE {$table}_id_alter_column_tmp_seq RENAME TO {$table}_id_seq");
    $result&=execute_sql("ALTER TABLE $table ALTER COLUMN id SET DEFAULT nextval('{$table}_id_seq')");
    return $result;
}

?>

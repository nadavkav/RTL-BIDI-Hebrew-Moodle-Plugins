<?php
/**
 * Format Upgrade Path
 *
 * @version $Id: upgrade.php,v 1.1 2009/12/21 01:00:29 michaelpenne Exp $
 * @package format_page
 **/

function xmldb_format_page_upgrade($oldversion=0) {
    global $CFG, $db;

    include_once($CFG->dirroot.'/course/format/page/lib.php');

    $result = true;
    if ($result && $oldversion < 2007041202) {

        /// Define field id to be added to block_course_menu
        $table = new XMLDBTable('format_page');
        
        /// Add field showbuttons
        $field = new XMLDBField('showbuttons');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null, null, 0, 'template');
        $result = $result && add_field($table, $field);
    }
    
    if ($result && $oldversion < 2007042500) {
        // update showbuttons settings to allow for indedependent bitwise previous & next
        if(defined('BUTTON_BOTH')) {
            $result = set_field('format_page', 'showbuttons', BUTTON_BOTH, 'showbuttons', 1);
        }
        else {
            $result = false;
            notify('BUTTON_BOTH constant not set', 'notifyfailure');
        }
    }
    
    if ($result && $oldversion < 2007042503) {
         /// Define index index (not unique) to be added to format_page
        $table = new XMLDBTable('format_page');
        $index = new XMLDBIndex('parentpageindex');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('parent'));
        
        if (!index_exists($table, $index)) {
            $result = $result && add_index($table, $index);
        }

        $index = new XMLDBIndex('sortorderpageindex');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('sortorder'));
        
        if (!index_exists($table, $index)) {
            $result = $result && add_index($table, $index);
        }

        // now add indexes for format_page_items tables
        $table = new XMLDBTable('format_page_items');
        $index = new XMLDBIndex('format_page_items_sortorder_index');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('sortorder'));

        if (!index_exists($table, $index)) {
            $result = $result && add_index($table, $index);
        }
        
        $index = new XMLDBIndex('format_page_items_pageid_index');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('pageid'));

        if (!index_exists($table, $index)) {
            $result = $result && add_index($table, $index);
        }
    }
    
    if ($result && $oldversion < 2007071800) {
        $validcourses = get_records_menu('course', '', '', '', 'id, shortname');
        if (!empty($validcourses)) {
            $keys = array_keys($validcourses);
            
            $invalidpages = get_records_select_menu('format_page', 'courseid NOT IN('.implode(', ', $keys).')','','id, nameone');
            if (!empty($invalidpages)) {
                $pagekeys = array_keys($invalidpages);
                delete_records_select('format_page_items', 'pageid IN ('.implode(', ', $pagekeys).')');
                delete_records_select('format_page', 'id IN ('.implode(', ', $pagekeys).')');
            }
        }
        else {
            delete_records('format_page');
            delete_records('format_page_items');
        }
    }

    if ($result && $oldversion < 2007071801) {

    /// Define field width to be dropped from format_page_items
        $table = new XMLDBTable('format_page_items');
        $field = new XMLDBField('width');

    /// Launch drop field width
        $result = $result && drop_field($table, $field);
    }

    if ($result && $oldversion < 2007071802) {
    /// Changing logic for sortorder field to more closely resemble block weight

        // This could be huge, do not output everything
        $olddebug  = $db->debug;
        $db->debug = false;

        // Setup some values
        $result = true;
        $i      = 0;

        if ($rs = get_recordset('format_page', '1', '1', '', 'id')) {
            if ($rs->RecordCount() > 0) {
                echo 'Processing page item sortorder field....';

                while ($page = rs_fetch_next_record($rs)) {
                    if ($pageitems = get_records('format_page_items', 'pageid', $page->id, 'sortorder', 'id, position')) {
                        // Organize by position
                        $organized = array('l' => array(), 'c' => array(), 'r' => array());
                        foreach ($pageitems as $pageitem) {
                            $organized[$pageitem->position][] = $pageitem->id;
                        }
                        // Now - reset sortorder value
                        foreach ($organized as $position => $pageitemids) {
                            $sortorder = 0;
                            foreach ($pageitemids as $pageitemid) {
                                $result = $result and set_field('format_page_items', 'sortorder', $sortorder, 'id', $pageitemid);
                                $sortorder++;
                            }
                        }
                    }
                    if ($i % 50 == 0) {
                        echo '.';
                        flush();
                    }
                    $i++;
                }
                if ($result) {
                    notify('SUCCESSFULLY fixed page item sort order field', 'notifysuccess');
                } else {
                    notify('FAILED!  An error occured during upgrade');
                }
            }
            rs_close($rs);
        }
        // Restore
        $db->debug = $olddebug;
    }

    if ($result && $oldversion < 2007071803) {
        // This could be huge, do not output everything
        $olddebug  = $db->debug;
        $db->debug = false;

        $result = true;

        // Make sure all block weights are set properly (before this was never really managed properly)
        if ($courses = get_records('course', 'format', 'page', '', 'id')) {
            echo 'Fixing block weights in courses with format = \'page\'....';

            $i = 0;
            foreach ($courses as $course) {
                page_fix_block_weights($course->id);
                if ($i % 5 == 0) {
                    echo '.';
                    flush();
                }
                $i++;
            }
        }
        // Restore
        $db->debug = $olddebug;
    }

    if ($result && $oldversion < 2007071804) {

    /// Changing the default of field sortorder on table format_page_items to 0
        $table = new XMLDBTable('format_page_items');
        $field = new XMLDBField('sortorder');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'position');

    /// Launch change of default for field sortorder
        $result = $result && change_field_default($table, $field);
    }

    if ($result && $oldversion < 2007071805) {
        // This could be huge, do not output everything
        $olddebug  = $db->debug;
        $db->debug = false;
        $result    = true;

        // Make sure all page sortorder values are set properly (before this was never really managed properly)
        if ($courses = get_records('course', 'format', 'page', '', 'id')) {
            echo 'Fixing page sort orders in courses with format = \'page\'....';

            $i = 0;
            foreach ($courses as $course) {
                page_fix_page_sortorder($course->id);
                if ($i % 5 == 0) {
                    echo '.';
                    flush();
                }
                $i++;
            }
        }
        // Restore
        $db->debug = $olddebug;
    }

    if ($result && $oldversion < 2007071806) {
        // Remove old setting
        if (record_exists('config', 'name', 'pageformatusedefault')) {
            unset_config('pageformatusedefault');
        }
    }

    if ($result && $oldversion < 2007071807) {
        $site = get_site();

        if ($site->format == 'page') {
            $result = ($result and set_field('course', 'format', 'site', 'id', $site->id));
            $result = ($result and set_config('pageformatonfrontpage', 1));
        }
    }

    if ($result && $oldversion < 2008082100) {
        $site = get_site();

        if ($CFG->pageformatonfrontpage == 1) {
            // Turns out having this set is very important - EG: backup/restore
            $result = set_field('course', 'format', 'page', 'id', $site->id);
        }
    }

    if ($result && $oldversion < 2008121000) {

    /// Define field locks to be added to format_page
        $table = new XMLDBTable('format_page');
        $field = new XMLDBField('locks');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null, 'showbuttons');

    /// Launch add field locks
        $result = $result && add_field($table, $field);
    }
    
    
   if ($result && $oldversion < 2009060200) { //MR-263 column widths to strings to allow for px, % and em etc.
        $table = new XMLDBTable('format_page');
        
        $field = new XMLDBField('prefleftwidth');
        $field->setType(XMLDB_TYPE_CHAR);
        $result = $result && change_field_type($table, $field);
 
        $field = new XMLDBField('prefcenterwidth');
        $field->setType(XMLDB_TYPE_CHAR);
        $result = $result && change_field_type($table, $field);
        
        $field = new XMLDBField('prefrightwidth');
        $field->setType(XMLDB_TYPE_CHAR);
        $result = $result && change_field_type($table, $field);
              
        // XMLDB_TYPE_CHAR isn't the same as varchar???
        //$alter = "ALTER TABLE {$CFG->prefix}format_page CHANGE prefleftwidth prefleftwidth varchar(8)";
    }
    

    return $result;
}

?>
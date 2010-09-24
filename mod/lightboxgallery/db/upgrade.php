<?php

function xmldb_lightboxgallery_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($result && $oldversion < 2007111400) {
        // Insert perpage and comments fields into lightboxgallery

        $table = new XMLDBTable('lightboxgallery');

        $field = new XMLDBField('perpage');
        if (!field_exists($table, $field)) {
            $field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'description');
            $result = $result && add_field($table, $field);
        }

        $field = new XMLDBField('comments');
        if (!field_exists($table, $field)) {
            $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'perpage');
            $result = $result && add_field($table, $field);
        }

        // Create new lightboxgallery_comments table

        $table = new XMLDBTable('lightboxgallery_comments');

        if (!table_exists($table)) {
            $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
            $table->addFieldInfo('gallery', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
            $table->addFieldInfo('user', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
            $table->addFieldInfo('comment', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

            $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->addIndexInfo('gallery', XMLDB_INDEX_NOTUNIQUE, array('gallery'));

            $result = $result && create_table($table);
        }

        // Insert add_to_log entry to log_display table

        if (!record_exists('log_display', 'module', 'lightboxgallery', 'action', 'comment')) {
            $record = new object;
            $record->module = 'lightboxgallery';
            $record->action = 'comment';
            $record->mtable = 'lightboxgallery';
            $record->field  = 'name';

            $result = $result && insert_record('log_display', $record);
        }

    }

    if ($result && $oldversion < 2007121700) {
        // Insert extinfo field into lightboxgallery

        $table = new XMLDBTable('lightboxgallery');

        $field = new XMLDBField('extinfo');
        if (!field_exists($table, $field)) {
            $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'comments');
            $result = $result && add_field($table, $field);
        }

        // Create lightboxgallery_captions table

        $table = new XMLDBTable('lightboxgallery_captions');

        if (!table_exists($table)) {
            $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
            $table->addFieldInfo('gallery', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
            $table->addFieldInfo('image', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('caption', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);

            $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->addIndexInfo('gallery', XMLDB_INDEX_NOTUNIQUE, array('gallery'));

            $result = $result && create_table($table);
        }

    }

    if ($result && $oldversion < 2008110600) {
        // Insert public, rss, autoresize, resize fields into lightboxgallery

        $table = new XMLDBTable('lightboxgallery');

        $newfields = array('public', 'rss', 'autoresize', 'resize');
        $previousfield = 'comments';
        foreach ($newfields as $newfield) {
            $field = new XMLDBField($newfield);
            if (!field_exists($table, $field)) {
                $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', $previousfield);
                $result = $result && add_field($table, $field);
                $previousfield = $newfield;
            }
        }

        // Rename user field to userid in lightboxgallery_comments(for postgres)

        $table = new XMLDBTable('lightboxgallery_comments');

        $field = new XMLDBField('user');
        if (field_exists($table, $field)) {
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
            $result = $result && rename_field($table, $field, 'userid');
        }

        // Rename caption field to description and insert metatype field in lightboxgallery_captions

        $table = new XMLDBTable('lightboxgallery_captions');

        $field = new XMLDBField('caption');
        if (field_exists($table, $field)) {
            $field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null, 'image');
            $result = $result && rename_field($table, $field, 'description');
        }

        $field = new XMLDBField('metatype');
        if (table_exists($table) && !field_exists($table, $field)) {
            $field->setAttributes(XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, XMLDB_ENUM, array('caption', 'tag'), 'caption', 'image');
            $result = $result && add_field($table, $field);
        }

        // Rename table lightboxgallery_captions to lightboxgallery_image_meta

        $result = $result && rename_table($table, 'lightboxgallery_image_meta');
    }

    if ($result && $oldversion < 2010080101) {
        // Insert coursefp fields into lightboxgallery (nadavkav patch)

        $table = new XMLDBTable('lightboxgallery');

        $newfields = array('coursefp');
        $previousfield = 'timemodified';
        foreach ($newfields as $newfield) {
            $field = new XMLDBField($newfield);
            if (!field_exists($table, $field)) {
                $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', $previousfield);
                $result = $result && add_field($table, $field);
                $previousfield = $newfield;
            }
        }

    }

    return $result;
}

?>

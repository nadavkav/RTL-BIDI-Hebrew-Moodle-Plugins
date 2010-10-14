<?php

// This file keeps track of upgrades to
// the email
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

function xmldb_block_email_list_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one
/// block of code similar to the next one. Please, delete
/// this comment lines once this file start handling proper
/// upgrade code.

	if ($result && $oldversion < 2007062205) {
		$fields = array(
						'mod/email:viewmail',
						'mod/email:addmail',
						'mod/email:reply',
						'mod/email:replyall',
						'mod/email:forward',
						'mod/email:addsubfolder',
						'mod/email:updatesubfolder',
						'mod/email:removesubfolder'	);

		/// Remove no more used fields
        $table = new XMLDBTable('capabilities');

        foreach ($fields as $name) {

            $field = new XMLDBField($name);
            $result = $result && drop_field($table, $field);
        }

        // Active cron block of email_list
        if ( $result ) {
        	if ( $email_list = get_record('block', 'name', 'email_list') ) {
        		$email_list->cron = 1;
        		update_record('block',$email_list);
        	}
        }

	}

	// force
	$result = true;

	if ($result && $oldversion < 2007072003) {
		// Add marriedfolder2courses flag on email_preferences
		$table = new XMLDBTable('email_preference');

		$field = new XMLDBField('marriedfolders2courses');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', null);

        $result = $result && add_field($table, $field);


        // Add course ID on email_folder
        $table = new XMLDBTable('email_folder');

		$field = new XMLDBField('course');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', null);

        $result = $result && add_field($table, $field);

		// Add index
        $key = new XMLDBKey('course');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('course'), 'course', array('id'));

        $result = $result && add_key($table, $key);

	}

	if ($result && $oldversion < 2008061400 ) {

		// Add reply and forwarded info field on email_mail.
		$table = new XMLDBTable('email_send');

		$field = new XMLDBField('answered');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', null);

        $result = $result && add_field($table, $field);
	}

	// Solve old problems
	if ($result && $oldversion < 2008061600 ) {
		$table = new XMLDBTable('email_preference');
		$field = new XMLDBField('marriedfolders2courses');

		if ( !field_exists($table, $field) ) {
			$field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', null);

        	$result = $result && add_field($table, $field);
		}

		$table = new XMLDBTable('email_folder');

		$field = new XMLDBField('course');

		if ( !field_exists($table, $field) ) {
	        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', null);

	        $result = $result && add_field($table, $field);

			// Add index
	        $key = new XMLDBKey('course');
	        $key->setAttributes(XMLDB_KEY_FOREIGN, array('course'), 'course', array('id'));

	        $result = $result && add_key($table, $key);
		}

	}

	// Add new index
	if ( $result and $oldversion < 2008081600 ) {
		// Add combine key on foldermail
        $table = new XMLDBTable('email_foldermail');
        $index = new XMLDBIndex('folderid-mailid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('folderid', 'mailid'));

        if (!index_exists($table, $index)) {
        /// Launch add index
            $result = $result && add_index($table, $index);
        }

	}

    return $result;
}

?>
<?php
//$Id: upgrade.php,v 1.6 2009/07/03 13:10:27 fabiangebert Exp $

// This file keeps track of upgrades to
// the studynotes module
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

function xmldb_studynotes_upgrade($oldversion = 0) {

	global $CFG, $THEME, $db;

	$result = true;

	if ($result && $oldversion < 2009032400) {

		error("Version too old to be upgraded. Please delete the module and re-install it.");
	}


	if ($result && $oldversion < 2009041603) {

		/// Define table studynotes_uploads to be created
		$table = new XMLDBTable('studynotes_uploads');

		/// Adding fields to table studynotes_uploads
		$table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
		$table->addFieldInfo('type', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('user_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
		$table->addFieldInfo('topic_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
		$table->addFieldInfo('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
		$table->addFieldInfo('created', XMLDB_TYPE_DATETIME, null, null, XMLDB_NOTNULL, null, null, null, '0');
		$table->addFieldInfo('modified', XMLDB_TYPE_DATETIME, null, null, XMLDB_NOTNULL, null, null, null, '0');

		/// Adding keys to table studynotes_uploads
		$table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array ('id'));

		/// Launch create table for studynotes_uploads
		$result = $result && create_table($table);
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_cards');
		$field = new XMLDBField('user');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);

		/// Launch rename field
		$result = $result && rename_field($table, $field, 'user_id');
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_cards');
		$field = new XMLDBField('index');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);

		if($CFG->dbfamily=="mysql"||$CFG->dbfamily=="mysqli") {
			if(field_exists($table, $field)) {
				$query="ALTER TABLE {$CFG->prefix}studynotes_cards CHANGE `index` index_num BIGINT( 11 ) UNSIGNED NOT NULL";
				$db->Execute($query);
			}
		}
		else {
			/// Launch rename field
			$result = $result && rename_field($table, $field, 'index_num');
		}
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_cards');
		$field = new XMLDBField('level');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);

		/// Launch rename field
		$result = $result && rename_field($table, $field, 'level_num');
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_flashcards');
		$field = new XMLDBField('user');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);

		/// Launch rename field
		$result = $result && rename_field($table, $field, 'user_id');
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_flashcards');
		$field = new XMLDBField('number');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);

		/// Launch rename field
		$result = $result && rename_field($table, $field, 'num');
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_flashcards');
		$field = new XMLDBField('level');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);

		/// Launch rename field
		$result = $result && rename_field($table, $field, 'level_num');
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_groups');
		$field = new XMLDBField('access');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);

		/// Launch rename field
		$result = $result && rename_field($table, $field, 'access_num');
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_markers');
		$field = new XMLDBField('user');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);

		/// Launch rename field
		$result = $result && rename_field($table, $field, 'user_id');
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_markers');
		$field = new XMLDBField('range');
		$field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, null);
		if($CFG->dbfamily=="mysql"||$CFG->dbfamily=="mysqli") {
			if(field_exists($table, $field)) {
				$query="ALTER TABLE {$CFG->prefix}studynotes_markers CHANGE `range` range_store TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
				$db->Execute($query);
			}
		}
		else {
			/// Launch rename field
			$result = $result && rename_field($table, $field, 'range_store');
		}
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_memberships');
		$field = new XMLDBField('user');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);

		/// Launch rename field
		$result = $result && rename_field($table, $field, 'user_id');
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_memberships');
		$field = new XMLDBField('group');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);
		if($CFG->dbfamily=="mysql"||$CFG->dbfamily=="mysqli") {
			if(field_exists($table, $field)) {
				$query="ALTER TABLE {$CFG->prefix}studynotes_memberships CHANGE `group` group_id BIGINT( 11 ) UNSIGNED NOT NULL";
				$db->Execute($query);
			}
		}
		else {
			/// Launch rename field
			$result = $result && rename_field($table, $field, 'group_id');
		}
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_memberships');
		$field = new XMLDBField('level');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);

		/// Launch rename field
		$result = $result && rename_field($table, $field, 'level_num');
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_rights');
		$field = new XMLDBField('group');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);
		if($CFG->dbfamily=="mysql"||$CFG->dbfamily=="mysqli") {
			if(field_exists($table, $field)) {
				$query="ALTER TABLE {$CFG->prefix}studynotes_rights CHANGE `group` group_id BIGINT( 11 ) UNSIGNED NOT NULL";
				$db->Execute($query);
			}
		}
		else {
			/// Launch rename field
			$result = $result && rename_field($table, $field, 'group_id');
		}
	}

	/// Rename field
	if ($result && $oldversion < 2009042400) {

		$table = new XMLDBTable('studynotes_topics');
		$field = new XMLDBField('user');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0', null);

		/// Launch rename field
		$result = $result && rename_field($table, $field, 'user_id');
	}

	// Rename tables longer than 24 chars
	if ($result && $oldversion < 2009043001) {

		/// Define table studynotes_feed_msg_stat to be renamed
		$table = new XMLDBTable('studynotes_feed_messages_status');

		/// Launch rename table for studynotes_feed_msg_stat
		$result = $result && rename_table($table, 'studynotes_feed_msg_stat');
	}

	// Rename tables longer than 24 chars
	if ($result && $oldversion < 2009043001) {

		/// Define table studynotes_feed_subscrib to be renamed
		$table = new XMLDBTable('studynotes_feed_subscriptions');

		/// Launch rename table for studynotes_feed_subscrib
		$result = $result && rename_table($table, 'studynotes_feed_subscrib');
	}

	// Rename tables longer than 24 chars
	if ($result && $oldversion < 2009043001) {

		/// Define table studynotes_rel_questions to be renamed
		$table = new XMLDBTable('studynotes_relation_questions');

		/// Launch rename table for studynotes_rel_questions
		$result = $result && rename_table($table, 'studynotes_rel_questions');
	}

	// Rename tables longer than 24 chars
	if ($result && $oldversion < 2009043001) {

		/// Define table studynotes_rel_questions to be renamed
		$table = new XMLDBTable('studynotes_relation_translations');

		/// Launch rename table for studynotes_rel_questions
		$result = $result && rename_table($table, 'studynotes_rel_translations');
	}

	if ($result && $oldversion < 2009050301) {

		$fields = array(
		array('studynotes_cards','created'),
		array('studynotes_cards','modified'),
		array('studynotes_cards','locked_time'),
		array('studynotes_feed_messages','created'),
		array('studynotes_feed_messages','modified'),
		array('studynotes_feed_msg_stat','created'),
		array('studynotes_feed_msg_stat','modified'),
		array('studynotes_feeds','created'),
		array('studynotes_feeds','modified'),
		array('studynotes_groups','created'),
		array('studynotes_groups','modified'),
		array('studynotes_markers','created'),
		array('studynotes_markers','modified'),
		array('studynotes_memberships','created'),
		array('studynotes_memberships','modified'),
		array('studynotes_relations','created'),
		array('studynotes_relations','modified'),
		array('studynotes_relation_answers','created'),
		array('studynotes_relation_answers','modified'),
		array('studynotes_relation_links','created'),
		array('studynotes_relation_links','modified'),
		array('studynotes_rel_questions','created'),
		array('studynotes_rel_questions','modified'),
		array('studynotes_topics','created'),
		array('studynotes_topics','modified'),
		array('studynotes_uploads','created'),
		array('studynotes_uploads','modified'),
		array('studynotes_users','created'),
		array('studynotes_users','last_login')
		);

		foreach($fields as $info) {
			$table = new XMLDBTable($info[0]);
			$tmpField = new XMLDBField($info[1]."_cpy");
			$tmpField->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', $info[1]);
				
			//add new integer field
			$result = $result && add_field($table, $tmpField);
				
			//get value
			if($records = get_records($info[0], '', '', '', 'id,'.$info[1])) {

				//convert value
				foreach($records as $record) {
					$record->{$info[1]."_cpy"} = strtotime($record->{$info[1]});
					unset($record->{$info[1]});
					print_r($record);
					$result = $result && update_record($info[0],$record);
				}
			}
				
			//drop old field
			$field = new XMLDBField($info[1]);
			$result = $result && drop_field($table, $field);

			//rename copy
			$tmpField->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', null);
			$result = $result && change_field_default($table, $tmpField);
			$result = $result && rename_field($table, $tmpField, $info[1]);
		}
	}

	if ($result && $oldversion < 2009050301) {

		/// Define table studynotes_rel_translations to be dropped
		$table = new XMLDBTable('studynotes_rel_translations');

		/// Launch drop table for studynotes_rel_translations
		$result = $result && drop_table($table);
	}

	if ($result && $oldversion < 2009070300) {

		/// Define index link (not unique) to be dropped form studynotes_relation_links
		$table = new XMLDBTable('studynotes_relation_links');
		$index = new XMLDBIndex('link');
		$index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('link'));

		/// Launch drop index link
		$result = $result && drop_index($table, $index);
	}
	
	if ($result && $oldversion < 2009070300) {

		/// Changing type of field link on table studynotes_relation_links to text
		$table = new XMLDBTable('studynotes_relation_links');
		$field = new XMLDBField('link');
		$field->setAttributes(XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null, null, null, 'id');

		/// Launch change of type for field link
		$result = $result && change_field_type($table, $field);
	}
	if ($result && $oldversion < 2009070300) {

		/// Changing type of field answer on table studynotes_relation_answers to text
		$table = new XMLDBTable('studynotes_relation_answers');
		$field = new XMLDBField('answer');
		$field->setAttributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null, 'id');

		/// Launch change of type for field answer
		$result = $result && change_field_type($table, $field);
	}
	if ($result && $oldversion < 2009070300) {

		/// Changing type of field question on table studynotes_rel_questions to text
		$table = new XMLDBTable('studynotes_rel_questions');
		$field = new XMLDBField('question');
		$field->setAttributes(XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null, null, null, 'id');

		/// Launch change of type for field question
		$result = $result && change_field_type($table, $field);
	}

	return $result;
}

?>

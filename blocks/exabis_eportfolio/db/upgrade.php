<?php

function xmldb_block_exabis_eportfolio_upgrade($oldversion=0, $tmp)
{
    global $CFG, $db;

    $result = true;

//     if (empty($db)) {
//         return false;
//     }

	if ($oldversion < 2008090100 && !empty($db) ) {
		// old tables
		$tables = array(
			'block_exabeporpers', 'block_exabeporexte', 'block_exabeporcate',
			'block_exabeporbooklink', 'block_exabeporcommlink', 'block_exabeporsharlink',
			'block_exabeporbookfile', 'block_exabeporcommfile', 'block_exabeporsharfile',
			'block_exabepornote', 'block_exabeporcommnote', 'block_exabeporsharnote'
		);

		$tableNames = array();

		// rename tables to old_*
		foreach ($tables as $table) {
			$tableNames[$table] = 'old_'.$oldversion.'_'.$table;

			$xmltable = new XMLDBTable($table);
			rename_table($xmltable, $tableNames[$table]);
		}

		// add new tables
		install_from_xmldb_file(dirname(__FILE__).'/install.xml');

		// import data from old tables
		$insert_type = 'REPLACE';
		$db->Execute($insert_type.' INTO '.$CFG->prefix.'block_exabeporuser (id, user_id, persinfo_timemodified, description, user_hash)'.
			' SELECT u.id, u.userid, u.timemodified, u.description, e.user_hash FROM '.$CFG->prefix.$tableNames['block_exabeporpers'].' AS u LEFT JOIN '.$CFG->prefix.$tableNames['block_exabeporexte'].' AS e ON u.userid = e.user_id');
		$db->Execute($insert_type.' INTO '.$CFG->prefix.'block_exabeporcate (id, pid, userid, name, timemodified, courseid)'.
			' SELECT id, pid, userid, name, timemodified, course FROM '.$CFG->prefix.$tableNames['block_exabeporcate']);

		$file_id_start = 0;
		$note_id_start = get_field_select($tableNames['block_exabepornote'], 'MAX(id)', null) + 100;
		$link_id_start = get_field_select($tableNames['block_exabeporbooklink'], 'MAX(id)', null) + $note_id_start + 100;

		// combine item table
		$db->Execute($insert_type.' INTO '.$CFG->prefix.'block_exabeporitem'.
			' (id, userid, type, categoryid, name, url, intro, attachment, timemodified, courseid, shareall, externaccess, externcomment)'.
			' SELECT id+'.$file_id_start.', userid, "file", category, name, url, intro, attachment, timemodified, course, shareall, externaccess, externcomment'.
			' FROM '.$CFG->prefix.$tableNames['block_exabeporbookfile']);
		$db->Execute($insert_type.' INTO '.$CFG->prefix.'block_exabeporitem'.
			' (id, userid, type, categoryid, name, url, intro, attachment, timemodified, courseid, shareall, externaccess, externcomment)'.
			' SELECT id+'.$note_id_start.', userid, "note", category, name, url, intro, attachment, timemodified, course, shareall, externaccess, externcomment'.
			' FROM '.$CFG->prefix.$tableNames['block_exabepornote']);
		$db->Execute($insert_type.' INTO '.$CFG->prefix.'block_exabeporitem'.
			' (id, userid, type, categoryid, name, url, intro, attachment, timemodified, courseid, shareall, externaccess, externcomment)'.
			' SELECT id+'.$link_id_start.', userid, "link", category, name, url, intro, attachment, timemodified, course, shareall, externaccess, externcomment'.
			' FROM '.$CFG->prefix.$tableNames['block_exabeporbooklink']);


		// combine comment table
		$db->Execute($insert_type.' INTO '.$CFG->prefix.'block_exabeporitemcomm'.
			' (id, itemid, userid, entry, timemodified)'.
			' SELECT id, bookmarkid+'.$file_id_start.', userid, entry, timemodified'.
			' FROM '.$CFG->prefix.$tableNames['block_exabeporcommfile']);
		$db->Execute($insert_type.' INTO '.$CFG->prefix.'block_exabeporitemcomm'.
			' (id, itemid, userid, entry, timemodified)'.
			' SELECT id, bookmarkid+'.$note_id_start.', userid, entry, timemodified'.
			' FROM '.$CFG->prefix.$tableNames['block_exabeporcommnote']);
		$db->Execute($insert_type.' INTO '.$CFG->prefix.'block_exabeporitemcomm'.
			' (id, itemid, userid, entry, timemodified)'.
			' SELECT id, bookmarkid+'.$link_id_start.', userid, entry, timemodified'.
			' FROM '.$CFG->prefix.$tableNames['block_exabeporcommlink']);


		// combine share table
		$ret = $db->Execute($insert_type.' INTO '.$CFG->prefix.'block_exabeporitemshar'.
			' (id, itemid, userid, original, courseid)'.
			' SELECT id, bookid+'.$file_id_start.', userid, original, course'.
			' FROM '.$CFG->prefix.$tableNames['block_exabeporsharfile']);
		$db->Execute($insert_type.' INTO '.$CFG->prefix.'block_exabeporitemshar'.
			' (id, itemid, userid, original, courseid)'.
			' SELECT id, bookid+'.$note_id_start.', userid, original, course'.
			' FROM '.$CFG->prefix.$tableNames['block_exabeporsharnote']);
		$db->Execute($insert_type.' INTO '.$CFG->prefix.'block_exabeporitemshar'.
			' (id, itemid, userid, original, courseid)'.
			' SELECT id, bookid+'.$link_id_start.', userid, original, course'.
			' FROM '.$CFG->prefix.$tableNames['block_exabeporsharlink']);

		$result = true;

	}

  if ( $result && $oldversion < 2009010104) {

    // Add THEME support (nadavkav)
    //$result = execute_sql("ALTER TABLE `{$CFG->prefix}_block_exabeporview` ADD `theme` TEXT NULL DEFAULT NULL AFTER `description`");
    $table = new XMLDBTable('block_exabeporview');

    /// Adding fields to table block_exabeporview
    //$table->addFieldInfo('theme', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null, null, null);
    $field = new XMLDBField('theme');
    $field->setAttributes(XMLDB_TYPE_CHAR, '25', null, null, null, null, null, null, 'description');

    /// Launch update table for block_exabeporview
    $result = $result && add_field($table, $field);
  }

  if ( $result && $oldversion < 2009010105) {

    // Add THEME support (nadavkav)
    //$result = execute_sql("ALTER TABLE `{$CFG->prefix}_block_exabeporuser` ADD `emailnotification` TEXT NULL DEFAULT NULL AFTER `description`");
    $table = new XMLDBTable('block_exabeporuser');

    /// Adding fields to table block_exabeporview
    //$table->addFieldInfo('theme', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null, null, null);
    $field = new XMLDBField('emailnotification');
    $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'user_hash');

    /// Launch update table for block_exabeporview
    $result = $result && add_field($table, $field);
  }

  return $result;
}

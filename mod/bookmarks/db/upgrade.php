<?php  //$Id: upgrade.php,v 1.7 2007/09/18 22:20:17 stronk7 Exp $


function xmldb_bookmarks_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;
	
	if ($oldversion < 2008062001){
		$table = new XMLDBTable("bookmarks");
		$field = new XMLDBField("intro");
	
		$field->setAttributes(XMLDB_TYPE_TEXT, small, null, true, null, null, null, "", null);
		change_field_default($table, $field);
		change_field_type($table, $field);
		change_field_notnull($table, $field);
	}

    return $result;
}

?>

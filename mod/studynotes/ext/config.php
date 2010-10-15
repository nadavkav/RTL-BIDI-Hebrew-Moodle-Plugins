<?php

MediabirdConfig::$database_table_prefix=$CFG->prefix;

$tableNames = MediabirdConfig::$table_names;

//database adjustments
foreach($tableNames as $key=>$value) {
	$tableNames[$key] = 'studynotes_'.$value;
}

MediabirdConfig::$table_names = $tableNames;

MediabirdConfig::$database_name=null;

?>

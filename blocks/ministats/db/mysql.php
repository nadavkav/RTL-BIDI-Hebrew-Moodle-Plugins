<?php //$Id: mysql.php

function ministats_upgrade($oldversion=0) {

	global $CFG;

	$result = true;

	if ($oldversion < 2007071600 and $result) {
		$result = true; //Nothing to do
	}

	//Finally, return result
	return $result;
}

?>
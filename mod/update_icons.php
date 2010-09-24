<?php

	include("../config.php");
	global $USER;

	$context = get_context_instance(CONTEXT_SYSTEM);
	if ( !has_capability('moodle/legacy:admin', $context, $USER->id, false) ) notify('for Admin users, only! bye...');

	$modfolders = get_directory_list("$CFG->dirroot/mod/", '',false,true,false);
    foreach($modfolders as $folder) {
      $modiconslist[$folder] = $folder."/icon.gif";
    }

	$themefolders = get_directory_list("$CFG->dirroot/theme/", '',false,true,false);

	foreach($themefolders as $theme) {
		//$theme = "aardvark";
		echo "<hr><br/>  >> $theme << <br/>";
		foreach($modiconslist as $folder => $icon) {
			echo "checking.... $CFG->dirroot/theme/$theme/pix/mod/$folder/icon.gif { isFile=".is_file("$CFG->dirroot/theme/$theme/pix/mod/$folder/icon.gif")." }<br/>";
			if ( !is_file("$CFG->dirroot/theme/$theme/pix/mod/$folder/icon.gif") ) {
				mkdir("$CFG->dirroot/theme/$theme/pix");
				mkdir("$CFG->dirroot/theme/$theme/pix/mod");
				echo "mkdir($CFG->dirroot/theme/$theme/pix/mod/$folder)= ".mkdir("$CFG->dirroot/theme/$theme/pix/mod/$folder") . "<br/>";
				echo "copy()  $CFG->dirroot/mod/$folder/icon.gif  >>  $CFG->dirroot/theme/$theme/pix/mod/$folder/icon.gif (status=";
				echo copy("$CFG->dirroot/mod/$folder/icon.gif","$CFG->dirroot/theme/$theme/pix/mod/$folder/icon.gif");
				echo ")<br/>";
			}
		}
    }


?>
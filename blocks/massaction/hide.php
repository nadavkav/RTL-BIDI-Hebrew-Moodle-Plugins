<?php

function ma_hide_execute($modids) {
    $message = "";
    foreach ($modids as $modid) {
        if (! set_coursemodule_visible($modid, 0)) {
            $message .= "Could not hide module $modid, &nbsp;";
        }
    }
    return $message;
}
	
?>

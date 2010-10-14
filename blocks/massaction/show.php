<?php

function ma_show_execute($modids) {
    $message = "";
    foreach ($modids as $modid) {
        if (! set_coursemodule_visible($modid, 1)) {
            $message .= "Could not show module $modid, &nbsp;";
        }
    }
    return $message;
}

?>

<?php

function ma_indent_execute($modids) {
    $message = "";
    foreach ($modids as $modid) {
        if (! $cm = get_record("course_modules", "id", $modid)) {
            error_log("Module $modid does not exist");
        } else {
            $cm->indent += 1;
            if ($cm->indent < 0) {
                $cm->indent = 0;
            }
            if (!set_field("course_modules", "indent", $cm->indent, "id", $cm->id)) {
                error_log ("Could not update indent level in course module $cm->id");
            }
        }
    }
    return $message;
}
	
?>

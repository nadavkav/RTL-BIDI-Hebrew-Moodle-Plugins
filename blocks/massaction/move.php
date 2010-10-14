<?php

/*function ma_move_confirm($modnames, $post) {
    $section_encoded = $post['section'];
    if ($section_encoded == -1) {
        redirect($post['return_to'], "No section was selected");
        exit;
    }
    $section = decode_array($section_encoded);
    $str = "";
    $str .= "<table width = 100%>";
    $str .= "<tr><td colspan=3>Are you sure you want to move module(s)</td></tr>";
    foreach ($modnames as $modname) {
        $str .= "<tr><td width=20/><td><b>$modname</b></td><td/></tr>";
    }
    $str .= "<tr><td colspan=3>To Section \"<b>".$section['name']."</b>\"?</td></tr></table>";
    return $str;
}*/

function ma_move_execute($modids, $post) {
    $section_id = -1;
    foreach ($post as $key => $val) {
        if ($key == 'section') {
            $section = decode_array($val);
            $section_id = $section['id'];
        }
    }
    if ($section_id == -1) {
        return;
    }
    $section = get_record("course_sections", "id", $section_id);
    $message = "";
    if ($section) {
        foreach ($modids as $modid) {
            $cm = get_record("course_modules", "id", $modid);
            if (! moveto_module($cm, $section)) {
                $message .= "Could not move module $modid, ";
            }
        }
    } else {
        $message = "No sections were selected";
    }
    return $message;
}	

?>

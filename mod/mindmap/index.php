<?php 

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "mindmap", "view all", "index.php?id=$course->id", "");


/// Get all required stringsnewmodule

    $strmindmaps = get_string("modulenameplural", "mindmap");
    $strmindmap  = get_string("modulename", "mindmap");


/// Print the header

    $navlinks = array();
    $navlinks[] = array('name' => $strmindmaps, 'link' => '', 'type' => 'activity');
    $navigation = build_navigation($navlinks);

    print_header_simple("$strmindmaps", "", $navigation, "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $mindmaps = get_all_instances_in_course("mindmap", $course)) {
        notice("There are no mindmaps", "../../course/view.php?id=$course->id");
        die;
    }

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ("center", "left");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ("center", "left", "left", "left");
    } else {
        $table->head  = array ($strname);
        $table->align = array ("left", "left", "left");
    }

    foreach ($mindmaps as $mindmap) {
        if (!$mindmap->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$mindmap->coursemodule\">$mindmap->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$mindmap->coursemodule\">$mindmap->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($mindmap->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>

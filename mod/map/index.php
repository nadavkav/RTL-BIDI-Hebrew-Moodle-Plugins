<?php  
/**
 * index.php
 * 
 * @package map
 * @author Ted Bowman <ted@tedbow.com>
 * @version 0.1
 * Lists all maps for a course
 *
*/

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id',PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_course_login($course);

    add_to_log($course->id, "map", "view all", "index?id=$course->id", "");

    $strmap = get_string("modulename", "map");
    $strmaps = get_string("modulenameplural", "map");

    print_header_simple("$strmaps", "",
                 "$strmaps", "", "", true, "", navmenu($course));


    if (! $maps = get_all_instances_in_course("map", $course)) {
        notice("There are no maps", "../../course/view.php?id=$course->id");
    }

    $timenow = time();
        $table->head  = array ( get_string("name"));
        $table->align = array ("center");
 
    $currentsection = "";

    foreach ($maps as $map) {

        $printsection = "";
        if ($map->section !== $currentsection) {
            if ($map->section) {
                $printsection = $map->section;
            }
            if ($currentsection !== "") {
                $table->data[] = 'hr';
            }
            $currentsection = $map->section;
        }
        
        //Calculate the href
        if (!$map->visible) {
            //Show dimmed if the mod is hidden
            $tt_href = "<a class=\"dimmed\" href=\"view.php?id=$map->coursemodule\">".format_string($map->name,true)."</a>";
        } else {
            //Show normal if the mod is visible
            $tt_href = "<a href=\"view.php?id=$map->coursemodule\">".format_string($map->name,true)."</a>";
        }
        $table->data[] = array ($tt_href);

    }
    echo "<br />";
    print_table($table);

    print_footer($course);
 
?>


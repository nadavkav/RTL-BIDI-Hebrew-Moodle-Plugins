<?php   // Code by Amr Hourani

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id',PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_course_login($course);

    add_to_log($course->id, "skype", "view all", "index?id=$course->id", "");

    $strskype = get_string("modulename", "skype");
    $strskypes = get_string("modulenameplural", "skype");

    print_header_simple("$strskypes", "",
                 "$strskypes", "", "", true, "", navmenu($course));


    if (! $skypes = get_all_instances_in_course("skype", $course)) {
        notice("There are no skypes", "../../course/view.php?id=$course->id");
    }



    $timenow = time();

    if ($course->format == "weeks") {
        $table->head  = array (get_string("week"), get_string("skype","skype"));
        $table->align = array ("center", "left", "left");
    } else if ($course->format == "topics") {
        $table->head  = array (get_string("topic"), get_string("skype","skype"));
        $table->align = array ("center", "left", "left");
    } else {
        $table->head  = array (get_string("skype","skype"));
        $table->align = array ("left", "left");
    }

    $currentsection = "";

    foreach ($skypes as $skype) {
        
        $printsection = "";
        if ($skype->section !== $currentsection) {
            if ($skype->section) {
                $printsection = $skype->section;
            }
            if ($currentsection !== "") {
                $table->data[] = 'hr';
            }
            $currentsection = $skype->section;
        }
        
        //Calculate the href
        if (!$skype->visible) {
            //Show dimmed if the mod is hidden
            $tt_href = "<a class=\"dimmed\" href=\"view.php?id=$skype->coursemodule\">".format_string($skype->name,true)."</a>";
        } else {
            //Show normal if the mod is visible
            $tt_href = "<a href=\"view.php?id=$skype->coursemodule\">".format_string($skype->name,true)."</a>";
        }
        

        
        
        if ($course->format == "weeks" || $course->format == "topics") {
            $table->data[] = array ($printsection, $tt_href);
        } else {
            $table->data[] = array ($tt_href);
        }
       
    }
    echo "<br />";
    print_table($table);

    print_footer($course);
 
?>


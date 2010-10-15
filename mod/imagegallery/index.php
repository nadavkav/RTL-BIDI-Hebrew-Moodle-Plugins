<?PHP // $Id: index.php,v 1.2 2006/06/07 15:10:02 janne Exp $

/// This page lists all the instances of imagegallery in a particular course

    require_once("../../config.php");
    require_once("lib.php");

    $id             = optional_param('id', '', PARAM_TEXT);

    require_variable($id);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "imagegallery", "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strimagegalleries = get_string("modulenameplural", "imagegallery");
    $strimagegallery  = get_string("modulename", "imagegallery");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    print_header("$course->shortname: $strimagegalleries", "$course->fullname",
                 "$navigation $strimagegalleries", "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $imagegalleries = get_all_instances_in_course("imagegallery", $course)) {
        notice("There are no image galleries", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ("CENTER", "LEFT");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ("CENTER", "LEFT", "LEFT", "LEFT");
    } else {
        $table->head  = array ($strname);
        $table->align = array ("LEFT", "LEFT", "LEFT");
    }

    foreach ($imagegalleries as $imagegallery) {
        if (!$imagegallery->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$imagegallery->coursemodule\">$imagegallery->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$imagegallery->coursemodule\">$imagegallery->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($imagegallery->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>

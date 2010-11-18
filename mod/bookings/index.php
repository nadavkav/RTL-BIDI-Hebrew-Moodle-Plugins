<?PHP // $Id: index.php,v 1.1 2003/09/30 02:45:19 moodler Exp $

/// This page lists all the instances of bookings in a particular course
/// Replace bookings with the name of your module

    require_once("../../config.php");
    require_once("lib.php");

//CZW    require_variable($id);   // course
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "bookings", "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strbookingss = get_string("modulenameplural", "bookings");
    $strbookings  = get_string("modulename", "bookings");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
		$navigation .= $strbookingss;
    }

    print_header("$course->shortname: $strbookingss", "$course->fullname", "$navigation", "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $bookingss = get_all_instances_in_course("bookings", $course)) {
        notice("There are no bookingss", "../../course/view.php?id=$course->id");
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

    foreach ($bookingss as $bookings) {
        if (!$bookings->visible) {
            //Show dimmed if the mod is hidden
            $link = "<A class=\"dimmed\" HREF=\"view.php?id=$bookings->coursemodule\">$bookings->name</A>";
        } else {
            //Show normal if the mod is visible
            $link = "<A HREF=\"view.php?id=$bookings->coursemodule\">$bookings->name</A>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($bookings->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<BR>";

    print_table($table);

/// Finish the page

    print_footer($course);

?>

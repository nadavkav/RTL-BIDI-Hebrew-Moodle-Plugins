<?PHP // $Id: index.php,v 1.2 2007/04/27 09:10:51 janne Exp $

/// This page lists all the instances of netpublish in a particular course
/// Replace netpublish with the name of your module

    require_once("../../config.php");
    //require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "netpublish", "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strnetpublishes    = get_string("modulenameplural", "netpublish");
    $strnetpublish      = get_string("modulename", "netpublish");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    print_header("$course->shortname: $strnetpublishes", "$course->fullname", "$navigation $strnetpublishes", "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $netpublishes = get_all_instances_in_course("netpublish", $course)) {
        notice("There are no netpublishes", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow    = time();
    $strname    = get_string("name");
    $strweek    = get_string("week");
    $strtopic   = get_string("topic");

    // set table alignment according to course's RTL/LTR mode
    if (right_to_left()){
      $rtlalignment = 'RIGHT';
    } else {
      $rtlalignment = 'LEFT';
    }

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ("center", $rtlalignment);
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ("center", $rtlalignment, $rtlalignment, $rtlalignment);
    } else {
        $table->head  = array ($strname);
        $table->align = array ($rtlalignment, $rtlalignment, $rtlalignment);
    }

    foreach ($netpublishes as $netpublish) {
        if (!$netpublish->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" HREF=\"view.php?id=$netpublish->coursemodule\">$netpublish->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$netpublish->coursemodule\">$netpublish->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $options = new stdClass;
            $options->noclean = true;
            $intro = format_text($netpublish->intro, FORMAT_HTML, $options);

            $table->data[] = array ($netpublish->section, $link, $intro);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>

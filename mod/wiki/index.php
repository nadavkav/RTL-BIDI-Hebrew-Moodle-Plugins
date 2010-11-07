<?php // $Id: index.php,v 1.3 2007/10/24 08:07:54 pigui Exp $

/// This page lists all the instances of wiki in a particular course
/// Replace wiki with the name of your module

    //this variable determine if we need all wiki libraries.
    $full_wiki = true;

	require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id',PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, 'wiki', "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strwikis = get_string("modulenameplural", 'wiki');
    $strwikis  = get_string("modulename", 'wiki');


	/// Print header.
    $navlinks = array();
    $navlinks[] = array('name' => get_string('modulenameplural','wiki'), 'link' => $CFG->wwwroot.'/mod/wiki/index.php?id='.$course->id, 'type' => 'activity');
    
    $navigation = build_navigation($navlinks);
    
    print_header_simple(format_string($course->fullname), "",
                 $navigation, "", "", true, "", navmenu($course, $WS->cm));
/// Get all the appropriate data

    if (! $wikis = get_all_instances_in_course('wiki', $course)) {
        notice("There are no wikis", "../../course/view.php?id=$course->id");
        die;
    }
    
	print_heading(get_string("modulenameplural", "wiki"));
/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string('name');
    $strweek  = get_string('week');
    $strtopic  = get_string('topic');
    $strintro  = get_string('summary');

    if ($course->format == 'weeks') {
        $table->head  = array ($strweek, $strname, $strintro);
        $table->align = array ('center', 'left', 'left');
    } else if ($course->format == 'topics') {
        $table->head  = array ($strtopic, $strname, $strintro);
        $table->align = array ('center', 'left', 'left');
    } else {
        $table->head  = array ($strname, $strintro);
        $table->align = array ('left', 'left');
    }

    foreach ($wikis as $wiki) {
        if (!$wiki->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$wiki->coursemodule\">".s($wiki->name)."</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$wiki->coursemodule\">".s($wiki->name)."</a>";
        }

        $introoptions->para=false;
        $intro = trim(format_text($wiki->intro, $wiki->introformat, $introoptions));

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($wiki->section, $link, $intro);
        } else {
            $table->data[] = array ($link, $intro);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>

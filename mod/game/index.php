<?php // $Id: index.php,v 1.1 2008/03/26 17:40:38 arborrow Exp $
/**
 * This page lists all the instances of game module in a particular course
 *
 * @author 
 * @version $Id: index.php,v 1.1 2008/03/26 17:40:38 arborrow Exp $
 * @package game
 **/

    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "game", "view all", "index.php?id=$course->id", "");


/// Get all required strings game

    $strgames = get_string("modulenameplural", "game");
    $strgame = get_string("modulename", "game");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }
    
    $navlinks = array();
    $navlinks[] = array('name' => $strgames, 'link' => "index.php?id=$course->id", 'type' => 'activity');
        
    if( function_exists( 'build_navigation')){
        $navigation = build_navigation( $navlinks);
        
        print_header( $course->shortname, $course->shortname, $navigation);
    }else{    
        if ($course->category) {
            $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
        } else {
            $navigation = '';
        }
        print_header("$course->shortname: $strgames", "$course->fullname", "$navigation $strgames", "", "", true, "", navmenu($course));
    }
    
/// Get all the appropriate data

    if (! $games = get_all_instances_in_course("game", $course)) {
        notice("There are no games", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

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

    foreach ($games as $game) {
        if (!$game->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$game->coursemodule\">$game->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$game->coursemodule\">$game->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($game->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>

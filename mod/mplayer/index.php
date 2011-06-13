<?php // $Id: view.php,v 0.2 2010/01/15 matbury Exp $
/**
 * This page prints a list of instances of mplayer in current course
 *
 * @author Matt Bury - matbury@gmail.com
 * @version $Id: view.php,v 1.1 2010/01/15 matbury Exp $
 * @licence http://www.gnu.org/copyleft/gpl.html GNU Public Licence
 * @package mplayer
 **/
 
/**    Copyright (C) 2009  Matt Bury
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/// Replace mplayer with the name of your module

    require_once('../../config.php');
    require_once('lib.php');

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record('course', 'id', $id)) {
        error('Course ID is incorrect');
    }
	

    require_login($course->id);

    add_to_log($course->id, 'mplayer', 'view all', 'index.php?id='.$course->id, '');


/// Get all required stringsmplayer

    $strmplayers = get_string('modulenameplural', 'mplayer');
    $strmplayer  = get_string('modulename', 'mplayer');


/// Print the header

    $navlinks = array();
    $navlinks[] = array('name' => $strmplayers, 'link' => '', 'type' => 'activity');
    $navigation = build_navigation(get_string('mplayer', 'mplayer').'s', $id);
    print_header_simple("$strmplayers", '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data

    if (! $mplayers = get_all_instances_in_course('mplayer', $course)) {
        notice('There are no mplayers', '../../course/view.php?id='.$course->id);
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string('name');
    $strweek  = get_string('week');
    $strtopic  = get_string('topic');

    if ($course->format == 'weeks')
	{
        $table->head  = array ($strweek, $strname);
        $table->align = array ('center', 'left');
    } else if ($course->format == 'topics'){
        $table->head  = array ($strtopic, $strname);
        $table->align = array ('center', 'left', 'left', 'left');
    } else {
        $table->head  = array ($strname);
        $table->align = array ('left', 'left', 'left');
    }

    foreach ($mplayers as $mplayer) {
        if (!$mplayer->visible) {
            //Show dimmed if the mod is hidden
            $link = '<a class="dimmed" href="view.php?id='.$mplayer->coursemodule.'">'.$mplayer->name.'</a>';
        } else {
            //Show normal if the mod is visible
            $link = '<a href="view.php?id='.$mplayer->coursemodule.'">'.$mplayer->name.'</a>';
        }

        if ($course->format == 'weeks' or $course->format == 'topics') {
            $table->data[] = array ($mplayer->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo '<br />';

    print_table($table);

/// Finish the page

    print_footer($course);

?>

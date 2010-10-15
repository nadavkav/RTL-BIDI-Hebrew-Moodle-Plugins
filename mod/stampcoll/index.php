<?php  // $Id: index.php,v 1.5 2008/02/20 23:58:35 mudrd8mz Exp $

    require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
    require_once(dirname(__FILE__).'/lib.php');

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record('course', 'id', $id)) {
        error('Course ID is incorrect');
    }

    require_course_login($course);

    add_to_log($course->id, 'stampcoll', 'view all', 'index.php?id=$course->id', '');

    $strstamps = get_string('modulenameplural', 'stampcoll');

    $navigation = build_navigation($strstamps);
    print_header_simple($strstamps, '',
                 $navigation, '', '', true, '', navmenu($course));


    if (! $stampcolls = get_all_instances_in_course('stampcoll', $course)) {
        notice('There are no stamp collections', '../../course/view.php?id='.$course->id);
    }

    if ($course->format == 'weeks') {
        $table->head  = array (get_string('week'), get_string('name'), get_string('numberofstamps', 'stampcoll'));
        $table->align = array ('center', 'left', 'center');
    } else if ($course->format == 'topics') {
        $table->head  = array (get_string('topic'), get_string('name'), get_string('numberofstamps', 'stampcoll'));
        $table->align = array ('center', 'left', 'center');
    } else {
        $table->head  = array (get_string('name'), get_string('numberofstamps', 'stampcoll') );
        $table->align = array ('left', 'left');
    }

    $currentsection = '';

    foreach ($stampcolls as $stampcoll) {
        if (! $cm = get_coursemodule_from_instance('stampcoll', $stampcoll->id)) {
            error('Course Module ID was incorrect');
        }
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        include(dirname(__FILE__).'/caps.php');

        if (! $cap_viewsomestamps) {
            $count_mystamps = get_string('notallowedtoviewstamps', 'stampcoll');
        } else {
            if (! $allstamps = stampcoll_get_stamps($stampcoll->id)) {
                $allstamps = array();
            }
            $count_totalstamps = count($allstamps);
            $count_mystamps = 0;
            foreach ($allstamps as $s) {
                if ($s->userid == $USER->id) {
                    $count_mystamps++;
                }
            }
            unset($allstamps);
            unset($s);
        }

        $printsection = '';
        if ($stampcoll->section !== $currentsection) {
            if ($stampcoll->section) {
                $printsection = $stampcoll->section;
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $stampcoll->section;
        }
        
        //Calculate the href
        if (!$stampcoll->visible) {
            //Show dimmed if the mod is hidden
            $tt_href = '<a class="dimmed" href="view.php?id='.$stampcoll->coursemodule.'">';
            $tt_href .= format_string($stampcoll->name, true);
            $tt_href .= '</a>';
        } else {
            //Show normal if the mod is visible
            $tt_href = '<a href="view.php?id='.$stampcoll->coursemodule.'">';
            $tt_href .= format_string($stampcoll->name, true);
            $tt_href .= '</a>';
        }

        if (! $cap_viewsomestamps) {
            $aa = get_string('notallowedtoviewstamps', 'stampcoll');
        } else {
            $aa = '';
            if ($cap_viewownstamps) {
                $aa .= $count_mystamps;
            }
            if ($cap_viewotherstamps) {
                $aa .= ' ('. ($count_totalstamps - $count_mystamps) .')';
            }
        }
            
        if ($course->format == 'weeks' || $course->format == 'topics') {
            $table->data[] = array ($printsection, $tt_href, $aa);
        } else {
            $table->data[] = array ($tt_href, $aa);
        }
    }
    print_table($table);

    print_footer($course);
 
?>

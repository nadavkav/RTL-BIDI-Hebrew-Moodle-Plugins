<?php

    require_once('../../../config.php');
    require_once('../../lib.php');

    $topic    = optional_param('topic', -1, PARAM_INT);
    $courseid = optional_param('courseid', 0, PARAM_INT);

    if (!($course = get_record('course', 'id', $courseid)) ) {
        error('Invalid course id');
    }

    //add_to_log($course->id, 'course', 'view', "view.php?id=$course->id", "$course->id");
    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    if (! $section1 = get_record('course_sections', 'course', $course->id, 'section', $topic)) {
        error('Invalid topic number');
    }

    if (! $section2 = get_record('course_sections', 'course', $course->id, 'section', (int)($topic+1))) {
        error('Invalid topic number');
    }

    get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);

//    echo '<tr id="section-'.$section->id.'" class="section main">';
//    echo '<td class="left side">&nbsp;</td>';
//    echo '<td class="content">';

    echo '<table id="tbookpages"><tbody><tr>';
    echo '<td>';
    print_section($course, $section1, $mods, $modnamesused);
    echo '</td>';

    echo '<td>';
    print_section($course, $section2, $mods, $modnamesused);
    echo '</td>';
    echo '</tr></tbody></table>';

//    echo '</td>';
//    echo '<td class="right side">&nbsp;</td>';
//    echo '</tr>';
//    echo '<tr class="section separator"><td colspan="3" class="spacer"></td></tr>';

?>
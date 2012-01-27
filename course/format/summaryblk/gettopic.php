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

    if (! $section = get_record('course_sections', 'course', $course->id, 'section', $topic)) {
        error('Invalid topic number');
    }

    get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);

    echo '<tr id="section-0" class="section main">';
    echo '<td class="left side">&nbsp;</td>';
    echo '<td class="content">';

    echo '<div class="summary">';
    $summaryformatoptions->noclean = true;
    //echo format_text($section->summary, FORMAT_HTML, $summaryformatoptions);

    if (isediting($course->id) && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
        echo '<a title="'.$streditsummary.'" '.
             ' href="editsection.php?id='.$section->id.'"><img src="'.$CFG->pixpath.'/t/edit.gif" '.
             ' alt="'.$streditsummary.'" /></a><br /><br />';
    }
    echo '</div>';

    print_section($course, $section, $mods, $modnamesused);

    if (isediting($course->id)) {
        print_section_add_menus($course, $section, $modnames);
    }

    echo '</td>';
    echo '<td class="right side">&nbsp;</td>';
    echo '</tr>';
    echo '<tr class="section separator"><td colspan="3" class="spacer"></td></tr>';

?>
<?php

    require_once('../../config.php');
    require_once($CFG->libdir . '/rsslib.php');
    require_once('lib.php');

    $id = required_param('id', PARAM_INT);

    if (! $course = get_record('course', 'id', $id)) {
        error('Course ID is incorrect');
    }

    require_login($course->id);
    add_to_log($course->id, 'lightboxgallery', 'view all', 'index.php?id=' . $course->id, '');

    $strgalleries = get_string('modulenameplural', 'lightboxgallery');

    $navigation = build_navigation($strgalleries);

    print_header($course->shortname . ': ' . $strgalleries, $course->fullname, $navigation, '', '', true, '&nbsp;', navmenu($course));

    echo('<br />');

    if (! $galleries = get_all_instances_in_course('lightboxgallery', $course)) {
        notice(get_string('thereareno', 'moodle', $strgalleries), $CFG->wwwroot . '/course/view.php?id=' . $course->id);
        exit;
    }

    $strhead = get_string($course->format == 'weeks' ? 'week' : 'topic');

    $table = new object;
    $table->head = array($strhead, get_string('name'), get_string('description'), 'RSS');
    $table->align = array('center', 'left', 'left', 'center');
    $table->width = '*';

    $fobj = new object;
    $fobj->para = false;

    $currentsection = '';

    foreach ($galleries as $gallery) {
        $printsection = '&nbsp;';
        $rss = '&nbsp;';
        $rsssubscribe = get_string('rsssubscribe', 'lightboxgallery');
        if ($currentsection !== $gallery->section) {
            $printsection = $gallery->section;
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $gallery->section;
        }
        if (lightboxgallery_rss_enabled() && $gallery->rss) {
            $rss = rss_get_link($course->id, $USER->id, 'lightboxgallery', $gallery->id, $rsssubscribe);
        }
        $table->data[] = array($printsection,
                               '<a href="'.$CFG->wwwroot.'/mod/lightboxgallery/view.php?l='.$gallery->id.'">'.$gallery->name.'</a>', 
                               format_text($gallery->description, FORMAT_MOODLE, $fobj), $rss);
    }

    print_table($table);

    print_footer($course);

?>

<?php

    require_once('../../config.php');
    include($CFG->libdir . '/filelib.php');
    include('lib.php');

    $id = required_param('id', PARAM_INT);
    $l = optional_param('l' , 0, PARAM_INT);
    $search = optional_param('search', '', PARAM_TEXT);

    if (! $course = get_record('course', 'id', $id)) {
        error('Course is misconfigured');
    }

    if ($l && ! $gallery = get_record('lightboxgallery', 'id', $l)) {
        error('Course module is incorrect');
    }

    if (isset($gallery) && $gallery->public) {
        $userid = 0;
    } else {
        require_login($course->id);
        $userid = $USER->id;
    }

    add_to_log($course->id, 'lightboxgallery', 'search', 'search.php?id=' . $course->id . '&l=' . $l . '&search=' . $search, $search, 0, $userid);

    require_js(array('js/prototype.js', 'js/scriptaculous.js', 'js/effects.js', 'js/lightbox.js'));

    $navlinks = array();
    $navlinks[] = array('name' => get_string('search'), 'link' => '', 'type' => 'misc');
    $navlinks[] = array('name' => "'$search'", 'link' => '', 'type' => 'misc');

    if (isset($gallery)) {
        if (! $cm = get_coursemodule_from_instance('lightboxgallery', $gallery->id, $course->id)) {
            error('Course Module ID was incorrect');
        }
        $heading = $course->shortname . ': ' . $gallery->name;
        $navigation = build_navigation($navlinks, $cm);
    } else {
        $strmodplural = get_string('modulenameplural', 'lightboxgallery');
        array_unshift($navlinks, array('name' => $strmodplural, 'link' => $CFG->wwwroot . '/mod/lightboxgallery/index.php?id=' . $course->id, 'type' => 'activity'));
        $heading = $course->shortname . ': ' . $strmodplural;
        $navigation = build_navigation($navlinks);
    }

    print_header($heading, $course->fullname, $navigation, '', '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/mod/lightboxgallery/css/lightbox.css" />');

    echo('<br />');

    lightboxgallery_print_js_config(1);

    if ($instances = get_all_instances_in_course('lightboxgallery', $course)) {
        $options = array(0 => get_string('all'));
        foreach ($instances as $instance) {
            $options[$instance->id] = $instance->name;
        }

        echo('<form action="search.php" method="get">' .
                '<input type="hidden" name="id" value="' . $course->id . '" />' .
                '<table class="generaltable boxaligncenter" cellpadding="4" style="background-color: #f9fafa;">' .
                  '<tr>' .
                    '<td class="cell" style="vertical-align: middle;">' . get_string('modulenameshort', 'lightboxgallery') . '</td>' .
                    '<td>' . choose_from_menu($options, 'l', $l, '', '', '', true) . '</td>' .
                    '<td><input type="text" name="search" size="10" value="' . $search . '" /></td>' .
                    '<td><input type="submit" value="' . get_string('search') . '" /></td>' .
                  '</tr>' .
                '</table>' .
             '</form>');
    }

    $galleryselect = (isset($gallery) ? 'AND l.id = ' . $gallery->id : '');

    $like = sql_ilike();

    $sql = "SELECT m.image, m.metatype, m.description, l.id AS lid
            FROM mdl_lightboxgallery l, mdl_lightboxgallery_image_meta m
            WHERE m.gallery = l.id
            AND l.course = $course->id
            AND m.description $like '%$search%'
            $galleryselect
            ORDER BY l.id, m.image";

    if ($images = get_records_sql($sql)) {
        $imagesdisplay = array();
        $currentgallery = 0;
        foreach ($images as $image) {
            if ($currentgallery != $image->lid) {
                if ($currentgallery > 0) {
                    print_simple_box_end();
                }
                $gallery = get_record('lightboxgallery', 'id', $image->lid);

                $dataroot = $CFG->dataroot . '/' . $gallery->course . '/' . $gallery->folder;
                $webroot = lightboxgallery_get_image_url($gallery->id);

                $currentgallery = $gallery->id;

                print_heading('<a href="' . $CFG->wwwroot . '/mod/lightboxgallery/view.php?l=' . $gallery->id . '">' . $gallery->name . '</a>');
                print_simple_box_start('center');
            }
            echo('<div class="lightboxgalleryimage"><a href="' . $webroot . '/' . $image->image . '" rel="lightbox[search-result]" title="' . ($image->metatype == 'caption' ? $image->description : $image->image) . '">' . lightboxgallery_image_thumbnail($gallery->course, $gallery, $image->image) . '</a><br />' . $image->image . '</div>');
            $imagesdisplay[] = "'{$image->lid}{$image->image}'";
        }
        print_simple_box_end();

        if (count($imagesdisplay) > 0) {
            $sql = 'SELECT description
                    FROM ' . $CFG->prefix . 'lightboxgallery_image_meta
                    WHERE CONCAT(gallery, image) IN (' . implode(',', $imagesdisplay) . ')
                    AND description != \'' . $search . '\'
                    AND metatype = \'tag\'
                    GROUP BY description
                    ORDER BY COUNT(description) DESC, description ASC';
            if ($tags = get_records_sql($sql, 0, 10)) {
                lightboxgallery_print_tags(get_string('tagsrelated', 'lightboxgallery'), $tags, $course->id, $l);
            }
        }
    } else {
        echo('<br />');
        print_simple_box(get_string('errornosearchresults', 'lightboxgallery'), 'center');
    }

    print_footer($course);

?>


<?php  // $Id: view.php,v 1.4 2006/10/19 12:06:28 janne Exp $

/// This page prints a particular instance of learningdiary

    require_once("../../config.php");
    require_once("lib.php");
    //include_once ("slideshow.php");

    $edit   = optional_param('edit', '', PARAM_ALPHA);
    $pageid = optional_param('page', 0, PARAM_INT);
    $catid  = optional_param('catid', '0', PARAM_INT);
    $sort   = optional_param('sort', 'name', PARAM_ALPHA);
    $dir    = optional_param('dir', 'asc', PARAM_ALPHA);

    $gallery = new modImagegallery(); // Instantiate imagegallery object.

    // Check directory
    if ( !$gallery->file_area() ) {
        error("Could not create necessary directory!",
              "$CFG->wwwroot/course/view.php?id={$gallery->course->id}");
    }

    if (!isset($USER->editing)) {
        $USER->editing = false;
    }

    $strimagegalleries = get_string("modulenameplural", "imagegallery");
    $strimagegallery   = get_string("modulename", "imagegallery");

    if ($gallery->user_allowed_editing()) {
        if ($edit == 'on') {
            $USER->editing = true;
        } else if ($edit == 'off') {
            $USER->editing = false;
        }
        $stredit   = !empty($USER->editing) ? get_string('turneditingoff') : get_string('turneditingon');
        $editvalue = !empty($USER->editing) ? 'off' : 'on';

        $buttons  = '<table><tr><td><form method="get" action="view.php" target="_self">';
        $buttons .= '<input type="hidden" name="id" value="'. s($gallery->cm->id) .'" />';
        $buttons .= '<input type="hidden" name="edit" value="'. s($editvalue) .'" />';
        $buttons .= '<input type="hidden" name="catid" value="'. s($catid) .'" />';
        $buttons .= '<input type="submit" value="'. $stredit .'" /></form></td><td>';
        $buttons .= update_module_button($gallery->cm->id, $gallery->course->id, $strimagegallery);
        $buttons .= '</td></tr></table>';
    }

    if ( empty($buttons) ) {
        $buttons = update_module_button($gallery->cm->id, $gallery->course->id, $strimagegallery);
    }

    add_to_log($gallery->course->id, "imagegallery", "view", "view.php?id={$gallery->cm->id}", "{$gallery->module->name}");

    if ($gallery->course->category) {
        $navigation = "<a href=\"../../course/view.php?id={$gallery->course->id}\">{$gallery->course->shortname}</a> ->";
    }

    print_header("{$gallery->course->shortname}: {$gallery->module->name}", "{$gallery->course->fullname}",
                 "$navigation <a href=\"index.php?id={$gallery->course->id}\">$strimagegalleries</a> ->".
                 " {$gallery->module->name}", "", "", true,
                 $buttons,
                 navmenu($gallery->course, $gallery->cm));

    print_heading($gallery->module->name);
    print_simple_box_start("center");

    if ( empty($catid) ) {
        $catid = intval($gallery->module->defaultcategory);
    }

     print_box(format_text($gallery->module->intro), 'generalbox', 'intro');

    $gallery->print_category_list($catid);

    $gallery->print_image_list($catid, $pageid, $sort, $dir);

    if ( $gallery->user_allowed_upload() ) {
        $gallery->print_upload_form($catid);
    }
    print_simple_box_end();
    print_footer($gallery->course);

?>

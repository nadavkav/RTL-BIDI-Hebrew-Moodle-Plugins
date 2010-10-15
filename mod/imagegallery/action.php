<?php  // $Id: action.php,v 1.1.2.1 2006/12/05 10:05:29 janne Exp $

/// This page handels image deleting and image moving to another category.

    require_once("../../config.php");
    require_once("lib.php");

    $sesskey  = required_param('sesskey', PARAM_ALPHANUM);
    $imageids = required_param('image', PARAM_NOTAGS);
    $catid    = required_param('catid', PARAM_INT);
    $action   = optional_param('action', '', PARAM_NOTAGS);

    $gallery = new modImagegallery(); // Instantiate imagegallery object.

    if ( !$gallery->user_allowed_editing() ) {
        error("You're not allowed to use this page!!!",
              "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
    }

    if ( !confirm_sesskey($sesskey) ) {
        error("Session key error!!!");
    }

    if ( is_array($imageids) ) {

        // clean image id array.
        $arrayimages = array();
        foreach ( $imageids as $key => $value ) {
            array_push($arrayimages, intval($value));
        }

        $strimageids = implode(",", $arrayimages);
        $select  = "galleryid = {$gallery->module->id} AND ";
        $select .= "categoryid = $catid AND ";
        $select .= "id IN ($strimageids)";
        $images = get_records_select("imagegallery_images", $select, 'id', 'id, name, userid, path');

        $strimagegalleries = get_string("modulenameplural", "imagegallery");
        $strimagegallery   = get_string("modulename", "imagegallery");

        $navigation = '';
        if ($gallery->course->category) {
            $navigation = "<a href=\"../../course/view.php?id={$gallery->course->id}\">{$gallery->course->shortname}</a> ->";
        }

        $navigation .= " <a href=\"index.php?id={$gallery->course->id}\">$strimagegalleries</a> ->".
                       " <a href=\"view.php?id={$gallery->cm->id}&amp;catid=$catid\">{$gallery->module->name}</a> ";

        print_header("{$gallery->course->shortname}: {$gallery->module->name}", "{$gallery->course->fullname}",
                     "$navigation ", "", "", true,
                     update_module_button($gallery->cm->id, $gallery->course->id, $strimagegallery),
                     navmenu($gallery->course, $gallery->cm));

        if ( $data = data_submitted() ) {
            if ( !empty($data->delete) ) {


                $strconfirmdelete  = get_string('confirmimagedelete','imagegallery');
                $strdeleteimages   = get_string('deleteimages','imagegallery');



                print_simple_box_start("center");

                $tbl = new stdClass;
                $tbl->head  = array("&nbsp;", get_string('image','imagegallery'), get_string('username'));
                $tbl->align = array("right", "left", "left");
                $tbl->width = "100%";
                $tbl->data = array();
                if ( !empty($images) ) {
                    print_heading($strconfirmdelete, 'center', 4);

                    foreach ( $images as $image ) {
                        $row = array();
                        $row[] = '<input type="checkbox" name="image[]" value="'. $image->id .'" checked="checked" />';
                        $popupurl = '/mod/imagegallery/image.php?id='. $image->id;
                        $row[] = link_to_popup_window($popupurl, "image", s($image->name), 400, 500, s($image->name),'none',true);
                        $row[] = $gallery->get_user_fullname($image->userid);
                        array_push($tbl->data, $row);
                    }

                    echo '<form method="post" action="delete.php">'."\n";
                    echo '<input type="hidden" name="id" value="'. s($gallery->cm->id) .'" />'."\n";
                    echo '<input type="hidden" name="sesskey" value="'. s($USER->sesskey) .'" />'."\n";
                    echo '<input type="hidden" name="catid" value="'. s($catid) .'" />'."\n";
                    echo '<input type="hidden" name="action" value="remove" />'."\n";

                    print_table($tbl);

                    echo '<div style="text-align: center">';
                    echo '<input type="submit" value="'. get_string('ok') .'" />'."\n";
                    echo '<input type="button" name="cancel" value="'. get_string('cancel') .
                         '" onclick="javascript:void(history.back())"  />'."\n";
                    echo '</div></form>';
                }

                print_simple_box_end();

            } else if ( !empty($data->move) ) {
                // Moving images to an other gallery or category.
                $strmoveselectedimages = get_string('moveselected','imagegallery');
                $strchoose = get_string('choose') . '...';
                print_simple_box_start('center');
                print_heading($strmoveselectedimages);

                echo "\n";
                echo '<form id="galForm" name="gForm" method="post" action="move.php">'."\n";
                echo '<input type="hidden" name="id" value="'. s($gallery->cm->id) .'" />'."\n";
                echo '<input type="hidden" name="image" value="'. s($strimageids) .'" />'."\n";
                echo '<input type="hidden" name="sesskey" value="'. s($USER->sesskey) .'" />'."\n";

                if ( $galleries = get_records_menu("imagegallery", "course",
                                                   $gallery->course->id, "", "id, name") ) {

                    print_string('choosegallery','imagegallery');
                    choose_from_menu($galleries, "gallery", $gallery->module->id,
                                     $strchoose,
                                     "iGallery._getCat('{$gallery->cm->id}','$USER->sesskey'" .
                                     ",'$CFG->wwwroot/mod/imagegallery/xmloutforajax.php', " .
                                     "this.value, 'categorylist')");

                    echo '<select id="cat_id" name="category">'."\n";
                    echo '<option value="0">'. $strchoose .'</option>' . "\n";
                    echo '</select>'."\n";
                    echo '<input type="submit" value="'. get_string('move') .'" />' . "\n";
                    echo '<input type="button" value="'. get_string('cancel') .'" ' .
                         'onclick="javascript: history.back()" />' . "\n";
                }
                echo '</form>'."\n";
                print_simple_box_end();

            }
        }

        echo "\n";
        echo '<script language="javascript" type="text/javascript">'."\n";
        echo '<!--'. "\n";
        echo 'iGallery._getCat(\''. $gallery->cm->id .'\',\''. $USER->sesskey .'\',\''.
             $CFG->wwwroot .'/mod/imagegallery/xmloutforajax.php\', ';
        echo $gallery->module->id .', \'categorylist\');'. "\n";
        echo '// stop hiding -->'."\n";
        echo '</script>'."\n";
        print_footer($gallery->course);

    } else {
        redirect("$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
    }
?>
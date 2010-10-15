<?php  // $Id: move.php,v 1.1.2.1 2006/12/05 10:05:29 janne Exp $

/// This script does the actual removing images from
/// the database and filesystem.

    require_once("../../config.php");
    require_once("lib.php");

    $sesskey   = required_param('sesskey', PARAM_ALPHANUM);
    $imageids  = required_param('image',   PARAM_NOTAGS);
    $galleryid = required_param('gallery', PARAM_INT);
    $catid    = optional_param('category', PARAM_INT);

    $gallery = new modImagegallery(); // Instantiate imagegallery object.

    if ( !$gallery->user_allowed_editing() ) {
        error("You're not allowed to use this page!!!",
              "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
    }

    if ( !confirm_sesskey($sesskey) ) {
        error("Session key error!!!");
    }

    $arrimages = explode(",", $imageids);

    if ( empty($arrimages) && !is_array($arrimages) ) {
        error("Could not find any images to move!",
              "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
    }

    if ( $images = get_records_select("imagegallery_images", "id IN (". addslashes($imageids) .")") ) {
        $sql  = "UPDATE {$CFG->prefix}imagegallery_images ";
        $sql .= "SET galleryid = '$galleryid', categoryid = ";
        $sql .= "'$catid' WHERE id IN (". addslashes($imageids) .")";

        if ( execute_sql($sql, false) ) {
            $count = 1;
            $strimages = '';
            foreach ( $images as $image ) {
                $strimages .= s($image->name);
                if ( $count > 0 ) {
                    $strimages .= ', ';
                }
                $count++;
            }
            $strmessage = get_string('imagemovesuccessful','imagegallery', $strimages);
            redirect("view.php?a=$galleryid&amp;catid=$catid", $strmessage, 2);
        }
    }

?>
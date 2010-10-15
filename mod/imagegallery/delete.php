<?php  // $Id: delete.php,v 1.2.2.1 2006/12/05 10:05:29 janne Exp $

/// This script does the actual removing images from
/// the database and filesystem.

    require_once("../../config.php");
    require_once("lib.php");

    $sesskey  = required_param('sesskey', PARAM_ALPHANUM);
    $imageids = required_param('image', PARAM_RAW);
    $catid    = required_param('catid', PARAM_INT);

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

        if ( $data = data_submitted() ) {

            if ( !empty($data->cancel) ) {
                redirect("$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}&amp;catid=$catid");
            }

            if ( empty($data->cancel) && !empty($data->action) ) {
                foreach ( $images as $image ) {
                    $success = false;
                    $thumbnail = $CFG->dataroot . dirname($image->path) .'/thumb_'. $image->name;
                    if ( delete_records("imagegallery_images", "id", $image->id,
                                        "galleryid", $gallery->module->id) ) {
                        if ( @unlink($thumbnail) ) {
                            if ( @unlink($CFG->dataroot . $image->path) ) {
                                $success = true;
                            }
                        }
                    }
                    if ( !$success ) {
                        $imagename = s($image->name);
                        error("Error while deleting image <strong>$imagename</strong>!!!",
                              "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
                    }
                }
                $strsuccess = get_string('imagedeletesuccess','imagegallery');
                redirect("$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}&amp;catid=$catid",
                         $strsuccess, 2);
            }
        }

    } else {
        redirect("$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
    }
?>
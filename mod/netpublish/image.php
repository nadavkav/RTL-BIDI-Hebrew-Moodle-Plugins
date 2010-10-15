<?php // $Id: image.php,v 1.1 2006/02/01 23:37:43 janne Exp $
/// This file is used to output image from netpublish
/// Data base it only takes one argument which is
/// images id.

    require_once('../../config.php');

    $id = required_param('id', PARAM_INT);

    if (empty($id)) {
        header("HTTP/1.0 404 not found");
        error(get_string("filenotfound", "error"), "$CFG->wwwroot/course/view.php?id=$courseid");
        exit;
    }

    $image = __get_one_image_ ($id);

    if (empty($image)) {
        header("HTTP/1.0 404 not found");
        error(get_string("filenotfound", "error"), "$CFG->wwwroot/course/view.php?id=$courseid");
    }

    // output image
    $imagesource = $CFG->dataroot .'/'. $image->path;
    $filesize     = filesize($imagesource);
    $filetime     = filemtime($imagesource);
    $date         = gmdate("D, d M Y H:i:s", $filetime) .' GMT';
    $lastmodified = gmdate("D, d M Y H:i:s", $filetime) .' GMT';

    header("Content-Type: $image->mimetype\r\n");
    header("Content-Length:  $filesize\r\n");
    header("Date: $date\r\n");
    header("Last-Modified: $lastmodified\r\n");
    header("Content-Disposition: inline; filename=\"$image->name\"\r\n");
    header("Connection: close\r\n");
    readfile($imagesource);

/// OTHER FUNCTIONS ///
/// This function is here so we don't have to load
/// library file from netpublish module. This decrease
/// memory usage and possible overhead. The function
/// name is indeed awful but it prevent possible redeclaration.
/// (Hopefully no-one else names his/hers function this stupidly :-D)

function __get_one_image_ ($id) {

    $select = 'id = '. $id;

    return get_record_select("netpublish_images", $select);

}
?>
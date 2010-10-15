<?php // $Id: image.php,v 1.5 2006/10/17 10:24:47 janne Exp $
/// This file is used to output image from imagegallery
/// Data base it only takes one argument which is
/// images id.

    require_once('../../config.php');
    define('IMAGEGALLERY_ERROR_IMAGE', "{$CFG->dirroot}/mod/imagegallery/pix/error.png");

    $id    = optional_param('id', 0, PARAM_INT); //required_param('id', PARAM_INT);
    $thumb = optional_param('thumb', false, PARAM_BOOL);

    if (empty($id)) {
        //header("HTTP/1.0 404 not found");
        //error(get_string("filenotfound", "error"), "$CFG->wwwroot/course/view.php?id=$courseid");
        display_error_image("Image not found!!!");
        exit;
    }

    $image = __get_one_image_ ($id, $thumb);

    if ( !empty($image->requirelogin) ) {
        $cm = get_coursemodule_from_instance("imagegallery",
                                             $image->galleryid,
                                             $image->course);
        $course = new stdClass;
        $course->id = $image->course;

		// strange bug, $course is not good enought for require_course_login()
		// so i "had" to use $COURSE
        require_course_login($COURSE, $CFG->autologinguests, $cm);
    }

    if (empty($image)) {
        // Create an error image.
        display_error_image();
        exit;
    }

    // output image
    $filesize     = filesize($image->path);
    $filetime     = filemtime($image->path);
    $date         = gmdate("D, d M Y H:i:s", $filetime) .' GMT';
    $lastmodified = gmdate("D, d M Y H:i:s", $filetime) .' GMT';

    header("Content-Type: $image->mime\r\n");
    header("Content-Length:  $filesize\r\n");
    header("Date: $date\r\n");
    header("Last-Modified: $lastmodified\r\n");
    header("Content-Disposition: inline; filename=\"$image->name\"\r\n");
    header("Connection: close\r\n");
    readfile($image->path);

/// OTHER FUNCTIONS ///
/// This function is here so we don't have to load
/// library file from netpublish module. This decrease
/// memory usage and possible overhead. The function
/// name is indeed awful but it prevent possible redeclaration.
/// (Hopefully no-one else names his/hers function this stupidly :-D)

function __get_one_image_ ($id, $thumb) {

    global $CFG;

    $id = intval($id);
    $image = get_record_sql("SELECT i.* ,g.requirelogin, g.course
                             FROM
                               {$CFG->prefix}imagegallery_images AS i,
                               {$CFG->prefix}imagegallery AS g
                             WHERE i.galleryid = g.id AND i.id = '$id'");

    if ( empty($image) ) {
        return false;
        exit;
    }

    if ( $thumb ) {
        $image->path  = dirname($image->path);
        $image->path .= '/thumb_'. $image->name;
    }
    $image->path = $CFG->dataroot . $image->path;

    return $image;
}

function display_error_image ($errorstring='') {

    $errorimage = IMAGEGALLERY_ERROR_IMAGE;

    if ( !empty($errorstring) && function_exists('imagecreatefrompng') ) {

        if ( $image = imagecreate(150, 150) ) {
            $white = imagecolorallocate($image, 255,255,255);
            $red   = imagecolorallocate($image, 255, 0, 0);
            $black = imagecolorallocate($image, 0, 0, 0);
            imagefill($image, 0, 0, $red);
            imagefilledrectangle($image, 2, 2, 147, 147, $white);
            imagestring($image, 4, 4, 60, $errorstring, $black);

            header("Content-Type: image/png\r\n");
            header("Content-Length: ". strlen($image) ."\r\n");
            header("Date: ". gmdate("D, d M Y H:i:s", time()) ." GMT\r\n");
            header("Last-Modified: ". gmdate("D, d M Y H:i:s", time()) ." GMT\r\n");
            header("Content-Disposition: inline; filename=\"". basename($errorimage) ."\"\r\n");
            header("Connection: close\r\n");
            imagepng($image);
        }
        exit;
    }

    header("Content-Type: image/png\r\n");
    header("Content-Length: ". filesize($errorimage) ."\r\n");
    header("Date: ". gmdate("D, d M Y H:i:s", time()) ." GMT\r\n");
    header("Last-Modified: ". gmdate("D, d M Y H:i:s", time()) ." GMT\r\n");
    header("Content-Disposition: inline; filename=\"". basename($errorimage) ."\"\r\n");
    header("Connection: close\r\n");
    readfile($errorimage);
}
?>
<?php  // $Id: upload.php,v 1.9 2006/10/17 10:24:47 janne Exp $

/// This page handles uploads for imagegallery.

    require_once("../../config.php");
    require_once("lib.php");

    $id     = required_param('id', PARAM_ALPHA);            // Module instance id.
    $skey   = required_param('sesskey', PARAM_ALPHANUM); // Session key.
    $catid  = optional_param('categoryid', 0, PARAM_INT);        // Category id.

    $gallery = new modImagegallery(); // Instantiate imagegallery object.

    if ( !confirm_sesskey($skey) )  {
        error("Session key error!!!");
    }

    if ( !$gallery->user_allowed_upload() ) {
        error(get_string('unallowedupload','imagegallery'),
              "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
    }

    if ( !$category = get_record("imagegallery_categories", "id", $catid) ) {
        $category = new stdClass;
        $category->id = 0;
    }

    $strsuccess = '';
    if ( $data = data_submitted() ) {

        include($CFG->libdir .'/filelib.php');
        include($CFG->libdir .'/uploadlib.php');

        // Process uploaded images or uploaded zip file.
        $dir = $gallery->file_area($category->id);
        $um = new upload_manager('userfile', false, true, $gallery->course, false, 0);
        if ($um->process_file_uploads($dir)) {

            $file = new stdClass;
            $file->galleryid = $gallery->module->id;
            $file->categoryid = $catid;
            $file->userid = $USER->id;
            $file->name = $um->get_new_filename();
            $file->path = $um->get_new_filepath();

            if ( preg_match($GALLERY_ALLOWED_TYPES, $file->name) ) {
                // Check if uploaded file was a zip package.
                $icon = mimeinfo('icon', $file->name);

                if ( $icon != 'zip.gif' ) { // Single file.
                    $file->size = filesize($file->path);
                    $file->mime = mimeinfo('type', $file->name);
                    $fileinfo   = getimagesize($file->path);
                    $file->width  = $fileinfo[0];
                    $file->height = $fileinfo[1];
                    $file->timecreated  = time();
                    $file->timemodified = time();

                    $gallery->check_dimensions($file);

                    $file->description = addslashes(trim(strip_tags($_POST['description'])));
                    $file->path = $gallery->get_file_path($file->path);

                    if ( !insert_record("imagegallery_images", $file) ) {
                        @unlink($file->path);
                        error("Could not add new file $file->name to database!",
                              "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
                    }

                    $srcfile  = $CFG->dataroot . dirname($file->path);
                    $srcfile .= '/thumb_' . $file->name;
                    $gallery->make_thumbnail($CFG->dataroot . $file->path, $srcfile);

                } else {
                    // Unpack zip file and process files.
                    imagegallery_process_zip_file($file);
                }

                $strsuccess = get_string('uploadedfile');

            } else {
                @unlink($file->path);
                $filetype = substr($file->name, strpos($file->name, "."), strlen($file->name));
                $filetype = strtoupper($filetype);
                error("Unallowed file type <strong>$filetype</strong>",
                      "$CFG->wwwroot/mod/imagegallery/view.php?id=".
                      "{$gallery->cm->id}&amp;catid=$catid");
                exit;
            }
        }
    }

    redirect("view.php?id={$gallery->cm->id}&amp;catid=$catid", $strsuccess, 2);

function imagegallery_process_zip_file ($file) {
    global $CFG, $USER, $gallery;

    $tmpdir   = random_string(6);
    $fullpath = make_upload_directory($tmpdir);
    $origpath     = dirname($file->path);

    if ( !unzip_file($file->path, $fullpath) ) {
        error(get_string("unzipfileserror","error"));
    }

    $images = imagegallery_search_images($fullpath);

    if ( !empty($images) ) {
        foreach ( $images as $image ) {

            $newpath = $origpath .'/'. basename($image);
            // If file already exists, just skip it.
            if ( @file_exists($newpath) ) {
                continue;
            }
            $fileinfo = getimagesize($image);

            if ( !rename($image, $newpath) ) {
                error("Could not move file to new location!");
            }

            $newfile = new stdClass;
            $newfile->galleryid = $file->galleryid;
            $newfile->categoryid = $file->categoryid;
            $newfile->userid = $USER->id;
            $newfile->name = basename($image);
            $newfile->path = $newpath;
            $newfile->size   = filesize($newpath);
            $newfile->mime = mimeinfo('type', basename($image));
            $newfile->width  = $fileinfo[0];
            $newfile->height = $fileinfo[1];
            $newfile->timecreated  = time();
            $newfile->timemodified = time();
            // Check dimensions.
            $gallery->check_dimensions($newfile);
            $newfile->path = $gallery->get_file_path($newpath);

            if ( !insert_record("imagegallery_images", $newfile) ) {
                @unlink($newpath);
                error("Could not add new file $file->name to database!",
                      "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
            }

            // Make thumbnail.
            $thumb = $origpath . '/thumb_'. $newfile->name;
            $gallery->make_thumbnail($newpath, $thumb);
        }
    }
    fulldelete($fullpath);
    @unlink($file->path);
}

function imagegallery_search_images ($dir) {

    global $GALLERY_ALLOWED_TYPES;
    static $files;

    $imagetypes = str_replace("zip|", "", $GALLERY_ALLOWED_TYPES);

    if ( empty($files) ) {
        $files = array();
    }

    if ( $handle = opendir($dir) ) {
        while ( ($file = readdir($handle)) !== false ) {
            $chr = substr($file, 0, 1);
            if ( $chr == "." ) {
                continue;
            }
            $fullpath = "$dir/$file";
            if ( is_dir($fullpath) ) {
                imagegallery_search_images($fullpath);
            } else {
                if ( preg_match($imagetypes, $file) ) {
                    array_push($files, $fullpath);
                }
            }
        }
    }

    if ( $handle ) {
        @closedir($handle);
    }

    return $files;
}

?>
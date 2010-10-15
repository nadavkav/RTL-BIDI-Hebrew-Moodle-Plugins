<?php  // $Id: lib.php,v 1.12.2.1 2006/12/05 10:05:29 janne Exp $

/// Library of functions and constants for module imagegallery
    $GALLERY_ALLOWED_TYPES = "/\.(png|jpe?g|zip|gif)$/i";
    define('GALLERY_IMAGE_QUALITY', 90);
    define('GALLERY_ASPECT_RATIO', 1.3333333333);

function imagegallery_add_instance($imagegallery) {
/// Given an object containing all the necessary data,
/// (defined by the form in mod.html) this function
/// will create a new instance and return the id number
/// of the new instance.

    $imagegallery->timemodified = time();

    # May have to add extra stuff in here #

    return insert_record("imagegallery", $imagegallery);
}


function imagegallery_update_instance($imagegallery) {
/// Given an object containing all the necessary data,
/// (defined by the form in mod.html) this function
/// will update an existing instance with new data.

    $imagegallery->timemodified = time();
    $imagegallery->id = $imagegallery->instance;

    # May have to add extra stuff in here #

    return update_record("imagegallery", $imagegallery);
}


function imagegallery_delete_instance($id) {
/// Given an ID of an instance of this module,
/// this function will permanently delete the instance
/// and any data that depends on it.

    if (! $imagegallery = get_record("imagegallery", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #
    // Delete images in this imagegallery
    if (! delete_records("imagegallery_images", "galleryid", $imagegallery->id) ) {
        return false;
        exit;
    }
    // Delete categories in this imagegallery
    if (! delete_records("imagegallery_categories","galleryid", $imagegallery->id) ) {
        return false;
        exit;
    }
    return $result;

}

function imagegallery_user_outline($course, $user, $mod, $imagegallery) {
/// Return a small object with summary information about what a
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description

    return $return;
}

function imagegallery_user_complete($course, $user, $mod, $imagegallery) {
/// Print a detailed representation of what a  user has done with
/// a given particular instance of this module, for user activity reports.

    return true;
}

function imagegallery_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity
/// that has occurred in imagegallery activities and print it out.
/// Return true if there was output, or false is there was none.

    global $CFG;

    return false;  //  True if anything was printed, otherwise false
}

function imagegallery_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such
/// as sending out mail, toggling flags etc ...

    global $CFG;

    return true;
}

function imagegallery_grades($imagegalleryid) {
/// Must return an array of grades for a given instance of this module,
/// indexed by user.  It also returns a maximum allowed grade.
///
///    $return->grades = array of grades;
///    $return->maxgrade = maximum allowed grade;
///
///    return $return;

   return NULL;
}

function imagegallery_get_participants($imagegalleryid) {
//Must return an array of user records (all data) who are participants
//for a given instance of imagegallery. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.

    return false;
}

function imagegallery_scale_used ($imagegalleryid,$scaleid) {
//This function returns if a scale is being used by one imagegallery
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.

    $return = false;

    //$rec = get_record("imagegallery","id","$imagegalleryid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other imagegallery functions go here.  Each of them must have a name that
/// starts with imagegallery_

class modImagegallery {

    var $cm;
    var $course;
    var $module;
    var $isstudent = false;
    var $isteacher = false;
    var $isguest   = false;
    var $isadmin   = false;

    function modImagegallery () {

        global $CFG;
        $id = optional_param('id', 0, PARAM_INT);
        $a  = optional_param('a',  0, PARAM_INT);

        $cm = new stdClass;
        $course = new stdClass;
        $module = new stdClass;

        $coursefields = 'id, category, shortname, fullname, guest, '.
                        'maxbytes, visible, lang, theme, metacourse, '.
                        'format, modinfo, numsections';
        $modulefields = 'id, course, name, intro, maxbytes, maxwidth, maxheight, '.
                        'allowstudentupload, imagesperpage, requirelogin, '.
                        'resize, defaultcategory, shadow';
        $cmfields     = 'id, course, module, instance, section, '.
                        'visible';

        if ( $id ) {
            if (! $this->cm = get_record("course_modules", "id", $id,
                                         "", "", "", "", $cmfields)) {
                error("Course Module ID was incorrect");
            }

            if (! $this->course = get_record("course", "id", $this->cm->course,
                                             "", "", "", "", $coursefields)) {
                error("Course is misconfigured");
            }

            if (! $this->module = get_record("imagegallery", "id", $this->cm->instance,
                                  "", "", "", "", $modulefields)) {
                error("Course module is incorrect");
            }
        } else {
            if (! $this->module = get_record("imagegallery", "id", $a,
                                  "", "", "", "", $modulefields)) {
                error("Course module is incorrect");
            }
            if (! $this->course = get_record("course", "id", $this->module->course,
                                             "", "", "", "", $coursefields)) {
                error("Course is misconfigured");
            }
            if (! $this->cm = get_coursemodule_from_instance("imagegallery",
                                                             $this->module->id,
                                                             $this->course->id)) {
                error("Course Module ID was incorrect");
            }
        }

        // Free some memory.
        unset($coursefields, $modulefields, $cmfields);
        // Always require login for this module.
        require_course_login($this->course, $CFG->autologinguests, $this->cm);
        // Cache guest, student and teacher information.
        $this->isteacher = isteacher($this->course->id);
        $this->isstudent = isstudent($this->course->id);
        $this->isguest   = isguest();
        $this->isadmin   = isadmin();

        if ( (!$this->isstudent && !$this->isteacher) or $this->isguest ) {
            error(get_string('noaccess','imagegallery'),
                  "$CFG->wwwroot/course/view.php?id={$this->course->id}");
        }

    }

    function __construct() {
        $this->modImagegallery();
    }

    function user_allowed_editing() {
        if ( $this->isadmin ) {
            return true;
        }
        if ( $this->isteacher ) {
            return true;
        }
	if ( $this->isstudent ) {
            return true;
        }
        return false;
    }

    function user_allowed_upload () {
        if ( $this->isteacher ) {
            return true;
        }
        if ( !empty($this->module->allowstudentupload) ) {
            return true;
        }
        return false;
    }

    function file_area_name ($categoryname='') {

        global $CFG;
        $path = "{$this->course->id}/{$CFG->moddata}/imagegallery/{$this->module->id}";
        if ( !empty($categoryname) ) {
            $path .= "/$categoryname";
        }
        return $path;

    }

    function file_area ($categoryname='') {
        return make_upload_directory( $this->file_area_name($categoryname) );
    }

    function print_category_list($current) {

        global $USER;

        $options[0] = get_string('defaultcategory', 'imagegallery');
        if ( $categories = get_records("imagegallery_categories", "galleryid",
                                       $this->module->id) ) {
            foreach ( $categories as $category ) {
                $options[$category->id] = s($category->name);
            }
        }
        echo '<table border="0" align="center"><tr><td>';
        echo '<form name="glrCat" method="get" action="view.php">'."\n";
        echo '<input type="hidden" name="id" value="'. $this->cm->id .'" />'."\n";
        choose_from_menu($options, "catid", $current, "", "glrCat.submit()");
        echo '</form>';
        echo '</td>'."\n";
        if ( $USER->editing ) {
            echo '<td>';
            echo '<form method="get" action="categories.php">'."\n";
            echo '<input type="hidden" name="id" value="'. $this->cm->id .'" />'."\n";
            echo '<input type="hidden" name="sesskey" value="'. $USER->sesskey .'" />'."\n";
            echo '<input type="submit" value="'. get_string('managecategories','imagegallery') .
                 '" />'."\n";
            echo '</form>'."\n";
            echo '</td>'."\n";
        }
        echo '</tr></table>'."\n";
        echo '<br />'."\n";
    }

    function print_upload_form ($current) {

        global $USER, $CFG;
        $upload_max_filesize = get_max_upload_file_size($CFG->maxbytes, $this->module->maxbytes);

        $strparams  = get_string('maxbytes','imagegallery') .': '. display_size($upload_max_filesize) .', ';
        $strparams .= get_string('maxdimensions','imagegallery') .': '. $this->module->maxwidth .' x ';
        $strparams .= $this->module->maxheight .' (px)';

        echo '<div class="mod_ig_upload_help">';
        echo get_string('uploadhelp','imagegallery', $strparams);
        echo '</div>'."\n";
        echo '<form enctype="multipart/form-data" method="post" action="upload.php">'."\n";
        echo '<input type="hidden" name="id" value="'. $this->cm->id .'" />'."\n";
        echo '<input type="hidden" name="sesskey" value="'. $USER->sesskey .'" />'."\n";
        echo '<input type="hidden" name="categoryid" value="'. $current .'" />'."\n";
        echo '<input type="hidden" name="MAX_FILE_SIZE" value="'. $upload_max_filesize .'" />'."\n";

        echo '<table border="0" cellpadding="4" align="center">'."\n";
        echo '<tr valign="top"><td align="right"><strong>'. get_string('browse','imagegallery') .
             ':</strong></td>'."\n";
        echo '<td><input type="file" size="40" name="userfile" /></td></tr>'."\n";
        echo '<tr valign="top"><td align="right"><strong>'. get_string('description') .':</strong></td>'."\n";
        echo '<td><textarea name="description" cols="40" rows="2"></textarea></td></tr>'."\n";
        echo '<tr><td>&nbsp;</td><td>';
        echo '<input type="submit" value="'. get_string('upload') .'" />'."\n";
        echo '</td></tr></table>'."\n";
        echo '</form>';

    }

    function print_image_list ($catid, $page=0, $sort='name', $dir='asc') {

        global $CFG, $USER;

        $sortopt = array('name','date','size');
        $diropt  = array('asc','desc');

        if ( !in_array($sort, $sortopt) ) {
            $sort = 'name';
        }

        if ( !in_array($dir, $diropt) ) {
            $dir = 'asc';
        }

        if ( $images = $this->get_images_listing($catid, $sort, $dir,
                                                 $page*$this->module->imagesperpage,
                                                 'id, name, path, size, mime, width, height') ) {

            $rows    = count($images);
            $columns = 5;
            $cnt     = 1;
            $keys    = array_keys($images); // Needed for checkbox printing.
            $start   = 0;                   // Needed for checkbox printing.
            $end     = $columns;            // Needed for checkbox printing.

            if ( $USER->editing && $this->user_allowed_editing() ) {
                echo '<form method="post" action="action.php">'."\n";
                echo '<input type="hidden" name="id" value="'. $this->cm->id .'" />'."\n";
                echo '<input type="hidden" name="sesskey" value="'. $USER->sesskey .'" />'."\n";
                echo '<input type="hidden" name="catid" value="'. $catid .'" />'."\n";
                echo '<input type="hidden" name="page" value="'. $page .'" />'."\n";
                echo '<input type="hidden" name="sort" value="'. $sort .'" />'."\n";
                echo '<input type="hidden" name="dir" value="'. $dir .'" />'."\n";
            }
            echo '<table border="0" cellpadding="4" cellspacing="2" align="center">'."\n";
            foreach ( $images as $image ) {
                $div = $cnt % $columns;
                if ($div === 1) {
                    echo "<tr>\n";
                }

                echo '<td align="center">';
                $thumb = $CFG->dataroot . dirname($image->path) .'/thumb_'. $image->name;
                if ( file_exists($thumb) ) {

                    $strimagename = s($image->name);
                    $imginfo = getimagesize($thumb);

                    $overlib = $this->create_overlib_string($image);

                    $imgsrc = '<img src="image.php?id='.
                              $image->id .'&amp;thumb=true" '.
                              $imginfo[3] . ' alt="'.$image->description.'" border="0" /><br />'.substr($image->description,0,30).'...<br />';
                    if ($this->isteacher or $this->istadmin) { // display username, which uploaded the image (nadavkav)
                        $imgsrc .= $this->get_user_fullname($image->userid);
                    }

                    echo '<a href="details.php?id='. $this->cm->id .
                         '&amp;catid='. $catid . '&amp;image='. $image->id .
                         '&page='. $page .'&amp;sort='. s($sort) .
                         '&amp;dir='. s($dir) .'"'. $overlib .'>';
                    echo $imgsrc .'</a>';

                } else {
                    echo s($image->name);
                }
                echo '</td>';

                if ($cnt == $rows && $div != 0) {
                    for ($col = 0; $col < ($columns - $div); $col++) {
                        echo "\t<td width=\"100\">&nbsp;</td>\n";
                    }
                }

                if ($div === 0 or $cnt == $rows) {
                    echo "</tr>\n";
                    if ( $USER->editing && $this->user_allowed_editing() ) {
                        echo "<tr>\n";
                        for ($i = $start; $i < $end; $i++) {
                            $img = (!empty($keys[$i]) &&
                                    !empty($images[$keys[$i]])) ? $images[$keys[$i]] : NULL;

                        echo "\t<td align=\"center\">";
                            echo !empty($img->id) ? '<input type="checkbox" name="image[]" value="'.
                                                     s($img->id) .'" />' : '&nbsp;';
                        echo "</td>\n";
                    }
                        echo "</tr>\n";
                    }
                    $start = $end;
                    $end  += $columns;
                }

                $cnt++;
            }
            echo '</table>'."\n";

            if ( $USER->editing && $this->user_allowed_editing() ) {
                echo '<div align="center"><input type="submit" name="delete" value="'.
                     get_string('deleteselected') .'" /> '."\n";
                echo '<input type="submit" name="move" value="'.
                     get_string('moveselected','imagegallery') .'" /></div>'."\n";
                echo '</form><br />';
            }

            $imagecount = $this->count_images($catid);

            print_paging_bar($imagecount, $page, $this->module->imagesperpage,
                             "view.php?id={$this->cm->id}&amp;catid=$catid&amp;".
                             "sort=$sort&amp;dir=$dir&amp;");

            $this->print_sort_menus($catid, $page, $sort, $dir);

        } else {
            print_heading(get_string('noimagesincategory','imagegallery'));
        }
        echo '<p>&nbsp;</p>'."\n";
    }

    function get_user_fullname ($userid) {
        $userid = intval($userid);
        if ( $user = get_record("user", "id", $userid) ) {
            return fullname($user);
        }
        return NULL;
    }

    function print_category_form ($form) {

        if ( empty($form->name) ) {
            $form->name = '';
        }

        if ( empty($form->description) ) {
            $form->description = '';
        }

        if ( empty($form->categoryid) ) {
            $form->categoryid = '';
        }

        if ( !empty($form->categoryid) && $form->categoryid == $this->module->defaultcategory ) {
            $form->defaultcategory = 1;
        } else {
            $form->defaultcategory = 0;
        }

        $strheading = ($form->action != 'edit') ?
                      get_string('createcategory','imagegallery') :
                      get_string('editcategory','imagegallery', $form->name);

        print_heading($strheading);
        include('editcategories.html');

    }

    function print_category_delete_confirm_form ( $categories ) {
        global $CFG, $USER;

        if ( !empty($categories[$this->module->defaultcategory]) ) {
            error("You cannot delete default category!!!",
                  "$CFG->wwwroot/mod/imagegallery/categories.php".
                  "?id={$this->cm->id}&amp;sesskey={$USER->sesskey}");
        }

        $strheading = get_string('confirmdeletecategory','imagegallery');
        $strcategories = '';
        $strcatids = implode(",", array_keys($categories));
        $i = 1;
        foreach ( $categories as $category ) {
            if ( $i > 1 ) {
                $strcategories .= ', ';
            }
            $strcategories .= s($category->name);
            $i++;
        }
        $strmessage = get_string('confirmdeletestring','imagegallery', $strcategories);

        print_heading($strheading);
        $yesoptions = array("id" => $this->cm->id,
                            "sesskey" => $USER->sesskey,
                            "catid" => "$strcatids",
                            "action" => "delete");
        $nooptions = array("id" => $this->cm->id,
                           "sesskey" => $USER->sesskey);
        echo '<table border="0" width="100%">';
        echo '<tr valign="top">';
        echo '<td colspan="2">';
        echo '<p>'. $strmessage .'</p>';
        echo '</td></tr>';
        echo '<tr><td align="right">';
        print_single_button("categories.php", $yesoptions, get_string('yes'), 'post');
        echo '</td><td>';
        print_single_button("categories.php", $nooptions, get_string('no'));
        echo '</td></tr></table>';


    }

    function print_sort_menus($catid, $page, $sort, $dir) {

        $sortopt = array("name" => get_string('sortbyname','imagegallery'),
                         "date" => get_string('sortbydate','imagegallery'),
                         "size" => get_string('sortbysize','imagegallery'));
        $diropt  = array("asc"  => get_string('ascending','imagegallery'),
                         "desc" => get_string('descending','imagegallery'));

        echo '<div id="mod_ig_id_sort" class="mod_ig_class_sort">';
        echo '<form name="glrSort" method="get" action="view.php">'."\n";
        echo '<input type="hidden" name="id" value="'. s($this->cm->id) .'" />'."\n";
        echo '<input type="hidden" name="catid" value="'. s($catid) .'" />'."\n";
        echo '<input type="hidden" name="page" value="'. s($page) .'" />'."\n";
        choose_from_menu($sortopt, "sort", $sort, "", "glrSort.submit()");
        choose_from_menu($diropt, "dir", $dir, "", "glrSort.submit()");
        echo '</form></div>';

    }

    function check_dimensions ($file) {
        global $CFG;

        $maxlong  = intval($this->module->maxwidth);
        $maxshort = intval($this->module->maxheight);
        $resized  = false;

        if ( !is_object($file) ) {
            error("File parameter was not object!!");
        }

        $file->width  = intval($file->width);
        $file->height = intval($file->height);

        if ( $file->width < $file->height ) {
            $longside = 'height';
        } else {
            $longside = 'width';
        }

        switch ( $longside ) {
            case 'height':
                if ( $maxlong < $file->height ) {
                    if ( $this->module->resize ) { // Try to resize.
                        $newwidth = round($file->width / GALLERY_ASPECT_RATIO);
                        if ( !$this->resize_image($file->path, $file->path, $newwidth, $maxlong) ) {
                    @unlink($file->path);
                    error(get_string('resizefailed','imagegallery', s($file->name)),
                    "$CFG->wwwroot/mod/imagegallery/view.php?id={$this->cm->id}");
                }
                        $resized = true;
                    } else { // Do not try to resize.
                @unlink($file->path);
                error(get_string('imagewidthtoolarge','imagegallery', s($file->name)),
                      "$CFG->wwwroot/mod/imagegallery/view.php?id={$this->cm->id}");
            }
        }
            break;
            default:
                if ( $maxlong < $file->width ) {
            if ( $this->module->resize ) {
                        $newheight = round($file->height / GALLERY_ASPECT_RATIO);
                        if ( !$this->resize_image($file->path, $file->path, $maxlong, $newheight) ) {
                    @unlink($file->path);
                    error(get_string('resizefailed','imagegallery', s($file->name)),
                          "$CFG->wwwroot/mod/imagegallery/view.php?id={$this->cm->id}");
                }
                        $resized = true;
            } else {
                @unlink($file->path);
                        error(get_string('resizefailed','imagegallery', s($file->name)),
                  "$CFG->wwwroot/mod/imagegallery/view.php?id={$this->cm->id}");
            }
        }
        }

        if ( $resized ) {
            $fileinfo = getimagesize($file->path);
            $file->width  = (int) $fileinfo[0];
            $file->height = (int) $fileinfo[1];
        }

    }

    /**
    * Returns true if new image was created, else false
    * If newheight is not specified then image is scaled in proportion
    * to newwidth.  If newheight is specified, then newheight and newwidth
    * represent a bounding box to fit image into
    *
    * @param resource $image
    * @param resource $newimage
    * @param int $newwidth
    * @param int $newheight
    * @return bool
    */
    function resize_image ($image, $newimage, $newwidth, $newheight=0) {
        return $this->gd_resize_image($image, $newimage, $newwidth, $newheight);
    }

    function read_image_from_file ($filename, $type) {
        $imagetypes = ImageTypes();

        switch ($type) {
            case 1 :
                if ($imagetypes & IMG_GIF) {
                    return $im = ImageCreateFromGIF($filename);
                }
            break;
            case 2 :
                if ($imagetypes & IMG_JPEG) {
                    return ImageCreateFromJPEG($filename);
                }
                break;
            case 3 :
                if ($imagetypes & IMG_PNG)
                    return ImageCreateFromPNG($filename);
                break;
            default:
            return 0;
        }
    }

    function write_image_to_file ($im, $filename, $type) {
        switch ($type) {
            case 1 : return ImageGIF($im, $filename); break;
            case 2 : return ImageJpeg($im, $filename, GALLERY_IMAGE_QUALITY); break;
            case 3 : return ImagePNG($im, $filename); break;
            default: return false;
        }
    }

    function gd_resize_image($image, $newimage, $newwidth, $newheight) {

        $gdversion = check_gd_version();

        if ( $gdversion < 1 ) {
            return false;
        }

        $size = getimagesize($image);
        $width  = $size[0];
        $height = $size[1];
        $type   = $size[2];

        if ($im = $this->read_image_from_file($image, $type)) {
            if ($newheight && ($width < $height)) {
                $newwidth = ($newheight / $height) * $width;
            } else {
                $newheight = ($newwidth / $width) * $height;
            }

            if ($gdversion > 1) {
                $im2 = ImageCreateTrueColor($newwidth,$newheight);
            } else {
                $im2 = ImageCreate($newwidth,$newheight);
            }

            if ($gdversion > 1) {
                ImageCopyResampled($im2,$im,0,0,0,0,$newwidth,$newheight,$width,$height);
            } else {
                ImageCopyResized($im2,$im,0,0,0,0,$newwidth,$newheight,$width,$height);
            }

            if ($this->write_image_to_file($im2, $newimage, $type)) {
                return true;
            }
        }
        return false;
    }

    /**
    * Create a thumbnail from image.
    *
    * @param string $srcimage Full path to image.
    * @param string $destimage Full path to destination dir.
    * @return bool
    */
    function make_thumbnail ( $srcimage, $destimage ) {

        $orig = getimagesize($srcimage);
        $width  = $orig[0];
        $height = $orig[1];
        $ratio  = ($width / $height);

        $maxwidth  = 80;
        $maxheight = 80;

        if ($width > $maxwidth) {
            $width  = $maxwidth;
            $height = round($width / $ratio);
        }

        if ($height > $maxheight) {
            $height = $maxheight;
            $width  = round($height * $ratio);
        }

        if ( !$this->resize_image($srcimage, $destimage, $width, $height) ) {
            return false;
        }

        // Create a dropshadow if requested.
        if ( $this->module->shadow ) {
            if ( $thumb = $this->read_image_from_file($destimage, $orig[2]) ) {
                $newimage = (check_gd_version() > 1) ? imagecreatetruecolor($width+4, $height+4) :
                                                       imagecreate($width+4, $height+4);

                $white  = imagecolorallocate($newimage, 255, 255, 255);
                $shadow = imagecolorallocate($newimage, 128, 128, 128);
                imagefill($newimage, 0, 0, $white);
                imagefilledrectangle($newimage, 2, 2, $width+2, $height+2,$shadow);
                if ( function_exists('imagefilter') ) {
                    // Call it twice to get nice blur effect.
                    if ( function_exists('imagefilter') ) {
                        imagefilter($newimage, IMG_FILTER_GAUSSIAN_BLUR);
                        imagefilter($newimage, IMG_FILTER_GAUSSIAN_BLUR);
                    }
                }
                imagecopy($newimage, $thumb, 0, 0, 0, 0, $width, $height);
                $this->write_image_to_file($newimage, $destimage, $orig[2]);
            }
        }

        return true;
    }

    function get_images_listing($catid, $sort='name', $dir='ASC', $page=0, $fields='*') {

        global $CFG;

        $limit = sql_paging_limit($page, $this->module->imagesperpage);

        $select  = 'galleryid = '. $this->module->id .' AND ';
        $select .= 'categoryid = '. intval($catid);

        if ($sort) {
            if ( $sort == 'date' ) {
                $sort = 'timemodified';
            }
            $sort = ' ORDER BY '. $sort .' '. $dir;
        }
        $fields = 'id, galleryid, categoryid, userid, name, size, '.
                  'mime, width, height, path, description';

        return get_records_sql("SELECT $fields
                                FROM
                                  {$CFG->prefix}imagegallery_images
                                WHERE $select $sort $limit");

    }

    function count_images ($catid=0, $all=false) {

        $catid = intval($catid);

        if ( $all ) { // Retrun count of all images in this gallery.
            return count_records("imagegallery_images", "galleryid", $this->module->id);
        }

        return count_records("imagegallery_images", "galleryid", $this->module->id,
                             "categoryid", $catid);
    }

    function get_file_path($fullpath, $full=false) {
        global $CFG;
        if ( $full ) {
            return $fullpath;
        }
        $fullpath = str_replace($CFG->dataroot, "", $fullpath);
        $fullpath = str_replace(addslashes($CFG->dataroot), "", $fullpath);
        if ( !preg_match("/^\//", $fullpath) ) {
            $fullpath = '/'. $fullpath;
        }
        return $fullpath;
    }

    function create_overlib_string( $image ) {

        if ( !is_object($image) ) {
            return NULL;
        }

        $caption = s($image->name);
        $text = '<table border=\'0\'>';
        $text .= '<tr><td align=\'right\'>'. get_string('size') .':</td>';
        $text .= '<td>' . display_size($image->size) .'</td></tr>';
        $text .= '<tr><td align=\'right\'>' . get_string('width','imagegallery') .': </td>';
        $text .= '<td>'. s($image->width) .'px</td></tr>';
        $text .= '<tr><td align=\'right\'>' . get_string('height','imagegallery') .': </td>';
        $text .= '<td>'. s($image->height) .'px</td></tr></table>';

        $returnstr  = " onmouseover=\"return overlib('". addslashes($text) ."',CAPTION,'$caption',WRAP,";
        $returnstr .= "FGCLASS,'mod_ig_fgclass',CGCLASS,'mod_ig_cgclass',BGCLASS,'mod_ig_bgclass');\" ";
        $returnstr .= "onmouseout=\"nd();\"";

        return $returnstr;

    }

    function print_notify_page ( $arrnotices ) {

        print_heading(get_string('error'));

        echo '<ol id="imagegallery_notice">'."\n";
        foreach ( $arrnotices as $notice ) {
            echo '<li>';
            echo $notice;
            echo '</li>' ."\n";
        }
        echo '</ol>';

    }

    function print_prev_back_next (&$images, &$image, $page=0, $sort='name', $dir='asc') {

        global $CFG;
        // Clean parameters.
        $imageid   = intval($image->id);
        $catid     = intval($image->categoryid);
        $galleryid = intval($image->galleryid);

        // Find previous and next from images array.
        $keys = array_keys($images);
        $current  = array_search($imageid, $keys, true);
        $previous = !empty($keys[$current-1]) ? $keys[$current-1] : null;
        $next     = !empty($keys[$current+1]) ? $keys[$current+1] : null;

        // TODO prev and next links.
        $prevlink = '';
        if ( !empty($previous) && !is_null($previous) ) {
            $previmage = $images[$previous];
            $prevlink = '<a href="details.php?id='. $this->cm->id .
                        '&amp;catid='. $previmage->categoryid .
                        '&amp;image='. $previmage->id .
                        '&amp;page='. $page .'&amp;sort='. $sort .
                        '&amp;dir='. $dir .'">'.
                        get_string('previous','imagegallery') .'</a>';
        }
        $nextlink = '';
        if ( !empty($next) && !is_null($next) ) {
            $nextimage = $images[$next];
            $nextlink = '<a href="details.php?id='. $this->cm->id .
                        '&amp;catid='. $nextimage->categoryid .
                        '&amp;image='. $nextimage->id .
                        '&amp;page='. $page .'&amp;sort='. $sort .
                        '&amp;dir='. $dir .'">'.
                        get_string('next','imagegallery') .'</a>';
        }

        $backlink = '<a href="view.php?id='. $this->cm->id .
                    '&amp;catid='. $image->categoryid .
                    '&amp;page='. $page .'&amp;sort='. $sort .
                    '&amp;dir='. $dir .'">'. get_string('back','imagegallery') .
                    '</a>';

        echo '<div id="mod_ig_navibar">';
        if ( !empty($prevlink) ) {
            echo '&laquo; '. $prevlink;
        }
        echo ' '. $backlink .' ';
        if ( !empty($nextlink) ) {
            echo $nextlink . ' &raquo;';
        }
        echo '</div>';
    }

}

?>
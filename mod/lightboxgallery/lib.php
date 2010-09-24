<?php

require_once($CFG->libdir . '/gdlib.php');
require_once($CFG->libdir . '/filelib.php');

define('THUMB_WIDTH', 120);
define('THUMB_HEIGHT', 105);
define('MAX_IMAGE_LABEL', 14);

define('AUTO_RESIZE_SCREEN', 1);
define('AUTO_RESIZE_UPLOAD', 2);
define('AUTO_RESIZE_BOTH', 3);

if (! get_config('lightboxgallery', 'disabledplugins')) {
    set_config('disabledplugins', '', 'lightboxgallery');
}

if (! get_config('lightboxgallery', 'enablerssfeeds')) {
    set_config('enablerssfeeds', 0, 'lightboxgallery');
}

function lightboxgallery_add_instance($gallery) {
    global $CFG;

    if (! lightboxgallery_rss_enabled()) {
        $gallery->rss = 0;
    }
   
    $gallery->timemodified = time();

    return insert_record('lightboxgallery', $gallery);
}


function lightboxgallery_update_instance($gallery) {
    global $CFG;

    $gallery->id = $gallery->instance;

    if (! lightboxgallery_rss_enabled()) {
        $gallery->rss = 0;
    }

    if ($gallery->autoresizedisabled) {
        $gallery->autoresize = 0;
        $gallery->resize = 0;
    }

    $gallery->timemodified = time();

    return update_record('lightboxgallery', $gallery);
}


function lightboxgallery_delete_instance($id) {
    if ($gallery = get_record('lightboxgallery', 'id', $id)) {
        $result = true;

        $result = $result && delete_records('lightboxgallery', 'id', $gallery->id);
        $result = $result && delete_records('lightboxgallery_comments', 'gallery', $gallery->id);
        $result = $result && delete_records('lightboxgallery_image_meta', 'gallery', $gallery->id);

    } else {
        $result = false;
    }

    return $result;
}

function lightboxgallery_user_outline($course, $user, $mod, $resource) {
    if ($logs = get_records_select('log', "userid='$user->id' AND module='lightboxgallery' AND action='view' AND info='$resource->id'", 'time ASC')) {
        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $result = new object;
        $result->info = get_string('numviews', '', $numviews);
        $result->time = $lastlog->time;
        return $result;
    } else {
        return null;
    }
}

function lightboxgallery_user_complete($course, $user, $mod, $resource) {
    if ($logs = get_records_select('log', "userid='$user->id' AND module='lightboxgallery' AND action='view' AND info='$resource->id'", 'time ASC')) {
        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $strmostrecently = get_string('mostrecently');
        $strnumviews = get_string('numviews', '', $numviews);

        echo($strnumviews . ' - ' . $strmostrecently . ' ' . userdate($lastlog->time));
    } else {
        print_string('neverseen', 'resource');
    }
}

function lightboxgallery_get_participants($lightboxgalleryid) {
    return false;
}

function lightboxgallery_get_coursemodule_info($coursemodule) {
   $info = null;

   if ($gallery = get_record('lightboxgallery', 'id', $coursemodule->instance)) {
       $info->extra = urlencode($gallery->name);
   }

   return $info;
}

function lightboxgallery_get_view_actions() {
    return array('view', 'view all', 'search');
}

function lightboxgallery_get_post_actions() {
    return array('comment', 'addimage', 'editimage');
}

function lightboxgallery_get_types() {
    $types = array();

    $type = new object;
    $type->modclass = MOD_CLASS_RESOURCE;
    $type->type = 'lightboxgallery';
    $type->typestr = get_string('modulenameadd', 'lightboxgallery');
    $types[] = $type;

    return $types;
}

// Custom lightboxgallery methods

function lightboxgallery_allowed_filetypes() {
    return array('jpg', 'jpeg', 'gif', 'png');
}

function lightboxgallery_allowed_filetype($element) {
    $extension = strtolower(substr(strrchr($element, '.'), 1));
    return in_array($extension, lightboxgallery_allowed_filetypes());
}

function lightboxgallery_directory_images($directory) {
    $files = get_directory_list($directory, '', false, false, true);
    return array_filter($files, 'lightboxgallery_allowed_filetype');
}

function lightboxgallery_get_image_url($galleryid, $image = false, $thumb = false) {
    global $CFG;

    $script = $CFG->wwwroot . '/mod/lightboxgallery/pic.php';
    $path = $galleryid . ($image ? '/' . rawurlencode($image) : '');
    if ($CFG->slasharguments) {
        $url = $script . '/' . $path . ($thumb ? '?thumb=1' : '');
    } else {
        $url = $script . '?file=/' . $path . ($thumb ? '&amp;thumb=1' : '');
    }
    return $url;
}

function lightboxgallery_make_img($path, $imageid = '') {
    return '<img src="' . $path . '" alt="" id="' . $imageid . '" />';
}

function lightboxgallery_imagecreatefromtype($type, $path) {
    switch ($type) {
        case 1:
            $function = 'ImageCreateFromGIF';
            break;
        case 2:
            $function = 'ImageCreateFromJPEG';
            break;
        case 3:
            $function = 'ImageCreateFromPNG';
            break;
    }
    if (function_exists($function)) {
        return $function($path);
    } else {
        return false;
    }
}

function lightboxgallery_image_create($width, $height) {
    global $CFG;
    if (function_exists('ImageCreateTrueColor') and $CFG->gdversion >= 2) {
        return ImageCreateTrueColor($width, $height);
    } else {
        return ImageCreate($width, $height);
    }
}

function lightboxgallery_resize_image($image, $infoobj, $width, $height, $offsetx = 0, $offsety = 0) {
    global $CFG;

    $resized = lightboxgallery_image_create($width, $height);

    $oldwidth = $infoobj->imagesize[0];
    $oldheight = $infoobj->imagesize[1];

    $cx = $oldwidth / 2;
    $cy = $oldheight / 2;

    $ratiow = $width / $oldwidth;
    $ratioh = $height / $oldheight;

    if ($ratiow < $ratioh) {
        $srcw = floor($width / $ratioh);
        $srch = $oldheight;
        $srcx = floor($cx - ($srcw / 2)) + $offsetx;
        $srcy = $offsety;
    } else {
        $srcw = $oldwidth;
        $srch = floor($height / $ratiow);
        $srcx = $offsetx;
        $srcy = floor($cy - ($srch / 2)) + $offsety;
    }

    ImageCopyBicubic($resized, $image, 0, 0, $srcx, $srcy, $width, $height, $srcw, $srch);

    return $resized;
}

function lightboxgallery_image_thumbnail($courseid, $gallery, $file, $offsetx = 0, $offsety = 0) {
    global $CFG;

    // If anything fails when retrieving the thumbnail, we'll fallback to just printing a label
    $fallback = '['.$file.']';

    $oldpath = $CFG->dataroot.'/'.$courseid.'/'.$gallery->folder.'/'.$file;
    $newpath = $courseid.'/'.$gallery->folder.'/_thumb/'.$file.'.jpg';

    if (empty($CFG->gdversion)) {
        return $fallback;
    }

    umask(0000);

    if (file_exists($CFG->dataroot.'/'.$newpath)) {
        return lightboxgallery_make_img(lightboxgallery_get_image_url($gallery->id, $file, true));
    } else {
        $thumbdir = $CFG->dataroot.'/'.dirname($newpath);
        if (!file_exists($thumbdir) && !mkdir($thumbdir, $CFG->directorypermissions)) {
            return $fallback;
        }
    }

    $info = lightboxgallery_image_info($oldpath);

    if (! $im = lightboxgallery_imagecreatefromtype($info->imagesize[2], $oldpath)) {
        return $fallback;
    }

    $thumb = lightboxgallery_resize_image($im, $info, THUMB_WIDTH, THUMB_HEIGHT, $offsetx, $offsety);

    if (function_exists('ImageJpeg')) {
        @touch($CFG->dataroot.'/'.$newpath);
        if (ImageJpeg($thumb, $CFG->dataroot.'/'.$newpath, 90)) {
            @chmod($CFG->dataroot.'/'.$newpath, 0666);
            return lightboxgallery_make_img(lightboxgallery_get_image_url($gallery->id, $file, true));
        }
    } else {
        return $fallback;
    }
}

function lightboxgallery_print_comment($comment, $context) {
    global $CFG, $COURSE;

    $user = get_record('user', 'id', $comment->userid);

    echo('<table cellspacing="0" align="center" width="50%" class="datacomment forumpost">');

    echo('<tr class="header"><td class="picture left">');
    print_user_picture($user->id, $COURSE->id, $user->picture);
    echo('</td>');

    echo('<td class="topic starter" align="left"><div class="author">');
    echo('<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$COURSE->id.'">' . fullname($user, has_capability('moodle/site:viewfullnames', $context)) . '</a> - '.userdate($comment->timemodified));
    echo('</div></td></tr>');

    echo('<tr><td class="left side">');
    if ($groups = user_group($COURSE->id, $user->id)) {
        print_group_picture($groups, $COURSE->id, false, false, true);
    } else {
        echo('&nbsp;');
    }

    echo('</td><td class="content" align="left">');

    echo(format_text($comment->comment, FORMAT_MOODLE));

    echo('<div class="commands">');
    if (has_capability('mod/lightboxgallery:edit', $context)) {
        echo('<a href="'.$CFG->wwwroot.'/mod/lightboxgallery/comment.php?id='.$comment->gallery.'&amp;cid='.$comment->id.'&amp;action=delete">'.get_string('delete').'</a>');
    }
    echo('</div>');

    echo('</td></tr></table>');
}

function lightboxgallery_image_modified($file) {
    $timestamp = 0;
    if (function_exists('exif_read_data') && $exif = @exif_read_data($file, 0, true)) {
        if (isset($exif['IFD0']['DateTime'])) {
            $date = preg_split('/[:]|[ ]/', $exif['IFD0']['DateTime']);
            $timestamp = mktime($date[3], $date[4], $date[5], $date[1], $date[2], $date[0]);
        }
    }
    if (! $timestamp > 0) {
        $timestamp = filemtime($file);
    }
    return date('d/m/y H:i', $timestamp);
}

function lightboxgallery_image_info($file) {
    $result = new object;
    $result->filesize  = display_size(filesize($file));
    $result->modified  = lightboxgallery_image_modified($file);
    $result->imagesize = getimagesize($file);
    return $result;
}

function lightboxgallery_set_image_caption($galleryid, $image, $caption) {
    if ($oldcaption = get_record('lightboxgallery_image_meta', 'metatype', 'caption', 'gallery', $galleryid, 'image', $image)) {
        $oldcaption->description = $caption;
        return update_record('lightboxgallery_image_meta', $oldcaption);
    } else {
        $newcaption = new object;
        $newcaption->gallery = $galleryid;
        $newcaption->image = $image;
        $newcaption->metatype = 'caption';
        $newcaption->description = $caption;
        return insert_record('lightboxgallery_image_meta', $newcaption);
    }
}

function lightboxgallery_edit_types($showall = false) {
    global $CFG;

    $result = array();

    $disabledplugins = explode(',', get_config('lightboxgallery', 'disabledplugins'));

    $edittypes = get_directory_list($CFG->dirroot . '/mod/lightboxgallery/edittype/', '', false, true, false);
    foreach ($edittypes as $edittype) {
        if ($showall || !in_array($edittype, $disabledplugins)) {
            $result[$edittype] = get_string('edit_' . $edittype, 'lightboxgallery');
        }
    }

    return $result;
}

function lightboxgallery_print_tags($heading, $tags, $courseid, $galleryid) {
    global $CFG;

    print_simple_box_start('center');

    echo('<form action="search.php" style="float: right; margin-left: 4px;">' .
         ' <input type="hidden" name="id" value="' . $courseid . '" />' .
         ' <input type="hidden" name="l" value="' . $galleryid . '" />' .
         ' <input type="text" name="search" size="8" />' .
         ' <input type="submit" value="' . get_string('search') . '" />' . 
         '</form>');

    echo($heading . ': ');
    $tagarray = array();
    foreach ($tags as $tag) {
        $tagarray[] = '<a class="taglink" href="' . $CFG->wwwroot . '/mod/lightboxgallery/search.php?id=' . $courseid . '&amp;l=' . $galleryid . '&amp;search=' . urlencode($tag->description) . '">' . $tag->description . '</a>';
    }
    echo(implode(', ', $tagarray));

    print_simple_box_end();
}

function lightboxgallery_resize_options() {
    return array(1 => '1280x1024', 2 => '1024x768', 3 => '800x600', 4 => '640x480');
}

function lightboxgallery_rss_enabled() {
    global $CFG;
    return ($CFG->enablerssfeeds && get_config('lightboxgallery', 'enablerssfeeds'));
}

function lightboxgallery_print_js_config($autoresize) {
    $resizetoscreen = (int)in_array($autoresize, array(AUTO_RESIZE_SCREEN, AUTO_RESIZE_BOTH));

    $jsconf = array('resizetoscreen' => $resizetoscreen, 'download' => get_string('imagedownload', 'lightboxgallery'));
    $jsconfvalues = array();

    foreach ($jsconf as $key => $value) {
        $jsconfvalues[] = "$key: '$value'";
    }

    echo('<script type="text/javascript">
            //<![CDATA[
              lightboxgallery_config = {' . implode(', ', $jsconfvalues) . '};
            //]]>
          </script>');
}

?>

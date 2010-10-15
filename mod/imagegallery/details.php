<?php  // $Id: details.php,v 1.7.2.1 2006/12/05 10:05:29 janne Exp $

/// This page prints a particular instance of learningdiary

    require_once("../../config.php");
    require_once("lib.php");

    $imageid = required_param('image', PARAM_INT);
    $catid   = required_param('catid', PARAM_INT);
    $edit    = optional_param('edit', '', PARAM_ALPHA);
    $page    = optional_param('page', 0, PARAM_INT);
    $sort    = optional_param('sort', 'name', PARAM_ALPHA);
    $dir     = optional_param('dir', 'asc', PARAM_ALPHA);

    $gallery = new modImagegallery(); // Instantiate imagegallery object.

    $strimagegalleries = get_string("modulenameplural", "imagegallery");
    $strimagegallery   = get_string("modulename", "imagegallery");

    if ( !$images = $gallery->get_images_listing($catid, $sort, $dir,
                                                 $page*$gallery->module->imagesperpage) ) {
        error("Images not found!", "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
    }

    $image = null;
    if ( !empty($images[$imageid]) ) {
        $image = $images[$imageid];
    } else {
        error("Image not found!!!!",
              "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
    }

    if ( $data = data_submitted() ) {
        $sesskey = required_param('sesskey', PARAM_ALPHANUM);
        $onerrorurl  = "$CFG->wwwroot/mod/imagegallery/details.php?id=";
        $onerrorurl .= "{$gallery->cm->id}&amp;image={$image->id}&amp;";
        $onerrorurl .= "page=$page&amp;sort=$sort&amp;dir=$dir";

        $file = new stdClass;
        $file->name = clean_filename($data->name);
        $file->path = dirname($image->path) . '/'. $file->name;
        $file->width = clean_param($data->width, PARAM_INT);
        $file->height = clean_param($data->height, PARAM_INT);
        $file->description = addslashes(trim(strip_tags($data->description)));

        // Check if file name chanched and does it already exist.
        // Let's keep it windows way where case doesn't matter.
        if ( strtolower($image->name) != strtolower($file->name) ) {
            // File name changed
            if ( @file_exists($file->path) ) {
                error("File $file->name already exists!!!!", $onerrorurl);
            }
            // Rename file and thumbnail.
            $oldthumbnail = $CFG->dataroot . dirname($image->path) .'/thumb_'. $image->name;
            $newthumbnail = $CFG->dataroot . dirname($image->path) .'/thumb_'. $file->name;
            $oldfile = $CFG->dataroot . $image->path;
            $newfile = $CFG->dataroot . $file->path;
            if ( !rename($oldfile, $newfile) ) {
                error("Couldn't rename $image->name to $file->name !!!", $onerrorurl);
            }

            if ( !rename($oldthumbnail, $newthumbnail) ) {
                @rename($newfile, $oldfile);
                $strimage = basename($oldthumbnail);
                error("Couldn't rename {$strname}!!!", $onerrorurl);
            }
        }

        // Check if dimensions have changed.
        if ( intval($image->width) != intval($file->width) or
             intval($image->height) != intval($file->height) ) {
            $filepath = $CFG->dataroot . $file->path;
            if ( !$gallery->resize_image($filepath,  $filepath,
                                         $file->width, $file->height) ) {
                error("Couldn't resize {$file->name}!!!", $onerrorurl);
            }
        }

        // Update database.
        $file->id = $image->id;
        $file->timemodified = time();
        if ( !update_record("imagegallery_images", $file) ) {
            // Try to rollback changes. For dimensions we
            // cannot do much about or there might be massive
            // quality change if we resize again, so leave it be.
            if ( !empty($oldthumbnail) ) {
                @rename($newthumbnail, $oldthumbnail);
            }
            if ( !empty($oldfile) ) {
                @rename($newfile, $oldfile);
            }
            error("Could not update database!!!", $onerrorurl);
        }

        // Retrieve fresh record of image
        if ( !$image = get_record("imagegallery_images", "id", $image->id ) ) {
            error("Could not fetch fresh image information!!!", $onerrorurl);
        }
    }

    $navigation = '';
    if ($gallery->course->category) {
        $navigation = "<a href=\"../../course/view.php?id={$gallery->course->id}\">{$gallery->course->shortname}</a> ->";
    }

    $navigation .= " <a href=\"index.php?id={$gallery->course->id}\">$strimagegalleries</a> ->".
                   " <a href=\"view.php?id={$gallery->cm->id}&amp;catid={$image->categoryid}&amp;page=$page".
                   "&amp;sort=$sort&amp;dir=$dir\">{$gallery->module->name}</a> -> " . s($image->name);

    if ($gallery->user_allowed_editing()) {
        if ($edit == 'on') {
            $USER->editing = true;
        } else if ($edit == 'off') {
            $USER->editing = false;
        }
        $stredit   = !empty($USER->editing) ? get_string('turneditingoff') : get_string('turneditingon');
        $editvalue = !empty($USER->editing) ? 'off' : 'on';

        $buttons  = '<table><tr><td><form method="get" action="details.php" target="_self">';
        $buttons .= '<input type="hidden" name="id" value="'. s($gallery->cm->id) .'" />';
        $buttons .= '<input type="hidden" name="edit" value="'. $editvalue .'" />';
        $buttons .= '<input type="hidden" name="catid" value="'. s($image->categoryid) .'" />';
        $buttons .= '<input type="hidden" name="image" value="'. s($image->id) .'" />';
        $buttons .= '<input type="hidden" name="page" value="'. s($page) .'" />';
        $buttons .= '<input type="hidden" name="sort" value="'. s($sort) .'" />';
        $buttons .= '<input type="hidden" name="dir" value="'. s($dir) .'" />';
        $buttons .= '<input type="submit" value="'. $stredit .'" /></form></td><td>';
        $buttons .= update_module_button($gallery->cm->id, $gallery->course->id, $strimagegallery);
        $buttons .= '</td></tr></table>';
    }

    if ( empty($buttons) ) {
        $buttons = update_module_button($gallery->cm->id, $gallery->course->id, $strimagegallery);
    }

    print_header("{$gallery->course->shortname}: {$gallery->module->name}", "{$gallery->course->fullname}",
                 "$navigation", "", "", true,
                 $buttons,
                 navmenu($gallery->course, $gallery->cm));

    print_heading(s($image->name));
    //echo '<div style="text-align: center;">&laquo; Edellinen Takaisin Seuraava &raquo;</div>'."\n";
    $gallery->print_prev_back_next($images, $image, $page, $sort, $dir);

    print_simple_box_start("center");

    ?>
    <table border="0" cellpadding="4" class="mod_ig_infotable">
    <tr valign="top">
        <td align="center"><div style="width: <?php p($image->width) ?>px; height: <?php p($image->height) ?>px;">
        <img id="changing" name="changing"
        src="image.php?id=<?php p($image->id) ?>" width="<?php p($image->width) ?>"
        height="<?php p($image->height) ?>" alt="<?php p($image->name) ?>" title="<?php p($image->name) ?>" />
        </div></td>
    </tr>
    <?php if ( !$USER->editing && !empty($image->description) ) { ?>
    <tr>
        <td><fieldset><legend><?php print_string('description') ?></legend>
        <?php echo(format_text($image->description, FORMAT_MOODLE)) ?>
        </fieldset></td>
    </tr>
    <?php } ?>
    <tr>
    <?php
        if ( $USER->editing && $gallery->user_allowed_editing() ) {
            ?>
        <td>
        <div class="mod_ig_warning"><?php print_string('dimensionchangewarnging','imagegallery') ?></div>
        <form name="imageval" method="post" action="details.php">
        <input type="hidden" name="id" value="<?php p($gallery->cm->id) ?>" />
        <input type="hidden" name="sesskey" value="<?php p($USER->sesskey) ?>" />
        <input type="hidden" name="catid" value="<?php p($image->categoryid) ?>" />
        <input type="hidden" name="image" value="<?php p($image->id) ?>" />
        <input type="hidden" name="page" value="<?php p($page) ?>" />
        <input type="hidden" name="sort" value="<?php p($sort) ?>" />
        <input type="hidden" name="dir" value="<?php p($dir) ?>" />
    <table border="0" cellpadding="4">
    <tr valign="top">
            <td align="right"><?php print_string('name') ?>: </td>
            <td><input type="text" name="name" value="<?php p($image->name) ?>" /></td>
        </tr>
        <tr valign="top">
            <td align="right"><?php print_string('imagesize','imagegallery') ?>: </td>
            <td>
            <table border="0">
            <tr>
                <td><input type="text" name="width" size="3" maxlength="4"
                value="<?php p($image->width) ?>" onchange="iGnewHeight(''+ document.imageval.width.value +'');" /></td>
                <td> x </td>
                <td><input type="text" name="height" size="3" maxlength="4"
                value="<?php p($image->height) ?>" onchange="iGnewWidth(''+ document.imageval.height.value +'');" /></td>
            </tr>
            <!-- <tr>
                <td align="center"><input id="igBtnUp" type="button" value=" &uarr; " onclick="void(iGchangeSize('up'));"
                title="<?php print_string('increasesize','imagegallery') ?>" /></td>
                <td>&nbsp;</td>
                <td align="center"><input type="button" value=" &darr; " onclick="void(iGchangeSize('down'));"
                title="<?php print_string('decreasesize','imagegallery') ?>" /></td>
            </tr> -->
            </table>
            </td>
        </tr>

        <tr valign="top">
            <td align="right"><?php print_string('description') ?>: </td>
            <td><?php print_textarea(false, "3", "40", "", "", "description",
                                     $image->description,
                                     $gallery->course->id); ?></td>
    </tr>
    <tr>
            <td>&nbsp;</td>
            <td><input type="submit" value="<?php print_string('savechanges') ?>" /></td>
        </tr>
        </table>
        </form>
        </td>
            <?php
        } else {
            ?>
        <td><fieldset><legend><?php print_string('imageinfo','imagegallery') ?></legend>
        <p><?php print_string('width','imagegallery') ?>: <?php p($image->width) ?> px,
        <?php print_string('height','imagegallery') ?>: <?php p($image->height) ?> px,
        <?php print_string('filesize','imagegallery') ?>: <?php echo display_size($image->size) ?></p>
        </fieldset></td>
        <?php
        } ?>
    </tr>

    </table>
    <?php

    print_simple_box_end();
    if ( $USER->editing && $gallery->user_allowed_editing() ) {
        echo "\n";
        ?>
<script language="javascript" type="text/javascript">
<!--
var igMaxWidth  = <?php echo ($image->width < $image->height) ? p($gallery->module->maxheight) : p($gallery->module->maxwidth) ?>;
var igMaxHeight = <?php echo ($image->height < $image->width) ? p($gallery->module->maxheight) : p($gallery->module->maxwidth) ?>;
var igMinWidth  = 50;
var igMinHeight = 50;
var ratio       = 0;

function ig_set_orig () {
    origwidth  = <?php echo $image->width; ?>;
    origheight = <?php echo $image->height; ?>;
    ratio = (origwidth / origheight);
}

function iGnewWidth(height) {
    var nw = Math.round(height * ratio);
    if (nw <= igMaxWidth && nw >= igMinWidth) {
        document.imageval.width.value = nw;
        iGresizeImage(nw, height);
    } else {
        alert("Image maxwidth is: " + igMaxWidth +
              ". And minwidth is: " + igMinWidth);
    }
}

function iGnewHeight(width) {
    var nh = Math.round(width / ratio);
    if (nh <= igMaxHeight && nh >= igMinHeight) {
        document.imageval.height.value = nh;
        iGresizeImage(width, nh);
    } else {
        alert("Image maxheight is: " + igMaxWidth +
              ". And minheight is: " + igMinWidth);
    }
}
function iGresizeImage(width, height) {
    document.changing.width  = parseInt(width);
    document.changing.height = parseInt(height);
}
function iGrestore () {
    document.imageval.width.value  = origwidth;
    document.imageval.height.value = origheight;
}
function iGchangeSize (dir) {

    var h = parseInt(document.imageval.height.value);

    if (dir == 'up') {
        h++;
        iGnewWidth(h);
        var w = parseInt(document.imageval.width.value);
        iGnewHeight(w);
    }

    if (dir == 'down') {
        h--;
        iGnewWidth(h);
        var w = parseInt(document.imageval.width.value);
        iGnewHeight(w);
    }

}
function iGchange_to_max () {

    if (document.getElementById) {
        var el = document.getElementById('changing');
    } else if (document.all) {
        var el = document.all('changing');
    }

    if (el == null) {
        return false;
    }

    var width  = iGgetWidth(el);
    var height = iGgetHeight(el);

    /*if (height > igMaxHeight) {
        var nw = iGnewWidth(igMaxHeight);
        document.imageval.width.value  = nw;
        document.imageval.height.value = igMaxHeight;
        el.width = nw;
        el.height = igMaxHeight;
    }

    if (width > igMaxWidth) {
        var nh = iGnewHeight(igMaxWidth);
        document.imageval.width.value  = igMaxWidth;
        document.imageval.height.value = nh;
        el.width = igMaxWidth;
        el.height = nh;
    }*/
    document.imageval.width.value  = ( igMaxWidth < width ) ? igMaxWidth : width;
    document.imageval.height.value = ( igMaxHeight < height ) ? igMaxHeight: height;
    //el.width  = ( igMaxWidth < width ) ? igMaxWidth : width;
    //el.height = ( igMaxHeight < height ) ? igMaxHeight: height;

}

function iGgetWidth (el) {

    var result = 0;

    if (el.offsetWidth) {
        result = el.offsetWidth;
    } else if (el.clip && el.clip.width) {
        result = el.clip.width;
    } else if (el.style && el.style.pixelWidth) {
        result = el.style.pixelWidth;
    }

    return parseInt(result);

}

function iGgetHeight (el) {

    var result = 0;

    if (el.offsetHeight) {
        result = el.offsetHeight;
    } else if (el.clip && el.clip.height) {
        result = el.clip.height;
    } else if (el.style && el.style.pixelHeight) {
        result = el.style.pixelHeight;
    }

    return parseInt(result);
}

    ig_set_orig();
    iGchange_to_max();
    //set();

// done hiding -->
</script>
        <?php
        echo "\n";
    }
    print_footer($gallery->course);

?>
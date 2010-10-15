<?php // $Id: imagebank.php,v 1.3 2007/04/27 09:10:51 janne Exp $

//  Manage netpublishes images uploaded by users

    require("../../config.php");

    if (file_exists($CFG->dirroot. '/files/mimetypes.php')) {
        require("../../files/mimetypes.php");
    } else {
        require($CFG->libdir .'/filelib.php');
    }

    $id      = required_param('id', PARAM_INT);
    $file    = optional_param('file', '', PARAM_PATH);
    $wdir    = optional_param('wdir', '', PARAM_PATH);
    $action  = optional_param('action', '', PARAM_ACTION);
    $name    = optional_param('name', '', PARAM_FILE);
    $oldname = optional_param('oldname', '', PARAM_FILE);
    $fileid  = optional_param('fileid', 0, PARAM_INT);
    $usecheckboxes  = optional_param('usecheckboxes', 1, PARAM_INT);

    if (! $course = get_record("course", "id", $id) ) {
        error("That's an invalid course id");
    }

    require_login($course->id);

    $isteacher = has_capability('moodle/legacy:editingteacher', get_context_instance(CONTEXT_COURSE, $course->id));
    $isstudent = has_capability('moodle/legacy:student', get_context_instance(CONTEXT_COURSE, $course->id));

    //if (isguest() ) {
    //    error("Only memebers of this course can access imagebank!");
    //}

    if (!$isteacher && !$isstudent) {
        error("Only memebers of this course can access imagebank!");
    }

    function html_footer() {
        echo "\n\n</body>\n</html>";
    }

    function html_header($course, $wdir, $formfield=""){

        global $CFG;

        ?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html>
        <head>
        <meta http-equiv="content-type" content="text/html; charset=<?php print_string("thischarset");?>" />
        <title>coursefiles</title>
        <script language="javscript" type="text/javascript">
        <!--
        function set_value(params) {
            /// function's argument is an object containing necessary values
            /// to export parent window (url,isize,itype,iwidth,iheight, imodified)
            /// set values when user click's an image name.
            var upper = window.parent;
            var insimg = upper.document.getElementById('f_url');

            try {
                if(insimg != null) {
                    if(params.itype.indexOf("image/gif") == -1 &&
                       params.itype.indexOf("image/jpeg") == -1 &&
                       params.itype.indexOf("image/pjpeg") == -1 &&
                       params.itype.indexOf("image/png") == -1 &&
                       params.itype.indexOf("image/x-png") == -1) {
                        alert("<?php print_string("notimage","editor");?>");
                        return false;
                    }
                    for(field in params) {
                        var value = params[field];
                        switch(field) {
                            case "url"   : upper.document.getElementById('f_url').value = value;
                                     upper.ipreview.location.replace('imagepreview.php?id='+ <?php print($course->id);?> +'&imageurl='+ value);
                                break;
                            case "ialt"  : upper.document.getElementById('f_alt').value = value; break;
                            case "isize" : upper.document.getElementById('isize').value = value; break;
                            case "itype" : upper.document.getElementById('itype').value = value; break;
                            case "iwidth": upper.document.getElementById('f_width').value = value; break;
                            case "iheight": upper.document.getElementById('f_height').value = value; break;
                        }
                    }
                } else {
                    for(field in params) {
                        var value = params[field];
                        switch(field) {
                            case "url" :
                                //upper.document.getElementById('f_href').value = value;
                                upper.opener.document.getElementById('f_href').value = value;
                                upper.close();
                                break;
                            //case "imodified" : upper.document.getElementById('imodified').value = value; break;
                            //case "isize" : upper.document.getElementById('isize').value = value; break;
                            //case "itype" : upper.document.getElementById('itype').value = value; break;
                        }
                    }
                }
            } catch(e) {
                alert("Something odd just occurred!!!");
            }
            return false;
        }

        function set_dir(strdir) {
            // sets wdir values
            var upper = window.parent.document;
            if(upper) {
                for(var i = 0; i < upper.forms.length; i++) {
                    var f = upper.forms[i];
                    try {
                        f.wdir.value = strdir;
                    } catch (e) {

                    }
                }
            }
        }

        function set_rename(fileid) {
            var upper = window.parent.document;
            if (upper) {
                for (var i = 0; i < upper.forms.length; i++) {
                    var r = upper.forms[i];
                    try {
                        r.file.value = fileid;
                    } catch (e) {
                    }
                }
            }

            return true;
        }

        function reset_value() {
            var upper = window.parent.document;
            for(var i = 0; i < upper.forms.length; i++) {
                var f = upper.forms[i];
                for(var j = 0; j < f.elements.length; j++) {
                    var e = f.elements[j];
                    if(e.type != "submit" && e.type != "button" && e.type != "hidden") {
                        try {
                            e.value = "";
                        } catch (e) {
                        }
                    }
                }
            }

            var ren = upper.getElementById('irename');
            if(ren != null) {
                upper.irename.file.value = "";
            }
            var prev = window.parent.ipreview;
            if(prev != null) {
                prev.location.replace('about:blank');
            }
            var uploader = window.parent.document.forms['uploader'];
            if(uploader != null) {
                uploader.reset();
            }
            set_dir('<?php print(!empty($_REQUEST['wdir'])) ? $_REQUEST['wdir'] : "";?>');
            return true;
        }
        -->
        </script>
        <style type="text/css">
        <!--
        body {
            background-color: white;
            margin-top: 2px;
            margin-left: 4px;
            margin-right: 4px;
        }
        body,p,table,td,input,select,a {
            font-family: Tahoma, sans-serif;
            font-size: 11px;
        }
        select {
        position: absolute;
        top: -20px;
        left: 0px;
        }
        -->
        </style>
        </head>
        <body onload="reset_value();">

        <?php
    }

    $baseweb = $CFG->wwwroot;

    if (! $basedir = make_upload_directory("netpublish_images/$id")) {
        error("The site administrator needs to fix the file permissions");
    }



//  End of configuration and access control


    if (!$wdir) {
        $wdir="/";
    }

    if (($wdir != '/' and detect_munged_arguments($wdir, 0))
      or ($file != '' and detect_munged_arguments($file, 0))) {
        $message = "Error: Directories can not contain \"..\"";
        $wdir = "/";
        $action = "";
    }

    // ARRRGHHHH &%¤%¤%?###
    $CFG->framename = 'ibrowser';

    switch ($action) {

        case "upload":
            html_header($course, $wdir);
            require_once($CFG->dirroot.'/lib/uploadlib.php');

            if (!empty($save) and confirm_sesskey()) {
                $um = new upload_manager('userfile',false,false,$course,false,0);
                $dir = "$basedir/tmp";
                if ($um->process_file_uploads($dir)) {
                    // copy and resize file to its real
                    // location and add info to database
                    $image = new stdClass;
                    $image->mimetype = $_FILES['userfile']['type'];
                    $image->size     = $_FILES['userfile']['size'];
                    $image->name = $um->get_new_filename();
                    $image->temp = "$basedir/tmp/". $image->name;
                    $image->info = getimagesize("$basedir/tmp/" . $image->name);
                    $image->width  = (int) $image->info[0];
                    $image->height = (int) $image->info[1];
                    $image->type   = (int) $image->info[2];
                    $image->course = (int) $course->id;
                    $image->path   = "netpublish_images/nbimg_". time() .".image";

                    if ($wdir == '/') {
                        $wdir = '';
                    }

                    $image->dir    = addslashes($wdir);
                    $image->fullpath = $CFG->dataroot .'/'. $image->path;
                    $image->timemodified = time();
                    $image->owner = (int) $USER->id;

                    // Resize image if necessary
                    // Start with width
                    if ($image->width > 300) {
                        $division      = ($image->width / 300);
                        $image->height = round($image->height / $division);
                        $image->width  = 300;
                    }
                    // Check height
                    if ($image->height > 300) {
                        $division      = ($image->height / 300);
                        $image->width  = round($image->width / $division);
                        $image->height = 300;
                    }

                    if (!netpublish_makeimage($image->temp, $image->fullpath,
                                      $image->width, $image->height, $image->type)) {
                        @unlink($image->temp);
                    }

                    if (!insert_record("netpublish_images", $image)) {
                        @unlink($image->fullpath);
                        notify("Couldn't add record! Image not added");
                    }

                    // Remove temporary image
                    @unlink($image->temp);

                    notify(get_string('uploadedfile'));
                }
                // um will take care of error reporting.
                displaydir($wdir);
            } else {
                $upload_max_filesize = get_max_upload_file_size($CFG->maxbytes);
                $filesize = display_size($upload_max_filesize);

                $struploadafile = get_string("uploadafile");
                $struploadthisfile = get_string("uploadthisfile");
                $strmaxsize = get_string("maxsize", "", $filesize);
                $strcancel = get_string("cancel");

                echo "<p>$struploadafile ($strmaxsize) --> <strong>$wdir</strong>";
                echo "<table border=\"0\"><tr><td colspan=\"2\">\n";
                echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"imagebank.php\">\n";
                upload_print_form_fragment(1,array('userfile'),null,false,null,$course->maxbytes,0,false);
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
                echo " <input type=\"hidden\" name=\"action\" value=\"upload\" />\n";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
                echo " </td><tr><td align=\"right\">";
                echo " <input type=\"submit\" name=\"save\" value=\"$struploadthisfile\" />\n";
                echo "</form>\n";
                echo "</td>\n<td>\n";
                echo "<form action=\"imagebank.php\" method=\"get\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />\n";
                echo " <input type=\"submit\" value=\"$strcancel\" />\n";
                echo "</form>\n";
                echo "</td>\n</tr>\n</table>\n";
            }
            html_footer();
            break;

        case "delete":
            if (!empty($confirm) and confirm_sesskey()) {
                html_header($course, $wdir);

                foreach ($USER->filelist as $file) {
                    $isint = intval($file);

                    if ($isint) {

                        $delfile = get_record("netpublish_images","id", $isint);

                        if (unlink($CFG->dataroot .'/'. $delfile->path)) {
                            if (! delete_records("netpublish_images", "id", $delfile->id)) {
                                echo "<br />Error: Could not delete selected file!";
                            }
                        }

                    } else {

                        $delfiles = get_records_select("netpublish_images","dir LIKE '". addslashes($file) ."%'");

                        $fullfile = $basedir.$file;
                        if (! fulldelete($fullfile)) {
                            echo "<br />Error: Could not delete: $fullfile";
                        }

                        // Delete all files under this directory
                        if (!empty($delfiles)) {
                            foreach ($delfiles as $delfile) {
                                delete_records("netpublish_images", "id", $delfile->id);
                                @unlink($CFG->dataroot .'/'. $delfile->path);
                            }
                        }
                    }
                }

                clearfilelist();
                displaydir($wdir);
                html_footer();

            } else {
                html_header($course, $wdir);
                if (setfilelist($_POST)) {
                    echo "<p align=center>".get_string("deletecheckwarning").":</p>";
                    print_simple_box_start("center");
                    printfilelist($USER->filelist);
                    print_simple_box_end();
                    echo "<br />";
                    notice_yesno (get_string("deletecheckfiles"),
                                "imagebank.php?id=$id&amp;wdir=$wdir&amp;action=delete&amp;confirm=1&amp;sesskey=$USER->sesskey",
                                "imagebank.php?id=$id&amp;wdir=$wdir&amp;action=cancel");
                } else {
                    displaydir($wdir);
                }
                html_footer();
            }
            break;

        case "rename":
            if (!empty($name) and confirm_sesskey()) {
                html_header($course, $wdir);
                $name    = clean_filename($name);
                $oldname = clean_filename($oldname);

                if (!empty($fileid)) {
                    $fileid = clean_param($fileid, PARAM_INT);

                    $file = get_record("netpublish_images", "id", $fileid);

                    if (!empty($file)) {
                        // check owner
                        if (intval($file->owner) != intval($USER->id) && !$isteacher) {
                            error("You can't rename file you don't own!");
                        }
                        // update record
                        $file->name = $name;
                        $file->timemodified = time();
                        if (!update_record("netpublish_images", $file)) {
                            notify("Error: could not rename $oldname to $name !!!");
                        }
                    }
                } else {

                    if (file_exists($basedir.$wdir."/".$name)) {
                        echo "Error: $name already exists!";
                    } else if (!$renamed = rename($basedir.$wdir."/".$oldname, $basedir.$wdir."/".$name)) {
                        echo "Error: could not rename $oldname to $name";
                    }

                    if ($renamed) {
                        if ($wdir == "/") {
                            $wdir = "";
                        }
                        $dir = addslashes($wdir .'/'. $oldname);
                        $select = "dir LIKE '$dir%'";
                        $files = get_records_select("netpublish_images", $select);
                        if (!empty($files)) {
                            foreach ($files as $file) {
                                $newdir = str_replace($oldname, $name, $file->dir);
                                $newdir = addslashes($newdir);
                                set_field("netpublish_images","dir", $newdir, "id", $file->id);
                            }
                        }
                    }
                }
                displaydir($wdir);

            } else {
                $integertest = intval($file);
                if ($integertest) {
                    $file = get_field("netpublish_images", "name", "id", $integertest);
                }
                $strrename = get_string("rename");
                $strcancel = get_string("cancel");
                $strrenamefileto = get_string("renamefileto", "moodle", $file);
                html_header($course, $wdir, "form.name");
                echo "<p>$strrenamefileto:";
                echo "<table border=\"0\">\n<tr>\n<td>\n";
                echo "<form action=\"imagebank.php\" method=\"post\" name=\"form\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
                echo " <input type=\"hidden\" name=\"action\" value=\"rename\" />\n";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
                if (!empty($integertest)) {
                    echo "<input type=\"hidden\" name=\"fileid\" value=\"$integertest\" />\n";
                }
                echo " <input type=\"hidden\" name=\"oldname\" value=\"$file\" />\n";
                echo " <input type=\"text\" name=\"name\" size=\"35\" value=\"$file\" />\n";
                echo " <input type=\"submit\" value=\"$strrename\" />\n";
                echo "</form>\n";
                echo "</td><td>\n";
                echo "<form action=\"imagebank.php\" method=\"get\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />\n";
                echo " <input type=\"submit\" value=\"$strcancel\" />\n";
                echo "</form>";
                echo "</td></tr>\n</table>\n";
            }
            html_footer();
            break;

        case "mkdir":
            if (!empty($name) and confirm_sesskey()) {
                html_header($course, $wdir);
                $name = clean_filename($name);
                if (file_exists("$basedir$wdir/$name")) {
                    echo "Error: $name already exists!";
                } else if (! make_upload_directory("netpublish_images/$id$wdir/$name")) {
                    echo "Error: could not create $name";
                }
                displaydir($wdir);

            } else {
                $strcreate = get_string("create");
                $strcancel = get_string("cancel");
                $strcreatefolder = get_string("createfolder", "moodle", $wdir);
                html_header($course, $wdir, "form.name");
                echo "<p>$strcreatefolder:";
                echo "<table border=\"0\">\n<tr><td>\n";
                echo "<form action=\"imagebank.php\" method=\"post\" name=\"form\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
                echo " <input type=\"hidden\" name=\"action\" value=\"mkdir\" />\n";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
                echo " <input type=\"text\" name=\"name\" size=\"35\" />\n";
                echo " <input type=\"submit\" value=\"$strcreate\" />\n";
                echo "</form>\n";
                echo "</td><td>\n";
                echo "<form action=\"imagebank.php\" method=\"get\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />\n";
                echo " <input type=\"submit\" value=\"$strcancel\" />\n";
                echo "</form>\n";
                echo "</td>\n</tr>\n</table>\n";
            }
            html_footer();
            break;

        case "cancel";
            clearfilelist();

        default:
            html_header($course, $wdir);
            displaydir($wdir);
            html_footer();
            break;
}


/// FILE FUNCTIONS ///////////////////////////////////////////////////////////


if (! function_exists('fulldelete')) {
    function fulldelete($location) {
        if (is_dir($location)) {
            $currdir = opendir($location);
            while ($file = readdir($currdir)) {
                if ($file <> ".." && $file <> ".") {
                    $fullfile = $location."/".$file;
                    if (is_dir($fullfile)) {
                        if (!fulldelete($fullfile)) {
                            return false;
                        }
                    } else {
                        if (!unlink($fullfile)) {
                            return false;
                        }
                    }
                }
            }
            closedir($currdir);
            if (! rmdir($location)) {
                return false;
            }

        } else {
            if (!unlink($location)) {
                return false;
            }
        }
        return true;
    }
}



function setfilelist($VARS) {
    global $USER;

    $USER->filelist = array ();
    $USER->fileop = "";

    $count = 0;
    foreach ($VARS as $key => $val) {
        if (substr($key,0,4) == "file") {
            $count++;
            $USER->filelist[] = rawurldecode($val);
        }
    }
    return $count;
}

function clearfilelist() {
    global $USER;

    $USER->filelist = array ();
    $USER->fileop = "";
}


function printfilelist($filelist) {
    global $basedir, $CFG;

    foreach ($filelist as $file) {
        if (is_dir($basedir.$file)) {
            echo "<img src=\"$CFG->pixpath/f/folder.gif\" height=\"16\" width=\"16\" alt=\"\" /> $file<br />";
            $subfilelist = array();
            $currdir = opendir($basedir.$file);
            while ($subfile = readdir($currdir)) {
                if ($subfile <> ".." && $subfile <> ".") {
                    $subfilelist[] = $file."/".$subfile;
                }
            }
            printfilelist($subfilelist);

        } else {
            $id = intval($file);
            $thisfile = get_record("netpublish_images", "id", $id);

            $icon = mimeinfo("icon", $thisfile->name);
            echo "<img src=\"$CFG->pixpath/f/$icon\"  height=\"16\" width=\"16\" alt=\"\" /> $thisfile->name<br />";
        }
    }
}


function print_cell($alignment="center", $text="&nbsp;") {
    echo "<td align=\"$alignment\" nowrap=\"nowrap\">\n";
    echo "$text";
    echo "</td>\n";
}

function get_image_size($filepath) {
/// This function get's the image size

    /// Check if file exists
    if(!file_exists($filepath)) {
        return false;
    } else {
        /// Get the mime type so it really an image.
        if(mimeinfo("icon", basename($filepath)) != "image.gif") {
            return false;
        } else {
            $array_size = getimagesize($filepath);
            return $array_size;
        }
    }
    unset($filepath,$array_size);
}

function displaydir ($wdir) {
//  $wdir == / or /a or /a/b/c/d  etc

    global $basedir;
    global $usecheckboxes;
    global $id;
    global $USER, $CFG, $isteacher, $isstudent;

    $fullpath = $basedir . $wdir;

    $directory = opendir($fullpath);             // Find all files
    while ($file = readdir($directory)) {
        if ($file == "." || $file == ".." || $file == "tmp") {
            continue;
        }

        if (is_dir($fullpath."/".$file)) {
            $dirlist[] = $file;
        }
    }

    $test = str_replace("/", "", $wdir);

    if (empty($test)) {
        $wdir = "";
    }

    $images = netpublish_get_images($id, $wdir);
    /*print "<pre>";
    print_r($images);
    print "</pre>";
    exit;*/

    $filelist = array();

    if (!empty($images)) {
        $i = 0;
        foreach ($images as $img) {
            $filelist[$i]['id']     = $img->id;
            $filelist[$i]['course'] = $img->course;
            $filelist[$i]['name']   = $img->name;
            $filelist[$i]['mime']   = $img->mimetype;
            $filelist[$i]['size']   = $img->size;
            $filelist[$i]['dir']    = $img->dir;
            $filelist[$i]['filedate'] = userdate($img->timemodified, "%d %b %Y, %I:%M %p");
            $filelist[$i]['width']  = $img->width;
            $filelist[$i]['height'] = $img->height;
            $filelist[$i]['owner']  = $img->owner;
            $i++;
        }
    }

    //closedir($directory);

    $strfile = get_string("file");
    $strname = get_string("name");
    $strsize = get_string("size");
    $strmodified = get_string("modified");
    $straction = get_string("action");
    $strmakeafolder = get_string("makeafolder");
    $struploadafile = get_string("uploadafile");
    $strwithchosenfiles = get_string("withchosenfiles");
    $strmovetoanotherfolder = get_string("movetoanotherfolder");
    $strmovefilestohere = get_string("movefilestohere");
    $strdeletecompletely = get_string("deletecompletely");
    $strcreateziparchive = get_string("createziparchive");
    $strrename = get_string("rename");
    $stredit   = get_string("edit");
    $strunzip  = get_string("unzip");
    $strlist   = get_string("list");
    $strchoose   = get_string("choose");


    echo "<form action=\"imagebank.php\" method=\"post\" name=\"dirform\">\n";
    echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"0\" width=\"100%\">\n";

    if (empty($wdir)) {
        $wdir = "";
    } else {
        $bdir = str_replace("/".basename($wdir),"",$wdir);
        if($bdir == "/") {
            $bdir = "";
        }
        print "<tr>\n<td colspan=\"5\">";
        print "<a href=\"imagebank.php?id=$id&amp;wdir=$bdir&amp;usecheckboxes=$usecheckboxes\" onclick=\"return reset_value();\">";
        print "<img src=\"$CFG->wwwroot/lib/editor/images/folderup.gif\" height=\"14\" width=\"24\" border=\"0\" alt=\"Move up\" />";
        print "</a></td>\n</tr>\n";
    }

    $count = 0;

    if (!empty($dirlist)) {
        asort($dirlist);
        foreach ($dirlist as $dir) {

            $count++;

            $filename = $fullpath."/".$dir;
            $fileurl  = rawurlencode($wdir."/".$dir);
            $filesafe = rawurlencode($dir);
            $filedate = userdate(filemtime($filename), "%d %b %Y, %I:%M %p");

            echo "<tr>";

            if ($usecheckboxes && $isteacher) {
                print_cell("center", "<input type=\"checkbox\" name=\"file$count\" value=\"$fileurl\" onclick=\"return set_rename('$filesafe');\" />");
            } else {
                print_cell("left");
            }

            print_cell("left", "<a href=\"imagebank.php?id=$id&amp;wdir=$fileurl\" onclick=\"return reset_value();\"><img src=\"$CFG->pixpath/f/folder.gif\" height=\"16\" width=\"16\" border=\"0\" alt=\"folder\" /></a> <a href=\"imagebank.php?id=$id&amp;wdir=$fileurl&amp;usecheckboxes=$usecheckboxes\" onclick=\"return reset_value();\">".htmlspecialchars($dir)."</a>");
            print_cell("right", "&nbsp;");
            print_cell("right", $filedate);

            echo "</tr>";
        }
    }


    if (!empty($filelist)) {

        foreach ($filelist as $file) {

            $icon = mimeinfo("icon", $file['name']);
            $imgtype = $file['mime'];

            $count++;
            //$filename    = $fullpath."/".$file['name'];
            $fileurl     = "/mod/netpublish/image.php?id={$file['id']}";
            $filesafe    = rawurlencode($file['name']);
            $fileurlsafe = rawurlencode($fileurl);
            $filedate    = $file['filedate'];

            $imgwidth  = $file['width'];
            $imgheight = $file['height'];

            echo "<tr>\n";

            if ($usecheckboxes) {
                if (intval($file['owner']) == intval($USER->id) or $isteacher) {
                    print_cell("center", "<input type=\"checkbox\" name=\"file$count\" value=\"{$file['id']}\" onclick=\"return set_rename('{$file['id']}');\" />");
                } else {
                    print_cell("left");
                }
            }
            echo "<td align=\"left\" nowrap=\"nowrap\">";
            if ($CFG->slasharguments) {
                $ffurl = $fileurl;
            } else {
                $ffurl = $fileurl;
            }
            link_to_popup_window ($ffurl, "display",
                                  "<img src=\"$CFG->pixpath/f/$icon\" height=\"16\" width=\"16\" border=\"0\" align=\"middle\" alt=\"$strfile\" />",
                                  480, 640);
            $file_size = $file['size'];

            echo "<a onclick=\"return set_value(info = {url: '".$CFG->wwwroot.$ffurl."',";
            echo " isize: '".$file_size."', itype: '".$imgtype."', iwidth: '".$imgwidth."',";
            echo " iheight: '".$imgheight."', imodified: '".$filedate."', ialt: '". $file['name'] ."' })\" href=\"#\">{$file['name']}</a>";
            echo "</td>\n";

            if ($icon == "zip.gif") {
                $edittext = "<a href=\"imagebank.php?id=$id&amp;wdir=$wdir&amp;file=$fileurl&amp;action=unzip&amp;sesskey=$USER->sesskey\">$strunzip</a>&nbsp;";
                $edittext .= "<a href=\"imagebank.php?id=$id&amp;wdir=$wdir&amp;file=$fileurl&amp;action=listzip&amp;sesskey=$USER->sesskey\">$strlist</a> ";
            } else {
                $edittext = "&nbsp;";
            }
            print_cell("right", "$edittext ");
            print_cell("right", $filedate);

            echo "</tr>\n";
        }
    }
    echo "</table>\n";

    if (empty($wdir)) {
        $wdir = "/";
    }

    echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
    echo "<tr>\n<td>";
    echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
    echo "<input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
    echo "<input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
    $options = array (
                   "move" => "$strmovetoanotherfolder",
                   "delete" => "$strdeletecompletely",
                   "zip" => "$strcreateziparchive"
               );
    if (!empty($count)) {
        choose_from_menu ($options, "action", "", "$strwithchosenfiles...", "javascript:document.dirform.submit()");
    }
    if (!empty($USER->fileop) and ($USER->fileop == "move") and ($USER->filesource <> $wdir)) {
        echo "<form action=\"imagebank.php\" method=\"get\">\n";
        echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
        echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
        echo " <input type=\"hidden\" name=\"action\" value=\"paste\" />\n";
        echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
        echo " <input type=\"submit\" value=\"$strmovefilestohere\" />\n";
        echo "</form>";
    }
    echo "</td></tr>\n";
    echo "</table>\n";
    echo "</form>\n";
}

function netpublish_get_images ($course=0, $wdir="") {

    if (empty($course)) {
        return false;
    }

    if (empty ($wdir)) {
        $dircondition = '(dir IS NULL OR dir = \'\')';
    } else {
        $dircondition = "dir = '". addslashes($wdir) ."'";
    }

    if (!empty($course)) {
        $select  = $dircondition;
        $select .= " AND course = ". $course ."";

    }

    $fields = 'id, course, name, mimetype, width, height, size, timemodified, owner, dir';
    $sort   = 'timemodified DESC';

    return get_records_select("netpublish_images", $select, $sort, $fields);

}

function netpublish_makeimage ($source, $destination, $width, $height, $type=2) {

    global $CFG;

    // Load source image
    switch ($type) {
        case 2:
            $imagecreatefrom = 'imagecreatefromjpeg';
            $imagefunc       = 'imagejpeg';
            break;
        case 3:
            $imagecreatefrom = 'imagecreatefrompng';
            $imagefunc       = 'imagepng';
            break;
        case 1:
            $imagecreatefrom = 'imagecreatefromgif';
            $imagefunc       = 'imagegif';
            break;
        default:
            $imagecreatefrom = 'imagecreatefromjpeg';
    }

    if (!function_exists($imagecreatefrom)) {
        return false;
    }

    if (!function_exists($imagefunc)) {
        return false;
    }

    if (!($sourceimage = @$imagecreatefrom($source))) {
        // Create error image
        $image = imagecreate($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image,   0,   0,   0);

        imagefill($image, 0, 0, $white);
        imagestring($image, 1, 1, 10, "Failed!", $black);
        imagepng($image, $destination);
        return(FALSE);
    }

    // Create destination image
    if ($CFG->gdversion < 2) {
        // Imagecreatetruecolor requires GD version 2 so try
        // to create just a temporary blank image. This is
        // a little trick to get images resized with truecolor
        // enabled when gd version is smaller than 2.
        $tempimage  = $CFG->dataroot;
        $tempimage .=  '/temp_';
        $tempimage .=  time();
        $tempimage .=  '.jpg';
        $tmpimage = imagecreate($width, $height);
        if (! imagejpeg($tmpimage, $tempimage)) {
            return false;
        }
        $destinationimage = imagecreatefromjpeg($tempimage);
        @unlink ($tempimage); // Remove temporary image.
    } else {
        $destinationimage = imagecreatetruecolor($width, $height);
    }

    // copy source to destination
    // resampling and possibly distorting
    if ($CFG->gdversion < 2) {
        imagecopyresized ($destinationimage, $sourceimage, 0, 0, 0, 0,
                       $width, $height, imagesx($sourceimage), imagesy($sourceimage));
    } else {
        imagecopyresampled($destinationimage, $sourceimage, 0, 0, 0, 0,
                       $width, $height, imagesx($sourceimage), imagesy($sourceimage));
    }

    // save image
    if (!($imagefunc($destinationimage, $destination))) {
        return false;
    }

    return true;
}
?>

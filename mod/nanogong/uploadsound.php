<?php // $Id: uploadsound.php,v 4 2010/04/22 00:00:00 gibson Exp $

//  Handle uploading of sound

    require("../../config.php");
    require_once($CFG->libdir.'/filelib.php');
    require_once($CFG->dirroot.'/lib/uploadlib.php');

    $id      = required_param('id', PARAM_INT);

    if (! $course = get_record("course", "id", $id) ) {
        error("That's an invalid course id");
    }

    require_login($id);

    make_mod_upload_directory($id);
    if (! $basedir = make_upload_directory("$id/moddata/nanogong")) {
        error("The site administrator needs to fix the file permissions");
    }

    $baseweb = $CFG->wwwroot;

    if (confirm_sesskey()) {
        // remove the annoying warning from upload manager
        $el = error_reporting(0);

        // hardcode the file name
        if (isset($_FILES['userfile']['name'])) {
            $oldname = $_FILES['userfile']['name'];
            $ext = preg_replace("/.*(\.[^\.]*)$/", "$1", $oldname);
            $newname = date("Y-m-d") . "_" . date("His") . $ext;
            $_FILES['userfile']['name'] = $newname;
        }
        
        // handle the upload
        $um = new upload_manager('userfile',false,true,$course,false,0,true);
        $wdir = "/{$USER->id}";
        $dir = "$basedir$wdir";
        if ($um->process_file_uploads($dir)) {
            $filename = $um->get_new_filename();
            $fileurl = "$wdir/" . $filename;
            print "/$id/moddata/nanogong$fileurl";
        }
        error_reporting($el);
    }

?>

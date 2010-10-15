<?php

function webquest_upgrade($oldversion) {
/// This function does anything necessary to upgrade
/// older versions to match current functionality
    $status = true;
    global $CFG;
    if ($oldversion < 2007081222) {
        require_once($CFG->dirroot.'/backup/lib.php');
        //make the change into each course
        $courses = get_records("course");
        foreach ($courses as $course){
            $newdir = "$course->id/$CFG->moddata/webquest";
            if (make_upload_directory($newdir)){
                $olddir = "$CFG->dataroot/$course->id/$CFG->moddata/webquest/submissions";
                //chec k if the old directory exists

                if (is_dir($olddir)){
                    $status = backup_copy_file($olddir,$CFG->dataroot."/".$newdir);
                }
                if ($status){
                    fulldelete($olddir);
                }
            }
        }
    }


    return $status;
}

?>

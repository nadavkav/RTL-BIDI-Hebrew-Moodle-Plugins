<?php
    //This page copies th zip to the temp directory,
    //unzip it, check that it is a valid backup file
    //inform about its contents and fill all the necesary
    //variables to continue with the restore.

    //Checks we have the file variable
    if (!isset($file)) {         
        error ("File not specified");
    }

    //Check login   
    require_login();
 
    //Check admin
    if (!empty($id)) {
        if (!has_capability('moodle/site:restore', get_context_instance(CONTEXT_COURSE, $id))) {
            if (empty($to)) {
                error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
            } else {
                if (!has_capability('moodle/site:restore', get_context_instance(CONTEXT_COURSE, $to))
                    && !has_capability('moodle/site:import',  get_context_instance(CONTEXT_COURSE, $to))) {
                    error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
                }
            }
        }
    } else {
        if (!has_capability('moodle/site:restore', get_context_instance(CONTEXT_SYSTEM, SITEID))) {
            error("You need to be an admin user to use this page.", "$CFG->wwwroot/login/index.php");   
        }
    }

    //Check site
    if (!$site = get_site()) {
        error("Site not found!");
    }

    $errorstr = '';
    define('RESTORE_SILENTLY',1);
    
    $backup_unique_code = restore_precheck($id,$file,$errorstr,true);
    $SESSION->restore->section = $section;
    
    if (!$backup_unique_code) {
        error("An error occured " . $errorstr);
    }
?>
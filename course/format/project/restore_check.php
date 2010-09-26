<?php //$Id: restore_check.html,2008/4/1 12:00:00 Akio Ohnishi  Exp $
    //This page receive all the restore_form data. Then, if existing course
    //has been selected, shows a list of courses to select one.
    //It cheks that the parammeter from restore_form are coherent.
    //It puts all the restore info in the session.
    //Finally, it calls restore_execute to do the hard work
    //Get objects from session
    if ($SESSION) {
        $info = $SESSION->info;
        $course_header = $SESSION->course_header;
    }

    //Detect if we are coming from the restore form
    $fromform = 0;

    if ($form1 = data_submitted()) {
        $currentcourseshortname = $course_header->course_shortname; //"store_ShortName";
        $course_header->course_shortname =  stripslashes_safe($form1->shortname);  //"update_ShortName";
        $course_header->course_fullname =   stripslashes_safe($form1->fullname);   //"update_FullName";
    /// Roll dates only if the backup course has a start date
    /// (some formats like main page, social..., haven't it and rolling dates
    /// from 0 produces crazy dates. MDL-10125
        if ($course_header->course_startdate) {
            $form1->startdate = make_timestamp($form1->startyear, $form1->startmonth, $form1->startday);
            $currentcoursestartdate = $course_header->course_startdate;
            $coursestartdatedateoffset = $form1->startdate - $currentcoursestartdate;
            $restore->course_startdateoffset = $coursestartdatedateoffset; //change to restore
        } else { // don't roll if the course hasn't start date
            $coursestartdatedateoffset = 0;
            $restore->course_startdateoffset = 0;
        }
    } else {
        $currentcourseshortname = $course_header->course_shortname;
        $coursestartdatedateoffset = 0;
    }

    ///Enforce SESSION->course_header rewrite (PHP 4.x needed because assigns are by value) MDL-8298
    $SESSION->course_header = $course_header;

    // 強制的にrestoreを削除する
    unset($restore);

    // check for session objects
    if (empty($info) or empty($course_header)) {
      error( 'important information missing from SESSION' );
    }


    // $backup_unique_codeはrestore_precheck.phpで取得済み(restore.phpを参照)
    //file
    $file = required_param( 'file');
    
    //Checks for the required restoremod parameters
    if ($allmods = get_records("modules")) {
        foreach ($allmods as $mod) {
            $modname = $mod->name;
            $var = "restore_".$modname;
            $$var = 1;
            $var = "restore_user_info_".$modname;
            $$var = 0;
            $instances = !empty($info->mods[$mod->name]->instances) ? $info->mods[$mod->name]->instances : NULL;
            if ($instances === NULL) {
                continue;
            }
            foreach ($instances as $instance) {
                $var = 'restore_'.$modname.'_instance_'.$instance->id;
                $$var = 1;
                $var = 'restore_user_info_'.$modname.'_instance_'.$instance->id;
                $$var = 0;
            }
        }
    }
    
    $restore_restoreto = 2; // 新規（無視される）
    $restore_metacourse = 0;
    $restore_users = 2;
    $restore_logs = 0;
    $restore_user_files = 0;
    $restore_course_files = 1;
    $restore_site_files = 0;
    $restore_gradebook_history = 0;
    $restore_messages = 0;
    
    //Check we've selected a course to restore to
    $course_id = $id;

    //We are here, having all we need !!
    //Create the restore object and put it in the session
    $restore->backup_unique_code = $backup_unique_code;
    $restore->file = $file;
    if ($allmods = get_records("modules")) {
        foreach ($allmods as $mod) {
            $modname = $mod->name;
            $var = "restore_".$modname;
            $restore->mods[$modname]->restore=$$var;
            $var = "restore_user_info_".$modname;
            $restore->mods[$modname]->userinfo=$$var;
            $instances = !empty($info->mods[$mod->name]->instances) ? $info->mods[$mod->name]->instances : NULL;
            if ($instances === NULL) {
                continue;
            }
            foreach ($instances as $instance) {
                $var = 'restore_'.$modname.'_instance_'.$instance->id;
                $restore->mods[$modname]->instances[$instance->id]->restore = $$var;
                $var = 'restore_user_info_'.$modname.'_instance_'.$instance->id;
                $restore->mods[$modname]->instances[$instance->id]->userinfo = $$var;
            }
        }
    }
    $restore->restoreto=$restore_restoreto;
    $restore->metacourse=$restore_metacourse;
    $restore->users=$restore_users;
    $restore->logs=$restore_logs;
    $restore->user_files=$restore_user_files;
    $restore->course_files=$restore_course_files;
    $restore->site_files=$restore_site_files;
    $restore->messages=$restore_messages;
    $restore->restore_gradebook_history=$restore_gradebook_history;
    $restore->course_id=$course_id;
    
    // セクション
    $restore->section = $section;
    
    // 新しいディレクトリ名
    $restore->newdirectoryname = $newdirectoryname;
    
    //add new vars to restore object
    $restore->course_startdateoffset = $coursestartdatedateoffset;
    $restore->course_shortname = $currentcourseshortname;

    // default role mapping for moodle < 1.7
    if ($defaultteacheredit = optional_param('defaultteacheredit', 0, PARAM_INT)) {
        $restore->rolesmapping['defaultteacheredit'] = $defaultteacheredit;
    }
    if ($defaultteacher = optional_param('defaultteacher', 0, PARAM_INT)) {
        $restore->rolesmapping['defaultteacher'] = $defaultteacher;
    }
    if ($defaultstudent = optional_param('defaultstudent', 0, PARAM_INT)) {
        $restore->rolesmapping['defaultstudent'] = $defaultstudent;
    }

    // pass in the course category param
    $cat_id = 0;
    if ($cat_id) {
        $restore->restore_restorecatto = $cat_id;
    }

    //We have the object with data, put it in the session
    $SESSION->restore = $restore;

    //From here to the end of the script, only use the $restore object

    //Check login
    require_login();

    //Check admin
    if (!empty($id)) {
        if (!has_capability('moodle/site:restore', get_context_instance(CONTEXT_COURSE, $id))) {
            error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
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

    //If the user is a teacher and not a creator


    //if (!has_capability('moodle/course:create', get_context_instance(CONTEXT_SYSTEM, SITEID))) {

    if (!user_can_create_courses()) {
        $restore->course_id = $id;
        if ($restore->restoreto == 0) {
            $restore->deleting = true;
        } else {
            $restore->deleting = false;
        }
    }

    //If the user is a creator (or admin)
    //if (has_capability('moodle/course:create', get_context_instance(CONTEXT_SYSTEM, SITEID))) {
    if (user_can_create_courses()) {
        //Set restore->deleting as needed
        if ($restore->restoreto == 0) {
            $restore->deleting = true;
        } else {
            $restore->deleting = false;
        }
    }



    // Non-cached - get accessinfo
    if (isset($USER->access)) {
        $accessinfo = $USER->access;
    } else {
        $accessinfo = get_user_access_sitewide($USER->id);
    }
    $mycourses = get_user_courses_bycap($USER->id, 'moodle/site:restore', $accessinfo, true, 'c.sortorder ASC',  array('id', 'fullname', 'shortname', 'visible'));

    //Final access control check
    if ($restore->course_id == 0 and !user_can_create_courses()) {
        error("You need to be a creator or admin to restore into new course!");
    } else if ($restore->course_id != 0 and !has_capability('moodle/site:restore', get_context_instance(CONTEXT_COURSE, $restore->course_id))) {
        error("You need to be an edit teacher or admin to restore into selected course!");
    }
    $show_continue_button = true;
    //Check if we've selected any mod's user info and restore->users
    //is set to none. Change it to course and inform.
    if ($restore->users == 2) {
        $changed = false;
        $mods = $restore->mods;
        foreach ($mods as $mod) {
            if ($mod->userinfo) {
                $changed = true;
            }
        }
        //If we have selected user files or messages, then users must be restored too
        if ($restore->user_files || $restore->messages) {
            $changed = 1;
        }
        if ($changed) {
            echo get_string ("noteuserschangednonetocourse");
            echo "<hr noshade size=\"1\">";
            $restore->users = 1;
        }
    }
    //Save the restore session object
    $SESSION->restore = $restore;
    if ($show_continue_button) {
        //Print the continue button to execute the restore NOW !!!!
        //All is prepared !!!
        echo "<div style='text-align:center'>";
        $hidden["launch"]             = "execute";
        $hidden["file"]               =  $file;
        $hidden["id"]                 =  $id;
        $hidden["section"]            =  $section;
        print_string('longtimewarning','admin');

        if ($restore->users && !empty($info->mnet_externalusers)
            && $info->mnet_externalusers === 'true') {
            if ($info->original_wwwroot === $CFG->wwwroot) {
                print '<p>'.get_string('mnetrestore_extusers','admin').'</p>';
            } else {
                print '<p>'. get_string('mnetrestore_extusers_mismatch','admin').'</p>';
            }
        }
        print_single_button("restore.php", $hidden, get_string("restoresectionnow",'format_project'),"post");
        echo "</div>";
    } else {
        //Show error
        error ("Something was wrong checking restore preferences");
    }

?>

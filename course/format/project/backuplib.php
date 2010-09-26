<?php
/**
 * Moodleから呼び出されるバックアップモジュール
 * 
 * @param stream $bf
 * @param object $preferences
 * @return bool
 */
function project_backup_format_data($bf,$preferences) {
    $status = true;
    
    // コースセクション一覧を読み込む
    // $preferences->sectionが設定されている場合は１つのタイトルのみ
    if ( $sections = get_all_sections($preferences->backup_course)) {
        fwrite ($bf,start_tag("SECTIONTITLES",3,true));
        foreach ($sections as $section) {
            if ((empty($preferences->backup_section) || $preferences->backup_section == $section->section)
             && $section->id > 0 && $sectiontitle = get_record('course_project_title', 'sectionid', $section->id)) {
                fwrite ($bf,start_tag("SECTIONTITLE",4,true));
                fwrite ($bf,full_tag("ID",5,false,$sectiontitle->id));
                fwrite ($bf,full_tag("SECTIONID",5,false,$sectiontitle->sectionid));
                fwrite ($bf,full_tag("DIRECTORYNAME",5,false,$sectiontitle->directoryname));
                fwrite ($bf,end_tag("SECTIONTITLE",4,true));
            } else {
                continue;
            }
        }
        $status = fwrite ($bf,end_tag("SECTIONTITLES",3,true));
    } else {
        $status = false;
    }
    
    return $status;
}


/*
 * フォームで設定するバックアップデータを自動生成する
 * 
 * @param string $backup_name
 * @param string $backup_unique_code
 * @param int $sectionnumber
 * @param object &$preference
 * @param int &$count
 * @param object $course : コースオブジェクト
 * @param object $virtualform : 擬似フォーム
 */
function project_backup_get_preferences($sectionnumber,&$preferences,&$count,$course) {
    global $CFG,$SESSION;

    // check to see if it's in the session already
    if (!empty($SESSION->backupprefs)  && array_key_exists($course->id,$SESSION->backupprefs) && !empty($SESSION->backupprefs[$course->id])) {
        $sprefs = $SESSION->backupprefs[$course->id];
        $preferences = $sprefs;
        // refetch backup_name just in case.
        $bn = optional_param('backup_name','',PARAM_FILE);
        if (!empty($bn)) {
            $preferences->backup_name = $bn;
        }
        $count = 1;
        return true;
    }

    if ($allmods = get_records("modules") ) {
        foreach ($allmods as $mod) {
            $modname = $mod->name;
            $modfile = "$CFG->dirroot/mod/$modname/backuplib.php";
            $modbackup = $modname."_backup_mods";
            $modbackupone = $modname."_backup_one_mod";
            $modcheckbackup = $modname."_check_backup_mods";
            if (!file_exists($modfile)) {
                continue;
            }
            include_once($modfile);
            if (!function_exists($modbackup) || !function_exists($modcheckbackup)) {
                continue;
            }
            $var = "exists_".$modname;
            $preferences->$var = true;
            $count++;
            // check that there are instances and we can back them up individually
            if (!count_records('course_modules','course',$course->id,'module',$mod->id) || !function_exists($modbackupone)) {
                continue;
            }
            $var = 'exists_one_'.$modname;
            $preferences->$var = true;
            $varname = $modname.'_instances';
            $preferences->$varname = get_all_instances_in_course($modname, $course, NULL, true);
            foreach ($preferences->$varname as $k => $instance) {
                if ($instance->section == $sectionnumber) {
                    $preferences->mods[$modname]->instances[$instance->id]->name = $instance->name;
                    $var = 'backup_'.$modname.'_instance_'.$instance->id;
                    //$$var = optional_param($var,0);
                    $$var = 1;
                    $preferences->$var = $$var;
                    $preferences->mods[$modname]->instances[$instance->id]->backup = $$var;
                    $var = 'backup_user_info_'.$modname.'_instance_'.$instance->id;
                    //$$var = optional_param($var,0);
                    $$var = 1;
                    $preferences->$var = $$var;
                    $preferences->mods[$modname]->instances[$instance->id]->userinfo = $$var;
                    $var = 'backup_'.$modname.'_instances';
                    $preferences->$var = 1; // we need this later to determine what to display in modcheckbackup.
                } else {
                    eval('unset($preferences->'.$varname.'['. $k.']);');
                }
            }
            
            // バックアップ対象がある場合バックアップ処理追加
            if (count($preferences->$varname)) {
                //Check data
                //Check module info
                $preferences->mods[$modname]->name = $modname;
                $var = "backup_".$modname;
                //$$var = optional_param( $var,0);
                $$var = 1;
                $preferences->$var = $$var;
                $preferences->mods[$modname]->backup = $$var;
    
                //Check include user info
                $var = "backup_user_info_".$modname;
                //$$var = optional_param( $var,0);
                $$var = 1;
                $preferences->$var = $$var;
                $preferences->mods[$modname]->userinfo = $$var;
            }
        }
    }

    // セクション情報の取得
    if (! $section = get_course_section($sectionnumber, $course->id)) {
        error("Section data was incorrect (can't find it)");
    }

    // セクションタイトルの取得
    if (! $sectiontitle = project_format_get_title($course, $section->id)) {
        error("Section title data was incorrect (can't find it)");
    }    
    
    //バックアップ用変数
    $backup_unique_code = time();        
    $backup_name = project_backup_get_zipfile_name($sectiontitle, $course);
    
    $preferences->backup_metacourse = 1;
    $preferences->backup_users = 2;
    $preferences->backup_logs = 0;
    $preferences->backup_user_files = 0;
    $preferences->backup_course_files = 1;
    $preferences->backup_gradebook_history = 0;
    $preferences->backup_site_files = 0;
    $preferences->backup_messages = 0;
    $preferences->backup_course = $course->id;
    $preferences->backup_section = $sectionnumber;
    $preferences->backup_name = $backup_name;
    $preferences->backup_unique_code = $backup_unique_code;
    $preferences->backup_blogs = 0;
    
    $preferences->newdirectoryname = optional_param('newdirectoryname');

    // put it (back) in the session
    $SESSION->backupprefs[$course->id] = $preferences;
}

/**
 * コースセクションのバックアップ
 * 
 * @param object $bf : FileObject
 * @param object $preferences
 * @return bool
 */
function project_backup_course_sections ($bf,$preferences) {

    global $CFG;

    $status = true;


    //Get info from sections
    $section=false;
    if ($sections = get_records("course_sections","course",$preferences->backup_course,"section")) {
        //Section open tag
        fwrite ($bf,start_tag("SECTIONS",2,true));
        //Iterate over every section (ordered by section)
        foreach ($sections as $section) {
            if ($preferences->backup_section == $section->section) {
                //Begin Section
                fwrite ($bf,start_tag("SECTION",3,true));
                fwrite ($bf,full_tag("ID",4,false,$section->id));
                fwrite ($bf,full_tag("NUMBER",4,false,$section->section));
                fwrite ($bf,full_tag("SUMMARY",4,false,$section->summary));
                fwrite ($bf,full_tag("VISIBLE",4,false,$section->visible));
                //Now print the mods in section
                backup_course_modules ($bf,$preferences,$section);
                //End section
                fwrite ($bf,end_tag("SECTION",3,true));
            }
        }
        //Section close tag
        $status = fwrite ($bf,end_tag("SECTIONS",2,true));
    }
    return $status;

}
    
    
/**
 * バックアップZIPファイル名の取得
 *
 * @param object $sectiontitle
 * @return string filename (excluding path information)
 */
function project_backup_get_zipfile_name($sectiontitle, $course) {

    //Calculate the backup word
    $backup_word = 'project-section-' . $sectiontitle->directoryname;

    //Calculate the date format string
    $backup_date_format = str_replace(" ","_",get_string("backupnameformat"));
    //If non-translated, use "%Y%m%d-%H%M"
    if (substr($backup_date_format,0,1) == "[") {
        $backup_date_format = "%%Y%%m%%d-%%H%%M";
    }

    //Calculate the shortname
    $backup_shortname = clean_filename($course->shortname);
    if (empty($backup_shortname) or $backup_shortname == '_' ) {
        $backup_shortname = $course->id;
    }

    //Calculate the final backup filename
    //The backup word
    $backup_name = $backup_word."-";
    //The date format
    $backup_name .= userdate(time(),$backup_date_format,99,false);
    //The extension
    $backup_name .= ".zip";
    //And finally, clean everything
    $backup_name = clean_filename($backup_name);

    return $backup_name;
}


/**
 * 対象のセクションのコースファイルのみをコピーする
 * 
 * @param object $preferences
 * @return bool
 */
function project_backup_copy_course_files ($preferences) {

    global $CFG;

    $status = true;
    
    // 対象のコース情報の取得
    if (! $course = get_record("course", "id", $preferences->backup_course)) {
        error("Course ID was incorrect (can't find it)");
    }
    
    // セクション情報の取得
    if (! $section = get_course_section($preferences->backup_section, $course->id)) {
        error("Section data was incorrect (can't find it)");
    }

    // セクションタイトルの取得
    if (! $sectiontitle = project_format_get_title($course, $section->id)) {
        error("Section title data was incorrect (can't find it)");
    }
    
    //First we check to "course_files" exists and create it as necessary
    //in temp/backup/$backup_code  dir
    $status = check_and_create_course_files_dir($preferences->backup_unique_code);
    
    //Now iterate over files and directories except $CFG->moddata and backupdata to be
    //copied to backup

    // 対象のセクションのディレクトリ名を取得
    $rootdir = $CFG->dataroot."/".$preferences->backup_course . '/' . $sectiontitle->directoryname;
    
    // ディレクトリをまるごとコピーする
    if (is_dir($rootdir)) {
        $status = backup_copy_file($rootdir,
                       $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/course_files/".$sectiontitle->directoryname);
    }
    return $status;
}

/**
 * バックアップするセクションファイルのチェック
 * 
 * Calculate the number of course files to backup
 * under $CFG->dataroot/$course, except $CFG->moddata, and backupdata
 * and put them (their path) in backup_ids
 * Return an array of info (name,value)
 */
function project_course_files_check_backup($courseid, $sectionnumber, $backup_unique_code) {
    global $CFG;

    // 対象のコース情報の取得
    if (! $course = get_record("course", "id", $courseid)) {
        error("Course ID was incorrect (can't find it)");
    }
    
    // セクション情報の取得
    if (! $section = get_course_section($sectionnumber, $course->id)) {
        error("Section data was incorrect (can't find it)");
    }
    
    // セクションタイトルの取得
    if (! $sectiontitle = get_record('course_project_title', 'sectionid', $section->id)) {
        error("Could not find section title");
    }
    
    // バックアップ対象ディレクトリ
    $rootdir = $CFG->dataroot."/$courseid/".$sectiontitle->directoryname;
    //Check if directory exists
    if (is_dir($rootdir)) {
        //Get files and directories without descend
        $coursedirs = get_directory_list($rootdir,$CFG->moddata,false,true,true);
        $backupdata_dir = "backupdata";
        foreach ($coursedirs as $dir) {
            //Check it isn't backupdata_dir
            if (strpos($dir,$backupdata_dir)!==0) {
                //Insert them into backup_files
                $status = execute_sql("INSERT INTO {$CFG->prefix}backup_files
                                              (backup_code, file_type, path)
                                       VALUES
                                          ('$backup_unique_code','course','".addslashes($dir)."')",false);
            }
        //Do some output
        backup_flush(30);
        }
    }

    //Now execute the select
    $ids = get_records_sql("SELECT DISTINCT b.path, b.old_id
                            FROM {$CFG->prefix}backup_files b
                            WHERE backup_code = '$backup_unique_code' AND
                                  file_type = 'course'");
    // Gets the user data
    $info = array();
    // ディレクトリ名
    $info[0] = array();
    $info[0][0] = get_string('directoryname', 'format_project');
    $info[0][1] = $sectiontitle->directoryname;
    // ファイル数
    $info[1] = array();
    $info[1][0] = get_string("files");
    if ($ids) {
        $info[1][1] = count($ids);
    } else {
        $info[1][1] = 0;
    }

    return $info;
}


/**
 * バックアップの実行
 * 
 * @param object $preference
 * @param string &$errorstr
 * @return bool
 */
function project_backup_execute(&$preferences, &$errorstr) {
    global $CFG;
    $status = true;

    //Check for temp and backup and backup_unique_code directory
    //Create them as needed
    if (!defined('BACKUP_SILENTLY')) {
        echo "<li>".get_string("creatingtemporarystructures").'</li>';
    }

    $status = check_and_create_backup_dir($preferences->backup_unique_code);
    //Empty dir
    if ($status) {
        $status = clear_backup_dir($preferences->backup_unique_code);
    }

    //Delete old_entries from backup tables
    if (!defined('BACKUP_SILENTLY')) {
        echo "<li>".get_string("deletingolddata").'</li>';
    }
    $status = backup_delete_old_data();
    if (!$status) {
        if (!defined('BACKUP_SILENTLY')) {
            error ("An error occurred deleting old backup data");
        }
        else {
            $errorstr = "An error occurred deleting old backup data";
            return false;
        }
    }

    //Create the moodle.xml file
    if ($status) {
        if (!defined('BACKUP_SILENTLY')) {
            echo "<li>".get_string("creatingxmlfile");
            //Begin a new list to xml contents
            echo "<ul>";
            echo "<li>".get_string("writingheader").'</li>';
        }
        //Obtain the xml file (create and open) and print prolog information
        $backup_file = backup_open_xml($preferences->backup_unique_code);
        if (!defined('BACKUP_SILENTLY')) {
            echo "<li>".get_string("writinggeneralinfo").'</li>';
        }
        //Prints general info about backup to file
        if ($backup_file) {
            if (!$status = backup_general_info($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up general info");
                }
                else {
                    $errorstr = "An error occurred while backing up general info";
                    return false;
                }
            }
        }
        if (!defined('BACKUP_SILENTLY')) {
            echo "<li>".get_string("writingcoursedata");
            //Start new ul (for course)
            echo "<ul>";
            echo "<li>".get_string("courseinfo").'</li>';
        }
        //Prints course start (tag and general info)
        if ($status) {
            if (!$status = backup_course_start($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up course start");
                }
                else {
                    $errorstr = "An error occurred while backing up course start";
                    return false;
                }
            }
        }
        
        //Metacourse information
        if ($status && $preferences->backup_metacourse) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("metacourse").'</li>';
            }
            if (!$status = backup_course_metacourse($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up metacourse info");
                }
                else {
                    $errorstr = "An error occurred while backing up metacourse info";
                    return false;
                }
            }
        }
        
        if (!defined('BACKUP_SILENTLY')) {
            echo "<li>".get_string("sections").'</li>';
        }
        //Section info
        if ($status) {
            //if (!$status = backup_course_sections($backup_file,$preferences)) {
            if (!$status = project_backup_course_sections($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up course sections");
                }
                else {
                    $errorstr = "An error occurred while backing up course sections";
                    return false;
                }
            }
        }

        //End course contents (close ul)
        if (!defined('BACKUP_SILENTLY')) {
            echo "</ul></li>";
        }

        //User info
        if ($status) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("writinguserinfo").'</li>';
            }
            if (!$status = backup_user_info($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up user info");
                }
                else {
                    $errorstr = "An error occurred while backing up user info";
                    return false;
                }
            }
        }

        //If we have selected to backup messages and we are
        //doing a SITE backup, let's do it
        if ($status && $preferences->backup_messages && $preferences->backup_course == SITEID) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("writingmessagesinfo").'</li>';
            }
            if (!$status = backup_messages($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up messages");
                }
                else {
                    $errorstr = "An error occurred while backing up messages";
                    return false;
                }
            }
        }

        //If we have selected to backup quizzes or other modules that use questions
        //we've already added ids of categories and questions to backup to backup_ids table
        if ($status) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("writingcategoriesandquestions").'</li>';
            }
            require_once($CFG->dirroot.'/question/backuplib.php');
            if (!$status = backup_question_categories($backup_file, $preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up quiz categories");
                }
                else {
                    $errorstr = "An error occurred while backing up quiz categories";
                    return false;
                }
            }
        }

        //Print logs if selected
        if ($status) {
            if ($preferences->backup_logs) {
                if (!defined('BACKUP_SILENTLY')) {
                    echo "<li>".get_string("writingloginfo").'</li>';
                }
                if (!$status = backup_log_info($backup_file,$preferences)) {
                    if (!defined('BACKUP_SILENTLY')) {
                        notify("An error occurred while backing up log info");
                    }
                    else {
                        $errorstr = "An error occurred while backing up log info";
                        return false;
                    }
                }
            }
        }

        //Print scales info
        if ($status) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("writingscalesinfo").'</li>';
            }
            if (!$status = backup_scales_info($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up scales");
                }
                else {
                    $errorstr = "An error occurred while backing up scales";
                    return false;
                }
            }
        }

        //Print groups info
        if ($status) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("writinggroupsinfo").'</li>';
            }
            if (!$status = backup_groups_info($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up groups");
                }
                else {
                    $errostr = "An error occurred while backing up groups";
                    return false;
                }
            }
        }

        //Print groupings info
        if ($status) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("writinggroupingsinfo").'</li>';
            }
            if (!$status = backup_groupings_info($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up groupings");
                }
                else {
                    $errorstr = "An error occurred while backing up groupings";
                    return false;
                }
            }
        }

        //Print groupings_groups info
        if ($status) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("writinggroupingsgroupsinfo").'</li>';
            }
            if (!$status = backup_groupings_groups_info($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up groupings groups");
                }
                else {
                    $errorstr = "An error occurred while backing up groupings groups";
                    return false;
                }
            }
        }

        //Print events info
        if ($status) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("writingeventsinfo").'</li>';
            }
            if (!$status = backup_events_info($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up events");
                }
                else {
                    $errorstr = "An error occurred while backing up events";
                    return false;
                }
            }
        }

        //Module info, this unique function makes all the work!!
        //db export and module fileis copy
        if ($status) {
            $mods_to_backup = false;
            //Check if we have any mod to backup
            foreach ($preferences->mods as $module) {
                if ($module->backup) {
                    $mods_to_backup = true;
                }
            }
            //If we have to backup some module
            if ($mods_to_backup) {
                if (!defined('BACKUP_SILENTLY')) {
                    echo "<li>".get_string("writingmoduleinfo");
                }
                //Start modules tag
                if (!$status = backup_modules_start ($backup_file,$preferences)) {
                    if (!defined('BACKUP_SILENTLY')) {
                        notify("An error occurred while backing up module info");
                    }
                    else {
                        $errorstr = "An error occurred while backing up module info";
                        return false;
                    }
                }
                //Open ul for module list
                if (!defined('BACKUP_SILENTLY')) {
                    echo "<ul>";
                }
                //Iterate over modules and call backup
                foreach ($preferences->mods as $module) {
                    if ($module->backup and $status) {
                        if (!defined('BACKUP_SILENTLY')) {
                            echo "<li>".get_string("modulenameplural",$module->name).'</li>';
                        }
                        if (!$status = backup_module($backup_file,$preferences,$module->name)) {
                            if (!defined('BACKUP_SILENTLY')) {
                                notify("An error occurred while backing up '$module->name'");
                            }
                            else {
                                $errorstr = "An error occurred while backing up '$module->name'";
                                return false;
                            }
                        }
                    }
                }
                //Close ul for module list
                if (!defined('BACKUP_SILENTLY')) {
                    echo "</ul></li>";
                }
                //Close modules tag
                if (!$status = backup_modules_end ($backup_file,$preferences)) {
                    if (!defined('BACKUP_SILENTLY')) {
                        notify("An error occurred while finishing the module backups");
                    }
                    else {
                        $errorstr = "An error occurred while finishing the module backups";
                        return false;
                    }
                }
            }
        }

        //Backup course format data, if any.
        if (!defined('BACKUP_SILENTLY')) {
            echo '<li>'.get_string("courseformatdata").'</li>';
        }
        if($status) {
            if (!$status = backup_format_data($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while backing up the course format data");
                }
                else {
                    $errorstr = "An error occurred while backing up the course format data";
                    return false;
                }
            }
        }

        //Prints course end
        if ($status) {
            if (!$status = backup_course_end($backup_file,$preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while closing the course backup");
                }
                else {
                    $errorstr = "An error occurred while closing the course backup";
                    return false;
                }
            }
        }
        //Close the xml file and xml data
        if ($backup_file) {
            backup_close_xml($backup_file);
        }

        //End xml contents (close ul)
        if (!defined('BACKUP_SILENTLY')) {
            echo "</ul></li>";
        }
    }

    //Now, if selected, copy user files
    if ($status) {
        if ($preferences->backup_user_files) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("copyinguserfiles").'</li>';
            }
            if (!$status = backup_copy_user_files ($preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while copying user files");
                }
                else {
                    $errorstr = "An error occurred while copying user files";
                    return false;
                }
            }
        }
    }

    //Now, if selected, copy course files
    if ($status) {
        if ($preferences->backup_course_files) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("copyingcoursefiles").'</li>';
            }
            //if (!$status = backup_copy_course_files ($preferences)) {
            if (!$status = project_backup_copy_course_files ($preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while copying course files");
                }
                else {
                    $errorstr = "An error occurred while copying course files";
                    return false;
                }
            }
        }
    }
    //Now, if selected, copy site files
    if ($status) {
        if ($preferences->backup_site_files) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("copyingsitefiles").'</li>';
            }
            if (!$status = backup_copy_site_files ($preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while copying site files");
                }
                else {
                    $errorstr = "An error occurred while copying site files";
                    return false;
                }
            }
        }
    }
    //Now, zip all the backup directory contents
    if ($status) {
        if (!defined('BACKUP_SILENTLY')) {
            echo "<li>".get_string("zippingbackup").'</li>';
        }
        if (!$status = backup_zip ($preferences)) {
            if (!defined('BACKUP_SILENTLY')) {
                notify("An error occurred while zipping the backup");
            }
            else {
                $errorstr = "An error occurred while zipping the backup";
                return false;
            }
        }
    }

    //Now, copy the zip file to course directory
    if ($status) {
        if (!defined('BACKUP_SILENTLY')) {
            echo "<li>".get_string("copyingzipfile").'</li>';
        }
        if (!$status = copy_zip_to_course_dir ($preferences)) {
            if (!defined('BACKUP_SILENTLY')) {
                notify("An error occurred while copying the zip file to the course directory");
            }
            else {
                $errorstr = "An error occurred while copying the zip file to the course directory";
                return false;
            }
        }
    }

    //Now, clean temporary data (db and filesystem)
    if ($status) {
        if (!defined('BACKUP_SILENTLY')) {
            echo "<li>".get_string("cleaningtempdata").'</li>';
        }
        if (!$status = clean_temp_data ($preferences)) {
            if (!defined('BACKUP_SILENTLY')) {
                notify("An error occurred while cleaning up temporary data");
            }
            else {
                $errorstr = "An error occurred while cleaning up temporary data";
                return false;
            }
        }
    }

    return $status;
}
?>
<?php
/**
 * Moodleから呼び出されるリストアモジュール
 * 
 * @param object $restore
 * @param array $data : XMLパース
 * @return bool
 */
function project_restore_format_data($restore,$data) {
    global $CFG;
    
    $status = true;
    
    if (!defined('RESTORE_SILENTLY')) {
        echo "<ul>";
    }
    // $dataをパースする
    foreach ($data["FORMATDATA"]["#"]["SECTIONTITLES"]["0"]["#"]["SECTIONTITLE"] as $sectiontitle) {
        // XMLデータから旧セクションIDを取得
        $oldsectionid = $sectiontitle["#"]["SECTIONID"]["0"]["#"];
        
        // 新しく登録するタイトルデータを構築
        $newtitle = new StdClass;
        if ($restore->newdirectoryname) {
            // 新しいディレクトリ名がある場合
            $newtitle->directoryname = $restore->newdirectoryname;
            $restore->olddirectoryname = backup_todb($sectiontitle["#"]["DIRECTORYNAME"]["0"]["#"]);
        } else {
            $newtitle->directoryname = backup_todb($sectiontitle["#"]["DIRECTORYNAME"]["0"]["#"]);
        }
        
        
        // 新しいタイトルのレコードを登録する
        if (empty($restore->section)) {
            // 旧セクションIDから新セクションIDを取得する
            $sec = backup_getid($restore->backup_unique_code,"course_sections",$oldsectionid);
            $newtitle->sectionid = $sec->new_id;
        
            // セクション指定がない場合は新規でセクションを追加する
            if (! $newtitle->id = insert_record('course_project_title', $newtitle)) {
                $status = false;
            } else {
                // ログ出力
                if (!defined('RESTORE_SILENTLY')) {
                    echo "<li>" . get_string('sectiontitle', 'format_project') . ' "' . $newtitle->title . '"</li>';
                }
           }
        } else {
            // ディレクトリ名重複チェック
            if (!project_format_check_directoryname($restore->course_id,$newtitle->directoryname,$restore->section)) {
                // 重複していた
                error(get_string('directoryalreadyexist','format_project',$newtitle->directoryname), $CFG->wwwroot.'/course/view.php?id='.$restore->course_id);
                $status = false;
            } else {
                // セクションタイトルを指定したセクションに上書き
                if (!defined('RESTORE_SILENTLY')) {
                    echo "<li>".get_string("updatesection",'format_project') . "</li>";
                }
            
                // 対象のコース情報の取得
                if (! $course = get_record("course", "id", $restore->course_id)) {
                    error("Course ID was incorrect (can't find it)");
                }
    
                // 対象のセクション情報の取得
                if (! $section = get_course_section($restore->section, $restore->course_id)) {
                    error("Section data was incorrect (can't find it)");
                }
            
                // 現在のセクションのディレクトリ名を取得
                if (! $sectiontitle = project_format_get_title($course, $section->id)) {
                    error("Section directory was incorrect");
                }
                
                // 現在のディレクトリをストアする
                $restore->currentdirectoryname = $sectiontitle->directoryname;
                
                // タイトルIDを取得
                $newtitle->id = $sectiontitle->id;
                $status = update_record("course_project_title",$newtitle);
            }
        } 
    }
    if (!defined('RESTORE_SILENTLY')) {
        echo "</ul>";
    }
    
    return $status;
}

/**
 * セクション内のコンテンツを削除
 * 
 * @param object &$restore
 * @return bool
 */
function project_remove_section_contents(&$restore) {
    global $CFG;
    $status = true;
    
    // 対象のコース情報の取得
    if (! $course = get_record("course", "id", $restore->course_id)) {
        error("Course ID was incorrect (can't find it)");
    }
    
    // 対象のセクション情報の取得
    if (! $section = get_course_section($restore->section, $restore->course_id)) {
        error("Section data was incorrect (can't find it)");
    }
    
    if ($restore->currentdirectoryname) {
        $currentdirectoryname = $restore->currentdirectoryname;
    } else {
        // 現在のセクションのディレクトリ名を取得
        if (! $sectiontitle = project_format_get_title($course, $section->id)) {
            error("Section directory was incorrect");
        }
        $currentdirectoryname = $sectiontitle->directoryname;
    }    
    
    
    // インスタンスを削除する
    // セクション内のモジュールを取得
    get_all_mods($course->id, &$mods, &$modnames, &$modnamesplural, &$modnamesused);
    $modules = array();
    foreach ($mods as $mod) {
        if ($mod->section == $section->id) {
            $modules[] = $mod;
        }
    }
    // セクション内からモジュールを削除する
    foreach ($modules as $mod) {
        $modlib = "$CFG->dirroot/mod/$mod->modname/lib.php";
        if (file_exists($modlib)) {
            include_once($modlib);
        } else {
            error("This module is missing important code! ($modlib)");
        }
        $deleteinstancefunction = $mod->modname."_delete_instance";

        if (! $deleteinstancefunction($mod->instance)) {
            notify("Could not delete the $mod->type (instance)");
        }
        if (! delete_course_module($mod->id)) {
            notify("Could not delete the $mod->type (coursemodule)");
        }
        if (! delete_mod_from_section($mod->id, $section->id)) {
            notify("Could not delete the $mod->type from that section");
        }
    }
    
    // ディレクトリを削除する
    if (! $basedir = make_upload_directory("$course->id")) {
        error("The site administrator needs to fix the file permissions");
    }
    if (!defined('RESTORE_SILENTLY')) {
        echo "<li>".get_string("deletedirectory",'format_project');
    }
    
    if (!$status = fulldelete($basedir . "/" . $currentdirectoryname)) {
        if (!defined('RESTORE_SILENTLY')) {
            notify("Error deleting directory");
        } else {
            $errorstr = "Error deleting directory.";
            return false;
        }
    }
    if (!defined('RESTORE_SILENTLY')) {
        echo '</li>';
    }
    
    return $status;
}

/**
 * セクションのリストア
 * 
 * @param object $restore
 * @param string $xml_file
 * @return bool
 */
function project_restore_section(&$restore, $xml_file) {

    global $CFG,$db;

    $status = true;
    
    // $restoreのsectionパラメーターのチェック
    if (empty($restore->section)) {
        return false;
    }
    // セクションを取得
    if (! $section = get_course_section($restore->section, $restore->course_id)) {
        error("Section data was incorrect (can't find it)");
    }
    
    //Check it exists
    if (!file_exists($xml_file)) {
        $status = false;
    }
    
    //Get info from xml
    if ($status) {
        $info = restore_read_xml_sections($xml_file);
    }
    
    //Put the info in the DB, recoding ids and saving the in backup tables

    $sequence = "";
    if ($info) {
        //For each, section, save it to db
        foreach ($info->sections as $key => $sect) {
            $sequence = "";
            $update_rec = new object();
            $update_rec->id = $section->id;
            $update_rec->course = $restore->course_id;
            $update_rec->section = $restore->section;
            $update_rec->summary = backup_todb($sect->summary);
            $update_rec->visible = $sect->visible;
            $update_rec->sequence = "";
            $status = update_record('course_sections', $update_rec);
            $newid = $update_rec->id;
            
            if ($newid) {
                //save old and new section id
                backup_putid ($restore->backup_unique_code,"course_sections",$key,$newid);
            } else {
                $status = false;
            }
            //If all is OK, go with associated mods
            if ($status) {
                //If we have mods in the section
                if (!empty($sect->mods)) {
                    //For each mod inside section
                    foreach ($sect->mods as $keym => $mod) {
                        // Yu: This part is called repeatedly for every instance,
                        // so it is necessary to set the granular flag and check isset()
                        // when the first instance of this type of mod is processed.

                        //if (!isset($restore->mods[$mod->type]->granular) && isset($restore->mods[$mod->type]->instances) && is_array($restore->mods[$mod->type]->instances)) {

                        if (!isset($restore->mods[$mod->type]->granular)) {
                            if (isset($restore->mods[$mod->type]->instances) && is_array($restore->mods[$mod->type]->instances)) {
                                // This defines whether we want to restore specific
                                // instances of the modules (granular restore), or
                                // whether we don't care and just want to restore
                                // all module instances (non-granular).
                                $restore->mods[$mod->type]->granular = true;
                            } else {
                                $restore->mods[$mod->type]->granular = false;
                            }
                        }

                        //Check if we've to restore this module (and instance)
                        if (!empty($restore->mods[$mod->type]->restore)) {
                            if (empty($restore->mods[$mod->type]->granular)  // we don't care about per instance
                                || (array_key_exists($mod->instance,$restore->mods[$mod->type]->instances)
                                    && !empty($restore->mods[$mod->type]->instances[$mod->instance]->restore))) {

                                //Get the module id from modules
                                $module = get_record("modules","name",$mod->type);
                                if ($module) {
                                    $course_module = new object();
                                    $course_module->course = $restore->course_id;
                                    $course_module->module = $module->id;
                                    $course_module->section = $newid;
                                    $course_module->added = $mod->added;
                                    $course_module->score = $mod->score;
                                    $course_module->indent = $mod->indent;
                                    $course_module->visible = $mod->visible;
                                    $course_module->groupmode = $mod->groupmode;
                                    if ($mod->groupingid and $grouping = backup_getid($restore->backup_unique_code,"groupings",$mod->groupingid)) {
                                        $course_module->groupingid = $grouping->new_id;
                                    } else {
                                        $course_module->groupingid = 0;
                                    }
                                    $course_module->groupmembersonly = $mod->groupmembersonly;
                                    $course_module->instance = 0;
                                    //NOTE: The instance (new) is calculated and updated in db in the
                                    //      final step of the restore. We don't know it yet.
                                    //print_object($course_module);                    //Debug
                                    //Save it to db

                                    $newidmod = insert_record("course_modules",$course_module);
                                    if ($newidmod) {
                                        //save old and new module id
                                        //In the info field, we save the original instance of the module
                                        //to use it later
                                        backup_putid ($restore->backup_unique_code,"course_modules",
                                                      $keym,$newidmod,$mod->instance);

                                        $restore->mods[$mod->type]->instances[$mod->instance]->restored_as_course_module = $newidmod;
                                    } else {
                                        $status = false;
                                    }
                                    //Now, calculate the sequence field
                                    if ($status) {
                                        if ($sequence) {
                                            $sequence .= ",".$newidmod;
                                        } else {
                                            $sequence = $newidmod;
                                        }
                                    }
                                } else {
                                    $status = false;
                                }
                            }
                        }
                    }
                }
            }
            //If all is OK, update sequence field in course_sections
            if ($status) {
                if (isset($sequence)) {
                    $update_rec = new object();
                    $update_rec->id = $newid;
                    $update_rec->sequence = $sequence;
                    $status = update_record("course_sections",$update_rec);
                }
            }
        }
    } else {
        $status = false;
    }

    return $status;
}


/**
 * コースファイルのコピー
 * 
 * @param object $restore
 * @return
 */
function project_restore_course_files(&$restore) {

    global $CFG;

    $status = true;

    $counter = 0;

    // 対象のコース情報の取得
    if (! $course = get_record("course", "id", $restore->course_id)) {
        error("Course ID was incorrect (can't find it)");
    }
    
    // 対象のセクション情報の取得
    if (! $section = get_course_section($restore->section, $restore->course_id)) {
        error("Section data was incorrect (can't find it)");
    }
    
    // 現在のセクションのディレクトリ名を取得
    if (! $sectiontitle = project_format_get_title($course, $section->id)) {
        error("Section directory was incorrect");
    }
    
    //First, we check to "course_id" exists and create is as necessary
    //in CFG->dataroot
    $dest_dir = $CFG->dataroot."/".$restore->course_id.'/'.$restore->newdirectoryname;
    $status = check_dir_exists($dest_dir,true);

    //Now, we iterate over "course_files" records to check if that file/dir must be
    //copied to the "dest_dir" dir.
    $rootdir = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code."/course_files/".$restore->olddirectoryname;
    
    // ディレクトリをまるごとコピーする
    if (is_dir($rootdir)) {
        $status = backup_copy_file($rootdir, $dest_dir);
    }
    
    return $status;
}


/**
 * リストア先のフォルダ一覧を作成
 * 
 * @param object $resotre
 */
function project_restore_create_directory_list(&$restore) {
    
    global $CFG;
    $currentfiles = array();
    
    // 過去のディレクトリ名のチェック
    if (!$restore->olddirectoryname) {
        return true;
    }
    
    // ディレクトリが存在するかチェック
    $destdir = $CFG->dataroot.'/'.$restore->course_id.'/'.$restore->olddirectoryname;
    if ($currentfiles = project_restore_get_directory($destdir)) {
        // リストア配列をセット
        $restore->currentfiles = $currentfiles;
    }
    
    return true;
}


/**
 * 新しくできたディレクトリ内から新しいファイルを削除
 * 
 * @param string &$restore
 * @param array $currentfiles
 * @return bool
 */
function project_restore_remove_diff_files(&$restore) {
    global $CFG;
    
    // 過去のディレクトリ名のチェック
    if (!$restore->olddirectoryname) {
        return true;
    }
    
    // 作成するディレクトリと過去のディレクトリの同一チェック
    if ($restore->olddirectoryname == $restore->newdirectoryname) {
        return true;
    }
    
    // 過去のファイル一覧が空のときはディレクトリを削除
    $destdir = $CFG->dataroot.'/'.$restore->course_id.'/'.$restore->olddirectoryname;
    if (!is_array($restore->currentfiles) || !$restore->currentfiles) {
        if (!fulldelete($destdir)) {
            error("Can't remove directory.");
            return false;
        }
    } else {
        // 現在のディレクトリから一覧を取得
        if ($temporaryfiles = project_restore_get_directory($destdir)) {
            foreach ($temporaryfiles as $file) {
                // 元々あったファイル一覧と比較
                if (array_search($file, $restore->currentfiles) === false) {
                    // 元々あったファイルではなかった場合削除
                    if (!unlink($destdir.'/'.$file)) {
                        error("Can't unlink file");
                    }
                }
            }
        }
    }
    return true;
}


/**
 * ディレクトリ一覧の取得
 * 
 * @param string $destdir
 * @return array
 */
function project_restore_get_directory($destdir) {
    if (is_dir($destdir)) {
        // ファイル戻り配列
        $currentfiles = array();
        
        //ディレクトリ一覧を取得
        $resdir = opendir($destdir);
        while($filename = readdir($resdir)){
            if ($filename != '.' && $filename != '..') {
                $currentfiles[] = $filename;
            }
        }
        closedir($resdir);
        
        return $currentfiles;
    } else {
        return false;
    }
}

/**
 * リストアの実行
 * 
 * @
 */
function project_restore_execute(&$restore,$info,$course_header,&$errorstr) {

    global $CFG, $USER;
    $status = true;
    
    //Localtion of the xml file
    $xml_file = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code."/moodle.xml";
    
    if($status) {
        //If we are deleting and bringing into a course or making a new course, same situation
        if (!defined('RESTORE_SILENTLY')) {
            echo '<li>'.get_string('courseformatdata');
        }
        if (!$status = restore_set_format_data($restore, $xml_file)) {
            $error = "Error while setting the course format data";
            if (!defined('RESTORE_SILENTLY')) {
                notify($error);
            } else {
                $errorstr=$error;
                return false;
            }
        }
        if (!defined('RESTORE_SILENTLY')) {
            echo '</li>';
        }
    }
    
    // リストア先のフォルダがバックアップデータと重複する場合
    // フォルダ内のファイル一覧リストを作成する
    if ($status) {
        if (!defined('RESTORE_SILENTLY')) {
            echo '<li>'.get_string('getcurrentdirectorylist','format_project');
        }
        if (!$status = project_restore_create_directory_list($restore)) {
            $error = "Error while getting directory list";
            if (!defined('RESTORE_SILENTLY')) {
                notify($error);
            } else {
                $errorstr=$error;
                return false;
            }
        }
        if (!defined('RESTORE_SILENTLY')) {
            echo '</li>';
        }
    }
    
//    //Checks for the required files/functions to restore every module
//    //and include them
//    if ($allmods = get_records("modules") ) {
//        foreach ($allmods as $mod) {
//            $modname = $mod->name;
//            $modfile = "$CFG->dirroot/mod/$modname/restorelib.php";
//            
//            //If file exists and we have selected to restore that type of module
//            if ((file_exists($modfile)) and !empty($restore->mods[$modname]) and ($restore->mods[$modname]->restore)) {
//                include_once($modfile);
//            }
//        }
//    }

    if (!defined('RESTORE_SILENTLY')) {
        //Start the main table
        echo "<table cellpadding=\"5\">";
        echo "<tr><td>";

        //Start the main ul
        echo "<ul>";
    }


	if (file_exists($shared_lib = $CFG->dirroot.'/blocks/sharing_cart/shared/SharingCart_Restore.php')) {
	    require_once $shared_lib;
	} else {
	    require_once dirname(__FILE__).'/shared/SharingCart_Restore.php';
	}
	
	$course_id = $restore->course_id;
	$section_i = $restore->section;
	
	//$prev_restore = clone $restore;
	
	$worker = new SharingCart_Restore($course_id, $section_i);
	
	$worker->beginPreferences();
	
	//$restore = $prev_restore;
	
	if ($file = optional_param('file')) {
		$worker->setZipDir($CFG->dataroot.'/'.dirname($file));
	} else {
		$worker->setZipDir($CFG->dataroot.'/'.$course_id.'/backupdata');
	}
	$worker->setZipName($info->backup_name);
	
	$worker->setRestoreSectionStatus(TRUE);
	$worker->setRestoreModulesStatus(TRUE);
	
	$worker->endPreferences();
	
	$worker->execute();


//    // 復旧先のディレクトリを削除
//    if ($status) {
//        if (!defined('RESTORE_SILENTLY')) {
//            echo "<li>".get_string("deletingolddata").'</li>';
//        }
//        if (!$status = project_remove_section_contents($restore)) {
//            if (!defined('RESTORE_SILENTLY')) {
//                notify("An error occurred while deleting some of the course contents.");
//            } else {
//                $errrostr = "An error occurred while deleting some of the course contents.";
//                return false;
//            }
//        }
//    }
//    
//    //Now create the course_sections and their associated course_modules
//    //we have to do this after groups and groupings are restored, because we need the new groupings id
//    if ($status) {
//        //Into new course
//        if (!defined('RESTORE_SILENTLY')) {
//            echo "<li>".get_string("checkingsections");
//        }
//        if (!$status = project_restore_section($restore,$xml_file)) {
//            if (!defined('RESTORE_SILENTLY')) {
//                notify("Error creating sections in the existing course.");
//            } else {
//                $errorstr = "Error creating sections in the existing course.";
//                return false;
//            }
//        }
//        if (!defined('RESTORE_SILENTLY')) {
//            echo '</li>';
//        }
//    }
//
//    //Now create categories and questions as needed
//    if ($status) {
//        include_once("$CFG->dirroot/question/restorelib.php");
//        if (!defined('RESTORE_SILENTLY')) {
//            echo "<li>".get_string("creatingcategoriesandquestions");
//            echo "<ul>";
//        }
//        if (!$status = restore_create_questions($restore,$xml_file)) {
//            if (!defined('RESTORE_SILENTLY')) {
//                notify("Could not restore categories and questions!");
//            } else {
//                $errorstr = "Could not restore categories and questions!";
//                return false;
//            }
//        }
//        if (!defined('RESTORE_SILENTLY')) {
//            echo "</ul></li>";
//        }
//    }
//
//    //Now create course files as needed
//    if ($status and ($restore->course_files)) {
//        if (!defined('RESTORE_SILENTLY')) {
//            echo "<li>".get_string("copyingcoursefiles");
//        }
//        if (!$status = project_restore_course_files($restore)) {
//            if (empty($status)) {
//                notify("Could not restore course files!");
//            } else {
//                $errorstr = "Could not restore course files!";
//                return false;
//            }
//        }
//        //If all is ok (and we have a counter)
//        if ($status and ($status !== true)) {
//            //Inform about user dirs created from backup
//            if (!defined('RESTORE_SILENTLY')) {
//                echo "<ul>";
//                echo "<li>".get_string("filesfolders").": ".$status.'</li>';
//                echo "</ul>";
//            }
//        }
//        if (!defined('RESTORE_SILENTLY')) {
//            echo "</li>";
//        }
//    }
//    
//    //Now create course modules as needed
//    if ($status) {
//        if (!defined('RESTORE_SILENTLY')) {
//            echo "<li>".get_string("creatingcoursemodules");
//        }
//        
//        if (!$status = restore_create_modules($restore,$xml_file)) {
//            if (!defined('RESTORE_SILENTLY')) {
//                notify("Could not restore modules!");
//            } else {
//                $errorstr = "Could not restore modules!";
//                return false;
//            }
//        }
//        
//        if (!defined('RESTORE_SILENTLY')) {
//            echo '</li>';
//        }
//    }
//    
//    //Now, if all is OK, adjust the instance field in course_modules !!
//    if ($status) {
//        if (!defined('RESTORE_SILENTLY')) {
//            echo "<li>".get_string("checkinginstances");
//        }
//        if (!$status = restore_check_instances($restore)) {
//            if (!defined('RESTORE_SILENTLY')) {
//                notify("Could not adjust instances in course_modules!");
//            } else {
//                $errorstr = "Could not adjust instances in course_modules!";
//                return false;
//            }
//        }
//        if (!defined('RESTORE_SILENTLY')) {
//            echo '</li>';
//        }
//    }
//
//    //Now, if all is OK, adjust inter-activity links
//    if ($status) {
//        if (!defined('RESTORE_SILENTLY')) {
//            echo "<li>".get_string("decodinginternallinks");
//        }
//        if (!$status = restore_decode_content_links($restore)) {
//            if (!defined('RESTORE_SILENTLY')) {
//                notify("Could not decode content links!");
//            } else {
//                $errorstr = "Could not decode content links!";
//                return false;
//            }
//        }
//        if (!defined('RESTORE_SILENTLY')) {
//            echo '</li>';
//        }
//    }
//
//    // リストア先のフォルダがバックアップデータと重複する場合
//    // フォルダ内のファイル一覧リストを作成する
//    if ($status) {
//        if (!defined('RESTORE_SILENTLY')) {
//            echo '<li>'.get_string('removenewdirectorylist','format_project');
//        }
//        if (!$status = project_restore_remove_diff_files($restore)) {
//            $error = "Error while getting directory list";
//            if (!defined('RESTORE_SILENTLY')) {
//                notify($error);
//            } else {
//                $errorstr=$error;
//                return false;
//            }
//        }
//        if (!defined('RESTORE_SILENTLY')) {
//            echo '</li>';
//        }
//    }
//    
//    //Cleanup temps (files and db)
//    if ($status) {
//        if (!defined('RESTORE_SILENTLY')) {
//            echo "<li>".get_string("cleaningtempdata");
//        }
//        if (!$status = clean_temp_data ($restore)) {
//            if (!defined('RESTORE_SILENTLY')) {
//                notify("Could not clean up temporary data from files and database");
//            } else {
//                $errorstr = "Could not clean up temporary data from files and database";
//                return false;
//            }
//        }
//        if (!defined('RESTORE_SILENTLY')) {
//            echo '</li>';
//        }
//    }
        
    if (!defined('RESTORE_SILENTLY')) {
        //End the main ul
        echo "</ul>";

        //End the main table
        echo "</td></tr>";
        echo "</table>";
    }

    return $status;
}

?>
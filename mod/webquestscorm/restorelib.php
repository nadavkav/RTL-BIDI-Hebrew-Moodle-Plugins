<?php 
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: restorelib.php v 2.0 2009/25/04
 * @package webquestscorm
 **/
    //This php script contains all the stuff to backup/restore
    //webquestscorm mods

    //This is the "graphical" structure of the webquestscorm mod:
    //
    //                     webquestscorm
    //                    (CL,pk->id)             
    //                        |
    //                        |
    //                        |
    //                 webquestscorm_submisions 
    //           (UL,pk->id, fk->webquestscorm,files)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    //This function executes all the restore procedure about this mod
    function webquestscorm_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //if necessary, write to restorelog and adjust date/time fields
            if ($restore->course_startdateoffset) {
                restore_log_date_changes('Webquestscorm', $restore, $info['MOD']['#'], array('TIMEDUE', 'TIMEAVAILABLE'));
            }
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //Now, build the webquestscorm record structure
            $webquestscorm->course = $restore->course_id;
            $webquestscorm->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $webquestscorm->grade = backup_todb($info['MOD']['#']['GRADE']['0']['#']);
            $webquestscorm->timeavailable = backup_todb($info['MOD']['#']['TIMEAVAILABLE']['0']['#']);
            $webquestscorm->timedue = backup_todb($info['MOD']['#']['TIMEDUE']['0']['#']);
            $webquestscorm->dueenable = backup_todb($info['MOD']['#']['DUEENABLE']['0']['#']);
            $webquestscorm->dueyear = backup_todb($info['MOD']['#']['DUEYEAR']['0']['#']);
            $webquestscorm->duemonth = backup_todb($info['MOD']['#']['DUEMONTH']['0']['#']);
            $webquestscorm->dueday = backup_todb($info['MOD']['#']['DUEDAY']['0']['#']);
            $webquestscorm->duehour = backup_todb($info['MOD']['#']['DUEHOUR']['0']['#']);
            $webquestscorm->dueminute = backup_todb($info['MOD']['#']['DUEMINUTE']['0']['#']);
            $webquestscorm->availableenable = backup_todb($info['MOD']['#']['AVAILABLEENABLE']['0']['#']);
            $webquestscorm->availableyear = backup_todb($info['MOD']['#']['AVAILABLEYEAR']['0']['#']);
            $webquestscorm->availablemonth = backup_todb($info['MOD']['#']['AVAILABLEMONTH']['0']['#']);
            $webquestscorm->availableday = backup_todb($info['MOD']['#']['AVAILABLEDAY']['0']['#']);
            $webquestscorm->availablehour = backup_todb($info['MOD']['#']['AVAILABLEHOUR']['0']['#']);
            $webquestscorm->availableminute = isset($info['MOD']['#']['AVAILABLEMINUTE']['0']['#'])?backup_todb($info['MOD']['#']['TYPE']['0']['#']):'';
            $webquestscorm->maxbytes = backup_todb($info['MOD']['#']['MAXBYTES']['0']['#']);
            $webquestscorm->preventlate = backup_todb($info['MOD']['#']['PREVENTLATE']['0']['#']);
            $webquestscorm->resubmit = backup_todb($info['MOD']['#']['RESUBMIT']['0']['#']);
            $webquestscorm->emailteachers = backup_todb($info['MOD']['#']['EMAILTEACHERS']['0']['#']);
            $webquestscorm->template = backup_todb($info['MOD']['#']['TEMPLATE']['0']['#']);
            $webquestscorm->introduction = backup_todb($info['MOD']['#']['INTRODUCTION']['0']['#']);
            $webquestscorm->task = backup_todb($info['MOD']['#']['TASK']['0']['#']);
            $webquestscorm->process = backup_todb($info['MOD']['#']['PROCESS']['0']['#']);
            $webquestscorm->evaluation = backup_todb($info['MOD']['#']['EVALUATION']['0']['#']);
            $webquestscorm->conclusion = backup_todb($info['MOD']['#']['CONCLUSION']['0']['#']);
            $webquestscorm->credits = backup_todb($info['MOD']['#']['CREDITS']['0']['#']);
            $webquestscorm->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the grade field if it is <0 (scale)
            if ($webquestscorm->grade < 0) {
                $scale = backup_getid($restore->backup_unique_code,"scale",abs($webquestscorm->grade));        
                if ($scale) {
                    $webquestscorm->grade = -($scale->new_id);       
                }
            }

         
            //The structure is equal to the db, so insert the webquestscorm
            $newid = insert_record ("webquestscorm",$webquestscorm);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","webquestscorm")." \"".format_string(stripslashes($webquestscorm->name),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
                //Now check if want to restore user data and do it.
                if (restore_userdata_selected($restore,'webquestscorm',$mod->id)) { 
                    //Restore assignmet_submissions
                    $status = webquestscorm_submissions_restore_mods($mod->id, $newid,$info,$restore) && $status;
                }
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

    //This function restores the webquestscorm_submissions
    function webquestscorm_submissions_restore_mods($old_webquestscorm_id, $new_webquestscorm_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the submissions array - it might not be present
        if (isset($info['MOD']['#']['SUBMISSIONS']['0']['#']['SUBMISSION'])) {
            $submissions = $info['MOD']['#']['SUBMISSIONS']['0']['#']['SUBMISSION'];
        } else {
            $submissions = array();
        }

        //Iterate over submissions
        for($i = 0; $i < sizeof($submissions); $i++) {
            $sub_info = $submissions[$i];
            //traverse_xmlize($sub_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($sub_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($sub_info['#']['USERID']['0']['#']);

            //Now, build the webquestscorm_SUBMISSIONS record structure
            $submission->webquestscorm = $new_webquestscorm_id;
            $submission->userid = backup_todb($sub_info['#']['USERID']['0']['#']);
            $submission->timecreated = backup_todb($sub_info['#']['TIMECREATED']['0']['#']);
            $submission->timemodified = backup_todb($sub_info['#']['TIMEMODIFIED']['0']['#']);
            $submission->numfiles = backup_todb($sub_info['#']['NUMFILES']['0']['#']);
            $submission->data1 = backup_todb($sub_info['#']['DATA1']['0']['#']);
            $submission->data2 = backup_todb($sub_info['#']['DATA2']['0']['#']);
            $submission->grade = backup_todb($sub_info['#']['GRADE']['0']['#']);
            if (isset($sub_info['#']['COMMENT']['0']['#'])) {
                $submission->submissioncomment = backup_todb($sub_info['#']['COMMENT']['0']['#']);
            } else {
                $submission->submissioncomment = backup_todb($sub_info['#']['SUBMISSIONCOMMENT']['0']['#']);
            }  
            $submission->format = backup_todb($sub_info['#']['FORMAT']['0']['#']);
            $submission->teacher = backup_todb($sub_info['#']['TEACHER']['0']['#']);
            $submission->timemarked = backup_todb($sub_info['#']['TIMEMARKED']['0']['#']);
            $submission->mailed = backup_todb($sub_info['#']['MAILED']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$submission->userid);
            if ($user) {
                $submission->userid = $user->new_id;
            }

            //We have to recode the teacher field
            $user = backup_getid($restore->backup_unique_code,"user",$submission->teacher);
            if ($user) {
                $submission->teacher = $user->new_id;
            } 

            //The structure is equal to the db, so insert the webquestscorm_submission
            $newid = insert_record ("webquestscorm_submissions",$submission);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"webquestscorm_submission",$oldid,
                             $newid);

                //Now copy moddata associated files
                $status = webquestscorm_restore_files ($old_webquestscorm_id, $new_webquestscorm_id, 
                                                    $olduserid, $submission->userid, $restore);

            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function copies the webquestscorm related info from backup temp dir to course moddata folder,
    //creating it if needed and recoding everything (webquestscorm id and user id) 
    function webquestscorm_restore_files ($oldassid, $newassid, $olduserid, $newuserid, $restore) {

        global $CFG;

        $status = true;
        $todo = false;
        $moddata_path = "";
        $webquestscorm_path = "";
        $temp_path = "";

        //First, we check to "course_id" exists and create is as necessary
        //in CFG->dataroot
        $dest_dir = $CFG->dataroot."/".$restore->course_id;
        $status = check_dir_exists($dest_dir,true);

        //Now, locate course's moddata directory
        $moddata_path = $CFG->dataroot."/".$restore->course_id."/".$CFG->moddata;
   
        //Check it exists and create it
        $status = check_dir_exists($moddata_path,true);

        //Now, locate webquestscorm directory
        if ($status) {
            $webquestscorm_path = $moddata_path."/webquestscorm";
            //Check it exists and create it
            $status = check_dir_exists($webquestscorm_path,true);
        }

        //Now locate the temp dir we are gong to restore
        if ($status) {
            $temp_path = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code.
                         "/moddata/webquestscorm/".$oldassid."/".$olduserid;
            //Check it exists
            if (is_dir($temp_path)) {
                $todo = true;
            }
        }

        //If todo, we create the neccesary dirs in course moddata/webquestscorm
        if ($status and $todo) {
            //First this webquestscorm id
            $this_webquestscorm_path = $webquestscorm_path."/".$newassid;
            $status = check_dir_exists($this_webquestscorm_path,true);
            //Now this user id
            $user_webquestscorm_path = $this_webquestscorm_path."/".$newuserid;
            //And now, copy temp_path to user_webquestscorm_path
            $status = backup_copy_file($temp_path, $user_webquestscorm_path); 
        }
       
        return $status;
    }

    //Return a content decoded to support interactivities linking. Every module
    //should have its own. They are called automatically from
    //webquestscorm_decode_content_links_caller() function in each module
    //in the restore process
    function webquestscorm_decode_content_links ($content,$restore) {
            
        global $CFG;
            
        $result = $content;
                
        //Link to the list of webquestscorms
                
        $searchstring='/\$@(WEBQUESTSCORMINDEX)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$content,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course id)
                $rec = backup_getid($restore->backup_unique_code,"course",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(WEBQUESTSCORMINDEX)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/webquestscorm/index.php?id='.$rec->new_id,$result);
                } else { 
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/webquestscorm/index.php?id='.$old_id,$result);
                }
            }
        }

        //Link to webquestscorm view by moduleid

        $searchstring='/\$@(WEBQUESTSCORMVIEWBYID)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$result,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course_modules id)
                $rec = backup_getid($restore->backup_unique_code,"course_modules",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(WEBQUESTSCORMVIEWBYID)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/webquestscorm/view.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/webquestscorm/view.php?id='.$old_id,$result);
                }
            }
        }

        return $result;
    }

    //This function makes all the necessary calls to xxxx_decode_content_links()
    //function in each module, passing them the desired contents to be decoded
    //from backup format to destination site/course in order to mantain inter-activities
    //working in the backup/restore process. It's called from restore_decode_content_links()
    //function in restore process
    function webquestscorm_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

        if ($webquestscorms = get_records_sql ("SELECT a.id,a.introduction
                                   FROM {$CFG->prefix}webquestscorm a
                                   WHERE a.course = $restore->course_id")) {
            //Iterate over each webquestscorm->introduction
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($webquestscorms as $webquestscorm) {
                //Increment counter
                $i++;
                $content = $webquestscorm->introduction;
                $result = restore_decode_content_links_worker($content,$restore);
                if ($result != $content) {
                    //Update record
                    $webquestscorm->introduction = addslashes($result);
                    $status = update_record("webquestscorm",$webquestscorm);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                }
                //Do some output
                if (($i+1) % 5 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 100 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }
        }
        return $status;
    }

    //This function converts texts in FORMAT_WIKI to FORMAT_MARKDOWN for
    //some texts in the module
    function webquestscorm_restore_wiki2markdown ($restore) {
    
        global $CFG;

        $status = true;

        //Convert webquestscorm->description
        if ($records = get_records_sql ("SELECT a.id, a.description, a.format
                                         FROM {$CFG->prefix}webquestscorm a,
                                              {$CFG->prefix}backup_ids b
                                         WHERE a.course = $restore->course_id AND
                                               a.format = ".FORMAT_WIKI. " AND
                                               b.backup_code = $restore->backup_unique_code AND
                                               b.table_name = 'webquestscorm' AND
                                               b.new_id = a.id")) {
            foreach ($records as $record) {
                //Rebuild wiki links
                $record->description = restore_decode_wiki_content($record->description, $restore);
                //Convert to Markdown
                $wtm = new WikiToMarkdown();
                $record->description = $wtm->convert($record->description, $restore->course_id);
                $record->format = FORMAT_MARKDOWN;
                $status = update_record('webquestscorm', addslashes_object($record));
                //Do some output
                $i++;
                if (($i+1) % 1 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 20 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }

        }
        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function webquestscorm_restore_logs($restore,$log) {
                    
        $status = false;
                    
        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view all":
            $log->url = "index.php?id=".$log->course;
            $status = true;
            break;
        case "upload":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?a=".$mod->new_id;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view submission":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "submissions.php?id=".$mod->new_id;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update grades":
            if ($log->cmid) {
                //Extract the webquestscorm id from the url field                             
                $assid = substr(strrchr($log->url,"="),1);
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$assid);
                if ($mod) {
                    $log->url = "submissions.php?id=".$mod->new_id;
                    $status = true;
                }
            }
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
?>

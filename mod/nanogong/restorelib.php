<?php //$Id: restorelib.php,v 4 2010/04/22 00:00:00 gibson,oz Exp $
    //This php script contains all the stuff to backup/restore
    //nanogong mods

    //This is the "graphical" structure of the nanogong mod:
    //
    //                     nanogong
    //                    (CL,pk->id)             
    //                        |
    //                        |
    //                        |
    //                 nanogong_message 
    //           (UL,pk->id, fk->nanogongid,files)
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
    function nanogong_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //if necessary, write to restorelog and adjust date/time fields
            if ($restore->course_startdateoffset) {
                restore_log_date_changes('NanoGong', $restore, $info['MOD']['#'], array('TIMEDUE', 'TIMEAVAILABLE'));
            }
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            // Now, build the NANOGONG record structure
            $nanogong->course = $restore->course_id;
            $nanogong->id = backup_todb($info['MOD']['#']['ID']['0']['#']);
            $nanogong->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $nanogong->message = backup_todb($info['MOD']['#']['MESSAGE']['0']['#']);
            $nanogong->color = backup_todb($info['MOD']['#']['COLOR']['0']['#']);
            $nanogong->maxduration = backup_todb($info['MOD']['#']['MAXDURATION']['0']['#']);
            $nanogong->maxmessages = backup_todb($info['MOD']['#']['MAXMESSAGES']['0']['#']);
            $nanogong->maxscore = backup_todb($info['MOD']['#']['MAXSCORE']['0']['#']);
            $nanogong->allowguestaccess = backup_todb($info['MOD']['#']['ALLOWGUESTACCESS']['0']['#']);
            $nanogong->timecreated = backup_todb($info['MOD']['#']['TIMECREATED']['0']['#']);
            $nanogong->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //The structure is equal to the db, so insert the nanogong
            $newid = insert_record ("nanogong",$nanogong);

            //Do some output
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","nanogong")." \"".format_string(stripslashes($nanogong->name),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, $mod->modtype, $mod->id, $newid);
                //Now check if want to restore user data and do it.
                if (restore_userdata_selected($restore,'nanogong',$mod->id)) { 
                    //Restore nanogong messages
                    $status = nanogong_messages_restore_mods ($mod->id, $newid, $info, $restore);
                }
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

    //This function restores the nanogong messages
    function nanogong_messages_restore_mods($old_nanogong_id, $new_nanogong_id, $info, $restore) {

        global $CFG;

        $status = true;

        //Get the messages array 
        $messages = $info['MOD']['#']['MESSAGES']['0']['#']['MESSAGE'];
        
        //Iterate over messages
        for($i = 0; $i < sizeof($messages); $i++) {
            $sub_info = $messages[$i];
            //traverse_xmlize($sub_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($sub_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($sub_info['#']['USERID']['0']['#']);

            //Now, build the MESSAGE record structure
            $message->id = backup_todb($sub_info['#']['ID']['0']['#']);
            $message->nanogongid = $new_nanogong_id;
            $message->userid = backup_todb($sub_info['#']['USERID']['0']['#']);
            $message->groupid = backup_todb($sub_info['#']['GROUPID']['0']['#']);
            $message->title = backup_todb($sub_info['#']['TITLE']['0']['#']);
            $message->message = backup_todb($sub_info['#']['MESSAGE']['0']['#']);
            $message->path = backup_todb($sub_info['#']['PATH']['0']['#']);
            $message->comments = backup_todb($sub_info['#']['COMMENTS']['0']['#']);
            $message->commentedby = backup_todb($sub_info['#']['COMMENTEDBY']['0']['#']);
            $message->score = backup_todb($sub_info['#']['SCORE']['0']['#']);
            $message->timestamp = backup_todb($sub_info['#']['TIMESTAMP']['0']['#']);
            $message->timeedited = backup_todb($sub_info['#']['TIMEEDITED']['0']['#']);
            $message->locked = backup_todb($sub_info['#']['LOCKED']['0']['#']);

            //Sound filename
            $fn = $message->path;

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$message->userid);
            if ($user) {
                $message->userid = $user->new_id;
                $message->path = "/".$restore->course_id."/".$CFG->moddata."/nanogong/".$message->userid."/".$message->path;
            }

            //We have to recode the commentedby field
            $user = backup_getid($restore->backup_unique_code,"user",$message->commentedby);
            if ($user) {
                $message->commentedby = $user->new_id;
            } 

            //The structure is equal to the db, so insert the message
            $newid = insert_record ("nanogong_message", $message);

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
                backup_putid($restore->backup_unique_code, "nanogong_message", $oldid, $newid);

                //Now copy moddata associated files
                $status = nanogong_restore_files ($old_nanogong_id, $new_nanogong_id, $olduserid, $message->userid, $message->path, $restore);

            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function copies the nanogong related info from backup temp dir to course moddata folder,
    //creating it if needed and recoding everything (nanogong id and user id) 
    function nanogong_restore_files ($oldngid, $newngid, $olduserid, $newuserid, $path, $restore) {

        global $CFG;

        $status = true;
        $todo = false;
        $moddata_path = "";
        $nanogong_path = "";
        $temp_path = "";

        //First, we check to "course_id" exists and create is as necessary
        //in CFG->dataroot
        $dest_dir = $CFG->dataroot."/".$restore->course_id;
        $status = check_dir_exists($dest_dir,true);

        //Now, locate course's moddata directory
        $moddata_path = $CFG->dataroot."/".$restore->course_id."/".$CFG->moddata;
        $status = check_dir_exists($moddata_path,true);

        //Now, locate nanogong directory
        $nanogong_path = $moddata_path."/nanogong";
        $status = check_dir_exists($nanogong_path, true);

        //Now locate the temp dir we are going to restore
        if ($status) {
            $fn = array_pop(explode('/', $path));
            $temp_path = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code."/moddata/nanogong/".$olduserid."/".$fn;
            //Check it exists
            if (is_file($temp_path)) {
                $todo = true;
            }
        }

        //If todo, we create the neccesary dirs in course moddata/nanogong
        if ($status and $todo) {
            //Now this user id
            $user_nanogong_path = $CFG->dataroot.$path;
            $full_path = explode('/', $path);
            $the_path = '';
            while(count($full_path) > 1) {
            	$new_dir = array_shift($full_path);
            	$the_path .= $new_dir."/";
            	
            	check_dir_exists($CFG->dataroot.$the_path, true);
            }
            
            //$status = check_dir_exists(dirname($user_nanogong_path), true, true);

            //And now, copy temp_path to user_nanogong_path
            $status = backup_copy_file($temp_path, $user_nanogong_path); 
        }
       
        return $status;
    }

    //Return a content decoded to support interactivities linking. Every module
    //should have its own. They are called automatically from
    //nanogong_decode_content_links_caller() function in each module
    //in the restore process
    function nanogong_decode_content_links ($content, $restore) {
            
        global $CFG;
            
        $result = $content;
                
        //Link to the list of nanogongs
                
        $searchstring='/\$@(NANOGONGINDEX)\*([0-9]+)@\$/';
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
                $searchstring='/\$@(NANOGONGINDEX)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/nanogong/index.php?id='.$rec->new_id,$result);
                } else { 
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/nanogong/index.php?id='.$old_id,$result);
                }
            }
        }

        //Link to nanogong view by moduleid

        $searchstring='/\$@(NANOGONGVIEWBYID)\*([0-9]+)@\$/';
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
                $searchstring='/\$@(NANOGONGVIEWBYID)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/nanogong/view.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/nanogong/view.php?id='.$old_id,$result);
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
    function nanogong_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

        if ($nanogongs = get_records_sql ("SELECT n.id, n.name
                                   FROM {$CFG->prefix}nanogong n
                                   WHERE n.course = $restore->course_id")) {
            //Iterate over each nanogong->description
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($nanogongs as $nanogong) {
                //Increment counter
                $i++;
                $content = $nanogong->name;
                $result = restore_decode_content_links_worker($content,$restore);
                if ($result != $content) {
                    //Update record
                    $nanogong->name = addslashes($result);
                    $status = update_record("nanogong",$nanogong);
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

?>

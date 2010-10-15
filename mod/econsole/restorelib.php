<?php //$Id: restorelib.php,v 1.22.6.2 2008/03/17 21:33:59 skodak Exp $
    //This php script contains all the stuff to backup/restore
    //econsole mods

    //This is the "graphical" structure of the econsole mod:
    //
    //                    econsole
    //                    (CL,pk->id)             
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
    function econsole_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object   
            $info = $data->info;

            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug
            // if necessary, write to restorelog and adjust date/time fields
            if ($restore->course_startdateoffset) {
                restore_log_date_changes('E-Console', $restore, $info['MOD']['#'], array('CONSOLETRERSTIME'));
            }
            //Now, build the CONSOLETRERS record structure
            $econsole->course = $restore->course_id;
            $econsole->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $econsole->content = backup_todb($info['MOD']['#']['CONTENT']['0']['#']);
            $econsole->unitstring = backup_todb($info['MOD']['#']['UNITSTRING']['0']['#']);			
            $econsole->showunit = backup_todb($info['MOD']['#']['SHOWUNIT']['0']['#']);			
            $econsole->lessonstring = backup_todb($info['MOD']['#']['LESSONSTRING']['0']['#']);			
            $econsole->showlesson = backup_todb($info['MOD']['#']['SHOWLESSON']['0']['#']);	
            $econsole->url1name = backup_todb($info['MOD']['#']['URL1NAME']['0']['#']);			
            $econsole->url1 = backup_todb($info['MOD']['#']['URL1']['0']['#']);		
            $econsole->url2name = backup_todb($info['MOD']['#']['URL2NAME']['0']['#']);			
            $econsole->url2 = backup_todb($info['MOD']['#']['URL2']['0']['#']);	
            $econsole->url3name = backup_todb($info['MOD']['#']['URL3NAME']['0']['#']);			
            $econsole->url3 = backup_todb($info['MOD']['#']['URL3']['0']['#']);	
            $econsole->url4name = backup_todb($info['MOD']['#']['URL4NAME']['0']['#']);			
            $econsole->url4 = backup_todb($info['MOD']['#']['URL4']['0']['#']);	
            $econsole->url5name = backup_todb($info['MOD']['#']['URL5NAME']['0']['#']);			
            $econsole->url5 = backup_todb($info['MOD']['#']['URL5']['0']['#']);		
            $econsole->url6name = backup_todb($info['MOD']['#']['URL6NAME']['0']['#']);			
            $econsole->url6 = backup_todb($info['MOD']['#']['URL6']['0']['#']);								
            $econsole->glossary = backup_todb($info['MOD']['#']['GLOSSARY']['0']['#']);			
            $econsole->journal = backup_todb($info['MOD']['#']['JOURNAL']['0']['#']);				
            $econsole->forum = backup_todb($info['MOD']['#']['FORUM']['0']['#']);			
            $econsole->chat = backup_todb($info['MOD']['#']['CHAT']['0']['#']);	
            $econsole->choice = backup_todb($info['MOD']['#']['CHOICE']['0']['#']);			
            $econsole->quiz = backup_todb($info['MOD']['#']['QUIZ']['0']['#']);				
            $econsole->assignment = backup_todb($info['MOD']['#']['ASSIGNMENT']['0']['#']);			
            $econsole->wiki = backup_todb($info['MOD']['#']['WIKI']['0']['#']);				
            $econsole->theme = backup_todb($info['MOD']['#']['THEME']['0']['#']);
            $econsole->imagebartop = backup_todb($info['MOD']['#']['IMAGEBARTOP']['0']['#']);																			
            $econsole->imagebarbottom = backup_todb($info['MOD']['#']['IMAGEBARBOTTOM']['0']['#']);																						
            $econsole->timecreated = backup_todb($info['MOD']['#']['TIMECREATED']['0']['#']);
            $econsole->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //The structure is equal to the db, so insert the econsole
            $newid = insert_record ("econsole",$econsole);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","econsole")." \"".format_string(stripslashes($econsole->name),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
                //Now check if want to restore user data and do it.
/*                if (restore_userdata_selected($restore,'econsole',$mod->id)) {
                    //Restore econsole_messages
                    $status = econsole_messages_restore_mods ($mod->id, $newid,$info,$restore);
                }
*/            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

    //This function restores the econsole_messages
/*    function econsole_messages_restore_mods($old_econsole_id, $new_econsole_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the messages array 
        $messages = $info['MOD']['#']['MESSAGES']['0']['#']['MESSAGE'];

        //Iterate over messages
        for($i = 0; $i < sizeof($messages); $i++) {
            $mes_info = $messages[$i];
            //traverse_xmlize($mes_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($mes_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($mes_info['#']['USERID']['0']['#']);

            //Now, build the CONSOLETRERS_MESSAGES record structure
            $message = new object();
            $message->econsoleid = $new_econsole_id;
            $message->userid = backup_todb($mes_info['#']['USERID']['0']['#']);
            $message->groupid = backup_todb($mes_info['#']['GROUPID']['0']['#']);
            $message->system = backup_todb($mes_info['#']['SYSTEM']['0']['#']);
            $message->message = backup_todb($mes_info['#']['MESSAGE_TEXT']['0']['#']);
            $message->timestamp = backup_todb($mes_info['#']['TIMESTAMP']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$message->userid);
            if ($user) {
                $message->userid = $user->new_id;
            }

            //We have to recode the groupid field
            $group = restore_group_getid($restore, $message->groupid);
            if ($group) {
                $message->groupid = $group->new_id;
            }

            //The structure is equal to the db, so insert the econsole_message
            $newid = insert_record ("econsole_messages",$message);

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
        }
        return $status;
    }
*/
    //Return a content decoded to support interactivities linking. Every module
    //should have its own. They are called automatically from
    //econsole_decode_content_links_caller() function in each module
    //in the restore process
    function econsole_decode_content_links ($content,$restore) {
            
        global $CFG;
            
        $result = $content;
                
        //Link to the list of econsoles
                
        $searchstring='/\$@(CONSOLETRERSINDEX)\*([0-9]+)@\$/';
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
                $searchstring='/\$@(CONSOLETRERSINDEX)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/econsole/index.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/econsole/index.php?id='.$old_id,$result);
                }
            }
        }

        //Link to econsole view by moduleid

        $searchstring='/\$@(CONSOLETRERSVIEWBYID)\*([0-9]+)@\$/';
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
                $searchstring='/\$@(CONSOLETRERSVIEWBYID)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/econsole/view.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/econsole/view.php?id='.$old_id,$result);
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
    function econsole_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;
        
        if ($econsoles = get_records_sql ("SELECT e.id, e.content, e.url1, e.url2, e.url3, e.url4, e.url5, e.url6 
                                   FROM {$CFG->prefix}econsole e
                                   WHERE e.course = $restore->course_id")) {
                                               //Iterate over each econsole->intro
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($econsoles as $econsole) {
                //Increment counter
                $i++;
                $content1 = $econsole->content;
                $content2 = $econsole->url1;
                $content3 = $econsole->url2;
                $content4 = $econsole->url3;
                $content5 = $econsole->url4;
                $content6 = $econsole->url5;
                $content7 = $econsole->url6;														
                $result1 = restore_decode_content_links_worker($content1,$restore);
                $result2 = restore_decode_content_links_worker($content2,$restore);
                $result3 = restore_decode_content_links_worker($content3,$restore);
                $result4 = restore_decode_content_links_worker($content4,$restore);
                $result5 = restore_decode_content_links_worker($content5,$restore);
                $result6 = restore_decode_content_links_worker($content6,$restore);
                $result7 = restore_decode_content_links_worker($content7,$restore);																								
                if ($result1 != $content1 || $result2 != $content2 ||  $result3 != $content3 || $result4 != $content4 || $result5 != $content5 ||  $result6 != $content6 || $result7 != $content7) {
                    //Update record
                    $econsole->content = addslashes($result1);
                    $econsole->url1 = addslashes($result2);					
                    $econsole->url2 = addslashes($result3);					
                    $econsole->url3 = addslashes($result4);					
                    $econsole->url4 = addslashes($result5);
					$econsole->url5 = addslashes($result6);																				
                    $econsole->url6 = addslashes($result7);					
                    $status = update_record("econsole",$econsole);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content1).'<br />changed to<br />'.s($result1).'<hr /><br />';
                            echo '<br /><hr />'.s($content2).'<br />changed to<br />'.s($result2).'<hr /><br />';
                            echo '<br /><hr />'.s($content3).'<br />changed to<br />'.s($result3).'<hr /><br />';
                            echo '<br /><hr />'.s($content4).'<br />changed to<br />'.s($result4).'<hr /><br />';
                            echo '<br /><hr />'.s($content5).'<br />changed to<br />'.s($result5).'<hr /><br />';
                            echo '<br /><hr />'.s($content6).'<br />changed to<br />'.s($result6).'<hr /><br />';
                            echo '<br /><hr />'.s($content7).'<br />changed to<br />'.s($result7).'<hr /><br />';														
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

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function econsole_restore_logs($restore,$log) {

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
        case "talk":
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
        case "report":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "report.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
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

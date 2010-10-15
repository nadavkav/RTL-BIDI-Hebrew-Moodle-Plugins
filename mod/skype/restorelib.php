<?php 

    //This function executes all the restore procedure about this mod
    function skype_restore_mods($mod,$restore) {

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
          
            //Now, build the skype record structure
            $skype->course = $restore->course_id;
            $skype->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $skype->participants = backup_todb($info['MOD']['#']['PARTICIPANTS']['0']['#']);
            $skype->timemodified = $info['MOD']['#']['TIMEMODIFIED']['0']['#'];
 
            //The structure is equal to the db, so insert the skype
            $newid = insert_record ("skype",$skype);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","skype")." \"".format_string(stripslashes($skype->name),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
   
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

    function skype_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

        if ($skypes = get_records_sql ("SELECT l.id, l.participants
                                   FROM {$CFG->prefix}skype l
                                   WHERE l.course = $restore->course_id")) {
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($skypes as $skype) {
                //Increment counter
                $i++;
                $participants = $skype->participants;
                $result = restore_decode_content_links_worker($participants,$restore);

                if ($result != $participants) {
                    //Update record
                    $skype->participants = addslashes($result);
                    $status = update_record("skype", $skype);
                    if ($CFG->debug>7) {
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

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function skype_restore_logs($restore,$log) {
                    
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

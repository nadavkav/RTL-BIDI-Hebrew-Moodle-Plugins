<?php //$Id: restorelib.php,v 1.1 2008/01/21 11:01:50 jamiesensei Exp $
    //This php script contains all the stuff to restore
    //qcreate mods

    //This is the "graphical" structure of the qcreate mod:
    //
    //                             qcreate
    //                            (CL, pk->id)             
    //                                 |
    //                                 |
    //         ---------------------------------------------------        
    //         |                                                 |
    //    qcreate_grades                                 qcreate_required
    //(UL, pk->id, fk->qcreateid)                      (CL, pk->id, fk->qcreateid)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          CL->course level info
    //          UL->user level info
    //
    //-----------------------------------------------------------

    //This function executes all the restore procedure about this mod
    function qcreate_restore_mods($mod, $restore) {

        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code, $mod->modtype, $mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //if necessary, write to restorelog and adjust date/time fields
            if ($restore->course_startdateoffset) {
                restore_log_date_changes('Qcreate', $restore, $info['MOD']['#'], array('TIMEOPEN', 'TIMECLOSE'));
            }

            //Now, build the QCREATE record structure
            $qcreate = new object();
            $qcreate->course = $restore->course_id;
            $qcreate->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $qcreate->grade = backup_todb($info['MOD']['#']['GRADE']['0']['#']);
            $qcreate->graderatio = backup_todb($info['MOD']['#']['GRADERATIO']['0']['#']);
            $qcreate->intro = backup_todb($info['MOD']['#']['INTRO']['0']['#']);
            $qcreate->introformat = backup_todb($info['MOD']['#']['INTROFORMAT']['0']['#']);
            $qcreate->allowed = backup_todb($info['MOD']['#']['ALLOWED']['0']['#']);
            $qcreate->totalrequired = backup_todb($info['MOD']['#']['TOTALREQUIRED']['0']['#']);
            $qcreate->studentqaccess = backup_todb($info['MOD']['#']['STUDENTQACCESS']['0']['#']);
            $qcreate->timesync = 0;
            $qcreate->timeopen = backup_todb($info['MOD']['#']['TIMEOPEN']['0']['#']);
            $qcreate->timeclose = backup_todb($info['MOD']['#']['TIMECLOSE']['0']['#']);
            $qcreate->timecreated = backup_todb($info['MOD']['#']['TIMECREATED']['0']['#']);
            $qcreate->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the grade field if it is <0 (scale)
            if ($qcreate->grade < 0) {
                $scale = backup_getid($restore->backup_unique_code, "scale", abs($qcreate->grade));        
                if ($scale) {
                    $qcreate->grade = -($scale->new_id);       
                }
            }


            //The structure is equal to the db, so insert the qcreate
            $newid = insert_record("qcreate", $qcreate);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename", "qcreate")." \"".format_string(stripslashes($qcreate->name), true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, $mod->modtype,
                             $mod->id, $newid);
                $status = qcreate_requireds_restore_mods($mod->id, $newid, $info, $restore) && $status;
                //Now check if want to restore user data and do it.
                if (restore_userdata_selected($restore, 'qcreate', $mod->id)) { 
                    //Restore qcreate_grades
                    $status = qcreate_grades_restore_mods($mod->id, $newid, $info, $restore) && $status;
                }
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

    //This function restores the qcreate_grades
    function qcreate_grades_restore_mods($old_qcreate_id, $new_qcreate_id, $info, $restore) {

        global $CFG;

        $status = true;

        //Get the submissions array - it might not be present
        if (isset($info['MOD']['#']['GRADES']['0']['#']['GRADE'])) {
            $grades = $info['MOD']['#']['GRADES']['0']['#']['GRADE'];
        } else {
            $grades = array();
        }

        //Iterate over grades
        for($i = 0; $i < sizeof($grades); $i++) {
            $sub_info = $grades[$i];
            //traverse_xmlize($sub_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($sub_info['#']['ID']['0']['#']);

            //Now, build the QCREATE_SUBMISSIONS record structure
            $grade = new object();
            $grade->qcreateid = $new_qcreate_id;
            $grade->questionid = backup_todb($sub_info['#']['QUESTIONID']['0']['#']);
            $grade->grade = backup_todb($sub_info['#']['GRADE']['0']['#']);
            $grade->gradecomment = backup_todb($sub_info['#']['GRADECOMMENT']['0']['#']);
            $grade->teacher = backup_todb($sub_info['#']['TEACHER']['0']['#']);
            $grade->timemarked = backup_todb($sub_info['#']['TIMEMARKED']['0']['#']);
            
            //We have to recode the questionid field
            $question = backup_getid($restore->backup_unique_code, "question", $grade->questionid);
            if ($question) {
                $grade->questionid = $question->new_id;
            } else {
                continue; // skip this grade, the question associated has not been restored
            }

            //We have to recode the teacher field
            $user = backup_getid($restore->backup_unique_code, "user", $grade->teacher);
            if ($user) {
                $grade->teacher = $user->new_id;
            } 

            //The structure is equal to the db, so insert the qcreate_submission
            $newid = insert_record("qcreate_grades", $grade);

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
                backup_putid($restore->backup_unique_code, "qcreate_grades", $oldid,
                             $newid);


            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function restores the qcreate_requireds
    function qcreate_requireds_restore_mods($old_qcreate_id, $new_qcreate_id, $info, $restore) {

        global $CFG;

        $status = true;

        //Get the requireds array - it might not be present
        if (isset($info['MOD']['#']['REQUIREDS']['0']['#']['REQUIRED'])) {
            $requireds = $info['MOD']['#']['REQUIREDS']['0']['#']['REQUIRED'];
        } else {
            $requireds = array();
        }

        //Iterate over requireds
        for($i = 0; $i < sizeof($requireds); $i++) {
            $sub_info = $requireds[$i];
            //traverse_xmlize($sub_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($sub_info['#']['ID']['0']['#']);

            //Now, build the QCREATE_REQUIRED record structure
            $required = new object();
            $required->qcreateid = $new_qcreate_id;
            $required->qtype = backup_todb($sub_info['#']['QTYPE']['0']['#']);
            $required->no = backup_todb($sub_info['#']['NO']['0']['#']);
            

            //The structure is equal to the db, so insert the qcreate_submission
            $newid = insert_record("qcreate_required", $required);

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
                backup_putid($restore->backup_unique_code, "qcreate_required", $oldid,
                             $newid);


            } else {
                $status = false;
            }
        }

        return $status;
    }



    //Return a content decoded to support interactivities linking. Every module
    //should have its own. They are called automatically from
    //qcreate_decode_content_links_caller() function in each module
    //in the restore process
    function qcreate_decode_content_links($content, $restore) {
            
        global $CFG;
            
        $result = $content;
                
        //Link to the list of qcreates
                
        $searchstring='/\$@(QCREATEINDEX)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring, $content, $foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course id)
                $rec = backup_getid($restore->backup_unique_code, "course", $old_id);
                //Personalize the searchstring
                $searchstring='/\$@(QCREATEINDEX)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring, $CFG->wwwroot.'/mod/qcreate/index.php?id='.$rec->new_id, $result);
                } else { 
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring, $restore->original_wwwroot.'/mod/qcreate/index.php?id='.$old_id, $result);
                }
            }
        }

        //Link to qcreate view by moduleid

        $searchstring='/\$@(QCREATEVIEWBYID)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring, $result, $foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course_modules id)
                $rec = backup_getid($restore->backup_unique_code, "course_modules", $old_id);
                //Personalize the searchstring
                $searchstring='/\$@(QCREATEVIEWBYID)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring, $CFG->wwwroot.'/mod/qcreate/view.php?id='.$rec->new_id, $result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring, $restore->original_wwwroot.'/mod/qcreate/view.php?id='.$old_id, $result);
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
    function qcreate_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

        if ($qcreates = get_records_sql("SELECT a.id, a.intro
                                   FROM {$CFG->prefix}qcreate a
                                   WHERE a.course = $restore->course_id")) {
            //Iterate over each qcreate->description
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($qcreates as $qcreate) {
                //Increment counter
                $i++;
                $content = $qcreate->intro;
                $result = restore_decode_content_links_worker($content, $restore);
                if ($result != $content) {
                    //Update record
                    $qcreate->intro = addslashes($result);
                    $status = update_record("qcreate", $qcreate);
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



    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function qcreate_restore_logs($restore, $log) {
                    
        $status = false;
                    
        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code, $log->module, $log->info);
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
                $mod = backup_getid($restore->backup_unique_code, $log->module, $log->info);
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
                $mod = backup_getid($restore->backup_unique_code, $log->module, $log->info);
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

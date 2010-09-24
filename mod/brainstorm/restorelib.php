<?php //$Id: restorelib.php,v 1.2 2006/02/02 00:35:46 gustav_delius Exp $
    //This php script contains all the stuff to backup/restore
    //brainstorm mods

    //This is the "graphical" structure of the brainstorm mod:
    //
    //                     brainstorm                                      
    //                    (CL, pk->id)
    //                        |
    //                        +----------------------------------------+
    //                        |                                        |
    //                   brainstorm_operator                   brainstorm_responses
    //               (IL, pk->id, fk->brainstormid)          (IL, pk->id, fk->userid, fk->groupid,
    //                                                             fk->brainstormid)
    //                                                                 |
    //         +--------------------------------+----------------------+---------------------+
    //         |                                |                      |                     |
    // brainstorm_userdata             brainstorm_operatordata         |        brainstorm_categories
    // (IL, pk->id,fk->brainstormid  (IL, pk->id, fk->brainstormid,    |    (IL, pk->id, fk->brainstormid,
    // fk->userid)                       fk->userid, fk->groupid,      |        pk->userid, pk->groupid)
    //                                         fk->operatorid)         |         
    //                                                                 |
    //                                                           brainstorm_grades
    //                                                       (IL, pk->id, fk->brainstormid,
    //                                                                fk->userid)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          IL->instance level info
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files
    //
    //-----------------------------------------------------------

    //This function executes all the restore procedure about this mod
    function brainstorm_restore_mods($mod,$restore) {
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

            //Now, build the SCHEDULER record structure
            $brainstorm->course = $restore->course_id;
            $brainstorm->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $brainstorm->description = backup_todb($info['MOD']['#']['DESCRIPTION']['0']['#']);
            $brainstorm->flowmode = backup_todb($info['MOD']['#']['FLOWMODE']['0']['#']);
            $brainstorm->seqaccesscollect = backup_todb($info['MOD']['#']['SEQACCESSCOLLECT']['0']['#']); 
            $brainstorm->seqaccessprepare = backup_todb($info['MOD']['#']['SEQACCESSPREPARE']['0']['#']);
            $brainstorm->seqaccessorganize = backup_todb($info['MOD']['#']['SEQACCESSORGANIZE']['0']['#']);
            $brainstorm->seqaccessdisplay = backup_todb($info['MOD']['#']['SEQACCESSDISPLAY']['0']['#']);
            $brainstorm->seqaccessfeedback = backup_todb($info['MOD']['#']['SEQACCESSFEEDBACK']['0']['#']);
            $brainstorm->phase = backup_todb($info['MOD']['#']['PHASE']['0']['#']);
            $brainstorm->privacy = backup_todb($info['MOD']['#']['PRIVACY']['0']['#']);
            $brainstorm->numresponses = backup_todb($info['MOD']['#']['NUMRESPONSES']['0']['#']);
            $brainstorm->numresponsesinform = backup_todb($info['MOD']['#']['NUMRESPONSESINFORM']['0']['#']); 
            $brainstorm->numcolumns = backup_todb($info['MOD']['#']['NUMCOLUMNS']['0']['#']);
            $brainstorm->oprequirementtype = backup_todb($info['MOD']['#']['OPREQUIREMENTTYPE']['0']['#']);
            $brainstorm->scale = backup_todb($info['MOD']['#']['SCALE']['0']['#']);
            $brainstorm->singlegrade = backup_todb($info['MOD']['#']['SINGLEGRADE']['0']['#']);
            $brainstorm->participationweight = backup_todb($info['MOD']['#']['PARTICIPATIONWEIGHT']['0']['#']);
            $brainstorm->preparingweight = backup_todb($info['MOD']['#']['PREPARINGWEIGHT']['0']['#']);
            $brainstorm->organizeweight = backup_todb($info['MOD']['#']['ORGANIZEWEIGHT']['0']['#']);
            $brainstorm->feedbackweight = backup_todb($info['MOD']['#']['FEEDBACKWEIGHT']['0']['#']);
            $brainstorm->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the scale field (foreing key on existing scale)
            // should be verified
            $scale = backup_getid($restore->backup_unique_code, 'scale', $brainstorm->scale);
            if ($scale) {
                $brainstorm->scale = $scale->new_id;
            }

            //The structure is equal to the db, so insert the brainstorm
            $newid = insert_record ('brainstorm', $brainstorm);

            //Do some output
            echo "<li>".get_string('modulename', 'brainstorm')." \"".format_string(stripslashes($brainstorm->name),true)."\"</li>";
            backup_flush(300);

            if ($newid) {
                // We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);

                // Now restore operators. All other data is user dependant.
                $status = brainstorm_operators_restore_mods ($mod->id, $newid, $info, $restore);

                // Now check if want to restore user data and do it.
                if ($restore->mods['brainstorm']->userinfo) {
                    $status = brainstorm_responses_restore_mods ($mod->id, $newid, $info, $restore);
                    $status = brainstorm_categories_restore_mods ($mod->id, $newid, $info, $restore);
                    $status = brainstorm_operatordata_restore_mods ($mod->id, $newid, $info, $restore);
                    $status = brainstorm_userdata_restore_mods ($mod->id, $newid, $info, $restore);
                    $status = brainstorm_grades_restore_mods ($mod->id, $newid, $info, $restore);
                }
            } 
            else {
                $status = false;
            }
        } 
        else {
            $status = false;
        }
        return $status;
    }


    //This function restores the brainstorm_operators
    function brainstorm_operators_restore_mods($old_brainstorm_id, $new_brainstorm_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the operators array
        $operators = $info['MOD']['#']['OPERATORS']['0']['#']['OPERATOR'];

        //Iterate over operators
        for($i = 0; $i < sizeof($operators); $i++) {
            $operator_info = $operators[$i];
            //traverse_xmlize($operator_info);                                                               //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            // We'll need this later!!
            $oldid = backup_todb($slot_info['#']['ID']['0']['#']);

            // Now, build the BRAINSTORM_OPERATOR record structure
            $operator->brainstormid = $new_brainstorm_id;
            $operator->operatorid = backup_todb($operator_info['#']['OPERATORID']['0']['#']);
            $operator->configdata = backup_todb($operator_info['#']['CONFIGDATA']['0']['#']);
            $operator->active = backup_todb($operator_info['#']['ACTIVE']['0']['#']);

            // We check if the code of the operator is available. we keep the record either but set it to unavailable
            if (!file_exists("{$CFG->dirroot}/mod/brainstorm/operators/{$operator->operatorid}/prepare.php")){
                $operator->active = 0;
            }
            
            // The structure is equal to the db, so insert the operator
            $newid = insert_record ('brainstorm_operators', $operator);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, 'brainstorm_operators', $oldid, $newid);
            } 
            else {
                $status = false;
            }
        }
        return $status;
    }

    //This function restores the brainstorm responses
    function brainstorm_responses_restore_mods($old_brainstorm_id, $new_brainstorm_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the responses array
        $responses = $info['MOD']['#']['RESPONSES']['0']['#']['RESPONSE'];

        //Iterate over responses
        for($i = 0; $i < sizeof($responses); $i++) {
            $response_info = $responses[$i];
            //traverse_xmlize($response_info);                                                         //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($response_info['#']['ID']['0']['#']);

            //Now, build the BRAINSTORM_RESPONSE record structure
            $response->brainstormid = $new_brainstorm_id;
            $response->userid = backup_todb($response_info['#']['USERID']['0']['#']);
            $response->groupid = backup_todb($response_info['#']['GROUPID']['0']['#']);
            $response->response = backup_todb($response_info['#']['RESPONSE']['0']['#']);
            $response->timemodified = backup_todb($response_info['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code, 'user', $response->userid);
            if ($user) {
                $response->userid = $user->new_id;
            }

            //We have to recode the groupid field
            $group = backup_getid($restore->backup_unique_code, 'groups', $response->groupid);
            if ($group) {
                $response->groupid = $group->new_id;
            }

            //The structure is equal to the db, so insert the brainstorm response
            $newid = insert_record ('brainstorm_response', $response);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, 'brainstorm_response', $oldid, $newid);
            } 
            else {
                $status = false;
            }
        }
        return $status;
    }

    //This function restores the brainstorm categories
    function brainstorm_categories_restore_mods($old_brainstorm_id, $new_brainstorm_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the categories array
        $categories = $info['MOD']['#']['CATEGORIES']['0']['#']['CATEGORY'];

        //Iterate over categories
        for($i = 0; $i < sizeof($categories); $i++) {
            $category_info = $categories[$i];
            //traverse_xmlize($category_info);                                                         //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($category_info['#']['ID']['0']['#']);

            //Now, build the BRAINSTORM_CATEGORY record structure
            $category->brainstormid = $new_brainstorm_id;
            $category->userid = backup_todb($category_info['#']['USERID']['0']['#']);
            $category->groupid = backup_todb($category_info['#']['GROUPID']['0']['#']);
            $category->title = backup_todb($category_info['#']['TITLE']['0']['#']);
            $category->timemodified = backup_todb($category_info['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code, 'user', $category->userid);
            if ($user) {
                $category->userid = $user->new_id;
            }

            //We have to recode the groupid field
            $group = backup_getid($restore->backup_unique_code, 'groups', $category->groupid);
            if ($group) {
                $category->groupid = $group->new_id;
            }

            //The structure is equal to the db, so insert the brainstorm category
            $newid = insert_record ('brainstorm_categories', $category);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, 'brainstorm_categories', $oldid, $newid);
            } 
            else {
                $status = false;
            }
        }
        return $status;
    }

    //This function restores the brainstorm operatordata
    function brainstorm_operatordata_restore_mods($old_brainstorm_id, $new_brainstorm_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the op data array
        $opdata = $info['MOD']['#']['CATEGORIES']['0']['#']['CATEGORY'];

        //Iterate over opdata
        for($i = 0; $i < sizeof($opdata); $i++) {
            $datum_info = $opdata[$i];
            //traverse_xmlize($datum_info);                                                         //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($datum_info['#']['ID']['0']['#']);

            //Now, build the BRAINSTORM_DATUM record structure
            $datum->brainstormid = $new_brainstorm_id;
            $datum->userid = backup_todb($datum_info['#']['USERID']['0']['#']);
            $datum->groupid = backup_todb($datum_info['#']['GROUPID']['0']['#']);
            $datum->operatorid = backup_todb($datum_info['#']['OPERATORID']['0']['#']);
            
            // We ignore if operator is not implemented here
            if (!file_exists("{$CFG->dirroot}/mod/brainstorm/operators/{$datum->operatorid}/prepare.php")){
                continue;
            }

            $datum->itemsource = backup_todb($datum_info['#']['ITEMSOURCE']['0']['#']);
            $datum->itemdest = backup_todb($datum_info['#']['ITEMDEST']['0']['#']);
            $datum->intvalue = backup_todb($datum_info['#']['INTVALUE']['0']['#']);
            $datum->floatvalue = backup_todb($datum_info['#']['FLOATVALUE']['0']['#']);
            $datum->blobvalue = backup_todb($datum_info['#']['BLOBVALUE']['0']['#']);
            $datum->timemodified = backup_todb($datum_info['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code, 'user', $datum->userid);
            if ($user) {
                $datum->userid = $user->new_id;
            }

            //We have to recode the groupid field
            $group = backup_getid($restore->backup_unique_code, 'groups', $datum->groupid);
            if ($group) {
                $datum->groupid = $group->new_id;
            }

            //We have to recode the itemsource field
            $response = backup_getid($restore->backup_unique_code, 'brainstorm_responses', $datum->itemsource);
            if ($response) {
                $datum->itemsource = $response->new_id;
            }

            //We have to recode the itemdest field
            $response = backup_getid($restore->backup_unique_code, 'brainstorm_responses', $datum->itemdest);
            if ($response) {
                $datum->itemdest = $response->new_id;
            }

            //The structure is equal to the db, so insert the brainstorm datum
            $newid = insert_record ('brainstorm_operatordata', $datum);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, 'brainstorm_operatordata', $oldid, $newid);
            } 
            else {
                $status = false;
            }
        }
        return $status;
    }

    //This function restores the brainstorm userdata
    function brainstorm_userdata_restore_mods($old_brainstorm_id, $new_brainstorm_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the userdata array
        $userdata = $info['MOD']['#']['USERDATA']['0']['#']['DATUM'];

        //Iterate over userdata records
        for($i = 0; $i < sizeof($userdata); $i++) {
            $userdata_info = $userdata[$i];
            //traverse_xmlize($userdata_info);                                                         //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($userdata_info['#']['ID']['0']['#']);

            //Now, build the BRAINSTORM_USERDATA record structure
            $datum->brainstormid = $new_brainstorm_id;
            $datum->userid = backup_todb($userdata_info['#']['USERID']['0']['#']);
            $datum->report = backup_todb($userdata_info['#']['REPORT']['0']['#']);
            $datum->reportformat = backup_todb($userdata_info['#']['REPORTFORMAT']['0']['#']);
            $datum->feedback = backup_todb($userdata_info['#']['FEEDBACK']['0']['#']);
            $datum->feedbackformat = backup_todb($userdata_info['#']['FEEDBACKFORMAT']['0']['#']);
            $datum->timeupdated = backup_todb($userdata_info['#']['TIMEUPDATED']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code, 'user', $datum->userid);
            if ($user) {
                $datum->userid = $user->new_id;
            }

            //The structure is equal to the db, so insert the brainstorm userdata
            $newid = insert_record ('brainstorm_userdata', $datum);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, 'brainstorm_userdata', $oldid, $newid);
            } 
            else {
                $status = false;
            }
        }
        return $status;
    }

    //This function restores the brainstorm grades
    function brainstorm_grades_restore_mods($old_brainstorm_id, $new_brainstorm_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the grades array
        $grades = $info['MOD']['#']['GRADES']['0']['#']['GRADE'];

        //Iterate over grades
        for($i = 0; $i < sizeof($grades); $i++) {
            $grades_info = $grades[$i];
            //traverse_xmlize($grades_info);                                                         //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($grades_info['#']['ID']['0']['#']);

            //Now, build the BRAINSTORM_GRADES record structure
            $grade->brainstormid = $new_brainstorm_id;
            $grade->userid = backup_todb($grades_info['#']['USERID']['0']['#']);
            $grade->grade = backup_todb($grades_info['#']['GRADE']['0']['#']);
            $grade->gradeitem = backup_todb($grades_info['#']['GRADEITEM']['0']['#']);
            $grade->timeupdated = backup_todb($grades_info['#']['TIMEUPDATED']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code, 'user', $grade->userid);
            if ($user) {
                $grade->userid = $user->new_id;
            }

            //The structure is equal to the db, so insert the brainstorm grade
            $newid = insert_record ('brainstorm_grades', $grade);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, 'brainstorm_grades', $oldid, $newid);
            } 
            else {
                $status = false;
            }
        }
        return $status;
    }

?>

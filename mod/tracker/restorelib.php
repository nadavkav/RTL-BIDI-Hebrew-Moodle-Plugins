<?php //$Id: restorelib.php,v 1.2.2.6 2010/02/13 16:35:17 diml Exp $

/**
* @package mod-tracker
* @category mod
* @author Valery Fremaux > 1.8
* @date 02/12/2007
*
* Restore library for module tracker
*/

    //This php script contains all the stuff to backup/restore
    //tracker mods

    //This is the "graphical" structure of the tracker mod:
    //
    //           +------------------------------------------------------------+
    //           |                                                            |
    //        tracker                                                  tracker_element
    //       (CL,pk->id)                                          (CL, pk->id, fk->course)
    //           |                                                            |
    //           |                                                   tracker_elementitem   
    //           |                                                 (pk->id, fk->elementid) 
    //           |
    //           +-------------------------------+------------------------------+--------------------------+                
    //           |                               |                              |                          |
    //      tracker_issue              tracker_elementused              tracker_dependancy         tracker_preferences
    //  (IL,pk->id, fk->trackerid)  (IL, pk->id, fk->trackerid)    (IL, pk->id, fk->parentid,   (UL, pk->id, fk->userid) 
    //           |                                                         fk->childid)
    //           |
    //           +-----------------------------+------------------------+----------------------+
    //           |                             |                        |                      |
    //      tracker_issueattribute        tracker_issuecc          tracker_issuecomment        |
    //   (UL, pk->id, fk->issueid,     (UL, pk->id, fk->issueid,  (UL, pk->id, fk->issueid,    |
    //       fk->elementid,                fk->userid)                   fk->userid)           |
    //      fk->elementitemid)                                                                 |
    //                                                                      +------------------+
    // Meaning: pk->primary key field of the table                          |
    //          fk->foreign key to link with parent                tracker_issueownership
    //          nt->nested field (recursive data)            (UL, pk->id, fk->issueid, fk->userid,
    //          IL->instance level info                                 fk->bywhomid)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files
    //
    //-----------------------------------------------------------

    //This function executes all the restore procedure about this mod
    function tracker_restore_mods($mod, $restore) {
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

            //Now, build the TRACKER record structure
            $tracker->course = $restore->course_id;
            $tracker->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $tracker->description = backup_todb($info['MOD']['#']['DESCRIPTION']['0']['#']);
            $tracker->format = backup_todb($info['MOD']['#']['FORMAT']['0']['#']);  
            $tracker->requirelogin = backup_todb($info['MOD']['#']['REQUIRELOGIN']['0']['#']);  
            $tracker->allownotifications = backup_todb($info['MOD']['#']['ALLOWNOTIFICATIONS']['0']['#']);  
            $tracker->enablecomments = backup_todb($info['MOD']['#']['ENABLECOMMENTS']['0']['#']);  
            $tracker->ticketprefix = backup_todb($info['MOD']['#']['TICKETPREFIX']['0']['#']); 
            $tracker->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);
            $tracker->parent = backup_todb($info['MOD']['#']['PARENT']['0']['#']);
            $tracker->supportmode = backup_todb($info['MOD']['#']['SUPPORTMODE']['0']['#']);

            //The structure is equal to the db, so insert the tracker
            $newid = insert_record ('tracker', $tracker);

            //Do some output
            echo "<li>".get_string('modulename', 'tracker')." \"".format_string(stripslashes($tracker->name),true)."\"</li>";
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);

                // We have to store the sitewide elements that are used
                $status = tracker_elements_restore_mods ($mod->id, $newid, $info, $restore, $restore->course_id);
                $status = tracker_elementitems_restore_mods ($mod->id, $newid, $info, $restore);
                
                // We have to restore the trackerwide elements 
                $status = tracker_elementuseds_restore_mods ($mod->id, $newid, $info, $restore);

                //Now check if want to restore user data and do it.
                if ($restore->mods['tracker']->userinfo) {
                    //Restore tracker_issue
                    $status = tracker_preferences_restore_mods ($mod->id, $newid, $info, $restore);
                    $status = tracker_issues_restore_mods ($mod->id, $newid, $info, $restore);
                    $status = tracker_issueattributes_restore_mods ($mod->id, $newid, $info, $restore);
                    $status = tracker_issueccs_restore_mods ($mod->id, $newid, $info, $restore);
                    $status = tracker_issuecomments_restore_mods ($mod->id, $newid, $info, $restore);
                    $status = tracker_issuedependancies_restore_mods ($mod->id, $newid, $info, $restore);
                    $status = tracker_issueownerships_restore_mods ($mod->id, $newid, $info, $restore);
                    $status = tracker_queries_restore_mods ($mod->id, $newid, $info, $restore);
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



    //This function restores the tracker elements
    function tracker_elements_restore_mods($old_tracker_id, $new_tracker_id, $info, $restore, $new_course_id) {
        global $CFG;

        $status = true;

        //Get the elements array
        $elements = @$info['MOD']['#']['ELEMENTS']['0']['#']['ELEMENT'];

        //Iterate over elements
        if (!empty($elements)){
            for($i = 0; $i < sizeof($elements); $i++) {
                $element_info = $elements[$i];
                //traverse_xmlize($element_info);                                                         //Debug
                //print_object ($GLOBALS['traverse_array']);                                                  //Debug
                //$GLOBALS['traverse_array']="";                                                              //Debug
    
                //We'll need this later!!
                $oldid = backup_todb($element_info['#']['ID']['0']['#']);
    
                //Now, build the TRACKER_ELEMENT record structure
                $element->course = $new_course_id;  
                $element->name = backup_todb($element_info['#']['NAME']['0']['#']);
                $element->description = backup_todb($element_info['#']['DESCRIPTION']['0']['#']);  
                $element->type = backup_todb($element_info['#']['TYPE']['0']['#']); 
                
                //The structure is equal to the db, so insert the tracker element
                $newid = insert_record ('tracker_element', $element);
    
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
                    backup_putid($restore->backup_unique_code, 'tracker_element', $oldid, $newid);
                } else {
                    $status = false;
                }
            }
        }
        return $status;
    }

    //This function restores the tracker element items
    function tracker_elementitems_restore_mods($old_tracker_id, $new_tracker_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the element items array
        $elementitems = @$info['MOD']['#']['ELEMENTITEMS']['0']['#']['ELEMENTITEM'];

        //Iterate over element items
        if (!empty($elementitems)){
            for($i = 0; $i < sizeof($elementitems); $i++) {
                $elementitem_info = $elementitems[$i];
                //traverse_xmlize($elementitem_info);                                                         //Debug
                //print_object ($GLOBALS['traverse_array']);                                                  //Debug
                //$GLOBALS['traverse_array']="";                                                              //Debug
    
                //We'll need this later!!
                $oldid = backup_todb($elementitem_info['#']['ID']['0']['#']);
    
                //Now, build the TRACKER_ELEMENTITEM record structure
                $elementitem->elementid = backup_todb($elementitem_info['#']['ELEMENTID']['0']['#']);  
                $elementitem->name = backup_todb($elementitem_info['#']['NAME']['0']['#']);  
                $elementitem->description = backup_todb($elementitem_info['#']['DESCRIPTION']['0']['#']);  
                $elementitem->sortorder = backup_todb($elementitem_info['#']['SORTORDER']['0']['#']);  
                $elementitem->active = backup_todb($elementitem_info['#']['ACTIVE']['0']['#']);  
    
                //We have to recode the elementid field
                $element = backup_getid($restore->backup_unique_code, 'tracker_element', $elementitem->elementid);
                if ($element) {
                    $elementitem->elementid = $element->new_id;
                }
                
                //The structure is equal to the db, so insert the tracker elementitem
                $newid = insert_record ('tracker_elementitem', $elementitem);
    
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
                    backup_putid($restore->backup_unique_code, 'tracker_elementitem', $oldid, $newid);
                } else {
                    $status = false;
                }
            }
        }
        return $status;
    }

    //This function restores the tracker element used in a tracker instance
    function tracker_elementuseds_restore_mods($old_tracker_id, $new_tracker_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the used elements array
        $elementuseds = @$info['MOD']['#']['ELEMENTUSEDS']['0']['#']['ELEMENTUSED'];

        //Iterate over used elements
        if (!empty($elementsused)){
            for($i = 0; $i < sizeof($elementuseds); $i++) {
                $elementused_info = $elementuseds[$i];
                //traverse_xmlize($elementused_info);                                                         //Debug
                //print_object ($GLOBALS['traverse_array']);                                                  //Debug
                //$GLOBALS['traverse_array']="";                                                              //Debug
    
                //We'll need this later!!
                $oldid = backup_todb($elementused_info['#']['ID']['0']['#']);
    
                //Now, build the TRACKER_ELEMENTUSED record structure
                $elementused->trackerid = $new_tracker_id;
                $elementused->elementid = backup_todb($elementused_info['#']['ELEMENTID']['0']['#']);
                $elementused->sortorder = backup_todb($elementused_info['#']['SORTORDER']['0']['#']);  
                $elementused->canbemodifiedby = backup_todb($elementused_info['#']['CANBEMODIFIEDBY']['0']['#']);  
                $elementused->active = backup_todb($elementused_info['#']['ACTIVE']['0']['#']); 
    
                //We have to recode the elementid field
                $element = backup_getid($restore->backup_unique_code, 'tracker_element', $elementused->elementid);
                if ($element) {
                    $elementused->elementid = $element->new_id;
                }
                
                //The structure is equal to the db, so insert the used item
                $newid = insert_record ('tracker_elementused', $elementused);
    
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
                    backup_putid($restore->backup_unique_code, 'tracker_elementused', $oldid, $newid);
                } else {
                    $status = false;
                }
            }
        }
        return $status;
    }

    //This function restores the tracker preferences for known users
    function tracker_preferences_restore_mods($old_tracker_id, $new_tracker_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the issues array
        $preferences = @$info['MOD']['#']['PREFERENCES']['0']['#']['PREFERENCE'];

        //Iterate over preferences
        if (!empty($preferences)){
            for($i = 0; $i < sizeof($preferences); $i++) {
                $preference_info = $preferences[$i];
                //traverse_xmlize($preference_info);                                                               //Debug
                //print_object ($GLOBALS['traverse_array']);                                                  //Debug
                //$GLOBALS['traverse_array']="";                                                              //Debug
    
                //We'll need this later!!
                $oldid = backup_todb($issue_info['#']['ID']['0']['#']);
    
                //Now, build the TRACKER_PREFERENCE record structure
                $preference->trackerid = $new_tracker_id;
                $preference->userid = backup_todb($preference_info['#']['USERID']['0']['#']);  
                $preference->name = backup_todb($preference_info['#']['NAME']['0']['#']);  
                $preference->value = backup_todb($preference_info['#']['VALUE']['0']['#']);  
    
                //We have to recode the "user related" fields
                $user = backup_getid($restore->backup_unique_code, "user", $preference->userid);
                if ($user) {
                    $preference->userid = $user->new_id;
                }
    
                //The structure is equal to the db, so insert the preference
                $newid = insert_record ('tracker_preferences', $preference);
    
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
                    backup_putid($restore->backup_unique_code, 'tracker_preferences', $oldid, $newid);
                } else {
                    $status = false;
                }
            }
        }
        return $status;
    }

    //This function restores the tracker queries for known users
    function tracker_queries_restore_mods($old_tracker_id, $new_tracker_id, $info) {
        global $CFG;

        $status = true;

        //Get the issues array
        $queries = @$info['MOD']['#']['QUERIES']['0']['#']['QUERY'];

        //Iterate over preferences
        if (!empty($queries)){
            for($i = 0; $i < sizeof($queries); $i++) {
                $query_info = $queries[$i];
                //traverse_xmlize($query_info);                    //Debug
                //print_object ($GLOBALS['traverse_array']);       //Debug
                //$GLOBALS['traverse_array']="";                   //Debug
    
                //We'll need this later!!
                $oldid = backup_todb($query_info['#']['ID']['0']['#']);
    
                //Now, build the TRACKER_QUERY record structure
                $query->trackerid = $new_tracker_id;
                $query->userid = backup_todb($query_info['#']['USERID']['0']['#']);  
                $query->name = backup_todb($query_info['#']['NAME']['0']['#']);  
                $query->description = backup_todb($query_info['#']['DESCRIPTION']['0']['#']);  
                $query->published = backup_todb($query_info['#']['PUBLISHED']['0']['#']);  
                $query->fieldnames = backup_todb($query_info['#']['FIELDNAMES']['0']['#']);  
                $query->fieldvalues = backup_todb($query_info['#']['FIELDVALUE']['0']['#']);  
    
                //We have to recode the "user related" fields
                $user = backup_getid($restore->backup_unique_code, "user", $query->userid);
                if ($user) {
                    $query->userid = $user->new_id;
                }
    
                //The structure is equal to the db, so insert the preference
                $newid = insert_record ('tracker_query', $query);
    
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
                    backup_putid($restore->backup_unique_code, 'tracker_query', $oldid, $newid);
                } else {
                    $status = false;
                }
            }
        }
        return $status;
    }

    //This function restores the tracker issues
    function tracker_issues_restore_mods($old_tracker_id, $new_tracker_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the issues array
        $issues = @$info['MOD']['#']['ISSUES']['0']['#']['ISSUE'];

        //Iterate over issues
        if (!empty($issues)){
            for($i = 0; $i < sizeof($issues); $i++) {
                $issue_info = $issues[$i];
                //traverse_xmlize($issue_info);                                                               //Debug
                //print_object ($GLOBALS['traverse_array']);                                                  //Debug
                //$GLOBALS['traverse_array']="";                                                              //Debug
    
                //We'll need this later!!
                $oldid = backup_todb($issue_info['#']['ID']['0']['#']);
    
                //Now, build the TRACKER_ISSUE record structure
                $issue->trackerid = $new_tracker_id;
                $issue->summary = backup_todb($issue_info['#']['SUMMARY']['0']['#']);  
                $issue->description = backup_todb($issue_info['#']['DESCRIPTION']['0']['#']);  
                $issue->format = backup_todb($issue_info['#']['FORMAT']['0']['#']);  
                $issue->datereported = backup_todb($issue_info['#']['DATEREPORTED']['0']['#']);  
                $issue->reportedby = backup_todb($issue_info['#']['REPORTEDBY']['0']['#']);  
                $issue->status = backup_todb($issue_info['#']['STATUS']['0']['#']);  
                $issue->assignedto = backup_todb($issue_info['#']['ASSIGNEDTO']['0']['#']);  
                $issue->bywhomid = backup_todb($issue_info['#']['BYWHOMID']['0']['#']);  
                $issue->timeassigned = backup_todb($issue_info['#']['TIMEASSIGNED']['0']['#']);  
                $issue->resolution = backup_todb($issue_info['#']['RESOLUTION']['0']['#']);  
                $issue->resolutionformat = backup_todb($issue_info['#']['RESOLUTIONFORMAT']['0']['#']); 
                $issue->resolutionpriority = backup_todb($issue_info['#']['RESOLUTIONPRIORITY']['0']['#']); 
    
                //We have to recode the "user related" fields
                $user = backup_getid($restore->backup_unique_code, "user", $issue->reportedby);
                if ($user) {
                    $issue->reportedby = $user->new_id;
                }
    
                $user = backup_getid($restore->backup_unique_code, "user", $issue->assignedto);
                if ($user) {
                    $issue->assignedto = $user->new_id;
                }
    
                $user = backup_getid($restore->backup_unique_code, "user", $issue->bywhomid);
                if ($user) {
                    $issue->bywhomid = $user->new_id;
                }
    
                //The structure is equal to the db, so insert the issue
                $newid = insert_record ('tracker_issue', $issue);
    
                // FIXME: Shouldn't we check for conflicts?
                // We have to add the events to the calendar as well
    
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
                    backup_putid($restore->backup_unique_code, 'tracker_issue', $oldid, $newid);
                } else {
                    $status = false;
                }
            }
        }
        return $status;
    }

    //This function restores the tracker issue attributes
    function tracker_issueattributes_restore_mods($old_tracker_id, $new_tracker_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the slots array
        $attributes = @$info['MOD']['#']['ATTRIBUTES']['0']['#']['ATTRIBUTE'];

        //Iterate over slots
        if (!empty($attributes)){
            for($i = 0; $i < sizeof($attributes); $i++) {
                $attribute_info = $attributes[$i];
                //traverse_xmlize($attribute_info);                                                         //Debug
                //print_object ($GLOBALS['traverse_array']);                                                  //Debug
                //$GLOBALS['traverse_array']="";                                                              //Debug
    
                //We'll need this later!!
                $oldid = backup_todb($attribute_info['#']['ID']['0']['#']);
    
                //Now, build the TRACKER_ISSUEATTRIBUTE record structure
                $attribute->trackerid = $new_tracker_id;  
                $attribute->issueid = backup_todb($attribute_info['#']['ISSUEID']['0']['#']);  
                $attribute->elementid = backup_todb($attribute_info['#']['ELEMENTID']['0']['#']);  
                $attribute->elementitemid = backup_todb($attribute_info['#']['ELEMENTITEMID']['0']['#']);  
                $attribute->timemodified = backup_todb($attribute_info['#']['TIMEMODIFIED']['0']['#']);
    
                //We have to recode the issueid field
                $issue = backup_getid($restore->backup_unique_code, 'tracker_issue', $attribute->issueid);
                if ($issue) {
                    $attribute->issueid = $issue->new_id;
                }
    
                //We have to recode the elementid field
                $element = backup_getid($restore->backup_unique_code, 'tracker_element', $attribute->elementid);
                if ($element) {
                    $attribute->elementid = $element->new_id;
                }
    
                //We have to recode the elementitemid field
                $elementitem = backup_getid($restore->backup_unique_code, 'tracker_elementitem', $attribute->elementitemid);
                if ($elementitem) {
                    $attribute->elementitemid = $elementitem->new_id;
                }
    
                //The structure is equal to the db, so insert the attribute
                $newid = insert_record ('tracker_issueattribute', $attribute);
    
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
                    backup_putid($restore->backup_unique_code, 'tracker_issueattribute', $oldid, $newid);
                } else {
                    $status = false;
                }
            }
        }
        return $status;
    }

    //This function restores the tracker issue carbon copies
    function tracker_issueccs_restore_mods($old_tracker_id, $new_tracker_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the carbon copy array
        $ccs = @$info['MOD']['#']['CCS']['0']['#']['CC'];

        //Iterate over carbon copies
        if (!empty($ccs)){
            for($i = 0; $i < sizeof($ccs); $i++) {
                $cc_info = $ccs[$i];
                //traverse_xmlize($cc_info);                                                         //Debug
                //print_object ($GLOBALS['traverse_array']);                                                  //Debug
                //$GLOBALS['traverse_array']="";                                                              //Debug
    
                //We'll need this later!!
                $oldid = backup_todb($cc_info['#']['ID']['0']['#']);
    
                //Now, build the TRACKER_ISSUECC record structure
                $cc->trackerid = $new_tracker_id;  
                $cc->issueid = backup_todb($cc_info['#']['ISSUEID']['0']['#']);  
                $cc->userid = backup_todb($cc_info['#']['USERID']['0']['#']);  
                $cc->events = backup_todb($cc_info['#']['EVENTS']['0']['#']);  
    
                //We have to recode the issueid field
                $issue = backup_getid($restore->backup_unique_code, 'tracker_issue', $cc->issueid);
                if ($issue) {
                    $cc->issueid = $issue->new_id;
                }
    
                //We have to recode the userid field
                $user = backup_getid($restore->backup_unique_code, 'user', $cc->userid);
                if ($user) {
                    $cc->userid = $user->new_id;
                }
    
                //The structure is equal to the db, so insert the carbon copy
                $newid = insert_record ('tracker_issuecc', $cc);
    
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
                    backup_putid($restore->backup_unique_code, 'tracker_issuecc', $oldid, $newid);
                } else {
                    $status = false;
                }
            }
        }
        return $status;
    }

    //This function restores the issue comments
    function tracker_issuecomments_restore_mods($old_tracker_id, $new_tracker_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the comments array
        $comments = @$info['MOD']['#']['COMMENTS']['0']['#']['COMMENT'];

        //Iterate over comments
        if (!empty($comments)){
            for($i = 0; $i < sizeof($comments); $i++) {
                $comment_info = $comments[$i];
                //traverse_xmlize($comment_info);                                                         //Debug
                //print_object ($GLOBALS['traverse_array']);                                                  //Debug
                //$GLOBALS['traverse_array']="";                                                              //Debug
    
                //We'll need this later!!
                $oldid = backup_todb($comment_info['#']['ID']['0']['#']);
    
                //Now, build the TRACKER_ISSUECOMMENT record structure
                $comment->trackerid = $new_tracker_id;  
                $comment->issueid = backup_todb($comment_info['#']['ISSUEID']['0']['#']);  
                $comment->userid = backup_todb($comment_info['#']['USERID']['0']['#']);  
                $comment->comment = backup_todb($comment_info['#']['COMMENT']['0']['#']);  
                $comment->commentformat = backup_todb($comment_info['#']['COMMENTFORMAT']['0']['#']);  
                $comment->datecreated = backup_todb($comment_info['#']['DATECREATED']['0']['#']);  
    
                //We have to recode the issueid field
                $issue = backup_getid($restore->backup_unique_code, 'tracker_issue', $comment->issueid);
                if ($issue) {
                    $comment->issueid = $issue->new_id;
                }
    
                //We have to recode the userid field
                $user = backup_getid($restore->backup_unique_code, 'user', $comment->userid);
                if ($user) {
                    $comment->userid = $user->new_id;
                }
    
                //The structure is equal to the db, so insert the comment
                $newid = insert_record ('tracker_issuecomment', $comment);
    
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
                    backup_putid($restore->backup_unique_code, 'tracker_issuecomment', $oldid, $newid);
                } else {
                    $status = false;
                }
            }
        }
        return $status;
    }

    //This function restores the issue dependancies
    function tracker_issuedependancies_restore_mods($old_tracker_id, $new_tracker_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the dependancies array
        $dependancies = @$info['MOD']['#']['DEPENDANCIES']['0']['#']['DEPENDANCY'];

        //Iterate over dependancies
        if (!empty($dependancies)){
            for($i = 0; $i < sizeof($dependancies); $i++) {
                $dependancy_info = $dependancies[$i];
                //traverse_xmlize($dependancy_info);                                                         //Debug
                //print_object ($GLOBALS['traverse_array']);                                                  //Debug
                //$GLOBALS['traverse_array']="";                                                              //Debug
    
                //We'll need this later!!
                $oldid = backup_todb($dependancy_info['#']['ID']['0']['#']);
    
                //Now, build the TRACKER_ISSUEDEPENDANCY record structure
                $dependancy->trackerid = $new_tracker_id;  
                $dependancy->childid = backup_todb($dependancy_info['#']['CHILDID']['0']['#']);  
                $dependancy->parentid = backup_todb($dependancy_info['#']['PARENTID']['0']['#']);  
                $dependancy->comment = backup_todb($dependancy_info['#']['COMMENT']['0']['#']);  
                $dependancy->commentformat = backup_todb($dependancy_info['#']['COMMENTFORMAT']['0']['#']);  
    
                //We have to recode the childid field
                $issue = backup_getid($restore->backup_unique_code, 'tracker_issue', $dependancy->childid);
                if ($issue) {
                    $dependancy->childid = $issue->new_id;
                }
    
                //We have to recode the parentid field
                $issue = backup_getid($restore->backup_unique_code, 'tracker_issue', $dependancy->parentid);
                if ($issue) {
                    $dependancy->parentid = $issue->new_id;
                }
    
                //The structure is equal to the db, so insert the dependancy
                $newid = insert_record ('tracker_issuedependancy', $dependancy);
    
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
                    backup_putid($restore->backup_unique_code, 'tracker_issuedependancy', $oldid, $newid);
                } else {
                    $status = false;
                }
            }
        }
        return $status;
    }

    //This function restores the issue ownerships
    function tracker_issueownerships_restore_mods($old_tracker_id, $new_tracker_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the ownerships array
        $ownerships = @$info['MOD']['#']['OWNERSHIPS']['0']['#']['OWNERSHIP'];

        //Iterate over ownerships
        if (!empty($ownerships)){
            for($i = 0; $i < sizeof($ownerships); $i++) {
                $ownership_info = $ownerships[$i];
                //traverse_xmlize($ownership_info);                                                         //Debug
                //print_object ($GLOBALS['traverse_array']);                                                  //Debug
                //$GLOBALS['traverse_array']="";                                                              //Debug
    
                //We'll need this later!!
                $oldid = backup_todb($dependancy_info['#']['ID']['0']['#']);
    
                //Now, build the TRACKER_ISSUEOWNERSHIP record structure
                $ownership->trackerid = $new_tracker_id;  
                $ownership->userid = backup_todb($ownership_info['#']['USERID']['0']['#']);  
                $ownership->issueid = backup_todb($ownership_info['#']['ISSUEID']['0']['#']);  
                $ownership->bywhomid = backup_todb($ownership_info['#']['BYWHOMID']['0']['#']);  
                $ownership->timeassigned = backup_todb($ownership_info['#']['TIMEASSIGNED']['0']['#']);  
    
                //We have to recode the issueid field
                $issue = backup_getid($restore->backup_unique_code, 'tracker_issue', $ownership->issueid);
                if ($issue) {
                    $ownership->issueid = $issue->new_id;
                }
    
                //We have to recode the userid field
                $user = backup_getid($restore->backup_unique_code, 'user', $ownership->userid);
                if ($user) {
                    $ownership->userid = $user->new_id;
                }
    
                //We have to recode the bywhomid field
                $user = backup_getid($restore->backup_unique_code, 'user', $ownership->bywhomid);
                if ($user) {
                    $ownership->bywhomid = $user->new_id;
                }
    
                //The structure is equal to the db, so insert the ownership
                $newid = insert_record ('tracker_issueownership', $ownership);
    
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
                    backup_putid($restore->backup_unique_code, 'tracker_issueownership', $oldid, $newid);
                } else {
                    $status = false;
                }
            }
        }
        return $status;
    }

?>

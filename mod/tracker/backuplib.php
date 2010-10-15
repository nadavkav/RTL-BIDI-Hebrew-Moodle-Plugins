<?php //$Id: backuplib.php,v 1.2.2.7 2010/02/13 16:35:16 diml Exp $

/**
* @package mod-tracker
* @category mod
* @author Valery Fremaux > 1.8
* @date 02/12/2007
*
* Backup library for module tracker
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
    //           +-------------------------------+------------------------------+-------------------------+               
    //           |                               |                              |                         |
    //      tracker_issue              tracker_elementused              tracker_dependancy        tracker_preferences
    //  (IL,pk->id, fk->trackerid)  (IL, pk->id, fk->trackerid)  (IL, pk->id, fk->parentid,      (UL, pk->id, fk->userid)
    //           |                                                       fk->childid)
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

    function tracker_backup_mods($bf, $preferences) {
        //Iterate over tracker table
        $trackers = get_records ('tracker', 'course', $preferences->backup_course, 'id');
        if ($trackers) {
            foreach ($trackers as $tracker) {
                tracker_backup_one_mod($bf, $preferences, $tracker);
            }
        }
    }

    function tracker_backup_one_mod($bf, $preferences, $tracker) {
        global $CFG;

        if (is_numeric($tracker)) {
            $tracker = get_record('tracker', 'id', $tracker);
        }

        $status = true;

        //Start mod
        $status = $status && fwrite ($bf, start_tag('MOD', 3, true));
        //Print tracker data
        fwrite ($bf, full_tag('ID', 4, false, $tracker->id));
        fwrite ($bf, full_tag('MODTYPE', 4, false, 'tracker'));
        fwrite ($bf, full_tag('NAME', 4, false, $tracker->name));
        fwrite ($bf, full_tag('DESCRIPTION', 4, false, $tracker->description));
        fwrite ($bf, full_tag('FORMAT', 4, false, $tracker->format));
        fwrite ($bf, full_tag('REQUIRELOGIN', 4, false, $tracker->requirelogin));
        fwrite ($bf, full_tag('ALLOWNOTIFICATIONS', 4, false, $tracker->allownotifications));
        fwrite ($bf, full_tag('ENABLECOMMENTS', 4, false, $tracker->enablecomments));
        fwrite ($bf, full_tag('TICKETPREFIX', 4, false, $tracker->ticketprefix));
        fwrite ($bf, full_tag('TIMEMODIFIED', 4, false, $tracker->timemodified));
        fwrite ($bf, full_tag('PARENT', 4, false, $tracker->parent));
        fwrite ($bf, full_tag('SUPPORTMODE', 4, false, $tracker->supportmode));

        // store user independant records anyway
        $used = backup_tracker_elementuseds($bf, $preferences, $tracker->id);
        $stored = backup_tracker_elements($bf, $preferences, $used);
        $status = $status && backup_tracker_elementitems($bf, $preferences, $stored);

        //if we've selected to backup users info, then execute other backups. We say it is "user generated" content
        if ($preferences->mods['tracker']->userinfo) {
            $status = $status && backup_tracker_preferences($bf, $preferences, $tracker->id);
            $tracker_issues = get_records('tracker_issue', 'trackerid', $tracker->id, 'id');
            $status = $status && backup_tracker_issues($bf, $preferences, $tracker->id, $tracker_issues);
            $status = $status && backup_tracker_issueattributes($bf, $preferences, $tracker->id);
            $status = $status && backup_tracker_issuecomments($bf, $preferences, $tracker->id);
            $status = $status && backup_tracker_issueccs($bf, $preferences, $tracker->id);
            $status = $status && backup_tracker_issuedependancies($bf, $preferences, $tracker->id);
            $status = $status && backup_tracker_issueownerships($bf, $preferences, $tracker->id);
            $status = $status && backup_tracker_queries($bf, $preferences, $tracker->id);
        }
        //End mod
        $status = $status && fwrite($bf, end_tag('MOD', 3, true));
        return $status;
    }

    //Backup elementused (executed from tracker_backup_mods). Returns an array of used elements
    function backup_tracker_elementuseds($bf, $preferences, $trackerid) {
        global $CFG;
        
        $used = array();
        $elementuseds = get_records('tracker_elementused', 'trackerid', $trackerid);
        if ($elementuseds) {
            //Write start tag
            fwrite ($bf, start_tag('ELEMENTUSEDS', 4, true));
            //Iterate over each elementused
            foreach ($elementuseds as $elementused) {
                //Start elementused
                fwrite ($bf, start_tag('ELEMENTUSED', 5, true));
                //Print elementused data
                fwrite ($bf, full_tag('ID', 6, false, $elementused->id));
                fwrite ($bf, full_tag('TRACKERID', 6, false, $elementused->trackerid)); 
                fwrite ($bf, full_tag('ELEMENTID', 6, false, $elementused->elementid)); 
                $used[] = $elementused->elementid;
                fwrite ($bf, full_tag('SORTORDER', 6, false, $elementused->sortorder));
                fwrite ($bf, full_tag('CANBEMODIFIEDBY', 6, false, $elementused->canbemodifiedby)); 
                fwrite ($bf, full_tag('ACTIVE', 6, false, $elementused->active));
                //End elementused
                fwrite ($bf, end_tag('ELEMENTUSED', 5, true));
            }
            //Write end tag
            fwrite($bf, end_tag('ELEMENTUSEDS', 4, true));
        }
        return $used;
    }

    //Backup elements (executed from tracker_backup_mods). Only backups those which are actually used
    function backup_tracker_elements($bf, $preferences, $used) {
        global $CFG, $COURSE;

        $stored = array();        
        if (!empty($used)){
            $elementlist = implode ("','", $used);
            $elements = get_records_select('tracker_element', " id IN ('$elementlist') ");
            if ($elements) {
                //Write start tag
                fwrite ($bf, start_tag('ELEMENTS', 4, true));
                //Iterate over each element
                foreach ($elements as $element) {
                    //Start element
                    fwrite ($bf, start_tag('ELEMENT', 5, true));
                    //Print element data
                    $stored[] = $element->id;
                    fwrite ($bf, full_tag('ID', 6, false, $element->id));
                    fwrite ($bf, full_tag('COURSE', 6, false, $COURSE->id)); // Reassign element locally to this tracker
                    fwrite ($bf, full_tag('NAME', 6, false, $element->name)); 
                    fwrite ($bf, full_tag('DESCRIPTION', 6, false, $element->description));
                    fwrite ($bf, full_tag('TYPE', 6, false, $element->type)); 
                    //End element
                    fwrite ($bf, end_tag('ELEMENT', 5, true));
                }
                //Write end tag
                fwrite($bf, end_tag('ELEMENTS', 4, true));
            }
        }
        return $stored;
    }

    //Backup elementitems (executed from tracker_backup_mods). Only backups those which are actually used
    function backup_tracker_elementitems($bf, $preferences, $stored) {
        global $CFG, $COURSE;

        $status = true;        
        if (!empty($stored)){
            $elementlist = implode ("','", $stored);
            $elementitems = get_records_select('tracker_elementitem', " elementid IN ('$elementlist') ");
            if ($elementitems) {
                //Write start tag
                $status = $status && fwrite ($bf, start_tag('ELEMENTITEMS', 4, true));
                //Iterate over each elementitem
                foreach ($elementitems as $elementitem) {
                    //Start elementitem
                    $status = $status && fwrite ($bf, start_tag('ELEMENTITEM', 5, true));
                    //Print elementitem data
                    $stored[] = $elementitem->id;
                    fwrite ($bf, full_tag('ID', 6, false, $elementitem->id));
                    fwrite ($bf, full_tag('ELEMENTID', 6, false, $elementitem->elementid));
                    fwrite ($bf, full_tag('NAME', 6, false, $elementitem->name)); 
                    fwrite ($bf, full_tag('DESCRIPTION', 6, false, $elementitem->description));
                    fwrite ($bf, full_tag('SORTORDER', 6, false, $elementitem->sortorder)); 
                    fwrite ($bf, full_tag('ACTIVE', 6, false, $elementitem->active)); 
                    //End elementitem
                    $status = $status && fwrite ($bf, end_tag('ELEMENTITEM', 5, true));
                }
                //Write end tag
                $status = $status && fwrite($bf, end_tag('ELEMENTITEMS', 4, true));
            }
        }
        return $status;
    }

    //Backup user preferences (executed from tracker_backup_mods)
    function backup_tracker_preferences($bf, $preferences, $trackerid) {
        global $CFG, $COURSE;

        $status = true;        
        $preferences = get_records('tracker_preferences', 'trackerid', $trackerid);
        if ($preferences) {
            //Write start tag
            $status = $status && fwrite ($bf, start_tag('PREFERENCES', 4, true));
            //Iterate over each preference
            foreach ($preferences as $preference) {
                //Start elementitem
                $status = $status && fwrite ($bf, start_tag('PREFERENCE', 5, true));
                //Print preference data
                fwrite ($bf, full_tag('ID', 6, false, $preference->id));
                fwrite ($bf, full_tag('TRACKERID', 6, false, $preference->trackerid));
                fwrite ($bf, full_tag('USERID', 6, false, $preference->userid));
                fwrite ($bf, full_tag('NAME', 6, false, $preference->name)); 
                fwrite ($bf, full_tag('VALUE', 6, false, $preference->value));
                //End preference
                $status = $status && fwrite ($bf, end_tag('PREFERENCE', 5, true));
            }
            //Write end tag
            $status = $status && fwrite($bf, end_tag('PREFERENCES', 4, true));
        }
        return $status;
    }

    //Backup tracker issue contents (executed from tracker_backup_mods)
    function backup_tracker_issues($bf, $preferences, $trackerid, $issues) {
        global $CFG;

        $status = true;

        //If there are issues
        if ($issues) {
            //Write start tag
            $status = $status && fwrite ($bf, start_tag('ISSUES', 4, true));
            //Iterate over each issue
            foreach ($issues as $issue) {
                //Start issue
                $status = $status && fwrite ($bf, start_tag('ISSUE', 5, true));
                //Print issues contents
                fwrite ($bf, full_tag('ID', 6, false, $issue->id));
                fwrite ($bf, full_tag('TRACKERID', 6, false, $issue->trackerid));       
                fwrite ($bf, full_tag('SUMMARY', 6, false, $issue->summary));         
                fwrite ($bf, full_tag('DESCRIPTION', 6, false, $issue->description));     
                fwrite ($bf, full_tag('FORMAT', 6, false, $issue->format));          
                fwrite ($bf, full_tag('DATEREPORTED', 6, false, $issue->datereported));    
                fwrite ($bf, full_tag('REPORTEDBY', 6, false, $issue->reportedby));      
                fwrite ($bf, full_tag('STATUS', 6, false, $issue->status));          
                fwrite ($bf, full_tag('ASSIGNEDTO', 6, false, $issue->assignedto));      
                fwrite ($bf, full_tag('BYWHOMID', 6, false, $issue->bywhomid));        
                fwrite ($bf, full_tag('TIMEASSIGNED', 6, false, $issue->timeassigned));    
                fwrite ($bf, full_tag('RESOLUTION', 6, false, $issue->resolution));      
                fwrite ($bf, full_tag('RESOLUTIONFORMAT', 6, false, $issue->resolutionformat));
                fwrite ($bf, full_tag('RESOLUTIONPRIORITY', 6, false, $issue->resolutionpriority));
                //End issue
                $status = $status && fwrite ($bf, end_tag('ISSUE', 5, true));
            }
            //Write end tag
            $status = $status && fwrite($bf, end_tag('ISSUES', 4, true));
        }
        return $status;
    }

    //Backup issue attributes (executed from tracker_backup_mods)
    function backup_tracker_issueattributes($bf, $preferences, $trackerid) {
        global $CFG;

        $status = true;

        $attributes = get_records('tracker_issueattribute', 'trackerid', $trackerid);
        if ($attributes) {
            //Write start tag
            $status = $status && fwrite ($bf, start_tag('ATTRIBUTES', 4, true));
            //Iterate over each attributes
            foreach ($attributes as $attribute) {
                //Start attribute
                $status = $status && fwrite ($bf, start_tag('ATTRIBUTE', 5, true));
                //Print attribute data
                fwrite ($bf, full_tag('ID', 6, false, $attribute->id));
                fwrite ($bf, full_tag('TRACKERID', 6, false, $attribute->trackerid)); 
                fwrite ($bf, full_tag('ISSUEID', 6, false, $attribute->issueid)); 
                fwrite ($bf, full_tag('ELEMENTID', 6, false, $attribute->elementid));
                fwrite ($bf, full_tag('ELEMENTITEMID', 6, false, $attribute->elementitemid)); 
                fwrite ($bf, full_tag('TIMEMODIFIED', 6, false, $attribute->timemodified));
                //End attribute
                $status = $status && fwrite ($bf, end_tag('ATTRIBUTE', 5, true));
            }
            //Write end tag
            $status = $status && fwrite($bf, end_tag('ATTRIBUTES', 4, true));
        }
        return $status;
    }

    //Backup issue comments (executed from tracker_backup_mods)
    function backup_tracker_issuecomments($bf, $preferences, $trackerid) {
        global $CFG;

        $status = true;

        $comments = get_records('tracker_issuecomment', 'trackerid', $trackerid);
        if ($comments) {
            //Write start tag
            $status = $status && fwrite ($bf, start_tag('COMMENTS', 4, true));
            //Iterate over each comment
            foreach ($comments as $comment) {
                //Start comment
                $status = $status && fwrite ($bf, start_tag('COMMENT', 5, true));
                //Print comment data
                fwrite ($bf, full_tag('ID', 6, false, $comment->id));
                fwrite ($bf, full_tag('TRACKERID', 6, false, $comment->trackerid)); 
                fwrite ($bf, full_tag('USERID', 6, false, $comment->userid)); 
                fwrite ($bf, full_tag('ISSUEID', 6, false, $comment->issueid)); 
                fwrite ($bf, full_tag('COMMENT', 6, false, $comment->comment));
                fwrite ($bf, full_tag('COMMENTFORMAT', 6, false, $comment->commentformat)); 
                fwrite ($bf, full_tag('DATECREATED', 6, false, $comment->datecreated));
                //End comment
                $status = $status && fwrite ($bf, end_tag('COMMENT', 5, true));
            }
            //Write end tag
            $status = $status && fwrite($bf, end_tag('COMMENTS', 4, true));
        }
        return $status;
    }

    //Backup issue carbon copy (executed from tracker_backup_mods)
    function backup_tracker_issueccs($bf, $preferences, $trackerid) {
        global $CFG;

        $status = true;

        $ccs = get_records('tracker_issuecc', 'trackerid', $trackerid);
        if ($ccs) {
            //Write start tag
            $status = $status && fwrite ($bf, start_tag('CCS', 4, true));
            //Iterate over each cc
            foreach ($ccs as $cc) {
                //Start cc
                $status = $status && fwrite ($bf, start_tag('CC', 5, true));
                //Print cc data
                fwrite ($bf, full_tag('ID', 6, false, $cc->id));
                fwrite ($bf, full_tag('TRACKERID', 6, false, $cc->trackerid)); 
                fwrite ($bf, full_tag('USERID', 6, false, $cc->userid)); 
                fwrite ($bf, full_tag('ISSUEID', 6, false, $cc->issueid)); 
                fwrite ($bf, full_tag('EVENTS', 6, false, $cc->events)); 
                //End cc
                $status = $status && fwrite ($bf, end_tag('CC', 5, true));
            }
            //Write end tag
            $status = $status && fwrite($bf, end_tag('CCS', 4, true));
        }
        return $status;
    }

    //Backup issue dependancies (executed from tracker_backup_mods)
    function backup_tracker_issuedependancies($bf, $preferences, $trackerid) {
        global $CFG;

        $status = true;

        $dependancies = get_records('tracker_issuedependancy', 'trackerid', $trackerid);
        if ($dependancies) {
            //Write start tag
            $status = $status && fwrite ($bf, start_tag('DEPENDANCIES', 4, true));
            //Iterate over each dependancy
            foreach ($dependancies as $dependancy) {
                //Start dependancy
                $status = $status && fwrite ($bf, start_tag('DEPENDANCY', 5, true));
                //Print dependancy data
                fwrite ($bf, full_tag('ID', 6, false, $dependancy->id));
                fwrite ($bf, full_tag('TRACKERID', 6, false, $dependancy->trackerid)); 
                fwrite ($bf, full_tag('CHILDID', 6, false, $dependancy->childid)); 
                fwrite ($bf, full_tag('PARENTID', 6, false, $dependancy->parentid));
                fwrite ($bf, full_tag('COMMENT', 6, false, $dependancy->comment));
                fwrite ($bf, full_tag('COMMENTFORMAT', 6, false, $dependancy->commentformat));
                //End dependancy
                $status = $status && fwrite ($bf, end_tag('DEPENDANCY', 5, true));
            }
            //Write end tag
            $status = $status && fwrite($bf, end_tag('DEPENDANCIES', 4, true));
        }
        return $status;
    }

    //Backup issue ownership (executed from tracker_backup_mods)
    function backup_tracker_issueownerships($bf, $preferences, $trackerid) {
        global $CFG;

        $status = true;

        $ownerships = get_records('tracker_issueownership', 'trackerid', $trackerid);
        if ($ownerships) {
            //Write start tag
            $status = $status && fwrite ($bf, start_tag('OWNERSHIPS', 4, true));
            //Iterate over each ownership
            foreach ($ownerships as $ownership) {
                //Start ownership
                $status = $status && fwrite ($bf, start_tag('OWNERSHIP', 5, true));
                //Print ownership data
                fwrite ($bf, full_tag('ID', 6, false, $ownership->id));
                fwrite ($bf, full_tag('TRACKERID', 6, false, $ownership->trackerid)); 
                fwrite ($bf, full_tag('USERID', 6, false, $ownership->userid)); 
                fwrite ($bf, full_tag('ISSUEID', 6, false, $ownership->issueid));
                fwrite ($bf, full_tag('BYWHOMID', 6, false, $ownership->bywhomid));
                fwrite ($bf, full_tag('TIMEASSIGNED', 6, false, $ownership->timeassigned));
                //End ownership
                $status = $status && fwrite ($bf, end_tag('OWNERSHIP', 5, true));
            }
            //Write end tag
            $status = $status && fwrite($bf, end_tag('OWNERSHIPS', 4, true));
        }
        return $status;
    }

    //Backup user queries (executed from tracker_backup_mods)
    function backup_tracker_queries($bf, $preferences, $trackerid) {
        global $CFG;

        $status = true;

        $queries = get_records('tracker_query', 'trackerid', $trackerid);
        if ($queries) {
            //Write start tag
            $status = $status && fwrite ($bf, start_tag('QUERIES', 4, true));
            //Iterate over each query
            foreach ($queries as $query) {
                //Start query
                $status = $status && fwrite ($bf, start_tag('QUERY', 5, true));
                //Print query data
                fwrite ($bf, full_tag('ID', 6, false, $query->id));
                fwrite ($bf, full_tag('TRACKERID', 6, false, $query->trackerid)); 
                fwrite ($bf, full_tag('USERID', 6, false, $query->userid)); 
                fwrite ($bf, full_tag('NAME', 6, false, $query->name));
                fwrite ($bf, full_tag('DESCRIPTION', 6, false, $query->description));
                fwrite ($bf, full_tag('PUBLISHED', 6, false, $query->published));
                fwrite ($bf, full_tag('FIELDNAMES', 6, false, $query->fieldnames));
                fwrite ($bf, full_tag('FIELDVALUES', 6, false, $query->fieldvalues));
                //End query
                $status = $status && fwrite ($bf, end_tag('QUERY', 5, true));
            }
            //Write end tag
            $status = $status && fwrite($bf, end_tag('QUERIES', 4, true));
        }
        return $status;
    }
 
   ////Return an array of info (name, value)
   function tracker_check_backup_mods($course, $user_data=false, $backup_unique_code) {
        //First the course data
        $info[0][0] = get_string('modulenameplural', 'tracker');
        if ($ids = tracker_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string('issues', 'tracker');
            if ($ids = tracker_issue_ids_by_course ($course)) {
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
            $info[2][0] = get_string('attributes', 'tracker');
            if ($ids = tracker_issueattribute_ids_by_course($course)) {
                $info[2][1] = count($ids);
            } else {
                $info[2][1] = 0;
            }
            $info[3][0] = get_string('comments', 'tracker');
            if ($ids = tracker_issuecomment_ids_by_course($course)) {
                $info[3][1] = count($ids);
            } else {
                $info[3][1] = 0;
            }
            $info[4][0] = get_string('ccs', 'tracker');
            if ($ids = tracker_issuecc_ids_by_course($course)) {
                $info[4][1] = count($ids);
            } else {
                $info[4][1] = 0;
            }
            $info[5][0] = get_string('dependancies', 'tracker');
            if ($ids = tracker_issuedependancy_ids_by_course($course)) {
                $info[5][1] = count($ids);
            } else {
                $info[5][1] = 0;
            }
            $info[6][0] = get_string('preferences', 'tracker');
            if ($ids = tracker_preferences_ids_by_course($course)) {
                $info[6][1] = count($ids);
            } else {
                $info[6][1] = 0;
            }
            $info[7][0] = get_string('queries', 'tracker');
            if ($ids = tracker_queries_ids_by_course($course)) {
                $info[7][1] = count($ids);
            } else {
                $info[7][1] = 0;
            }
        }
        return $info;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of trackers id
    function tracker_ids ($course) {
        global $CFG;

        $sql = "
            SELECT 
                t.id, 
                t.course
            FROM 
                {$CFG->prefix}tracker t
            WHERE 
                t.course = '{$course}'
        ";
        return get_records_sql ($sql);
    }
   
    //Returns an array of tracker issues id
    function tracker_issue_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                i.id , 
                i.trackerid
            FROM 
                {$CFG->prefix}tracker_issue i, 
                {$CFG->prefix}tracker t
            WHERE 
                t.course = '{$course}' AND
                i.trackerid = t.id
        ";
        return get_records_sql($sql);
    }

    //Returns an array of tracker issue attribute ids
    function tracker_issueattribute_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                ia.id,
                ia.trackerid
            FROM 
                {$CFG->prefix}tracker_issueattribute ia, 
                {$CFG->prefix}tracker t
            WHERE 
                t.course = '{$course}' AND
                ia.trackerid = t.id
        ";
        return get_records_sql($sql);
    }

    //Returns an array of tracker issue comments ids
    function tracker_issuecomment_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                ic.id,
                ic.trackerid
            FROM 
                {$CFG->prefix}tracker_issuecomment ic, 
                {$CFG->prefix}tracker t
            WHERE 
                t.course = '{$course}' AND
                ic.trackerid = t.id
        ";
        return get_records_sql($sql);
    }

    //Returns an array of tracker issue cc ids
    function tracker_issuecc_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                ic.id,
                ic.trackerid
            FROM 
                {$CFG->prefix}tracker_issuecc ic, 
                {$CFG->prefix}tracker t
            WHERE 
                t.course = '{$course}' AND
                ic.trackerid = t.id
        ";
        return get_records_sql($sql);
    }

    //Returns an array of tracker issue dependancy ids
    function tracker_issuedependancy_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                id.id,
                id.trackerid
            FROM 
                {$CFG->prefix}tracker_issuedependancy id, 
                {$CFG->prefix}tracker t
            WHERE 
                t.course = '{$course}' AND
                id.trackerid = t.id
        ";
        return get_records_sql($sql);
    }

    //Returns an array of tracker issue ownership ids
    function tracker_issueownership_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                io.id,
                io.trackerid
            FROM 
                {$CFG->prefix}tracker_issueownership io, 
                {$CFG->prefix}tracker t
            WHERE 
                t.course = '{$course}' AND
                io.trackerid = t.id
        ";
        return get_records_sql($sql);
    }

    //Returns an array of tracker preferences ids
    function tracker_preferences_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                p.id,
                p.trackerid
            FROM 
                {$CFG->prefix}tracker_preferences p, 
                {$CFG->prefix}tracker t
            WHERE 
                t.course = '{$course}' AND
                p.trackerid = t.id
        ";
        return get_records_sql($sql);
    }

    //Returns an array of user's queries ids
    function tracker_queries_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                q.id,
                q.trackerid
            FROM 
                {$CFG->prefix}tracker_query q, 
                {$CFG->prefix}tracker t
            WHERE 
                t.course = '{$course}' AND
                q.trackerid = t.id
        ";
        return get_records_sql($sql);
    }
    
?>

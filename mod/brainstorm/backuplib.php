<?php //$Id: backuplib.php, v 1.2 2006/02/02 00:35:46 diml Exp $
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

    //This function executes all the backup procedure about this mod
    function brainstorm_backup_mods($bf, $preferences) {
        global $CFG;

        $status = true; 
        
        ////Iterate over brainstorm table
        $brainstorms = get_records('brainstorm', 'course', $preferences->backup_course);

        if ($brainstorms) {
            foreach ($brainstorms as $brainstorm) {
                $status = brainstorm_backup_one_mod($bf, $preferences, $brainstorm);
            }
        }
        return $status;
    }

    function brainstorm_backup_one_mod($bf, $preferences, $brainstorm){

        if (is_numeric($brainstorm)) {
            $brainstorm = get_record('brainstorm', 'id', $brainstorm);
        }
        
        $status = true;

        //Start mod
        $status = $status && fwrite ($bf, start_tag('MOD', 3, true));
        //Print brainstorm data
        fwrite ($bf, full_tag('ID', 4, false, $brainstorm->id));
        fwrite ($bf, full_tag('MODTYPE', 4, false, 'brainstorm'));
        fwrite ($bf, full_tag('NAME', 4, false, $brainstorm->name));
        fwrite ($bf, full_tag('DESCRIPTION', 4, false, $brainstorm->description));
        fwrite ($bf, full_tag('FLOWMODE', 4, false, $brainstorm->flowmode));
        fwrite ($bf, full_tag('SEQACCESSCOLLECT', 4, false, $brainstorm->seqaccesscollect));  
        fwrite ($bf, full_tag('SEQACCESSPREPARE', 4, false, $brainstorm->seqaccessprepare));  
        fwrite ($bf, full_tag('SEQACCESSORGANIZE', 4, false, $brainstorm->seqaccessorganize));  
        fwrite ($bf, full_tag('SEQACCESSDISPLAY', 4, false, $brainstorm->seqaccessdisplay));
        fwrite ($bf, full_tag('SEQACCESSFEEDBACK', 4, false, $brainstorm->seqaccessfeedback));
        fwrite ($bf, full_tag('PHASE', 4, false, $brainstorm->phase));  
        fwrite ($bf, full_tag('PRIVACY', 4, false, $brainstorm->privacy));  
        fwrite ($bf, full_tag('NUMRESPONSES', 4, false, $brainstorm->numresponses));  
        fwrite ($bf, full_tag('NUMRESPONSESINFORM', 4, false, $brainstorm->numresponsesinform));  
        fwrite ($bf, full_tag('NUMCOLUMNS', 4, false, $brainstorm->numcolumns));
        fwrite ($bf, full_tag('OPREQUIREMENTTYPE', 4, false, $brainstorm->oprequirementtype));
        fwrite ($bf, full_tag('SCALE', 4, false, $brainstorm->scale));
        fwrite ($bf, full_tag('SINGLEGRADE', 4, false, $brainstorm->singlegrade));
        fwrite ($bf, full_tag('PARTICIPATIONWEIGHT', 4, false, $brainstorm->participationweight));
        fwrite ($bf, full_tag('PREPARINGWEIGHT', 4, false, $brainstorm->preparingweight));
        fwrite ($bf, full_tag('ORGANIZEWEIGHT', 4, false, $brainstorm->organizeweight));
        fwrite ($bf, full_tag('FEEDBACKWEIGHT', 4, false, $brainstorm->feedbackweight));
        fwrite ($bf, full_tag('TIMEMODIFIED', 4, false, $brainstorm->timemodified));

        $status = $status && backup_brainstorm_operators($bf, $preferences, $brainstorm->id);

        //if we've selected to backup users info, then execute backup_brainstorm_slots and appointments
        if ($preferences->mods['brainstorm']->userinfo) {
            $status = $status && backup_brainstorm_categories($bf, $preferences, $brainstorm->id);
            $status = $status && backup_brainstorm_operatordata($bf, $preferences, $brainstorm->id);
            $status = $status && backup_brainstorm_responses($bf, $preferences, $brainstorm->id);
            $status = $status && backup_brainstorm_userdata($bf, $preferences, $brainstorm->id);
            $status = $status && backup_brainstorm_grades($bf, $preferences, $brainstorm->id);
        }
        //End mod
        $status = $status && fwrite($bf, end_tag('MOD', 3, true));

        return $status;
    }

    //Backup operator settings (executed from brainstorm_backup_mods)
    function backup_brainstorm_operators ($bf, $preferences, $brainstormid) {
        global $CFG;

        $status = true;
        
        $operators = get_records('brainstorm_operators', 'brainstormid', $brainstormid);

        //If there is operators
        if ($operators) {
            //Write start tag
            $status = $status && fwrite ($bf, start_tag('OPERATORS', 4, true));
            //Iterate over each operator
            foreach ($operators as $operator) {
                //Start slot
                $status = $status && fwrite ($bf, start_tag('OPERATOR', 5, true));
                //Print brainstorm_slots contents
                fwrite ($bf, full_tag('ID', 6, false, $operator->id));
                fwrite ($bf, full_tag('BRAINSTORMID', 6, false, $operator->brainstormid));  
                fwrite ($bf, full_tag('OPERATORID', 6, false, $operator->operatorid));  
                fwrite ($bf, full_tag('CONFIGDATA', 6, false, $operator->configdata));  
                fwrite ($bf, full_tag('ACTIVE', 6, false, $operator->active));  
                //End slot
                $status = $status && fwrite ($bf, end_tag('OPERATOR', 5, true));
            }
            //Write end tag
            $status = $status && fwrite($bf, end_tag('OPERATORS', 4, true));
        }
        return $status;
    }

    //Backup brainstorm categories (executed from brainstorm_backup_mods)
    function backup_brainstorm_categories($bf, $preferences, $brainstormid) {
        global $CFG;

        $status = true;

        $categories = get_records('brainstorm_categories', 'brainstormid', $brainstormid);

        $status = $status && fwrite ($bf, start_tag('CATEGORIES', 4, true));
        //Iterate over each categories
        foreach ($categories as $category) {
            //Start categories
            $status = $status && fwrite ($bf, start_tag('CATEGORY', 5, true));
            //Print category data
            fwrite ($bf, full_tag('ID', 6, false, $category->id));
            fwrite ($bf, full_tag('BRAINSTORMID', 6, false, $category->brainstormid));
            fwrite ($bf, full_tag('USERID', 6, false, $category->userid));
            fwrite ($bf, full_tag('GROUPID', 6, false, $category->groupid));
            fwrite ($bf, full_tag('TITLE', 6, false, $category->title));
            fwrite ($bf, full_tag('TIMEMODIFIED', 6, false, $category->timemodified));
            //End category
            $status = $status && fwrite ($bf, end_tag('CATEGORY', 5, true));
        }
        //Write end tag
        $status = $status && fwrite($bf, end_tag('CATEGORIES', 4, true));

        return $status;
    }

    //Backup brainstorm responses (executed from brainstorm_backup_mods)
    function backup_brainstorm_responses($bf, $preferences, $brainstormid) {
        global $CFG;

        $status = true;

        $responses = get_records('brainstorm_responses', 'brainstormid', $brainstormid);

        $status = $status && fwrite ($bf, start_tag('RESPONSES', 4, true));
        //Iterate over each response
        foreach ($responses as $response) {
            //Start responses
            $status = $status && fwrite ($bf, start_tag('RESPONSE', 5, true));
            //Print response data
            fwrite ($bf, full_tag('ID', 6, false, $response->id));
            fwrite ($bf, full_tag('BRAINSTORMID', 6, false, $response->brainstormid));  
            fwrite ($bf, full_tag('USERID', 6, false, $response->userid));
            fwrite ($bf, full_tag('GROUPID', 6, false, $response->groupid));
            fwrite ($bf, full_tag('RESPONSE', 6, false, $response->response));
            fwrite ($bf, full_tag('TIMEMODIFIED', 6, false, $response->timemodified));
            //End response
            $status = $status && fwrite ($bf, end_tag('RESPONSE', 5, true));
        }
        //Write end tag
        $status = $status && fwrite($bf, end_tag('RESPONSES', 4, true));

        return $status;
    }

    //Backup brainstorm operator data (executed from brainstorm_backup_mods)
    function backup_brainstorm_operatordata($bf, $preferences, $brainstormid) {
        global $CFG;

        $status = true;

        $data = get_records('brainstorm_operatordata', 'brainstormid', $brainstormid);

        $status = $status && fwrite ($bf, start_tag('DATA', 4, true));
        //Iterate over each datum
        foreach ($data as $datum) {
            //Start data
            $status = $status && fwrite ($bf, start_tag('DATUM', 5, true));
            //Print operator datum
            fwrite ($bf, full_tag('ID', 6, false, $datum->id));
            fwrite ($bf, full_tag('BRAINSTORMID', 6, false, $datum->brainstormid));  
            fwrite ($bf, full_tag('USERID', 6, false, $datum->userid));
            fwrite ($bf, full_tag('GROUPID', 6, false, $datum->groupid));
            fwrite ($bf, full_tag('OPERATORID', 6, false, $datum->operatorid));  
            fwrite ($bf, full_tag('ITEMSOURCE', 6, false, $datum->itemsource));  
            fwrite ($bf, full_tag('ITEMDEST', 6, false, $datum->itemdest));  
            fwrite ($bf, full_tag('INTVALUE', 6, false, $datum->intvalue));  
            fwrite ($bf, full_tag('FLOATVALUE', 6, false, $datum->floatvalue));  
            fwrite ($bf, full_tag('BLOBVALUE', 6, false, $datum->blobvalue)); 
            fwrite ($bf, full_tag('TIMEMODIFIED', 6, false, $datum->timemodified));
            //End datum
            $status = $status && fwrite ($bf, end_tag('DATUM', 5, true));
        }
        //Write end tag
        $status = $status && fwrite($bf, end_tag('DATA', 4, true));

        return $status;
    }

    //Backup brainstorm user specific data (executed from brainstorm_backup_mods)
    function backup_brainstorm_userdata($bf, $preferences, $brainstormid) {
        global $CFG;

        $status = true;

        $userdata = get_records('brainstorm_userdata', 'brainstormid', $brainstormid);

        $status = $status && fwrite ($bf, start_tag('USERDATA', 4, true));
        //Iterate over each datum
        foreach ($userdata as $datum) {
            //Start userdata
            $status = $status && fwrite ($bf, start_tag('DATUM', 5, true));
            //Print user datum
            fwrite ($bf, full_tag('ID', 6, false, $datum->id));
            fwrite ($bf, full_tag('BRAINSTORMID', 6, false, $datum->brainstormid));  
            fwrite ($bf, full_tag('USERID', 6, false, $datum->userid));
            fwrite ($bf, full_tag('REPORT', 6, false, $datum->report));
            fwrite ($bf, full_tag('REPORTFORMAT', 6, false, $datum->reportformat));  
            fwrite ($bf, full_tag('FEEDBACK', 6, false, $datum->feedback));  
            fwrite ($bf, full_tag('FEEDBACKFORMAT', 6, false, $datum->feedbackformat));  
            fwrite ($bf, full_tag('TIMEUPDATED', 6, false, $datum->timeupdated));
            //End datum
            $status = $status && fwrite ($bf, end_tag('DATUM', 5, true));
        }
        //Write end tag
        $status = $status && fwrite($bf, end_tag('USERDATA', 4, true));

        return $status;
    }

    //Backup brainstorm grading data (executed from brainstorm_backup_mods)
    function backup_brainstorm_grades($bf, $preferences, $brainstormid) {
        global $CFG;

        $status = true;

        $grades = get_records('brainstorm_grades', 'brainstormid', $brainstormid);

        $status = $status && fwrite ($bf, start_tag('GRADES', 4, true));
        //Iterate over each grade
        foreach ($grades as $grade) {
            //Start grade
            $status = $status && fwrite ($bf, start_tag('GRADE', 5, true));
            //Print grade
            fwrite ($bf, full_tag('ID', 6, false, $grade->id));
            fwrite ($bf, full_tag('BRAINSTORMID', 6, false, $grade->brainstormid));  
            fwrite ($bf, full_tag('USERID', 6, false, $grade->userid));
            fwrite ($bf, full_tag('GRADE', 6, false, $grade->grade));
            fwrite ($bf, full_tag('GRADEITEM', 6, false, $grade->gradeitem));  
            fwrite ($bf, full_tag('TIMEUPDATED', 6, false, $grade->timeupdated));
            //End grade
            $status = $status && fwrite ($bf, end_tag('GRADE', 5, true));
        }
        //Write end tag
        $status = $status && fwrite($bf, end_tag('GRADES', 4, true));

        return $status;
    }
 
   ////Return an array of info (name, value)
   function brainstorm_check_backup_mods($course, $user_data=false, $backup_unique_code) {
        //First the course data
        $info[0][0] = get_string('modulenameplural', 'brainstorm');
        if ($ids = brainstorm_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string('operators', 'brainstorm');
            if ($ids = brainstorm_operators_ids_by_course ($course)) {
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
            $info[2][0] = get_string('categories', 'brainstorm');
            if ($ids = brainstorm_categories_ids_by_course($course)) {
                $info[2][1] = count($ids);
            } else {
                $info[2][1] = 0;
            }
            $info[3][0] = get_string('responses', 'brainstorm');
            if ($ids = brainstorm_responses_ids_by_course($course)) {
                $info[3][1] = count($ids);
            } else {
                $info[3][1] = 0;
            }
            $info[4][0] = get_string('data', 'brainstorm');
            if ($ids = brainstorm_operatordata_ids_by_course($course)) {
                $info[4][1] = count($ids);
            } else {
                $info[4][1] = 0;
            }
            $info[5][0] = get_string('userdata', 'brainstorm');
            if ($ids = brainstorm_userdata_ids_by_course($course)) {
                $info[5][1] = count($ids);
            } else {
                $info[5][1] = 0;
            }
            $info[6][0] = get_string('grades', 'brainstorm');
            if ($ids = brainstorm_grades_ids_by_course($course)) {
                $info[6][1] = count($ids);
            } else {
                $info[6][1] = 0;
            }
        }
        return $info;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of brainstorms id
    function brainstorm_ids ($course) {
        global $CFG;

        $sql = "
            SELECT 
                b.id, 
                b.course
            FROM 
                {$CFG->prefix}brainstorm AS b
            WHERE 
                b.course = '{$course}'
        ";
        return get_records_sql ($sql);
    }
   
    //Returns an array of brainstorm operators id
    function brainstorm_operators_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                op.id , 
                op.brainstormid
            FROM 
                {$CFG->prefix}brainstorm_operators AS op, 
                {$CFG->prefix}brainstorm AS b
            WHERE 
                b.course = '{$course}' AND
                op.brainstormid = b.id
        ";
        return get_records_sql($sql);
    }

    //Returns an array of brainstorm categories id
    function brainstorm_categories_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                c.id,
                c.brainstormid
            FROM 
                {$CFG->prefix}brainstorm_categories AS c, 
                {$CFG->prefix}brainstorm AS b
            WHERE 
                b.course = '{$course}' AND
                c.brainstormid = b.id
        ";
        return get_records_sql($sql);
    }

    //Returns an array of brainstorm responses id
    function brainstorm_responses_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                r.id,
                r.brainstormid
            FROM 
                {$CFG->prefix}brainstorm_responses AS r, 
                {$CFG->prefix}brainstorm AS b
            WHERE 
                b.course = '{$course}' AND
                r.brainstormid = b.id
        ";
        return get_records_sql($sql);
    }

    //Returns an array of operatordata responses id
    function brainstorm_operatordata_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                opd.id,
                opd.brainstormid
            FROM 
                {$CFG->prefix}brainstorm_operatordata AS opd, 
                {$CFG->prefix}brainstorm AS b
            WHERE 
                b.course = '{$course}' AND
                opd.brainstormid = b.id
        ";
        return get_records_sql($sql);
    }

    //Returns an array of userdata id
    function brainstorm_userdata_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                ud.id,
                ud.brainstormid
            FROM 
                {$CFG->prefix}brainstorm_userdata AS ud, 
                {$CFG->prefix}brainstorm AS b
            WHERE 
                b.course = '{$course}' AND
                ud.brainstormid = b.id
        ";
        return get_records_sql($sql);
    }

    //Returns an array of grades id
    function brainstorm_grades_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                g.id,
                g.brainstormid
            FROM 
                {$CFG->prefix}brainstorm_grades AS g, 
                {$CFG->prefix}brainstorm AS b
            WHERE 
                b.course = '{$course}' AND
                g.brainstormid = b.id
        ";
        return get_records_sql($sql);
    }
?>

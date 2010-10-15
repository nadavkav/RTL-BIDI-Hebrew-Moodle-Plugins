<?php //$Id: backuplib.php,v 1.3 2008/02/06 08:07:36 jamiesensei Exp $
    //This php script contains all the stuff to backup
    //qcreate mods

    //This is the "graphical" structure of the qcreate mod:
    //
    //                             qcreate
    //                            (CL,pk->id)             
    //                                 |
    //                                 |
    //         ---------------------------------------------------        
    //         |                                                 |
    //    qcreate_grades                                 qcreate_required
    //(UL,pk->id, fk->qcreateid)                      (CL,pk->id, fk->qcreateid)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          CL->course level info
    //          UL->user level info
    //
    //-----------------------------------------------------------

    //This function executes all the backup procedure about this mod
    function qcreate_backup_mods($bf,$preferences) {

        global $CFG;

        $status = true;

        //Iterate over qcreate table
        $qcreates = get_records("qcreate","course",$preferences->backup_course,"id");
        if ($qcreates) {
            foreach ($qcreates as $qcreate) {
                if (backup_mod_selected($preferences,'qcreate',$qcreate->id)) {
                    $status =  $status &&  qcreate_backup_one_mod($bf,$preferences,$qcreate);
                    // backup files happens in backup_one_mod now too.
                }
            }
        }
        return $status;  
    }

    function qcreate_backup_one_mod($bf,$preferences,$qcreate) {
        if (is_numeric($qcreate)) {
            $qcreate = get_record('qcreate','id',$qcreate);
        }
    
        $status = true;

        //Start mod
        fwrite($bf,start_tag("MOD",3,true));
        //Print qcreate data
        fwrite($bf,full_tag("ID",4,false,$qcreate->id));
        fwrite($bf,full_tag("MODTYPE",4,false, 'qcreate'));
        fwrite($bf,full_tag("NAME",4,false,$qcreate->name));
        fwrite($bf,full_tag("GRADE",4,false,$qcreate->grade));
        fwrite($bf,full_tag("GRADERATIO",4,false,$qcreate->graderatio));
        fwrite($bf,full_tag("INTRO",4,false,$qcreate->intro));
        fwrite($bf,full_tag("INTROFORMAT",4,false,$qcreate->introformat));
        fwrite($bf,full_tag("ALLOWED",4,false,$qcreate->allowed));
        fwrite($bf,full_tag("TOTALREQUIRED",4,false,$qcreate->totalrequired));
        fwrite($bf,full_tag("STUDENTQACCESS",4,false,$qcreate->studentqaccess));
        fwrite($bf,full_tag("TIMEOPEN",4,false,$qcreate->timeopen));
        fwrite($bf,full_tag("TIMECLOSE",4,false,$qcreate->timeclose));
        fwrite($bf,full_tag("TIMECREATED",4,false,$qcreate->timecreated));
        fwrite($bf,full_tag("TIMEMODIFIED",4,false,$qcreate->timemodified));
        $status =  $status && backup_qcreate_requireds($bf,$preferences,$qcreate->id);
        //if we've selected to backup users info, then execute backup_qcreate_grades
        if (backup_userdata_selected($preferences,'qcreate',$qcreate->id)) {
            $status =  $status &&  backup_qcreate_grades($bf,$preferences,$qcreate->id);
        }
        //End mod
        $status =  $status &&  fwrite($bf,end_tag("MOD",3,true));

        return $status;
    }

    //Backup qcreate_grades contents (executed from qcreate_backup_mods)
    function backup_qcreate_grades($bf,$preferences,$qcreate) {
        $status = true;

        $qcreate_grades = get_records("qcreate_grades","qcreateid",$qcreate,"id");
        //If there is grades
        if ($qcreate_grades) {
            //Write start tag
            $status =  $status && fwrite($bf,start_tag("GRADES",4,true));
            //Iterate over each grade
            foreach ($qcreate_grades as $qcreate_grade) {
                //Start grade
                $status =  $status && fwrite($bf,start_tag("GRADE",5,true));
                //Print grade contents
                fwrite($bf,full_tag("ID",6,false,$qcreate_grade->id));       
                fwrite($bf,full_tag("QUESTIONID",6,false,$qcreate_grade->questionid));       
                fwrite($bf,full_tag("GRADE",6,false,$qcreate_grade->grade));       
                fwrite($bf,full_tag("GRADECOMMENT",6,false,$qcreate_grade->gradecomment));       
                fwrite($bf,full_tag("TEACHER",6,false,$qcreate_grade->teacher));       
                fwrite($bf,full_tag("TIMEMARKED",6,false,$qcreate_grade->timemarked));       
                //End grade
                $status =  $status && fwrite($bf,end_tag("GRADE",5,true));
            }
            //Write end tag
            $status =  $status && fwrite($bf,end_tag("GRADES",4,true));
        }
        return $status;
    }

    //Backup qcreate_requireds contents (executed from qcreate_backup_mods)
    function backup_qcreate_requireds($bf,$preferences,$qcreate) {
        $status = true;

        $qcreate_requireds = get_records("qcreate_required", "qcreateid", $qcreate, "id");
        //If there is requireds
        if ($qcreate_requireds) {
            //Write start tag
            $status =  $status && fwrite($bf,start_tag("REQUIREDS",4,true));
            //Iterate over each required
            foreach ($qcreate_requireds as $qcreate_required) {
                //Start required
                $status =  $status && fwrite($bf, start_tag("REQUIRED", 5,true));
                //Print required contents
                fwrite($bf, full_tag("ID", 6,false, $qcreate_required->id));       
                fwrite($bf, full_tag("QTYPE", 6,false, $qcreate_required->qtype));       
                fwrite($bf, full_tag("NO", 6,false, $qcreate_required->no));       
                //End required
                $status =  $status && fwrite($bf, end_tag("REQUIRED", 5,true));
            }
            //Write end tag
            $status =  $status && fwrite($bf, end_tag("REQUIREDS", 4,true));
        }
        return $status;
    }
    
    //Return an array of info (name, value)
    function qcreate_check_backup_mods($course, $user_data=false, $backup_unique_code, $instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += qcreate_check_backup_mods_instances($instance, $backup_unique_code);
            }
            return $info;
        }
        //First the course data
        $info[0][0] = get_string("modulenameplural", "qcreate");
        if ($ids = qcreate_ids($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string("grades");
            if ($ids = qcreate_grade_ids_by_course($course)) { 
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
        }
        return $info;
    }

    //Return an array of info (name, value)
    function qcreate_check_backup_mods_instances($instance, $backup_unique_code) {
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
        if (!empty($instance->userdata)) {
            // in this module question categories and questions are user data.
            //Categories
            $info[$instance->id.'1'][0] = get_string("categories","quiz");
            if ($catids = qcreate_category_ids_by_instance ($instance->id, $backup_unique_code)) {
                $info[$instance->id.'1'][1] = count($catids);
            } else {
                $info[$instance->id.'1'][1] = 0;
            }
            //Questions
            $info[$instance->id.'2'][0] = get_string("questionsinclhidden","quiz");
            if ($ids = qcreate_question_ids_in_cats ($catids, $backup_unique_code)) {
                $info[$instance->id.'2'][1] = count($ids);
            } else {
                $info[$instance->id.'2'][1] = 0;
            }
            $info[$instance->id.'3'][0] = get_string("grades");
            if ($ids = qcreate_grade_ids_by_instance($instance->id)) {
                $info[$instance->id.'3'][1] = count($ids);
            } else {
                $info[$instance->id.'3'][1] = 0;
            }
        }
        return $info;
    }
    
    function qcreate_category_ids_by_instance ($instanceid, $backup_unique_code){
        
        $cm = get_coursemodule_from_instance('qcreate', $instanceid);
        $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
        $cats = get_records('question_categories', 'contextid', $modcontext->id, '', 'id, contextid');
        if ($cats){
            foreach ($cats as $cat){
                backup_putid($backup_unique_code, 'question_categories', $cat->id, 0);
            }
            return array_keys($cats);
        } else {
            return array();
        }
    }
    
    function qcreate_question_ids_in_cats ($catids, $backup_unique_code){
        $qs = get_records_select('question', 'category IN ('.join($catids, ', ').')', '', 'id, 0');
        if ($qs){
            foreach (array_keys($qs) as $q){
                backup_putid($backup_unique_code, 'question', $q, 0);
            }
            return array_keys($qs);
        } else {
            return array();
        }
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function qcreate_encode_content_links($content, $preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        //Link to the list of qcreates
        $buscar="/(".$base."\/mod\/qcreate\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar, '$@QCREATEINDEX*$2@$', $content);

        //Link to qcreate view by moduleid
        $buscar="/(".$base."\/mod\/qcreate\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar, '$@QCREATEVIEWBYID*$2@$', $result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of qcreates id 
    function qcreate_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT qc.id, qc.course
                                 FROM {$CFG->prefix}qcreate qc
                                 WHERE qc.course = '$course'");
    }
    
    //Returns an array of qcreate_grades id
    //only returns grades where question record exists.
    function qcreate_grade_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT g.id , g.qcreateid
                                 FROM {$CFG->prefix}qcreate_grades g,
                                      {$CFG->prefix}question q,
                                      {$CFG->prefix}qcreate qc
                                 WHERE qc.course = '$course' AND
                                       g.qcreateid = qc.id AND " .
                                      "q.id = g.questionid");
    }

    //Returns an array of qcreate_grades id
    //only returns grades where question record exists.
    function qcreate_grade_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT g.id , g.qcreateid
                                 FROM {$CFG->prefix}qcreate_grades g,
                                      {$CFG->prefix}question q
                                 WHERE g.qcreateid = $instanceid AND " .
                                      "q.id = g.questionid");
    }
?>

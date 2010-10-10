<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez 
 * @version $Id: backuplib.php,v 2.0 2009/25/04 
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

    //This function executes all the backup procedure about this mod
    function webquestscorm_backup_mods($bf,$preferences) {

        global $CFG;

        $status = true;

        //Iterate over webquestscorm table
        $webquestscorms = get_records ("webquestscorm","course",$preferences->backup_course,"id");
        if ($webquestscorms) {
            foreach ($webquestscorms as $webquestscorm) {
                if (backup_mod_selected($preferences,'webquestscorm',$webquestscorm->id)) {
                    $status = webquestscorm_backup_one_mod($bf,$preferences,$webquestscorm);
                    // backup files happens in backup_one_mod now too.
                }
            }
        }
        return $status;  
    }

    function webquestscorm_backup_one_mod($bf,$preferences,$webquestscorm) {
        
        global $CFG;
    
        if (is_numeric($webquestscorm)) {
            $webquestscorm = get_record('webquestscorm','id',$webquestscorm);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print webquestscorm data
        fwrite ($bf,full_tag("ID",4,false,$webquestscorm->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"webquestscorm"));
        fwrite ($bf,full_tag("NAME",4,false,$webquestscorm->name));
        fwrite ($bf,full_tag("GRADE",4,false,$webquestscorm->grade));
        fwrite ($bf,full_tag("TIMEAVAILABLE",4,false,$webquestscorm->timeavailable));
        fwrite ($bf,full_tag("TIMEDUE",4,false,$webquestscorm->timedue));
        fwrite ($bf,full_tag("DUEENABLE",4,false,$webquestscorm->dueenable));
        fwrite ($bf,full_tag("DUEYEAR",4,false,$webquestscorm->dueyear));
        fwrite ($bf,full_tag("DUEMONTH",4,false,$webquestscorm->duemonth));
        fwrite ($bf,full_tag("DUEDAY",4,false,$webquestscorm->dueday));
        fwrite ($bf,full_tag("DUEHOUR",4,false,$webquestscorm->duehour));
        fwrite ($bf,full_tag("DUEMINUTE",4,false,$webquestscorm->dueminute));
        fwrite ($bf,full_tag("AVAILABLEENABLE",4,false,$webquestscorm->availableenable));
        fwrite ($bf,full_tag("AVAILABLEYEAR",4,false,$webquestscorm->availableyear));
        fwrite ($bf,full_tag("AVAILABLEMONTH",4,false,$webquestscorm->availablemonth));
        fwrite ($bf,full_tag("AVAILABLEDAY",4,false,$webquestscorm->availableday));
        fwrite ($bf,full_tag("AVAILABLEHOUR",4,false,$webquestscorm->availablehour));
        fwrite ($bf,full_tag("AVAILABLEMINUTE",4,false,$webquestscorm->availableminute));
        fwrite ($bf,full_tag("PREVENTLATE",4,false,$webquestscorm->preventlate));
        fwrite ($bf,full_tag("MAXBYTES",4,false,$webquestscorm->maxbytes));
        fwrite ($bf,full_tag("RESUBMIT",4,false,$webquestscorm->resubmit));
        fwrite ($bf,full_tag("EMAILTEACHERS",4,false,$webquestscorm->emailteachers));
        fwrite ($bf,full_tag("TEMPLATE",4,false,$webquestscorm->template));
        fwrite ($bf,full_tag("INTRODUCTION",4,false,$webquestscorm->introduction));
        fwrite ($bf,full_tag("TASK",4,false,$webquestscorm->task));
        fwrite ($bf,full_tag("PROCESS",4,false,$webquestscorm->process));
        fwrite ($bf,full_tag("EVALUATION",4,false,$webquestscorm->evaluation));
        fwrite ($bf,full_tag("CONCLUSION",4,false,$webquestscorm->conclusion));
        fwrite ($bf,full_tag("CREDITS",4,false,$webquestscorm->credits));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$webquestscorm->timemodified));
        //if we've selected to backup users info, then execute backup_webquestscorm_submisions and
        //backup_webquestscorm_files_instance
        if (backup_userdata_selected($preferences,'webquestscorm',$webquestscorm->id)) {
            $status = backup_webquestscorm_submissions($bf,$preferences,$webquestscorm->id);
            if ($status) {
                $status = backup_webquestscorm_files_instance($bf,$preferences,$webquestscorm->id);
            }
        }
        //End mod
        $status =fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

    //Backup webquestscorm_submissions contents (executed from webquestscorm_backup_mods)
    function backup_webquestscorm_submissions ($bf,$preferences,$webquestscorm) {

        global $CFG;

        $status = true;

        $webquestscorm_submissions = get_records("webquestscorm_submissions","webquestscorm",$webquestscorm,"id");
        //If there is submissions
        if ($webquestscorm_submissions) {
            //Write start tag
            $status =fwrite ($bf,start_tag("SUBMISSIONS",4,true));
            //Iterate over each submission
            foreach ($webquestscorm_submissions as $ass_sub) {
                //Start submission
                $status =fwrite ($bf,start_tag("SUBMISSION",5,true));
                //Print submission contents
                fwrite ($bf,full_tag("ID",6,false,$ass_sub->id));       
                fwrite ($bf,full_tag("USERID",6,false,$ass_sub->userid));       
                fwrite ($bf,full_tag("TIMECREATED",6,false,$ass_sub->timecreated));       
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$ass_sub->timemodified));       
                fwrite ($bf,full_tag("NUMFILES",6,false,$ass_sub->numfiles));       
                fwrite ($bf,full_tag("DATA1",6,false,$ass_sub->data1));       
                fwrite ($bf,full_tag("DATA2",6,false,$ass_sub->data2));       
                fwrite ($bf,full_tag("GRADE",6,false,$ass_sub->grade));       
                fwrite ($bf,full_tag("SUBMISSIONCOMMENT",6,false,$ass_sub->submissioncomment));       
                fwrite ($bf,full_tag("FORMAT",6,false,$ass_sub->format));       
                fwrite ($bf,full_tag("TEACHER",6,false,$ass_sub->teacher));       
                fwrite ($bf,full_tag("TIMEMARKED",6,false,$ass_sub->timemarked));       
                fwrite ($bf,full_tag("MAILED",6,false,$ass_sub->mailed));       
                //End submission
                $status =fwrite ($bf,end_tag("SUBMISSION",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("SUBMISSIONS",4,true));
        }
        return $status;
    }

    //Backup webquestscorm files because we've selected to backup user info
    //and files are user info's level
    function backup_webquestscorm_files($bf,$preferences) {

        global $CFG;
       
        $status = true;

        //First we check to moddata exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = check_and_create_moddata_dir($preferences->backup_unique_code);
        //Now copy the webquestscorm dir
        if ($status) {
            //Only if it exists !! Thanks to Daniel Miksik.
            if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/webquestscorm")) {
                $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/webquestscorm",
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/webquestscorm");
            }
        }

        return $status;

    } 

    function backup_webquestscorm_files_instance($bf,$preferences,$instanceid) {

        global $CFG;
       
        $status = true;

        //First we check to moddata exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = check_and_create_moddata_dir($preferences->backup_unique_code);
        $status = check_dir_exists($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/webquestscorm/",true);
        //Now copy the webquestscorm dir
        if ($status) {
            //Only if it exists !! Thanks to Daniel Miksik.
            if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/webquestscorm/".$instanceid)) {
                $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/webquestscorm/".$instanceid,
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/webquestscorm/".$instanceid);
            }
        }

        return $status;

    } 

    //Return an array of info (name,value)
    function webquestscorm_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += webquestscorm_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        //First the course data
        $info[0][0] = get_string("modulenameplural","webquestscorm");
        if ($ids = webquestscorm_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string("submissions","webquestscorm");
            if ($ids = webquestscorm_submission_ids_by_course ($course)) { 
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
        }
        return $info;
    }

    //Return an array of info (name,value)
    function webquestscorm_check_backup_mods_instances($instance,$backup_unique_code) {
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
        if (!empty($instance->userdata)) {
            $info[$instance->id.'1'][0] = get_string("submissions","webquestscorm");
            if ($ids = webquestscorm_submission_ids_by_instance ($instance->id)) {
                $info[$instance->id.'1'][1] = count($ids);
            } else {
                $info[$instance->id.'1'][1] = 0;
            }
        }
        return $info;
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function webquestscorm_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of webquestscorms
        $buscar="/(".$base."\/mod\/webquestscorm\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@WEBQUESTSCORMINDEX*$2@$',$content);

        //Link to webquestscorm view by moduleid
        $buscar="/(".$base."\/mod\/webquestscorm\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@WEBQUESTSCORMVIEWBYID*$2@$',$result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of webquestscorms id 
    function webquestscorm_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}webquestscorm a
                                 WHERE a.course = '$course'");
    }
    
    //Returns an array of webquestscorm_submissions id
    function webquestscorm_submission_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT s.id , s.webquestscorm
                                 FROM {$CFG->prefix}webquestscorm_submissions s,
                                      {$CFG->prefix}webquestscorm a
                                 WHERE a.course = '$course' AND
                                       s.webquestscorm = a.id");
    }

    //Returns an array of webquestscorm_submissions id
    function webquestscorm_submission_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT s.id , s.webquestscorm
                                 FROM {$CFG->prefix}webquestscorm_submissions s
                                 WHERE s.webquestscorm = $instanceid");
    }
?>

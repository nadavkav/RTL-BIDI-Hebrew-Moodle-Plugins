<?php //$Id: backuplib.php,v 4 2010/04/22 00:00:00 gibson Exp $
    //This php script contains all the stuff to backup/restore
    //nanogong mods

    //This is the "graphical" structure of the nanogong mod:
    //
    //                      nanogong
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

    //This function executes all the backup procedure about this mod
    function nanogong_backup_mods($bf,$preferences) {

        global $CFG;

        $status = true;

        //Iterate over nanogong table
        $nanogongs = get_records ("nanogong","course",$preferences->backup_course,"id");
        if ($nanogongs) {
            foreach ($nanogongs as $nanogong) {
                if (backup_mod_selected($preferences,'nanogong',$nanogong->id)) {
                    $status = nanogong_backup_one_mod($bf,$preferences,$nanogong);
                    // backup files happens in backup_one_mod now too.
                }
            }
        }
        return $status;  
    }

    function nanogong_backup_one_mod($bf,$preferences,$nanogong) {
        
        global $CFG;
    
        if (is_numeric($nanogong)) {
            $nanogong = get_record('nanogong','id',$nanogong);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print nanogong data
        fwrite ($bf,full_tag("ID",4,false,$nanogong->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"nanogong"));
        fwrite ($bf,full_tag("NAME",4,false,$nanogong->name));
        fwrite ($bf,full_tag("MESSAGE",4,false,$nanogong->message));
        fwrite ($bf,full_tag("COLOR",4,false,$nanogong->color));
        fwrite ($bf,full_tag("MAXDURATION",4,false,$nanogong->maxduration));
        fwrite ($bf,full_tag("MAXMESSAGES",4,false,$nanogong->maxmessages));
        fwrite ($bf,full_tag("MAXSCORE",4,false,$nanogong->maxscore));
        fwrite ($bf,full_tag("ALLOWGUESTACCESS",4,false,$nanogong->allowguestaccess));
        fwrite ($bf,full_tag("TIMECREATED",4,false,$nanogong->timecreated));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$nanogong->timemodified));
        //if we've selected to backup users info, then execute backup_nanogong_messages and
        //backup_nanogong_files_instance
        if (backup_userdata_selected($preferences,'nanogong',$nanogong->id)) {
            $status = backup_nanogong_message($bf,$preferences,$nanogong->id);
        }
        //End mod
        $status =fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

    //Backup nanogong_message contents (executed from nanogong_backup_mods)
    function backup_nanogong_message ($bf,$preferences,$nanogong) {

        global $CFG;

        $status = true;

        //First we check to moddata exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = check_and_create_moddata_dir($preferences->backup_unique_code);
        $status = check_dir_exists($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/nanogong/",true);

        $nanogong_message = get_records("nanogong_message","nanogongid",$nanogong,"id");
        //If there is a message
        if ($nanogong_message) {
            //Write start tag
            $status =fwrite ($bf,start_tag("MESSAGES",4,true));
            //Iterate over each message
            foreach ($nanogong_message as $message) {
                //Sound filename
                $fn = array_pop(explode('/', $message->path));

                //Start message
                $status =fwrite ($bf,start_tag("MESSAGE",5,true));
                //Print message data
                fwrite ($bf,full_tag("ID",6,false,$message->id));       
                fwrite ($bf,full_tag("USERID",6,false,$message->userid));       
                fwrite ($bf,full_tag("GROUPID",6,false,$message->groupid));       
                fwrite ($bf,full_tag("TITLE",6,false,$message->title));       
                fwrite ($bf,full_tag("MESSAGE",6,false,$message->message));       
                fwrite ($bf,full_tag("PATH",6,false,$fn));       
                fwrite ($bf,full_tag("COMMENTS",6,false,$message->comments));       
                fwrite ($bf,full_tag("COMMENTEDBY",6,false,$message->commentedby));       
                fwrite ($bf,full_tag("SCORE",6,false,$message->score));       
                fwrite ($bf,full_tag("TIMESTAMP",6,false,$message->timestamp));       
                fwrite ($bf,full_tag("TIMEEDITED",6,false,$message->timeedited));       
                fwrite ($bf,full_tag("LOCKED",6,false,$message->locked));       
                //End submission
                $status =fwrite ($bf,end_tag("MESSAGE",5,true));

                //Copy the file
                if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/nanogong/".$message->userid)) {
                    $status = check_dir_exists($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/nanogong/".$message->userid,true);
                    $status = backup_copy_file($CFG->dataroot.$message->path,
                                               $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/nanogong/".$message->userid."/".$fn);
                }
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("MESSAGES",4,true));
        }
        return $status;
    }

    //Return an array of info (name,value)
    function nanogong_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += nanogong_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        //First the course data
        $info[0][0] = get_string("modulenameplural","nanogong");
        if ($ids = nanogong_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string("message","nanogong");
            if ($ids = nanogong_message_ids_by_course ($course)) { 
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
        }
        return $info;
    }

    //Return an array of info (name,value)
    function nanogong_check_backup_mods_instances($instance,$backup_unique_code) {
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
        if (!empty($instance->userdata)) {
            $info[$instance->id.'1'][0] = get_string("message","nanogong");
            if ($ids = nanogong_message_ids_by_instance ($instance->id)) {
                $info[$instance->id.'1'][1] = count($ids);
            } else {
                $info[$instance->id.'1'][1] = 0;
            }
        }
        return $info;
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function nanogong_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of nanogongs
        $buscar="/(".$base."\/mod\/nanogong\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@NANOGONGINDEX*$2@$',$content);

        //Link to nanogong view by moduleid
        $buscar="/(".$base."\/mod\/nanogong\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@NANOGONGVIEWBYID*$2@$',$result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of nanogongs id 
    function nanogong_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT n.id, n.course
                                 FROM {$CFG->prefix}nanogong n
                                 WHERE n.course = '$course'");
    }
    
    //Returns an array of nanogong_message id
    function nanogong_message_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT m.id , m.nanogongid
                                 FROM {$CFG->prefix}nanogong_message m,
                                      {$CFG->prefix}nanogong n
                                 WHERE n.course = '$course' AND
                                       m.nanogongid = n.id");
    }

    //Returns an array of nanogong_message id
    function nanogong_message_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT m.id , m.nanogongid
                                 FROM {$CFG->prefix}nanogong_message m
                                 WHERE m.nanogongid = $instanceid");
    }
?>

<?php 
	//$Id: backuplib.php,v 1.5 2006/01/13 03:45:29 mjollnir_ Exp $
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

    //This function executes all the backup procedure about this mod
    function econsole_backup_mods($bf,$preferences) {

        global $CFG;

        $status = true;

        //Iterate over econsole table
        $econsoles = get_records ("econsole","course",$preferences->backup_course,"id");
        if ($econsoles) {
            foreach ($econsoles as $econsole) {
                if (backup_mod_selected($preferences,'econsole',$econsole->id)) {
                    $status = econsole_backup_one_mod($bf,$preferences,$econsole);
                }
            }
        }
        return $status;  
    }

    function econsole_backup_one_mod($bf,$preferences,$econsole) {

        global $CFG;
    
        if (is_numeric($econsole)) {
            $econsole = get_record('econsole','id',$econsole);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print econsole data
        fwrite ($bf,full_tag("ID",4,false,$econsole->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"econsole"));
        fwrite ($bf,full_tag("NAME",4,false,$econsole->name));
        fwrite ($bf,full_tag("CONTENT",4,false,$econsole->content));
        fwrite ($bf,full_tag("UNITSTRING",4,false,$econsole->unitstring));
        fwrite ($bf,full_tag("SHOWUNIT",4,false,$econsole->showunit));
        fwrite ($bf,full_tag("LESSONSTRING",4,false,$econsole->lessonstring));
        fwrite ($bf,full_tag("SHOWLESSON",4,false,$econsole->showlesson));
        fwrite ($bf,full_tag("URL1NAME",4,false,$econsole->url1name));
        fwrite ($bf,full_tag("URL1",4,false,$econsole->url1));
        fwrite ($bf,full_tag("URL2NAME",4,false,$econsole->url2name));
        fwrite ($bf,full_tag("URL2",4,false,$econsole->url2));		
        fwrite ($bf,full_tag("URL3NAME",4,false,$econsole->url3name));
        fwrite ($bf,full_tag("URL3",4,false,$econsole->url3));
        fwrite ($bf,full_tag("URL4NAME",4,false,$econsole->url4name));
        fwrite ($bf,full_tag("URL4",4,false,$econsole->url4));
        fwrite ($bf,full_tag("URL5NAME",4,false,$econsole->url5name));
        fwrite ($bf,full_tag("URL5",4,false,$econsole->url5));
        fwrite ($bf,full_tag("URL6NAME",4,false,$econsole->url6name));
        fwrite ($bf,full_tag("URL6",4,false,$econsole->url6));				
        fwrite ($bf,full_tag("GLOSSARY",4,false,$econsole->glossary));		
        fwrite ($bf,full_tag("JOURNAL",4,false,$econsole->journal));		
        fwrite ($bf,full_tag("FORUM",4,false,$econsole->forum));		
        fwrite ($bf,full_tag("CHAT",4,false,$econsole->chat));		
        fwrite ($bf,full_tag("CHOICE",4,false,$econsole->choice));		
        fwrite ($bf,full_tag("QUIZ",4,false,$econsole->quiz));		
        fwrite ($bf,full_tag("ASSIGNMENT",4,false,$econsole->assignment));		
        fwrite ($bf,full_tag("WIKI",4,false,$econsole->wiki));
        fwrite ($bf,full_tag("THEME",4,false,$econsole->theme));	
        fwrite ($bf,full_tag("IMAGEBARTOP",4,false,$econsole->imagebartop));
        fwrite ($bf,full_tag("IMAGEBARBOTTOM",4,false,$econsole->imagebarbottom));						
        fwrite ($bf,full_tag("TIMECREATED",4,false,$econsole->timecreated));		
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$econsole->timemodified));
        //if we've selected to backup users info, then execute backup_econsole_messages
/*		
        if (backup_userdata_selected($preferences,'econsole',$econsole->id)) {
            $status = backup_econsole_messages($bf,$preferences,$econsole->id);
        }		
*/        //End mod
        $status =fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

    //Backup econsole_messages contents (executed from econsole_backup_mods)
/*    function backup_econsole_messages ($bf,$preferences,$econsole) {

        global $CFG;

        $status = true;

        $econsole_messages = get_records("econsole_messages","econsoleid",$econsole,"id");
        //If there is messages
        if ($econsole_messages) {
            //Write start tag
            $status =fwrite ($bf,start_tag("MESSAGES",4,true));
            //Iterate over each message
            foreach ($econsole_messages as $cha_mes) {
                //Start message
                $status =fwrite ($bf,start_tag("MESSAGE",5,true));
                //Print message contents
                fwrite ($bf,full_tag("ID",6,false,$cha_mes->id));       
                fwrite ($bf,full_tag("USERID",6,false,$cha_mes->userid));       
                fwrite ($bf,full_tag("GROUPID",6,false,$cha_mes->groupid)); 
                fwrite ($bf,full_tag("SYSTEM",6,false,$cha_mes->system));       
                fwrite ($bf,full_tag("MESSAGE_TEXT",6,false,$cha_mes->message));       
                fwrite ($bf,full_tag("TIMESTAMP",6,false,$cha_mes->timestamp));       
                //End submission
                $status =fwrite ($bf,end_tag("MESSAGE",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("MESSAGES",4,true));
        }
        return $status;
    }
*/
    //Return an array of info (name,value)
    function econsole_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {

        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += econsole_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        //First the course data
        $info[0][0] = get_string("modulenameplural","econsole");
        if ($ids = econsole_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
/*        if ($user_data) {
            $info[1][0] = get_string("messages","econsole");
            if ($ids = econsole_message_ids_by_course ($course)) { 
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
        }
*/        return $info;
    }

    //Return an array of info (name,value)
    function econsole_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';

        //Now, if requested, the user_data
/*        if (!empty($instance->userdata)) {
            $info[$instance->id.'1'][0] = get_string("messages","econsole");
            if ($ids = econsole_message_ids_by_instance ($instance->id)) { 
                $info[$instance->id.'1'][1] = count($ids);
            } else {
                $info[$instance->id.'1'][1] = 0;
            }
        }
*/        return $info;
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function econsole_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of econsoles
        $buscar="/(".$base."\/mod\/econsole\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@CONSOLETRERSINDEX*$2@$',$content);

        //Link to econsole view by moduleid
        $buscar="/(".$base."\/mod\/econsole\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@CONSOLETRERSVIEWBYID*$2@$',$result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of econsoles id 
    function econsole_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT c.id, c.course
                                 FROM {$CFG->prefix}econsole c
                                 WHERE c.course = '$course'");
    }
    
    //Returns an array of assignment_submissions id
/*    function econsole_message_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT m.id , m.econsoleid
                                 FROM {$CFG->prefix}econsole_messages m,
                                      {$CFG->prefix}econsole c
                                 WHERE c.course = '$course' AND
                                       m.econsoleid = c.id");
    }

    //Returns an array of econsole id
    function econsole_message_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT m.id , m.econsoleid
                                 FROM {$CFG->prefix}econsole_messages m
                                 WHERE m.econsoleid = $instanceid");
    }
*/	
?>

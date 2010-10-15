<?php 

    //This function executes all the backup procedure about this mod
    function skype_backup_mods($bf,$preferences) {
        global $CFG;

        $status = true; 

        ////Iterate over skype table
        if ($skypes = get_records ("skype","course", $preferences->backup_course,"id")) {
            foreach ($skypes as $skype) {
                if (backup_mod_selected($preferences,'skype',$skype->id)) {
                    $status = skype_backup_one_mod($bf,$preferences,$skype);
                }
            }
        }
        return $status;
    }
   
    function skype_backup_one_mod($bf,$preferences,$skype) {

        global $CFG;
    
        if (is_numeric($skype)) {
            $skype = get_record('skype','id',$skype);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print assignment data
        fwrite ($bf,full_tag("ID",4,false,$skype->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"skype"));
        fwrite ($bf,full_tag("NAME",4,false,$skype->name));
        fwrite ($bf,full_tag("PARTICIPANTS",4,false,$skype->participants));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$skype->timemodified));
        //End mod
        $status = fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

    ////Return an array of info (name,value)
    function skype_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += skype_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        
         //First the course data
         $info[0][0] = get_string("modulenameplural","skype");
         $info[0][1] = count_records("skype", "course", "$course");
         return $info;
    } 

    ////Return an array of info (name,value)
    function skype_check_backup_mods_instances($instance,$backup_unique_code) {
         //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
        return $info;
    }

?>

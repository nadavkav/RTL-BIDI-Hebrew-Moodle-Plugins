<?php //$Id: backuplib.php,v 1.4 2006/01/13 03:45:30 mjollnir_ Exp $
    //This php script contains all the stuff to backup/restore
    //accord mods

    //This is the "graphical" structure of the accord mod:
    //
    //                       accord
    //                     (CL,pk->id)
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
    function accordion_backup_mods($bf,$preferences) {
        global $CFG;

        $status = true; 

        ////Iterate over accord table
        if ($accordions = get_records ("accordion","course", $preferences->backup_course,"id")) {
            foreach ($accordions as $accordion) {
                if (backup_mod_selected($preferences,'accordion',$accordion->id)) {
                    $status = accord_backup_one_mod($bf,$preferences,$accordion);
                }
            }
        }
        return $status;
    }
   
    function accordion_backup_one_mod($bf,$preferences,$accordion) {

        global $CFG;
    
        if (is_numeric($accordion)) {
            $accordion = get_record('accordion','id',$accordion);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print assignment data
        fwrite ($bf,full_tag("ID",4,false,$accordion->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"accordion"));
	fwrite ($bf,full_tag("NAME",4,false,$accordion->name));
        fwrite ($bf,full_tag("TITLE",4,false,$accordion->title));
        fwrite ($bf,full_tag("CONTENT",4,false,$accordion->content));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$accordion->timemodified));
        //End mod
        $status = fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

    ////Return an array of info (name,value)
    function accordion_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += accordion_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        
         //First the course data
         $info[0][0] = get_string("modulenameplural","accordion");
         $info[0][1] = count_records("accordion", "course", "$course");
         return $info;
    } 

    ////Return an array of info (name,value)
    function accordion_check_backup_mods_instances($instance,$backup_unique_code) {
         //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
        return $info;
    }

?>

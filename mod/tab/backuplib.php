<?php //$Id: backuplib.php,v 1.4 2006/01/13 03:45:30 mjollnir_ Exp $
    //This php script contains all the stuff to backup/restore
    //tab mods

    //This is the "graphical" structure of the tab mod:
    //
    //                       tTAB
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
    function tab_backup_mods($bf,$preferences) {
        global $CFG;

        $status = true; 

        ////Iterate over tab table
        if ($tabs = get_records ("tab","course", $preferences->backup_course,"id")) {
            foreach ($tabs as $tab) {
                if (backup_mod_selected($preferences,'tab',$tab->id)) {
                    $status = tab_backup_one_mod($bf,$preferences,$tab);
                }
            }
        }
        return $status;
    }
   
    function tab_backup_one_mod($bf,$preferences,$tab) {

        global $CFG;
    
        if (is_numeric($tab)) {
            $tab = get_record('tab','id',$tab);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print assignment data
        fwrite ($bf,full_tag("ID",4,false,$tab->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"tab"));
        fwrite ($bf,full_tag("NAME",4,false,$tab->name));
        fwrite ($bf,full_tag("TAB1",4,false,$tab->tab1));
		fwrite ($bf,full_tag("TAB2",4,false,$tab->tab2));
		fwrite ($bf,full_tag("TAB3",4,false,$tab->tab3));
		fwrite ($bf,full_tag("TAB4",4,false,$tab->tab4));
		fwrite ($bf,full_tag("TAB5",4,false,$tab->tab5));
		fwrite ($bf,full_tag("TAB6",4,false,$tab->tab6));
		fwrite ($bf,full_tag("TAB7",4,false,$tab->tab7));
		fwrite ($bf,full_tag("TAB8",4,false,$tab->tab8));
		fwrite ($bf,full_tag("TAB9",4,false,$tab->tab9));
		fwrite ($bf,full_tag("TAB0",4,false,$tab->tab0));
		fwrite ($bf,full_tag("TAB1CONTENT",4,false,$tab->tab1content));
		fwrite ($bf,full_tag("TAB2CONTENT",4,false,$tab->tab2content));
		fwrite ($bf,full_tag("TAB3CONTENT",4,false,$tab->tab3content));
		fwrite ($bf,full_tag("TAB4CONTENT",4,false,$tab->tab4content));
		fwrite ($bf,full_tag("TAB5CONTENT",4,false,$tab->tab5content));
		fwrite ($bf,full_tag("TAB6CONTENT",4,false,$tab->tab6content));
		fwrite ($bf,full_tag("TAB7CONTENT",4,false,$tab->tab7content));
		fwrite ($bf,full_tag("TAB8CONTENT",4,false,$tab->tab8content));
		fwrite ($bf,full_tag("TAB9CONTENT",4,false,$tab->tab9content));
		fwrite ($bf,full_tag("TAB0CONTENT",4,false,$tab->tab0content));
		fwrite ($bf,full_tag("CSS",4,false,$tab->css));
		fwrite ($bf,full_tag("MENUCSS",4,false,$tab->menucss));
		fwrite ($bf,full_tag("DISPLAYMENU",4,false,$tab->displaymenu));
		fwrite ($bf,full_tag("MENUNAME",4,false,$tab->menuname));		
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$tab->timemodified));
        //End mod
        $status = fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

    ////Return an array of info (name,value)
    function tab_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += tab_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        
         //First the course data
         $info[0][0] = get_string("modulenameplural","tab");
         $info[0][1] = count_records("tab", "course", "$course");
         return $info;
    } 

    ////Return an array of info (name,value)
    function tab_check_backup_mods_instances($instance,$backup_unique_code) {
         //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
        return $info;
    }

?>

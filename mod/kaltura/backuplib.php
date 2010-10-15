<?php //$Id: backuplib.php,v 1.7 2007/04/22 22:07:03 stronk7 Exp $
    //This php script contains all the stuff to backup/restore
    //resource mods

    //This is the "graphical" structure of the resource mod:
    //
    //                     resource                                      
    //                 (CL,pk->id,files)
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
    function kaltura_backup_mods($bf,$preferences) {
        global $CFG;

        $status = true; 

        ////Iterate over resource table
//        $resources = get_records ("resource","course",$preferences->backup_course,"id");
        $resources = kaltura_ids($preferences->backup_course);
        if ($resources) {
            foreach ($resources as $resource) {
                if (backup_mod_selected($preferences,'kaltura',$resource->id)) {
                    $status = kaltura_backup_one_mod($bf,$preferences,$resource);
                }
            }
        }
        return $status;
    }
   
    function kaltura_backup_one_mod($bf,$preferences,$resource) {

        global $CFG;
    
        if (is_numeric($resource)) {
            $kaltura_entry = get_record('kaltura_entries','id',$resource);
        }
        else {
          $kaltura_entry = get_record('kaltura_entries','id',$resource->id);
        }
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print assignment data
        fwrite ($bf,full_tag("ID",4,false,$kaltura_entry->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"kaltura"));
        fwrite ($bf,full_tag("ENTRY_ID",4,false,$kaltura_entry->entry_id));
        fwrite ($bf,full_tag("DIMENSIONS",4,false,$kaltura_entry->dimensions));
        fwrite ($bf,full_tag("SIZE",4,false,$kaltura_entry->size));
        fwrite ($bf,full_tag("CUSTOM_WIDTH",4,false,$kaltura_entry->custom_width));
        fwrite ($bf,full_tag("DESIGN",4,false,$kaltura_entry->design));
        fwrite ($bf,full_tag("TITLE",4,false,$kaltura_entry->title));
        fwrite ($bf,full_tag("CONTEXT",4,false,$kaltura_entry->context));
        fwrite ($bf,full_tag("ENTRY_TYPE",4,false,$kaltura_entry->entry_type));
        fwrite ($bf,full_tag("MEDIA_TYPE",4,false,$kaltura_entry->media_type));
        //End mod
        $status = fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

   ////Return an array of info (name,value)
   function kaltura_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
      if (!empty($instances) && is_array($instances) && count($instances)) {
           $info = array();
           foreach ($instances as $id => $instance) {
               $info += kaltura_check_backup_mods_instances($instance,$backup_unique_code);
           }
           return $info;
       }
       //First the course data
       $info[0][0] = get_string("modulenameplural","resource");
       if ($ids = kaltura_ids ($course)) {
           $info[0][1] = count($ids);
       } else {
           $info[0][1] = 0;
       }
       
       return $info;
   }

   ////Return an array of info (name,value)
   function kaltura_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';

        return $info;
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function kaltura_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of resources
        $buscar="/(".$base."\/mod\/resource\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@RESOURCEINDEX*$2@$',$content);

        //Link to resource view by moduleid
        $buscar="/(".$base."\/mod\/resource\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@RESOURCEVIEWBYID*$2@$',$result);

        //Link to resource view by resourceid
        $buscar="/(".$base."\/mod\/resource\/view.php\?r\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@RESOURCEVIEWBYR*$2@$',$result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of resources id
    function kaltura_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.instance id, a.course
                                 FROM {$CFG->prefix}course_modules a JOIN {$CFG->prefix}modules b ON a.module=b.id AND b.name='kaltura'
                                 WHERE a.course = '$course'");
    }
   
?>

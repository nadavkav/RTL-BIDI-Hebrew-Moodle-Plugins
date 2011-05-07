<?php //$Id: backuplib.php,v 1.2 2008/11/12 17:55:51 tedbow Exp $
    //This php script contains all the stuff to backup/restore
    //map mods

    //This is the "graphical" structure of the map mod:
    //
    //                      map
    //                    (CL,pk->id)
    //                        |                
    //                        |                
    //                        |                
    //                  map_locations          
    //             (UL,pk->id, fk->mapid)      
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    function map_backup_mods($bf,$preferences) {
        
        global $CFG;

        $status = true;

        //Iterate over map table
        $maps = get_records ("map","course",$preferences->backup_course,"id");
        if ($maps) {
            foreach ($maps as $map) {
                if (backup_mod_selected($preferences,'map',$map->id)) {
                    $status = map_backup_one_mod($bf,$preferences,$map);
                }
            }
        }
        return $status;
    }

    function map_backup_one_mod($bf,$preferences,$map) {

        global $CFG;
    
        if (is_numeric($map)) {
            $map = get_record('map','id',$map);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print map data
        fwrite ($bf,full_tag("ID",4,false,$map->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"map"));
        fwrite ($bf,full_tag("NAME",4,false,$map->name));
        fwrite ($bf,full_tag("TEXT",4,false,$map->text));
        fwrite ($bf,full_tag("FORMAT",4,false,$map->format));
        fwrite ($bf,full_tag("STUDENTLOCATIONS",4,false,$map->studentlocations));
        fwrite ($bf,full_tag("REQUIREOK",4,false,$map->requireok));
        fwrite ($bf,full_tag("EXTRALOCATIONS",4,false,$map->extralocations));
        fwrite ($bf,full_tag("SHOWADDRESS4EXTRA",4,false,$map->showaddress4extra));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$map->timemodified));

        //Now backup map_locations
        $status = backup_map_locations($bf,$preferences,$map->id);

        //End mod
        $status =fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

    //Backup map_locations contents (executed from map_backup_mods)
    function backup_map_locations ($bf,$preferences,$map) {

        global $CFG;

        $status = true;

        $map_locations = get_records("map_locations","mapid",$map,"id");
        //If there is locations
        if ($map_locations) {
            //Write start tag
            $status =fwrite ($bf,start_tag("LOCATIONS",4,true));
            //Iterate over each answer
            foreach ($map_locations as $map_location) {
                //Start answer
                $status =fwrite ($bf,start_tag("LOCATION",5,true));
                //Print location contents
                fwrite ($bf,full_tag("ID",6,false,$map_location->id));
                fwrite ($bf,full_tag("USERID",6,false,$map_location->userid));
                fwrite ($bf,full_tag("TITLE",6,false,$map_location->title));
                fwrite ($bf,full_tag("SHOWCODE",6,false,$map_location->showcode));
                fwrite ($bf,full_tag("LATITUDE",6,false,$map_location->latitude));
                fwrite ($bf,full_tag("LONGITUDE",6,false,$map_location->longitude));
                fwrite ($bf,full_tag("ADDRESS",6,false,$map_location->address));
                fwrite ($bf,full_tag("CITY",6,false,$map_location->city));
                fwrite ($bf,full_tag("STATE",6,false,$map_location->state));
                fwrite ($bf,full_tag("COUNTRY",6,false,$map_location->country));
                fwrite ($bf,full_tag("TEXT",6,false,$map_location->text));
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$map_location->timemodified));
                //End answer
                $status =fwrite ($bf,end_tag("LOCATION",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("LOCATIONS",4,true));
        }
        return $status;
    }


     
   ////Return an array of info (name,value)
   function map_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {

        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += map_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        //First the course data
        $info[0][0] = get_string("modulenameplural","map");
        if ($ids = map_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string("locations","map");
            if ($ids = map_location_ids_by_course ($course)) {
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
        }
        return $info;
    }

   ////Return an array of info (name,value)
   function map_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';

        //Now, if requested, the user_data
        if (!empty($instance->userdata)) {
            $info[$instance->id.'1'][0] = get_string("locations","map");
            if ($ids = map_location_ids_by_instance ($instance->id)) {
                $info[$instance->id.'1'][1] = count($ids);
            } else {
                $info[$instance->id.'1'][1] = 0;
            }
        }
        return $info;
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function map_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of maps
        $buscar="/(".$base."\/mod\/map\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@mapINDEX*$2@$',$content);

        //Link to map view by moduleid
        $buscar="/(".$base."\/mod\/map\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@mapVIEWBYID*$2@$',$result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of maps id
    function map_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}map a
                                 WHERE a.course = '$course'");
    }
   
    //Returns an array of map_locations id
    function map_location_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT s.id , s.mapid
                                 FROM {$CFG->prefix}map_locations s,
                                      {$CFG->prefix}map a
                                 WHERE a.course = '$course' AND
                                       s.mapid = a.id");
    }

    //Returns an array of map_locations id
    function map_location_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT s.id , s.mapid
                                 FROM {$CFG->prefix}map_locations s
                                 WHERE s.mapid = $instanceid");
    }
?>

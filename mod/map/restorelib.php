<?php //$Id: restorelib.php,v 1.1 2008/09/18 19:31:25 tedbow Exp $
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

    //This function executes all the restore procedure about this mod
    function map_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
                                                        //Debug

            //Now, build the MAP record structure
            $map->course = $restore->course_id;
            valuesBackup2Object($map,$info['MOD'],array('name','text','format','studentlocations','requireok','extralocations','showaddress4extra','timemodified'));

            //The structure is equal to the db, so insert the map
            $newid = insert_record ("map",$map);
            
            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);

                 //now restore the locations for this map.
                 if (restore_userdata_selected($restore,'map',$mod->id)) {
                    //Restore map_locations
                    $status = map_locations_restore_mods($newid,$info,$restore);     
                 }                               
            } else {
                $status = false;
            }

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","map")." \"".format_string(stripslashes($map->name),true)."\"</li>";
            }
            backup_flush(300);

        } else {
            $status = false;
        }
        return $status;
    }

function map_options_restore_mods($mapid,$info,$restore) {

        global $CFG;

        $status = true;

        $options = $info['MOD']['#']['OPTIONS']['0']['#']['OPTION'];

        //Iterate over options
        for($i = 0; $i < sizeof($options); $i++) {
            $opt_info = $options[$i];
            //traverse_xmlize($opt_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($opt_info['#']['ID']['0']['#']);
            $olduserid = isset($opt_info['#']['USERID']['0']['#'])?backup_todb($opt_info['#']['USERID']['0']['#']):'';

            //Now, build the MAP_OPTIONS record structure
            $option->mapid = $mapid;
            $option->text = backup_todb($opt_info['#']['TEXT']['0']['#']);
            $option->maxlocations = backup_todb($opt_info['#']['MAXLOCATIONS']['0']['#']);
            $option->timemodified = backup_todb($opt_info['#']['TIMEMODIFIED']['0']['#']);

            //The structure is equal to the db, so insert the map_options
            $newid = insert_record ("map_options",$option);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"map_options",$oldid,
                             $newid);
            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function restores the map_locations
    function map_locations_restore_mods($mapid,$info,$restore) {

        global $CFG;

        $status = true;

        $locations = $info['MOD']['#']['LOCATIONS']['0']['#']['LOCATION'];

        //Iterate over locations
        for($i = 0; $i < sizeof($locations); $i++) {
            $location_info = $locations[$i];
            //traverse_xmlize($sub_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($location_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($location_info['#']['USERID']['0']['#']);

            //Now, build the MAP_LOCATIONS record structure
            $location->mapid = $mapid;
           	valuesBackup2Object($location,$location_info,array('userid','title','showcode','latitude','longitude','address','city','state','country','text','timemodified'));
            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$location->userid);
            if ($user) {
                $location->userid = $user->new_id;
            }

            //The structure is equal to the db, so insert the map_locations
            $newid = insert_record ("map_locations",$location);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"map_locations",$oldid,
                             $newid);
            } else {
                $status = false;
            }
        }

        return $status;
    }

    //Return a content decoded to support interactivities linking. Every module
    //should have its own. They are called automatically from
    //map_decode_content_links_caller() function in each module
    //in the restore process
    function map_decode_content_links ($content,$restore) {
            
        global $CFG;
            
        $result = $content;
                
        //Link to the list of maps
                
        $searchstring='/\$@(MAPINDEX)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$content,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course id)
                $rec = backup_getid($restore->backup_unique_code,"course",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(MAPINDEX)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/map/index.php?id='.$rec->new_id,$result);
                } else { 
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/map/index.php?id='.$old_id,$result);
                }
            }
        }

        //Link to map view by moduleid

        $searchstring='/\$@(MAPVIEWBYID)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$result,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course_modules id)
                $rec = backup_getid($restore->backup_unique_code,"course_modules",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(MAPVIEWBYID)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/map/view.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/map/view.php?id='.$old_id,$result);
                }
            }
        }

        return $result;
    }

    //This function makes all the necessary calls to xxxx_decode_content_links()
    //function in each module, passing them the desired contents to be decoded
    //from backup format to destination site/course in order to mantain inter-activities
    //working in the backup/restore process. It's called from restore_decode_content_links()
    //function in restore process
    function map_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;
        
        if ($maps = get_records_sql ("SELECT c.id, c.text
                                   FROM {$CFG->prefix}map c
                                   WHERE c.course = $restore->course_id")) {
                                               //Iterate over each map->text
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($maps as $map) {
                //Increment counter
                $i++;
                $content = $map->text;
                $result = restore_decode_content_links_worker($content,$restore);
                if ($result != $content) {
                    //Update record
                    $map->text = addslashes($result);
                    $status = update_record("map",$map);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                }
                //Do some output
                if (($i+1) % 5 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 100 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }
        }

        return $status;
    }

    //This function converts texts in FORMAT_WIKI to FORMAT_MARKDOWN for
    //some texts in the module
    function map_restore_wiki2markdown ($restore) {
    
        global $CFG;

        $status = true;

        //Convert map->text
        if ($records = get_records_sql ("SELECT c.id, c.text, c.format
                                         FROM {$CFG->prefix}map c,
                                              {$CFG->prefix}backup_ids b
                                         WHERE c.course = $restore->course_id AND
                                               c.format = ".FORMAT_WIKI. " AND
                                               b.backup_code = $restore->backup_unique_code AND
                                               b.table_name = 'map' AND
                                               b.new_id = c.id")) {
            foreach ($records as $record) {
                //Rebuild wiki links
                $record->text = restore_decode_wiki_content($record->text, $restore);
                //Convert to Markdown
                $wtm = new WikiToMarkdown();
                $record->text = $wtm->convert($record->text, $restore->course_id);
                $record->format = FORMAT_MARKDOWN;
                $status = update_record('map', addslashes_object($record));
                //Do some output
                $i++;
                if (($i+1) % 1 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 20 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }

        }
        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function map_restore_logs($restore,$log) {
                    
        $status = false;
                    
        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "choose":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "choose again":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view all":
            $log->url = "index.php?id=".$log->course;
            $status = true;
            break;
        case "report":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "report.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
    function valuesBackup2Object(&$obj,$infoObj,$memberNames){
    	foreach($memberNames as $name){
    		//first check to see if member is used allows for adding field in later versions
    		if(!empty($infoObj['#'][strtoupper($name)]['0']['#'])){
    			$obj->$name = backup_todb($infoObj['#'][strtoupper($name)]['0']['#']);
    		}
    	}
    }
?>

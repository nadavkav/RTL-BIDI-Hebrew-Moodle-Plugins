<?php //$Id: restorelib.php,v 1.26 2007/04/22 22:07:03 stronk7 Exp $
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

    //This function executes all the restore procedure about this mod
    function kaltura_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;
        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug
          
            //Now, build the RESOURCE record structure
 //           $kaltura_entry->course = $restore->course_id;
            $kaltura_entry->entry_id = backup_todb($info['MOD']['#']['ENTRY_ID']['0']['#']);
            $kaltura_entry->dimensions = $info['MOD']['#']['DIMENSIONS']['0']['#'];
            $kaltura_entry->size = backup_todb($info['MOD']['#']['SIZE']['0']['#']);
            $kaltura_entry->custom_width = backup_todb($info['MOD']['#']['CUSTOM_WIDTH']['0']['#']);
            $kaltura_entry->design = backup_todb($info['MOD']['#']['DESIGN']['0']['#']);
            $kaltura_entry->title = backup_todb($info['MOD']['#']['TITLE']['0']['#']);
            $kaltura_entry->context = backup_todb($info['MOD']['#']['CONTEXT']['0']['#']);
            $kaltura_entry->entry_type = $info['MOD']['#']['ENTRY_TYPE']['0']['#'];
            $kaltura_entry->media_type = $info['MOD']['#']['MEDIA_TYPE']['0']['#'];
            $kaltura_entry->id = 0;
            $context_parts = explode('_', $kaltura_entry->context);
            if ($context_parts[0] == 'R')
            {
              $data2 = backup_getid($restore->backup_unique_code,'resource',$context_parts[1]);
            }
            else if ($context_parts[0] == 'S')
            {
              $data2 = backup_getid($restore->backup_unique_code,'assignment_submission',$context_parts[1]);
            }
            else
            {
              $status = false;
              return;
            }
            $kaltura_entry->context = $context_parts[0] . '_' . $data2->new_id;
            //The structure is equal to the db, so insert the resource
            $newid = insert_record ("kaltura_entries",$kaltura_entry);
            
            //restore course/module connection
            $mod_crs = new object();
            $mod_crs->course = $restore->course_id;
            $mod_crs->module = get_field('modules', 'id', 'name', 'kaltura');
            $mod_crs->instance = $newid;
            $mod_crs->section = 0;
            $mod_crs->added = time();
            unset($mod_crs->id);
            insert_record("course_modules", $mod_crs);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","resource")." \"".format_string(stripslashes($kaltura_entry->title),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {

                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);   
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

    //Return a content decoded to support interactivities linking. Every module
    //should have its own. They are called automatically from
    //kaltura_decode_content_links_caller() function in each module
    //in the restore process
    function kaltura_decode_content_links ($content,$restore) {
            
        global $CFG;
            
        $result = $content;
                
        //Link to the list of resources
                
        $searchstring='/\$@(RESOURCEINDEX)\*([0-9]+)@\$/';
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
                $searchstring='/\$@(RESOURCEINDEX)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if (!empty($rec->new_id)) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/resource/index.php?id='.$rec->new_id,$result);
                } else { 
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/resource/index.php?id='.$old_id,$result);
                }
            }
        }

        //Link to resource view by moduleid

        $searchstring='/\$@(RESOURCEVIEWBYID)\*([0-9]+)@\$/';
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
                $searchstring='/\$@(RESOURCEVIEWBYID)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if (!empty($rec->new_id)) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/resource/view.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/resource/view.php?id='.$old_id,$result);
                }
            }
        }

        //Link to resource view by resourceid

        $searchstring='/\$@(RESOURCEVIEWBYR)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$result,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (forum id)
                $rec = backup_getid($restore->backup_unique_code,"resource",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(RESOURCEVIEWBYR)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/resource/view.php?r='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/resource/view.php?r='.$old_id,$result);
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
    function kaltura_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;
        return $status;

        if ($resources = get_records_sql ("SELECT r.id, r.alltext, r.summary, r.reference
                                   FROM {$CFG->prefix}resource r
                                   WHERE r.course = $restore->course_id")) {

            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($resources as $resource) {
                //Increment counter
                $i++;
                $content1 = $resource->alltext;
                $content2 = $resource->summary;
                $content3 = $resource->reference;
                $result1 = restore_decode_content_links_worker($content1,$restore);
                $result2 = restore_decode_content_links_worker($content2,$restore);
                $result3 = restore_decode_content_links_worker($content3,$restore);

                if ($result1 != $content1 || $result2 != $content2 ||  $result3 != $content3) {
                    //Update record
                    $resource->alltext = addslashes($result1);
                    $resource->summary = addslashes($result2);
                    $resource->reference = addslashes($result3);
                    $status = update_record("resource",$resource);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content1).'<br />changed to<br />'.s($result1).'<hr /><br />';
                            echo '<br /><hr />'.s($content2).'<br />changed to<br />'.s($result2).'<hr /><br />';
                            echo '<br /><hr />'.s($content3).'<br />changed to<br />'.s($result3).'<hr /><br />';
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
    function kaltura_restore_wiki2markdown ($restore) {

        global $CFG;

        $status = true;
        return $status;

        //Convert resource->alltext
        if ($records = get_records_sql ("SELECT r.id, r.alltext, r.options
                                         FROM {$CFG->prefix}resource r,
                                              {$CFG->prefix}backup_ids b
                                         WHERE r.course = $restore->course_id AND
                                               options = ".FORMAT_WIKI. " AND
                                               b.backup_code = $restore->backup_unique_code AND
                                               b.table_name = 'resource' AND
                                               b.new_id = r.id")) {
            foreach ($records as $record) {
                //Rebuild wiki links
                $record->alltext = restore_decode_wiki_content($record->alltext, $restore);
                //Convert to Markdown
                $wtm = new WikiToMarkdown();
                $record->alltext = $wtm->convert($record->alltext, $restore->course_id);
                $record->options = FORMAT_MARKDOWN;
                $status = update_record('resource', addslashes_object($record));
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
    function kaltura_restore_logs($restore,$log) {
                    
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

?>

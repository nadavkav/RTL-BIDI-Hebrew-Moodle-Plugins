<?php //$Id: restorelib.php,v 1.13 2006/09/18 09:13:04 moodler Exp $
    //This php script contains all the stuff to backup/restore
    //swf mods

    //This is the "graphical" structure of the swf mod:   
    //
    //                       swf 
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

    //This function executes all the restore procedure about this mod
    function swf_restore_mods($mod,$restore) {

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
          
            //Now, build the FLV record structure
            $swf->course = $restore->course_id;
            $swf->type = $info['MOD']['#']['TYPE']['0']['#'];
			$swf->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
			$swf->intro = backup_todb($info['MOD']['#']['INTRO']['0']['#']);
			$swf->introformat = backup_todb($info['MOD']['#']['INTROFORMAT']['0']['#']);
			$swf->timecreated = backup_todb($info['MOD']['#']['TIMECREATED']['0']['#']);
			$swf->timemodified = $info['MOD']['#']['TIMEMODIFIED']['0']['#'];
			$swf->swfurl = backup_todb($info['MOD']['#']['SWFURL']['0']['#']);
			$swf->width = backup_todb($info['MOD']['#']['WIDTH']['0']['#']);
			$swf->height = backup_todb($info['MOD']['#']['HEIGHT']['0']['#']);
			$swf->version = backup_todb($info['MOD']['#']['VERSION']['0']['#']);
			$swf->interaction = backup_todb($info['MOD']['#']['INTERACTION']['0']['#']);
			$swf->xmlurl = backup_todb($info['MOD']['#']['XMLURL']['0']['#']);
			$swf->apikey = backup_todb($info['MOD']['#']['APIKEY']['0']['#']);
			$swf->play = backup_todb($info['MOD']['#']['PLAY']['0']['#']);
			$swf->loopswf = backup_todb($info['MOD']['#']['LOOPSWF']['0']['#']);
			$swf->menu = backup_todb($info['MOD']['#']['MENU']['0']['#']);
			$swf->quality = backup_todb($info['MOD']['#']['QUALITY']['0']['#']);
			$swf->scale = backup_todb($info['MOD']['#']['SCALE']['0']['#']);
			$swf->salign = backup_todb($info['MOD']['#']['SALIGN']['0']['#']);
            $swf->wmode = backup_todb($info['MOD']['#']['WMODE']['0']['#']);
			$swf->bgcolor = backup_todb($info['MOD']['#']['BGCOLOR']['0']['#']);
			$swf->devicefont = backup_todb($info['MOD']['#']['DEVICEFONT']['0']['#']);
			$swf->seamlesstabbing = backup_todb($info['MOD']['#']['SEAMLESSTABBING']['0']['#']);
			$swf->allowfullscreen = backup_todb($info['MOD']['#']['ALLOWFULLSCREEN']['0']['#']);
			$swf->allowscriptaccess = backup_todb($info['MOD']['#']['ALLOWSCRIPTACCESS']['0']['#']);
			$swf->allownetworking = backup_todb($info['MOD']['#']['ALLOWNETWORKING']['0']['#']);
			$swf->align = backup_todb($info['MOD']['#']['ALIGN']['0']['#']);
			$swf->flashvar1 = backup_todb($info['MOD']['#']['FLASHVAR1']['0']['#']);
			$swf->flashvar2 = backup_todb($info['MOD']['#']['FLASHVAR2']['0']['#']);
			$swf->flashvar3 = backup_todb($info['MOD']['#']['FLASHVAR3']['0']['#']);
            $swf->grading = backup_todb($info['MOD']['#']['GRADING']['0']['#']);
            //The structure is equal to the db, so insert the swf
            $newid = insert_record ("swf",$swf);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","swf")." \"".format_string(stripslashes($swf->name),true)."\"</li>";
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

	//This function makes all the necessary calls to xxxx_decode_content_links()
    //function in each module, passing them the desired contents to be decoded
    //from backup format to destination site/course in order to mantain inter-activities
    //working in the backup/restore process. It's called from restore_decode_content_links()
    //function in restore process
    function swf_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;
		
		//
		if ($swfs = get_records_sql ("SELECT s.id, s.intro, s.swfurl, s.xmlurl
                                   FROM {$CFG->prefix}swf s
                                   WHERE s.course = $restore->course_id")) {
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($swfs as $swf) {
                //Increment counter
                $i++;
				//
				$intro = $swf->intro;
                $swfurl = $swf->swfurl;
				$xmlurl = $swf->swffile;
				//
                $r_intro = restore_decode_content_links_worker($intro,$restore);
				$r_swfurl = restore_decode_content_links_worker($swfurl,$restore);
				$r_xmlurl = restore_decode_content_links_worker($xmlurl,$restore);
				//
				if ($r_intro != $intro || $r_swfurl != $swfurl || $r_xmlurl != $xmlurl) {
                    //Update record
                    $swf->intro = addslashes($r_intro);
					$swf->swfurl = addslashes($r_swfurl);
					$swf->xmlurl = addslashes($r_xmlurl);
					//
                    $status = update_record("swf", $swf);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($intro).'<br />changed to<br />'.s($r_intro).'<hr /><br />';
							echo '<br /><hr />'.s($swfurl).'<br />changed to<br />'.s($r_swfurl).'<hr /><br />';
							echo '<br /><hr />'.s($xmlurl).'<br />changed to<br />'.s($r_xmlurl).'<hr /><br />';
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

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function swf_restore_logs($restore,$log) {
                    
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

<?php //$Id: restorelib.php,v 1.13 2006/09/18 09:13:04 moodler Exp $
    //This php script contains all the stuff to backup/restore
    //mplayer mods

    //This is the "graphical" structure of the mplayer mod:   
    //
    //                       mplayer 
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
    function mplayer_restore_mods($mod,$restore) {

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
          
            //Now, build the MPLAYER record structure
            $mplayer->course = $restore->course_id;
            $mplayer->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
			$mplayer->intro = backup_todb($info['MOD']['#']['INTRO']['0']['#']);
			$mplayer->introformat = backup_todb($info['MOD']['#']['INTROFORMAT']['0']['#']);
			$mplayer->timecreated = backup_todb($info['MOD']['#']['TIMECREATED']['0']['#']);
			$mplayer->timemodified = $info['MOD']['#']['TIMEMODIFIED']['0']['#'];
			$mplayer->configxml = backup_todb($info['MOD']['#']['CONFIGXML']['0']['#']);
			$mplayer->author = backup_todb($info['MOD']['#']['AUTHOR']['0']['#']);
			$mplayer->mplayerdate = backup_todb($info['MOD']['#']['MPLAYERDATE']['0']['#']);
			$mplayer->description = backup_todb($info['MOD']['#']['DESCRIPTION']['0']['#']);
			$mplayer->infoboxcolor = backup_todb($info['MOD']['#']['INFOBOXCOLOR']['0']['#']);
			$mplayer->infoboxposition = backup_todb($info['MOD']['#']['INFOBOXPOSITION']['0']['#']);
			$mplayer->infoboxsize = backup_todb($info['MOD']['#']['INFOBOXSIZE']['0']['#']);
			$mplayer->duration = backup_todb($info['MOD']['#']['DURATION']['0']['#']);
			$mplayer->mplayerfile = backup_todb($info['MOD']['#']['MPLAYERFILE']['0']['#']);
			$mplayer->hdbitrate = backup_todb($info['MOD']['#']['HDBITRATE']['0']['#']);
			$mplayer->hdfile = backup_todb($info['MOD']['#']['HDFILE']['0']['#']);
			$mplayer->hdfullscreen = backup_todb($info['MOD']['#']['HDFULLSCREEN']['0']['#']);
			$mplayer->hdstate = backup_todb($info['MOD']['#']['HDSTATE']['0']['#']);
			$mplayer->livestreamfile = backup_todb($info['MOD']['#']['LIVESTREAMFILE']['0']['#']);
			$mplayer->livestreamimage = backup_todb($info['MOD']['#']['LIVESTREAMIMAGE']['0']['#']);
			$mplayer->livestreaminterval = backup_todb($info['MOD']['#']['LIVESTREAMINTERVAL']['0']['#']);
			$mplayer->livestreammessage = backup_todb($info['MOD']['#']['LIVESTREAMMESSAGE']['0']['#']);
			$mplayer->livestreamstreamer = backup_todb($info['MOD']['#']['LIVESTREAMSTREAMER']['0']['#']);
			$mplayer->livestreamtags = backup_todb($info['MOD']['#']['LIVESTREAMTAGS']['0']['#']);
			$mplayer->image = backup_todb($info['MOD']['#']['IMAGE']['0']['#']);
			$mplayer->audiodescriptionfile = backup_todb($info['MOD']['#']['AUDIODESCRIPTIONFILE']['0']['#']);
			$mplayer->audiodescriptionstate = backup_todb($info['MOD']['#']['AUDIODESCRIPTIONSTATE']['0']['#']);
			$mplayer->audiodescriptionvolume = backup_todb($info['MOD']['#']['AUDIODESCRIPTIONVOLUME']['0']['#']);
			$mplayer->mplayerstart = backup_todb($info['MOD']['#']['MPLAYERSTART']['0']['#']);
			$mplayer->tags = backup_todb($info['MOD']['#']['TAGS']['0']['#']);
			$mplayer->title = backup_todb($info['MOD']['#']['TITLE']['0']['#']);
			$mplayer->type = $info['MOD']['#']['TYPE']['0']['#'];
            $mplayer->backcolor = backup_todb($info['MOD']['#']['BACKCOLOR']['0']['#']);
			$mplayer->frontcolor = backup_todb($info['MOD']['#']['FRONTCOLOR']['0']['#']);
			$mplayer->lightcolor = backup_todb($info['MOD']['#']['LIGHTCOLOR']['0']['#']);
			$mplayer->screencolor = backup_todb($info['MOD']['#']['SCREENCOLOR']['0']['#']);
			$mplayer->controlbar = backup_todb($info['MOD']['#']['CONTROLBAR']['0']['#']);
			$mplayer->smoothing = backup_todb($info['MOD']['#']['SMOOTHING']['0']['#']);
			$mplayer->height = backup_todb($info['MOD']['#']['HEIGHT']['0']['#']);
			$mplayer->playlist = backup_todb($info['MOD']['#']['PLAYLIST']['0']['#']);
			$mplayer->playlistsize = backup_todb($info['MOD']['#']['PLAYLISTSIZE']['0']['#']);
			$mplayer->skin = backup_todb($info['MOD']['#']['SKIN']['0']['#']);
			$mplayer->width = backup_todb($info['MOD']['#']['WIDTH']['0']['#']);
			$mplayer->autostart = backup_todb($info['MOD']['#']['AUTOSTART']['0']['#']);
			$mplayer->bufferlength = backup_todb($info['MOD']['#']['BUFFERLENGTH']['0']['#']);
			$mplayer->fullscreen = backup_todb($info['MOD']['#']['FULLSCREEN']['0']['#']);
			$mplayer->icons = backup_todb($info['MOD']['#']['ICONS']['0']['#']);
			$mplayer->item = backup_todb($info['MOD']['#']['ITEM']['0']['#']);
			$mplayer->logoboxalign = backup_todb($info['MOD']['#']['LOGOBOXALIGN']['0']['#']);
			$mplayer->logoboxfile = backup_todb($info['MOD']['#']['LOGOBOXFILE']['0']['#']);
			$mplayer->logoboxlink = backup_todb($info['MOD']['#']['LOGOBOXLINK']['0']['#']);
			$mplayer->logoboxmargin = backup_todb($info['MOD']['#']['LOGOBOXMARGIN']['0']['#']);
			$mplayer->logoboxposition = backup_todb($info['MOD']['#']['LOGOBOXPOSITION']['0']['#']);
			$mplayer->logofile = backup_todb($info['MOD']['#']['LOGOFILE']['0']['#']);
			$mplayer->logolink = backup_todb($info['MOD']['#']['LOGOLINK']['0']['#']);
			$mplayer->logohide = backup_todb($info['MOD']['#']['LOGOHIDE']['0']['#']);
			$mplayer->logoposition = backup_todb($info['MOD']['#']['LOGOPOSITION']['0']['#']);
			$mplayer->mute = backup_todb($info['MOD']['#']['MUTE']['0']['#']);
			$mplayer->quality = backup_todb($info['MOD']['#']['QUALITY']['0']['#']);
			$mplayer->mplayerrepeat = backup_todb($info['MOD']['#']['MPLAYERREPEAT']['0']['#']);
			$mplayer->resizing = backup_todb($info['MOD']['#']['RESIZING']['0']['#']);
			$mplayer->shuffle = backup_todb($info['MOD']['#']['SHUFFLE']['0']['#']);
			$mplayer->state = backup_todb($info['MOD']['#']['STATE']['0']['#']);
			$mplayer->stretching = backup_todb($info['MOD']['#']['STRETCHING']['0']['#']);
			$mplayer->volume = backup_todb($info['MOD']['#']['VOLUME']['0']['#']);
			$mplayer->plugins = backup_todb($info['MOD']['#']['PLUGINS']['0']['#']);
			$mplayer->streamer = backup_todb($info['MOD']['#']['STREAMER']['0']['#']);
			$mplayer->tracecall = backup_todb($info['MOD']['#']['TRACECALL']['0']['#']);
			$mplayer->captionsback = backup_todb($info['MOD']['#']['CAPTIONSBACK']['0']['#']);
			$mplayer->captionsfile = backup_todb($info['MOD']['#']['CAPTIONSFILE']['0']['#']);
			$mplayer->captionsfontsize = backup_todb($info['MOD']['#']['CAPTIONSFONTSIZE']['0']['#']);
			$mplayer->captionsstate = backup_todb($info['MOD']['#']['CAPTIONSSTATE']['0']['#']);
			$mplayer->fpversion = backup_todb($info['MOD']['#']['FPVERSION']['0']['#']);
			$mplayer->notes = backup_todb($info['MOD']['#']['NOTES']['0']['#']);
            $mplayer->metaviewerposition = backup_todb($info['MOD']['#']['METAVIEWERPOSITION']['0']['#']);
            $mplayer->metaviewersize = backup_todb($info['MOD']['#']['METAVIEWERSIZE']['0']['#']);
            $mplayer->searchbarcolor = backup_todb($info['MOD']['#']['SEARCHBARCOLOR']['0']['#']);
            $mplayer->searchbarlabel = backup_todb($info['MOD']['#']['SEARCHBARLABEL']['0']['#']);
            $mplayer->searchbarposition = backup_todb($info['MOD']['#']['SEARCHBARPOSITION']['0']['#']);
            $mplayer->searchbarscript = backup_todb($info['MOD']['#']['SEARCHBARSCRIPT']['0']['#']);
            $mplayer->snapshotbitmap = backup_todb($info['MOD']['#']['SNAPSHOTBITMAP']['0']['#']);
            $mplayer->snapshotscript = backup_todb($info['MOD']['#']['SNAPSHOTSCRIPT']['0']['#']);
            
            //The structure is equal to the db, so insert the mplayer
            $newid = insert_record ("mplayer",$mplayer);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","mplayer")." \"".format_string(stripslashes($mplayer->name),true)."\"</li>";
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
    function mplayer_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;
		
		//
		if ($mplayers = get_records_sql ("SELECT f.id, f.configxml, f.mplayerfile, f.hdfile, f.livestreamstreamer, f.audiodescriptionfile, f.image, f.link, f.skin, f.logoboxfile, f.logoboxlink, f.logofile, f.logolink, f.streamer, f.captionsfile, f.notes, f.searchbarscript, f.snapshotscript
                                   FROM {$CFG->prefix}mplayer f
                                   WHERE f.course = $restore->course_id")) {
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($mplayers as $mplayer) {
                //Increment counter
                $i++;
				//
                $configxml = $mplayer->configxml;
				$mplayerfile = $mplayer->mplayerfile;
				$hdfile = $mplayer->hdfile;
				$livestreamstreamer = $mplayer->livestreamstreamer;
				$audiodescriptionfile = $mplayer->audiodescriptionfile;
				$image = $mplayer->image;
				$link = $mplayer->link;
				$skin = $mplayer->skin;
				$logoboxfile = $mplayer->logoboxfile;
				$logoboxlink = $mplayer->logoboxlink;
				$logofile = $mplayer->logofile;
				$logolink = $mplayer->logolink;
				$streamer = $mplayer->streamer;
				$captionsfile = $mplayer->captionsfile;
				$notes = $mplayer->notes;
				$searchbarscript = $mplayer->searchbarscript;
				$snapshotscript = $mplayer->snapshotscript;
				//
                $r_configxml = restore_decode_content_links_worker($configxml,$restore);
				$r_mplayerfile = restore_decode_content_links_worker($mplayerfile,$restore);
				$r_hdfile = restore_decode_content_links_worker($hdfile,$restore);
				$r_livestreamstreamer = restore_decode_content_links_worker($livestreamstreamer,$restore);
				$r_audiodescriptionfile = restore_decode_content_links_worker($audiodescriptionfile,$restore);
				$r_image = restore_decode_content_links_worker($image,$restore);
				$r_link = restore_decode_content_links_worker($link,$restore);
				$r_skin = restore_decode_content_links_worker($skin,$restore);
				$r_logoboxfile = restore_decode_content_links_worker($logoboxfile,$restore);
				$r_logoboxlink = restore_decode_content_links_worker($logoboxlink,$restore);
				$r_logofile = restore_decode_content_links_worker($logofile,$restore);
				$r_logolink = restore_decode_content_links_worker($logolink,$restore);
				$r_streamer = restore_decode_content_links_worker($streamer,$restore);
				$r_captionsfile = restore_decode_content_links_worker($captionsfile,$restore);
				$r_notes = restore_decode_content_links_worker($notes,$restore);
				$r_searchbarscript = restore_decode_content_links_worker($searchbarscript,$restore);
				$r_snapshotscript = restore_decode_content_links_worker($snapshotscript,$restore);
				//
				if ($r_configxml != $configxml || $r_mplayerfile != $mplayerfile || $r_hdfile != $hdfile || $r_livestreamstreamer != $livestreamstreamer || $r_audiodescriptionfile != $audiodescriptionfile || $r_image != $image || $r_link != $link || $r_skin != $skin || $r_logoboxfile != $logoboxfile || $r_logoboxlink != $logoboxlink || $r_logofile != $logofile || $r_logolink != $logolink || $r_streamer != $streamer || $r_captionsfile != $captionsfile || $r_notes != $notes || $r_searchbarscript != $searchbarscript || $r_snapshotscript != $snapshotscript) {
                    //Update record
                    $mplayer->configxml = addslashes($r_configxml);
					$mplayer->mplayerfile = addslashes($r_mplayerfile);
					$mplayer->hdfile = addslashes($r_hdfile);
					$mplayer->livestreamstreamer = addslashes($r_livestreamstreamer);
					$mplayer->audiodescriptionfile = addslashes($r_audiodescriptionfile);
					$mplayer->image = addslashes($r_image);
					$mplayer->link = addslashes($r_link);
					$mplayer->skin = addslashes($r_skin);
					$mplayer->logoboxfile = addslashes($r_logoboxfile);
					$mplayer->logoboxlink = addslashes($r_logoboxlink);
					$mplayer->logofile = addslashes($r_logofile);
					$mplayer->logolink = addslashes($r_logolink);
					$mplayer->streamer = addslashes($r_streamer);
					$mplayer->captionsfile = addslashes($r_captionsfile);
					$mplayer->notes = addslashes($r_notes);
					$mplayer->searchbarscript = addslashes($r_searchbarscript);
					$mplayer->snapshotscript = addslashes($r_snapshotscript);
					//
                    $status = update_record("mplayer", $mplayer);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($configxml).'<br />changed to<br />'.s($r_configxml).'<hr /><br />';
							echo '<br /><hr />'.s($mplayerfile).'<br />changed to<br />'.s($r_mplayerfile).'<hr /><br />';
							echo '<br /><hr />'.s($hdfile).'<br />changed to<br />'.s($r_hdfile).'<hr /><br />';
							echo '<br /><hr />'.s($livestreamstreamer).'<br />changed to<br />'.s($r_livestreamstreamer).'<hr /><br />';
							echo '<br /><hr />'.s($audiodescriptionfile).'<br />changed to<br />'.s($r_audiodescriptionfile).'<hr /><br />';
							echo '<br /><hr />'.s($image).'<br />changed to<br />'.s($r_image).'<hr /><br />';
							echo '<br /><hr />'.s($link).'<br />changed to<br />'.s($r_link).'<hr /><br />';
							echo '<br /><hr />'.s($skin).'<br />changed to<br />'.s($r_skin).'<hr /><br />';
							echo '<br /><hr />'.s($logoboxfile).'<br />changed to<br />'.s($r_logoboxfile).'<hr /><br />';
							echo '<br /><hr />'.s($logoboxlink).'<br />changed to<br />'.s($r_logoboxlink).'<hr /><br />';
							echo '<br /><hr />'.s($logofile).'<br />changed to<br />'.s($r_logofile).'<hr /><br />';
							echo '<br /><hr />'.s($logolink).'<br />changed to<br />'.s($r_logolink).'<hr /><br />';
							echo '<br /><hr />'.s($abouttext).'<br />changed to<br />'.s($r_abouttext).'<hr /><br />';
							echo '<br /><hr />'.s($aboutlink).'<br />changed to<br />'.s($r_aboutlink).'<hr /><br />';
							echo '<br /><hr />'.s($streamer).'<br />changed to<br />'.s($r_streamer).'<hr /><br />';
							echo '<br /><hr />'.s($captionsfile).'<br />changed to<br />'.s($r_captionsfile).'<hr /><br />';
							echo '<br /><hr />'.s($notes).'<br />changed to<br />'.s($r_notes).'<hr /><br />';
							echo '<br /><hr />'.s($searchbarscript).'<br />changed to<br />'.s($r_searchbarscript).'<hr /><br />';
							echo '<br /><hr />'.s($snapshotscript).'<br />changed to<br />'.s($r_snapshotscript).'<hr /><br />';
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
    function mplayer_restore_logs($restore,$log) {
                    
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

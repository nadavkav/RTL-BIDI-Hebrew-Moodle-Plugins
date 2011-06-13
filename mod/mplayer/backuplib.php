<?php //$Id: backuplib.php,v 1.7 2007/04/22 22:07:03 stronk7 Exp $
    //This php script contains all the stuff to backup/restore
    //mplayer mods

    //This is the "graphical" structure of the mplayer mod:
    //
    //                     mplayer                                      
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
    function mplayer_backup_mods($bf,$preferences) {
        global $CFG;

        $status = true; 

        ////Iterate over mplayer table
        $mplayers = get_records ("mplayer","course",$preferences->backup_course,"id");
        if ($mplayers) {
            foreach ($mplayers as $mplayer) {
                if (backup_mod_selected($preferences,'mplayer',$mplayer->id)) {
                    $status = mplayer_backup_one_mod($bf,$preferences,$mplayer);
                }
            }
        }
        return $status;
    }
   
    function mplayer_backup_one_mod($bf,$preferences,$mplayer) {

        global $CFG;
    
        if (is_numeric($mplayer)) {
            $mplayer = get_record('mplayer','id',$mplayer);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print assignment data
        fwrite ($bf,full_tag("ID",4,false,$mplayer->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"mplayer"));
        fwrite ($bf,full_tag("NAME",4,false,$mplayer->name));
		fwrite ($bf,full_tag("INTRO",4,false,$mplayer->intro));
        fwrite ($bf,full_tag("INTROFORMAT",4,false,$mplayer->introformat));
        fwrite ($bf,full_tag("TIMECREATED",4,false,$mplayer->timecreated));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$mplayer->timemodified));
        fwrite ($bf,full_tag("CONFIGXML",4,false,$mplayer->configxml));
        fwrite ($bf,full_tag("AUTHOR",4,false,$mplayer->author));
        fwrite ($bf,full_tag("MPLAYERDATE",4,false,$mplayer->mplayerdate));
        fwrite ($bf,full_tag("DESCRIPTION",4,false,$mplayer->description));
        fwrite ($bf,full_tag("INFOBOXCOLOR",4,false,$mplayer->infoboxcolor));
		fwrite ($bf,full_tag("INFOBOXPOSITION",4,false,$mplayer->infoboxposition));
		fwrite ($bf,full_tag("INFOBOXSIZE",4,false,$mplayer->infoboxsize));
		fwrite ($bf,full_tag("DURATION",4,false,$mplayer->duration));
        fwrite ($bf,full_tag("MPLAYERFILE",4,false,$mplayer->mplayerfile));
        fwrite ($bf,full_tag("HDBITRATE",4,false,$mplayer->hdbitrate));
		fwrite ($bf,full_tag("HDFILE",4,false,$mplayer->hdfile));
        fwrite ($bf,full_tag("HDFULLSCREEN",4,false,$mplayer->hdfullscreen));
		fwrite ($bf,full_tag("HDSATE",4,false,$mplayer->hdstate));
		fwrite ($bf,full_tag("LIVESTREAMFILE",4,false,$mplayer->livestreamfile));
		fwrite ($bf,full_tag("LIVESTREAMIMAGE",4,false,$mplayer->livestreamimage));
		fwrite ($bf,full_tag("LIVESTREAMINTERVAL",4,false,$mplayer->livestreaminterval));
		fwrite ($bf,full_tag("LIVESTREAMMESSAGE",4,false,$mplayer->livestreammessage));
		fwrite ($bf,full_tag("LIVESTREAMSTREAMER",4,false,$mplayer->livestreamstreamer));
		fwrite ($bf,full_tag("LIVESTREAMTAGS",4,false,$mplayer->livestreamtags));
		fwrite ($bf,full_tag("IMAGE",4,false,$mplayer->image));
		fwrite ($bf,full_tag("AUDIODESCRIPTIONFILE",4,false,$mplayer->audiodescriptionfile));
        fwrite ($bf,full_tag("AUDIODESCRIPTIONSTATE",4,false,$mplayer->audiodescriptionstate));
        fwrite ($bf,full_tag("AUDIODESCRIPTIONVOLUME",4,false,$mplayer->audiodescriptionvolume));
        fwrite ($bf,full_tag("MPLAYERSTART",4,false,$mplayer->mplayerstart));
        fwrite ($bf,full_tag("TAGS",4,false,$mplayer->tags));
        fwrite ($bf,full_tag("TITLE",4,false,$mplayer->title));
        fwrite ($bf,full_tag("TYPE",4,false,$mplayer->type));
        fwrite ($bf,full_tag("BACKCOLOR",4,false,$mplayer->backcolor));
        fwrite ($bf,full_tag("FRONTCOLOR",4,false,$mplayer->frontcolor));
        fwrite ($bf,full_tag("LIGHTCOLOR",4,false,$mplayer->lightcolor));
        fwrite ($bf,full_tag("SCREENCOLOR",4,false,$mplayer->screencolor));
        fwrite ($bf,full_tag("CONTROLBAR",4,false,$mplayer->controlbar));
        fwrite ($bf,full_tag("SMOOTHING",4,false,$mplayer->smoothing));
        fwrite ($bf,full_tag("HEIGHT",4,false,$mplayer->height));
        fwrite ($bf,full_tag("PLAYLIST",4,false,$mplayer->playlist));
        fwrite ($bf,full_tag("PLAYLISTSIZE",4,false,$mplayer->playlistsize));
        fwrite ($bf,full_tag("SKIN",4,false,$mplayer->skin));
        fwrite ($bf,full_tag("WIDTH",4,false,$mplayer->width));
        fwrite ($bf,full_tag("AUTOSTART",4,false,$mplayer->autostart));
        fwrite ($bf,full_tag("BUFFERLENGTH",4,false,$mplayer->bufferlength));
        fwrite ($bf,full_tag("FULLSCREEN",4,false,$mplayer->fullscreen));
        fwrite ($bf,full_tag("ICONS",4,false,$mplayer->icons));
        fwrite ($bf,full_tag("ITEM",4,false,$mplayer->item));
        fwrite ($bf,full_tag("LOGOBOXALIGN",4,false,$mplayer->logoboxalign));
        fwrite ($bf,full_tag("LOGOBOXFILE",4,false,$mplayer->logoboxfile));
        fwrite ($bf,full_tag("LOGOBOXLINK",4,false,$mplayer->logoboxlink));
        fwrite ($bf,full_tag("LOGOBOXMARGIN",4,false,$mplayer->logoboxmargin));
        fwrite ($bf,full_tag("LOGOBOXPOSITION",4,false,$mplayer->logoboxposition));
        fwrite ($bf,full_tag("LOGOFILE",4,false,$mplayer->logofile));
        fwrite ($bf,full_tag("LOGOLINK",4,false,$mplayer->logolink));
        fwrite ($bf,full_tag("LOGOHIDE",4,false,$mplayer->logohide));
        fwrite ($bf,full_tag("LOGOPOSITION",4,false,$mplayer->logoposition));
        fwrite ($bf,full_tag("MUTE",4,false,$mplayer->mute));
        fwrite ($bf,full_tag("QUALITY",4,false,$mplayer->quality));
        fwrite ($bf,full_tag("MPLAYERREPEAT",4,false,$mplayer->mplayerrepeat));
        fwrite ($bf,full_tag("RESIZING",4,false,$mplayer->resizing));
        fwrite ($bf,full_tag("SHUFFLE",4,false,$mplayer->shuffle));
        fwrite ($bf,full_tag("STATE",4,false,$mplayer->state));
        fwrite ($bf,full_tag("STRETCHING",4,false,$mplayer->stretching));
        fwrite ($bf,full_tag("VOLUME",4,false,$mplayer->volume));
        fwrite ($bf,full_tag("PLUGINS",4,false,$mplayer->plugins));
        fwrite ($bf,full_tag("STREAMER",4,false,$mplayer->streamer));
        fwrite ($bf,full_tag("TRACECALL",4,false,$mplayer->tracecall));
        fwrite ($bf,full_tag("CAPTIONSBACK",4,false,$mplayer->captionsback));
        fwrite ($bf,full_tag("CAPTIONSFILE",4,false,$mplayer->captionsfile));
        fwrite ($bf,full_tag("CAPTIONSFONTSIZE",4,false,$mplayer->captionsfontsize));
        fwrite ($bf,full_tag("CAPTIONSSTATE",4,false,$mplayer->captionsstate));
        fwrite ($bf,full_tag("FPVERSION",4,false,$mplayer->fpversion));
        fwrite ($bf,full_tag("NOTES",4,false,$mplayer->notes));
        fwrite ($bf,full_tag("METAVIEWERPOSITION",4,false,$mplayer->metaviewerposition));
        fwrite ($bf,full_tag("METAVIEWERSIZE",4,false,$mplayer->metaviewersize));
		fwrite ($bf,full_tag("SEARCHBARCOLOR",4,false,$mplayer->searchbarcolor));
		fwrite ($bf,full_tag("SEARCHBARLABEL",4,false,$mplayer->searchbarlabel));
		fwrite ($bf,full_tag("SEARCHBARPOSITION",4,false,$mplayer->searchbarposition));
		fwrite ($bf,full_tag("SEARCHBARSCRIPT",4,false,$mplayer->searchbarscript));
		fwrite ($bf,full_tag("SNAPSHOTBITMAP",4,false,$mplayer->snapshotbitmap));
		fwrite ($bf,full_tag("SNAPSHOTSCRIPT",4,false,$mplayer->snapshotscript));
        //End mod
        $status = fwrite ($bf,end_tag("MOD",3,true));

        if ($status) {
            // backup files for this mplayer.
            $status = mplayer_backup_files($bf,$preferences,$mplayer);
        }

        return $status;
    }

   ////Return an array of info (name,value)
   function mplayer_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
       if (!empty($instances) && is_array($instances) && count($instances)) {
           $info = array();
           foreach ($instances as $id => $instance) {
               $info += mplayer_check_backup_mods_instances($instance,$backup_unique_code);
           }
           return $info;
       }
       //First the course data
       $info[0][0] = get_string("modulenameplural","mplayer");
       if ($ids = mplayer_ids ($course)) {
           $info[0][1] = count($ids);
       } else {
           $info[0][1] = 0;
       }
       
       return $info;
   }

   ////Return an array of info (name,value)
   function mplayer_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';

        return $info;
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function mplayer_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of mplayers
        $buscar="/(".$base."\/mod\/mplayer\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@MPLAYERINDEX*$2@$',$content);

        //Link to mplayer view by moduleid
        $buscar="/(".$base."\/mod\/mplayer\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@MPLAYERVIEWBYID*$2@$',$result);

        //Link to mplayer view by mplayerid
        $buscar="/(".$base."\/mod\/mplayer\/view.php\?r\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@MPLAYERVIEWBYR*$2@$',$result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of mplayers id
    function mplayer_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}mplayer a
                                 WHERE a.course = '$course'");
    }
   
    function mplayer_backup_files($bf,$preferences,$mplayer) {
        global $CFG;
        $status = true;

        if (!file_exists($CFG->dataroot.'/'.$preferences->backup_course.'/'.$mplayer->mplayerfile)) {
            return true ; // doesn't exist but we don't want to halt the entire process so still return true.
        }
        
        $status = $status && check_and_create_course_files_dir($preferences->backup_unique_code);

        // if this is somewhere deeply nested we need to do all the structure stuff first.....
        $bits = explode('/',$mplayer->mplayerfile);
        $newbit = '';
        for ($i = 0; $i< count($bits)-1; $i++) {
            $newbit .= $bits[$i].'/';
            $status = $status && check_dir_exists($CFG->dataroot.'/temp/backup/'.$preferences->backup_unique_code.'/course_files/'.$newbit,true);
        }

        if ($mplayer->mplayerfile === '') {
            $status = $status && backup_copy_course_files($preferences); // copy while ignoring backupdata and moddata!!!
        } else if (strpos($mplayer->mplayerfile, 'backupdata') === 0 or strpos($mplayer->mplayerfile, $CFG->moddata) === 0) {
            // no copying - these directories must not be shared anyway!
        } else {
            $status = $status && backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$mplayer->mplayerfile,
                                                  $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/course_files/".$mplayer->mplayerfile);
        }
         
        // now, just in case we check moddata ( going forwards, mplayers should use this )
        $status = $status && check_and_create_moddata_dir($preferences->backup_unique_code);
        $status = $status && check_dir_exists($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/".$CFG->moddata."/mplayer/",true);
        
        if ($status) {
            //Only if it exists !! Thanks to Daniel Miksik.
            $instanceid = $mplayer->id;
            if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/mplayer/".$instanceid)) {
                $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/mplayer/".$instanceid,
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/mplayer/".$instanceid);
            }
        }

        return $status;
    }

?>

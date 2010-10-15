<?php // $Id: view.php,v 1.0 2009/01/28 matbury Exp $

/*
*    Copyright (C) 2009  Matt Bury - matbury@gmail.com - http://matbury.com/
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


    //This php script contains all the stuff to backup
    //swf mods

    //This is the "graphical" structure of the swf mod:
    //
    //                           swf                                      
    //                        (CL,pk->id)
    //                            |
    //                            |
    //                       swf_accesses
    //                     (UL,pk->id, fk->swfid)
    //                            |
    //                            |
    //                            |
    //                       swf_answers
    //                    (UL,pk->id,fk->accessid) 
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
    function swf_backup_mods($bf,$preferences) {
        global $CFG;

        $status = true; 

        ////Iterate over swf table
        $swfs = get_records ("swf","course",$preferences->backup_course,"id");
        if ($swfs) {
            foreach ($swfs as $swf) {
                if (backup_mod_selected($preferences,'swf',$swf->id)) {
                    $status = swf_backup_one_mod($bf,$preferences,$swf);
                }
            }
        }
        return $status;
    }
    
    function swf_backup_one_mod($bf,$preferences,$swf) {
        
        global $CFG;
		
        if (is_numeric($swf)) {
            $swf = get_record('swf','id',$swf);
        }

        $status = true;

		//Start mod
		fwrite ($bf,start_tag("MOD",3,true));
		//Print swf data
		fwrite ($bf,full_tag("ID",4,false,$swf->id));
		//tags will not be UTF encoded as they are already in UTF format 
		fwrite ($bf,full_tag("MODTYPE",4,false,"swf"));
		fwrite ($bf,full_tag("COURSE",4,false,$swf->course));
		fwrite ($bf,full_tag("NAME",4,false,$swf->name));
		fwrite ($bf,full_tag("INTRO",4,false,$swf->intro));
		fwrite ($bf,full_tag("INTROFORMAT",4,false,$swf->introformat));
		fwrite ($bf,full_tag("SWFURL",4,false,$swf->swfurl));
		fwrite ($bf,full_tag("WIDTH",4,false,$swf->width));
		fwrite ($bf,full_tag("HEIGHT",4,false,$swf->height));
		fwrite ($bf,full_tag("VERSION",4,false,$swf->version));
		fwrite ($bf,full_tag("INTERACTION",4,false,$swf->interaction));
		fwrite ($bf,full_tag("XMLURL",4,false,$swf->xmlurl));
		fwrite ($bf,full_tag("APIKEY",4,false,$swf->apikey));
		fwrite ($bf,full_tag("PLAY",4,false,$swf->play));
		fwrite ($bf,full_tag("LOOPSWF",4,false,$swf->loopswf));
		fwrite ($bf,full_tag("MENU",4,false,$swf->menu));
		fwrite ($bf,full_tag("QUALITY",4,false,$swf->quality));
		fwrite ($bf,full_tag("SCALE",4,false,$swf->scale));
		fwrite ($bf,full_tag("SALIGN",4,false,$swf->salign));
		fwrite ($bf,full_tag("WMODE",4,false,$swf->wmode));
		fwrite ($bf,full_tag("BGCOLOR",4,false,$swf->bgcolor));
		fwrite ($bf,full_tag("DEVICEFONT",4,false,$swf->devicefont));
		fwrite ($bf,full_tag("SEAMLESSTABBING",4,false,$swf->seamlesstabbing));
		fwrite ($bf,full_tag("ALLOWFULLSCREEN",4,false,$swf->allowfullscreen));
		fwrite ($bf,full_tag("ALLOWSCRIPTACCESS",4,false,$swf->allowscriptaccess));
		fwrite ($bf,full_tag("ALLOWNETWORKING",4,false,$swf->allownetworking));
		fwrite ($bf,full_tag("ALIGN",4,false,$swf->align));
		fwrite ($bf,full_tag("FLASHVAR1",4,false,$swf->flashvar1));
		fwrite ($bf,full_tag("FLASHVAR2",4,false,$swf->flashvar2));
		fwrite ($bf,full_tag("FLASHVAR3",4,false,$swf->flashvar3));
		fwrite ($bf,full_tag("GRADING",4,false,$swf->grading));
		//End mod
		$status = fwrite ($bf,end_tag("MOD",3,true));
		
		if ($status && ($swf->type == 'file' || $swf->type == 'directory' || $swf->type == 'ims')) { // more should go here later!
            // backup files for this swf.
            $status = swf_backup_files($bf,$preferences,$swf);
        }
        return $status;
    }

    //Backup swf_interactions contents
    /*function backup_swf_interactions($bf,$preferences,$swf) {

        global $CFG;

        $status = true;
		
		//Get swf_interaction by course ID
        $swf_interactions = get_records("swf_interactions","course",$preferences->backup_course,"id");
        //If there is submissions
        if ($swf_interactions) {
            //Write start tag
            $status =fwrite ($bf,start_tag("INTERACTIONS",4,true));
            //Iterate over each access
            foreach ($swf_interactions as $swf_interaction) {
                //Start access
                $status =fwrite ($bf,start_tag("INTERACTION",5,true));
                //Print submission contents
                fwrite ($bf,full_tag("ID",6,false,$swf_interaction->id, false));
                fwrite ($bf,full_tag("COURSE",6,false,$swf_interaction->course, false));
                fwrite ($bf,full_tag("NAME",6,false,$swf_interaction->name, false));
				fwrite ($bf,full_tag("INTRO",6,false,$swf_interaction->intro, false));
                fwrite ($bf,full_tag("INTROFORMAT",6,false,$swf_interaction->introformat, false));
                fwrite ($bf,full_tag("AMFTABLE",6,false,$swf_interaction->amftable, false));
                fwrite ($bf,full_tag("TIMECREATED",6,false,$swf_interaction->timecreated, false));
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$swf_interaction->timemodified, false));
                //End access
                $status =fwrite ($bf,end_tag("INTERACTION",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("INTERACTIONS",4,true));
        }
        return $status;
    }*/
    
    //Backup swf_interaction_data contents
    /*function backup_swf_interaction_data ($bf,$preferences,$access) {

        global $CFG;

        $status = true;
		
		//Get swf_interaction_data by course ID
        $swf_interaction_datas = get_records("swf_interaction_data","course",$swf,"id");
		
        //If there is data
        if ($swf_interaction_datas) {
            //Write start tag
            $status =fwrite ($bf,start_tag("INTERACTIONDATAS",6,true));
            //Iterate over each answer
            foreach ($swf_interaction_datas as $swf_interaction_data) {
                //Start answer
                $status =fwrite ($bf,start_tag("INTERACTIONDATA",7,true));
                //Print submission contents
                fwrite ($bf,full_tag("ID",8,false,$swf_interaction_data->id, false));
                fwrite ($bf,full_tag("COURSE",8,false,$swf_interaction_data->course, false));
                fwrite ($bf,full_tag("INTERACTION",8,false,$swf_interaction_data->interaction, false));
                fwrite ($bf,full_tag("QUESTIONAUDIO",8,false,$swf_interaction_data->questionAudio, false));
                fwrite ($bf,full_tag("QUESTIONSTRETCHED",8,false,$swf_interaction_data->questionStretched, false));
				fwrite ($bf,full_tag("QUESTIONTEXT",8,false,$swf_interaction_data->questionText, false));
				fwrite ($bf,full_tag("QUESTIONIMAGE",8,false,$swf_interaction_data->questionImage, false));
				fwrite ($bf,full_tag("ANSWERAUDIO",8,false,$swf_interaction_data->answerAudio, false));
				fwrite ($bf,full_tag("ANSWERSTRETCHED",8,false,$swf_interaction_data->answerStretched, false));
				fwrite ($bf,full_tag("ANSWERTEXT",8,false,$swf_interaction_data->answerText, false));
				fwrite ($bf,full_tag("ANSWERIMAGE",8,false,$swf_interaction_data->answerImage, false));
				fwrite ($bf,full_tag("BEGTEXT",8,false,$swf_interaction_data->begText, false));
				fwrite ($bf,full_tag("MIDTEXT",8,false,$swf_interaction_data->midText, false));
				fwrite ($bf,full_tag("ENDTEXT",8,false,$swf_interaction_data->endText, false));
				fwrite ($bf,full_tag("CORRECTAUDIO",8,false,$swf_interaction_data->correctAudio, false));
				fwrite ($bf,full_tag("CORRECTSTRETCHED",8,false,$swf_interaction_data->correctStretched, false));
				fwrite ($bf,full_tag("CORRECTTEXT",8,false,$swf_interaction_data->correctText, false));
				fwrite ($bf,full_tag("CORRECTIMAGE",8,false,$swf_interaction_data->correctImage, false));
				fwrite ($bf,full_tag("WRONGAUDIO",8,false,$swf_interaction_data->wrongAudio, false));
				fwrite ($bf,full_tag("WRONGSTRETCHED",8,false,$swf_interaction_data->wrongStretched, false));
				fwrite ($bf,full_tag("WRONGTEXT",8,false,$swf_interaction_data->wrongText, false));
				fwrite ($bf,full_tag("WRONGIMAGE",8,false,$swf_interaction_data->wrongImage, false));
				fwrite ($bf,full_tag("VIDEOSOURCE",8,false,$swf_interaction_data->videoSource, false));
				fwrite ($bf,full_tag("VIDEOTEXT",8,false,$swf_interaction_data->videoText, false));
				fwrite ($bf,full_tag("VIDEOCAPTIONS",8,false,$swf_interaction_data->videoCaptions, false));
				fwrite ($bf,full_tag("SPEAKER",8,false,$swf_interaction_data->speaker, false));
				fwrite ($bf,full_tag("KEYWORDS",8,false,$swf_interaction_data->keywords, false));
				fwrite ($bf,full_tag("TIMECREATED",8,false,$swf_interaction_data->timecreated, false));
				fwrite ($bf,full_tag("TIMEMODIFIED",8,false,$swf_interaction_data->timemodified, false));
				//End answer
                $status =fwrite ($bf,end_tag("INTERACTIONDATA",7,true));
            }
            //Write end tag
            $status = fwrite ($bf,end_tag("INTERACTIONDATAS",6,true));
        }
        return $status;
    }*/
   
   ////Return an array of info (name,value)
   function swf_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
       if (!empty($instances) && is_array($instances) && count($instances)) {
           $info = array();
           foreach ($instances as $id => $instance) {
               $info += swf_check_backup_mods_instances($instance,$backup_unique_code);
           }
           return $info;
       }
       //First the course data
       $info[0][0] = get_string("modulenameplural","swf");
       if ($ids = swf_ids ($course)) {
           $info[0][1] = count($ids);
       } else {
           $info[0][1] = 0;
       }
       return $info;
   }

   ////Return an array of info (name,value)
   function swf_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
		
        return $info;
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function swf_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of swfs
        $buscar="/(".$base."\/mod\/swf\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@SWFINDEX*$2@$',$content);

        //Link to swf view by module id
        $buscar="/(".$base."\/mod\/swf\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@SWFVIEWBYID*$2@$',$result);

        //Link to swf view by swf id
        $buscar="/(".$base."\/mod\/swf\/view.php\?r\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@swfVIEWBYR*$2@$',$result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of swfs id
    function swf_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT f.id, f.course
                                 FROM {$CFG->prefix}swf f
                                 WHERE f.course = '$course'");
    }
   
    //Returns an array of swf_interactions id
    /*function swf_interaction_ids_by_course ($course) {

        global $CFG;
        $sql="SELECT acc.id , acc.swfid
                                 FROM {$CFG->prefix}swf_interactions acc,
                                      {$CFG->prefix}swf f
                                 WHERE f.course = '$course' AND
                                       acc.swfid = f.id";
        return get_records_sql ($sql);
    }*/
	
	//
    /*function swf_interaction_data_ids_by_course ($course) {

        global $CFG;
        $sql="SELECT ans.id , acc.swfid
                                 FROM {$CFG->prefix}swf_answers ans,
                                      {$CFG->prefix}swf_accesses acc,
                                      {$CFG->prefix}swf f
                                 WHERE ans.accessid = acc.id AND
                                         f.course = '$course' AND
                                       acc.swfid = f.id";
        return get_records_sql ($sql);
    }*/
	
	//
    function swf_backup_files($bf,$preferences,$swf) {
        global $CFG;
        $status = true;

        if (!file_exists($CFG->dataroot.'/'.$preferences->backup_course.'/'.$swf->reference)) {
            return true ; // doesn't exist but we don't want to halt the entire process so still return true.
        }
        
        $status = $status && check_and_create_course_files_dir($preferences->backup_unique_code);

        // if this is somewhere deeply nested we need to do all the structure stuff first.....
        $bits = explode('/',$swf->reference);
        $newbit = '';
        for ($i = 0; $i< count($bits)-1; $i++) {
            $newbit .= $bits[$i].'/';
            $status = $status && check_dir_exists($CFG->dataroot.'/temp/backup/'.$preferences->backup_unique_code.'/course_files/'.$newbit,true);
        }

        if ($swf->reference === '') {
            $status = $status && backup_copy_course_files($preferences); // copy while ignoring backupdata and moddata!!!
        } else if (strpos($swf->reference, 'backupdata') === 0 or strpos($swf->reference, $CFG->moddata) === 0) {
            // no copying - these directories must not be shared anyway!
        } else {
            $status = $status && backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$swf->reference,
                                                  $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/course_files/".$swf->reference);
        }
         
        // now, just in case we check moddata ( going forwards, swfs should use this )
        $status = $status && check_and_create_moddata_dir($preferences->backup_unique_code);
        $status = $status && check_dir_exists($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/".$CFG->moddata."/swf/",true);
        
        if ($status) {
            //Only if it exists !! Thanks to Daniel Miksik.
            $instanceid = $swf->id;
            if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/swf/".$instanceid)) {
                $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/swf/".$instanceid,
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/swf/".$instanceid);
            }
        }

        return $status;
    }
?>

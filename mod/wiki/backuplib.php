<?php //Created by Antonio Casta�o & Juan Casta�o
	//Updated and fixed by Bernardino Todoli
    //This php script contains all the stuff to backup/restore
    //wiki mods
    
	require_once('lib.php');

    //This function executes all the backup procedure about this mod
    function wiki_backup_mods($bf,$preferences) {
		
        $status = true;
        ////Iterate over wiki table
        $dfwikis = get_records ('wiki',"course", $preferences->backup_course,"id");
        if ($dfwikis){
			foreach ($dfwikis as $dfwiki) {
			if (backup_mod_selected($preferences,'wiki',$dfwiki->id)) {
                    $status = wiki_backup_one_mod($bf,$preferences,$dfwiki);
                    // backup files happens in backup_one_mod now too.
                }
            }
        }
        return $status;
	}
			
	function wiki_backup_one_mod($bf,$preferences,$dfwiki){
        
        if (is_numeric($dfwiki)) {
            $dfwiki = get_record('wiki','id',$dfwiki);
        }
        $instanceid = $dfwiki->id;
        
        $status = true;
                
        //Start mod
		
		fwrite ($bf,start_tag("MOD",3,true));
		//Print assignment data
		fwrite ($bf,full_tag("ID",4,false,$dfwiki->id));
		fwrite ($bf,full_tag("MODTYPE",4,false,"wiki"));
		$order = wiki_order($dfwiki->id, $preferences->backup_course);
		fwrite ($bf,full_tag("ORDER",4,false,$order));
		fwrite ($bf,full_tag("NAME",4,false,$dfwiki->name));
        fwrite ($bf,full_tag("INTRO",4,false,$dfwiki->intro));
        fwrite ($bf,full_tag("INTROFORMAT",4,false,$dfwiki->introformat));
		fwrite ($bf,full_tag("PAGENAME",4,false,$dfwiki->pagename));
		fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$dfwiki->timemodified));
		fwrite ($bf,full_tag("EDITABLE",4,false,$dfwiki->editable));
		fwrite ($bf,full_tag("ATTACH",4,false,$dfwiki->attach));
		fwrite ($bf,full_tag("RESTORE",4,false,$dfwiki->restore));
		fwrite ($bf,full_tag("EDITOR",4,false,$dfwiki->editor));
		// discussions
        fwrite ($bf,full_tag("STUDENTDISCUSSION",4,false,$dfwiki->studentdiscussion));
        fwrite ($bf,full_tag("TEACHERDISCUSSION",4,false,$dfwiki->teacherdiscussion));
        // groups mode
        fwrite ($bf,full_tag("STUDENTMODE",4,false,$dfwiki->studentmode));
        // student edition privileges (groupmode)
        fwrite ($bf,full_tag("EDITANOTHERGROUP",4,false,$dfwiki->editanothergroup));
        fwrite ($bf,full_tag("EDITANOTHERSTUDENT",4,false,$dfwiki->editanotherstudent));
		
        fwrite ($bf,full_tag("VOTEMODE",4,false,$dfwiki->votemode));
        fwrite ($bf,full_tag("LISTOFTEACHERS",4,false,$dfwiki->listofteachers));
		
        fwrite ($bf,full_tag("EDITORROWS",4,false,$dfwiki->editorrows));
        fwrite ($bf,full_tag("EDITORCOLS",4,false,$dfwiki->editorcols));
        fwrite ($bf,full_tag("WIKICOURSE",4,false,$dfwiki->wikicourse));
       		        		
		//backup pages, synonymous and blocks
		if ($preferences->mods["wiki"]->userinfo) {
			$status=backup_wiki_pages($bf,$preferences,$dfwiki->id, $preferences->mods["wiki"]->userinfo);
			//using standard blocks, it deactivates the next line: 
			$status=backup_wiki_synonymous($bf,$preferences,$dfwiki->id, $preferences->mods["wiki"]->userinfo);
			$status = backup_wiki_files($bf,$preferences,$dfwiki->id);
		}

		//End mod
		 fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

    ////Return an array of info (name,value)
    function wiki_check_backup_mods($course,$user_data=false,$backup_unique_code) {

         //First the course data
         $info[0][0] = get_string("modulenameplural",'wiki');
         $info[0][1] = count_records('wiki', "course", "$course");
         return $info;
    }

    //Backup dfwiki_pages contents (executed from dfwiki_backup_mods)
    function backup_wiki_pages ($bf,$preferences,$dfwiki, $userinfo) {

        $status = true;

        $dfwiki_pages = get_records("wiki_pages","dfwiki",$dfwiki,"id");
        //If there are pages
        if ($dfwiki_pages) {
            //Write start tag
            $status =fwrite ($bf,start_tag("PAGES",4,true));
            //Iterate over each page
            foreach ($dfwiki_pages as $dfwik_pag) {
                //Page start
                $status =fwrite ($bf,start_tag("PAGE",5,true));

                fwrite ($bf,full_tag("ID",6,false,$dfwik_pag->id));
                fwrite ($bf,full_tag("PAGENAME",6,false,$dfwik_pag->pagename));
                fwrite ($bf,full_tag("VERSION",6,false,$dfwik_pag->version));
                fwrite ($bf,full_tag("CONTENT",6,false,$dfwik_pag->content));
                fwrite ($bf,full_tag("AUTHOR",6,false,$dfwik_pag->author));
                fwrite ($bf,full_tag("USERID",6,false,$dfwik_pag->userid));
                fwrite ($bf,full_tag("OWNERID",6,false,$dfwik_pag->ownerid));
                fwrite ($bf,full_tag("CREATED",6,false,$dfwik_pag->created));
                fwrite ($bf,full_tag("LASTMODIFIED",6,false,$dfwik_pag->lastmodified));
                fwrite ($bf,full_tag("REFS",6,false,$dfwik_pag->refs));
                fwrite ($bf,full_tag("HITS",6,false,$dfwik_pag->hits));
                fwrite ($bf,full_tag("EDITABLE",6,false,$dfwik_pag->editable));
                fwrite ($bf,full_tag("EDITOR",6,false,$dfwik_pag->editor));                
                $groupname = wiki_groupname($dfwik_pag->groupid);
                fwrite ($bf,full_tag("GROUPNAME",6,false,$groupname));
                
                fwrite ($bf,full_tag("HIGHLIGHT",6,false,$dfwik_pag->highlight));
                fwrite ($bf,full_tag("DFWIKI",6,false,$dfwik_pag->dfwiki));
                
                //Page end
                $status =fwrite ($bf,end_tag("PAGE",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("PAGES",4,true));
        }
        return $status;
    }
    
    
//Backup dfwiki_synonymous contents (executed from dfwiki_backup_mods)
    function backup_wiki_synonymous ($bf,$preferences,$dfwiki, $userinfo) {

        $status = true;

        $dfwiki_synonymous = get_records("wiki_synonymous","dfwiki",$dfwiki,"id");
        //If there are synonymous
        if ($dfwiki_synonymous) {
            //Write start tag
            $status =fwrite ($bf,start_tag("SYNONYMOUS",4,true));
            //Iterate over each synonym
            foreach ($dfwiki_synonymous as $dfwik_syn) {
                //synonym start
                $status =fwrite ($bf,start_tag("SYNONYM",5,true));

                fwrite ($bf,full_tag("ID",6,false,$dfwik_syn->id));
                fwrite ($bf,full_tag("SYN",6,false,$dfwik_syn->syn));
                fwrite ($bf,full_tag("ORIGINAL",6,false,$dfwik_syn->original));
                $groupname = wiki_groupname($dfwik_syn->groupid);
                fwrite ($bf,full_tag("GROUPNAME",6,false,$groupname));
                fwrite ($bf,full_tag("OWNERID",6,false,$dfwik_syn->ownerid));

                //synonym end
                $status =fwrite ($bf,end_tag("SYNONYM",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("SYNONYMOUS",4,true));
        }
        return $status;
    }
    
    //Backup wiki binary files
    function backup_wiki_files($bf,$preferences,$id) {

        global $CFG;

        $status = true;
     
        $modul = get_record("modules", "name", 'wiki');
        
        if (! $cm = get_record("course_modules", "instance", $id, "module", $modul->id)) {
            error("Course Module ID was incorrect");
        }
        
        check_dir_exists("$CFG->dataroot/$preferences->backup_course",true);
        check_dir_exists("$CFG->dataroot/$preferences->backup_course/$CFG->moddata",true);
        check_dir_exists("$CFG->dataroot/$preferences->backup_course/$CFG->moddata/wiki$cm->id",true);
        $moddata_path = $CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/wiki".$cm->id;
        check_dir_exists("$CFG->dataroot/temp",true);
        check_dir_exists("$CFG->dataroot/temp/backup",true);
        check_dir_exists("$CFG->dataroot/temp/backup/$preferences->backup_unique_code",true);
        check_dir_exists("$CFG->dataroot/temp/backup/$preferences->backup_unique_code/moddata",true);
        check_dir_exists("$CFG->dataroot/temp/backup/$preferences->backup_unique_code/moddata/wiki",true);
        check_dir_exists("$CFG->dataroot/temp/backup/$preferences->backup_unique_code/moddata/wiki/wiki$id",true);
        $temp_path = $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/wiki/wiki".$id;

        //get all attachments
        $list = null;
        $list = list_directories_and_files ($moddata_path);
        if($list != null){
            foreach ($list as $file) {
                $from_file = "$moddata_path/$file";
                $to_file = "$temp_path/$file";
                copy($from_file,$to_file);

            }
        }

        return $status;

    }
    
    //Tis function returns a wiki position in a given course
    function wiki_order($dfwikiid, $course) {

        global $CFG;
        
        $modul = get_record("modules", "name", 'wiki');
        
        $dfwikisorder = get_records_sql('SELECT *
					FROM '. $CFG->prefix.'course_modules
					WHERE module='.$modul->id.' AND course='.$course.' ORDER BY section DESC, id DESC');
        
        $i = 0;
        if($dfwikisorder != null){
            foreach ($dfwikisorder as $dfwikiord) {
                if($dfwikiord->instance == $dfwikiid){
                    $order = $i;
                } 
                $i++;
            }
        }
        
        return $order;

    }
    
    //This function returns a group name
    function wiki_groupname($groupid) {

        global $CFG,$modname;

        if (!$group = get_record_sql('SELECT *
					FROM '. $CFG->prefix.'groups
					WHERE id='.$groupid)){

            $group->name = '';
        }
        
        return $group->name;

    }

?>

<?php

class storage{

	//var $cleanpage;//contains the page name to clean
	var $cm;//contains the course module information 
	//var $delpage;//contains the page name to remove
	var $dfcontentf = array();//contains all functions name
	var $dfcontent;//contains the number of function to execute
	var $dfcourse;//variable to know if we are in a wiki course
	var $dfdir;//contains all uploaded files data
	var $dfform = array();//dfform contains all form information
	var $dfformcontent;//contains all form information
	var $dfperms = array();//dfperms conatins all user permissions
	var $dfsetup;//contains the number of function to execute
	var $dfsetupf = array();//contains all functions name
	var $dfwiki;//contains all dfwiki information
	var $editor;//contains type of editor
	var $editor_size;//contains the editor size
	var $enpage;//contains one page name
	var $exportxml;//??
	var $gid;//contains a group id 
	var $groupmember;//contains the group member's information
	var $linkid;//contains id link
	var $member;//contains the user's information
	var $nocontents;//contains page names
	var $page;//contains wiki page name
	var $pageaction; //the action to do (edit, view, diff...)
	var $pagedata; //contains the page's information
	//var $pagename; //only the pagename
	var $pageolddata;//contains old page information
	var $pageverdata;//contains the page information
	var $parser_format = array();//contains parser commands
	var $parser_vars = array();//contains global parser vars
	var $parser_logs = array();//contains parser logs
	var $path;//contains a path
	var $selectedtab;//contains the selected tab
	var $type;//contains the wiki type
	var $uid;//contains an user id
	var $updatepage;//contains the page name to update
	var $upload_bar;//upload_bar determines if the upload bar is shown	
	var $ver;//contains page's version
	var $wiki_format = array();//contains parser commands
	var $wikieditable; //determines if the current user can edit the wiki
	var $wikitype;//contains the link path
	var $wikibook;
	var $wikibookinfo;
	
	function storage(){
		$this->dfperms['edit'] = false;
		$this->dfperms['attach'] = false;
		$this->dfperms['restore'] = false;
		$this->dfperms['discuss'] = false;
		$this->dfperms['evaluation'] = '0';
		$this->dfperms['notetype'] = false;
		$this->dfperms['editanothergroup'] = false;
		$this->dfperms['editanotherstudent'] = false;
		$this->dfperms['listofteachers'] = false;
		$this->upload_bar = false;
		$this->dfcontentf[0]="wiki_ead_mostviewed";
		$this->dfcontentf[1]="wiki_ead_updatest";
		$this->dfcontentf[2]="wiki_ead_newest";
		$this->dfcontentf[3]="wiki_ead_wanted";
		$this->dfcontentf[4]="wiki_ead_orphaned";
		$this->dfcontentf[5]="wiki_ead_activestusers";
		$this->dfcontentf[6]="wiki_ead_print_delpage";
		$this->dfcontentf[7]="wiki_ead_print_updatepage";
		$this->dfcontentf[8]="wiki_ead_print_enpage";
		$this->dfcontentf[9]="wiki_ead_print_cleanpage";
		$this->dfcontentf[10]="wiki_block_search_print";
		$this->dfcontentf[11]="wiki_hist_content";
		$this->dfsetupf[0]="wiki_ead_delpage";
		$this->dfsetupf[1]="wiki_ead_updatepage";
		$this->dfsetupf[2]="wiki_ead_cleanpage";
		$this->dfsetupf[3]="wiki_ead_enpage";
		$this->dfsetupf[4]="wiki_block_new_page";
		$this->dfsetupf[5]="wiki_block_search";
	}
	
	function set_info($id = false){
		global $CFG, $USER, $COURSE,$course;
		
		//trying to force cm id
		if ($id) {
			$this->id = $id;
		}
		
		if (!isset($this->a) || !$this->a) {
			$a = optional_param('a',NULL,PARAM_INT);
			wiki_param ('a',$a);
		}
		if (!isset($this->id) || !$this->id) {
			$id = optional_param('id',NULL,PARAM_INT);
			wiki_param ('id',$id);
		}
		
		//course-module
		if (isset ($this->id) && $this->id) {
			if (! $this->cm = get_coursemodule_from_id('wiki',$this->id)) {
	            error("Course Module ID was incorrect");
	        }
		} else {
			if (! $this->cm = get_coursemodule_from_instance('wiki',$this->a)) {
	            error("Course Module ID was incorrect");
	        }
		}
		if (! $this->dfwiki = get_record('wiki', "id", $this->cm->instance)) {
	            error("Course Module is incorrect");
	    }
	    
	    if (!$this->course = get_record("course", "id", $this->cm->course)) {
			error("Course is misconfigured");
		}
		//for some reason it's necessary to use $course as a global
		$course = $this->course;
	    
	    if (!isset($this->a) || !$this->a) $this->a = $this->cm->instance;
		if (!isset($this->id) || !$this->id) $this->id = $this->cm->id;
		
		/*if (!isset($this->cm->id)) {
			if (! $this->cm = get_coursemodule_from_instance('wiki',$this->dfwiki->id)) {
	            error("Course Module ID was incorrect");
	        }
		} else {
			if (! $this->cm = get_coursemodule_from_id('wiki',$this->cm->id)) {
	            error("Course Module ID was incorrect02");
	        }
		}
		//dfwiki
		if (!isset($this->dfwiki->id)) {
	        if (! $this->dfwiki = get_record('wiki', "id", $this->cm->instance)) {
	            error("Course Module is incorrect");
	        }
		}*/
		
		$this->linkid = $this->cm->id;
		if (isset($this->dfcourse)){
			$this->wikitype = '/course/view.php?id=';
			$this->linkid = $COURSE->id;
		}
		//$this->pagename = wiki_get_real_pagename ($this,$this->dfwiki->pagename);
		
		//load user group data
	    if(isset($this->gid)){
	        $this->groupmember->groupid = $this->gid;
	    }else if(isset($this->dfform['selectgroup'])){
	        $this->groupmember->groupid = $this->dfform['selectgroup'];
	    }else{
	        if (isset($USER->groupmember)) {
		        if (!in_array($this->groupmember,$USER->groupmember)){
		            $this->groupmember->groupid = '0';
		        }
	        }
	        if ($this->cm->id == '0'){
	            $this->groupmember->groupid = '0';
	        }
	        if($this->cm->groupmode != '0'){
	        	$group = get_records('groups_members','userid',$USER->id);
	        	if(is_array($group)){
	        		$this->groupmember->groupid = array_shift($group)->groupid;
	        	}
	        }
	    }
    
	    //load user data
	    if($this->uid){
			$this->member->id = $this->uid;
		}else if(isset($this->dfform['selectstudent'])){
	    	$this->member->id = $this->dfform['selectstudent'];
		}else{
			$this->member->id = $USER->id;
			//for commune wiki or students in group
				if($this->dfwiki->studentmode == '0'){
					$this->member->id = '0';	
				}
		}

		//load teacher page if it's selected
		if(isset($this->dfform['selectteacher'])){
		    $this->member->id = $this->dfform['selectteacher'];   
		    //teacher group (by the moment without group):
		    $this->groupmember->groupid = 0;
		}
	
		$this->wikibook = optional_param('wikibook',NULL,PARAM_CLEANHTML);
	}
	
	/**
	* This function loads all tWShe parametres of wiki storage class needed 
	* to use the wiki.
	* 
	* Usually it will be used like this:
	*    $WS->recover_variables();
	*
	*/
	
	function recover_variables(){
		
		$this->cm = optional_param('cm',NULL,PARAM_FILE);
		//$this->cleanpage = optional_param('cleanpage',NULL,PARAM_FILE);
		$this->gid = optional_param('gid',NULL,PARAM_INT);
		$this->groupmember = optional_param('groupmember',NULL,PARAM_FILE);
		//$this->delpage = optional_param('delpage',NULL,PARAM_FILE);
		$this->dfcontent = optional_param('dfcontent',null,PARAM_INT);
		wiki_dfform_param($this);
		$this->dfformcontent = optional_param('dfformcontent',NULL,PARAM_RAW);
		$this->dfsetup = optional_param('dfsetup',NULL,PARAM_INT);
		$this->enpage = optional_param('enpage',NULL,PARAM_FILE);
	    $this->uid = optional_param('uid',NULL,PARAM_INT);	
		$this->nocontents = optional_param('nocontents',NULL,PARAM_FILE);
		$this->page = optional_param('page',NULL,PARAM_CLEANHTML);
		$this->pageaction = optional_param('pageaction',NULL,PARAM_ALPHA);
		//$this->pagename = optional_param('pagename',NULL,PARAM_FILE);
		$this->updatepage = optional_param('updatepage',NULL,PARAM_FILE);
		$this->ver = optional_param('ver',NULL,PARAM_TEXT);
		$this->wikieditable = optional_param('wikieditable',NULL,PARAM_INT);
	}
	
	/**
	* This function selects the editor of the wiki.
	* 
	* Usually it will be used like this:
	*    $WS->select_editor();
	*
	*/
	
	function select_editor(){
		
		$editor = optional_param('editor',NULL,PARAM_ALPHA);
	
		if (isset($this->dfform['selectedit'])) {
	        switch ($this->dfform['selectedit']) {
	            case '0':
	                $this->pagedata->editor = 'dfwiki';
	                break;
	            case '1':
	                $this->pagedata->editor = 'ewiki';
	                break;
	            case '2':
	                $this->pagedata->editor = 'htmleditor';
	                break;
	            case '3':
	                $this->pagedata->editor = 'nwiki';
	                break;
	            default:
	                error ('No editor was selected');
	                break;
	            }
	    } else if (isset($editor)){
	        $this->pagedata->editor = $editor;
	    }
	}
		
	/**
	* This function loads the data of the wiki, depending on the wiki mode.
	* It considers the wikis with gropus and without them.
	* 
	* Usually it will be used like this:
	*    $WS->load_page_data();
	*
	*/
	
	function load_page_data(){
		global $USER, $CFG, $WS;
		
		//print_object($WS);
		/*
	    if (($this->dfwiki->studentmode == '0') && ($this->dfwiki->groupmode != '0')){   
	        //only by groups:
	        if (!$max = wiki_get_maximum_value_one($this->page,
			    $this->dfwiki->id,$this->groupmember->groupid)){
	            error ('Theres\'s an error in page location');
	        }
	    } else{
	        //by students and their groups:
	        $max = wiki_get_maximum_value_two($this->page,$this->dfwiki->id,$this->groupmember->groupid,$this->member->id);
	        if ($max === false){
	            error ('Theres\'s an error in page location');
	        }
	    }
	
	    //load page's data
	    if ($max){
	        if(($this->dfwiki->studentmode == '0') && ($this->dfwiki->groupmode != '0')){
	            //only by groups
	            $this->pagedata = wiki_get_latest_page_version_one($this->page,$this->dfwiki->id,$max,$this->groupmember->groupid);
	        
	        } else{
	            //by students and their groups
	            $this->pagedata = wiki_get_latest_page_version_two($this->page,$this->dfwiki->id,$max,$this->groupmember->groupid,
					$this->member->id);
					
	            if (isset($this->pagedata->pageaction)){
					print $this->pagedata->pageaction;
	            }
	        }
	    }else{
	        $this->pagedata->pagename = $this->page;
	        $this->pagedata->version = 0;
	        $this->pagedata->created = time();
	        $this->pagedata->editable = $this->dfwiki->editable;
	        $this->pagedata->editor = isset($this->dfform['editor'])?$this->dfform['editor']:'';
	        $this->pagedata->groupid = $this->groupmember->groupid;
	        $this->pagedata->author = $USER->username;
	        $this->pagedata->userid = $USER->id;
	        $this->pagedata->ownerid = $this->member->id;
	    }
	    */
	    
	    $pageinfo = wiki_page_last_version ($this->page);
	    //print_object(false);
	    //echo "\n";
	    if($pageinfo){
	    	$this->pagedata = $pageinfo; 
	    }else{
	    	$this->pagedata->pagename = $this->page;
	        $this->pagedata->version = 0;
	        $this->pagedata->created = time();
	        $this->pagedata->editable = $this->dfwiki->editable;
	        $this->pagedata->editor = isset($this->dfform['editor'])?$this->dfform['editor']:'';
	        $this->pagedata->groupid = $this->groupmember->groupid;
	        $this->pagedata->author = $USER->username;
	        $this->pagedata->userid = $USER->id;
	        $this->pagedata->ownerid = $this->member->id;	
	    }
    	$this->wikibookinfo = wikibook_info($this->page, $this->wikibook);
	}
	
}	
?>

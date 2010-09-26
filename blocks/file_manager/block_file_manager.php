<?php // block_file_manager.php

/**
* @package block_file_manager
* @category block
* @author
* 
*/

/*
* Includes and Requires
*/
require_once($CFG->dirroot.'/blocks/file_manager/lib.php');

/**
* master class for file_manager block
*/
class block_file_manager extends block_list {
	
	/**
	*
	*/
    function init() {
        $this->title = get_string('filemanager', 'block_file_manager');
		$this->content_type = BLOCK_TYPE_TEXT;
        //$this->version = 2008061701;
	$this->version = 2008112401;
    }

    /*
    *
    */
    function preferred_width() {
        return 200;
    }

    /**
    *
    * @uses $USER
    */
    function get_content() {		
		global $USER;
		
		if ($this->content !== NULL) {
			return $this->content;
		}
		$this->content = new stdClass;
		$this->content->items = array();
		$this->content->icons = array();
		$this->content->footer = "";
		 if(!isset($USER->id) || $USER->id == 1) { 
		    $this->content->text = '<div class="description">'.get_string('noaccess','block_mynotes').'</div>';
		  } else {
		if (isloggedin()) {
		    $systemcontext = get_context_instance(CONTEXT_SYSTEM, 0);
		    $isadmin = has_capability('moodle/site:doanything', $systemcontext);
			if ($isadmin) {
				// Creates entries for all three types of users
				$this->fm_make_entries();
			}
			// If some parts are disabled, this will display the Fmanager properly to the user
			$this->fm_check_user_rights();
		
			$this->display_filemanager_link();
			
			$sharedlinks = count_records('fmanager_shared', 'userid', $USER->id);
			$sharedlinks += count_records('fmanager_shared', 'userid', 0);
			if ($sharedlinks > 0){
				$this->display_sharedfiles_link();
			}
			
			if ($isadmin) {
				$this->display_admin_config();
			}
		}
	}	
		return $this->content;
	}
	
	/**
	*
	* @uses $CFG
	* @uses $USER
	*/
	function display_filemanager_link() {
		global $CFG, $USER;

        if (! $course = get_record('course', 'id', $this->instance->pageid)) {
            error("Course ID is incorrect");
        }

        $coursecontext = get_context_instance(CONTEXT_COURSE, $this->instance->pageid);
        $canmanagegroups = has_capability('block/file_manager:canmanagegroups', $coursecontext);

        $this->content->items[]="<a title=\"".get_string('msgfilemanager', 'block_file_manager')."\" href=\"{$CFG->wwwroot}/blocks/file_manager/view.php?id={$this->instance->pageid}&groupid=0\">".get_string('myfiles', 'block_file_manager')."</a>";
        $this->content->icons[]="<img src=\"{$CFG->pixpath}/i/files.gif\" alt=\"\" />";

        // If the user is member of any group of this course, links for each group in which he belongs must be displayed
        $groupmode = groups_get_course_groupmode($course);
        $groupsarray = array();

        switch ($groupmode) {
            case NOGROUPS :
		    // Nothing to display
		    break;
	    case SEPARATEGROUPS :
		    if ($canmanagegroups){ // Displays all groups because of super rights
			    $groupsarray = groups_get_all_groups($this->instance->pageid);
		    } else { // Display only links for groups in which the user is member
			    $groupsarray = groups_get_all_groups($this->instance->pageid, $USER->id);
		    }
		    break;
	    case VISIBLEGROUPS :
		    // Display a link for all groups
		    $groupsarray = groups_get_all_groups($this->instance->pageid);
		    break;
	  }
	  // Displays group links if user in a group.
	  if (is_array($groupsarray)){
		  foreach ($groupsarray as $groupid => $value){
			  $this->content->items[]="<a title=\"".get_string('msgfilemanagergroup', 'block_file_manager')."\" href=\"{$CFG->wwwroot}/blocks/file_manager/view.php?id={$this->instance->pageid}&groupid={$groupid}\">".groups_get_group_name($groupid)."</a>";
			  $this->content->icons[]="<img src=\"$CFG->pixpath/i/files.gif\" alt=\"\" />";
		  }
	  }
	}
	
	/**
	*
	* @uses $CFG
	* @uses $USER
	*/
	function display_sharedfiles_link() {
		global $CFG, $USER;
		
		$strnewshared = '';
		if ($tmp = count_records('fmanager_shared', 'userid', $USER->id, 'viewed', 0, 'course', $this->instance->pageid)) {
			$strnewshared = " ($tmp)<img src=\"{$CFG->wwwroot}/blocks/file_manager/pix/new.gif\" alt=\"".get_string('new', 'block_file_manager')."\" />";
		}
		
		$this->content->items[]="<a title=\"" . get_string('msgfilesshared','block_file_manager') . "\" href=\"{$CFG->wwwroot}/blocks/file_manager/view_shared.php?id={$this->instance->pageid}\">".get_string('sharedfiles', 'block_file_manager')."</a>$strnewshared";
		$this->content->icons[]="<img src=\"{$CFG->pixpath}/i/files.gif\" alt=\"\" />";
	}
	
	/**
	*
	*/
	function display_admin_config() {
		global $CFG;
		
		$teachedit = get_string('uploadsettings', 'block_shared_files');
		$this->content->items[]="<a title=\"" . get_string('msgadminsettings','block_file_manager') . "\" href=\"$CFG->wwwroot/blocks/file_manager/admin_settings.php?id=".$this->instance->pageid."&tab=files&tab2=students\">".get_string('adminsettings', 'block_file_manager')."</a>";
		$this->content->icons[]="<img src=\"$CFG->pixpath/i/settings.gif\" alt=\"\" />";
	}
	
	/**
	*
	*/
	function fm_get_user_int_type() {
	    $systemcontext = get_context_instance(CONTEXT_SYSTEM, 0);
	    $isadmin = has_capability('moodle/site:doanything', $systemcontext);
		if ($isadmin) {
			return 0;
		} else if (isteacherinanycourse('0', false)) {
			return 1;
		} else {
			return 2;
		}
	}
	
	/**
	*
	*/
	function fm_make_entries() {
		
		for ($x = 0 ; $x <= 2; $x++) {
			if (!$tmp = get_record('fmanager_admin', 'usertype', $x)) {		
				$create = NULL;
				$create->usertype = (int)$x;
				$create->maxupload = get_max_upload_file_size();
				if (!insert_record('fmanager_admin', $create)) {
					//error(get_string("errnoinsert",'block_file_manager'));
				}								
			}
		}
	}
	
	/**
	*
	* @uses $USER
	*/	
	function fm_check_user_rights() {
		global $USER;
		
		// Wont display fmanager to those it has been disabled for
		$userinttype = $this->fm_get_user_int_type();
		$systemcontext = get_context_instance(CONTEXT_SYSTEM, 0);
		$isadmin = has_capability('moodle/site:doanything', $systemcontext);

		$tmp = get_record('fmanager_admin', 'usertype', $userinttype);
		if ($tmp->enable_fmanager == 0) {
			// Still shows shared files
			$sharedlinks = count_records('fmanager_shared', 'userid', $USER->id);
			if ($sharedlinks > 0){
				$this->display_sharedfiles_link();
			}
			if ($isadmin) {
				$this->display_admin_config();
			}
			return $this->content;
		}		
	}
	
	/*prevent block from showing in mymoodle*/
	function applicable_formats() {
        return array('site' => false, 'course' => true);
    }

}
?>
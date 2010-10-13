<?php // $Id: block_openshare.php, v 0.5 10/01/2008 21:22PM jstein Exp $ 
      // /blocks/openshare/block_openshare.php - created for Moodle 1.9
	  
class block_openshare extends block_list {
    function init() {
        $this->title = get_string('openshare','block_openshare');
        $this->version = 2008092300;
    }

    function get_content() {
        global $CFG, $USER, $SITE, $course, $context;
		
	    $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
		
		//if Course web site...
		if (($course->id !== SITEID) and (has_capability('moodle/course:update', $context))) {
		
        $this->title .= ' ' . helpbutton('openshare','OpenShare','block_openshare',true,false,'',true);
			//Query for OpenShare enabled at course level
			$opencourse = get_record("block_openshare_courses", "courseid", $course->id);
			
			//If OpenShare enabled on this course...
			if ($opencourse->status == 1) {
				//Link to update OpenShare status of course modules en masse
				$this->content->items[]='<a href="'.$CFG->wwwroot.'/blocks/openshare/open_mods_set.php?id='.$this->instance->pageid.'&amp;sesskey='.sesskey().'">'.get_string('openmodsset','block_openshare').'</a>';
				$this->content->icons[]='<img src="'.$CFG->wwwroot.'/blocks/openshare/images/open.png" alt="cc" />';

				//Link to update teacher and students membership in this course's OpenShare group, Course Members
				$this->content->items[]='<a href="'.$CFG->wwwroot.'/blocks/openshare/group_members.php?id='.$this->instance->pageid.'&amp;sesskey='.sesskey().'">'.get_string('updatemembers','block_openshare').'</a>';
$this->content->icons[]='<img src="'.$CFG->pixpath.'/c/user.gif" alt="user" />';
				$this->content->icons[]='<img src="'.$CFG->pixpath.'/i/cross_red_small.gif" alt="no" />';

				//Link to disable OpenShare for this courses
				$this->content->items[]='<a href="'.$CFG->wwwroot.'/blocks/openshare/open_course_set.php?id='.$this->instance->pageid.'&amp;open=0&amp;sesskey='.sesskey().'">'.get_string('disablecourse','block_openshare').'</a>';
			} elseif ($opencourse->status < 1) {
				//Link to enable OpenShare for this course
				$this->content->icons[]='<img src="'.$CFG->pixpath.'/i/tick_green_small.gif" alt="yes" />';
				$this->content->items[]='<a href="'.$CFG->wwwroot.'/blocks/openshare/open_course_set.php?id='.$this->instance->pageid.'&amp;open=1&amp;sesskey='.sesskey().'">'.get_string('enablecourse','block_openshare').'</a>';
			}
		} else {
			//if not in a course, show the OpenShare courses RSS feed
			//$this->content->items[]='<a href="'.$CFG->wwwroot.'/rss/xml_generate.php">'.get_string('coursesfeed','block_openshare').'</a>';
			//$this->content->icons[]='<img src="'.$CFG->pixpath.'/i/rss.gif" alt="rss" />';
		
			/*
			if(has_capability('moodle/course:update', $context){
				//Update OpenShare status of course modules en masse
				$this->content->items[]='<a href="'.$CFG->wwwroot.'/blocks/openshare/open_mods_set.php">'.get_string('openmodsset','block_openshare').'</a>';
				$this->content->icons[]='<img src="'.$CFG->wwwroot.'/blocks/openshare/images/cc.png" alt="cc" />';
			}*/
		}
		$this->content->footer = '<div style="font-size: 75%; margin: .5em 0;"><a href="http://flexknowlogy.learningfield.org/addons/openshare/">'.get_string('blockfooter','block_openshare').'</a></div>';
	}
	
	function instance_allow_config() {
    	return false;
	}
	
	function after_install() {
		//currently after install we should require upgrade.php to run
		//test to see if this works!!
		
		$openrole = get_record("role", "shortname", "openlearner");
		
		if (empty($openrole)) {
			$roleid = create_role('Open Learner', 'openlearner', 'Open Learners have more privileges than a guest but less than a student. Custom role for OpenShare modification.');
			assign_capability("gradereport/user:view", 1, $roleid, 1);
			assign_capability("mod/assignment:submit", -1, $roleid, 1);
			assign_capability("mod/assignment:view", 1, $roleid, 1);
			assign_capability("mod/chat:chat", -1, $roleid, 1);
			assign_capability("mod/chat:readlog", -1, $roleid, 1);
			assign_capability("mod/choice:choose", 1, $roleid, 1);
			assign_capability("mod/data:comment", -1, $roleid, 1);
			assign_capability("mod/data:viewentry", 1, $roleid, 1);
			assign_capability("mod/data:writeentry", -1, $roleid, 1);
			assign_capability("mod/forum:createattachment", -1, $roleid, 1);
			assign_capability("mod/forum:deleteownpost", 1, $roleid, 1);
			assign_capability("mod/forum:initialsubscriptions", 1, $roleid, 1);
			assign_capability("mod/forum:replypost", 1, $roleid, 1);
			assign_capability("mod/forum:startdiscussion", 1, $roleid, 1);
			assign_capability("mod/forum:throttlingapplies", 1, $roleid, 1);
			assign_capability("mod/forum:viewdiscussion", 1, $roleid, 1);
			assign_capability("mod/forum:viewrating", 1, $roleid, 1);
			assign_capability("mod/glossary:comment", 1, $roleid, 1);
			assign_capability("mod/glossary:write", 1, $roleid, 1);
			assign_capability("mod/hotpot:attempt", 1, $roleid, 1);
			assign_capability("mod/lams:participate", 1, $roleid, 1);
			assign_capability("mod/quiz:attempt", 1, $roleid, 1);
			assign_capability("mod/quiz:view", 1, $roleid, 1);
			assign_capability("mod/scorm:savetrack", 1, $roleid, 1);
			assign_capability("mod/scorm:skipview", 1, $roleid, 1);
			assign_capability("mod/scorm:viewscores", 1, $roleid, 1);
			assign_capability("mod/survey:participate", 1, $roleid, 1);
			assign_capability("mod/wiki:participate", 1, $roleid, 1);
			assign_capability("mod/workshop:participate", -1, $roleid, 1);
			assign_capability("moodle/block:view", 1, $roleid, 1);
			assign_capability("moodle/blog:view", -1, $roleid, 1);
			assign_capability("moodle/calendar:manageownentries", 1, $roleid, 1);
			assign_capability("moodle/course:useremail", 1, $roleid, 1);
			assign_capability("moodle/course:view", 1, $roleid, 1);
			assign_capability("moodle/course:viewparticipants", -1, $roleid, 1);
			assign_capability("moodle/course:viewscales", 1, $roleid, 1);
			assign_capability("moodle/grade:view", 1, $roleid, 1);
			assign_capability("moodle/legacy:student", 1, $roleid, 1);
			assign_capability("moodle/site:viewparticipants", -1, $roleid, 1);
			assign_capability("moodle/user:changeownpassword", 1, $roleid, 1);
			assign_capability("moodle/user:editownprofile", 1, $roleid, 1);
			assign_capability("moodle/user:readuserblogs", -1, $roleid, 1);
			assign_capability("moodle/user:readuserposts", -1, $roleid, 1);
			assign_capability("moodle/user:viewdetails", -1, $roleid, 1);
		}
	}
}//end class block_openshare extends block_list


?>

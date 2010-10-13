<?php // $Id: open_course_set.php, v 0.5 10/01/2008 21:22PM jstein Exp $ 
      // /blocks/openshare/open_course_set.php - created for Moodle 1.9

//Moodle standards scripts
require_once ("../../config.php");
require_once ("../../course/lib.php");

//block OpenShare library
require_once("locallib.php");

/**
* Updates Open Course settings for the course OR update module settings with license or grouping
*/

//possible parameters
$modid = optional_param('cmid', 0, PARAM_INT);
$open = optional_param('open', -1, PARAM_INT);
$license = optional_param('license', 0, PARAM_INT);
$status = optional_param('status', 0, PARAM_INT);

$courseid = optional_param('id', 0, PARAM_INT);
$id = $courseid;

$course = get_record('course', 'id', $courseid);

$context = get_context_instance(CONTEXT_COURSE, $courseid);
require_login($courseid);

print_header(get_string('openshare','block_openshare').' '.$course->fullname,'' ,
                 '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$id.'">'.$course->shortname.'</a> ->'.get_string('openmodsset','block_openshare'));

print_heading_with_help(get_string('openmodsset','block_openshare'),'openshare','block_openshare');
print_simple_box_start("center");

//We may need to evaluate and replace this
if(!has_capability('moodle/course:update', $context)){
	print_error("You don't have privileges to use this page.");
}

//Query for OpenShare block installed at server level
$openshare = get_record("block", "name", "openshare");

//Query for OpenShare enabled at course level
$opencourse = get_record("block_openshare_courses", "courseid", $courseid);

//see if OpenShare course setting is being changed
if ($open>-1 && $openshare->visible==1) {
	//print "Current Status: ".$opencourse->status;
	
	//Data object for block_openshare_courses updates with basic properties
	$newopencourse = new object();
    $newopencourse->courseid = $courseid;
    $newopencourse->status = $open;
	$newopencourse->timemodified = time();

	//If this is set to enable OpenShare, check for groups &  groupings
	if ($open==1){
	
		//check status of group, grouping, and groupinggroup association
		$opengroup = get_record("groups", "courseid", $courseid, "name", "Course Members");
		$opengrouping = get_record("groupings", "courseid", $courseid, "name", "Closed");
		$opengroupinggroup = get_record("groupings_groups", "groupingid", $opengrouping->id, "groupid", $opengroup->id);

		//check for group "Course Members"
		if(empty($opengroup->id)){
			//create if it does not exist
			$newgroup = new object();
			$newgroup->timemodified = time();
	    	$newgroup->courseid = $courseid;
	    	$newgroup->name = "Course Members";
			insert_record("groups", $newgroup);
			$opengroup = get_record("groups", "courseid", $courseid, "name", "Course Members");
		}
	
		//check for grouping "Closed"
		if(empty($opengrouping->id)){
			//create if it does not exist
			$newgrouping = new object();
			$newgrouping->timemodified = time();
	    	$newgrouping->courseid = $courseid;
	    	$newgrouping->name = "Closed";
			insert_record("groupings", $newgrouping);
			$opengrouping = get_record("groupings", "courseid", $courseid, "name", "Closed");
		}

		//check for grouping > group association
		if(empty($opengroupinggroup->id)){
			//create if it does not exist
			$newgroupinggroup = new object();
			$newgroupinggroup->timemodified = time();
	    	$newgroupinggroup->groupingid = $opengrouping->id;
	    	$newgroupinggroup->groupid = $opengroup->id;
			insert_record("groupings_groups", $newgroupinggroup);
		}

		//check for existiting course record in block_openshare_courses
		//update or insert
		if ($opencourse->status>-1){
	    	$newopencourse->id = $opencourse->id;
			update_record("block_openshare_courses", $newopencourse);
		} else {
			insert_record("block_openshare_courses", $newopencourse);
		}
		
		//Enroll users (students, teachers, editing teachers) in Course Members Group 
		block_openshare_updategroup($courseid,$opengroup->id); //function from locallib.php
		
		//Query for Open Learner role
		$openlearner = get_record("role", "shortname", "openlearner");
		
		//change default role to student and make unenrollable
		if (!empty($openlearner)){
			$defaultrole = new object();
			$defaultrole->id = $courseid;
			$defaultrole->timemodified = time();
			$defaultrole->defaultrole = $openlearner->id;
			update_record("course", $defaultrole);
		}
		
        print get_string('openshareenabled','block_openshare');
        print get_string('ensureenable','block_openshare');
		
	} elseif ($open==0){
		//Disable OpenShare
		//This updates db table, but that's about it.
		$newopencourse->id = $opencourse->id;
		update_record("block_openshare_courses", $newopencourse);

		//We don't delete Group, Grouping.
		//We don't eliminate Open Learners, though we could.
		//Delete Group and Grouping
		//if($opengroup->id<1) delete_records("groups","courseid",$courseid, "name", "Course Members");
		//if($opengrouping->id<1) delete_records("groupings","courseid",$courseid, "name", "Closed");
		//if($opengroupinggroup->id<1) delete_records("groupings_groups","groupid",$courseid, "name", "Closed");

        print get_string('opensharedisabled','block_openshare');
	}
} else {
		print_error(get_string('openshareinvis','block_openshare'));
}
//end OpenShare enabler\

rebuild_course_cache($courseid);
if (SITEID == $courseid) {
	redirect($CFG->wwwroot);
} else {
		print_continue($CFG->wwwroot.'/course/view.php?id='.$courseid);
}
exit;

    print_simple_box_end();

    //Print footer
    print_footer();
?>
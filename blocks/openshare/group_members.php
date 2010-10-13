<?php // $Id: block_openshare.php, v 0.5 10/01/2008 21:22PM jstein Exp $ 
      // /blocks/openshare/block_openshare.php - created for Moodle 1.9

//Define some globals for all the script
require_once ("../../config.php");
require_once ("../../course/lib.php");

//block OpenShare library
require_once ("locallib.php");

$courseid = optional_param('id', 0, PARAM_INT);
$id = $courseid;

$course = get_record('course', 'id', $courseid);

$context = get_context_instance(CONTEXT_COURSE, $courseid);
require_login($courseid);

print_header(get_string('openshare','block_openshare').' '.$course->fullname,'' ,
                 '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$id.'">'.$course->shortname.'</a> ->'.get_string('openmodsset','block_openshare'));

print_heading($course->fullname.' ('.$course->shortname.')');
print_simple_box_start("center");

//Get Course Members Group ID
$opengroup = get_record("groups", "courseid", $id, "name", "Course Members");
//print 'Course ID: '.$id.', Group ID: '.$opengroup->id;

if(!empty($opengroup->id)){
	//Enroll users (students, teachers, editing teachers) in Course Members Group 
	block_openshare_updategroup($id,$opengroup->id); //function from locallib.php
	
	rebuild_course_cache($courseid);
	if (SITEID == $courseid) {
		redirect($CFG->wwwroot);
	} else {
		
        print get_string('membersupdated','block_openshare');
	redirect($CFG->wwwroot.'/course/view.php?id='.$courseid);
	}
	exit;
} else {
	print_error("The Course Members Group does not exist! Please re-enable Open Course in the openshare block.\n\n");
}

	
    print_simple_box_end();

    //Print footer
    print_footer();
?>
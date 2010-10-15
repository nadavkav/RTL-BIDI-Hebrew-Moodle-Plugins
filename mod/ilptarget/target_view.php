<?php

/*

 * @copyright &copy; 2007 University of London Computer Centre

 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk

 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License

 * @package ILP

 * @version 1.0

 */



    require_once("../../config.php");
    require_once("lib.php");
    require_once($CFG->dirroot.'/blocks/ilp/block_ilp_lib.php');
    global $CFG, $USER;



   $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or

    $a  = optional_param('a', 0, PARAM_INT);  // target ID

	$userid = optional_param('userid', 0, PARAM_INT); //User's targets we wish to view

    $courseid     = optional_param('courseid', SITEID, PARAM_INT);

	$status = optional_param('status', 0, PARAM_INT);

	$action = optional_param('action',NULL, PARAM_CLEAN);

	$targetpost = optional_param('targetpost', -1, PARAM_INT);



	require_login();



    add_to_log($userid, "target", "view", "view.php", "$userid");

        $sitecontext = get_context_instance(CONTEXT_SYSTEM);

/// Print the main part of the page

	if ($userid > 0){

		$user = get_record('user', 'id', ''.$userid.'');

	}else{

		$user = $USER;

	}

	$strtargets = get_string("modulenameplural", "ilptarget");
    $strtarget  = get_string("modulename", "ilptarget");
    $strilp = get_string("ilp", "block_ilp");
	$strilps = get_string("ilps", "block_ilp");
    $stredit = get_string("edit");
    $strdelete = get_string("delete");
    $strcomments = get_string("comments", "ilptarget");

	$navlinks = array();

	if($id != 0){ //module is accessed through a course module use course context

		if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $target = get_record("ilptarget", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

		$context = get_context_instance(CONTEXT_MODULE, $cm->id);

		$link_values = '?id='.$cm->id.'&amp;userid='.$user->id;

		$navlinks[] = array('name' => $course->shortname, 'link' => "$CFG->wwwroot/course/view.php?id=$course->id", 'type' => 'misc');

		$title = "$strtargets: ".fullname($user);

		$footer = $course;

    }elseif ($courseid != SITEID) { //module is accessed via report from within course

		$course = get_record('course', 'id', $courseid);

		$context = get_context_instance(CONTEXT_COURSE, $course->id);

		$link_values = '?courseid='.$course->id.'&amp;userid='.$user->id;

		$navlinks[] = array('name' => $course->shortname, 'link' => "$CFG->wwwroot/course/view.php?id=$course->id", 'type' => 'misc');

		$title = "$strtargets: ".fullname($user);

		$footer = $course;

	}else{ //module is accessed independent of a course use user context

		if($user->id == $USER->id) {
			$context = get_context_instance(CONTEXT_SYSTEM);
		}else{
			$context = get_context_instance(CONTEXT_USER, $user->id);
		}

		$link_values = '?userid='.$user->id;

		$title = "$strtargets: ".fullname($user);

		$footer = '';

	}

	$navlinks[] = array('name' => $strilps, 'link' => "$CFG->wwwroot/blocks/ilp/list.php?courseid=$courseid", 'type' => 'misc');

	$navlinks[] = array('name' => $strilp, 'link' => "$CFG->wwwroot/blocks/ilp/view.php?id=$user->id&amp;courseid=$courseid", 'type' => 'misc');

	$navlinks[] = array('name' => fullname($user), 'link' => FALSE, 'type' => 'misc');

	$navlinks[] = array('name' => $strtargets, 'link' => FALSE, 'type' => 'misc');

	$navigation = build_navigation($navlinks);
	print_header_simple($title, '', $navigation,'', '', true, '','');

	//Allow users to see their own profile, but prevent others



	if (has_capability('moodle/legacy:guest', $context, NULL, false)) {

        error("You are logged in as Guest.");

       }

if ($action == 'updatestatus') {
    require_once("$CFG->dirroot/message/lib.php");

	$report = get_record('ilptarget_posts', 'id', $targetpost);  // Get or make one
	$report->status      = $status;
	$report->timemodified   = time();

	update_record('ilptarget_posts', addslashes_object($report));

	if($CFG->ilptarget_send_target_message == 1){

			switch($status) {
				case "0":
					$thistargetstatus = get_string('outstanding', 'ilptarget');
					break;
				case "1":
					$thistargetstatus = get_string('achieved', 'ilptarget');
					break;
				//case "2":
					//$thistargetstatus = get_string('notachieved', 'ilptarget');
					//break;
				case "3":
					$thistargetstatus = get_string('withdrawn', 'ilptarget');
					break;
			}

			$updatedstatus = get_string('statusupdate', 'ilptarget', $thistargetstatus);

			//Sets message details for Targets
			$messagefrom = get_record('user', 'id', $USER->id);
			$messageto = get_record('user', 'id', $userid);
			$targeturl = $CFG->wwwroot.'/mod/ilptarget/target_view.php?'.(($courseid)?'courseid='.$courseid.'&amp;' : '').'userid='.$id.'">';
            $targetview = get_string('targetviewlink','ilptarget');
			$updatedstatus = get_string('statusupdate', 'ilptarget', $thistargetstatus);

			$message = '<p>'.$updatedstatus.'<br /><a href="'.$targeturl.'&amp;status='.$status.'">'.$targetview.'</a></p>';
			message_post_message($messagefrom, $messageto, $message, FORMAT_HTML, 'direct');
		}
}

$mform = new ilptarget_updatetarget_form('', array('userid' => $user->id, 'id' => $id, 'courseid' => $courseid, 'targetpost' => $targetpost, 'linkvalues' => $link_values));

if(!$mform->is_cancelled() && $fromform = $mform->get_data()){
	$mform->process_data($fromform);
}
if($action == 'delete'){ //Check to see if we are deleting a comment
	$report = get_record('ilptarget_posts', 'id', $targetpost);
    delete_records('ilptarget_posts', 'id', $report->id);
    delete_records('ilptarget_comments', 'targetpost', $report->id, 'userid', $user->id);
    delete_records('event', 'name', $report->name, 'instance', $report->id, 'userid', $user->id);
}
if($action == 'updatetarget'){
	print_heading(get_string('add','ilptarget'));
	$mform->display();
}else{

	if($USER->id != $user->id){
		require_capability('mod/ilptarget:view', $context);
		print_heading(get_string('targetreports', 'ilptarget', fullname($user)));
	}else{
		print_heading(get_string('mytargets', 'ilptarget'));
	}

	$tabs = array();
	$tabrows = array();

	$tabrows[] = new tabobject('0', "$link_values&amp;status=0", get_string('outstanding', 'ilptarget'));
	$tabrows[] = new tabobject('1', "$link_values&amp;status=1", get_string('achieved', 'ilptarget'));
	//$tabrows[] = new tabobject('2', "$link_values&amp;status=2", get_string('notachieved', 'ilptarget'));
	$tabrows[] = new tabobject('3', "$link_values&amp;status=3", get_string('withdrawn', 'ilptarget'));
	$tabs[] = $tabrows;

	print_tabs($tabs, $status);

	display_ilptarget ($user->id,$courseid,TRUE,FALSE,FALSE,$sortorder='ASC',0,$status);

	if(has_capability('mod/ilptarget:addtarget', $context) || ($USER->id == $user->id && has_capability('mod/ilptarget:addowntarget', $context))) {

		echo '<div class="addbox">';
		echo '<a href="'.$link_values.'&amp;action=updatetarget">'.get_string('add', 'ilptarget').'</a></div>';
	}
}

/// Finish the page

    print_footer($footer);

?>
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

    global $CFG, $USER;

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // concern ID
	$concernspost = optional_param('concernspost', 0, PARAM_INT); //User's concern
	$commentid = optional_param('commentid', 0, PARAM_INT); //Comment
	$courseid = optional_param('courseid', SITEID, PARAM_INT);
	$action = optional_param('action',NULL, PARAM_CLEAN);

	require_login();

	$post = get_record('ilpconcern_posts', 'id', ''.$concernspost.'');
	$user = get_record('user', 'id',$post->setforuserid);
	$posttutor = get_record('user', 'id', ''.$post->setbyuserid.'');

    add_to_log($user->id, "concerncomment", "view", "view.php", "$concernspost");

/// Print the main part of the page
	$strconcerns = get_string("modulenameplural", "ilpconcern");
    $strconcern  = get_string("modulename", "ilpconcern");
    $strilp = get_string("ilp", "block_ilp");
	$strilps = get_string("ilps", "block_ilp");
    $stredit = get_string("edit");
    $strdelete = get_string("delete");
    $strcomments = get_string("comments", "ilpconcern");

	$navlinks = array();

	if($id != 0){ //module is accessed through a course module use course context

		if (! $cm = get_record("course_modules", "id", $id)) {

            error("Course Module ID was incorrect");

        }



        if (! $course = get_record("course", "id", $cm->course)) {

            error("Course is misconfigured");

        }



        if (! $concern = get_record("ilpconcern", "id", $cm->instance)) {

            error("Course module is incorrect");

        }

		$context = get_context_instance(CONTEXT_MODULE, $cm->id);

		$link_values = '?id='.$cm->id.'&amp;concernspost='.$concernspost;

		$navlinks[] = array('name' => $course->shortname, 'link' => "$CFG->wwwroot/course/view.php?id=$course->id", 'type' => 'misc');

		$title = "$strconcerns: ".fullname($user);

		$footer = $course;

    }elseif ($courseid != SITEID) { //module is accessed via report from within course
		$course = get_record('course', 'id', $courseid);

		$context = get_context_instance(CONTEXT_COURSE, $course->id);

		$link_values = '?courseid='.$course->id.'&amp;concernspost='.$concernspost;
		$navlinks[] = array('name' => $course->shortname, 'link' => "$CFG->wwwroot/course/view.php?id=$course->id", 'type' => 'misc');

		$title = "$strconcerns: ".fullname($user);
		$baseurl = $CFG->wwwroot.'/mod/ilpconcern/view.php?id='.$id.'&amp;userid='.$user->id;

		$footer = $course;
	}else{ //module is accessed independent of a course use user context

		if($user->id == $USER->id) {
			$context = get_context_instance(CONTEXT_SYSTEM);
		}else{
			$context = get_context_instance(CONTEXT_USER, $user->id);
		}

		$link_values = '?concernspost='.$concernspost;

		$title = "$strconcerns: ".fullname($user);

		$footer = '';

	}

	$navlinks[] = array('name' => $strilps, 'link' => "$CFG->wwwroot/blocks/ilp/list.php?courseid=$courseid", 'type' => 'misc');

	$navlinks[] = array('name' => $strilp, 'link' => "$CFG->wwwroot/blocks/ilp/view.php?id=$user->id&amp;courseid=$courseid", 'type' => 'misc');

	$navlinks[] = array('name' => fullname($user), 'link' => FALSE, 'type' => 'misc');

	$navlinks[] = array('name' => $strconcerns, 'link' => FALSE, 'type' => 'misc');

	$navlinks[] = array('name' => $strcomments, 'link' => FALSE, 'type' => 'misc');

	$navigation = build_navigation($navlinks);
	print_header_simple($title, '', $navigation,'', '', true, '','');



	//Allow users to see their own profile, but prevent others



	if (has_capability('moodle/legacy:guest', $context, NULL, false)) {

        error("You are logged in as Guest.");

       }



	if($USER->id != $user->id){

		require_capability('mod/ilpconcern:view', $context);

	}

		$mform = new ilpconcern_updatecomment_form('', array('userid' => $user->id, 'id' => $id, 'courseid' => $courseid, 'concernspost' => $concernspost, 'commentid' => $commentid));

				echo '<div class="ilp_post yui-t4">';
				   echo '<div class="bd" role="main">';
					echo '<div class="yui-main">';
					echo '<div class="yui-b">';
					if(isset($post->name)){
						echo '<div class="yui-gd">';
						echo '<div class="yui-u first">';
						echo get_string('name', 'ilpconcern');
						echo '</div>';
						echo '<div class="yui-u">';
						echo $post->name;
						echo '</div>';
					echo '</div>';
					}
				echo '<div class="yui-gd">';
					echo '<div class="yui-u first">';
					echo '<p>'.get_string('report'.($post->status + 1),'ilpconcern').'</p>';
						echo '</div>';
					echo '<div class="yui-u">';
					echo '<p>'.$post->concernset.'</p>';
						echo '</div>';
				echo '</div>';
				echo '</div>';
					echo '</div>';
					echo '<div class="yui-b">';
					echo '<ul>';
					echo '<li>'.get_string('setby', 'ilpconcern').': '.fullname($posttutor);
					if($post->courserelated == 1){
						$targetcourse = get_record('course','id',$post->targetcourse);
						echo '<li>'.get_string('course').': '.$targetcourse->shortname.'</li>';
					}
					echo '<li>'.get_string('set', 'ilpconcern').': '.userdate($post->timecreated, get_string('strftimedateshort'));
					echo '<li>'.get_string('deadline', 'ilpconcern').': '.userdate($post->deadline, get_string('strftimedateshort'));
					echo '</ul>';

					$commentcount = count_records('ilpconcern_comments', 'concernspost', $post->id);

					echo '<div class="commands"><a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_comments.php?'.(($courseid > 1)?'courseid='.$courseid.'&amp;' : '').'userid='.$id.'&amp;concernspost='.$post->id.'">'.$commentcount.' '.get_string("comments", "ilpconcern").'</a> ';

					if($post->status == 0 || has_capability('moodle/site:doanything', $context)){
						echo ilpconcern_update_menu($post->id,$context,($post->status + 1));
					}
					echo '</div>';

					if($post->status == 1){
						echo '<img class="achieved" src="'.$CFG->pixpath.'/mod/ilpconcern/achieved.gif" alt="" />';
					}
					echo '</div>';
					echo '</div>';
				echo '</div>';

	if(!$mform->is_cancelled() && $fromform = $mform->get_data()){
		$mform->process_data($fromform);
	}
	if($action == 'delete'){ //Check to see if we are deleting a comment
		delete_records('ilpconcern_comments', 'id', $commentid);
	}
	if($action == 'updatecomment'){
		print_heading(get_string('addcomment', 'ilpconcern'));
		$mform->display();
	}else{

		print_heading(get_string('concerncomments', 'ilpconcern'));
		$comments = get_records('ilpconcern_comments', 'concernspost',$concernspost);

        $stryes = get_string('complete', 'ilpconcern');
		$strdelete = get_string('delete');
		$stredit = get_string('edit');
        $strenter  = get_string('update');

		echo '<div class="ilpcenter">';

		if ($comments !== false) {

            foreach ($comments as $comment) {

			$commentuser = get_record('user','id',$comment->userid);

			echo '<div class="forumpost ilpcomment boxaligncenter">'.format_text($comment->comment, $comment->format).'<div class="commands">'.fullname($commentuser).', '.userdate($comment->created, get_string('strftimedate')).'<br />'.ilpconcern_update_comment_menu($comment->id,$context).'</div></div>';

            }

        }

		echo '</div>';

		if(has_capability('mod/ilpconcern:addcomment', $context) || ($USER->id == $user->id && has_capability('mod/ilpconcern:addowncomment', $context))) {

		echo '<div class="addbox">';

		echo '<a href="'.$link_values.'&amp;action=updatecomment">'.get_string('addcomment', 'ilpconcern').'</a></div>';

		}
	}


	//add_to_log($course->id, 'comment', 'view',

         //  'view.php?id='.$cm->id.'&concernspost='.$concernspost.'&mode=student', fullname($USER), $cm->id);

/// Finish the page

    print_footer($footer);

?>
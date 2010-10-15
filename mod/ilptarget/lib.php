<?php


require_once($CFG->libdir.'/formslib.php');
$target_CONSTANT = 7;     /// for example

/**
 * Creates form to add/update a target
 */

class ilptarget_updatetarget_form extends moodleform {

    function definition() {
        global $USER, $CFG;
        require_once("$CFG->dirroot/blocks/ilp/block_ilp_lib.php");

        $mform    =& $this->_form;

        $courseid = $this->_customdata['courseid'];
        $userid = $this->_customdata['userid'];
        $id = $this->_customdata['id'];
        $targetpost = $this->_customdata['targetpost'];

        $user = get_record('user','id',$userid);

        if($targetpost > 0){
            $report = get_record('ilptarget_posts', 'setforuserid', $userid, 'id', $targetpost);
        }

        if($user->id == $USER->id){
            $mform->addElement('header', 'title', get_string('mytarget', 'ilptarget'));
        }else{
            $mform->addElement('header', 'title', get_string('targetfor', 'ilptarget', fullname($user)));
        }

        $mform->addElement('hidden', 'userid', $userid);
        if($courseid != SITEID){
            $mform->addElement('hidden', 'courseid', $courseid);
        }
        if($id > 0){
            $mform->addElement('hidden', 'id', $id);
        }
        if($targetpost > 0 && $report){
            $mform->addElement('hidden', 'targetpost', $targetpost);
        }

		$mform->addElement('text', 'name', get_string('name', 'ilptarget'),array('size'=>'60'));
        $mform->addRule('name', null, 'required', null, 'client');
		if($targetpost > 0 && $report){
            $mform->setDefault('name', $report->name);
        }

        $mform->addElement('checkbox', 'courserelated', get_string('courserelated', 'ilptarget'));
        if($targetpost > 0 && $report){
            $mform->setDefault('courserelated', $report->courserelated);
        }
        $ilpcourses = get_my_ilp_courses($user->id);
        $options = array();
        foreach ($ilpcourses as $ilpcourse) {
            $options[$ilpcourse->id] = $ilpcourse->shortname;
        }
        $mform->addElement('select', 'targetcourse', get_string('course'), $options);
        $mform->disabledIf('targetcourse', 'courserelated');
        if($targetpost > 0 && $report){
            $mform->setDefault('targetcourse', $report->targetcourse);
        }else{
            $mform->setDefault('targetcourse',$courseid);
        }
        $mform->addElement('htmleditor', 'targetset', get_string('targetagreed', 'ilptarget'));
        $mform->setType('targetset', PARAM_RAW);
        $mform->addRule('targetset', null, 'required', null, 'client');
        $mform->setHelpButton('targetset', array('writing', 'richtext'), false, 'editorhelpbutton');
        if($targetpost > 0 && $report){
            $mform->setDefault('targetset', $report->targetset);
        }else{
            if($CFG->ilptarget_use_template == 1){
                $template = stripslashes($CFG->ilptarget_template);
            }else{
                $template = '';
            }
            $mform->setDefault('targetset', $template);
        }

        $mform->addElement('format', 'format', get_string('format'));

        $mform->addElement('date_selector', 'deadline', get_string('deadline', 'ilptarget'));

        if($targetpost > 0 && $report){
            $mform->setDefault('deadline', $report->deadline);
        }else{
            $mform->setDefault('deadline', time());
        }

        $this->add_action_buttons($cancel = true, $submitlabel=get_string('savechanges'));
    }

    function process_data($data) {
        global $USER,$CFG;
        require_once("$CFG->dirroot/message/lib.php");

        $courseid = $this->_customdata['courseid'];
        $userid = $this->_customdata['userid'];
        $id = $this->_customdata['id'];
        $targetpost = $this->_customdata['targetpost'];

        //Sets message details for Targets
        $messagefrom = get_record('user', 'id', $USER->id);
        $messageto = get_record('user', 'id', $userid);
        $newtarget = get_string('newtarget','ilptarget');
        $updatedtarget = get_string('updatedtarget','ilptarget');
        $targetview = get_string('targetviewlink','ilptarget');
        $targeturl = $CFG->wwwroot.'/mod/ilptarget/target_view.php'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'&amp;userid='.$userid;

        if (!$report = get_record('ilptarget_posts', 'id', $targetpost)) {
            $report = new object();
			$report->setforuserid = $userid;
            $report->setbyuserid = $USER->id;
            $report->course = $courseid;
            if(isset($data->courserelated)){
                $report->courserelated = $data->courserelated;
                $report->targetcourse = $data->targetcourse;
            }else{
                $report->courserelated = 0;
                $report->targetcourse = 0;
            }
            $report->timecreated  = time();
            $report->timemodified = time();
            $report->deadline = $data->deadline;
            $report->data1 = '';
            $report->data2 = '';
			$report->name = $data->name;
            $report->targetset = $data->targetset;
            $report->format = $data->format;
            $report->status = 0;

            $targetinstance = insert_record('ilptarget_posts', $report, true);

            //Add Calendar event
            $event = new object();
            $event->name        = $data->name;
            $event->description = $data->targetset.'<br/><a href="'.$CFG->wwwroot.'/mod/ilptarget/target_comments.php?'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'targetpost='.$targetinstance.'">'.$targetview.'</a>';
            $event->format      = $data->format;
            $event->courseid    = 0;
            $event->groupid     = 0;
            $event->userid      = $userid;
            $event->modulename  = '';
            $event->instance    = $targetinstance;
            $event->eventtype   = 'due';
            $event->timestart   = $data->deadline;
            $event->timeduration = 0;

            add_event($event);

            $message = '<p>'.$newtarget;

        }else{

            $report->course = $courseid;
            if(isset($data->courserelated)){
                $report->courserelated = $data->courserelated;
                $report->targetcourse = $data->targetcourse;
            }else{
                $report->courserelated = 0;
                $report->targetcourse = 0;
            }
			$report->name = $data->name;
            $report->targetset = $data->targetset;
            $report->deadline = $data->deadline;
            $report->format = $data->format;
            $report->timemodified   = time();
            unset($report->data1);  // Don't need to update this.
            unset($report->data2);  // Don't need to update this.

            update_record('ilptarget_posts', $report);

            //Update Calendar event
            $event = get_record('event', 'name', $report->name, 'instance', $report->id, 'userid', $userid);
            $event->name        = $data->name;
			$event->description = $data->targetset.'<br/><a href="'.$CFG->wwwroot.'/mod/ilptarget/target_comments.php?'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'targetpost='.$report->id.'">'.$targetview.'</a>';
            $event->format      = $data->format;
            $event->timestart   = $data->deadline;
            $event->timemodified = time();

            update_record('event', $event);

            $message = '<p>'.$updatedtarget;
        }

        if($CFG->ilptarget_send_target_message == 1){
            $message .= '<br /><a href="'.$targeturl.'">'.$targetview.'</a></p>'.$data->targetset;
            message_post_message($messagefrom, $messageto, $message, FORMAT_HTML, 'direct');
        }
      }
}

//Creates a drop-down menu and/or editing options to update the status of a target

function ilptarget_update_status_menu($targetpost,$context) {

	global $USER, $CFG;
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
	$userid = optional_param('userid', 0, PARAM_INT); //User's targets we wish to view
    $courseid = optional_param('courseid', SITEID, PARAM_INT); //User's targets we wish to view

	$report = get_record('ilptarget_posts', 'id', $targetpost);

	if ($userid > 0){
		$user = get_record('user', 'id', $userid);
	}elseif ($id > 0){
		$user = get_record('user', 'id', $id);
	}else{
		$user = $USER;
	}

	$age = time() - $report->timecreated;
    $ownpost = ($USER->id == $report->setbyuserid);
    $tutorpost = ($user->id != $report->setbyuserid);
	$output = '';

	if((($age < $CFG->maxeditingtime) && $ownpost) || has_capability('mod/ilptarget:edittarget', $context) || has_capability('mod/ilptarget:editowntarget', $context)) {
			$output .= ' | <a title="'.get_string('edit').'" href="'.$CFG->wwwroot.'/mod/ilptarget/target_view.php?'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'userid='.$user->id.'&amp;targetpost='.$report->id.'&amp;action=updatetarget"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" /> '.get_string('edit').'</a> | <a title="'.get_string('delete').'" href="'.$CFG->wwwroot.'/mod/ilptarget/target_view.php?'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'userid='.$user->id.'&amp;targetpost='.$report->id.'&amp;action=delete""><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" /> '.get_string('delete').'</a> | ';
	}

	if($ownpost || ($tutorpost && has_capability('mod/ilptarget:viewclass', $context)) || has_capability('moodle/site:doanything', $context)){

		$output .= '<form name="submitform" action="'.$CFG->wwwroot.'/mod/ilptarget/target_view.php" method="post">';
		$output .= '<input type="hidden" name="targetpost" value="'.$targetpost.'" />';
		if($courseid != SITEID){
			$output .= '<input type="hidden" name="courseid" value="'.$courseid.'" />';
		}
		$output .= '<input type="hidden" name="userid" value="'.$user->id.'" />';
		$output .= '<input type="hidden" name="action" value="updatestatus" />';
		$output .= '<select name="status">';
		$output .= '<option value="0">'.get_string('outstanding', 'ilptarget').'</option>';
		$output .= '<option value="1">'.get_string('achieved', 'ilptarget').'</option>';
		//$output .= '<option value="2">'.get_string('notachieved', 'ilptarget').'</option>';
		$output .= '<option value="3">'.get_string('withdrawn', 'ilptarget').'</option>';
		$output .= '</select>';
		$output .= '<input type="submit" name="submit" value="'.get_string('updatestatus', 'ilptarget').'" />';
		$output .= '</form>';
	}else{
		$output = '';
	}

	return $output;
}

/**
 * Creates the form to update/add comments
 */

class ilptarget_updatecomment_form extends moodleform {

    function definition() {
        global $USER, $CFG;
        require_once("$CFG->dirroot/blocks/ilp/block_ilp_lib.php");

        $mform    =& $this->_form;

        $courseid = $this->_customdata['courseid'];
        $userid = $this->_customdata['userid'];
        $id = $this->_customdata['id'];
        $targetpost = $this->_customdata['targetpost'];
		$commentid = $this->_customdata['commentid'];

        $user = get_record('user','id',$userid);

        if($commentid > 0){
            $report = get_record('ilptarget_comments', 'targetpost', $targetpost, 'id', $commentid);
        }

        if($user->id == $USER->id){
            $mform->addElement('header', 'title', get_string('mycomment', 'ilptarget'));
        }else{
            $mform->addElement('header', 'title', get_string('commentfor', 'ilptarget', fullname($user)));
        }

        $mform->addElement('hidden', 'userid', $userid);
		$mform->addElement('hidden', 'targetpost', $targetpost);
        if($courseid != SITEID){
            $mform->addElement('hidden', 'courseid', $courseid);
        }
        if($id > 0){
            $mform->addElement('hidden', 'id', $id);
        }
        if($commentid > 0 && $report){
            $mform->addElement('hidden', 'commentid', $commentid);
        }

        $mform->addElement('htmleditor', 'comment', get_string('comment', 'ilptarget'));
        $mform->setType('comment', PARAM_RAW);
        $mform->addRule('comment', null, 'required', null, 'client');
        $mform->setHelpButton('comment', array('writing', 'richtext'), false, 'editorhelpbutton');
        if($commentid > 0 && $report){
            $mform->setDefault('comment', $report->comment);
        }

        $mform->addElement('format', 'format', get_string('format'));

        $this->add_action_buttons($cancel = true, $submitlabel=get_string('savechanges'));
    }

    function process_data($data) {
        global $USER,$CFG;
        require_once("$CFG->dirroot/message/lib.php");

        $courseid = $this->_customdata['courseid'];
        $userid = $this->_customdata['userid'];
        $id = $this->_customdata['id'];
        $targetpost = $this->_customdata['targetpost'];
        $commentid = $this->_customdata['commentid'];

        //Sets message details for comments
        $messagefrom = get_record('user', 'id', $USER->id);
        $messageto = get_record('user', 'id', $userid);
        $newcomment = get_string('newcomment','ilptarget');
        $updatedcomment = get_string('updatedcomment','ilptarget');
        $targetview = get_string('targetviewlink','ilptarget');
        $commenturl = $CFG->wwwroot.'/mod/ilptarget/target_comments.php?'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'&amp;targetpost='.$targetpost;

        if (!$report = get_record('ilptarget_comments', 'targetpost', $targetpost, 'id', $commentid)) {
            $report = new Object;
			$report->targetpost = $targetpost;
			$report->userid = $USER->id;
			$report->created  = time();
			$report->modified = time();
			$report->comment = $data->comment;
			$report->format = $data->format;

            $commentinstance = insert_record('ilptarget_comments', $report, true);

            $message = '<p>'.$newcomment;

        }else{

            $report->userid  = $USER->id;
			$report->comment = $data->comment;
			$report->format  = $data->format;
			$report->modified = time();

            $commentinstance = update_record('ilptarget_comments', $report);

            $message = '<p>'.$updatedcomment;
        }

        if($CFG->ilptarget_send_comment_message == 1){
            $message .= '<br /><a href="'.$commenturl.'">'.$targetview.'</a></p>'.$comment;
            message_post_message($messagefrom, $messageto, $message, FORMAT_HTML, 'direct');
        }
      }
}

function ilptarget_update_comment_menu($commentid,$context) {

	global $USER, $CFG;
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
	$userid = optional_param('userid', 0, PARAM_INT); //User's targets we wish to view
    $courseid = optional_param('courseid', SITEID, PARAM_INT); //User's targets we wish to view

	$report = get_record('ilptarget_comments', 'id', $commentid);

	if ($userid > 0){
		$user = get_record('user', 'id', $userid);
	}elseif ($id > 0){
		$user = get_record('user', 'id', $id);
	}else{
		$user = $USER;
	}

	$age = time() - $report->created;
    $ownpost = ($USER->id == $report->userid);
	$output = '';

	if((($age < $CFG->maxeditingtime) && $ownpost) || has_capability('moodle/site:doanything', $context)) {
			$output .= '<a title="'.get_string('edit').'" href="'.$CFG->wwwroot.'/mod/ilptarget/target_comments.php?'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'commentid='.$report->id.'&amp;targetpost='.$report->targetpost.'&amp;action=updatecomment"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" /> '.get_string('edit').'</a> | <a title="'.get_string('delete').'" href="'.$CFG->wwwroot.'/mod/ilptarget/target_comments.php?'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'commentid='.$report->id.'&amp;targetpost='.$report->targetpost.'&amp;action=delete"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" /> '.get_string('delete').'</a>';
	}

	return $output;
}



/**

 * Given an object containing all the necessary data,

 * (defined by the form in mod.html) this function

 * will create a new instance and return the id number

 * of the new instance.

 *

 * @param object $instance An object from the form in mod.html

 * @return int The id of the newly inserted target record

 **/

function ilptarget_add_instance($ilptarget) {



    // temp added for debugging

    echo "ADD INSTANCE CALLED";

   // print_object($target);



    $ilptarget->timemodified = time();



    # May have to add extra stuff in here #



    return insert_record("ilptarget", $ilptarget);

}



/**

 * Given an object containing all the necessary data,

 * (defined by the form in mod.html) this function

 * will update an existing instance with new data.

 *

 * @param object $instance An object from the form in mod.html

 * @return boolean Success/Fail

 **/

function ilptarget_update_instance($ilptarget) {



    $ilptarget->timemodified = time();

    $ilptarget->id = $ilptarget->instance;



    # May have to add extra stuff in here #



    return update_record("ilptarget", $ilptarget);

}



/**

 * Given an ID of an instance of this module,

 * this function will permanently delete the instance

 * and any data that depends on it.

 *

 * @param int $id Id of the module instance

 * @return boolean Success/Failure

 **/

function ilptarget_delete_instance($id) {



    if (! $ilptarget = get_record("ilptarget", "id", "$id")) {

        return false;

    }



    $result = true;



    # Delete any dependent records here #



    if (! delete_records("ilptarget", "id", "$ilptarget->id")) {

        $result = false;

    }



    return $result;

}



/**

 * Return a small object with summary information about what a

 * user has done with a given particular instance of this module

 * Used for user activity reports.

 * $return->time = the time they did it

 * $return->info = a short text description

 *

 * @return null

 * @todo Finish documenting this function

 **/

function ilptarget_user_outline($course, $user, $mod, $ilptarget) {

    return $return;

}



/**

 * Print a detailed representation of what a user has done with

 * a given particular instance of this module, for user activity reports.

 *

 * @return boolean

 * @todo Finish documenting this function

 **/

function ilptarget_user_complete($course, $user, $mod, $ilptarget) {

    return true;

}



/**

 * Given a course and a time, this module should find recent activity

 * that has occurred in target activities and print it out.

 * Return true if there was output, or false is there was none.

 *

 * @uses $CFG

 * @return boolean

 * @todo Finish documenting this function

 **/

function ilptarget_print_recent_activity($course, $isteacher, $timestart) {

    global $CFG;



    return false;  //  True if anything was printed, otherwise false

}



/**

 * Function to be run periodically according to the moodle cron

 * This function searches for things that need to be done, such

 * as sending out mail, toggling flags etc ...

 *

 * @uses $CFG

 * @return boolean

 * @todo Finish documenting this function

 **/

function ilptarget_cron () {

    global $CFG;



    return true;

}



/**

 * Must return an array of grades for a given instance of this module,

 * indexed by user.  It also returns a maximum allowed grade.

 *

 * Example:

 *    $return->grades = array of grades;

 *    $return->maxgrade = maximum allowed grade;

 *

 *    return $return;

 *

 * @param int $targetid ID of an instance of this module

 * @return mixed Null or object with an array of grades and with the maximum grade

 **/

function ilptarget_grades($ilptargetid) {

   return NULL;

}



/**

 * Must return an array of user records (all data) who are participants

 * for a given instance of target. Must include every user involved

 * in the instance, independient of his role (student, teacher, admin...)

 * See other modules as example.

 *

 * @param int $targetid ID of an instance of this module

 * @return mixed boolean/array of students

 **/

function ilptarget_get_participants($ilptargetid) {

    return false;

}



/**

 * This function returns if a scale is being used by one target

 * it it has support for grading and scales. Commented code should be

 * modified if necessary. See forum, glossary or journal modules

 * as reference.

 *

 * @param int $targetid ID of an instance of this module

 * @return mixed

 * @todo Finish documenting this function

 **/

function ilptarget_scale_used ($targetid,$scaleid) {

    $return = false;



    //$rec = get_record("ilptarget","id","$targetid","scale","-$scaleid");

    //

    //if (!empty($rec)  && !empty($scaleid)) {

    //    $return = true;

    //}



    return $return;

}

/**
 * Checks if scale is being used by any instance of ilptarget
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any forum
 */
function ilptarget_scale_used_anywhere($scaleid) {
    //if ($scaleid and record_exists('forum', 'scale', -$scaleid)) {
        //return true;
    //} else {
        return false;
    //}
}



function ilptarget_print_overview($courses, &$htmlarray) {

    global $USER, $CFG;



    if (empty($courses) || !is_array($courses) || count($courses) == 0) {

        return array();

    }



    if (!$targets = get_all_instances_in_courses('ilptarget',$courses)) {

        return;

    }



    // Do target_base::isopen() here without loading the whole thing for speed

    foreach ($targets as $key => $target) {

        $time = time();

    }



    $strtarget = get_string('modulename', 'ilptarget');



    foreach ($targets as $target) {

	$context = get_context_instance(CONTEXT_MODULE, $target->id);

        $str = '<div class="target overview"><div class="name">'.$strtarget. ': '.

               '<a '.($target->visible ? '':' class="dimmed"').

               'title="'.$strtarget.'" href="'.$CFG->wwwroot.

               '/mod/ilptarget/view.php?id='.$target->coursemodule.'">'.

               $target->name.'</a></div>';



        if (has_capability('mod/ilptarget:viewclass', $context)) {

        } else {



			$targettotal = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilptarget_posts WHERE setforuserid = '.$USER->id.' AND status != "3"' );

			$targetcomplete = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilptarget_posts WHERE setforuserid = '.$USER->id.' AND status = "1"');



				$str .= $targetcomplete.'/'.$targettotal.' target(s) complete';



                ///No more buttons, we use popups ;-).



                //$update  = '<div id="up'.$auser->id.'" class="up_target" ><a href="submissions.php?id='.$cm->id.'&amp;userid='.$auser->id.'&amp;mode=student">'.$buttontext.'</div>';



        }

        $str .= '</div>';

        if (empty($htmlarray[$target->course]['target'])) {

            $htmlarray[$target->course]['target'] = $str;

        } else {

            $htmlarray[$target->course]['target'] .= $str;

        }

    }

}





?>
<?php


require_once($CFG->libdir.'/formslib.php');
$concerns_CONSTANT = 7;     /// for example

//Creates a drop-down menu to update the status of a student

function update_student_status_menu($userid,$courseid) {
	global $CFG;

	$userid = optional_param('userid', 0, PARAM_INT);
	if ($userid > 0){
		$user = get_record('user', 'id',$userid);
	}else{
		$user = $USER;
	}

	if(!$post = get_record('ilpconcern_status', 'userid',$user->id)) {
		$post->status = 0;
	}
		$options = array(get_string('green', 'ilpconcern'), get_string('amber', 'ilpconcern'), get_string('red', 'ilpconcern'));

	popup_form ($CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?'.(($courseid > 1)?'courseid='.$courseid.'&amp;' : '').'&amp;userid='.$userid.'&amp;action=updatestatus&amp;studentstatus=', $options, "studentstatus", $post->status, "", "", "", false, 'self', get_string('updatestatus','ilpconcern'));

}


class ilpconcern_updateconcern_form extends moodleform {

    function definition() {
        global $USER, $CFG;
        require_once("$CFG->dirroot/blocks/ilp/block_ilp_lib.php");

		$mform    =& $this->_form;

        $courseid = $this->_customdata['courseid'];
		$userid = $this->_customdata['userid'];
		$id = $this->_customdata['id'];
		$status = $this->_customdata['status'];
		$concernspost = $this->_customdata['concernspost'];
		$thisreporttype = $this->_customdata['reporttype'];
		$template = $this->_customdata['template'];

		$user = get_record('user','id',$userid);

				//$report_no = $status + 1;
				//$template = stripslashes(eval('return $CFG->ilpconcern_report'.$report_no.'_template;'));
			//}else{
				//$template = '';
			//}



		if($concernspost > 0){
			$report = get_record('ilpconcern_posts', 'setforuserid', $userid, 'id', $concernspost);
		}

		if($user == $USER){
			$mform->addElement('header', 'title', get_string('myreport', 'ilpconcern', $thisreporttype));
		}else{
			$mform->addElement('header', 'title', get_string('reportfor', 'ilpconcern', fullname($user)));
		}

		$mform->addElement('hidden', 'userid', $userid);
		if($courseid != SITEID){
			$mform->addElement('hidden', 'courseid', $courseid);
		}
		if($id > 0){
			$mform->addElement('hidden', 'id', $id);
		}
		if($concernspost > 0 && $report){
			$mform->addElement('hidden', 'concernspost', $concernspost);
		}

		$mform->addElement('hidden', 'status', $status);
		$mform->addElement('hidden', 'reporttype', $thisreporttype);

		$mform->addElement('checkbox', 'courserelated', get_string('courserelated', 'ilpconcern'));
		if($concernspost > 0 && $report){
			$mform->setDefault('courserelated', $report->courserelated);
        }
		$ilpcourses = get_my_ilp_courses($user->id);
		$options = array();
		foreach ($ilpcourses as $ilpcourse) {
			$options[$ilpcourse->id] = $ilpcourse->shortname;
		}
		$mform->addElement('select', 'targetcourse', get_string('course'), $options);
		$mform->disabledIf('targetcourse', 'courserelated');
		if($concernspost > 0 && $report){
			$mform->setDefault('targetcourse', $report->targetcourse);
        }else{
			$mform->setDefault('targetcourse',$courseid);
		}
		$mform->addElement('htmleditor', 'concernset', $thisreporttype, array('canUseHtmlEditor'=>'detect','rows'  => 20,'cols'  => 65));
        $mform->setType('concernset', PARAM_RAW);
        $mform->addRule('concernset', null, 'required', null, 'client');
		$mform->setHelpButton('concernset', array('writing', 'richtext'), false, 'editorhelpbutton');
		if($concernspost > 0 && $report){
			$mform->setDefault('concernset', $report->concernset);
        }else{
			$select = "module = 'ilpconcern' AND status = $status";
			if($CFG->ilpconcern_use_template == 1 && $template > 0){
				$thistemplate = get_record('ilp_module_template','id',$template);
				$usetemplate = stripslashes($thistemplate->text);
			}elseif(count_records_select('ilp_module_template',$select) == 1){
				$thistemplate = get_record_select('ilp_module_template',$select);
				$usetemplate = stripslashes($thistemplate->text);
			}else{
				$usetemplate = '';
			}
        	$mform->setDefault('concernset', $usetemplate);
        }

        $mform->addElement('format', 'format', get_string('format'));

		$mform->addElement('date_selector', 'deadline', get_string('deadline', 'ilpconcern'));

		if($concernspost > 0 && $report){
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
		$status = $this->_customdata['status'];
		$concernspost = $this->_customdata['concernspost'];
		$thisreporttype = $this->_customdata['reporttype'];

		//Sets message details for Reports
		$messagefrom = get_record('user', 'id', $USER->id);
		$messageto = get_record('user', 'id', $userid);
		$newconcern = get_string('newconcern','ilpconcern', $thisreporttype);
		$updatedconcern = get_string('updatedconcern','ilpconcern', $thisreporttype);
		$concernview = get_string('concernviewlink','ilpconcern');
		$concernurl = $CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?'.(($courseid > 1)?'courseid='.$courseid.'&amp;' : '').'&amp;userid='.$userid.'&amp;status='.$status;

		$plpurl = $CFG->wwwroot.'/blocks/ilp/view.php?'.(($courseid > 1)?'courseid='.$courseid.'&amp;' : '');

		if (!$report = get_record('ilpconcern_posts', 'id', $concernspost)) {
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
			$report->concernset = $data->concernset;
			$report->format = $data->format;
			$report->status = $data->status;

			insert_record('ilpconcern_posts', $report, true);

			$message = '<p>'.$newconcern;

		}else{

			$report->course = $courseid;
			if(isset($data->courserelated)){
				$report->courserelated = $data->courserelated;
				$report->targetcourse = $data->targetcourse;
			}else{
				$report->courserelated = 0;
				$report->targetcourse = 0;
			}
			$report->concernset = $data->concernset;
			$report->deadline = $data->deadline;
			$report->format = $data->format;
			$report->timemodified   = time();
			unset($report->data1);  // Don't need to update this.
			unset($report->data2);  // Don't need to update this.

			update_record('ilpconcern_posts', $report);

			$message = '<p>'.$updatedconcern;
		}

		if($CFG->ilpconcern_send_concern_message == 1){
			$message .= '<br /><a href="'.$concernurl.'">'.$concernview.'</a></p>'.$data->concernset;
			message_post_message($messagefrom, $messageto, $message, FORMAT_HTML, 'direct');
		}
	  }
}

//Creates a drop-down menu to update the status of a concern

function ilpconcern_update_menu($concernpost,$context,$i) {

	global $USER, $CFG;
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
	$userid = optional_param('userid', 0, PARAM_INT); //User we wish to view
    $courseid = optional_param('courseid', 0, PARAM_INT); //Course

	$report = get_record('ilpconcern_posts', 'id', $concernpost);

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

	if((($age < $CFG->maxeditingtime) && $ownpost) || has_capability(eval("return 'mod/ilpconcern:editreport".$i."';"), $context) || has_capability(eval("return 'mod/ilpconcern:editownreport".$i."';"), $context)) {
			$output .= ' | <a title="'.get_string('edit').'" href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'userid='.$user->id.'&amp;concernspost='.$report->id.'&amp;action=updateconcern"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" /> '.get_string('edit').'</a> | <a title="'.get_string('delete').'" href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'userid='.$user->id.'&amp;concernspost='.$report->id.'&amp;action=delete""><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" /> '.get_string('delete').'</a> | ';
	}

	return $output;
}

/**
 * Creates the form to update/add comments
 */

class ilpconcern_updatecomment_form extends moodleform {

    function definition() {
        global $USER, $CFG;
        require_once("$CFG->dirroot/blocks/ilp/block_ilp_lib.php");

        $mform    =& $this->_form;

        $courseid = $this->_customdata['courseid'];
        $userid = $this->_customdata['userid'];
        $id = $this->_customdata['id'];
        $concernspost = $this->_customdata['concernspost'];
		$commentid = $this->_customdata['commentid'];

        $user = get_record('user','id',$userid);

        if($commentid > 0){
            $report = get_record('ilpconcern_comments', 'concernspost', $concernspost, 'id', $commentid);
        }

        if($user->id == $USER->id){
            $mform->addElement('header', 'title', get_string('mycomment', 'ilpconcern'));
        }else{
            $mform->addElement('header', 'title', get_string('commentfor', 'ilpconcern', fullname($user)));
        }

        $mform->addElement('hidden', 'userid', $userid);
		$mform->addElement('hidden', 'concernspost', $concernspost);
        if($courseid != SITEID){
            $mform->addElement('hidden', 'courseid', $courseid);
        }
        if($id > 0){
            $mform->addElement('hidden', 'id', $id);
        }
        if($commentid > 0 && $report){
            $mform->addElement('hidden', 'commentid', $commentid);
        }

        $mform->addElement('htmleditor', 'comment', get_string('comment', 'ilpconcern'));
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
        $concernspost = $this->_customdata['concernspost'];
        $commentid = $this->_customdata['commentid'];

        //Sets message details for comments
        $messagefrom = get_record('user', 'id', $USER->id);
        $messageto = get_record('user', 'id', $userid);
        $newcomment = get_string('newcomment','ilpconcern');
        $updatedcomment = get_string('updatedcomment','ilpconcern');
        $concernview = get_string('concernviewlink','ilpconcern');
        $commenturl = $CFG->wwwroot.'/mod/ilpconcern/concerns_comments.php?'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'&amp;concernspost='.$concernspost;

        if (!$report = get_record('ilpconcern_comments', 'concernspost', $concernspost, 'id', $commentid)) {
            $report = new Object;
			$report->concernspost = $concernspost;
			$report->userid = $USER->id;
			$report->created  = time();
			$report->modified = time();
			$report->comment = $data->comment;
			$report->format = $data->format;

            $commentinstance = insert_record('ilpconcern_comments', $report, true);

            $message = '<p>'.$newcomment;

        }else{

            $report->userid  = $USER->id;
			$report->comment = $data->comment;
			$report->format  = $data->format;
			$report->modified = time();

            $commentinstance = update_record('ilpconcern_comments', $report);

            $message = '<p>'.$updatedcomment;
        }

        if($CFG->ilpconcern_send_comment_message == 1){
            $message .= '<br /><a href="'.$commenturl.'">'.$concernview.'</a></p>'.$comment;
            message_post_message($messagefrom, $messageto, $message, FORMAT_HTML, 'direct');
        }
      }
}

function ilpconcern_update_comment_menu($commentid,$context) {

	global $USER, $CFG;
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
	$userid = optional_param('userid', 0, PARAM_INT); //User we wish to view
    $courseid = optional_param('courseid', SITEID, PARAM_INT); //Course

	$report = get_record('ilpconcern_comments', 'id', $commentid);

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
			$output .= '<a title="'.get_string('edit').'" href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_comments.php?'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'commentid='.$report->id.'&amp;concernspost='.$report->concernspost.'&amp;action=updatecomment"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" /> '.get_string('edit').'</a> | <a title="'.get_string('delete').'" href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_comments.php?'.(($courseid != SITEID)?'courseid='.$courseid.'&amp;' : '').'commentid='.$report->id.'&amp;concernspost='.$report->concernspost.'&amp;action=delete"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" /> '.get_string('delete').'</a>';
	}

	return $output;
}

// Creates a form to add templates to the configuration options

class ilpconcern_addtemplate_form extends moodleform {

    function definition() {
        global $USER, $CFG;

        $mform    =& $this->_form;

        $id = $this->_customdata['id'];

        if($id > 0) {
			$template = get_record('ilp_module_template','id',$id);
			$mform->addElement('hidden', 'id', $template->id);
		}

		$mform->addElement('header', 'template', get_string('templatedetails', 'ilpconcern'));

        $mform->addElement('text', 'name', get_string('name'),array('size'=>'60'));
        $mform->addRule('name', null, 'required', null, 'client');
		if($id > 0 && $template){
            $mform->setDefault('name', $template->name);
        }

		$options = array(get_string('report1','ilpconcern'),get_string('report2','ilpconcern'),get_string('report3','ilpconcern'),get_string('report4','ilpconcern'));
		$mform->addElement('select', 'status', get_string('status','ilpconcern'), $options);
		$mform->addRule('status', null, 'required', null, 'client');
		if($id > 0 && $template){
            $mform->setDefault('status', $template->status);
        }

		$mform->addElement('htmleditor', 'text', get_string('template','ilpconcern'), array('canUseHtmlEditor'=>'detect','rows'  => 20,'cols'  => 65));
        $mform->setType('text', PARAM_RAW);
        $mform->addRule('text', null, 'required', null, 'client');
		$mform->setHelpButton('text', array('writing', 'richtext'), false, 'editorhelpbutton');
		if($id > 0 && $template){
			$mform->setDefault('text', $template->text);
        }

        $this->add_action_buttons($cancel = true, $submitlabel=get_string('savechanges'));
    }

    function process_data($data) {
        global $USER,$CFG;

        $id = $this->_customdata['id'];

        if (!$template = get_record('ilp_module_template','id',$id)) {
            $template = new Object;
			$template->name = $data->name;
			$template->module = 'ilpconcern';
			$template->status  = $data->status;
			$template->text = $data->text;

            $templateinstance = insert_record('ilp_module_template', $template, true);

        }else{

			$template->name = $data->name;
			$template->status  = $data->status;
			$template->text = $data->text;

            $templateinstance = update_record('ilp_module_template', $template, true);

        }
      }
}


/**

 * Given an object containing all the necessary data,

 * (defined by the form in mod.html) this function

 * will create a new instance and return the id number

 * of the new instance.

 *

 * @param object $instance An object from the form in mod.html

 * @return int The id of the newly inserted concerns record

 **/

function ilpconcern_add_instance($ilpconcern) {



    // temp added for debugging

    echo "ADD INSTANCE CALLED";

   // print_object($concerns);



    $ilpconcern->timemodified = time();



    # May have to add extra stuff in here #



    return insert_record("ilpconcern", $ilpconcern);

}



/**

 * Given an object containing all the necessary data,

 * (defined by the form in mod.html) this function

 * will update an existing instance with new data.

 *

 * @param object $instance An object from the form in mod.html

 * @return boolean Success/Fail

 **/

function ilpconcern_update_instance($ilpconcern) {



    $ilpconcern->timemodified = time();

    $ilpconcern->id = $ilpconcern->instance;



    # May have to add extra stuff in here #



    return update_record("ilpconcern", $ilpconcern);

}



/**

 * Given an ID of an instance of this module,

 * this function will permanently delete the instance

 * and any data that depends on it.

 *

 * @param int $id Id of the module instance

 * @return boolean Success/Failure

 **/

function ilpconcern_delete_instance($id) {



    if (! $ilpconcern = get_record("ilpconcern", "id", "$id")) {

        return false;

    }



    $result = true;



    # Delete any dependent records here #



    if (! delete_records("ilpconcern", "id", "$ilpconcern->id")) {

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

function ilpconcern_user_outline($course, $user, $mod, $concerns) {

    return $return;

}



/**

 * Print a detailed representation of what a user has done with

 * a given particular instance of this module, for user activity reports.

 *

 * @return boolean

 * @todo Finish documenting this function

 **/

function ilpconcern_user_complete($course, $user, $mod, $concerns) {

    return true;

}



/**

 * Given a course and a time, this module should find recent activity

 * that has occurred in concerns activities and print it out.

 * Return true if there was output, or false is there was none.

 *

 * @uses $CFG

 * @return boolean

 * @todo Finish documenting this function

 **/

function ilpconcern_print_recent_activity($course, $isteacher, $timestart) {

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

function ilpconcern_cron () {

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

 * @param int $concernsid ID of an instance of this module

 * @return mixed Null or object with an array of grades and with the maximum grade

 **/

function ilpconcern_grades($ilpconcernid) {

   return NULL;

}



/**

 * Must return an array of user records (all data) who are participants

 * for a given instance of concerns. Must include every user involved

 * in the instance, independient of his role (student, teacher, admin...)

 * See other modules as example.

 *

 * @param int $concernsid ID of an instance of this module

 * @return mixed boolean/array of students

 **/

function ilpconcern_get_participants($ilpconcernid) {

    return false;

}



/**

 * This function returns if a scale is being used by one concerns

 * it it has support for grading and scales. Commented code should be

 * modified if necessary. See forum, glossary or journal modules

 * as reference.

 *

 * @param int $concernsid ID of an instance of this module

 * @return mixed

 * @todo Finish documenting this function

 **/

function ilpconcern_scale_used ($ilpconcernid,$scaleid) {

    $return = false;



    //$rec = get_record("ilpconcern","id","$concernsid","scale","-$scaleid");

    //

    //if (!empty($rec)  && !empty($scaleid)) {

    //    $return = true;

    //}



    return $return;

}

/**
 * Checks if scale is being used by any instance of ilpconcern
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any forum
 */
function ilpconcern_scale_used_anywhere($scaleid) {
    //if ($scaleid and record_exists('forum', 'scale', -$scaleid)) {
        //return true;
    //} else {
        return false;
    //}
}

function ilpconcern_process_options($data) {

	$plugin = 'project/ilp';

	foreach ($data as $name => $value) {
    	set_config($name, $value, $plugin);
    }

	redirect("$CFG->wwwroot", get_string("changessaved"), 1);

	exit;
}


function ilpconcern_print_overview($courses, &$htmlarray) {

    global $USER, $CFG;



    if (empty($courses) || !is_array($courses) || count($courses) == 0) {

        return array();

    }



    if (!$ilpconcerns = get_all_instances_in_courses('ilpconcern',$courses)) {

        return;

    }



    // Do concerns_base::isopen() here without loading the whole thing for speed

    foreach ($ilpconcerns as $key => $concerns) {

        $time = time();

    }



    $strconcerns = get_string('modulename', 'ilpconcern');



    foreach ($concerns as $concern) {

	$context = get_context_instance(CONTEXT_MODULE, $concerns->id);

        $str = '<div class="concerns overview"><div class="name">'.$strconcerns. ': '.

               '<a '.($concerns->visible ? '':' class="dimmed"').

               'title="'.$strconcerns.'" href="'.$CFG->wwwroot.

               '/mod/ilpconcern/view.php?id='.$concerns->coursemodule.'">'.

               $concerns->name.'</a></div>';



        if (has_capability('mod/ilpconcern:viewclass', $context)) {

        } else {



				$concernstotal = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE concerns = '.$concerns->id.' AND setforuserid = '.$USER->id);

				$concernscomplete = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE concerns = '.$concerns->id.' AND setforuserid = '.$USER->id.' AND complete = "1"');



				$str .= $concernscomplete.'/'.$concernstotal.' concerns(s) complete';



                ///No more buttons, we use popups ;-).



                //$update  = '<div id="up'.$auser->id.'" class="up_concerns" ><a href="submissions.php?id='.$cm->id.'&amp;userid='.$auser->id.'&amp;mode=student">'.$buttontext.'</div>';



        }

        $str .= '</div>';

        if (empty($htmlarray[$concerns->course]['concerns'])) {

            $htmlarray[$concerns->course]['concerns'] = $str;

        } else {

            $htmlarray[$concerns->course]['concerns'] .= $str;

        }

    }

}





?>
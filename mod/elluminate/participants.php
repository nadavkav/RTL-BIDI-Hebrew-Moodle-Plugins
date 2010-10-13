<?php
// $Id: participants.php,v 1.1.2.2 2009/03/18 16:45:54 mchurch Exp $

/**
 * Used to update the participants for a given meeting.
 *
 * @version $Id: participants.php,v 1.1.2.2 2009/03/18 16:45:54 mchurch Exp $
 * @author Justin Filip <jfilip@oktech.ca>
 * @author Remote Learner - http://www.remote-learner.net/
 */

require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(__FILE__) . '/lib.php';
require_js($CFG->wwwroot . '/mod/elluminate/jquery-1.4.2.min.js');
require_js($CFG->wwwroot . '/mod/elluminate/add_remove_submit.js');
    
$id = required_param('id', PARAM_INT);
$firstinitial = optional_param('firstinitial', '', PARAM_ALPHA);
$lastinitial = optional_param('lastinitial', '', PARAM_ALPHA);
$sort = optional_param('sort', '', PARAM_ALPHA);
$dir = optional_param('dir', '', PARAM_ALPHA);

if (!$elluminate = get_record('elluminate', 'id', $id)) {
        error('Could not get meeting (' . $id . ')');
    }
    if (!$course = get_record('course', 'id', $elluminate->course)) {
        error('Invalid course.');
    }    
    if($elluminate->groupmode == 0 && $elluminate->groupparentid == 0) {
	    if (! $cm = get_coursemodule_from_instance('elluminate', $elluminate->id, $course->id)) {
	        error('Course Module ID was incorrect');
	    }
	} else if ($elluminate->groupmode != 0 && $elluminate->groupparentid != 0){
		if (! $cm = get_coursemodule_from_instance('elluminate', $elluminate->groupparentid, $course->id)) {
	        error('Course Module ID was incorrect');
	    }
	} else if ($elluminate->groupmode != 0 && $elluminate->groupparentid == 0){
	    if (! $cm = get_coursemodule_from_instance('elluminate', $elluminate->id, $course->id)) {
	        error('Course Module ID was incorrect');
	    }
	} else {
		error('Elluminate Live! Group Error');
	}

require_course_login($course, true, $cm);
$modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
$crscontext = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('mod/elluminate:view', $modcontext);
require_capability('mod/elluminate:manageparticipants', $modcontext);

$usingroles = file_exists($CFG->libdir . '/accesslib.php');

$elluminate->name = stripslashes($elluminate->name);
$notice = '';

/// Check to see if groups are being used here
    $groupmode    = groups_get_activity_groupmode($cm);
    $currentgroup = groups_get_activity_group($cm, true);

    if (empty($currentgroup)) {
        $currentgroup = 0;
    }
    
    if(empty($elluminate->meetingid) && $elluminate->groupmode != 0) {
		elluminate_group_instance_check($elluminate);
	}

/// Process data submission.
if (($data = data_submitted($CFG->wwwroot . '/mod/elluminate/participants.php')) && confirm_sesskey()) {
	/// Delete records for selected participants chosen to be removed.
	if($data->submitvalue == "remove") {
		if (!empty ($data->userscur)) {		
			if (!elluminate_del_users($elluminate, $data->userscur, $currentgroup)) {
				$notice = get_string('couldnotremoveusersfrommeeting', 'elluminate');
			}
		}
	}

	/// Add records for selected participants chosen to be added.
	if($data->submitvalue == "add") {
		if (!empty ($data->usersavail)) {
	        if (!elluminate_add_users($elluminate, $data->usersavail, $currentgroup)) {
				$notice = get_string('couldnotadduserstomeeting', 'elluminate');
			}
		}
	}
}

/// Get a list of existing moderators for this meeting (if any) and assosciated
/// information.
$curmods = elluminate_get_meeting_participants($elluminate->id, true);

/// Get a list of existing participants for this meeting (if any) and assosciated
/// information.
$curusers = elluminate_get_meeting_participants($elluminate->id);

$usersexist = array ();
if (!empty ($curusers)) {
	foreach ($curusers as $curuser) {
		$usersexist[] = $curuser->id;
	}
	reset($curusers);
}

if (!empty ($curmods)) {
	foreach ($curmods as $curmod) {
		$usersexist[] = $curmod->id;
	}
}

	/// Particpants can be teachers or students in this course who have an account on
	/// the Elluminate server.
	$allusers = get_records_sql("select u.id, u.firstname, u.lastname, u.username from mdl_role_assignments ra, mdl_context con, mdl_course c, mdl_user u where ra.userid=u.id and ra.contextid=con.id and con.instanceid=c.id and c.id=" . $elluminate->course);                                     
    $ausers = array_keys($allusers);
    // if groupmembersonly used, only include members of the appropriate groups.
    if ($allusers and !empty($CFG->enablegroupings) and $cm->groupmembersonly) {
        if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
            $ausers = array_intersect($ausers, array_keys($groupingusers));
        }
    }

    $ausers = array_diff($ausers, $usersexist);

    $availusers = array();
    foreach ($ausers as $uid) {
        $availusers[$uid]  = $allusers[$uid];
    }
    unset($allusers);

    $cavailusers = empty($availusers) ? 0 : count($availusers);
    $ccurusers   = empty($curusers) ? 0 : count($curusers);

    $sesskey         = !empty($USER->sesskey) ? $USER->sesskey : '';
    $strmeeting      = get_string('modulename', 'elluminate');
    $strmeetings     = get_string('modulenameplural', 'elluminate');
    $strparticipants = get_string('editingparticipants', 'elluminate');
    $struserscur     = ($ccurusers == 1) ? get_string('existingparticipant', 'elluminate') :
                                           get_string('existingparticipants', 'elluminate', $ccurusers);
    $strusersavail   = ($cavailusers == 1) ? get_string('availableparticipant', 'elluminate') :
                                             get_string('availableparticipants', 'elluminate', $cavailusers);
    $strfilterdesc   = get_string('participantfilterdesc', 'elluminate');
    $strall          = get_string('all');
    $alphabet        = explode(',', get_string('alphabet'));

/// Print header.
    $navigation = build_navigation($strparticipants, $cm);
    print_header_simple($strparticipants, "", $navigation, "", "", true, '');

    groups_print_activity_menu($cm, 'participants.php?id=' . $elluminate->id, false, false);

    print_simple_box_start('center', '50%');

    if (!empty($notice)) {
        notify($notice);
    }

    include($CFG->dirroot . '/mod/elluminate/participants-edit.html');

    print_simple_box_end();

    print_footer();

?>

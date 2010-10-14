<?php
/**
 * This page apply changes on my preferences.
 *
 * @author Toni Mas
 * @version 1.0.0
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2006 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 * 		                   http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 **/


    require_once( "../../../config.php" );
    require_once($CFG->dirroot.'/blocks/email_list/email/lib.php');
    require_once($CFG->dirroot.'/blocks/email_list/email/preferences_form.php');

	$courseid		= optional_param('id', SITEID, PARAM_INT);		// Course ID

	global $CFG, $USER;

    // If defined course to view
    if (! $course = get_record('course', 'id', $courseid)) {
    	print_error('courseavailablenot', 'moodle');
    }

    require_login($course->id, false); // No autologin guest

    if ($course->id == SITEID) {
        $context = get_context_instance(CONTEXT_SYSTEM, SITEID);   // SYSTEM context
    } else {
        $context = get_context_instance(CONTEXT_COURSE, $course->id);   // Course context
    }

    // Can edit settings?
	if ( ! has_capability('block/email_list:editsettings', $context)) {
		print_error('forbiddeneditsettings', 'block_email_list', $CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$course->id);
	}

    // Security enable user's preference
    if ( empty($CFG->email_trackbymail) and empty($CFG->email_marriedfolders2courses) ) {
    	redirect($CFG->wwwroot.'/blocks/email_list/email/index.php?id'.$courseid, get_string('preferencesnotenable', 'block_email_list', '2'));
    }

    // Options for new mail and new folder
	$options = new stdClass();
	$options->id = $courseid;

    /// Print the page header

    $stremail  = get_string('name', 'block_email_list');

    if ( function_exists( 'build_navigation') ) {
    	// Prepare navlinks
    	$navlinks = array();
    	$navlinks[] = array('name' => get_string('nameplural', 'block_email_list'), 'link' => 'index.php?id='.$course->id, 'type' => 'misc');
    	$navlinks[] = array('name' => get_string('name', 'block_email_list'), 'link' => null, 'type' => 'misc');

		// Build navigation
		$navigation = build_navigation($navlinks);

		print_header("$course->shortname: $stremail", "$course->fullname",
    	             $navigation,
    	              "", '<link type="text/css" href="email.css" rel="stylesheet" /><link type="text/css" href="treemenu.css" rel="stylesheet" /><link type="text/css" href="tree.css" rel="stylesheet" /><script type="text/javascript" src="treemenu.js"></script><script type="text/javascript" src="email.js"></script>',
    	              true);
    } else {
    	$navigation = '';
		if ( isset($course) ) {
	    	if ($course->category) {
	    	    $navigation = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->shortname.'</a> ->';
	    	}
		}

		$stremails = get_string('nameplural', 'block_email_list');

    	print_header("$course->shortname: $stremail", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$stremails</a> -> $stremail",
                  "", '<link type="text/css" href="email.css" rel="stylesheet" /><link type="text/css" href="treemenu.css" rel="stylesheet" /><link type="text/css" href="tree.css" rel="stylesheet" /><script type="text/javascript" src="treemenu.js"></script><script type="text/javascript" src="email.js"></script>',
                  true);
    }


	// Print principal table. This have 2 columns . . .  and possibility to add right column.
	echo '<table id="layout-table">
  			<tr>';


	// Print "blocks" of this account
	echo '<td style="width: 180px;" id="left-column">';
	email_printblocks($USER->id, $courseid);

	// Close left column
	echo '</td>';

	// Print principal column
	echo '<td id="middle-column">';

    // Print block
    print_heading_block('');

    echo '<div>&#160;</div>';

	$mform = new preferences_form('preferences.php');

	if ( $mform->is_cancelled() ) {

		// Only redirect
		redirect($CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$courseid, '', 0);

	} else if ( $form = $mform->get_data() ) {

	    // Add log for one course
	    add_to_log($courseid, 'email', 'edit preferences', 'preferences.php', 'Edit my preferences', 0, $USER->id);

		$preference = new stdClass();

	    if ( record_exists('email_preference', 'userid', $USER->id) ) {

	    	if (! $preference = get_record('email_preference', 'userid', $USER->id) ) {
	    		print_error('failreadingpreferences', 'block_email_list', $CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$courseid);
	    	}

	    	// Security
	    	if ( $CFG->email_trackbymail ) {
	    		$preference->trackbymail = $form->trackbymail;
	    	} else {
	    		$preference->trackbymail = 0;
	    	}
	    	// Security
	    	if ( $CFG->email_marriedfolders2courses ) {
	    		$preference->marriedfolders2courses = $form->marriedfolders2courses;
	    	} else {
	    		$preference->marriedfolders2courses = 0;
	    	}

	    	if ( update_record('email_preference', $preference) ) {
	    		redirect($CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$courseid, get_string('savedpreferences', 'block_email_list'), '2');
	    	}
	    } else {

	    	$preference->userid = $USER->id;

	   		// Security
	    	if ( $CFG->email_trackbymail ) {
	    		$preference->trackbymail = $form->trackbymail;
	    	} else {
	    		$preference->trackbymail = 0;
	    	}
	    	// Security
	    	if ( $CFG->email_marriedfolders2courses ) {
	    		$preference->marriedfolders2courses = $form->marriedfolders2courses;
	    	} else {
	    		$preference->marriedfolders2courses = 0;
	    	}

	    	if ( insert_record('email_preference', $preference) ) {
	    		redirect($CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$courseid, get_string('savedpreferences', 'block_email_list'), '2');
	    	}
	    }

	    error(get_string('errorsavepreferences', 'block_email_list'), $CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$courseid );
	} else {

		// Get my preferences, if I have.
		$preferences = get_record('email_preference', 'userid', $USER->id);

		// Add course
		$preferences->id = $courseid;

		// Set data
		$mform->set_data($preferences);
		$mform->display();
	}

	// Close principal column
	echo '</td>';

	// Close table
	echo '</tr>
			</table>';

	print_footer($course);
?>
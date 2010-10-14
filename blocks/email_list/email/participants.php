<?php  // $Id: participants.php,v 1.11 2008/12/09 18:32:36 tmas Exp $
/**
 * This page prints all participants or contacts who sents mail/s
 *
 * @uses $CFG
 * @author Toni Mas
 * @version $Id: participants.php,v 1.11 2008/12/09 18:32:36 tmas Exp $
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2006 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 * 		                   http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 **/

	global $CFG;

    require_once( "../../../config.php" );
    require_once($CFG->dirroot.'/blocks/email_list/email/lib.php');
    require_once($CFG->libdir.'/ajax/ajaxlib.php');

    $courseid 	= required_param('id', PARAM_INT); 				// Course ID
    $selgroup   = optional_param('group', 0, PARAM_INT);    	// Selected group
	$roleid		= optional_param('roleid', 0, PARAM_INT);		// Role ID
	$page		= optional_param('page', 0, PARAM_INT);			// Page
	$perpage	= optional_param('perpage', 7, PARAM_INT);		// Max rows per page
	$search		= optional_param('search', '', PARAM_TEXT);	// Searching users

	$firstinitial 	= optional_param('fname', '', PARAM_ALPHA);		// Order by fistname
 	$lastinitial 	= optional_param('lname', '', PARAM_ALPHA);	// Order by lastname


	// Get course, if exist
	if (! $course = get_record('course', 'id', $courseid)) {
		print_error('invalidcourseid', 'block_email_list');
	}

	require_login($course->id);

	print_header (get_string('selectaction', 'block_email_list'), '',
		'', '', '<script type="text/javascript" src="manage.js"></script>');

	if ( $CFG->email_old_select_participants ) {
		//email_choose_course($courseid);

		email_choose_users_to_send($courseid, $roleid, $selgroup);
	} else {

		//add the alphabetical search first
		echo get_string('alphabetical','block_email_list');

		echo '<div id="abcd">';
		echo '<ul>';
		//array of letters
		$alpha  = explode(',', get_string('alphabet'));

		// First name
		echo '<li>'.get_string('firstname').': ';
		$nch = 0;
		foreach ($alpha as $ch) {
		 		if ($nch != 0) {
		     		echo ', ';
		 		}
		 		if ($ch == $firstinitial) {
		     		echo $ch;
		 		} else {
		     		echo "<a href=\"$CFG->wwwroot/blocks/email_list/email/participants.php?id=$courseid&amp;group=$selgroup&amp;perpage=$perpage&amp;search=$search&amp;roleid=$roleid&amp;lname=$lastinitial&amp;fname=$ch')\">$ch</a>";
		 		}
		 		$nch++;
		}
		echo '</li>';

		// Lastname initial
		echo '<li>'.get_string('lastname').': ';
		$nch = 0;
		foreach ($alpha as $ch) {
		 		if ($nch!=0) {
		     		echo ', ';
		 		}
		 		if ($ch == $lastinitial) {
		     		echo $ch;
		 		} else {
		     		echo "<a href=\"$CFG->wwwroot/blocks/email_list/email/participants.php?id=$courseid&amp;group=$selgroup&amp;perpage=$perpage&amp;search=$search&amp;roleid=$roleid&amp;fname=$firstinitial&amp;lname=$ch')\">$ch</a>";
		 		}
		 		$nch++;
		}

		echo '</li>';

		// Reset all param of the searching
		echo '<li>';
		echo '<a href="'.$CFG->wwwroot.'/blocks/email_list/email/participants.php?id='.$courseid.'&amp;group='.$selgroup.'">'.get_string('allusersincourse', 'block_email_list').'</a>';
		echo '</li></ul></div>';

		// Search input
		echo '<div onclick="switchMenu(\'srch\', \''.$CFG->pixpath.'/t/\')">';
		echo '<img id="srch_icon" src="'.$CFG->pixpath.'/t/switch_plus.gif"/>';
		print_spacer(1, 4, false);
		echo get_string('searchparticipant','block_email_list').'</div>';

	    echo '<div id="srch" style="display:none;">';
	    echo '<form method="post" action="participants.php">';
	    print_textfield ('search', $search, get_string('searchparticipants', 'block_email_list'));

		echo '<input type="hidden" name="id" value="'.$courseid.'"/>';
		echo '<input type="hidden" name="group" value="'.$selgroup.'"/>';
		echo '<input type="hidden" name="page" value="'.$page.'"/>';
		echo '<input type="hidden" name="perpage" value="'.$perpage.'"/>';
		echo '<input type="hidden" name="roleid" value="'.$roleid.'"/>';
		echo '<input type="hidden" name="fname" value="'.$firstinitial.'"/>';
 	   	echo '<input type="hidden" name="lname" value="'.$lastinitial.'"/>';

	    echo '<input type="submit" name="doit" value="'.get_string('search').'" />' .
		'</form>';
	    echo '</div>';

	    echo '<br />';

	    if ($courseid == SITEID) {
	        $context = get_context_instance(CONTEXT_SYSTEM, SITEID);   // SYSTEM context
	    } else {
	        $context = get_context_instance(CONTEXT_COURSE, $courseid);   // Course context
	    }

	    $sitecontext = get_context_instance(CONTEXT_SYSTEM);

	    $rolenames = array();
    	$avoidroles = array();

	    if ($roles = get_roles_used_in_context($context, true)) {
	        $canviewroles    = get_roles_with_capability('moodle/course:view', CAP_ALLOW, $context);
	        $doanythingroles = get_roles_with_capability('moodle/site:doanything', CAP_ALLOW, $sitecontext);

	        if ( ! $CFG->email_add_admins ) {
	        	$adminsroles = get_roles_with_capability('moodle/legacy:admin', CAP_ALLOW, $sitecontext);
	        }

	        foreach ($roles as $role) {
	            if (!isset($canviewroles[$role->id])) {   // Avoid this role (eg course creator)
	                $avoidroles[] = $role->id;
	                unset($roles[$role->id]);
	                continue;
	            }
	            if (isset($doanythingroles[$role->id])) {   // Avoid this role (ie admin)
	                $avoidroles[] = $role->id;
	                unset($roles[$role->id]);
	                continue;
	            }

	            if ( ! $CFG->email_add_admins ) {
	            	if (isset($adminsroles[$role->id])) {   // Avoid this role (ie admin)
		                $avoidroles[] = $role->id;
		                unset($roles[$role->id]);
		                continue;
		            }
	            }

	            // Prevent - CONTRIB-609
	        	if ( function_exists('role_get_name') ) {
	        		$rolenames[$role->id] = strip_tags(role_get_name($role, $context));   // Used in menus etc later on
	        	} else {
	        		$rolenames[$role->id] = strip_tags(format_string($role->name));   // Used in menus etc later on
	        	}
	        }
	    }

	    /// If there are multiple Roles in the course, then show a drop down menu for switching
	    if (count($rolenames) > 1) {
	        echo '<div class="rolesform">';
	        echo get_string('currentrole', 'role').': ';
	        $rolenames = array(0 => get_string('all')) + $rolenames;
	        popup_form("$CFG->wwwroot/blocks/email_list/email/participants.php?id=$courseid&amp;group=$selgroup&amp;page=$page&amp;perpage=$perpage&amp;search=$search&amp;fname=$firstinitial&amp;lname=$lastinitial&amp;contextid=$context->id&amp;roleid=", $rolenames,
	                   'rolesform', $roleid, '');
	        echo '</div>';
	    }

	    // Prints group selector for users with a viewallgroups capability if course groupmode is separate
	    echo '<br />';
		groups_print_course_menu($course, $CFG->wwwroot.'/blocks/email_list/email/participants.php?id='.$course->id);
		echo '<br /><br />';


		echo '<div id="participants"></div>' .
		'<iframe id="idsearch" name="bssearch" src="get_users.php?id='.$courseid.'&amp;roleid='.$roleid.'&amp;group='.$selgroup.'&amp;page='.$page.'&amp;perpage='.$perpage.'&amp;search='.$search.'&amp;fname='.$firstinitial.'&amp;lname='.$lastinitial.'" style="display:none;"></iframe>' . "\n\n";

		print_spacer(1, 4, false);
		if ( $perpage == '7' ) {
			echo '<div id="to_all_users" class="all_users"><img src="'.$CFG->wwwroot.'/blocks/email_list/email/images/add.png" height="16" width="16" alt="'.get_string("course").'" /> <a href="'.$CFG->wwwroot.'/blocks/email_list/email/participants.php?id='.$courseid.'&amp;group='.$selgroup.'&amp;perpage=99999&amp;search='.$search.'&amp;roleid='.$roleid.'&amp;fname='.$firstinitial.'&amp;lname='.$lastinitial.'">'.get_string('showallusers').'</a></div>';
		} else {
			echo '<div id="to_all_users" class="all_users"><img src="'.$CFG->wwwroot.'/blocks/email_list/email/images/delete.png" height="16" width="16" alt="'.get_string("course").'" /> <a href="'.$CFG->wwwroot.'/blocks/email_list/email/participants.php?id='.$courseid.'&amp;group='.$selgroup.'&amp;perpage=7&amp;search='.$search.'&amp;roleid='.$roleid.'&amp;fname='.$firstinitial.'&amp;lname='.$lastinitial.'">'.get_string('showperpage', '', 7).'</a></div>';
		}
	}
		// Print close button
		close_window_button();

	print_footer();
?>

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

    global $CFG, $USER, $db;



    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
	$courseid     = optional_param('courseid', SITEID, PARAM_INT); //Courseid

    $a  = optional_param('a', 0, PARAM_INT);  // concerns ID

	$mode  = optional_param('mode', 0, PARAM_INT);  // concerns mode

	$group = optional_param('group', -1, PARAM_INT);

	$updatepref = optional_param('updatepref', -1, PARAM_INT);

	require_login();

/// Print the main part of the page

	$strconcerns = get_string("modulenameplural", "ilpconcern");
    $strconcern  = get_string("modulename", "ilpconcern");
    $stredit = get_string("edit");
    $strdelete = get_string("delete");
    $strcomments = get_string("comments", "ilpconcern");
	$strilp = get_string("ilp", "block_ilp");
	$strilps = get_string('ilps','block_ilp');

	$navlinks = array();

	if($id != 0 || $courseid != SITEID){ //module is accessed through a course use course context

		if($id != 0) {

			if (! $cm = get_record("course_modules", "id", $id)) {

				error("Course Module ID was incorrect");

			}



			if (! $course = get_record("course", "id", $cm->course)) {

				error("Course is misconfigured");

			}



			if (! $concerns = get_record("ilpconcern", "id", $cm->instance)) {

				error("Course module is incorrect");

			}

			$context = get_context_instance(CONTEXT_MODULE, $cm->id);

			$link_values = '?id='.$cm->id;

			$baseurl = $CFG->wwwroot.'/mod/ilpconcern/view_students.php?id='.$id;

			}else{

			$course = get_record('course', 'id', $courseid);

			$context = get_context_instance(CONTEXT_COURSE, $course->id);

			$link_values = '?courseid='.$course->id;
			$baseurl = $CFG->wwwroot.'/mod/ilpconcern/view_students.php?courseid='.$course->id;

		}

		$navlinks[] = array('name' => $course->shortname, 'link' => "$CFG->wwwroot/course/view.php?id=$course->id", 'type' => 'misc');

		$navlinks[] = array('name' => $strilps, 'link' => "$CFG->wwwroot/blocks/ilp/list.php?courseid=$course->id", 'type' => 'misc');
				
		$navlinks[] = array('name' => $strconcerns, 'link' => FALSE, 'type' => 'misc');
		
		$navigation = build_navigation($navlinks);
		print_header_simple("$course->shortname: $strconcerns", '', $navigation,'', '', true, '','');


			/* first we check to see if the form has just been submitted

         * to request user_preference updates

         */



	require_capability('mod/ilpconcern:viewclass', $context);



	if ($updatepref > 0){

		$perpage = optional_param('perpage', 10, PARAM_INT);

		$perpage = ($perpage <= 0) ? 10 : $perpage ;

		set_user_preference('concerns_perpage', $perpage);

	}



	/* next we get perpage and from database

     */



	$perpage = get_user_preferences('concerns_perpage', 10);

	$teacherattempts = false; /// Temporary measure

	$page    = optional_param('page', 0, PARAM_INT);

/// Check to see if groups are being used in this course
/// and if so, set $currentgroup to reflect the current group

    $groupmode    = groups_get_course_groupmode($course);   // Groups are being used
    $currentgroup = groups_get_course_group($course, true);

    if (!$currentgroup) {      // To make some other functions work better later
        $currentgroup  = NULL;
    }

    $isseparategroups = ($course->groupmode == SEPARATEGROUPS and $course->groupmodeforce and
                         !has_capability('moodle/site:accessallgroups', $context));

    /// Get all teachers and students

    $users = get_users_by_capability($context, 'mod/ilpconcern:view'); // everyone with this capability set to non-prohibit



	print_heading(get_string('reportsset', 'ilpconcern', $course->shortname));

    groups_print_course_menu($course, $baseurl);

	if ($roles = get_roles_used_in_context($context)) {



        // We should exclude "admin" users (those with "doanything" at site level) because

        // Otherwise they appear in every participant list



        $sitecontext = get_context_instance(CONTEXT_SYSTEM);

        $doanythingroles = get_roles_with_capability('moodle/site:doanything', CAP_ALLOW, $sitecontext);



        foreach ($roles as $role) {

            if (isset($doanythingroles[$role->id])) {   // Avoid this role (ie admin)

                unset($roles[$role->id]);

                continue;

            }

            $rolenames[$role->id] = strip_tags(format_string($role->name));   // Used in menus etc later on

        }

    }

	$tablecolumns = array('picture', 'fullname');
	$tableheaders = array('', get_string('fullname'));

	if($CFG->ilpconcern_status_per_student == 1){
		$tablecolumns[] .= 'status';
		$tableheaders[] .= get_string('studentstatus', 'ilpconcern');
	}

	if($CFG->ilpconcern_report1 == 1){
		$tablecolumns[] .= 'report1';
		$tableheaders[] .= '';
	}
	if($CFG->ilpconcern_report2 == 1){
		$tablecolumns[] .= 'report2';
		$tableheaders[] .= '';
	}
	if($CFG->ilpconcern_report3 == 1){
		$tablecolumns[] .= 'report3';
		$tableheaders[] .= '';
	}
	if($CFG->ilpconcern_report4 == 1){
		$tablecolumns[] .= 'report4';
		$tableheaders[] .= '';
	}

	require_once($CFG->libdir.'/tablelib.php');

	$table = new flexible_table('mod-ilpconcern-reports');

	$table->define_columns($tablecolumns);

	$table->define_headers($tableheaders);

	$table->define_baseurl($baseurl);


	$table->sortable(true, 'lastname');

	$table->collapsible(false);

	$table->initialbars(true);



	$table->column_suppress('picture');

	$table->column_class('picture', 'picture');

	$table->column_class('fullname', 'fullname');



	$table->set_attribute('cellspacing', '0');

	$table->set_attribute('id', 'attempts');

	$table->set_attribute('class', 'submissions');

	$table->set_attribute('width', '90%');

	$table->set_attribute('align', 'center');



	// Start working -- this is necessary as soon as the niceties are over

	$table->setup();



	// we are looking for all users with this role assigned in this context or higher

    if ($usercontexts = get_parent_contexts($context)) {

        $listofcontexts = '('.implode(',', $usercontexts).')';

    } else {

        $sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);

        $listofcontexts = '('.$sitecontext->id.')'; // must be site

    }



	$select = 'SELECT u.id, u.firstname, u.lastname, u.picture';

    $from = ' FROM '.$CFG->prefix.'user u INNER JOIN

    '.$CFG->prefix.'role_assignments r on u.id=r.userid LEFT OUTER JOIN

    '.$CFG->prefix.'user_lastaccess ul on (r.userid=ul.userid and ul.courseid = '.$course->id.')';

    if($CFG->ilpconcern_status_per_student = 1) {
		$select .= ', s.status';
		$from .= 'LEFT JOIN '.$CFG->prefix.'ilpconcern_status s ON u.id = s.userid ';
	}


    // excluse users with these admin role assignments

    if ($doanythingroles) {

        $adminroles = 'AND r.roleid NOT IN (';



        foreach ($doanythingroles as $aroleid=>$role) {

            $adminroles .= "$aroleid,";

        }

        $adminroles = rtrim($adminroles,",");

        $adminroles .= ')';

    } else {

        $adminroles = '';

    }



	// join on 2 conditions

    // otherwise we run into the problem of having records in ul table, but not relevant course

    // and user record is not pulled out

    $where  = "WHERE (r.contextid = $context->id OR r.contextid in $listofcontexts)

        AND u.deleted = 0

        AND (ul.courseid = $course->id OR ul.courseid IS NULL)

        AND u.username <> 'guest'

		AND r.roleid = 5

        $adminroles";





    $wheresearch = '';



	if ($currentgroup) {    // Displaying a group by choice

        // FIX: TODO: This will not work if $currentgroup == 0, i.e. "those not in a group"

        $from  .= 'LEFT JOIN '.$CFG->prefix.'groups_members gm ON u.id = gm.userid ';

        $where .= ' AND gm.groupid = '.$currentgroup;

    }



	if ($table->get_sql_where()) {

        $where .= ' AND '.$table->get_sql_where();

    }



    if ($table->get_sql_sort()) {

        $sort = ' ORDER BY '.$table->get_sql_sort();

    } else {

        $sort = '';

    }



	$nousers = get_records_sql($select.$from.$where.$wheresearch.$sort);



	$table->pagesize($perpage, count($nousers));

	///offset used to calculate index of student in that particular query, needed for the pop up to know who's next

    $offset = $page * $perpage;



	if (($ausers = get_records_sql($select.$from.$where.$wheresearch.$sort, $table->get_page_start(), $table->get_page_size())) !== false) {


            foreach ($ausers as $auser) {

			$picture = print_user_picture($auser->id, $course->id, $auser->picture, false, true);
			$row = array($picture, fullname($auser));

			if($CFG->ilpconcern_status_per_student == 1){

				$studentstatus = $auser->status;

				if($studentstatus){
					switch ($studentstatus) {
						case "0":
							$thisstudentstatus = get_string('green', 'ilpconcern');
							break;
						case "1":
							$thisstudentstatus = get_string('amber', 'ilpconcern');
							break;
						case "2":
							$thisstudentstatus = get_string('red', 'ilpconcern');
							break;
						case "3":
							$thisstudentstatus = get_string('withdrawn', 'ilpconcern');
							break;
					}
				}else{
						$studentstatus = 0;
						$thisstudentstatus = get_string('green', 'ilpconcern');
				}

				$row[] .= '<span class="status-'.$studentstatus.'">'.$thisstudentstatus.'</span>';
			}

			if($CFG->ilpconcern_report1 == 1){
				$report1total = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE setforuserid = '.$auser->id.' AND status = 0' );
				$report1text  = '<a href="concerns_view.php'.$link_values.'&amp;userid='.$auser->id.'&amp;status=0">'.$report1total.' '.get_string('report1plural', 'ilpconcern').'</a>';
				$row[] .= $report1text;
			}
			if($CFG->ilpconcern_report2 == 1){
				$report2total = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE setforuserid = '.$auser->id.' AND status = 1' );
				$report2text  = '<a href="concerns_view.php'.$link_values.'&amp;userid='.$auser->id.'&amp;status=1">'.$report2total.' '.get_string('report2plural', 'ilpconcern').'</a>';
				$row[] .= $report2text;
			}
			if($CFG->ilpconcern_report3 == 1){
				$report3total = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE setforuserid = '.$auser->id.' AND status = 2' );
				$report3text  = '<a href="concerns_view.php'.$link_values.'&amp;userid='.$auser->id.'&amp;status=2">'.$report3total.' '.get_string('report3plural', 'ilpconcern').'</a>';
				$row[] .= $report3text;
			}
			if($CFG->ilpconcern_report4 == 1){
				$report4total = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE setforuserid = '.$auser->id.' AND status = 3' );
				$report4text  = '<a href="concerns_view.php'.$link_values.'&amp;userid='.$auser->id.'&amp;status=3">'.$report4total.' '.get_string('report4plural', 'ilpconcern').'</a>';
				$row[] .= $report4text;
			}

               $table->add_data($row);

            }

        }





        $table->print_html();  /// Print the whole table



		/// Mini form for setting user preference

        echo '<br />';

		if($id != 0 ){
        echo '<form name="options" action="view_students.php?id='.$cm->id.'" method="post">';
		}elseif($courseid != SITEID){
			echo '<form name="options" action="view_students.php?courseid='.$course->id.'" method="post">';
		}

        echo '<input type="hidden" id="updatepref" name="updatepref" value="1" />';

        echo '<table id="optiontable" align="center">';

        echo '<tr align="right"><td>';

        echo '<label for="perpage">'.get_string('pagesize', 'ilpconcern').'</label>';

        echo ':</td>';

        echo '<td align="left">';

        echo '<input type="text" id="perpage" name="perpage" size="1" value="'.$perpage.'" />';

        helpbutton('pagesize', get_string('pagesize', 'ilpconcern'), 'ilpconcern');

        echo '</td></tr>';

        echo '<tr>';

        echo '<td colspan="2" align="right">';

        echo '<input type="submit" value="'.get_string('savepreferences').'" />';

        echo '</td></tr></table>';

        echo '</form>';



		$footer = $course;

    }else{ //module is accessed independent of a course use user context

		//$context = get_context_instance(CONTEXT_USER, $user->id);

		$link_values = '';

		$navlinks[] = array('name' => $strilps, 'link' => "$CFG->wwwroot/blocks/ilp/list.php?courseid=$course->id", 'type' => 'misc');
				
		$navlinks[] = array('name' => $strconcerns, 'link' => FALSE, 'type' => 'misc');
		
		$navigation = build_navigation($navlinks);
		print_header_simple($strconcerns, '', $navigation,'', '', true, '','');

		$baseurl = $CFG->wwwroot.'/mod/ilpconcern/view_students.php';







		if ($usercontexts = get_records_sql("SELECT c.instanceid, c.instanceid, u.firstname, u.lastname

                                         FROM {$CFG->prefix}role_assignments ra,

                                              {$CFG->prefix}context c,

                                              {$CFG->prefix}user u

                                         WHERE ra.userid = $USER->id

                                         AND   ra.contextid = c.id

                                         AND   c.instanceid = u.id

                                         AND   c.contextlevel = ".CONTEXT_USER)) {



		print_heading(get_string('mystudents', 'ilpconcern'));



		$tablecolumns = array('user', 'concerns', 'ilpconcern');

        $tableheaders = array('', '', '');



        require_once($CFG->libdir.'/tablelib.php');

        $table = new flexible_table('personal-tutor-students');



        $table->define_columns($tablecolumns);

        $table->define_headers($tableheaders);

        $table->define_baseurl($baseurl);



        $table->sortable(false);

        $table->collapsible(false);

        $table->initialbars(false);



        $table->column_class('user', 'user');

        $table->column_class('concerns', 'ilpconcern');

		$table->column_class('concerns', 'ilpconcern');



        $table->set_attribute('cellspacing', '5');

        $table->set_attribute('cellpadding', '5');

        $table->set_attribute('id', 'mystudents');



        // Start working -- this is necessary as soon as the niceties are over

        $table->setup();



        foreach ($usercontexts as $usercontext) {



			$user = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$usercontext->instanceid.'&amp;course=1">'.fullname($usercontext).'</a>';

			$concerns = '<a href="concerns_view.php?userid='.$usercontext->instanceid.'">'.get_string('concerns', 'ilpconcern').'</a>';

			$concern = 'Concern';



			$row = array($user, $concerns, $concern);

			$table->add_data($row);

            }





        $table->print_html();  /// Print the whole table



		}else{

			print_heading(get_string('nostudents', 'ilpconcern'));

		}



		$footer = '';

	}



	//Allow users to see their own profile, but prevent others

	//require_capability('mod/ilpconcern:view', $context);



	print_footer($footer);



?>
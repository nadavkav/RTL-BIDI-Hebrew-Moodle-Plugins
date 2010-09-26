<?php // $Id: fnsettings.php,v 1.1 2009/04/17 20:45:22 mchurch Exp $

/// Editing interface for FN specific site settings

    require_once('../../../config.php');
    require_once('../../lib.php');
    require_once('lib.php');

    $courseid      = optional_param('id', SITEID, PARAM_INT);   // Course id
    $selectedgroup = optional_param('group', NULL);             // Current group id
    $edit          = optional_param('edit', '', PARAM_ALPHA);

    if (! $course = get_record('course', 'id', $courseid) ) {
        error("That's an invalid course id");
    }

    require_login($course->id);
    
    if (!isadmin()) {
    	error('You must be an administrator to use this function.');
    }

    if (empty($CFG->fncoursename)) {
        set_config('fncoursename', get_string('course'));
    }
    if (empty($CFG->fncoursesname)) {
        set_config('fncoursesname', get_string('courses'));
    }
    if (!isset($CFG->fnuserprofiles)) {
        set_config('fnuserprofiles', 1);
        $CFG->fnuserprofiles = 1;
    }
    if (!isset($CFG->fnseparateprofiles)) {
        set_config('fnseparateprofiles', 1);
        $CFG->fnseparateprofiles = 1;
    }
/// Print the header of the page

    $strcourse = $CFG->fncoursename;
    $strcourses = $CFG->fncoursesname;
    $loggedinas = "<p class=\"logininfo\">".user_login_string($course, $USER)."</p>";

/// First, process any inputs there may be.
    if ($data = data_submitted() and confirm_sesskey()) {

        // If the course names are empty, default them to the language strings.
        if (empty($data->fncoursename)) {
        	$data->fncoursename = get_string('course');
        }
        if (empty($data->fncoursesname)) {
            $data->fncoursesname = get_string('courses');
        }
        set_config('fncoursename', clean_param($data->fncoursename, PARAM_CLEAN));
        set_config('fncoursesname', clean_param($data->fncoursesname, PARAM_CLEAN));

        set_config('fnuserprofiles', $data->fnuserprofiles);
        set_config('fnseparateprofiles', $data->fnseparateprofiles);

        redirect($CFG->wwwroot.'/course/view.php?id='.$course->id);
    }

    $sesskey = !empty($USER->id) ? $USER->sesskey : '';

/// Print out the complete form
    print_header("$course->shortname: FN Settings", "$course->fullname", 
                 "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> 
                  -> FN Settings", "", "", true, false, $loggedinas);

?>

<div align="center" style="text-align: center; width=100%;">
<form name="form1" id="form1" method="post" action="fnsettings.php">
<input type="hidden" name="sesskey" value="<?php echo $sesskey; ?>" />
  <table cellpadding="9" width="100%">
    <tr align="center">
      <td align="right">
        <?php print_string('fnuserprofiles', 'block_fn_admin'); ?>?
      </td>
      <td align="left">
        <?php
            $profs = array(0 => get_string('moodleprofile', 'block_fn_admin'),
                           1 => get_string('fnprofile', 'block_fn_admin'),
                           2 => get_string('regprofile', 'block_fn_admin'));
            choose_from_menu($profs, 'fnuserprofiles', $CFG->fnuserprofiles);
        ?>
      </td>
    </tr>
    <tr align="center">
      <td align="right">
        <?php print_string('fnseparateprofiles', 'block_fn_admin'); ?>?
      </td>
      <td align="left">
        <?php
            $profs = array(0 => get_string('no'),
                           1 => get_string('yes'));
            choose_from_menu($profs, 'fnseparateprofiles', $CFG->fnseparateprofiles);
        ?>
      </td>
    </tr>
    <tr align="center">
      <td align="right">
        <?php print_string('fncoursename', 'block_fn_admin'); ?>?
      </td>
      <td align="left">
        <input type="text" name="fncoursename" size="20" value="<?php echo $CFG->fncoursename; ?>" />
      </td>
    </tr>
    <tr align="center">
      <td align="right">
        <?php print_string('fncoursesname', 'block_fn_admin'); ?>?
      </td>
      <td align="left">
        <input type="text" name="fncoursesname" size="20" value="<?php echo $CFG->fncoursesname; ?>" />
      </td>
    </tr>
    <tr>
      <td colspan="2" align="center">
        <input type="submit" value="<?php print_string('savechanges'); ?>" />
      </td>
    </tr>
  </table>
</form>
</div>

<?php
    print_footer($course);
?>
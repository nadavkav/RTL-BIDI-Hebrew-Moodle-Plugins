<?php //$Id: index.php,v 1.3 2009/04/17 13:53:42 argentum Exp $
/**
* user bulk action script for batch user enrolment
*/

require_once('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');

$allcourses  = optional_param('allcourses', '', PARAM_CLEAN);
$selcourses  = optional_param('selcourses', '', PARAM_CLEAN);
$accept      = optional_param('accept', 0, PARAM_BOOL);
$confirm     = optional_param('confirm', 0, PARAM_BOOL);
$cancel      = optional_param('cancel', 0, PARAM_BOOL);
$searchtext  = optional_param('searchtext', '', PARAM_RAW);
$groupname   = optional_param('groupname', '', PARAM_RAW);
$roleassign  = optional_param('roleassign', '', PARAM_RAW);
$showall     = optional_param('showall', 0, PARAM_BOOL);
$listadd     = optional_param('add', 0, PARAM_BOOL);
$listremove  = optional_param('remove', 0, PARAM_BOOL);
$removeall   = optional_param('removeall', 0, PARAM_BOOL);
$hidden      = optional_param('hidden', 0, PARAM_BOOL);

admin_externalpage_setup('userbulk');
check_action_capabilities('enroltocourses', true);

$return = $CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk.php';

if ($showall) {
    $searchtext = '';
}

$strsearch = get_string('search');
$langdir = $CFG->dirroot.'/admin/user/actions/enroltocourses/lang/';
$pluginname = 'bulkuseractions_enroltocourses';

if (empty($SESSION->bulk_users) || $cancel) {
    redirect($return);
}

if (!isset($SESSION->bulk_courses) || $removeall) {
    $SESSION->bulk_courses = array();
}

// course selection add/remove actions
if ($listadd && !empty($allcourses)) {
    foreach ($allcourses as $course) {
        if (!in_array( $course, $SESSION->bulk_courses )) {
            $SESSION->bulk_courses[] = $course;
        }
    }
}

if ($listremove && !empty($selcourses)) {
    foreach ($selcourses as $course) {
        unset($SESSION->bulk_courses[ array_search($course, $SESSION->bulk_courses) ]);
    }
}

// show the confirmation message
if ($accept) {
    if (empty( $SESSION->bulk_courses )) {
        redirect( $return );
    }

    // generate user name list
    $in = implode(',', $SESSION->bulk_users);
    $userlist = get_records_select_menu('user', "id IN ($in)", 'fullname', 'id,'.sql_fullname().' AS fullname');
    $usernames = implode('<br />', $userlist);

    // generate course name list
    $courselist = array();
    $courses = get_courses(0, 'c.sortorder ASC', 'c.id, c.fullname');
    foreach ($courses as $course) {
        if (in_array( $course->id, $SESSION->bulk_courses )) {
            $courselist[] = $course->fullname;
        }
    }

    // generate the message
    $confmsg = get_string('confirmpart1', $pluginname, NULL, $langdir).$usernames;
    $confmsg .= get_string('confirmpart2', $pluginname, NULL, $langdir);
    $confmsg .= implode('<br />', $courselist);
    $groupname = stripslashes($groupname);
    if (!empty($groupname)) {
        $confmsg .= get_string('confirmpart3', $pluginname, NULL, $langdir).s($groupname, false);
    }

    // get system roles info and add the selected role to the message
    if ($roleassign != 0) {
        $role = get_record('role', 'id', $roleassign);
        $rolename = $role->name;
    } else {
        $rolename = get_string('default', $pluginname, NULL, $langdir);
    }
    $confmsg .= get_string( 'confirmpart4', $pluginname, NULL, $langdir ).$rolename;
    if ($hidden) {
        $confmsg .= ' ('.get_string( 'hiddenassign' ).')';
    }
    $confmsg .= '?';

    $optionsyes['confirm'] = true;
    $optionsyes['groupname'] = $groupname;
    $optionsyes['roleassign'] = $roleassign;
    $optionsyes['hidden'] = $hidden;

    // print the message
    admin_externalpage_print_header();
    print_heading(get_string('confirmation', 'admin'));
    notice_yesno( $confmsg, 'index.php', $return, $optionsyes, NULL, 'post', 'get');
    admin_externalpage_print_footer();
    die;
}

// action confirmed, perform it
if( $confirm ) {
    if(empty($SESSION->bulk_courses)) {
        redirect($return);
    }

    // for each course, get the default role if needed and check the selected group
    foreach ($SESSION->bulk_courses as $course) {
        $context = get_context_instance(CONTEXT_COURSE, $course);
        $in = implode(',', $SESSION->bulk_users);
        $groupid = false;
        if ($roleassign == 0) {
            $defrole = get_default_course_role( $context );
            $roleassign = $defrole->id;
        }
        if (!empty($groupname)) {
            $groupid = groups_get_group_by_name($course, stripslashes($groupname));
        }
        // for each user, enrol them to the course with the selected role,
        // and add to the selected group if available
        if ($rs = get_recordset_select('user', "id IN ($in)")) {
            while ($user = rs_fetch_next_record($rs)) {
                role_assign($roleassign, $user->id, 0, $context->id, 0, 0, $hidden);
                if ($groupid !== false) {
                    groups_add_member($groupid, $user->id);
                }
            }
        }
        rs_close($rs);
    }
    // we're done, exit now
    admin_externalpage_print_header();
    redirect($return, get_string('changessaved'));
}

/**
* This function generates the list of courses for <select> control
* using the specified string filter and/or course id's filter
*
* @param string $strfilter The course name filter
* @param array $arrayfilter Course ID's filter, NULL by default, which means not to use id filter
* @return string
*/
function gen_course_list( $strfilter = '', $arrayfilter = NULL, $filtinvert = false )
{
    $courselist = array();
    $catcnt = 0;
    // get the list of course categories
    $categories = get_categories();
    foreach ($categories as $cat) {
        // for each category, add the <optgroup> to the string array first
        $courselist[$catcnt] = '<optgroup label="'.htmlspecialchars( $cat->name ).'">';
        // get the course list in that category
        $courses = get_courses($cat->id, 'c.sortorder ASC', 'c.fullname, c.id');
        $coursecnt = 0;

        // for each course, check the specified filter
        foreach ($courses as $course) {
            if (( !empty($strfilter) && strripos($course->fullname, $strfilter) === false ) || ( $arrayfilter !== NULL && in_array($course->id, $arrayfilter) === $filtinvert )) {
                continue;
            }
            // if we pass the filter, add the option to the current string
            $courselist[$catcnt] .= '<option value="'.$course->id.'">'.$course->fullname.'</option>';
            $coursecnt++;
        }

        // if no courses pass the filter in that category, delete the current string
        if ($coursecnt == 0) {
            unset($courselist[$catcnt]);
        } else {
            $courselist[$catcnt] .= '</optgroup>';
            $catcnt++;
        }
    }

    // return the html code with categorized courses
    return implode(' ', $courselist);
}

// generate full and selected course lists
$coursenames = gen_course_list($searchtext, $SESSION->bulk_courses, true);
$selcoursenames = gen_course_list('', $SESSION->bulk_courses);

// generate the list of groups names from the selected courses.
// groups with the same name appear only once
$groupnames = array();
foreach ($SESSION->bulk_courses as $course) {
    $cgroups = groups_get_all_groups($course);
    foreach ($cgroups as $cgroup) {
        if (!in_array($cgroup->name, $groupnames)) {
            $groupnames[] = $cgroup->name;
        }
    }
}

sort($groupnames);

// generate html code for the group select control
foreach ($groupnames as $key => $name) {
    $groupnames[$key] = '<option value="'.s($name, true).'" >'.s($name, true).'</option>';
}

$groupnames = '<option value="">'.get_string('nogroup', $pluginname, NULL, $langdir).'</option> '.implode(' ', $groupnames);

// get the system roles list and generate html code for role select control
$roles = array();
foreach ($SESSION->bulk_courses as $course) {
    $context = get_context_instance(CONTEXT_COURSE, $course);
    $courseroles = get_assignable_roles($context, 'name', ROLENAME_ORIGINAL);
    if (empty($roles)) {
        $roles = $courseroles;
    } else {
        $roles = array_intersect_key($roles, $courseroles);
    }
}
$roles[0] = get_string('default', $pluginname, NULL, $langdir);

$rolenames = '';
foreach ($roles as $key => $name) {
    $rolenames .= '<option value="'.$key.'"';
    if ($key == $roleassign) {
        $rolenames .= ' selected ';
    }
    $rolenames .= '>'.$name.'</option> ';
}

// print the general page
admin_externalpage_print_header();

?>
<div id="addmembersform">
    <h3 class="main"><?php echo get_string( 'title', $pluginname, NULL, $langdir ) ?></h3>

    <form id="addform" method="post" action="index.php">
    <div>

    <table cellpadding="6" class="generaltable generalbox groupmanagementtable boxaligncenter" summary="">
    <tr>
      <td valign="top">
          <p>
            <label for="allcourses"><?php echo get_string( 'allcourses', $pluginname, NULL, $langdir ) ?></label>
          </p>
          <select name="allcourses[]" size="20" id="allcourses" multiple="multiple"
                  onfocus="document.getElementById('addform').add.disabled=false;
                           document.getElementById('addform').remove.disabled=true;
                           document.getElementById('addform').selcourses.selectedIndex=-1;"
                  onclick="this.focus();">
          <?php echo $coursenames ?>
          </select>

    <br />
         <label for="searchtext" class="accesshide"><?php p($strsearch) ?></label>
         <input type="text" name="searchtext" id="searchtext" size="21" value="<?php p($searchtext, true) ?>"
                  onfocus ="getElementById('addform').add.disabled=true;
                            getElementById('addform').remove.disabled=true;
                            getElementById('addform').allcourses.selectedIndex=-1;
                            getElementById('addform').selcourses.selectedIndex=-1;"
                  onkeydown = "var keyCode = event.which ? event.which : event.keyCode;
                               if (keyCode == 13) {
                                    getElementById('addform').previoussearch.value=1;
                                    getElementById('addform').submit();
                               } " />
         <input name="search" id="search" type="submit" value="<?php p($strsearch) ?>" />
         <?php
              if (!empty($searchtext)) {
                  echo '<br /><input name="showall" id="showall" type="submit" value="'.get_string('showall').'" />'."\n";
              }
         ?>
    </td>
      <td align="center"; valign="top">

        <?php check_theme_arrows(); ?>
        <p class="arrow_button"><br />
            <input name="add" id="add" type="submit" disabled value="<?php echo '&nbsp;'.$THEME->rarrow.' &nbsp; &nbsp; '.get_string('add'); ?>" title="<?php print_string('add'); ?>" />
            <br />
            <input name="remove" id="remove" type="submit" disabled value="<?php echo '&nbsp; '.$THEME->larrow.' &nbsp; &nbsp; '.get_string('remove'); ?>" title="<?php print_string('remove'); ?>" />
        </p>
        <br />
        <label for="hidden">
            <?php print_string('hiddenassign') ?> <br />
            <input type="checkbox" name="hidden" value="1" <?php if ($hidden) echo 'checked ' ?>/>
            <img src="<?php echo $CFG->pixpath; ?>/t/hide.gif" alt="<?php print_string('hiddenassign') ?>" class="hide-show-image" />
        </label>

      </td>

      <td valign="top">
          <p>
            <label for="selcourses"><?php echo get_string( 'selectedcourses', $pluginname, NULL, $langdir ) ?></label>
          </p>
          <select name="selcourses[]" size="20" id="selcourses" multiple="multiple"
                  onfocus="document.getElementById('addform').remove.disabled=false;
                           document.getElementById('addform').add.disabled=true;
                           document.getElementById('addform').allcourses.selectedIndex=-1;"
                  onclick="this.focus();">
          <?php echo $selcoursenames; ?>
         </select>
    <br />
    <input name="removeall" id="removeall" type="submit" value="<?php echo get_string('removeall', 'bulkusers') ?>" />
    <tr><td align="center">
    <label for="roleassign"><?php echo get_string( 'roletoset', $pluginname, NULL, $langdir ) ?></label>
    <br />
    <select name="roleassign" id="roleassign" size="1">
    <?php echo $rolenames ?>
    </select>
    </td><td align="center">
    <label for="groupname"><?php echo get_string( 'autogroup', $pluginname, NULL, $langdir ) ?></label>
    <br />
    <select name="groupname" id="groupname" size="1" >
        <?php echo $groupnames; ?>
    </select>
    </td></tr>
    <tr><td></td><td align="center">
        <p><input type="submit" name="cancel" value="<?php echo get_string( 'cancel' ) ?>" />
        <input type="submit" name="accept" value="<?php echo get_string( 'accept', $pluginname, NULL, $langdir ) ?>" /></p>
    </td></tr>

    </table>
    </div>
    </form>
</div>
<?php
admin_externalpage_print_footer();
?>

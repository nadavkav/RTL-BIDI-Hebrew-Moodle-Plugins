<?php //$Id: index.php,v 1.2 2009/03/20 13:31:24 argentum Exp $
/**
* user bulk action script for batch user unenrolment
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
$showall     = optional_param('showall', 0, PARAM_BOOL);
$listadd     = optional_param('add', 0, PARAM_BOOL);
$listremove  = optional_param('remove', 0, PARAM_BOOL);
$removeall   = optional_param('removeall', 0, PARAM_BOOL);

admin_externalpage_setup('userbulk');
check_action_capabilities('removefromcourses', true);

$return = $CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk.php';

if ($showall) {
    $searchtext = '';
}

$strsearch = get_string('search');
$langdir = $CFG->dirroot.'/admin/user/actions/removefromcourses/lang/';
$pluginname = 'bulkuseractions_removefromcourses';

if (empty($SESSION->bulk_users) || $cancel) {
    redirect($return);
}

if (!isset($SESSION->bulk_courses) || $removeall)
    $SESSION->bulk_courses = array();

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
if( $accept ) {
    if( empty( $SESSION->bulk_courses ) )
        redirect( $return );

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
    $confmsg .= implode('<br />', $courselist) . '?';
    $optionsyes = array();
    $optionsyes['confirm'] = true;

    // print the message
    admin_externalpage_print_header();
    print_heading(get_string('confirmation', 'admin'));
    notice_yesno($confmsg, 'index.php', $return, $optionsyes, NULL, 'post', 'get');
    admin_externalpage_print_footer();
    die;
}

// action confirmed, perform it
if ($confirm) {
    if (empty( $SESSION->bulk_courses)) {
        redirect($return);
    }

    foreach ($SESSION->bulk_courses as $course) {
        $context = get_context_instance(CONTEXT_COURSE, $course);
        $in = implode(',', $SESSION->bulk_users);
        // for each user, unenrol them from the course
        if ($rs = get_recordset_select('user', "id IN ($in)")) {
            while ($user = rs_fetch_next_record($rs))
                role_unassign(0, $user->id, 0, $context->id);
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
function gen_course_list( $strfilter = '', $arrayfilter = NULL )
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
            if (( !empty($strfilter) && strripos($course->fullname, $strfilter) === false ) || ( $arrayfilter !== NULL && in_array($course->id, $arrayfilter) === false )) {
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
$availablecourses = array();
foreach ($SESSION->bulk_users as $user) {
    $usercourses = get_my_courses($user);
    foreach($usercourses as $key=>$junk) {
        if(!in_array($key, $availablecourses)) {
            $availablecourses[] = $key;
        }
    }
}
$coursenames = gen_course_list($searchtext, array_diff($availablecourses, $SESSION->bulk_courses));
$selcoursenames = gen_course_list('', array_intersect($availablecourses, $SESSION->bulk_courses));

// print the general page
admin_externalpage_print_header();

?>
<div id="removemembersform">
    <h3 class="main"><?php echo get_string( 'title', $pluginname, NULL, $langdir ) ?></h3>

    <form id="removeform" method="post" action="index.php">
    <div>

    <table cellpadding="6" class="generaltable generalbox groupmanagementtable boxaligncenter" summary="">
    <tr>
      <td valign="top">
          <p>
            <label for="allcourses"><?php echo get_string( 'allcourses', $pluginname, NULL, $langdir ) ?></label>
          </p>
          <select name="allcourses[]" size="20" id="allcourses" multiple="multiple"
                  onfocus="document.getElementById('removeform').add.disabled=false;
                           document.getElementById('removeform').remove.disabled=true;
                           document.getElementById('removeform').selcourses.selectedIndex=-1;"
                  onclick="this.focus();">
          <?php echo $coursenames ?>
          </select>

    <br />
         <label for="searchtext" class="accesshide"><?php p($strsearch) ?></label>
         <input type="text" name="searchtext" id="searchtext" size="21" value="<?php p($searchtext, true) ?>"
                  onfocus ="getElementById('removeform').add.disabled=true;
                            getElementById('removeform').remove.disabled=true;
                            getElementById('removeform').allcourses.selectedIndex=-1;
                            getElementById('removeform').selcourses.selectedIndex=-1;"
                  onkeydown = "var keyCode = event.which ? event.which : event.keyCode;
                               if (keyCode == 13) {
                                    getElementById('removeform').previoussearch.value=1;
                                    getElementById('removeform').submit();
                               } " />
         <input name="search" id="search" type="submit" value="<?php p($strsearch) ?>" />
         <?php
              if (!empty($searchtext)) {
                  echo '<br /><input name="showall" id="showall" type="submit" value="'.get_string('showall').'" />'."\n";
              }
         ?>
    </td>
      <td valign="top">

        <?php check_theme_arrows(); ?>
        <p class="arrow_button"><br />
            <input name="add" id="add" type="submit" disabled value="<?php echo '&nbsp;'.$THEME->rarrow.' &nbsp; &nbsp; '.get_string('add'); ?>" title="<?php print_string('add'); ?>" />
            <br />
            <input name="remove" id="remove" type="submit" disabled value="<?php echo '&nbsp; '.$THEME->larrow.' &nbsp; &nbsp; '.get_string('remove'); ?>" title="<?php print_string('remove'); ?>" />
        </p>
      </td>

      <td valign="top">
          <p>
            <label for="selcourses"><?php echo get_string( 'selectedcourses', $pluginname, NULL, $langdir ) ?></label>
          </p>
          <select name="selcourses[]" size="20" id="selcourses" multiple="multiple"
                  onfocus="document.getElementById('removeform').remove.disabled=false;
                           document.getElementById('removeform').add.disabled=true;
                           document.getElementById('removeform').allcourses.selectedIndex=-1;"
                  onclick="this.focus();">
          <?php echo $selcoursenames; ?>
         </select>
    <br />
    <input name="removeall" id="removeall" type="submit" value="<?php echo get_string('removeall', 'bulkusers') ?>" />
    <tr><td></td><td>
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

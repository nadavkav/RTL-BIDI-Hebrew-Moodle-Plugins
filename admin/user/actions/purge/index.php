<?php //$Id: index.php,v 1.1 2009/03/10 10:01:57 argentum Exp $
/**
* user bulk action script for batch deleting user activity and full unenroling
*/

require_once('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');
require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->dirroot.'/mod/lesson/lib.php');
require_once($CFG->dirroot.'/mod/assignment/lib.php');

$reset    = optional_param('reset', 0, PARAM_BOOL);
$accept    = optional_param('accept', 0, PARAM_BOOL);
$cancel      = optional_param('cancel', 0, PARAM_BOOL);
$allcourses  = optional_param('allcourses', '', PARAM_CLEAN);
$selcourses  = optional_param('selcourses', '', PARAM_CLEAN);
$searchtext  = optional_param('searchtext', '', PARAM_RAW);
$showall     = optional_param('showall', 0, PARAM_BOOL);
$listadd     = optional_param('add', 0, PARAM_BOOL);
$listremove  = optional_param('remove', 0, PARAM_BOOL);
$removeall   = optional_param('removeall', 0, PARAM_BOOL);

admin_externalpage_setup('userbulk');
check_action_capabilities('purge', true);

if ($showall) {
    $searchtext = '';
}

$strsearch = get_string('search');
$return = $CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk.php';
$langdir = $CFG->dirroot.'/admin/user/actions/purge/lang/';
$pluginname = 'bulkuseractions_purge';

if ($reset) {
    unset($SESSION->purge_progress);
}

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

$unaccessible = array_diff($SESSION->bulk_courses, $availablecourses);
if (!empty($unaccessible)) {
    $SESSION->bulk_courses = array();
}

$coursenames = gen_course_list($searchtext, array_diff($availablecourses, $SESSION->bulk_courses));
$selcoursenames = gen_course_list('', array_intersect($availablecourses, $SESSION->bulk_courses));

// print the page
if ($accept) {
    if (empty($SESSION->bulk_courses)) {
        redirect($return);
        die;
    } 
    admin_externalpage_print_header();
    notice_yesno(get_string('confirmation', $pluginname, NULL, $langdir), 'iterator.php', $return, array('confirm'=>'1'), NULL, 'post', 'get');
    admin_externalpage_print_footer();
    die;
}

admin_externalpage_print_header();
if (isset($SESSION->purge_progress)) {
    notice_yesno(get_string('continue', $pluginname, NULL, $langdir), 'iterator.php', 'index.php', NULL, array('reset'=>'1'), 'get', 'post' );
} else {

?>
<div id="purgeactivityform">
    <h3 class="main"><?php echo get_string( 'title', $pluginname, NULL, $langdir ) ?></h3>

    <form id="purgeform" method="post" action="index.php">
    <div>

    <table cellpadding="6" class="generaltable generalbox groupmanagementtable boxaligncenter" summary="">
    <tr>
      <td valign="top">
          <p>
            <label for="allcourses"><?php echo get_string( 'allcourses', $pluginname, NULL, $langdir ) ?></label>
          </p>
          <select name="allcourses[]" size="20" id="allcourses" multiple="multiple"
                  onfocus="document.getElementById('purgeform').add.disabled=false;
                           document.getElementById('purgeform').remove.disabled=true;
                           document.getElementById('purgeform').selcourses.selectedIndex=-1;"
                  onclick="this.focus();">
          <?php echo $coursenames ?>
          </select>

    <br />
         <label for="searchtext" class="accesshide"><?php p($strsearch) ?></label>
         <input type="text" name="searchtext" id="searchtext" size="21" value="<?php p($searchtext, true) ?>"
                  onfocus ="getElementById('purgeform').add.disabled=true;
                            getElementById('purgeform').remove.disabled=true;
                            getElementById('purgeform').allcourses.selectedIndex=-1;
                            getElementById('purgeform').selcourses.selectedIndex=-1;"
                  onkeydown = "var keyCode = event.which ? event.which : event.keyCode;
                               if (keyCode == 13) {
                                    getElementById('purgeform').previoussearch.value=1;
                                    getElementById('purgeform').submit();
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
                  onfocus="document.getElementById('purgeform').remove.disabled=false;
                           document.getElementById('purgeform').add.disabled=true;
                           document.getElementById('purgeform').allcourses.selectedIndex=-1;"
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

}

admin_externalpage_print_footer();
?>

<?php
/**
 * This page prints a particular instance of oublog
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */

// This code tells OU authentication system to let the public access this page
// (subject to Moodle restrictions below and with the accompanying .sams file).
global $DISABLESAMS;
$DISABLESAMS = 'opt';

require_once('../../config.php');
require_once('locallib.php');

if(class_exists('ouflags')) {
    require_once('../../local/mobile/ou_lib.php');

    global $OUMOBILESUPPORT;
    $OUMOBILESUPPORT = true;
    ou_set_is_mobile(ou_get_is_mobile_from_cookies());

    $blogdets = optional_param('blogdets', null, PARAM_TEXT);

    $DASHBOARD_COUNTER=DASHBOARD_BLOG_VIEW;
}

$id     = optional_param('id', 0, PARAM_INT);       // Course Module ID
$user   = optional_param('user', 0, PARAM_INT);     // User ID
$offset = optional_param('offset', 0, PARAM_INT);   // Offset fo paging
$tag    = optional_param('tag', null, PARAM_TAG);   // Tag to display

if ($id) {
    if (!$cm = get_coursemodule_from_id('oublog', $id)) {
        error("Course module ID was incorrect");
    }

    if (!$course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    if (!$oublog = get_record("oublog", "id", $cm->instance)) {
        error("Course module is incorrect");
    }
    $oubloguser->id = null;
    $oubloginstance = null;
    $oubloginstanceid = null;

} elseif ($user) {
    if (!$oubloguser = get_record('user', 'id', $user)) {
        error("User not found");
    }
    if (!list($oublog, $oubloginstance) = oublog_get_personal_blog($oubloguser->id)) {
        error("Course module is incorrect");
    }
    if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
        error("Course module ID was incorrect");
    }
    if (!$course = get_record("course", "id", $oublog->course)) {
        error("Course is misconfigured");
    }
    $oubloginstanceid = $oubloginstance->id;
} elseif (isloggedin()) {
    redirect('view.php?user='.$USER->id);
} else {
    redirect('bloglogin.php');
}

// The mod_edit page gets it wrong when redirecting to a personal blog.
// Since there's no way to know what personal blog was being updated
// this redirects to the users own blog
if ($oublog->global && empty($user)) {
    redirect('view.php?user='.$USER->id);
    exit;
}

// If viewing a course blog that requires login, but you're not logged in,
// this causes odd behaviour in OU systems, so redirect to bloglogin.php
if ($oublog->maxvisibility != OUBLOG_VISIBILITY_PUBLIC && !isloggedin()) {
    redirect('bloglogin.php?returnurl=' .
            substr($FULLME, strpos($FULLME, 'view.php')));
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
oublog_check_view_permissions($oublog, $context, $cm);


/// Check security
$canpost        = oublog_can_post($oublog,$user,$cm);
$canmanageposts = has_capability('mod/oublog:manageposts', $context);
$canaudit       = has_capability('mod/oublog:audit', $context);

/// Get strings
$stroublogs     = get_string('modulenameplural', 'oublog');
$stroublog      = get_string('modulename', 'oublog');
$straddpost     = get_string('newpost', 'oublog');
$strtags        = get_string('tags', 'oublog');
$stredit        = get_string('edit', 'oublog');
$strdelete      = get_string('delete', 'oublog');
$strnewposts    = get_string('newerposts', 'oublog');
$strolderposts  = get_string('olderposts', 'oublog');
$strcomment     = get_string('comment', 'oublog');
$strviews       = get_string('views', 'oublog');
$strlinks       = get_string('links', 'oublog');
$strfeeds       = get_string('feeds', 'oublog');
$strblogsearch  = get_string('searchthisblog', 'oublog');

/// Set-up groups
$groupmode = oublog_get_activity_groupmode($cm, $course);
$currentgroup = oublog_get_activity_group($cm, true);

if (!oublog_is_writable_group($cm)) {
    $canpost=false;
    $canmanageposts=false;
    $cancomment=false;
    $canaudit=false;
}

/// Print the header
$PAGEWILLCALLSKIPMAINDESTINATION = true;
$hideunusedblog=false;

if (class_exists('ouflags') && ou_get_is_mobile()){
    ou_mobile_configure_theme();
}

if ($oublog->global) {
    $blogtype = 'personal';
    $returnurl = $CFG->wwwroot . '/mod/oublog/view.php?user='.$user;

    $name = $oubloginstance->name;

    if(oublog_search_installed()) {
        $buttontext=<<<EOF
<form action="search.php" method="get"><div>
  <input type="hidden" name="user" value="{$oubloguser->id}"/>
  <input type="text" name="query" value=""/>
  <input type="submit" value="{$strblogsearch}"/>
</div></form>
EOF;
    } else {
        $buttontext='';
    }
    $buttontext.=update_module_button($cm->id, $course->id, $stroublog);

} else {
    $blogtype = 'course';
    $returnurl = $CFG->wwwroot . '/mod/oublog/view.php?id='.$id;

    $name = $oublog->name;

    if(oublog_search_installed()) {
        $buttontext=<<<EOF
<form action="search.php" method="get"><div>
  <input type="hidden" name="id" value="{$cm->id}"/>
  <input type="text" name="query" value=""/>
  <input type="submit" value="{$strblogsearch}"/>
</div></form>
EOF;
    } else {
        $buttontext='';
    }
    $buttontext.=update_module_button($cm->id, $course->id, $stroublog);
}
if ($tag) {
    $returnurl .= '&amp;tag='.urlencode($tag);
}

/// Set-up individual
$currentindividual = -1;
$individualdetails = 0;

//set up whether the group selector should display
$showgroupselector = true;
if($oublog->individual) {
    //if separate individual and visible group, do not show groupselector
    //unless the current user has permission
    if ($oublog->individual == OUBLOG_SEPARATE_INDIVIDUAL_BLOGS
        && !has_capability('mod/oublog:viewindividual', $context)) {
        $showgroupselector = false;
    }

    $canpost=true;
    $individualdetails = oublog_individual_get_activity_details($cm, $returnurl, $oublog, $currentgroup, $context);
    if ($individualdetails) {
        $currentindividual = $individualdetails->activeindividual;
        if (!$individualdetails->newblogpost) {
            $canpost=false;
        }
    }
}

/// Get Posts
list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, $currentgroup, $currentindividual, $oubloguser->id, $tag, $canaudit);

/// Generate extra navigation
$extranav = array();
if ($offset) {
    $a = new stdClass();
    $a->from = ($offset+1);
    $a->to   = (($recordcount - $offset) > OUBLOG_POSTS_PER_PAGE) ? $offset + OUBLOG_POSTS_PER_PAGE : $recordcount;
    $extranav = array('name' => get_string('extranavolderposts', 'oublog', $a), 'link' => '', 'type' => 'misc');
}
if ($tag) {
    $extranav = array('name' => get_string('extranavtag', 'oublog', $tag), 'link' => '', 'type' => 'misc');
}

if ($oublog->global) {
    // bit about hidden with if global then $posts
    // In order to prevent people from looping through numbers to get the
    // name of every user in the site (in case these names are considered
    // private), don't display the header when not displaying posts, except
    // to users who can post
    $hideunusedblog=!$posts && !$canpost && !$canaudit;
    if($hideunusedblog) {
        print_header();
    } else {
        $navigation = oublog_build_navigation($cm, $oublog, $oubloginstance,
            $oubloguser, $extranav);
        print_header_simple(format_string($oublog->name), "", $navigation, "", oublog_get_meta_tags($oublog, $oubloginstance, $currentgroup, $cm), true,
            $buttontext, navmenu($course, $cm));
    }
} else {
    $navigation = oublog_build_navigation($cm, $oublog, $oubloginstance,
        null, $extranav);
    print_header_simple(format_string($oublog->name), "", $navigation, "", oublog_get_meta_tags($oublog, $oubloginstance, $currentgroup, $cm), true,
                  $buttontext, navmenu($course, $cm));
}

print '<div class="oublog-topofpage"></div>';

require_once(dirname(__FILE__).'/pagelib.php');

// Initialize $PAGE, compute blocks
$PAGE       = page_create_instance($oublog->id);
$pageblocks = blocks_setup($PAGE);
$editing = isediting($cm->course);

if (class_exists('ouflags') && ou_get_is_mobile() && $blogdets == 'show'){
    print '<div id="middle-column">';

    ou_print_mobile_navigation($id,$blogdets,null,$user);
}
else {
	// The left column ...
	if($hasleft=!empty($CFG->showblocksonmodpages) && (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $editing)) {
	    print '<div id="left-column">';
	    blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
	    print '</div>';
	}

print '</div>';// fix mixed columns in rtl mode and editing mode (nadavkav patch)

	// The right column, BEFORE the middle-column.
	print '<div id="right-column">';
}

if(!$hideunusedblog) {
    // Name, summary, related links
    oublog_print_summary_block($oublog, $oubloginstance, $canmanageposts);

    /// Tag Cloud
    if ($tags = oublog_get_tag_cloud($returnurl, $oublog, $currentgroup, $cm, $oubloginstanceid, $currentindividual)) {
        print_side_block($strtags, $tags, NULL, NULL, NULL, array('id' => 'oublog-tags'),$strtags);
    }

    /// Links
    $links = oublog_get_links($oublog, $oubloginstance, $context);
    if ($links) {
        print_side_block($strlinks, $links, NULL, NULL, NULL, array('id' => 'oublog-links'),$strlinks);
    }

    // Feeds
    if ($feeds = oublog_get_feedblock($oublog, $oubloginstance, $currentgroup, false, $cm, $currentindividual)) {
        $feedicon = ' <img src="'.$CFG->pixpath.'/i/rss.gif" alt="'.get_string('blogfeed', 'oublog').'"  class="feedicon" />';
        print_side_block($strfeeds . $feedicon, $feeds, NULL, NULL, NULL, array('id' => 'oublog-feeds'), $strfeeds);
    }

    if (true and has_capability('moodle/legacy:editingteacher', $context, $USER->id, false)) {

      $timestart = round(time() - COURSE_MAX_RECENT_PERIOD, -2); // better db caching for guests - 100 seconds

      if (!has_capability('moodle/legacy:guest', $context, NULL, false)) {
          if (!empty($USER->lastcourseaccess[$course->id])) {
              if ($USER->lastcourseaccess[$course->id] > $timestart) {
                  $timestart = $USER->lastcourseaccess[$course->id];
              }
          }
      }

      // Slightly hacky way to do it but...
//         ob_start();
//         oublog_print_recent_activity($COURSE,NULL,$timestart);
//         $oublog_recent_activity = ob_get_contents();
//         ob_end_clean();
        $oublog_recent_activity = 'None';
        print_side_block(get_string('recentactivity', 'oublog'), $oublog_recent_activity, NULL, NULL, NULL, array('id' => 'oublog-recent-activity'),get_string('recentactivity', 'oublog'));
    }
}

if (class_exists('ouflags') && ou_get_is_mobile() && $blogdets == 'show'){
    ou_print_mobile_navigation($id,$blogdets,null,$user);
}

print '</div>';

if (class_exists('ouflags') && ou_get_is_mobile() && $blogdets == 'show'){
    print_footer($course);
    exit;
}

// Start main column
$classes='';

if (!class_exists('ouflags') || !ou_get_is_mobile()){
	$classes.=$hasleft ? 'has-left-column ' : '';
    $classes.='has-right-column ';
}

$classes=trim($classes);
if($classes) {
    print '<div id="middle-column" class="'.$classes.'">';
} else {
    print '<div id="middle-column">';
}

if (class_exists('ouflags') && ou_get_is_mobile()){
	ou_print_mobile_navigation($id,$blogdets,null,$user);
}

print skip_main_destination();

//adding a link to the computing guide
if (class_exists('ouflags')) {
    require_once($CFG->dirroot.'/local/utils_shared.php');
    $computingguidelink = get_link_to_computing_guide('oublog');
    print '<span class="computing-guide"> '.$computingguidelink.'</span>';
}

/// Print Groups and individual drop-down menu
echo '<div class="oublog-groups-individual-selectors">';

/// Print Groups
if ($showgroupselector) {
    groups_print_activity_menu($cm, $returnurl);
}
/// Print Individual
if($oublog->individual) {
    if ($individualdetails) {
        echo $individualdetails->display;
        $individualmode = $individualdetails->mode;
        $currentindividual = $individualdetails->activeindividual;
    }
}
echo '</div>';

/// Print the main part of the page

// New post button - in group blog, you can only post if a group is selected
if ($oublog->individual && $individualdetails) {
    $showpostbutton = $canpost;
} else {
    $showpostbutton = $canpost && ($currentgroup || !$groupmode );
}
if ($showpostbutton) {
    print_single_button('editpost.php', array('blog' => $cm->instance), $straddpost);
}

// Print blog posts
if ($posts) {
    echo '<div id="oublog-posts">';
    if ($offset > 0) {
        if ($offset-OUBLOG_POSTS_PER_PAGE == 0) {
            print "<div class='oublog-newerposts'><a href=\"$returnurl\">$strnewposts</a></div>";
        } else {
            print "<div class='oublog-newerposts'><a href=\"$returnurl&amp;offset=".($offset-OUBLOG_POSTS_PER_PAGE)."\">$strnewposts</a></div>";
        }
    }

    foreach ($posts as $post) {
        oublog_print_post($cm, $oublog, $post, $returnurl, $blogtype, $canmanageposts, $canaudit);
    }

    if ($recordcount - $offset > OUBLOG_POSTS_PER_PAGE) {
        print "<div class='oublog-olderposts'><a href=\"$returnurl&amp;offset=".($offset+OUBLOG_POSTS_PER_PAGE)."\">$strolderposts</a></div>";
    }
    echo '</div>';
}

// Print information allowing the user to log in if necessary, or letting
// them know if there are no posts in the blog
if (isguestuser() && $USER->id==$user) {
    print '<p class="oublog_loginnote">'.
        get_string('guestblog','oublog',
            'bloglogin.php?returnurl='.urlencode($returnurl)).'</p>';
} else if(!isloggedin() || isguestuser()) {
    print '<p class="oublog_loginnote">'.
        get_string('maybehiddenposts','oublog',
            'bloglogin.php?returnurl='.urlencode($returnurl)).'</p>';
} else if(!$posts) {
    print '<p class="oublog_noposts">'.
        get_string('noposts','oublog').'</p>';
}

/// Log visit and bump view count
add_to_log($course->id, "oublog", "view", $returnurl, $oublog->id, $cm->id);
$views = oublog_update_views($oublog, $oubloginstance);

// Show dashboard feature if enabled, if course blog
if (class_exists('ouflags') && !$oublog->global) {
    require_once($CFG->dirroot . '/local/externaldashboard/external_dashboard.php');
    external_dashboard::print_favourites_button($cm);
}

/// Finish the page
echo "<div class=\"clearer\"></div><div class=\"oublog-views\">$strviews $views</div></div>";

if(class_exists('ouflags')) {
    completion_set_module_viewed($course,$cm);
}

if (class_exists('ouflags') && ou_get_is_mobile()){
    ou_print_mobile_navigation($id,$blogdets,null,$user);
}

print_footer($course);
?>
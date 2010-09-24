<?php
/**
 * This page prints all non-private personal oublog posts 
 *
 * @author Jenny Gray <j.m.gray@open.ac.uk>
 * @package oublog
 */

// This code tells OU authentication system to let the public access this page
// (subject to Moodle restrictions below and with the accompanying .sams file).
global $DISABLESAMS, $USER;
$DISABLESAMS=true;

    require_once('../../config.php');
    require_once('locallib.php');

    if(class_exists('ouflags')) {
        $DASHBOARD_COUNTER=DASHBOARD_BLOG_VIEW;
    }

    $offset = optional_param('offset', 0, PARAM_INT);   // Offset for paging
    $tag    = optional_param('tag', null, PARAM_TAG);   // Tag to display

    if (!$oublog = get_record("oublog","global",1)) { // the personal blogs module
        error("Personal blogs not set up");
    }
    
    if (!$cm = get_coursemodule_from_instance('oublog',$oublog->id)) {
        error("Course module is incorrect");
    }

    if (!$course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    oublog_check_view_permissions($oublog, $context, $cm);

/// Check security
    $blogtype = 'personal';
    $returnurl = 'allposts.php?';
    
    if ($tag) {
        $returnurl .= '&amp;tag='.urlencode($tag);
    }
    
    $canmanageposts = has_capability('mod/oublog:manageposts', $context);
    $canaudit       = has_capability('mod/oublog:audit', $context);

/// Log visit 
    add_to_log($course->id, "oublog", "allposts", $returnurl, $oublog->id, $cm->id);

/// Get strings
    $stroublog      = get_string('modulename', 'oublog');
    $strnewposts    = get_string('newerposts', 'oublog');
    $strolderposts  = get_string('olderposts', 'oublog');
    $strfeeds       = get_string('feeds', 'oublog');
    $strfeeds       .= '<img src="'.$CFG->pixpath.'/i/rss.gif" alt="'.get_string('blogfeed', 'oublog').'"  class="feedicon" />';
    $strblogsearch  = get_string('searchblogs', 'oublog');
  
/// Get Posts
    list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, null, -1, null, $tag, $canaudit);

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

/// Print the header
    $navlinks = array();
    $navlinks[] = array('name' => format_string($oublog->name), 'link' => "allposts.php", 'type' => 'activityinstance');
    if ($extranav) {
        $navlinks[] = $extranav;
    }

    if(oublog_search_installed()) {
        $buttontext=<<<EOF
<form action="search.php" method="get"><div>
  <input type="text" name="query" value=""/>
  <input type="hidden" name="id" value="{$cm->id}"/>
  <input type="submit" value="{$strblogsearch}"/>
</div></form>
EOF;
    } else {
        $buttontext='';
    }
    $buttontext.=update_module_button($cm->id, $course->id, $stroublog);

$PAGEWILLCALLSKIPMAINDESTINATION = true; // OU accessibility feature
    $navigation = build_navigation($navlinks);
    print_header_simple(format_string($oublog->name), "", $navigation, "", oublog_get_meta_tags($oublog, 'all', '', $cm), true,
                $buttontext, navmenu($course, $cm));

print '<div class="oublog-topofpage"></div>';    
    
// The left column ...
if($hasleft=!empty($CFG->showblocksonmodpages) && blocks_have_content($pageblocks, BLOCK_POS_LEFT) ) {
    print '<div id="left-column">';
    blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
    print '</div>';
}

// The right column, BEFORE the middle-column.
print '<div id="right-column">';
    if (isloggedin() and !isguestuser()) {
        list($oublog, $oubloginstance) = oublog_get_personal_blog($USER->id);
        $blogeditlink = "<br /><a href=\"view.php\" class=\"oublog-links\">$oubloginstance->name</a>";
        print_side_block(format_string($oublog->name), $blogeditlink, NULL, NULL, NULL, array('id' => 'oublog-summary'),get_string('bloginfo','oublog'));
    } 

    if ($feeds = oublog_get_feedblock($oublog, 'all', '', false, $cm)) {
        print_side_block($strfeeds, $feeds, NULL, NULL, NULL, array('id' => 'oublog-feeds'),$strfeeds);
    }

print '</div>';

// Start main column
$classes='';
$classes.=$hasleft ? 'has-left-column ' : '';
$classes.='has-right-column ';
$classes=trim($classes);
if($classes) {
    print '<div id="middle-column" class="'.$classes.'">';
} else {    
    print '<div id="middle-column">';
}
print skip_main_destination();

// Print blog posts
    if ($posts) {
        echo '<div id="oublog-posts">';
        if ($offset > 0) {
            if ($offset-OUBLOG_POSTS_PER_PAGE == 0) {
                echo "<a href=\"$returnurl\">$strnewposts</a>";
            } else {
                echo "<a href=\"$returnurl&amp;offset=".($offset-OUBLOG_POSTS_PER_PAGE)."\">$strnewposts</a>";
            }
        }

        foreach ($posts as $post) {
            oublog_print_post(null, $oublog, $post, $returnurl, $blogtype, $canmanageposts, $canaudit);
        }

        if ($recordcount - $offset > OUBLOG_POSTS_PER_PAGE) {
            echo "<a href=\"$returnurl&amp;offset=".($offset+OUBLOG_POSTS_PER_PAGE)."\">$strolderposts</a>";
        }
        echo '</div>';
    } 

    // Print information allowing the user to log in if necessary, or letting
    // them know if there are no posts in the blog
    if(!isloggedin() || isguestuser()) {
        print '<p class="oublog_loginnote">'.
            get_string('maybehiddenposts','oublog',
                'bloglogin.php').'</p>';
    } else if(!$posts) {
        print '<p class="oublog_noposts">'.
            get_string('noposts','oublog').'</p>';
    }

/// Finish the page
    print_footer($course);
?>
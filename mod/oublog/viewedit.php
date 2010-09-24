<?php
/**
 * This page prints information about edits to a blog post.
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */

    require_once("../../config.php");
    require_once("locallib.php");

    $editid = required_param('edit', PARAM_INT);       // Blog post edit ID

    if (!$edit = get_record('oublog_edits', 'id', $editid)) {
        error('Edit ID was incorrect');
    }

    if (!$post = oublog_get_post($edit->postid)) {
        error("Post ID was incorrect");
    }

    if (!$cm = get_coursemodule_from_instance('oublog', $post->oublogid)) {
        error("Course module ID was incorrect");
    }

    if (!$course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    if (!$oublog = get_record("oublog", "id", $cm->instance)) {
        error("Course module is incorrect");
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    oublog_check_view_permissions($oublog, $context, $cm);

/// Check security
    $canpost            = oublog_can_post($oublog, $post->userid, $cm);
    $canmanageposts     = has_capability('mod/oublog:manageposts', $context);
    $canmanagecomments  = has_capability('mod/oublog:managecomments', $context);
    $canaudit           = has_capability('mod/oublog:audit', $context);

/// Get strings
    $stroublogs     = get_string('modulenameplural', 'oublog');
    $stroublog      = get_string('modulename', 'oublog');
    $strtags        = get_string('tags', 'oublog');
    $strviewedit    = get_string('viewedit', 'oublog');

/// Set-up groups
    $currentgroup = oublog_get_activity_group($cm, true);
    $groupmode = oublog_get_activity_groupmode($cm);

/// Generate extra navigation
    $extranav = array();
    if (!empty($post->title)) {
        $extranav[] = array('name' => format_string($post->title), 'link' => 'viewpost.php?post='.$post->id, 'type' => 'misc');
    } else {
        $extranav[] = array('name' => shorten_text(format_string($post->message, 30)), 'link' => 'viewpost.php?post='.$post->id, 'type' => 'misc');
    }
    $extranav[] = array('name' => $strviewedit, 'link' => '', 'type' => '');

/// Print the header
    if ($oublog->global) {
        $returnurl = 'view.php?user='.$oubloguser->id;

        $navlinks = array();
        $navlinks[] = array('name' => fullname($oubloguser), 'link' => "../../user/view.php?id=$oubloguser->id", 'type' => 'misc');
        $navlinks[] = array('name' => format_string($oublog->name), 'link' => $returnurl, 'type' => 'activityinstance');
        if ($extranav) {
            $navlinks = $navlinks + $extranav;
        }

        $navigation = build_navigation($navlinks);
        print_header_simple(format_string($oublog->name), "", $navigation, "", "", true,
                    update_module_button($cm->id, $course->id, $stroublog), navmenu($course, $cm));

    } else {
        $returnurl = 'view.php?id='.$cm->id;

        $navlinks = array();
        if ($extranav) {
            $navlinks = $navlinks + $extranav;
        }
        $navigation = build_navigation($navlinks, $cm);

        print_header_simple(format_string($oublog->name), "", $navigation, "", "", true,
                      update_module_button($cm->id, $course->id, $stroublog), navmenu($course, $cm));
    }

/// Print the main part of the page
    echo '<div class="oublog-topofpage"></div>';


/// Print blog posts
    ?>
    <div id="middle-column">
        <div class="oublog-post">
            <h3><?= format_string($edit->oldtitle) ?></h3>
            <div class="oublog-post-date">
                <?= oublog_date($edit->timeupdated) ?>
            </div>
            <p><?= format_text($edit->oldmessage, FORMAT_HTML) ?></p>
        </div>
    </div>
    <?



/// Finish the page
    echo '<div class="clearfix"></div>';
    print_footer($course);
?>
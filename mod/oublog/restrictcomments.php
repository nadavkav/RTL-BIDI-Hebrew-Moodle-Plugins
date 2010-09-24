<?php
// You can restrict comments (=change your posts so that they only permit
// comments from signed-in users) on either a post or a blog. A confirmation 
// screen displays first.
require_once('../../config.php');
require_once('locallib.php');

// Get post or blog details
$postid = optional_param('post', 0, PARAM_INT);
if ($postid) {
    $isblog = false;
    if (!$oublog = oublog_get_blog_from_postid($postid)) {
        print_error('invalidrequest');
    }
} else {
    $blogid = required_param('blog', PARAM_INT);
    $isblog = true;
    if (!$oublog = get_record('oublog', 'id', $blogid)) {
        print_error('invalidrequest');
    }
}

// Get other details and check access
if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
    print_error('error_unspecified', 'oublog', 'RC1');
}
if (!$course = get_record("course", "id", $cm->course)) {
    print_error('error_unspecified', 'oublog', 'RC2');
}

// Require login and access to blog
require_login($course, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
oublog_check_view_permissions($oublog, $context, $cm);

// You must be able to post to blog (if blog = site blog, then your one)
if (!oublog_can_post($oublog, $USER->id, $cm)) {
    print_error('accessdenied', 'oublog');
}

// If there was a specified post, it must be yours
if (!$isblog) {
    $userid = get_field_sql("
SELECT
    bi.userid 
FROM 
    {$CFG->prefix}oublog_posts bp
    INNER JOIN {$CFG->prefix}oublog_instances bi ON bi.id=bp.oubloginstancesid
WHERE
    bp.id = $postid");
    if ($userid !== $USER->id) {
        print_error('accessdenied', 'oublog');
    }
}

// Is this the actual change or just the confirm?
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_sesskey();

    // Apply actual change
    if ($isblog) {
        $restriction = 'b.id = ' . $blogid;
    } else {
        $restriction = 'bp.id = ' . $postid;
    }
    if (!execute_sql("
UPDATE {$CFG->prefix}oublog_posts SET allowcomments = " . OUBLOG_COMMENTS_ALLOW ."
WHERE id IN (
SELECT bp.id FROM
    {$CFG->prefix}oublog_posts bp
    INNER JOIN {$CFG->prefix}oublog_instances bi ON bi.id = bp.oubloginstancesid
    INNER JOIN {$CFG->prefix}oublog b ON b.id = bi.oublogid
WHERE
    bi.userid = {$USER->id}
    AND bp.allowcomments >= " . OUBLOG_COMMENTS_ALLOWPUBLIC . "
    AND $restriction)", false)) {
        print_error('error_unspecified', 'oublog', 'RC3');
    }

    // Redirect
    if ($isblog) {
        if ($oublog->global) {
            redirect('view.php?user=' . $USER->id);
        } else {
            redirect('view.php?id=' . $cm->id);
        }
    } else {
        redirect('viewpost.php?post=' . $postid);
    }
}

// This is the confirm screen. Do navigation first...

$extranav = array();
if (!$isblog) {
    $post = get_record('oublog_posts', 'id', $postid);
    $extranav[] = oublog_get_post_extranav($post);
}
$extranav[] = array(
    'name' => get_string('moderated_restrictpage', 'oublog'), 
    'link' => '', 'type' => 'misc');
$oubloginstance = get_record('oublog_instances', 'oublogid', $oublog->id, 'userid', $USER->id);
$nav = oublog_build_navigation($cm, $oublog, $oubloginstance, $USER, $extranav);
print_header_simple(format_string($oublog->name), '', $nav);

if ($isblog) {
    if ($oublog->global) {
        $nourl = 'view.php';
        $noparams = array('user' => $USER->id);
    } else {
        $nourl = 'view.php';
        $noparams = array('id' => $cm->id);
    }
    $yesparams = array('blog' => $blogid);
} else {
    $nourl ='viewpost.php';
    $noparams = array('post' => $postid);
    $yesparams = array('post' => $postid);
}
$yesparams['sesskey'] = sesskey();

// Display the query
notice_yesno(
        get_string('moderated_restrict' . ($isblog ? 'blog' : 'post') . '_info',
            'oublog'),
        'restrictcomments.php', $nourl, $yesparams, $noparams, 'POST', 'GET');

// Page foter
print_footer($COURSE);

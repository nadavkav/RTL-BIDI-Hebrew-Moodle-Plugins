<?php
require_once('../../config.php');
require_once('forum.php');
if (class_exists('ouflags')) {
    $DASHBOARD_COUNTER = DASHBOARD_FORUMNG_AJAX;
}

// Post ID
$postid = required_param('p', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

// 1 = set flag, 0 = clear it
$flag = required_param('flag', PARAM_INT);

// If the ajax flag is set, this only changes the flag and does not redirect
$ajax = optional_param('ajax', 0, PARAM_INT);

// Optional back parameter
$back = optional_param('back', 'discuss', PARAM_ALPHA);

// Optional time-read parameter (this is used to preserve unread state when
// redirecting back to the discussion
$timeread = optional_param('timeread', 0, PARAM_INT);

try {
    // Get post
    $post = forum_post::get_from_id($postid, $cloneid, true, true);

    // Do all access security checks
    $post->require_view();
    if (!$post->can_flag()) {
        print_error('error_nopermission', 'forumng');
    }

    // Change the flag
    $post->set_flagged($flag);

    // If it's ajax, that's done
    if ($ajax) {
        print 'ok';
        exit;
    }

    // Redirect
    if ($back == 'view') {
        redirect($post->get_forum()->get_url(forum::PARAM_PLAIN));
    } else {
        redirect('discuss.php?' .
            $post->get_discussion()->get_link_params(forum::PARAM_PLAIN) .
            ($timeread ? '&timeread=' . $timeread : '') .
            '#p' . $post->get_id());
    }

} catch(forum_exception $e) {
    header('Content-Type: text/plain', true, 500);
    print $e->getMessage();
}
?>
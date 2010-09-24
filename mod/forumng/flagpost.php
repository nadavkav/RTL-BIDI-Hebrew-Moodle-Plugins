<?php
require_once('../../config.php');
require_once('forum.php');
if (class_exists('ouflags')) {
    $DASHBOARD_COUNTER = DASHBOARD_FORUMNG_AJAX;
}

// Post ID
$postid = required_param('p', PARAM_INT);

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
    $post = forum_post::get_from_id($postid, true, true);

    // Do all access security checks
    $post->require_view();
    
    // Change the flag
    $post->set_flagged($flag);

    // If it's ajax, that's done
    if ($ajax) {
        print 'ok';
        exit;
    }

    // Redirect
    if ($back == 'view') {
        redirect('view.php?id=' . $post->get_forum()->get_course_module_id());
    } else {
        redirect('discuss.php?d=' . $post->get_discussion()->get_id() .
            ($timeread ? '&timeread=' . $timeread : '') .
            '#p' . $post->get_id());
    }

} catch(forum_exception $e) {
    header('Content-Type: text/plain', true, 500);
    print $e->getMessage();
}
?>
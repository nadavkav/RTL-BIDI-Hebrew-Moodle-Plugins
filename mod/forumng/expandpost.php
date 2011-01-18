<?php
require_once('../../config.php');
require_once('forum.php');
if (class_exists('ouflags')) {
    $DASHBOARD_COUNTER = DASHBOARD_FORUMNG_AJAX;
}

// Script retrieves content of a single post (plain). Intended for use only
// by AJAX calls.

// Post ID
$postid = required_param('p', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
$raw = optional_param('raw', 0, PARAM_INT);

try {
    // Get post
    $post = forum_post::get_from_id($postid, $cloneid, true, true);

    // Do all access security checks
    $post->require_view();

    // Display post
    if ($raw) {
        print $post->get_json_format();
    } else {
        forum_post::print_for_ajax_and_exit($post);
    }
} catch(forum_exception $e) {
    header('Content-Type: text/plain', true, 500);
    print $e->getMessage();
}
?>
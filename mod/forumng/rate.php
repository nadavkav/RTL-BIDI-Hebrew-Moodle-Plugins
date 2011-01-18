<?php
require_once('../../config.php');
require_once('forum.php');
if (class_exists('ouflags')) {
    // Ratings count as edits to a post
    $DASHBOARD_COUNTER = DASHBOARD_FORUMNG_POST;
}

try {
    // Discussion ID (to do a bunch at once)
    $discussionid = optional_param('d', 0, PARAM_INT);
    $cloneid = optional_param('clone', 0, PARAM_INT);
    if ($discussionid) {
        // Get discussion and check basic security
        $discussion = forum_discussion::get_from_id($discussionid, $cloneid);
        $discussion->require_view();

        // Get list of posts to change
        $changes = array();
        foreach($_POST as $key=>$value) {
            $matches = array();
            if (preg_match('~^rating([0-9]+)$~', $key, $matches) &&
                preg_match('~^[0-9]+$~', $value)) {
                $changes[$matches[1]] = (int)$value;
            }
        }

        forum_utils::start_transaction();
        $rootpost = $discussion->get_root_post();
        foreach ($changes as $postid => $rating) {
            $post = $rootpost->find_child($postid, true);
            if (!$post->can_rate()) {
                print_error('rate_nopermission', 'forumng', '', $postid);
            }
            $post->rate($rating);
        }
        forum_utils::finish_transaction();
        redirect('discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN));
    }

    // Post ID (to do a single post)
    $postid = required_param('p', PARAM_INT);
    $ajax = optional_param('ajax', 0, PARAM_INT);
    $rating = required_param('rating', PARAM_INT);

    // Get post and check basic security
    $post = forum_post::get_from_id($postid, $cloneid);
    $post->require_view();
    if (!$post->can_rate()) {
        print_error('rate_nopermission', 'forumng', '', $postid);
    }

    $post->rate($rating);
    if ($ajax) {
        forum_post::print_for_ajax_and_exit($postid, $cloneid);
    }
    redirect('discuss.php?' .
            $post->get_discussion()->get_link_params(forum::PARAM_PLAIN) .
            '#'. $postid);
} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
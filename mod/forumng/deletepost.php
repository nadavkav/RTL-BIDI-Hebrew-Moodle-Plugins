<?php
require_once('../../config.php');
require_once('forum.php');

if (class_exists('ouflags')) {
    require_once('../../local/mobile/ou_lib.php');
    
    global $OUMOBILESUPPORT;
    $OUMOBILESUPPORT = true;
    ou_set_is_mobile(ou_get_is_mobile_from_cookies());
}

// Get AJAX parameter which might affect error handling
$ajax = optional_param('ajax', 0, PARAM_INT);

// Post ID
$postid = required_param('p', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

// Delete or undelete
$delete = optional_param('delete', 1, PARAM_INT);

try {
    // Get post
    $post = forum_post::get_from_id($postid, $cloneid);

    // Get convenience variables
    $discussion = $post->get_discussion();
    $forum = $post->get_forum();
    $course = $forum->get_course();
    $cm = $forum->get_course_module();

    // Do all access security checks
    $post->require_view();
    if ($delete) {
        if (!$post->can_delete($whynot)) {
            print_error($whynot, 'forumng');
        }
    } else {
        if (!$post->can_undelete($whynot)) {
            print_error($whynot, 'forumng');
        }
    }

    // Is this the actual delete?
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Delete counts as edit to a post
        if (class_exists('ouflags')) {
            $DASHBOARD_COUNTER = DASHBOARD_FORUMNG_POST;
        }

        // Delete or undelete the post
        if ($delete) {
            $post->delete();
        } else {
            $post->undelete();
        }

        // Redirect back
        if ($ajax) {
            forum_post::print_for_ajax_and_exit($postid, $cloneid);
        }
        redirect('discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN) . '#p' .
            $post->get_id());
    }

    if(class_exists('ouflags') && ou_get_is_mobile()){
        ou_mobile_configure_theme();
    }
    
    // Confirm page. Work out navigation for header
    $pagename = get_string($delete ? 'deletepost' : 'undeletepost', 'forumng',
        $post->get_effective_subject(true));
    $discussion->print_subpage_header($pagename);

    // Show confirm option
    if ($delete) {
        $confirmstring = get_string('confirmdelete', 'forumng');
        if ($post->is_root_post()) {
            $confirmstring .= ' ' . get_string('confirmdelete_nodiscussion', 'forumng');
        }
    } else {
        $confirmstring = get_string('confirmundelete', 'forumng');
    }
    notice_yesno($confirmstring, 'deletepost.php', 'discuss.php',
        array('p'=>$post->get_id(), 'delete'=>$delete, 'clone'=>$cloneid),
        array('d'=>$discussion->get_id(), 'clone'=>$cloneid),
        'post', 'get');

    // Print post
    print $post->display(true,
            array(forum_post::OPTION_NO_COMMANDS=>true));

    // Display footer
    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
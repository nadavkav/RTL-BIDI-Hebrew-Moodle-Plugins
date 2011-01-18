<?php
require_once('../../config.php');
require_once('forum.php');

// Post ID
$postid = required_param('p', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

try {
    // Get post
    $post = forum_post::get_from_id($postid, $cloneid, true);

    // Get convenience variables
    $discussion = $post->get_discussion();
    $forum = $post->get_forum();
    $course = $forum->get_course();
    $cm = $forum->get_course_module();

    // Do all access security checks
    $post->require_view();
    if (!$post->can_split($whynot)) {
        print_error($whynot, 'forumng');
    }

    require_once('splitpost_form.php');
    $mform = new mod_forumng_splitpost_form('splitpost.php',
        array('p'=>$postid, 'clone'=>$cloneid));

    if ($mform->is_cancelled()) {
        redirect('discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN));
    } else if ($fromform = $mform->get_data(false)) {
        // Split post
        $newdiscussionid = $post->split($fromform->subject);

        // Redirect back
        redirect('discuss.php?d=' . $newdiscussionid . $forum->get_clone_param(forum::PARAM_PLAIN));
    }

    // Confirm page. Work out navigation for header
    $pagename = get_string('splitpost', 'forumng',
        $post->get_effective_subject(true));

    $navigation = array();
    $navigation[] = array(
        'name'=>shorten_text(htmlspecialchars(
            $discussion->get_subject())),
        'link'=>$discussion->get_url(), 'type'=>'forumng');
    $navigation[] = array(
        'name'=>$pagename, 'type'=>'forumng');

    $PAGEWILLCALLSKIPMAINDESTINATION = true;
    print_header_simple(format_string($forum->get_name()) . ': ' . $pagename,
        "", build_navigation($navigation, $cm), "", "", true,
        '', navmenu($course, $cm));

    print skip_main_destination();

    // Print post
    if ($post->get_subject() != null) {
        $mform->set_data(array('subject' => $post->get_subject()));
    }

    // Print form
    $mform->display();

    print '<div class="forumng-exampleposts">';

    // Print posts
    print $post->display_with_children(
        array(forum_post::OPTION_NO_COMMANDS=>true,
            forum_post::OPTION_CHILDREN_EXPANDED=>true));

    print '</div>';

    // Display footer
    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
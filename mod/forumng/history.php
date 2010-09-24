<?php
require_once('../../config.php');
require_once('forum.php');

if (class_exists('ouflags')) {
    require_once('../../local/mobile/ou_lib.php');
    
    global $OUMOBILESUPPORT;
    $OUMOBILESUPPORT = true;
    ou_set_is_mobile(ou_get_is_mobile_from_cookies());
}

// Post ID
$postid = required_param('p', PARAM_INT);

try {
    // Get post
    $post = forum_post::get_from_id($postid, true);

    // Get convenience variables
    $discussion = $post->get_discussion();
    $forum = $post->get_forum();
    $course = $forum->get_course();
    $cm = $forum->get_course_module();

    // Do all access security checks
    $post->require_view();
    if (!$post->can_view_history($whynot)) {
        print_error($whynot, 'forumng');
    }

    // Work out navigation for header
    $pagename = get_string('historypage', 'forumng',
        $post->get_effective_subject(true));

    $navigation = array();
    $navigation[] = array(
        'name'=>shorten_text(htmlspecialchars(
            $discussion->get_subject())),
        'link'=>$discussion->get_url(), 'type'=>'forumng');
    $navigation[] = array(
        'name'=>$pagename, 'type'=>'forumng');
    
    if (class_exists('ouflags') && ou_get_is_mobile()){
        ou_mobile_configure_theme();
    }

    $PAGEWILLCALLSKIPMAINDESTINATION = true;
    print_header_simple(format_string($forum->get_name()) . ': ' . $pagename,
        "", build_navigation($navigation, $cm), "", "", true,
        '', navmenu($course, $cm));

    print skip_main_destination();

    // Print current post
    print '<h2>'. get_string('currentpost', 'forumng') . '</h2>';
    print $post->display(true, array(forum_post::OPTION_NO_COMMANDS=>true,
            forum_post::OPTION_EXPANDED=>true));

    print '<h2>'. get_string('olderversions', 'forumng') . '</h2>';
    $oldversions = $post->get_old_versions();
    foreach ($oldversions as $oldpost) {
        print $oldpost->display(true,
            array(forum_post::OPTION_NO_COMMANDS=>true,
                forum_post::OPTION_EXPANDED=>true));
    }

    // Display footer
    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
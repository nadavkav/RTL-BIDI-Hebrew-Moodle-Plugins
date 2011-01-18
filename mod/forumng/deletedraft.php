<?php
require_once('../../config.php');
require_once('forum.php');

try {
    // Load draft and forum
    $draft = forum_draft::get_from_id(required_param('draft', PARAM_INT));
    $forum = forum::get_from_id($draft->get_forum_id(),
        optional_param('clone', 0, PARAM_INT));
    $course = $forum->get_course();
    $cm = $forum->get_course_module();

    // Check it belongs to current user
    if ($USER->id != $draft->get_user_id()) {
        print_error('draft_mismatch', 'forumng');
    }

    // If they are actually deleting it, go ahead
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $draft->delete();
        redirect($forum->get_url());
    }

    // Confirm page. Work out navigation for header    
    $pagename = get_string('deletedraft', 'forumng');
    $navigation = array();
    $navigation[] = array(
        'name' => $pagename, 'type' => 'forumng');

    $PAGEWILLCALLSKIPMAINDESTINATION = true;
    print_header_simple(format_string($forum->get_name()) . ': ' . $pagename,
        "", build_navigation($navigation, $cm), "", "", true,
        '', navmenu($course, $cm));

    print skip_main_destination();

    notice_yesno(get_string('confirmdeletedraft', 'forumng'), 
        'deletedraft.php', 'view.php',
        array('draft'=>$draft->get_id()), array('id'=>$cm->id),
        'post', 'get');
        
    print '<div class="forumng-post">';
    print '<div class="forumng-1"></div>';
    print '<div class="forumng-2"></div>';
    print '<div class="forumng-pic">';    
    print_user_picture($USER, $course->id);
    print '</div>';
    if ($subject = $draft->get_subject()) {
        print '<h3 class="forumng-subject">' . format_string($subject) . '</h3>';
    }
    print '<div class="forumng-postmain">';
    print format_text($draft->get_message(), $draft->get_format());
    print '</div>';
    print '</div>';

    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
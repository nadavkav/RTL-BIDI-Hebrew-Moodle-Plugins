<?php
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/forum.php');

$d = required_param('d', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

try {
    $discussion = forum_discussion::get_from_id($d, $cloneid);
    $forum = $discussion->get_forum();
    $cm = $forum->get_course_module();
    $course = $forum->get_course();

    // Check permission for change
    $discussion->require_edit();

    // Is this the actual unlock?
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $discussion->unlock();
        redirect('../../discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN));
    }

    // Confirm page. Work out navigation for header
    $pagename = get_string('unlockdiscussion', 'forumng',
        $discussion->get_subject(false));

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

    // Show confirm option
    $confirmstring = get_string('confirmunlock', 'forumng');
    notice_yesno($confirmstring, 'unlock.php', '../../discuss.php',
        array('d'=>$discussion->get_id(), 'clone'=>$cloneid),
        array('d'=>$discussion->get_id(), 'clone'=>$cloneid),
        'post', 'get');

    // Display footer
    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
<?php
require_once('../../config.php');
require_once('forum.php');

// This script handles requests to mark a discussion or forum read (without
// actually reading it).

// Can be called with id= (cmid) or d= (discussion id).
$cmid = optional_param('id', 0, PARAM_INT);
$discussionid = optional_param('d', 0, PARAM_INT);
if ((!$cmid && !$discussionid) || ($cmid && $discussionid)) {
    print_error('error_markreadparams', 'forumng');
}

// Permitted values 'view', 'discuss'
$back = optional_param('back', '', PARAM_ALPHA);
if (!preg_match('~^(discuss|view)$~', $back)) {
    $back = 'view';
}
if (($back=='discuss' && !$discussionid)) {
    $back = 'view';
}

try {
    // Handle whole forum
    if ($cmid) {
        $forum = forum::get_from_cmid($cmid);
        $groupid = required_param('group', PARAM_INT);
        if ($groupid == 0) {
            // Just the distinction between 0 and null
            $groupid = forum::ALL_GROUPS;
        }
        $forum->require_view($groupid);
        if (!$forum->can_mark_read()) {
            print_error('error_cannotmarkread', 'forumng');
        }
        $forum->mark_read($groupid);
    }

    // Handle single discussion course
    if ($discussionid) {
        $discussion = forum_discussion::get_from_id($discussionid);
        $discussion->require_view();
        if (!$discussion->get_forum()->can_mark_read()) {
            print_error('error_cannotmarkread', 'forumng');
        }
        $discussion->mark_read();
        $cmid = $discussion->get_forum()->get_course_module_id();
    }

    // Redirect back
    if ($back == 'discuss') {
        if (!$courseid) {
            $courseid = $forum->get_course()->id;
        }
        redirect('discuss.php?d=' . $discussionid);
    } else  {
        redirect('view.php?id=' . $cmid);
    }
} catch(Exception $e) {
    forum_utils::handle_exception($e);
}

?>
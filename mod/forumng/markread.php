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
$cloneid = optional_param('clone', 0, PARAM_INT);

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
        $forum = forum::get_from_cmid($cmid, $cloneid);
        $groupid = optional_param('group', -1, PARAM_INT);
        if ($groupid == 0) {
            // Just the distinction between 0 and null
            $groupid = forum::ALL_GROUPS;
        } else if ($groupid == -1) {
            $groupid = forum::NO_GROUPS;
        }
        $forum->require_view($groupid);
        if (!$forum->can_mark_read()) {
            print_error('error_cannotmarkread', 'forumng');
        }
        $forum->mark_read($groupid);
    }

    // Handle single discussion
    if ($discussionid) {
        $discussion = forum_discussion::get_from_id($discussionid, $cloneid);
        $forum = $discussion->get_forum();
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
        redirect('discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN));
    } else  {
        redirect($forum->get_url(forum::PARAM_PLAIN));
    }
} catch(Exception $e) {
    forum_utils::handle_exception($e);
}

?>
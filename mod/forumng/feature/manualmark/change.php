<?php
require_once('../../../../config.php');
require_once('../../forum.php');

// This script toggles the user's 'automatically mark read' preference.

$id = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

try {
    $forum = forum::get_from_cmid($id, $cloneid);
    $groupid = forum::get_activity_group($forum->get_course_module(), false);
    $forum->require_view($groupid);

    $manualmark = !forum::mark_read_automatically();
    if ($manualmark) {
        unset_user_preference('forumng_manualmark');
    } else {
        set_user_preference('forumng_manualmark', 1);
    }

    redirect($forum->get_url(forum::PARAM_PLAIN));
} catch(Exception $e) {
    forum_utils::handle_exception($e);
}

?>
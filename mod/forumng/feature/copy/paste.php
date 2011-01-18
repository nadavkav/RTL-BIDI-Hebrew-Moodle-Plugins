<?php
// Scripts for paste the discussion or cancel the paste.
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/forum.php');

$cmid = required_param('cmid', PARAM_INT);
$groupid = optional_param('group', forum::NO_GROUPS, PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

try {
    $targetforum = forum::get_from_cmid($cmid, $cloneid);
    if (optional_param('cancel', '', PARAM_RAW)) {
        unset($SESSION->forumng_copyfrom);
        redirect($targetforum->get_url(forum::PARAM_PLAIN));
    }
    //If the paste action has already been done or cancelled in a different window/tab
    if (!isset($SESSION->forumng_copyfrom)) {
        redirect($targetforum->get_url(forum::PARAM_PLAIN));
    }
    $olddiscussionid = $SESSION->forumng_copyfrom;
    $oldcloneid = $SESSION->forumng_copyfromclone;
    $olddiscussion = forum_discussion::get_from_id($olddiscussionid, $oldcloneid);
    // Check permission to copy the discussion
    require_capability('mod/forumng:copydiscussion',
        $olddiscussion->get_forum()->get_context());
    //security check to see if can start a new discussion in the target forum
    $targetforum->require_start_discussion($groupid);
    $olddiscussion->copy($targetforum, $groupid); 
    unset($SESSION->forumng_copyfrom);
    redirect($targetforum->get_url(forum::PARAM_PLAIN));
} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}

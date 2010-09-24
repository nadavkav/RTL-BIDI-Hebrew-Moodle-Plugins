<?php
global $CFG;
require_once($CFG->dirroot . '/mod/forumng/type/general/general_forum_type.php');

/**
 * Standard forum type.
 */
class studyadvice_forum_type extends general_forum_type {
    public function can_view_discussion($discussion, $userid=0) {
        $userid = forum_utils::get_real_userid($userid);
        
        // When loaded from cron, we need to 'fill' the discussion 
        // (time-consuming but oh well) to get the userid
        $discussion->fill(-1);
        return $discussion->get_poster()->id == $userid
            || $discussion->get_forum()->can_view_hidden($userid);
    }

    public function has_unread_restriction() {
        return true;
    }

    public function get_unread_restriction_sql($forum, $userid=0) {
        $userid = forum_utils::get_real_userid($userid);
        // See if they're already allowed to view all discussions
        if ($forum->can_view_hidden($userid)) {
            return '';
        }
        // Otherwise restrict it
        return 'fpfirst.userid=' . $userid;
    }

    protected function get_string($forum, $string, $a=null) {
        if($string == 'nodiscussions') {
            return get_string(
                $forum->can_view_hidden() ? 'studyadvice_noquestions' 
                    : 'studyadvice_noyourquestions', 'forumng');
        } else {
            return parent::get_string($string, $a);
        }
    }
}
?>
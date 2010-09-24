<?php
class copy_forum_feature extends discussion_feature {
    public function get_order() {
        return 360;
    }

    public function should_display($discussion) {
        global $SESSION;
        return has_capability('mod/forumng:copydiscussion', $discussion->get_forum()->get_context())
            && (!isset($SESSION->forumng_copyfrom)
            || $SESSION->forumng_copyfrom!=$discussion->get_id());
    }

    public function display($discussion) {
        return parent::get_button($discussion,
            get_string('copy_discussion', 'forumng'), 'feature/copy/copy.php');
    }
}
?>
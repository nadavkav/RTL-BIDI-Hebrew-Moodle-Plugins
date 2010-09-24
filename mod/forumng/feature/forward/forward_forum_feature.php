<?php
class forward_forum_feature extends discussion_feature {
    public function get_order() {
        return 1100;
    }

    public function should_display($discussion) {
        return has_capability('mod/forumng:forwardposts',
            $discussion->get_forum()->get_context());
    }

    public function display($discussion) {
        return parent::get_button($discussion,
            get_string('forward', 'forumng'),
                'feature/forward/forward.php');
    }
}

?>
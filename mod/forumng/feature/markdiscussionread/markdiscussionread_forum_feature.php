<?php
require_once(dirname(__FILE__).'/../discussion_list_feature.php');

/**
 * This feature lists posts from a user. It appears at the bottom of the
 * discussion list page.
 */
class markdiscussionread_forum_feature  extends discussion_feature {
    public function get_order() {
        return 90;
    }

    public function should_display($discussion) {
        return !forum::mark_read_automatically() &&
                $discussion->get_forum()->can_mark_read() &&
                $discussion->get_num_unread_posts();
    }

    public function display($discussion) {
        $params = $discussion->get_link_params_array();
        return parent::get_button($discussion,
                get_string('markdiscussionread', 'forumng'), 
                'markread.php', true, $params);
    }
}

?>
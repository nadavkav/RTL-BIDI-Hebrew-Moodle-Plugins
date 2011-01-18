<?php

require_once(dirname(__FILE__).'/../discussion_list_feature.php');

/**
 * This feature lists posts from a user. It appears at the bottom of the
 * discussion list page.
 */
class userposts_forum_feature extends discussion_list_feature {
    public function get_order() {
        return 300;
    }

    public function should_display($forum) {
        // Check they have actual permission
        $candisplay = has_capability('mod/forumng:viewallposts', $forum->get_context())
            && !($forum->is_shared() || $forum->is_clone());
        return $candisplay;
    }

    public function display($forum) {
        $name = get_string('viewpostsbyuser', 'forumng');
        $script = 'feature/userposts/list.php';
        return parent::get_button($forum, $name, $script);
    }
}

?>
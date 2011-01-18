<?php
require_once(dirname(__FILE__).'/../discussion_list_feature.php');

/**
 * This feature lists posts from a user. It appears at the bottom of the
 * discussion list page.
 */
class markallread_forum_feature  extends discussion_list_feature {
    public function get_order() {
        return 100;
    }

    public function should_display($forum) {
        return $forum->can_mark_read();
    }

    public function display($forum) {
        $params = $forum->get_link_params_array();
        if ($forum->get_group_mode()) {
            $params['group'] = forum::get_activity_group(
                    $forum->get_course_module());
        }
        return parent::get_button($forum, get_string('markallread', 'forumng'), 
                'markread.php', true, $params);
    }
}

?>
<?php
class delete_forum_feature extends discussion_feature {
    public function get_order() {
        return 400;
    }

    public function should_display($discussion) {
        // Display even if deleted
        return $discussion->can_manage();
    }

    public function display($discussion) {
        return parent::get_button($discussion,
            $discussion->is_deleted() ? get_string('undelete', 'forumng')
                : get_string('delete'),
            'feature/delete/delete.php', false,
            array('delete'=>($discussion->is_deleted() ? 0 : 1)));
    }
}
?>
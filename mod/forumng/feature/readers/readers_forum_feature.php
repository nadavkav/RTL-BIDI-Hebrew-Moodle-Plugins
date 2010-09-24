<?php
class readers_forum_feature extends discussion_feature {
    public function get_order() {
        return 400;
    }

    public function should_display($discussion) {
        // Check the discussion's within time period
        if (!$discussion->has_unread_data()) {
            return false;
        }

        // Check they have actual permission
        if (!has_capability('mod/forumng:viewreadinfo',
            $discussion->get_forum()->get_context())
            || $discussion->is_deleted()) {
            return false;
        }

        // For group forum, check they have group access
        if ($groupid = $discussion->get_group_id()) {
            // This requires 'write' access i.e. you don't get it just from
            // visible groups
            if (!$discussion->get_forum()->can_access_group($groupid, true)) {
                return false;
            }
        }

        // OK...
        return true;
    }

    public function display($discussion) {
        return parent::get_button($discussion,
            get_string('viewreaders', 'forumng'),
                'feature/readers/readers.php');
    }
}
?>
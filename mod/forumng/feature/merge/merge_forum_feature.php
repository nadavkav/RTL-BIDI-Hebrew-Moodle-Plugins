<?php
class merge_forum_feature extends discussion_feature {
    public function get_order() {
        return 350;
    }

    public function should_display($discussion) {
        global $SESSION;
        return has_capability('mod/forumng:splitdiscussions',
            $discussion->get_forum()->get_context())
            && $discussion->can_write_to_group()
            && !$discussion->is_deleted() && !$discussion->is_locked()
            && (!isset($SESSION->forumng_mergefrom)
                || $SESSION->forumng_mergefrom!=$discussion->get_id());
    }

    public function display($discussion) {
        global $SESSION;
        if (isset($SESSION->forumng_mergefrom)) {
            return parent::get_button($discussion,
                get_string('mergehere', 'forumng'), 'feature/merge/merge.php',
                true, array('stage'=>2),
                '<input type="submit" name="cancel" value="' .
                    get_string('cancel') . '" />', true);
        } else {
            return parent::get_button($discussion,
                get_string('merge', 'forumng'), 'feature/merge/merge.php');
        }
    }
}
?>
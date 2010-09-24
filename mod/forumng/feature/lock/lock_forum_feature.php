<?php
class lock_forum_feature extends discussion_feature {
    public function get_order() {
        return 200;
    }

    public function display($discussion) {
        if (!$discussion->is_locked()) {
            return parent::get_button($discussion,
                get_string('lock', 'forumng'), 'editpost.php', false,
                    array('lock'=>1));
        } else {
            return parent::get_button($discussion,
                get_string('unlock', 'forumng'),
                'feature/lock/unlock.php');
        }
    }
}
?>
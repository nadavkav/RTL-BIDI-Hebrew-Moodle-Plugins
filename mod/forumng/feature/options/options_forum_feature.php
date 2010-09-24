<?php
class options_forum_feature extends discussion_feature {
    public function get_order() {
        return 100;
    }

    public function display($discussion) {
        return parent::get_button($discussion,
            get_string('discussionoptions', 'forumng'), 'editpost.php');
    }
}
?>
<?php
class print_forum_feature extends discussion_feature {
    public function get_order() {
        return 1200;
    }

    public function should_display($discussion) {
        return true;
    }

    public function display($discussion) {
        return parent::get_button($discussion,
            get_string('print', 'forumng'),
                'feature/print/print.php');
    }
}
?>
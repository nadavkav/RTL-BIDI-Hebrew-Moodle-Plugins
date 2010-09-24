<?php
class export_forum_feature extends discussion_feature {
    public function get_order() {
        return 1000;
    }

    public function should_display($discussion) {
        return true;
    }

    public function display($discussion) {
        return parent::get_button($discussion,
            get_string('exportword', 'forumng'),
                'feature/export/export.php');
    }
}
?>
<?php
class portfolio_forum_feature extends discussion_feature {
    public function get_order() {
        return 1400;
    }

    public function should_display($discussion) {
        return class_exists('ouflags') && 
            has_capability('mod/portfolio:doanything', 
                $discussion->get_forum()->get_context(), NULL, true, 
                'portfolio:doanything:false', 'portfolio');
    }

    public function display($discussion) {
        return parent::get_button($discussion,
            get_string('savetoportfolio', 'forumng'),
                'feature/portfolio/savetoportfolio.php');
    }
}
?>
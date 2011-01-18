<?php
require_once(dirname(__FILE__).'/../discussion_list_feature.php');

/**
 * This feature lets users toggle between automatically and manually
 * marking discussions read. It appears at bottom of the
 * discussion list.
 */
class manualmark_forum_feature extends discussion_list_feature {
    public function get_order() {
        return 200;
    }

    public function should_display($forum) {
        // So long as you can view discussions, and you are not a guest,
        // you can mark them read.
        return $forum->can_mark_read();
    }

    public function display($forum) {
        // Work out current status
        $manualmark = !forum::mark_read_automatically();
        $current = get_string(
                $manualmark ? 'manualmark_manual' : 'manualmark_auto',
                'forumng');

        // Make a help button
        $change = get_string('manualmark_change', 'forumng');
        $helpbutton = helpbutton('manualmark', $change, 'forumng', true, 
            false, '', true);

        // Get the button form
        $params = $forum->get_link_params_array();
        return parent::get_button($forum, $change, 
                'feature/manualmark/change.php', true, $params, $helpbutton, 
                'forumng-manualmark', $current . '&nbsp;',
                'forumng-button-to-link');
    }
}

?>
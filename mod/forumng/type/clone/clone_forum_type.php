<?php
global $CFG;
require_once($CFG->dirroot . '/mod/forumng/type/general/general_forum_type.php');

/**
 * Clone forum type (not user-selectable).
 */
class clone_forum_type extends general_forum_type {
    public function is_user_selectable() {
        return false;
    }
}
?>
<?php
class move_forum_feature extends discussion_feature {
    public function get_order() {
        return 300;
    }

    public function should_display($discussion) {
        // Check they are allowed to move discussions, discussion not deleted
        if (!has_capability('mod/forumng:movediscussions',
            $discussion->get_forum()->get_context())
            || $discussion->is_deleted()
            || !$discussion->can_write_to_group()) {
            return false;
        }

        // Prevent this option on shared activities course as it will be a
        // performance issue
        global $CFG;
        if (class_exists('ouflags')) {
            require_once($CFG->dirroot . '/course/format/sharedactv/sharedactv.php');
            if (sharedactv_is_magic_course(
                $discussion->get_forum()->get_course())) {
                return false;
            }
        }

        // Otherwise always 'display' it (may display blank if there aren't
        // any target forums, though)
        return true;
    }

    private static function sort_ignore_case($a, $b) {
        $tl = textlib_get_instance();
        $alower = $tl->strtolower($a);
        $blower = $tl->strtolower($b);
        return $alower > $blower ? 1 : $alower < $blower ? -1 : 0;
    }

    public function display($discussion) {
        // Obtain list of other forums in this course where the user has the
        // 'move discussion' feature
        $modinfo = get_fast_modinfo($discussion->get_forum()->get_course());
        $results = array();
        foreach($modinfo->instances['forumng'] as $other) {
            // Don't let user move discussion to its current forum
            if ($other->instance == $discussion->get_forum()->get_id() ||
                $other->id == $discussion->get_forum()->get_course_module_id()) {
                continue;
            }
            $othercontext = get_context_instance(CONTEXT_MODULE, $other->id);
            if (has_capability('mod/forumng:movediscussions', $othercontext)) {
                $results[$other->id] = $other->name;
            }
        }
        if (count($results) == 0) {
            return '';
        }

        // Make list alphabetical
        uasort($results, array('move_forum_feature', 'sort_ignore_case'));

        // Build select using the list
        $select = choose_from_menu($results, 'target', '',
            get_string('movethisdiscussionto', 'forumng'),'', 0, true);
        return '<form method="post" action="feature/move/move.php"><div>' .
            $discussion->get_link_params(forum::PARAM_FORM) .
            $select . '<input class="forumng-zero-disable" ' .
            'type="submit" value="' .get_string('move') . '" /></div></form>';
    }
}
?>
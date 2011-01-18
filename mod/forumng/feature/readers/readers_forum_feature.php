<?php
class readers_forum_feature extends discussion_feature {
    public function get_order() {
        return 400;
    }

    public function should_display($discussion) {
        // Check the forum isn't shared (this breaks things because we dunno
        // which groups to use)
        if ($discussion->get_forum()->is_shared()) {
            return false;
        }

        // Check the discussion's within time period
        if (!$discussion->has_unread_data()) {
            return false;
        }

        // Check they have actual permission
        $context = $discussion->get_forum()->get_context();
        if (!has_capability('mod/forumng:viewreadinfo', $context)
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
        } else {
            // If the forum is NOT grouped, but the course IS, then you must
            // be in a group or have access all groups (because we will only
            // show read data for students in groups you're in)
            $course = $discussion->get_forum()->get_course();
            if ($course->groupmode && 
                    !has_capability('moodle/site:accessallgroups', $context)) {
                // Check they are in at least one group
                global $USER;
                $groups = groups_get_all_groups($course->id, $USER->id,
                        $course->defaultgroupingid);
                if (!$groups || count($groups) == 0) {
                    return false;
                }
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
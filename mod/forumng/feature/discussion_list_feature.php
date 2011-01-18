<?php

/**
 * Discussion list features appear at the bottom of a forum page.
 */
abstract class discussion_list_feature extends forum_feature {
    /**
     * Checks whether this feature should be displayed for the given user
     * in current forum.
     * By default, this checks the discussions's can_manage function and that
     * the discussion isn't deleted.
     * @param forum $forum Forum object
     * @param int $groupid Group id
     * @return bool True if this should display
     */
    public function should_display($forum) {
        return $forum->can_manage_discussions();
    }

    /**
     * @param forum $forum
     * @param int $groupid
     * @return string HTML code for button
     */
    public abstract function display($forum);

    /**
     * Convenience function for subclasses. Returns HTML code suitable to
     * use for a button in this area.
     * @param forum_discussion $discussion
     * @param string $name Text of button
     * @param string $script Name/path of .php script (relative to mod/forumng)
     * @param bool $post If true, makes the button send a POST request
     * @param array $options If included, passes these options as well as 'd'
     * @param string $afterhtml If specified, adds this HTML at end of (just
     *   inside) the form
     * @param bool $highlight If true, adds a highlight class to the form
     * @param string $beforehtml If specified, adds this HTML at start of (just
     *   inside) the form
     * @param string $buttonclass If set, adds additional css class to the button
     * @return string HTML code for button
     */
    protected static function get_button($forum, $name, $script,
            $post=false, $options=array(), $afterhtml='', $class='',
            $beforehtml='', $buttonclass='') {
        $method = $post ? 'post' : 'get';
        $optionshtml = '';
        $options['id'] = $forum->get_course_module_id(true);
        if ($forum->is_shared()) {
            $options['clone'] = $forum->get_course_module_id();
        }
        //$options['group'] = $groupid;
        if ($post) {
            $options['sesskey'] = sesskey();
        }
        foreach ($options as $key=>$value) {
            $optionshtml .= '<input type="hidden" name="' . $key .
                '" value="' . $value . '" />';
        }
        if ($class) {
            $class = " class='$class'";
        }
        if ($buttonclass) {
            $buttonclass = " class='$buttonclass'";
        }
        return "&nbsp;<form $class method='$method' action='$script'><div>" .
                $beforehtml .
                "$optionshtml<input type='submit' value='$name'$buttonclass/>" .
                "$afterhtml</div></form>";
    }

    /**
     * Returns a new object of each available type.
     * @return array Array of discussion_feature objects
     */
    public static function get_all() {
        $all = forum_feature::get_all();
        $results = array();
        foreach ($all as $feature) {
            if (is_a($feature, 'discussion_list_feature')) {
                $results[] = $feature;
            }
        }
        return $results;
    }
}

?>
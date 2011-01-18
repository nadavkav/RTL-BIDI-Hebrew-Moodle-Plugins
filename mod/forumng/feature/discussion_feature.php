<?php

/**
 * Discussion features appear at the bottom of a discussion page.
 */
abstract class discussion_feature extends forum_feature {
    /**
     * Checks whether this feature should be displayed for the current user
     * in current disscussion.
     * By default, this checks the discussions's can_manage function and that
     * the discussion isn't deleted.
     * @param forum_discussion $discussion
     * @return bool True if this should display
     */
    public function should_display($discussion) {
        return $discussion->can_manage() && !$discussion->is_deleted();
    }

    /**
     * @param forum_discussion $discussion
     * @return string HTML code for button
     */
    public abstract function display($discussion);

    /**
     * Convenience function for subclasses. Returns HTML code suitable to
     * use for a button in this area.
     * @param forum_discussion $discussion
     * @param string $name Text of button
     * @param string $script Name/path of .php script (relative to mod/forumng)
     * @param bool $post If true, makes the button send a POST request
     * @param array $options If included, passes these options as well as 'd'
     * @param string $extrahtml If specified, adds this HTML at end of (just
     *   inside) the form
     * @param bool $highlight If true, adds a highlight class to the form
     * @return string HTML code for button
     */
    protected static function get_button($discussion, $name, $script,
        $post=false, $options=array(), $extrahtml='', $highlight=false) {
        $method = $post ? 'post' : 'get';
        $optionshtml = '';
        $options['d'] = $discussion->get_id();
        if ($discussion->get_forum()->is_shared()) {
            $options['clone'] = $discussion->get_forum()->get_course_module_id();
        }
        if ($post) {
            $options['sesskey'] = sesskey();
        }
        foreach($options as $key=>$value) {
            $optionshtml .= '<input type="hidden" name="' . $key .
                '" value="' . $value . '" />';
        }

        $class = '';
        if ($highlight) {
            $class = ' class="forumng-highlight"';
        }
        return "<form method='$method' action='$script' $class><div>" .
            "$optionshtml<input type='submit' value='$name' />" .
            "$extrahtml</div></form>";
    }

    /**
     * Returns a new object of each available type.
     * @return array Array of discussion_feature objects
     */
    public static function get_all() {
        $all = forum_feature::get_all();
        $results = array();
        foreach ($all as $feature) {
            if (is_a($feature, 'discussion_feature')) {
                $results[] = $feature;
            }
        }
        return $results;
    }
}

?>
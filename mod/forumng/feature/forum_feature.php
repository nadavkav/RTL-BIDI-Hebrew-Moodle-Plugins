<?php
require_once(dirname(__FILE__).'/discussion_feature.php');
require_once(dirname(__FILE__).'/discussion_list_feature.php');
/**
 * Base class for 'forum features' which are facilities which appear at the
 * bottom of (usually) a discussion page.
 */
abstract class forum_feature {
    /**
     * Obtains the ID of this forum type. Default implementation cuts
     * '_forum_feature' off the class name and returns that.
     * @return string ID
     */
    public function get_id() {
        return str_replace('_forum_feature', '', get_class($this));
    }

    /**
     * Controls the order in which features are displayed. The lowest order
     * number is displayed first. If two items have the same order, the
     * tiebreak is the alphabetical order of their class names. Default
     * behaviour is to return order 500.
     * @return int Ordering index
     */
    public function get_order() {
        return 500;
    }

    /**
     * Compare function that orders features.
     * @param forum_feature $a One feature
     * @param forum_feature $b Another feature
     * @return int 1, -1, or 0 as per usual compare functions
     */
    private static function compare($a, $b) {
        $ordera = $a->get_order();
        $orderb = $b->get_order();
        if ($ordera > $orderb) {
            return 1;
        }
        if ($ordera < $orderb) {
            return -1;
        }
        $classa = get_class($a);
        $classb = get_class($b);
        if ($classa > $classb) {
            return 1;
        }
        if ($classb < $classa) {
            return -1;
        }
        return 0;
    }

    /**
     * Creates a new object of the given named type.
     * @param $feature Feature name (may be null for default)
     * @return forum_feature Feature
     * @throws forum_exception If the name isn't valid
     */
    public static function get_new($feature) {
        // Get type name
        if (!preg_match('~^[a-z][a-z0-9_]*$~', $feature)) {
            throw new forum_exception("Invalid forum feature name: $feature");
        }
        $classname = $feature . '_forum_feature';

        // Require library
        require_once(dirname(__FILE__) . "/$feature/$classname.php");

        // Create and return type object
        return new $classname;
    }

    /**
     * Returns a new object of each available type.
     * @return array Array of forum_feature objects
     */
    public static function get_all() {
        global $CFG;
        // Get directory listing (excluding simpletest, CVS, etc)
        $list = get_list_of_plugins('feature', '',
            $CFG->dirroot . '/mod/forumng');

        // Create array and put one of each object in it
        $results = array();
        foreach ($list as $name) {
            $results[] = self::get_new($name);
        }

        // Sort features into order and return
        usort($results, array('forum_feature', 'compare'));
        return $results;
    }
}

?>
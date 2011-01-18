<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/forum.php');
require_once($CFG->dirroot . '/mod/forumng/forum_cron.php');
/**
 * A class that deals with the various HTTP requests involved in selecting
 * specific posts (or a whole discussion) for processing, either in JavaScript
 * or non-JavaScript modes. Goes with matching JavaScript code in forumng.js.
 *
 * Example usage, in a file such as forward.php:
 *
 * // start of file
 * require_once('../post_selector.php');
 *
 * class forward_post_selector extends post_selector() {
 *   // class implements the base class methods below
 * }
 *
 * post_selector::go(new forward_post_selector());
 * // end of file
 */
abstract class post_selector {
    /**
     * For overriding in subclass. If this feature requires a particular
     * capability, require it here. The system will already have checked view
     * permission for the discussion.
     * @param object $context Moodle context object for forum
     * @param forum_discussion $discussion Discussion object
     */
    function require_capability($context, $discussion) {
        // Default makes no extra checks
    }

    /**
     * @return string Name of page for display in title etc (default is the
     *   same as button name)
     */
    function get_page_name() {
        return $this->get_button_name();
    }

    /**
     * @return string Text of button used to activate this feature
     */
    abstract function get_button_name();

    /**
     * For overriding in subclass. If there is a form, return the form object.
     * If there is no form, return null.
     *
     * NOTE: The form MUST contain a hidden field called 'postselectform' which
     * MUST always be set to 1.
     *
     * @param forum_discussion $discussion Discussion object
     * @param bool $all True if whole discussion is selected
     * @param array $selected Array of selected post IDs (if not $all)
     * @return object Form object or null if none
     */
    function get_form($discussion, $all, $selected = array()) {
        return null;
    }

    /**
     * For overriding in subclass. Called when posts have been selected. If
     * there is a form then this is called only once the form has also been
     * submitted. If there is no form then this is called as soon as posts have
     * been selected (immediately after get_form). This function must been defined
     * in your own class on what you want to do after you have selected the posts/discussion
     * @param forum_discussion $discussion
     * @param bool $all
     * @param array $selected Array of post IDs (if not $all)
     * @param object $formdata Data from form (if any; null if no form)
     */
    abstract function apply($discussion, $all, $selected, $formdata);

    /**
     * When displaying the form, extra content (such as an example of the
     * selected messages) can be displayed after it by overriding this function.
     * Default returns blank.
     * @param forum_discussion $discussion
     * @param bool $all
     * @param array $selected Array of post IDs (if not $all)
     * @param object $formdata Data from form (if any; null if no form)
     * @return string HTML content to display after form
     */
    function get_content_after_form($discussion, $all, $selected, $formdata) {
        return '';
    }

    /**
     * This function handles all aspects of page processing and then calls
     * methods in $selector at the appropriate moments.
     * @param post_selector $selector Object that extends this base class
     */
    static function go($selector) {
        $d = required_param('d', PARAM_INT);
        $cloneid = optional_param('clone', 0, PARAM_INT);

        $fromselect = optional_param('fromselect', 0, PARAM_INT);
        $all = optional_param('all', '', PARAM_RAW);
        $select = optional_param('select', '', PARAM_RAW);

        try {
            // Get basic objects
            $discussion = forum_discussion::get_from_id($d, $cloneid);
            if (optional_param('cancel', '', PARAM_RAW)) {
                // CALL TYPE 6
                redirect('../../discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN));
            }
            $forum = $discussion->get_forum();
            $cm = $forum->get_course_module();
            $course = $forum->get_course();
            $isform = optional_param('postselectform', 0, PARAM_INT);

            // Page name and permissions
            $pagename = $selector->get_page_name();
            $buttonname = $selector->get_button_name();
            $discussion->require_view();
            $selector->require_capability($forum->get_context(), $discussion);

            if(!($fromselect || $isform || $all)) {
                // Either an initial request (non-JS) to display the 'dialog' box,
                // or a request to show the list of posts with checkboxes for
                // selection

                // Both types share same navigation
                $discussion->print_subpage_header($pagename);
                if (!$select) {
                    // Show initial dialog
                    print_box_start();
?>
<h2><?php print $buttonname; ?></h2>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get"><div>
<?php
print $discussion->get_link_params(forum::PARAM_FORM)
?>
<p><?php print_string('selectorall', 'forumng'); ?></p>
<div class="forumng-buttons">
<input type="submit" name="all" value="<?php print_string('discussion', 'forumng'); ?>" />
<input type="submit" name="select" value="<?php print_string('selectedposts', 'forumng'); ?>" />
</div>
</div></form>
<?php
                    print_box_end();
                } else {
                    // Show list of posts to select
?>
<div class="forumng-selectintro">
  <p><?php print_string('selectintro', 'forumng'); ?></p>
</div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post"><div>
<?php
print $discussion->get_link_params(forum::PARAM_FORM)
?>
<input type="hidden" name="fromselect" value="1" />
<?php
                    print $forum->get_type()->display_discussion($discussion, array(
                            forum_post::OPTION_NO_COMMANDS => true,
                            forum_post::OPTION_CHILDREN_EXPANDED => true,
                            forum_post::OPTION_SELECTABLE => true));
?>
<div class="forumng-selectoutro">
<input type="submit" value="<?php print_string('confirmselection', 'forumng'); ?>" />
<input type="submit" name="cancel" value="<?php print_string('cancel'); ?>" />
</div>
</div></form>
<?php
                }

                // Display footer
                print_footer($course);
            } else {
                // Call types 3, 4, and 5 use the form (and may include list of postids)
                if ($all) {
                    $postids = false;
                } else {
                    $postids = array();
                    foreach($_POST as $field => $value) {
                        $matches = array();
                        if((string)$value !== '0' && 
                            preg_match('~^selectp([0-9]+)$~', $field, $matches)) {
                            $postids[] = $matches[1];
                        }
                    }
                }

                // Get form to use
                $mform = $selector->get_form($discussion, $all, $postids);
                if (!$mform) {
                    // Some options do not need a confirmation form; in that case,
                    // just apply the action immediately.
                    $selector->apply($discussion, $all, $postids, null);
                    exit;
                }

                // Check cancel
                if ($mform->is_cancelled()) {
                    redirect('../../discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN));
                }

                if ($fromform = $mform->get_data()) {
                    // User submitted form to confirm process, which should now be
                    // applied by selector.
                    $selector->apply($discussion, $all, $postids, $fromform);
                    exit;
                } else {
                    $discussion->print_subpage_header($pagename);
                    // User requested form either via JavaScript or the other way, and
                    // either with all messages or the whole discussion.

                    // Print form
                    print $mform->display();

                    // Print optional content that goes after form
                    print $selector->get_content_after_form($discussion, $all,
                        $postids, $fromform);

                        // Display footer
                    print_footer($course);
                }
            }
        } catch(forum_exception $e) {
            forum_utils::handle_exception($e);
        }
    }

}

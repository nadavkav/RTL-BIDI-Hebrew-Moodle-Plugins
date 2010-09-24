<?php
/**
 * A list of discussions, suitable for displaying on the forum index page. The
 * discussions may be divided into two categories: sticky and normal discussions.
 * Each discussion object contains enough information to display its entry on
 * the forum index, but does not (yet) contain actual messages.
 * @see forum_discussion
 * @package forumng
 * @author sam marshall
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @copyright Copyright 2008 The Open University
 */
class forum_discussion_list {
    private $page,$pagecount,$discussioncount;
    private $normaldiscussions,$stickydiscussions;

    /**
     * Constructs list (internal use only).
     * @param int $page Page number (1-based)
     * @param int $pagecount Count of pages
     * @param int $discussioncount Count of all discussions
     */
    function __construct($page,$pagecount,$discussioncount) {
        $this->page=$page;
        $this->pagecount=$pagecount;
        $this->discussioncount=$discussioncount;
        $this->normaldiscussions=array();
        $this->stickydiscussions=array();
    }

    /**
     * Adds a discussion to the list (internal use only).
     * @param forum_discussion $discussion
     */
    function add_discussion($discussion) {
        if($discussion->is_sticky() && !$discussion->is_deleted()) {
            $this->stickydiscussions[$discussion->get_id()]=$discussion;
        } else {
            $this->normaldiscussions[$discussion->get_id()]=$discussion;
        }
    }

    /**
     * @return array Array of all sticky discussions (forum_discussion objects)
     *   in the order they should be displayed; empty array if none
     */
    public function get_sticky_discussions() {
        return $this->stickydiscussions;
    }

    /**
     * @return array Array of all normal discussions (forum_discussion objects)
     *   in the order they should be displayed; empty array if none
     */
    public function get_normal_discussions() {
        return $this->normaldiscussions;
    }

    /**
     * @return int Page index - 1 is first page
     */
    public function get_page_index() {
        return $this->page;
    }

    /**
     * @return int Total number of available pages - e.g. if this is 6,
     *   then pages 1..6 are available.
     */
    public function get_total_pages() {
        return $this->pagecount;
    }

    /**
     * @return int Total number of discussions (not just the ones included
     *   in this list)
     */
    public function get_total_discussions() {
        return $this->discussioncount;
    }

    /**
     * @return bool True if there are no discussions in this list
     *   (get_sticky_discussions and get_normal_discussions both return
     *   empty arrrays)
     */
    public function is_empty() {
        return count($this->stickydiscussions)+count($this->normaldiscussions)==0;
    }

    /**
     * Displays a Moodle standard paging bar for this result.
     * @param string $baseurl Base URL (may include page= if you like)
     * @return string HTML code for paging bar
     */
    public function display_paging_bar($baseurl) {
        // Don't do anything if no pages
        if ($this->pagecount < 2) {
            return '';
        }
        // Remove page= if included and append &
        $baseurl = preg_replace('~&page=[0-9]+~', '', $baseurl) . '&';
        // Return Moodle standard paging bar
        $result = print_paging_bar($this->pagecount, $this->page-1, 1,
            htmlspecialchars($baseurl), 'page', false, true);
        // This is really damn annoying but discussionlist pages start from 1
        // not 0, so need to change the params
        $result = preg_replace_callback('~(&amp;page=)([0-9]+)~',
            'forum_discussion_list::munge_page_number', $result);

        return $result;
    }

    private static function munge_page_number($matches) {
        //always add &page= to the paging bar url no matter if it is the first page
        return $matches[1] . ($matches[2]+1);
    }
}
?>
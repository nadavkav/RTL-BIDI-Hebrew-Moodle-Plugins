<?php
/**
 * Represents a single forum post.
 * @see forum_discussion
 * @see forum
 * @package forumng
 * @author sam marshall
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @copyright Copyright 2009 The Open University
 */
class forum_post {
    const PARENT_NOT_LOADED = 'not_loaded';

    const PARENTPOST_DEPTH_PER_QUERY = 8;

    // For option definitions, see forum_type.php display_post function
    const OPTION_EMAIL = 'email';
    const OPTION_DIGEST = 'digest';
    const OPTION_COMMAND_REPLY = 'command_reply';
    const OPTION_COMMAND_EDIT = 'command_edit';
    const OPTION_COMMAND_DELETE = 'command_delete';
    const OPTION_COMMAND_UNDELETE = 'command_undelete';
    const OPTION_COMMAND_SPLIT = 'command_split';
    const OPTION_COMMAND_HISTORY = 'command_history';
    const OPTION_COMMAND_REPORT = 'command_report';
    const OPTION_COMMAND_DIRECTLINK = 'command_directlink';
    const OPTION_VIEW_FULL_NAMES = 'view_full_names';
    const OPTION_TIME_ZONE = 'time_zone';
    const OPTION_SUMMARY = 'summary';
    const OPTION_NO_COMMANDS = 'no_commands';
    const OPTION_RATINGS_VIEW = 'ratings_view';
    const OPTION_RATINGS_EDIT = 'ratings_edit';
    const OPTION_VIEW_DELETED_INFO = 'deleted_info';
    const OPTION_EXPANDED = 'short';
    const OPTION_FLAG_CONTROL = 'flag_control';
    const OPTION_READ_TIME = 'read_time';
    const OPTION_CHILDREN_EXPANDED = 'children_expanded';
    const OPTION_CHILDREN_COLLAPSED = 'children_collapsed';
    const OPTION_INCLUDE_LOCK = 'include_lock';
    const OPTION_EXPORT = 'export';
    const OPTION_FULL_ADDRESSES = 'full_addresses';
    const OPTION_DISCUSSION_SUBJECT = 'discussion_subject';
    const OPTION_SELECTABLE = 'selectable';
    const OPTION_VISIBLE_POST_NUMBERS = 'visible_post_numbers';
    const OPTION_USER_IMAGE = 'user_image';
    const OPTION_PRINTABLE_VERSION = 'printable_version';
    const OPTION_JUMP_NEXT = 'jump_next';
    const OPTION_JUMP_PREVIOUS = 'jump_previous';
    const OPTION_JUMP_PARENT = 'jump_parent';
    const OPTION_FIRST_UNREAD = 'first_unread';

    /** Constant indicating that post is not rated by user */
    const NO_RATING = 999;

    // Object variables and accessors
    /////////////////////////////////

    private $discussion, $parentpost, $postfields, $full, $children, 
        $forceexpand, $nextunread, $previousunread;

    /** @return forum The forum that this post is in */
    public function get_forum() { return $this->discussion->get_forum(); }

    /** @return forum_post Parent post*/
    public function get_parent() {
        if($this->parentpost==self::PARENT_NOT_LOADED) {
            throw new forum_exception('Parent post not loaded');
        }
        return $this->parentpost;
    }

    /** @return forum_discussion The discussion that this post is in  */
    public function get_discussion() { return $this->discussion; }

    /** @return int ID of this post */
    public function get_id() {
        return $this->postfields->id;
    }

    /** @return string Subject or null if no change in subject */
    public function get_subject() {
        return $this->postfields->subject;
    }

    /** @return int Post number [within discussion] */
    public function get_number() {
        if (!property_exists($this->postfields, 'number')) {
            throw new forum_exception('Post number not available here');
        }
        return $this->postfields->number;
    }

    /**
     * @return bool True if can flag
     */
    function can_flag() {
        // Cannot flag for deleted post
        if ($this->get_deleted() || $this->discussion->is_deleted()) {
            return false;
        }
        return true;
    }

    /** @return bool True if post is flagged by current user */
    public function is_flagged() {
        if (!property_exists($this->postfields, 'flagged')) {
            throw new forum_exception('Flagged information not available here');
        }
        return $this->postfields->flagged ? true : false;
    }

    /**
     * @param bool $flag True to set flag
     * @param int $userid User ID or 0 for current
     */
    public function set_flagged($flag, $userid=0) {
        $userid = forum_utils::get_real_userid($userid);
        if ($flag) {
            forum_utils::start_transaction();

            // Check there is not already a row
            if (!get_record('forumng_flags', 'postid', 
                $this->get_id(), 'userid', $userid)) {
                // Insert new row
                $newflag = (object)array('postid' => $this->get_id(),
                    'userid' => $userid, 'flagged' => time());
               forum_utils::insert_record('forumng_flags', $newflag);
            }

            // Note: Under rare circumstances this could result in two rows
            // for the same post and user, resulting in duplicates being
            // returned. This is dealt with in forum::get_flagged_posts.
            forum_utils::finish_transaction();
        } else {
            forum_utils::delete_records('forumng_flags', 'postid', $this->get_id(),
                'userid', $userid);
        }
    }

    /**
     * Obtains the subject to use for this post where a subject is required
     * (should not be blank), such as in email. May be of the form Re:
     * <parent subject>. This function call makes a database query if the full
     * discussion was not loaded into memory.
     * @param bool $expectingquery Set to true if you think this might make
     *     a db query (to prevent the warning)
     * @return string Subject
     */
    public function get_effective_subject($expectingquery = false) {
        global $CFG;
        if (property_exists($this->postfields, 'effectivesubject')) {
            return $this->postfields->effectivesubject;
        }

        // If subject is set in this post, return it
        if (!is_null($this->postfields->subject)) {
            $this->postfields->effectivesubject = $this->postfields->subject;
            return $this->postfields->effectivesubject;
        }

        // See if we already have other posts loaded
        if ($this->parentpost == self::PARENT_NOT_LOADED) {
            // Posts are not loaded, do a database query
            if(!$expectingquery) {
                debugging('This get method made a DB query; if this is expected,
                    set the flag to say so', DEBUG_DEVELOPER);
            }

            $this->postfields->effectivesubject = self::
                inner_get_recursive_subject($this->postfields->parentpostid);
            return $this->postfields->effectivesubject;
        } else {
            // Posts are loaded, loop through them to find subject
            for($parent = $this->parentpost; $parent!=null;
                $parent = $parent->parentpost) {
                if($parent->postfields->subject!==null) {
                    return get_string('re', 'forumng',
                        $parent->postfields->subject);
                }
            }
            return '[subject error]'; // shouldn't get here
        }
    }

    /**
     * Given a post id - or the id of some ancestor of a post - this query
     * obtains the next (up to) 8 ancestors and returns a 'Re:' subject line 
     * corresponding to the first ancestor which has a subject. If none of
     * the 8 have a subject, it makes another query to retrieve the next 8,
     * and so on.
     * @param int $parentid ID of a child post that we are trying to find
     *   the subject from a parent of
     * @return string Subject of post ('Re: something')
     */
    private static function inner_get_recursive_subject($parentid) {
        global $CFG;

        // Although the query looks scary because it has so many left joins,
        // in testing it worked quickly. The db just does eight primary-key 
        // lookups. Analysis of existing posts in our database showed that
        // doing 8 levels is currently sufficient for about 98.7% of posts.
        $select = '';
        $join = '';
        $maxdepth = self::PARENTPOST_DEPTH_PER_QUERY;
        for ($depth = 1; $depth <= $maxdepth; $depth++) {
            $select .= "p$depth.subject AS s$depth, p$depth.deleted AS d$depth, ";
            if ($depth >= 2) {
                $prev = $depth - 1;
                $join .= "LEFT JOIN {$CFG->prefix}forumng_posts p$depth
                    ON p$depth.id = p$prev.parentpostid ";
            }
        }
        
        do {
            $rs = get_recordset_sql("
SELECT
    $select
    p$maxdepth.parentpostid AS nextparent
FROM
    {$CFG->prefix}forumng_posts p1
    $join
WHERE
    p1.id = $parentid
");
            if (!$rs || !($rec = rs_fetch_next_record($rs))) {
                throw new forum_exception("Failed to run db query");
            }
            rs_close($rs);
            for ($depth = 1; $depth <= $maxdepth; $depth++) {
                $var = "s$depth";
                $var2 = "d$depth";
                if ($rec->{$var} !== null && $rec->{$var2}==0) {
                    return get_string('re', 'forumng', $rec->{$var});
                }
            }

            $parentid = $rec->nextparent;

        } while($parentid);

        // If the database and memory representations are correct, we shouldn't
        // really get here because the top-level post always has a subject
        return '';
    }

    /** @return object User who created this post */
    public function get_user() {
        if (!property_exists($this->postfields, 'user')) {
            throw new forum_exception('User is not available at this point.');
        }
        return $this->postfields->user;
    }

    /** @return object User who last edited this post or null if no edits */
    public function get_edit_user() {
        if (!property_exists($this->postfields, 'edituser')) {
            throw new forum_exception('Edit user is not available at this point.');
        }
        return is_null($this->postfields->edituserid)
            ? null : $this->postfields->edituser;
    }

    /** @return int Time post was originally created */
    public function get_created() { return $this->postfields->created; }

    /** @return int Time post was most recently modified */
    public function get_modified() { return $this->postfields->modified; }

    /** @return int 0 if post is not deleted, otherwise time of deletion */
    public function get_deleted() { return $this->postfields->deleted; }

    /** @return object User object (basic fields) of deleter */
    public function get_delete_user() { return $this->postfields->deleteuser; }

    /** @return bool True if this is an old version of a post */
    public function is_old_version() {
        return $this->postfields->oldversion ? true : false;
    }

    /** @return bool True if the post is important */
    public function is_important() {
        return $this->postfields->important ? true : false;
    }

    /** @return string Message (May be in arbitrary format) */
    public function get_message() {
        return $this->postfields->message;
    }

    /** @return int Format of message (Moodle FORMAT_xx constant) */
    public function get_format() {
        return $this->postfields->format;
    }

    /** @return bool True if this message has one or more attachments */
    public function has_attachments() {
        return $this->postfields->attachments ? true : false;
    }

    /**
     * Gets the names of all attachments (if any)
     * @return array Array of attachment names (may be empty). Names only,
     *   not including path to attachment folder
     */
    public function get_attachment_names() {
        $result = array();
        if (!$this->has_attachments()) {
            return $result;
        }
        $folder = $this->get_attachment_folder();
        $handle = forum_utils::opendir($folder);
        while (false !== ($name = readdir($handle))) {
            if ($name != '.' && $name != '..') {
                if (!is_dir("$folder/$name")) {
                    $result[] = $name;
                }
            }
        }
        closedir($handle);
        sort($result);
        return $result;
    }

    /**
     * @return string URL of this discussion
     */
    public function get_url() {
        return $this->get_discussion()->get_url() . '#p' . $this->get_id();
    }

    /**
     * Checks unread status (only available when requested as part of whole
     * discussion).
     * @return bool True if this post is unread
     * @throws forum_exception If unread data is not available
     */
    public function is_unread() {
        // Your own posts are always read (note: technically you can request
        // unread data for another user - so we use the id for whom data was
        // requested, not $USER->id directly).
        $userid = $this->discussion->get_unread_data_user_id();
        if (($this->postfields->edituserid == $userid) ||
            (!$this->postfields->edituserid
                && $this->postfields->userid==$userid)) {
            return false;
        }

        // Posts past sell-by are always read
        $deadline = forum::get_read_tracking_deadline();
        if ($this->postfields->modified < $deadline) {
            return false;
        }

        // Compare date to discussion read data
        return $this->postfields->modified > $this->discussion->get_time_read();
    }

    /**
     * Checks unread status of child posts (only available when requested as
     * part of whole discussion). Not a recursive method - checks only one
     * level of children.
     * @return bool True if any of the children of this post are unread
     */
    public function has_unread_child() {
        $this->require_children();
        foreach ($this->children as $child) {
            if ($child->is_unread()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if this post has any children (replies).
     * @return bool True if post has one or more replies
     */
    public function has_children() {
        $this->require_children();
        return count($this->children) > 0;
    }

    /**
     * Marks this post as being expanded from the start.
     */
    public function force_expand() {
        $this->forceexpand = true;
    }

    /** @return bool True if this is the first post of a discussion */
    public function is_root_post() {
        return $this->postfields->parentpostid ? false : true;
    }

    /**
     * @throws forum_exception If rating information wasn't queried
     */
    private function check_ratings() {
        if (!property_exists($this->postfields, 'averagerating')) {
            throw new forum_exception('Rating information not retrieved');
        }
    }

    /**
     * @param bool $astext If true, returns a string rather than a number
     * @return mixed Average rating as float, or a string description if
     *   $astext is true
     * @throws forum_exception If rating information wasn't queried
     */
    public function get_average_rating($astext = false) {
        $this->check_ratings();
        if ($astext) {
            $options = $this->get_forum()->get_rating_options();
            $value = (int)round($this->postfields->averagerating);
            if (array_key_exists($value, $options)) {
                return $options[$value];
            } else {
                return '?'; // Can occur if rating scale is changed
            }
        } else {
            return $this->postfields->averagerating;
        }
    }

    /**
     * @return int Number of ratings of this post (may be 0)
     */
    public function get_num_ratings() {
        $this->check_ratings();
        return $this->postfields->numratings;
    }

    /**
     * @return int Current user's rating of this post or null if none
     * @throws forum_exception If rating information wasn't queried
     */
    public function get_own_rating() {
        $this->check_ratings();
        return $this->postfields->ownrating;
    }

    /**
     * Obtains search document representing this post.
     * @return ousearch_document Document object
     */
    function search_get_document() {
        $doc=new ousearch_document();
        $doc->init_module_instance('forumng',
            $this->get_forum()->get_course_module());
        if($groupid = $this->discussion->get_group_id()) {
            $doc->set_group_id($groupid);
        }
        $doc->set_int_refs($this->get_id());
        return $doc;
    }

    /**
     * @param array $out Array that receives list of this post and all 
     *   children (including nested children) in order
     */
    public function build_linear_children(&$out) {
        $this->require_children();
        $out[count($out)] = $this;
        foreach($this->children as $child) {
            $child->build_linear_children($out);
        }
    }

    /**
     * Finds a child post (or this one) with the specified ID.
     * @param int $id Post ID
     * @param bool $toplevel True for initial request (makes it throw
     *   exception if not found)
     * @return forum_post Child post
     */
    public function find_child($id, $toplevel=true) {
        if ($this->postfields->id == $id) {
            return $this;
        }
        $this->require_children();
        foreach($this->children as $child) {
            $result = $child->find_child($id, false);
            if ($result) {
                return $result;
            }
        }

        if ($toplevel) {
            throw new forum_exception("Child id $id not found");
        }
        return null;
    }

    /**
     * Finds which child post (or this) has the most recent modified date.
     * @param forum_post &$newest Newest post (must be null when calling)
     */
    public function find_newest_child(&$newest) {
        if (!$newest || $newest->get_modified() < $this->get_modified()) {
            $newest = $this;
        }
        $this->require_children();
        foreach($this->children as $child) {
            $child->find_newest_child($newest);
        }
    }

    /**
     * Adds the ID of all children (and this post itself) to a list.
     * @param array &$list List of IDs
     */
    public function list_child_ids(&$list) {
        $list[] = $this->get_id();
        $this->require_children();
        foreach($this->children as $child) {
            $child->list_child_ids($list);
        }
    }

    /**
     * @return forum_post Next unread post or null if there are no more
     */
    public function get_next_unread() {
        $this->require_children();
        return $this->nextunread;
    }

    /**
     * @return forum_post Previous unread post or null if there are no more
     */
    public function get_previous_unread() {
        $this->require_children();
        return $this->previousunread;
    }

    /**
     * Used by discussion to set up the unread posts.
     * @param forum_post $nextunread
     * @param forum_post $previousunread
     */
    function set_unread_list($nextunread, $previousunread) {
        $this->nextunread = $nextunread;
        $this->previousunread = $previousunread;
    }

    // Factory method
    /////////////////

    /**
     * Creates a forum post object, forum object, and all related data from a
     * single forum post ID. Intended when entering a page which uses post ID
     * as a parameter.
     * @param int $id ID of forum post
     * @param bool $wholediscussion If true, retrieves entire discussion
     *   instead of just this single post
     * @param bool $usecache True to use cache when retrieving the discussion
     * @param int $userid User ID to get post on behalf of (controls flag data
     *   retrieved)
     * @return forum_post Post object
     */
    public static function get_from_id($id, $wholediscussion=false, $usecache=false, $userid=0) {
        if ($wholediscussion) {
            $discussion = forum_discussion::get_from_post_id($id, $usecache, $usecache);
            $root = $discussion->get_root_post();
            return $root->find_child($id);
        } else {
            // Get post data (including extra data such as ratings and flags)
            $records = self::query_posts('fp.id='.$id, 'fp.id', true, true, false, $userid);
            if(count($records)!=1) {
                throw new forum_exception("Invalid post ID $id");
            }
            $postfields = reset($records);

            $discussion = forum_discussion::get_from_id($postfields->discussionid);
            $newpost = new forum_post($discussion, $postfields);
            return $newpost;
        }
    }

    /**
     * Obtains a search document given the ousearch parameters.
     * @param object $document Object containing fields from the ousearch documents table
     * @return mixed False if object can't be found, otherwise object containing the following
     *   fields: ->content, ->title, ->url, ->activityname, ->activityurl,
     *   and optionally ->extrastrings array, ->data, ->hide
     */
    static function search_get_page($document) {
        global $CFG;

        // Implemented directly in SQL for performance, rather than using the
        // objects themselves
        $result = forum_utils::get_record_sql("
SELECT
    fp.message AS content, fp.subject, firstpost.subject AS firstpostsubject,
    firstpost.id AS firstpostid, fd.id AS discussionid,
    f.name AS activityname, cm.id AS cmid, fd.timestart, fd.timeend
FROM
    {$CFG->prefix}forumng_posts fp
    INNER JOIN {$CFG->prefix}forumng_discussions fd ON fd.id=fp.discussionid
    INNER JOIN {$CFG->prefix}forumng_posts firstpost ON fd.postid=firstpost.id
    INNER JOIN {$CFG->prefix}forumng f ON fd.forumid = f.id
    INNER JOIN {$CFG->prefix}course_modules cm ON cm.instance = f.id AND cm.course = f.course
    INNER JOIN {$CFG->prefix}modules m ON cm.module = m.id
WHERE
    fp.id = {$document->intref1} AND m.name='forumng'");
        if (!$result) {
            return false;
        }

        // Title is discussion subject...
        $result->title = $result->firstpostsubject;
        // ...plus post subject, if not discussion post ourselves
        if ($result->subject && ($result->firstpostid != $document->intref1)) {
            $result->title .= ': ' . $result->subject;
        }

        // Work out URL to post
        $result->url = $CFG->wwwroot . '/mod/forumng/discuss.php?d=' .
            $result->discussionid . '#p' . $document->intref1;

        // Activity name and URL
        $result->activityurl = $CFG->wwwroot . '/mod/forumng/view.php?id=' .
            $result->cmid;

        // Hide results outside their time range (unless current user can see)
        $now = time();
        if ($now < $result->timestart || ($result->timeend && $now>=$result->timeend) &&
            !has_capability('mod/forumng:viewallposts',
                get_context_instance(CONTEXT_MODULE, $result->cmid))) {
            $result->hide = true;
        }

        return $result;
    }

    // Object methods
    /////////////////

    /**
     * @param forum_discussion $discussion Discussion object
     * @param object $postfields Post fields from DB table (may also include 
     *   some extra fields provided by forum_post::query_posts)
     * @param forum_post $parentpost Parent post or null if this is root post,
     *   or PARENT_NOT_LOADED if not available
     */
    function __construct($discussion, $postfields, $parentpost=self::PARENT_NOT_LOADED) {
        $this->discussion = $discussion;
        $this->postfields = $postfields;

        // Extract the user details into Moodle user-like objects
        if (property_exists($postfields,'u_id')) {
            $postfields->user = forum_utils::extract_subobject($postfields, 'u_');
            $postfields->edituser = forum_utils::extract_subobject($postfields, 'eu_');
            $postfields->deleteuser = forum_utils::extract_subobject($postfields, 'du_');
        }

        $this->parentpost = $parentpost;
        $this->children = false;
    }

    /**
     * Used to inform the post that all its children will be supplied.
     * Call before calling add_child(), or even if there are no children.
     */
    function init_children() {
        $this->children = array();
    }

    /**
     * For internal use only. Adds a child to this post while constructing
     * the tree of posts
     * @param forum_post $child Child post
     */
    function add_child($child) {
        $this->require_children();
        $this->children[] = $child;
    }

    /**
     * Checks that children are available.
     * @throws forum_exception If children have not been loaded
     */
    function require_children() {
        if (!is_array($this->children)) {
            throw new forum_exception('Requires child post data');
        }
    }

    /**
     * Internal function. Queries for posts.
     * @param string $where Where clause (fp is alias for post table)
     * @param string $order Sort order; the default is fp.id - note this is preferable
     *   to fp.timecreated because it works correctly if there are two posts in
     *   the same second
     * @param bool $ratings True if ratings should be included in the query
     * @param bool $flags True if flags should be included in the query
     * @param bool $effectivesubjects True if the query should include the
     *   (complicated!) logic to obtain the 'effective subject'. This may result
     *   in additional queries afterward for posts which are very deeply nested.
     * @param int $userid 0 = current user (at present this is only used for
     *   flags)
     * @return array Resulting posts as array of Moodle records, empty array
     *   if none
     */
    static function query_posts($where, $order='fp.id', $ratings=true,
        $flags=false, $effectivesubjects=false, 
        $userid=0, $joindiscussion=false, $discussionsubject=false, $limitfrom='', 
        $limitnum='') {
        global $CFG, $USER;
        $userid = forum_utils::get_real_userid($userid);

        // We include ratings if these are enabled, otherwise save the database
        // some effort and don't bother
        if ($ratings) {
            $ratingsquery = ",
(SELECT AVG(rating) FROM {$CFG->prefix}forumng_ratings
    WHERE postid=fp.id) AS averagerating,
(SELECT COUNT(1) FROM {$CFG->prefix}forumng_ratings
    WHERE postid=fp.id) AS numratings,
(SELECT rating FROM {$CFG->prefix}forumng_ratings
    WHERE postid=fp.id AND userid={$USER->id}) AS ownrating";
        } else {
            $ratingsquery = '';
        }

        if ($flags) {
            $flagsjoin = "
    LEFT JOIN {$CFG->prefix}forumng_flags ff ON ff.postid = fp.id AND ff.userid = $userid";
            $flagsquery = ",ff.flagged";
        } else {
            $flagsjoin = '';
            $flagsquery = '';
        }

        if ($joindiscussion) {
            $discussionjoin = "
    INNER JOIN {$CFG->prefix}forumng_discussions fd ON fp.discussionid = fd.id";
            $discussionquery = ',' . forum_utils::select_discussion_fields('fd');
            if ($discussionsubject) {
                $discussionjoin .= "
    INNER JOIN {$CFG->prefix}forumng_posts fdfp ON fd.postid = fdfp.id";
                $discussionquery .= ', fdfp.subject AS fd_subject';
            }
        } else {
            $discussionjoin = '';
            $discussionquery = '';
        }
        
        if ($effectivesubjects) {
            $maxdepth = self::PARENTPOST_DEPTH_PER_QUERY;
            $subjectsjoin = '';
            $subjectsquery = ", p$maxdepth.parentpostid AS nextparent ";

            for ($depth = 2; $depth <= $maxdepth; $depth++) {
                $subjectsquery .= ", p$depth.subject AS s$depth, p$depth.deleted AS d$depth";
                $prev = 'p'. ($depth - 1);
                if ($prev == 'p1') {
                    $prev = 'fp';
                }
                $subjectsjoin .= "LEFT JOIN {$CFG->prefix}forumng_posts p$depth
                    ON p$depth.id = $prev.parentpostid ";
            }
        } else {
            $subjectsjoin = '';
            $subjectsquery = '';
        }

        // Retrieve posts from discussion with incorporated user information
        // and ratings info if specified
        $results = forum_utils::get_records_sql("
SELECT
    fp.*,
    ".forum_utils::select_username_fields('u').",
    ".forum_utils::select_username_fields('eu').",
    ".forum_utils::select_username_fields('du')."
    $ratingsquery
    $flagsquery
    $subjectsquery
    $discussionquery
FROM
    {$CFG->prefix}forumng_posts fp
    INNER JOIN {$CFG->prefix}user u ON fp.userid=u.id
    LEFT JOIN {$CFG->prefix}user eu ON fp.edituserid=eu.id
    LEFT JOIN {$CFG->prefix}user du ON fp.deleteuserid=du.id    
    $discussionjoin
    $flagsjoin
    $subjectsjoin
WHERE
    $where
ORDER BY
    $order
", $limitfrom, $limitnum);
        if ($effectivesubjects) {
            // Figure out the effective subject for each result
            foreach($results as $result) {
                $got = false;
                if ($result->subject !== null) {
                    $result->effectivesubject = $result->subject;
                    $got = true;
                    continue;
                }
                for ($depth = 2; $depth <= $maxdepth; $depth++) {
                    $var = "s$depth";
                    $var2 = "d$depth";
                    if (!$got && $result->{$var} !== null && $result->{$var2}==0) {
                        $result->effectivesubject = get_string('re', 'forumng', $result->{$var});
                        $got = true;
                    }
                    unset($result->{$var});
                    unset($result->{$var2});
                }
                if (!$got) {
                    // Do extra queries to pick up subjects for posts where it
                    // was unknown within the default depth. We can use the 
                    // 'nextparent' to get the ID of the parent post of the last
                    // one that we checked already
                    $result->effectivesubject = self::inner_get_recursive_subject(
                        $result->nextparent);
                }
            }
        }
        return $results;
    }

    /**
     * Replies to the post
     * @param string $subject Subject
     * @param string $message Message
     * @param int $format Moodle format used for message
     * @param array $attachments Array of paths to temporary files of
     *   attachments in post. [Note that these should have already been checked
     *   and renamed by the Moodle upload manager. They will be moved or
     *   deleted by the time this method returns.]
     * @param bool $setimportant If true, highlight the post
     * @param bool $mailnow If true, sends mail ASAP
     * @param int $userid User ID (0 = current)
     * @param bool $log True to log this reply
     * @return int ID of newly-created post
     */
    function reply($subject, $message, $format,
        $attachments=array(), $setimportant=false, $mailnow=false, $userid=0, $log=true) {
        forum_utils::start_transaction();
        $id = $this->discussion->create_reply($this, $subject, $message, $format,
            $attachments,$setimportant,$mailnow, $userid);
        if($log) {
            $this->log('add reply');
        }
        forum_utils::finish_transaction();
        $this->get_discussion()->uncache();
        return $id;
    }

    /**
     * Obtains a list of previous versions of this post (if any), in descending
     * order of modification date.
     * @return array Array of forum_post objects (empty if none)
     */
    function get_old_versions() {
        $postdata = self::query_posts(
            'fp.oldversion=1 AND fp.parentpostid='.$this->postfields->id,
            'fp.modified DESC', false, false);
        $posts = array();
        foreach($postdata as $postfields) {
            $newpost = new forum_post($this->discussion, $postfields, $this);
            $posts[] = $newpost;
        }
        return $posts;
    }

    /**
     * Recursive function obtains all users IDs that made this post and all
     * child posts.
     * @param array &$userids Associative array from id=>true that receives
     *   all user IDs
     */
    function list_all_user_ids(&$userids) {
        $this->require_children();

        // Add current ID
        $userid = $this->get_user()->id;
        if (!array_key_exists($userid, $userids)) {
            $userids[$userid] = true;
        }

        foreach ($this->children as $post) {
            $post->list_all_user_ids($userids);
        }
    }

    /**
     * @param int $otherpostid Optional parameter so that you can request
     *   the folder for a different post id in this same discussion
     * @return string Path of folder used for attachments to this post
     */
    function get_attachment_folder($otherpostid = 0) {
        return $this->get_discussion()->get_attachment_folder() .
            '/' . ($otherpostid ? $otherpostid : $this->postfields->id);
    }

    /**
     * Gets the attachment folder for any post.
     * @param int $courseid Course
     * @param int $forumid Forum
     * @param int $discussionid Discussion
     * @param int $postid Post
     */
    static function get_any_attachment_folder($courseid, $forumid,
        $discussionid, $postid) {
        return forum_discussion::get_attachment_folder($courseid, $forumid,
            $discussionid) . '/' . $postid;
    }

    /**
     * Adds an attachment to this post. The attachment must have already been
     * checked, and had its name sanitised, by an upload manager. Also note
     * that this method does NOT set the 'has attachments' flag in the
     * post; this flag should be set at a higher level.
     * @param string $path Path of new attachment file to add
     */
    function add_attachment($path) {
        // Check source file exists
        if(!file_exists($path)) {
            throw new forum_exception("Missing file $path");
        }

        // Get and check folder
        $folder = $this->get_attachment_folder();
        if(!check_dir_exists($folder, true, true)) {
            throw new forum_exception("Failed to create attachment folder $folder");
        }

        // Check target path doesn't already exist. If it does, delete existing
        // file.
        $target = $folder.'/'.basename($path);
        if(file_exists($target)) {
            forum_utils::unlink($target);
        }

        // Move new file into place
        forum_utils::rename($path, $target);

        $this->get_discussion()->uncache();
    }

    /**
     * Edits an existing message. The previous version of the message is
     * retained for admins to view if needed.
     * @param string $subject Subject
     * @param string $message Message
     * @param int $format Moodle format ID
     * @param mixed $deleteattachments Array of names (only) of existing 
     *   attachments to delete, or 'true' to delete all
     * @param array $newattachments Additional attachments to add (if any)
     * @param bool $setimportant If true, highlight the post
     * @param bool $mailnow New value of mailnow flag (ignored if message was already mailed)
     * @param int $userid Userid doing the editing (0 = current)
     */
    function edit($subject, $message, $format,
        $deleteattachments=array(), $newattachments=array(), $setimportant=false, $mailnow=false, $userid=0,
        $log=true) {
        $now = time();

        // Create copy of existing entry ('old version')
        $copy = clone($this->postfields);
        $copy->subject = is_null($copy->subject) ? null : addslashes($copy->subject);
        $copy->message = addslashes($copy->message);

        // Copy has oldversion set to 1 and parentpost set to id of real post
        $copy->oldversion = 1;
        $copy->parentpostid = $copy->id;
        unset($copy->id);

        // OK, add copy
        forum_utils::start_transaction();
        $copyid = forum_utils::insert_record('forumng_posts', $copy);

        // If there are attachments...
        $attachments = $this->get_attachment_names();
        $copiedattachments = 0;
        if ($attachments) {
            // Move the folder
            forum_utils::rename($newfolder=$this->get_attachment_folder(),
                $oldfolder=$this->get_attachment_folder($copyid));
            forum_utils::folder_debug('rename',
                'forum_post::edit', 'p=' . $this->get_id(), 
                $newfolder, $oldfolder);

            // If we're keeping attachments, copy them too
            if ($deleteattachments !== true) {
                $created = false;
                foreach ($attachments as $name) {
                    if (!$deleteattachments || !in_array($name, $deleteattachments)) {
                        if (!$created) {
                            if (!mkdir($newfolder)) {
                                throw new forum_exception(
                                    'Error creating updated attachment folder');
                            }
                            $created = true;
                        }
                        forum_utils::copy("$oldfolder/$name", "$newfolder/$name");
                        $copiedattachments++;
                    }
                }
            }
        }

        // Update existing entry with new data where it changed
        $update = new StdClass;
        $gotsubject = false;
        if($subject!==$this->postfields->subject) {
            $update->subject = strlen(trim($subject)) == 0 ? null : addslashes($subject);
            $gotsubject = true;
        }
        if($message!==$this->postfields->message) {
            $update->message = addslashes($message);
        }
        if($format!=$this->postfields->format) {
            $update->format = $format;
        }
        if($copiedattachments==0 && count($newattachments)==0 &&
            $this->postfields->attachments) {
            $update->attachments = 0;
        } else if(
            count($newattachments)>0 && !$this->postfields->attachments) {
            $update->attachments = 1;
        }
        if($setimportant) {
            $update->important = 1;
        } else {
            $update->important = 0;
        }
        if($this->postfields->mailstate==forum::MAILSTATE_NOT_MAILED &&
            $mailnow) {
            $update->mailstate = forum::MAILSTATE_NOW_NOT_MAILED;
        } else if($this->postfields->mailstate==forum::MAILSTATE_NOW_NOT_MAILED &&
            !$mailnow) {
            $update->mailstate = forum::MAILSTATE_NOT_MAILED;
        }
        $update->modified = $now;
        $update->edituserid = forum_utils::get_real_userid($userid);
        if(count((array)$update)>0) {
            $update->id = $this->postfields->id;
            forum_utils::update_record('forumng_posts', $update);
        }

        // Add new attachments
        foreach($newattachments as $path) {
            $this->add_attachment($path);
        }

        if($log) {
            $this->log('edit post');
        }

        // Update in-memory representation
        foreach((array)$update as $name=>$value) {
            $this->postfields->{$name} =
                $value===null ? null : stripslashes($value);
        }
        // If this is the root post, then changing its subject affects
        // the discussion subhject
        if ($this->is_root_post() && $gotsubject) {
            $this->discussion->hack_subject($this->postfields->subject);
        }

        // Uncache before updating search (want to make sure that the recursive
        // update gets latest data)
        $this->get_discussion()->uncache();

        // Update search index
        if ((isset($update->message) || $gotsubject)) {
            // Update for this post
            $this->search_update();

            // If changing the subject of a root post, update all posts in the
            // discussion (ugh)
            if ($this->is_root_post() && $gotsubject) {
                $this->search_update_children();
            }
        }

        forum_utils::finish_transaction();
    }

    /**
     * Updates search data for this post.
     * @param bool $expectingquery True if it might need to make a query to
     *   get the subject
     */
    function search_update($expectingquery = false) {
        if (!forum::search_installed()) {
            return;
        }

        // Disable transactions in search; forum manages transactions itself
        global $OUSEARCH_NO_TRANSACTIONS;
        $OUSEARCH_NO_TRANSACTIONS = true;

        $searchdoc = $this->search_get_document();

        forum_utils::start_transaction();
        if ($this->get_deleted() || $this->get_discussion()->is_deleted() ||
            $this->get_discussion()->is_making_search_change()) {
            if ($searchdoc->find()) {
                $searchdoc->delete();
            }
        } else {
            // Title is the discussion subject plus ': ' plus post subject
            $title = $this->get_discussion()->get_subject($expectingquery);
            if (!$this->is_root_post() && !is_null($this->get_subject())) {
                $title .= ': ' . $this->get_subject();
            }
            $searchdoc->update($title, $this->get_message());
        }
        forum_utils::finish_transaction();
    }

    /**
     * Calls search_update on each child of the current post, and recurses.
     * Used when the subject's discussion is changed.
     */
    function search_update_children() {
        if (!forum::search_installed()) {
            return;
        }
        // If the in-memory post object isn't already part of a full
        // discussion...
        if (!is_array($this->children)) {
            // ...then get one
            $discussion = forum_discussion::get_from_id(
                $this->discussion->get_id());
            $post = $discussion->get_root_post()->find_child($this->get_id());
            // Do this update on the new discussion
            $post->search_update_children();
            return;
        }

        // Loop through all children
        foreach ($this->children as $child) {
            // Update its search fields
            $child->search_update();

            // Recurse
            $child->search_update_children();
        }
    }

    /**
     * Marks a post as deleted.
     * @param int $userid User ID to mark as having deleted the post
     * @param bool $log If true, adds entry to Moodle log
     */
    function delete($userid=0, $log=true) {
        if($this->postfields->deleted) {
            return;
        }
        if(!$this->postfields->parentpostid) {
            throw new forum_exception('Cannot delete discussion root post');
        }
        forum_utils::start_transaction();

        // Mark this post as deleted
        $update = new StdClass;
        $update->id = $this->postfields->id;
        $update->deleted = time();
        $update->deleteuserid = forum_utils::get_real_userid($userid);
        forum_utils::update_record('forumng_posts', $update);
        $this->postfields->deleted = $update->deleted;
        $this->postfields->deleteuserid = $update->deleteuserid;

        // In case this post is the last one, update the discussion field
        $this->get_discussion()->possible_lastpost_change($this);

        // May result in user becoming incomplete
        $this->update_completion(false);

        if($log) {
            $this->log('delete post');
        }

        $this->search_update();

        forum_utils::finish_transaction();
        $this->get_discussion()->uncache();
    }

    /**
     * Marks a post as undeleted.
     * @param bool $log If true, adds entry to Moodle log
     */
    function undelete($log=true) {
        if(!$this->postfields->deleted) {
            return;
        }
        forum_utils::start_transaction();

        // Undelete this post
        $update = new StdClass;
        $update->id = $this->postfields->id;
        $update->deleted = 0;
        $update->deleteuserid = 0;
        forum_utils::update_record('forumng_posts', $update);
        $this->postfields->deleted = 0;
        $this->postfields->deleteuserid = 0;

        // In case this post is the last one, update the discussion field
        $this->get_discussion()->possible_lastpost_change($this);

        // May result in user becoming complete
        $this->update_completion(true);

        if($log) {
            $this->log('undelete post');
        }

        $this->search_update();

        forum_utils::finish_transaction();
        $this->get_discussion()->uncache();
    }

    /**
     * Splits this post to become a new discussion
     * @param $newsubject
     * @param bool $log True to log action
     * @return int ID of new discussion
     */
    function split($newsubject, $log=true) {
        global $CFG;
        $this->require_children();

        // Begin a transaction
        forum_utils::start_transaction();

        $olddiscussion = $this->get_discussion();

        // Create new discussion
        $newest = null;
        $this->find_newest_child($newest);
        $newdiscussionid = $olddiscussion->clone_for_split(
            $this->get_id(), $newest->get_id());

        // Update all child posts
        $list = array();
        $this->list_child_ids($list);
        unset($list[0]); // Don't include this post itself
        if (count($list) > 0) {
            $inorequals = forum_utils::in_or_equals($list);
            forum_utils::execute_sql("
UPDATE
    {$CFG->prefix}forumng_posts
SET
    discussionid = $newdiscussionid
WHERE
    id $inorequals");
        }

        // Update this post
        $changes = new stdClass;
        $changes->id = $this->get_id();
        $changes->subject = addslashes($newsubject);
        $changes->parentpostid = null;
        //When split the post, reset the important to 0 so that it is not highlighted.
        $changes->important = 0;
        // Note don't update modified time, or it makes this post unread,
        // which isn't very helpful
        $changes->discussionid = $newdiscussionid;
        forum_utils::update_record('forumng_posts', $changes);

        // Update read data if relevant
        if (forum::enabled_read_tracking() &&
            ($newest->get_modified() >= forum::get_read_tracking_deadline())) {
            $rs = forum_utils::get_recordset_sql("
SELECT
    userid, time
FROM
    {$CFG->prefix}forumng_read
WHERE
    discussionid = " . $olddiscussion->get_id() . "
    AND time >= " . $this->get_created());
            while($rec = rs_fetch_next_record($rs)) {
                $rec->discussionid = $newdiscussionid;
                forum_utils::insert_record('forumng_read', $rec);
            }
            rs_close($rs);
        }

        $olddiscussion->possible_lastpost_change();

        // Move attachments
        $olddiscussionfolder = $olddiscussion->get_attachment_folder();
        $newdiscussionfolder = $olddiscussion->get_attachment_folder(0, 0, $newdiscussionid);
        if (is_dir($olddiscussionfolder)) {
            // Put this post back on the list
            $list[0] = $this->get_id();
            // Loop through all posts; move attachments if present
            $madenewfolder = false;
            foreach ($list as $id) {
                $oldfolder = $olddiscussionfolder . '/' . $id;
                $newfolder = $newdiscussionfolder . '/' . $id;
                if (is_dir($oldfolder)) {
                    if (!$madenewfolder) {
                        check_dir_exists($newfolder, true, true);
                        $madenewfolder = true;
                    }
                    forum_utils::rename($oldfolder, $newfolder);
                    forum_utils::folder_debug('rename',
                        'forum_post::split', 'p=' . $this->get_id(), 
                        $oldfolder, $newfolder);
                }
            }
        }

        if($log) {
            $this->log('split post');
        }

        forum_utils::finish_transaction();
        $this->get_discussion()->uncache();

        // If discussion-based completion is turned on, this may enable someone
        // to complete
        if ($this->get_forum()->get_completion_discussions()) {
            $this->update_completion(true);
        }

        return $newdiscussionid;
    }

    /**
     * Rates this post or updates an existing rating.
     * @param $rating Rating (value depends on scale used) or NO_RATING
     * @param $userid User ID or 0 for current user
     */
    function rate($rating, $userid=0) {
        if(!$userid) {
            global $USER;
            $userid = $USER->id;
        }
        forum_utils::start_transaction();

        // Delete any existing rating
        forum_utils::delete_records('forumng_ratings',
            'postid', $this->postfields->id, 'userid', $userid);

        // Add new rating
        if ($rating != self::NO_RATING) {
            $ratingobj = new StdClass;
            $ratingobj->userid = $userid;
            $ratingobj->postid = $this->postfields->id;
            $ratingobj->time = time();
            $ratingobj->rating = $rating;
            forum_utils::insert_record('forumng_ratings', $ratingobj);
        }

        // Tell grade to update
        if ($this->get_forum()->get_grading()) {
            $this->get_forum()->update_grades($this->get_user()->id);
        }

        forum_utils::finish_transaction();
        $this->get_discussion()->uncache();
    }

    /**
     * Records an action in the Moodle log for current user.
     * @param $action Action name - see datalib.php for suggested verbs
     *   and this code for example usage
     */
    function log($action) {
        add_to_log($this->get_forum()->get_course_id(), 'forumng',
            $action,
            $this->discussion->get_log_url().'#p'.$this->postfields->id,
            $this->postfields->id,
            $this->get_forum()->get_course_module_id());
    }

    // Permissions
    //////////////

    /**
     * Makes security checks for viewing this post. Will not return if
     * user cannot view it.
     * This function should be a complete access check. It calls the
     * discussion's equivalent method.
     * Note that this function only works for the current user when used in
     * interactive mode (ordinary web page view). It cannot be called in cron,
     * web services, etc.
     */
    function require_view() {
        // Check forum and discussion view permission, group access, etc.
        $this->discussion->require_view();

        // Other than being able to view the discussion, no additional
        // requirements to view a normal post
        if(!$this->get_deleted() && !$this->is_old_version()) {
            return true;
        }

        // Deleted posts and old versions of edited posts require viewallposts
        require_capability('mod/forumng:viewallposts',
            $this->get_forum()->get_context());
    }

    /**
     * Checks whether the user can add a new reply to this post, assuming that
     * they can view the discussion.
     * @param string &$whynot
     * @param int $userid
     * @return unknown_type
     */
    function can_reply(&$whynot, $userid=0) {
        global $CFG;
        $userid = forum_utils::get_real_userid($userid);
        $context = $this->get_forum()->get_context();

        // Check if post is a special case
        if ($this->get_deleted() || $this->is_old_version()
            || $this->get_discussion()->is_deleted()) {
            $whynot = 'reply_notcurrentpost';
            return false;
        }
        
        // Check if discussion is different group
        if (!$this->get_discussion()->can_write_to_group()) {
            $whynot = 'reply_wronggroup';
            return false;
        }

        // Check if discussion is locked
        if ($this->get_discussion()->is_locked()) {
            $whynot = 'edit_locked';
            return false;
        }

        // Check read-only dates
        if ($this->get_forum()->is_read_only($userid)) {
            $whynot = 'reply_readonly';
            return false;
        }

        // Check permission
        if (!has_capability('mod/forumng:replypost', $context, $userid)) {
            $whynot = 'reply_nopermission';
            return false;
        }

        // Let forum type veto reply if required
        if (!$this->get_forum()->get_type()->can_reply($this, $userid)) {
            $whynot = 'reply_typelimit';
            return false;
        }

        // Throttling
        if ($this->get_forum()->get_remaining_post_quota($userid) == 0) {
            $whynot = 'reply_postquota';
            return false;
        }

        return true;
    }

    /**
     * @param int $userid User ID or 0 for current
     * @return bool True if user can rate this post
     */
    function can_rate($userid=0) {
        $userid = forum_utils::get_real_userid($userid);
        return
            !$this->get_deleted() && !$this->is_old_version()
            && !$this->get_discussion()->is_deleted()
            && !$this->get_discussion()->is_locked()
            && $this->get_discussion()->can_write_to_group()
            && $this->get_forum()->can_rate($this->get_created()) &&
            $this->get_user()->id != $userid;
    }


    /**
     * @param int $userid User ID or 0 for current
     * @return bool True if user can view ratings for this post
     */
    function can_view_ratings($userid=0) {
        $userid = forum_utils::get_real_userid($userid);
        return !$this->get_deleted() && !$this->is_old_version()
            && $this->get_forum()->has_ratings() &&
            has_capability($this->get_user()->id == $userid
            ? 'mod/forumng:viewrating'
            : 'mod/forumng:viewanyrating', $this->get_forum()->get_context());
    }

    function can_split(&$whynot, $userid=0) {
        global $CFG;

        // Check if this is a special case
        if ($this->get_deleted() || $this->is_old_version()
            || $this->get_discussion()->is_deleted()) {
            $whynot = 'edit_notcurrentpost';
            return false;
        }

        // Check if discussion is different group
        if (!$this->get_discussion()->can_write_to_group()) {
            $whynot = 'edit_wronggroup';
            return false;
        }

        // Can't split root post
        if ($this->is_root_post()) {
            $whynot = 'edit_rootpost';
            return false;
        }

        // Check permission
        if (!$this->get_discussion()->can_split($whynot, $userid)) {
            return false;
        }

        return true;
    }

    /**
     * @param string &$whynot
     * @return bool True if user can alert this post
     */
    function can_alert(&$whynot) {
        global $CFG;

        // Check if the post has been deleted
        if ($this->get_deleted() || $this->discussion->is_deleted()) {
            $whynot = 'alert_notcurrentpost';
            return false;
        }

        // If not site level or forum level reporting email has been set
        if (!$this->get_forum()->has_reporting_email()) {
            $whynot = 'alert_turnedoff';
            return false;
        }
        return true;
    }

    /**
     * @param string &$whynot
     * @return bool True if can display the direct link
     */
    function can_showdirectlink() {
        // Check if the post has been deleted
        if ($this->get_deleted() || $this->discussion->is_deleted()) {
            return false;
        }
        return true;
    }

    /**
     * Checks whether the user can delete the post, assuming that they can
     * view the discussion.
     * @param string &$whynot If returning false, set to the language string defining
     *   reason for not being able to edit
     * @param int $userid User ID or 0 if current
     * @return bool True if user can edit this post
     */
    function can_undelete(&$whynot, $userid=0) {
        global $CFG;
        $userid = forum_utils::get_real_userid($userid);
        $context = $this->get_forum()->get_context();

        // Check if post is a special case
        if ($this->is_old_version() || $this->get_discussion()->is_deleted()) {
            $whynot = 'edit_notcurrentpost';
            return false;
        }

        // Check if discussion is different group
        if (!$this->get_discussion()->can_write_to_group()) {
            $whynot = 'edit_wronggroup';
            return false;
        }

        // Check if discussion is locked
        if ($this->get_discussion()->is_locked()) {
            $whynot = 'edit_locked';
            return false;
        }

        if (!$this->get_deleted()) {
            $whynot = 'edit_notdeleted';
            return false;
        }

        // Check the 'edit any' capability (always required to undelete)
        if (!has_capability('mod/forumng:editanypost',
            $context, $userid)) {
            $whynot = 'edit_nopermission';
            return false;
        }

        // Check read-only dates
        if ($this->get_forum()->is_read_only($userid)) {
            $whynot = 'edit_readonly';
            return false;
        }

        // OK! They're allowed to undelete (whew)
        $whynot = '';
        return true;
    }

    /**
     * Checks whether the user can delete the post, assuming that they can
     * view the discussion.
     * @param string &$whynot If returning false, set to the language string defining
     *   reason for not being able to edit
     * @param int $userid User ID or 0 if current
     * @return bool True if user can edit this post
     */
    function can_delete(&$whynot, $userid=0) {
        // At present the logic for this is identical to the edit logic
        // except that you can't delete the root post
        return !$this->is_root_post() && $this->can_edit($whynot, $userid);
    }

    /**
     * Checks whether the user can view edits to posts.
     * @param string $whynot If returning false, set to the language string
     *   defining reason for not being able to view edits
     * @param int $userid User ID or 0 for current
     * @return bool True if user can view edits
     */
    function can_view_history(&$whynot, $userid=0) {
        // Check the 'edit any' capability
        if (!has_capability('mod/forumng:editanypost',
            $this->get_forum()->get_context(), $userid)) {
            $whynot = 'edit_nopermission';
            return false;
        }

        return true;
    }

    /**
     * Checks whether the user can edit the post, assuming that they can
     * view the discussion.
     * @param string &$whynot If returning false, set to the language string defining
     *   reason for not being able to edit
     * @param int $userid User ID or 0 if current
     * @return bool True if user can edit this post
     */
    function can_edit(&$whynot, $userid=0) {
        global $CFG;
        $userid = forum_utils::get_real_userid($userid);
        $context = $this->get_forum()->get_context();

        // Check if post is a special case
        if ($this->get_deleted() || $this->is_old_version()
            || $this->get_discussion()->is_deleted()) {
            $whynot = 'edit_notcurrentpost';
            return false;
        }

        // Check if discussion is different group
        if (!$this->get_discussion()->can_write_to_group()) {
            $whynot = 'edit_wronggroup';
            return false;
        }

        // Check if discussion is locked
        if ($this->get_discussion()->is_locked()) {
            $whynot = 'edit_locked';
            return false;
        }

        // Check the 'edit any' capability
        $editanypost = has_capability('mod/forumng:editanypost',
            $context, $userid);
        if (!$editanypost) {
            // If they don't have edit any, they must have either the
            // 'start discussion' or 'reply post' capability (the same
            // one they needed to create the post in the first place)
            if(($this->is_root_post() &&
                !has_capability('mod/forumng:startdiscussion', $context, $userid))
                && (!$this->is_root_post() &&
                !has_capability('mod/forumng:replypost', $context, $userid))) {
                $whynot = 'edit_nopermission';
                return false;
            }
        }

        // Check post belongs to specified user
        if (($this->get_user()->id != $userid) && !$editanypost) {
            $whynot = 'edit_notyours';
            return false;
        }

        // Check editing timeout
        if ((time() > $this->get_edit_time_limit()) && !$editanypost) {
            $whynot = 'edit_timeout';
            return false;
        }

        // Check read-only dates
        if ($this->get_forum()->is_read_only($userid)) {
            $whynot = 'edit_readonly';
            return false;
        }

        // OK! They're allowed to edit (whew)
        $whynot = '';
        return true;
    }

    /**
     * @param int $userid User ID or 0 for current
     * @return True if user can ignore the post editing time limit
     */
    function can_ignore_edit_time_limit($userid=0) {
        $context = $this->get_forum()->get_context();
        return has_capability('mod/forumng:editanypost',
            $context, $userid);
    }

    /**
     * @return int Time limit after which users who don't have the edit-all
     *   permission are not allowed to edit this post (as epoch value)
     */
    function get_edit_time_limit() {
        global $CFG;
        return $this->get_created() + $CFG->maxeditingtime;
    }

    /**
     * Checks that the user can edit this post - requiring all higher-level
     * access too.
     */
    function require_edit() {
        // Check forum and discussion view permission, group access, etc.
        $this->discussion->require_view();

        // Check post edit
        $whynot = '';
        if (!$this->can_edit($whynot)) {
            print_error($whynot, 'forumng',
              'discuss.php?d=' . $this->discussion->get_id());
        }
    }

    /**
     * Checks that the user can reply to this post - requiring all higher-level
     * access too.
     */
    function require_reply() {
        // Check forum and discussion view permission, group access, etc.
        $this->discussion->require_view();

        // Check post reply
        $whynot = '';
        if (!$this->can_reply($whynot)) {
            print_error($whynot, 'forumng',
              'discuss.php?d=' . $this->discussion->get_id());
        }
    }


    // Email
    ////////

    /**
     * Obtains a version of this post as an email.
     * @param forum_post $inreplyto Message this one's replying to, or null
     *   if none
     * @param string &$subject Output: Message subject
     * @param string $text Output: Message plain text
     * @param string $html Output: Message HTML (or blank if not in HTML mode)
     * @param bool $ishtml True if in HTML mode
     * @param bool $canreply True if user can reply
     * @param bool $viewfullnames True if user gets to see full names even when
     *   these are normally hidden
     * @param string $lang Language of receiving user
     * @param number $timezone Time zone of receiving user
     * @param bool $digest True if in digest mode (does not include parent
     *   message or surrounding links).
     * @param bool $discussionemail True if digest is of a single disussion;
     *   includes 'post 1' information
     */
    function build_email($inreplyto, &$subject, &$text, &$html,
        $ishtml, $canreply, $viewfullnames, $lang, $timezone, $digest=false,
        $discussionemail=false, $showuserimage=true, $printableversion=false) {
        global $CFG, $USER;

        $oldlang = $USER->lang;
        $USER->lang = $lang;

        $forum = $this->get_forum();
        $cmid = $forum->get_course_module_id();
        $course = $forum->get_course();
        $discussion = $this->get_discussion();

        // Get subject (may make DB query, unfortunately)
        $subject = $course->shortname . ': ' .
            format_string($this->get_effective_subject(true), true);

        $canunsubscribe = forum::SUBSCRIPTION_FORCED !=
            $forum->get_effective_subscription_option();

        // Header
        $text = '';
        if (!$discussionemail && !$digest) {
            $html = '<head>';
            foreach ($CFG->stylesheets as $stylesheet) {
                $html .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
            }
            $html .= '</head>';
            $html .= "\n<body id='forumng-email'>\n\n";
        }

        // Navigation bar (breadcrumbs)
        if (!$digest) {
            $text .= $forum->get_course()->shortname . ' -> ';
            $html .= "<div class='forumng-email-navbar'><a target='_blank' " .
              "href='$CFG->wwwroot/course/view.php?id=$course->id'>" .
              "$course->shortname</a> &raquo; ";

            // For consistency, don't show the word 'Forums' if it is hidden
            // (from students) in site breadcrumbs too
            if (!$CFG->hideactivitytypenavlink) {
                $strforums = get_string('forums', 'forumng');
                $text .=  $strforums . ' -> ';
                $html .= "<a target='_blank' " .
                    "href='$CFG->wwwroot/mod/forumng/index.php?id=$course->id'>" .
                    "$strforums</a> &raquo; ";
            }

            $text .= format_string($forum->get_name(), true);
            $html .= "<a target='_blank' " .
                "href='$CFG->wwwroot/mod/forumng/view.php?id=$cmid'>" .
                format_string($forum->get_name(), true) . '</a>';

            // Makes a query :(
            if($discussionsubject = $discussion->get_subject(true)) {
                $text .= ' -> ' . format_string($discussionsubject, true);
                $html .= " &raquo; <a target='_blank' " .
                    "href='$CFG->wwwroot/mod/forumng/discuss.php?d=" .
                    $discussion->get_id() . "'>" .
                    format_string($discussionsubject, true).'</a>';
            }

            $html .= '</div>';
        }
        $text .= "\n" . forum_cron::EMAIL_DIVIDER;

        // Main part of email
        $options = array(
            self::OPTION_EMAIL => true,
            self::OPTION_DIGEST => $digest ? true : false,
            self::OPTION_COMMAND_REPLY => ($canreply && !$digest),
            self::OPTION_VIEW_FULL_NAMES => $viewfullnames ? true : false,
            self::OPTION_TIME_ZONE => $timezone,
            self::OPTION_VISIBLE_POST_NUMBERS => $discussionemail,
            self::OPTION_USER_IMAGE => $showuserimage,
            self::OPTION_PRINTABLE_VERSION => $printableversion);
        $html .= $this->display(true, $options);
        $text .= $this->display(false, $options);

        // Now we need to display the parent post (if any, and if not in digest)
        if ($this->postfields->parentpostid && !$digest) {
            // Print the 'In reply to' heading
            $html .= '<h2>' . get_string('inreplyto', 'forumng') . '</h2>';

            $text .= "\n" . forum_cron::EMAIL_DIVIDER;
            $text .= get_string('inreplyto', 'forumng'). ":\n\n";

            // Get parent post (unfortunately this requires extra queries)
            $parent = forum_post::get_from_id(
                $this->postfields->parentpostid, false);

            $options = array(
                self::OPTION_EMAIL => true,
                self::OPTION_NO_COMMANDS => true,
                self::OPTION_TIME_ZONE => $timezone);
            $html .= $parent->display(true, $options);
            $text .= $parent->display(true, $options);
        }

        if (!$digest && $canunsubscribe) {
            $text .= "\n" . forum_cron::EMAIL_DIVIDER;
            $text .= get_string("unsubscribe", "forum");
            $text .= ": $CFG->wwwroot/mod/forumng/subscribe.php?id=$cmid\n";

            $html .= "<hr size='1' noshade='noshade' />" .
                "<div class='forumng-email-unsubscribe'>" .
                "<a href='$CFG->wwwroot/mod/forumng/subscribe.php?id=$cmid'>" .
                get_string('unsubscribe', 'forumng'). '</a></div>';
        }
        
        if (!$digest && !$discussionemail) {
            $html .= '</body>';
        }

        $USER->lang = $oldlang;

        // If not in HTML mode, chuck away the HTML version
        if (!$ishtml) {
            $html = '';
        }
    }

    // UI
    /////

    /**
     * Displays this post.
     * @param array $html True for HTML format, false for plain text
     * @param array $options See forum_type::display_post for details
     * @return string HTML or text of post
     */
    function display($html, $options=null) {
        global $CFG, $USER;

        // Initialise options array
        if (!is_array($options)) {
            $options = array();
        }
        // Default for other options
        if(!array_key_exists(self::OPTION_EMAIL, $options)) {
            $options[self::OPTION_EMAIL] = false;
        }
        if(!array_key_exists(self::OPTION_EXPORT, $options)) {
            $options[self::OPTION_EXPORT] = false;
        }
        if(!array_key_exists(self::OPTION_DIGEST, $options)) {
            $options[self::OPTION_DIGEST] = false;
        }
        if(!array_key_exists(self::OPTION_NO_COMMANDS, $options)) {
            $options[self::OPTION_NO_COMMANDS] = $options[self::OPTION_EXPORT];
        }
        if(!array_key_exists(self::OPTION_COMMAND_REPLY, $options)) {
            $options[self::OPTION_COMMAND_REPLY] =
                !$options[self::OPTION_NO_COMMANDS] && $this->can_reply($junk);
        }
        if(!array_key_exists(self::OPTION_COMMAND_EDIT, $options)) {
            $options[self::OPTION_COMMAND_EDIT] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_edit($junk);
        }
        if(!array_key_exists(self::OPTION_COMMAND_DELETE, $options)) {
            $options[self::OPTION_COMMAND_DELETE] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_delete($junk);
        }
        if(!array_key_exists(self::OPTION_COMMAND_REPORT, $options)) {
            $options[self::OPTION_COMMAND_REPORT] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_alert($junk);
        }
        if(!array_key_exists(self::OPTION_COMMAND_DIRECTLINK, $options)) {
            $options[self::OPTION_COMMAND_DIRECTLINK] =
                !$options[self::OPTION_NO_COMMANDS] && !$options[self::OPTION_EMAIL] && $this->can_showdirectlink();
        }
        if(!array_key_exists(self::OPTION_COMMAND_UNDELETE, $options)) {
            $options[self::OPTION_COMMAND_UNDELETE] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_undelete($junk);
        }
        if(!array_key_exists(self::OPTION_COMMAND_SPLIT, $options)) {
            $options[self::OPTION_COMMAND_SPLIT] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_split($junk);
        }
        if(!array_key_exists(self::OPTION_COMMAND_HISTORY, $options)) {
            $options[self::OPTION_COMMAND_HISTORY] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_view_history($junk);
        }
        if (!array_key_exists(self::OPTION_READ_TIME, $options)) {
            $options[self::OPTION_READ_TIME] = time();
        }
        if(!array_key_exists(self::OPTION_VIEW_FULL_NAMES, $options)) {
            // Default to whether current user has the permission in context
            $options[self::OPTION_VIEW_FULL_NAMES] = has_capability(
                'moodle/site:viewfullnames', $this->get_forum()->get_context());
        }
        if(!array_key_exists(self::OPTION_TIME_ZONE, $options)) {
            // Default to current user timezone
            $options[self::OPTION_TIME_ZONE] = $USER->timezone;
        }
        if(!array_key_exists(self::OPTION_RATINGS_EDIT, $options)) {
            $options[self::OPTION_RATINGS_EDIT] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_rate();
        }
        if(!array_key_exists(self::OPTION_EXPANDED, $options)) {
            $options[self::OPTION_EXPANDED] = true;
        }
        if (!array_key_exists(self::OPTION_FLAG_CONTROL, $options)) {
            $options[self::OPTION_FLAG_CONTROL] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_flag() &&
                $options[self::OPTION_EXPANDED];
        }
        if(!array_key_exists(self::OPTION_VIEW_DELETED_INFO, $options)) {
            $options[self::OPTION_VIEW_DELETED_INFO] =
                $this->can_undelete($junk) && !$options[self::OPTION_EXPORT];
        }
        if(!array_key_exists(self::OPTION_FULL_ADDRESSES, $options)) {
            $options[self::OPTION_FULL_ADDRESSES] =
                $options[self::OPTION_EXPORT] || $options[self::OPTION_EMAIL];
        }
        if (!array_key_exists(self::OPTION_DISCUSSION_SUBJECT, $options)) {
            $options[self::OPTION_DISCUSSION_SUBJECT] = false;
        }
        if (!array_key_exists(self::OPTION_SELECTABLE, $options)) {
            $options[self::OPTION_SELECTABLE] = false;
        }
        if (!array_key_exists(self::OPTION_VISIBLE_POST_NUMBERS, $options)) {
            $options[self::OPTION_VISIBLE_POST_NUMBERS] = false;
        }
        if (!array_key_exists(self::OPTION_USER_IMAGE, $options)) {
            $options[self::OPTION_USER_IMAGE] = true;
        }
        if (!array_key_exists(self::OPTION_PRINTABLE_VERSION, $options)) {
            $options[self::OPTION_PRINTABLE_VERSION] = false;
        }
        if(!array_key_exists(self::OPTION_RATINGS_VIEW, $options)) {
            $options[self::OPTION_RATINGS_VIEW] =
                ((!$options[self::OPTION_NO_COMMANDS] && !$options[self::OPTION_EMAIL]) || 
                    $options[self::OPTION_PRINTABLE_VERSION]) && 
                    $this->can_view_ratings();
        }
        if (!array_key_exists(self::OPTION_JUMP_NEXT, $options)) {
            $options[self::OPTION_JUMP_NEXT] = 
                (!$options[self::OPTION_NO_COMMANDS] && !$options[self::OPTION_EMAIL] &&
                    $this->is_unread() && ($next=$this->get_next_unread())) 
                ? $next->get_id() : null;
        }
        if (!array_key_exists(self::OPTION_JUMP_PREVIOUS, $options)) {
            $options[self::OPTION_JUMP_PREVIOUS] = 
                (!$options[self::OPTION_NO_COMMANDS] && !$options[self::OPTION_EMAIL] &&
                    $this->is_unread() && $this->get_previous_unread()) 
                ? $this->get_previous_unread()->get_id() : null;
        }
        if (!array_key_exists(self::OPTION_JUMP_PARENT, $options)) {
            $options[self::OPTION_JUMP_PARENT] = 
                (!$options[self::OPTION_NO_COMMANDS] && !$options[self::OPTION_EMAIL] &&
                    !$this->is_root_post()) 
                ? $this->get_parent()->get_id() : null;
        }
        if (!array_key_exists(self::OPTION_FIRST_UNREAD, $options)) {
            $options[self::OPTION_FIRST_UNREAD] = !$options[self::OPTION_EMAIL] &&
                $this->is_unread() && !$this->get_previous_unread();
        }

        // Get forum type to do actual display
        return $this->get_forum()->get_type()->display_post(
            $this, $html, $options);
   }

   function display_with_children($options = null, $recursing = false) {
        global $CFG, $USER;
        $this->require_children();

        if (!$recursing) {
            // Initialise options array
            if (!is_array($options)) {
                $options = array();
            }
            if(!array_key_exists(self::OPTION_EXPORT, $options)) {
                $options[self::OPTION_EXPORT] = false;
            }
            if (!array_key_exists(self::OPTION_CHILDREN_EXPANDED, $options)) {
                $options[self::OPTION_CHILDREN_EXPANDED] =
                    $options[self::OPTION_EXPORT];
            }
            if (!array_key_exists(self::OPTION_CHILDREN_COLLAPSED, $options)) {
                $options[self::OPTION_CHILDREN_COLLAPSED] = false;
            }
            if (!array_key_exists(self::OPTION_INCLUDE_LOCK, $options)) {
                $options[self::OPTION_INCLUDE_LOCK] = false;
            }
        }

        $export = $options[self::OPTION_EXPORT];

        // Decide ID of locked post to hide (if any)
        if ($this->discussion->is_locked() &&
            !$options[self::OPTION_INCLUDE_LOCK]) {
            $lockpostid = $this->discussion->get_last_post_id();
        } else {
            $lockpostid = 0;
        }

        // Display this post. It should be 'short' unless it is unread, parent
        // of unread post, top post, or flagged
        $options[self::OPTION_EXPANDED] = !$recursing || 
            ( !$options[self::OPTION_CHILDREN_COLLAPSED] && 
                ($this->is_unread()
                || $this->is_flagged()
                || $this->has_unread_child() || $this->forceexpand || !$recursing
                || $options[self::OPTION_CHILDREN_EXPANDED]));

        $output = $this->display(true, $options);

        // Are there any children?
        if (count($this->children) > 0 && !($lockpostid
            && count($this->children)==1
            && reset($this->children)->get_id()==$lockpostid)) {
            $output .= $export ? '<blockquote>' : '<div class="forumng-replies">';
            foreach ($this->children as $child) {
                if ($child->get_id()!=$lockpostid) {
                    $output .= $child->display_with_children($options, true);
                }
            }
            $output .= $export ? '</blockquote>' : '</div>';
        }

        if (!$recursing) {
            $output = $this->get_forum()->get_type()->display_post_group(
                $this->get_discussion(), $output);
        }

        return $output;
   }

   /** @return string User picture HTML (for post author) */
   function display_user_picture() {
       return print_user_picture($this->get_user(),
            $this->get_forum()->get_course_id(), null, 0, true);
   }

   /**
    * Displays group pictures. This may make a (single) DB query if group
    * data has not yet been retrieved for this discussion.
    * @return string Group pictures HTML (empty string if none) for groups
    * that post author belongs to
    */
   function display_group_pictures() {
        $groups = $this->discussion->get_user_groups($this->get_user()->id);
        if (count($groups) == 0) {
            return '';
        }
        return print_group_picture($groups, $this->get_forum()->get_course_id(),
            false, true);
   }

    /**
     * Displays this draft as an item on the list.
     * @param bool $last True if this is last in list
     * @return string HTML code for the item
     */
    public function display_flagged_list_item($last) {
        return $this->get_forum()->get_type()->display_flagged_list_item(
            $this, $last);
    }

    /**
     * Describes the post fields in JSON format. This is used for the AJAX
     * edit code.
     * @return string JSON structure listing key post fields.
     */
    function get_json_format() {
       // Attachments first (array)
       $result = '';
       foreach ($this->get_attachment_names() as $attachment) {
           if ($result !== '') {
               $result .= ',';
           }
           $result .= '"' . addslashes_js($attachment) . '"';
       }
       $result = 'var postdata = {attachments:[' . $result . ']';

       $basicvalues = array('subject'=>$this->get_subject(),
           'message'=>$this->get_message(), 'format'=>$this->get_format(), 'setimportant'=>$this->is_important() ? 1 : 0);
       $timelimit = $this->can_ignore_edit_time_limit() 
           ? 0 : $this->get_edit_time_limit();
       if ($timelimit) {
           $basicvalues['editlimit'] = $timelimit-time();
           $basicvalues['editlimitmsg'] = get_string('editlimited', 'forumng',
                userdate($timelimit-30,
                    get_string('strftimetime', 'langconfig')));
       } else {
           $basicvalues['editlimit'] = 0;
       }

       foreach ($basicvalues as $key=>$value) {
           $result .= ',' . $key . ':"' . addslashes_js($value) . '"';
       }
       $result .= '};';
       return $result;
   }

    /**
     * Prints AJAX version of the post to output, and exits.
     * @param mixed $postorid Post object or ID of post
     * @param array $options Post options if any
     * @param int $postid ID of post
     */
    public static function print_for_ajax_and_exit($postorid,
        $options=array()) {
        if (is_object($postorid)) {
            $post = $postorid;
        } else {
            $post = forum_post::get_from_id($postorid, true);
        }
        header('Content-Type: text/plain');
        print trim($post->display(true, $options));
        exit;
    }

    // Completion
    /////////////

    public function update_completion($positive) {
        // Do nothing if completion isn't enabled
        if (!$this->get_forum()->is_auto_completion_enabled(true)) {
            return;
        }
        $course = $this->get_forum()->get_course(); 
        $cm = $this->get_forum()->get_course_module();
        completion_update_state($course, $cm,
            $positive ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE,
            $this->postfields->userid);
    }
}
?>
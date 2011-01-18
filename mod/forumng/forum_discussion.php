<?php
/**
 * Represents a forum discussion.
 * @see forum_discussion_list
 * @see forum
 * @see forum_post
 * @package forumng
 * @author sam marshall
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @copyright Copyright 2009 The Open University
 */
class forum_discussion {
    /** Posts are cached for 10 minutes */
    const CACHE_TIMEOUT = 600;

    /** Max number of discussions to cache in session */
    const CACHE_COUNT = 5;

    /** Used for edit_settings when not changing a value */
    const NOCHANGE = -999;

    /** 
     * Used in the numreadposts field to indicate that read information is not
     * stored because a discussion is too old.
     */
    const PAST_SELL_BY = 1000000;

    // Object variables and accessors
    /////////////////////////////////

    private $forum, $discussionfields, $full, $rootpost, $timeretrieved,
        $pretendtimeread, $foruserid;

    private $postscache, $groupscache, $incache;

    private $ismakingsearchchange;

    /** @return forum The forum that this discussion comes from */
    public function get_forum() { return $this->forum; }

    /** @return object Moodle course object */
    public function get_course() { return $this->forum->get_course(); }

    /** @return object Moodle course-module object */
    public function get_course_module() { return $this->forum->get_course_module(); }

    /** @return int ID of this discussion */
    public function get_id() {
        return $this->discussionfields->id;
    }
    /** @return int Group ID for this discussion or null if any group */
    public function get_group_id() {
        return $this->discussionfields->groupid;
    }
    /** @return int Group name for this discussion */
    public function get_group_name() {
        if (is_null($this->discussionfields->groupid)) {
            return get_string('allparticipants');
        } else {
            return $this->discussionfields->groupname;
        }
    }
    /**
     * Obtains subject. Note this results in a DB query if the discussion
     * was not fully loaded in the first place.
     * @param bool $expectingquery True if code expects there to be a query;
     *   this just avoids a debugging() call.
     * @return string Subject or null if none
     */
    public function get_subject($expectingquery = false) {
        if(!isset($this->discussionfields->subject)) {
            if(!$expectingquery) {
                debugging('This get method made a DB query; if this is expected,
                    set the flag to say so', DEBUG_DEVELOPER);
            }
            $this->discussionfields->subject = forum_utils::get_field(
              'forumng_posts', 'subject',
              'id', $this->discussionfields->postid);
        }
        return $this->discussionfields->subject;
    }

    /**
     * For use only by forum_post when updating in-memory representation
     * after an edit.
     * @param string $subject New subject
     */
    function hack_subject($subject) {
        $this->discussionfields->subject = $subject;
    }

    /** @return bool True if discussion is 'sticky' */
    public function is_sticky() {
        return $this->discussionfields->sticky ? true : false;
    }

    /** @return bool True if discussion is locked */
    public function is_locked() {
        return $this->discussionfields->locked ? true : false;
    }

    /**
     * @return int Time this discussion becomes visible (seconds since epoch)
     *  or null if no start time
     */
    public function get_time_start() {
        return $this->discussionfields->timestart;
    }

    /**
     * @return int Time this discussion stops being visible (seconds since
     *  epoch) or null if no end time
     */
    public function get_time_end() {
        return $this->discussionfields->timeend;
    }

    /**
     * Obtains details of user who originally posted this discussion.
     * @return object Moodle user object (selected fields)
     */
    public function get_poster() {
        $this->check_full();
        return $this->discussionfields->firstuser;
    }

    /**
     * Obtains details of user who posted the last reply to this discussion.
     * @return object Moodle user object (selected fields)
     */
    public function get_last_post_user() {
        $this->check_full();
        return $this->discussionfields->lastuser;
    }

    /**
     * Obtains ID of last post
     * @return int ID of last post
     */
    public function get_last_post_id() {
        return $this->discussionfields->lastpostid;
    }

    /**
     * If the discussion is locked, this function returns the explanatory post.
     * Will retrieve discussion posts if not already obtained.
     * @return forum_post Lock post or null if none
     */
    public function get_lock_post() {
        if ($this->is_locked()) {
            return $this->get_root_post()->find_child(
                $this->discussionfields->lastpostid);
        } else {
            return null;
        }

    }

    /**
     * Checks that the discussion is fully loaded. There are two load states: full
     * (includes all data retrieved when loading discussion list) and partial
     * (includes only minimal data required when creating discussion). Note that
     * full data state does not imply that the actual posts are in memory yet,
     * post storage is tracked separately.
     * @throws forum_exception If discussion is not loaded
     */
    private function check_full() {
        if(!$this->full) {
            throw new forum_exception('This function is not available unless
              the discussion has been fully loaded.');
        }
    }

    /**
     * @param int $courseid Course ID; if not specified, uses actual course id
     * @param int $forumid Forum ID; if not specified, uses actual forum id
     * @param int $discussionid Discussion ID; if not specified, uses this
     *   discussion id (NOTE: If this parameter is provided, you can call this
     *   function statically)
     * @return string Path of folder used for post folders containing
     *   attachments within this discussion */
    function get_attachment_folder($courseid=0, $forumid=0, $discussionid=0) {
        global $CFG;
        if (!$courseid) {
            $courseid = $this->get_forum()->get_course_id();
        }
        if (!$forumid) {
            $forumid = $this->get_forum()->get_id();
        }
        if (!$discussionid) {
            $discussionid = $this->get_id();
        }
        return
            $CFG->dataroot . '/' . $courseid . '/moddata/forumng/' .
            $forumid . '/' . $discussionid;
    }

    /**
     * @return string URL of this discussion for log table, relative to the
     *   module's URL
     */
    function get_log_url() {
        return 'discuss.php?' . $this->get_link_params(forum::PARAM_PLAIN);
    }

    /**
     * @return mixed Number of unread posts as integer, possibly 0; or empty 
     *   string if unread data is no longer tracked for this post
     */
    public function get_num_unread_posts() {
        if(!isset($this->discussionfields->numreadposts)) {
            throw new forum_exception('Unread post count not obtained');
        }
        if ($this->discussionfields->numreadposts == self::PAST_SELL_BY) {
            return '';
        } else {
            return $this->discussionfields->numposts
                - $this->discussionfields->numreadposts;
        }
    }

    /**
     * @return int Number of discussions
     */
    public function get_num_posts() {
        if(!isset($this->discussionfields->numposts)) {
            throw new forum_exception('Post count not obtained');
        }
        return $this->discussionfields->numposts;
    }

    /**
     * @return int Time of last post
     */
    public function get_time_modified() {
        if(!isset($this->discussionfields->timemodified)) {
            throw new forum_exception('Time modified not obtained');
        }
        return $this->discussionfields->timemodified;
    }

    /**
     * @return string URL of this discussion
     */
    public function get_url($type = forum::PARAM_PLAIN) {
        global $CFG;
        return $CFG->wwwroot . '/mod/forumng/discuss.php?' .
                $this->get_link_params($type);
    }

    // Factory method
    /////////////////

    /**
     * Creates a forum discussion object, forum object, and all related data from a
     * single forum discussion ID. Intended when entering a page which uses
     * discussion ID as a parameter.
     * @param int $id ID of forum discussion
     * @param int $cloneid ID of clone (or 0 or forum::CLONE_DIRECT as relevant)
     * @param int $userid User ID; 0 = current user, -1 = do not get unread data
     * @param bool $usecache True if cache should be used (if available)
     * @param bool $storecache True if newly-retrieved discussion should be 
     *   stored to cache
     * @return forum_discussion Discussion object
     */
    public static function get_from_id($id, $cloneid, $userid=0, $usecache=false, $storecache=false) {
        if ($usecache) {
            global $SESSION;
            self::check_cache();
            foreach ($SESSION->forumng_cache->discussions as $info) {
                if ($info->userid==forum_utils::get_real_userid($userid) && $info->id==$id && $info->cloneid==$cloneid) {
                    $info->lastused = time();
                    $result = self::create_from_cache($info);
                    if ($result) {
                        return $result;
                    }
                }
            }
        }
        return self::get_base('fd.id='.$id, $userid, $storecache, $cloneid);
    }

    /**
     * Creates a forum discussion object, forum object, and all related data from a
     * forum post ID (the discussion related to that post). Intended when
     * requesting a post if we want 'context' data too
     * @param int $postid ID of forum post
     * @param int $userid User ID; 0 = current user, -1 = do not get unread data
     * @param bool $usecache True if cache should be used (if available)
     * @param bool $storecache True if newly-retrieved discussion should be 
     *   stored to cache
     * @return forum_discussion Discussion object
     */
    static function get_from_post_id($postid, $cloneid, $userid=0, $usecache=false, $storecache=false) {
        if ($usecache) {
            global $SESSION;
            self::check_cache();
            foreach ($SESSION->forumng_cache->discussions as $info) {
                if ($info->userid!=forum_utils::get_real_userid($userid)) {
                    continue;
                }
                // Check whether this discussion contains the desired
                // post
                if (in_array($postid, $info->posts)) {
                    $info->lastused = time();
                    $result = self::create_from_cache($info);
                    if ($result) {
                        return $result;
                    }
                }
            }
        }

        global $CFG;
        return self::get_base("fd.id =
            (SELECT discussionid FROM {$CFG->prefix}forumng_posts WHERE id=$postid)",
            $userid, $storecache, $cloneid);
    }

    private static function get_base($where, $userid, $cache, $cloneid) {
        // If user isn't logged in, don't get unread data
        if (!isloggedin()) {
            $userid = -1;
        }
        // Get discussion data (including read status)
        $rs = self::query_discussions($where, $userid, 'id', 0, 1);
        if (!($discussionfields = rs_fetch_next_record($rs))) {
            throw new forum_exception('Unable to retrieve relevant discussion');
        }
        rs_close($rs);

        // Get forum and construct discussion
        $forum = forum::get_from_id($discussionfields->forumid, $cloneid);
        $result = new forum_discussion($forum, $discussionfields, true,
            forum_utils::get_real_userid($userid));
        if ($cache) {
            $result->cache($userid);
        }
        return $result;
    }

    // Discussion caching
    /////////////////////

    /**
     * Caches the specified discussion in session.
     * Replaces the least-recently-used, if the number exceeds the
     * limit.
     * @param forum_discussion $discussion
     */
    private function cache() {
        global $SESSION;
        self::check_cache();

        if (!$this->full) {
            // Only cache 'full' data
            return;
        }

        // Remove any existing data for this discussion id
        $oldest = -1;
        $oldesttime = 0;
        foreach ($SESSION->forumng_cache->discussions as $key=>$info) {
            if ($info->id == $this->get_id()) {
                unset($SESSION->forumng_cache->discussions[$key]);
            } else {
                if ($oldest==-1 || $info->lastused<$oldesttime) {
                    $oldest = $key;
                }
            }
        }

        // If there are too many, discard oldest
        if (count($SESSION->forumng_cache->discussions) > self::CACHE_COUNT) {
            unset($SESSION->forumng_cache->discussions[$oldest]);
        }

        // Cache this data
        $info = new stdClass;
        $info->lastused = time();
        $info->id = $this->get_id();
        $info->timemodified = $this->get_time_modified();
        $info->discussionfields = serialize($this->discussionfields);
        $info->postscache = $this->postscache;
        $info->groupscache = serialize($this->groupscache);
        $info->userid = $this->get_unread_data_user_id();
        $info->posts = array();
        $info->settingshash = $this->get_forum()->get_settings_hash();
        $info->cloneid = $this->get_forum()->get_course_module_id();

        if ($this->rootpost) {
            $this->rootpost->list_child_ids($info->posts);
        }

        $this->incache = $info;
        $SESSION->forumng_cache->discussions[] = $info;
    }

    /**
     * Removes any instances of this discussion from current user's cache.
     * Used so that current user sees changes immediately (other users will
     * still wait 10 minutes).
     */
    function uncache() {
        global $SESSION;
        if (isset($SESSION->forumng_cache->discussions)) {
            foreach ($SESSION->forumng_cache->discussions as $key=>$info) {
                if ($info->id == $this->get_id()) {
                    unset($SESSION->forumng_cache->discussions[$key]);
                }
            }
        }
    }

    /**
     * Obtains a discussion from the cache.
     * @param object $info Object from session cache
     * @return forum_discussion New discussion object or null if there is a
     *   problem and you should re-cache
     */
    private static function create_from_cache($info) {
        $discussionfields = unserialize($info->discussionfields);
        $forum = forum::get_from_id($discussionfields->forumid, $info->cloneid);
        if ($forum->get_settings_hash() != $info->settingshash) {
            return null;
        }

        $result = new forum_discussion(
            $forum, $discussionfields, true, $info->userid);

        $result->groupscache = unserialize($info->groupscache);
        $result->postscache = $info->postscache;
        $result->incache = true;
        return $result;
    }

    /**
     * Checks whether the current discussion object is newer (contains
     * newer posts) than an equivalent discussion stored in the cache.
     * If so, removes the cached value.
     */
    function maybe_invalidate_cache() {
        global $SESSION;
        self::check_cache();

        foreach ($SESSION->forumng_cache->discussions as $key=>$info) {
            if ($info->id == $this->get_id()
                && $info->timemodified != $this->get_time_modified()) {
                unset($SESSION->forumng_cache->discussions[$key]);
            }
        }
    }

    /**
     * Updates the discussion cache, discarding old data.
     */
    static function check_cache() {
        global $SESSION;

        // Check cache variable exists
        if (!isset($SESSION->forumng_cache)) {
            $SESSION->forumng_cache = new stdClass;
        }
        if (!isset($SESSION->forumng_cache->discussions)) {
            $SESSION->forumng_cache->discussions = array();
        }

        // Remove old cache data
        foreach ($SESSION->forumng_cache->discussions as $key=>$info) {
            if (time() - $info->lastused > self::CACHE_TIMEOUT) {
                unset($SESSION->forumng_cache->discussions[$key]);
            }
        }
    }

    // Object methods
    /////////////////

    /**
     * Initialises the discussion. Used internally by forum - don't call directly.
     * @param forum $forum Forum object
     * @param object $discussionfields Discussion fields from db table (plus
     *   some extra fields provided by query in forum method)
     * @param bool $full True if the parameter includes 'full' data via the
     *   various joins, false if it's only the fields from the discussions table.
     * @param int $foruserid The user ID that was used to obtain the discussion
     *   data (may be -1 for no unread data)
     */
    function __construct($forum, $discussionfields, $full, $foruserid) {
        if($full && !isset($discussionfields->firstuser)) {
            // Extract the user details into Moodle user-like objects
            $discussionfields->firstuser = forum_utils::extract_subobject($discussionfields, 'fu_');
            $discussionfields->lastuser = forum_utils::extract_subobject($discussionfields, 'lu_');
        }

        $this->forum = $forum;
        $this->discussionfields = $discussionfields;
        $this->full = $full;
        $this->foruserid = $foruserid;
        $this->rootpost = null;
        $this->timeretrieved = time();
        $this->postscache = null;
        $this->groupscache = null;
        $this->ismakingsearchchange = false;
    }

    /**
     * Fills discussion data (loaded from db) for given user.
     * @param int $foruserid User ID or -1 if no unread data is required
     * @param bool $usecache True to use cache if available
     * @param bool $storecache True to sstore retrieved value in cache
     */
    public function fill($foruserid=0, $usecache=false, $storecache=false) {
        if($this->full && ($this->foruserid == $foruserid || $foruserid==-1)) {
            return;
        }
        $new = self::get_from_id($this->discussionfields->id, $foruserid,
            $usecache, $storecache);
        foreach(get_class_vars('forum_discussion') as $field=>$dontcare) {
            $this->{$field} = $new->{$field};
        }
    }

    /**
     * Obtains the root post of the discussion. This actually requests all
     * posts from the database; the first is returned, but others are
     * accessible from methods in the first.
     * If available, cached information is used unless
     * you set $usecache to false. The cache is stored within the discussion
     * object so will not persist beyond a request unless you make the
     * discussion object persist too.
     * @param bool $usecache True to use cache if available, false to
     *    request fresh data
     * @param int $userid User ID to get user-specific data (initially, post
     *   flags) for; 0 = current
     * @return forum_post Post object
     */
    function get_root_post($usecache=true, $userid=0) {
        if(!$usecache || !$this->rootpost) {
            global $CFG;

            if (!$usecache || !$this->postscache) {
                // Retrieve most posts in the discussion - even deleted
                // ones. These are necessary in case somebody deletes a post that has
                // replies. They will display as 'deleted post'. We don't retrieve
                // old versions of edited posts. Posts are retrieved in created order
                // so that the order of replies remains constant when we build the tree.
                $posts = forum_post::query_posts('fp.discussionid='.$this->discussionfields->id.
                    ' AND fp.oldversion=0', 'fp.created',
                    $this->forum->has_ratings(), true, false, $userid);
                $this->postscache = serialize($posts);
            } else {
                $posts = unserialize($this->postscache);
            }

            // Add numbers to posts
            $i = 1;
            foreach ($posts as $post) {
                $post->number = $i++;
            }

            // Obtain post relationships
            $children = array();
            foreach($posts as $id=>$fields) {
                if(!array_key_exists($fields->parentpostid, $children)) {
                    $children[$fields->parentpostid] = array();
                }
                $children[$fields->parentpostid][] = $id;
            }

            // Recursively build posts
            $this->rootpost = $this->build_posts($posts, $children,
                $this->discussionfields->postid, null);

            // Update the 'next/previous' unread lists stored in posts
            if ($this->get_unread_data_user_id() != -1) {
                $linear = array();
                $this->rootpost->build_linear_children($linear);
                $nextunread = array();
                foreach ($linear as $index=>$post) {
                    $nextunread[$index] = null;
                    if ($post->is_unread()) {
                        for ($j = $index-1; $j>=0; $j--) {
                            if ($nextunread[$j]) {
                                break;
                            }
                            $nextunread[$j] = $post;
                        }
                    }
                }
                $previous = null;
                foreach($linear as $index=>$post) {
                    $post->set_unread_list($nextunread[$index], $previous);
                    if ($post->is_unread()) {
                        $previous = $post;
                    }
                }
    
                // Update cached version to include this data
                if ($this->incache) {
                    $this->cache();
                }
            }
        }

        return $this->rootpost;
    }
    
    /**
     * Internal method. Queries for a number of discussions, including additional
     * data about unread posts etc. Returns the database result.
     * @param string $conditions WHERE clause (may refer to aliases 'd' for discussion)
     * @param int $userid User ID, 0 = current user, -1 = no unread data is needed
     * @param string $orderby ORDER BY clause
     * @param int $limitfrom Limit on results
     * @param int $limitnum Limit on results
     * @param bool $orderbyoutside If set, does a sort on the calculated
     *   results rather than inner db fields
     * @return adodb_recordset Database query results
     */
    static function query_discussions($conditions, $userid, $orderby,
        $limitfrom='', $limitnum='') {
        global $CFG, $USER;

        // For read tracking, we get a count of total number of posts in
        // discussion, and total number of read posts in the discussion (this
        // is so we can display the number of UNread posts, but the query
        // works that way around because it will return 0 if no read
        // information is stored).
        if(forum::enabled_read_tracking() && $userid!=-1) {
            if (!$userid) {
                $userid = $USER->id;
            }
            $deadline = forum::get_read_tracking_deadline();
            $readtracking = "
                , (CASE WHEN lp.modified<$deadline THEN " . self::PAST_SELL_BY .
                " ELSE (SELECT COUNT(1)
                    FROM {$CFG->prefix}forumng_posts fp3
                    WHERE fp3.discussionid=fd.id AND fp3.oldversion=0
                    AND fp3.deleted=0
                    AND (fp3.modified<fr.time OR fp3.edituserid=$userid
                        OR (fp3.edituserid IS NULL AND fp3.userid=$userid)
                        OR fp3.modified < $deadline)) END) AS numreadposts,
                fr.time AS timeread";
            $readtrackingjoin = "LEFT JOIN {$CFG->prefix}forumng_read fr
                ON fd.id=fr.discussionid AND fr.userid=$userid";
        } else {
            $readtracking = "";
            $readtrackingjoin = "";
        }

        $order = ($orderby) ? 'ORDER BY ' . $orderby : '';
        
        // Main query. This retrieves:
        // * Basic discussion information.
        // * Information about the discussion that is obtained from the first and
        //   last post.
        // * Information about the users responsible for first and last post.
        $rs = get_recordset_sql("
SELECT * FROM (SELECT
    fd.*,
    fp.created AS timecreated,
    lp.modified AS timemodified,
    fp.subject AS subject,
    lp.subject AS lastsubject,
    lp.message AS lastmessage,
    ".forum_utils::select_username_fields('fu').",
    ".forum_utils::select_username_fields('lu').",
    (SELECT COUNT(1)
        FROM {$CFG->prefix}forumng_posts fp2
        WHERE fp2.discussionid=fd.id AND fp2.deleted=0 AND fp2.oldversion=0)
        AS numposts,
    g.name AS groupname
    $readtracking
FROM
    {$CFG->prefix}forumng_discussions fd
    INNER JOIN {$CFG->prefix}forumng_posts fp ON fd.postid=fp.id
    INNER JOIN {$CFG->prefix}user fu ON fp.userid=fu.id
    INNER JOIN {$CFG->prefix}forumng_posts lp ON fd.lastpostid=lp.id
    INNER JOIN {$CFG->prefix}user lu ON lp.userid=lu.id
    LEFT JOIN {$CFG->prefix}groups g ON g.id=fd.groupid
    $readtrackingjoin
WHERE
    $conditions) x $order
", $limitfrom, $limitnum);
        if(!$rs) {
            throw new forum_exception("Failed to retrieve discussions");
        }
        return $rs;
    }

    /**
     * Constructs a post object and (recursively) all of its children from
     * information retrieved from the database.
     * @param $posts Array of post ID => fields from DB query
     * @param $children Array of post ID => array of child IDs
     * @param $id ID of post to construct
     * @param $parent Parent post or NULL if none
     * @return forum_post Newly-created post
     * @throws forum_exception If ID is invalid
     */
    private function build_posts(&$posts, &$children, $id, $parent) {
        if(!array_key_exists($id, $posts)) {
            $msg = "No such post: $id (discussion " . $this->get_id() . '); ' .
                'posts';
            foreach($posts as $id=>$junk) {
                $msg .= ' ' . $id;
            }
            $msg .= '; children';
            foreach($children as $id=>$junk) {
                $msg .= ' ' . $id;
            }
            throw new forum_exception($msg);
        }
        $post = new forum_post($this, $posts[$id], $parent);
        $post->init_children();

        if(array_key_exists($id, $children)) {
            foreach($children[$id] as $childid) {
                $post->add_child(
                    $this->build_posts($posts, $children, $childid, $post));
            }
        }
        return $post;
    }

    /**
     * Used by forum when creating a discussion. Do not call directly.
     * @param string $subject Subject
     * @param string $message Message
     * @param int $format Moodle format used for message
     * @param array $attachments Array of paths to temporary files of
     *   attachments in post
     * @param bool $mailnow If true, sends mail ASAP
     * @param int $userid User ID (0 = current)
     * @return int ID of newly-created post
     */
    function create_root_post($subject, $message, $format,
        $attachments=array(), $mailnow=false, $userid=0) {
        return $this->create_reply(null, $subject, $message, $format,
            $attachments, false, $mailnow, $userid);
    }

    /**
     * Used by forum_post when creating a reply. Do not call directly.
     * @param forum_post $parentpost Parent post object (NULL when creating root post)
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
     *
     * @return int ID of newly-created post
     */
    function create_reply($parentpost, $subject, $message, $format,
        $attachments=array(), $setimportant=false, $mailnow=false, $userid=0) {
            if($userid==0) {
            global $USER;
            $userid = $USER->id;
            if(!$userid) {
                throw new forum_exception('Cannot determine user ID');
            }
        }

        // Prepare post object
        $postobj = new StdClass;
        $postobj->discussionid = $this->discussionfields->id;
        $postobj->parentpostid = $parentpost ? $parentpost->get_id() : null;
        $postobj->userid = $userid;
        $postobj->created = time();
        $postobj->modified = $postobj->created;
        $postobj->deleted = 0;
        $postobj->mailstate = $mailnow
            ? forum::MAILSTATE_NOW_NOT_MAILED
            : forum::MAILSTATE_NOT_MAILED;
        $postobj->important = $setimportant ? 1 : 0;
        $postobj->oldversion = 0;
        $postobj->edituserid = null;
        $postobj->subject = strlen(trim($subject)) == 0 ? null : addslashes($subject);
        $postobj->message = addslashes($message);
        $postobj->format = $format;
        $postobj->attachments = count($attachments)>0;

        forum_utils::start_transaction();
        try {
            // Create post
            $postobj->id = forum_utils::insert_record('forumng_posts', $postobj);
            $post = new forum_post($this, $postobj);

            // For replies, update last post id
            if ($parentpost) {
                $discussionchange = new stdClass;
                $discussionchange->id = $parentpost->get_discussion()->get_id();
                $discussionchange->lastpostid = $postobj->id;
                forum_utils::update_record('forumng_discussions', $discussionchange);
            }

            // Place attachments
            foreach($attachments as $path) {
                $post->add_attachment($path);
            }

            // Update search index (replies only)
            if (forum::search_installed() && $parentpost) {
                $post->search_update();
            }

            // Update completion state
            $post->update_completion(true);

            // Outside the catch so we don't commit transaction if something
            // fails
            forum_utils::finish_transaction();

            return $post->get_id();
        } catch(Exception $e) {
            // Erase attachments from temp storage if error occurs
            foreach($attachments as $path) {
                unlink($path);
            }
            throw $e;
        }
    }

    /**
     * Used when updating search data for posts. When this function returns
     * true, updating search data will cause it to be deleted. After making
     * the change which affects search, make this function return false again.
     * @return bool True if search data is being changed and posts should
     *   delete their search data
     */
    public function is_making_search_change() {
        return $this->ismakingsearchchange;
    }

    /**
     * Edits discussion settings. These parameters may be set to the NOCHANGE
     * constant if not being altered.
     * @param int $groupid Group ID
     * @param int $timestart Seconds since epoch that this becomes visible,
     *   null/0 if always
     * @param int $timeend Seconds since epoch that this disappear, null/0 if
     *   it doesn't
     * @param bool $locked True if discussion should be locked
     * @param bool $sticky True if discussion should be sticky
     */
    public function edit_settings($groupid, $timestart, $timeend, $locked, $sticky) {
        // Apply defaults
        if ($groupid === self::NOCHANGE) {
            $groupid = $this->discussionfields->groupid;
        }
        if ($timestart === self::NOCHANGE) {
            $timestart = $this->discussionfields->timestart;
        }
        if ($timeend === self::NOCHANGE) {
            $timeend = $this->discussionfields->timeend;
        }
        if ($locked === self::NOCHANGE) {
            $locked = $this->discussionfields->locked;
        }
        if ($sticky === self::NOCHANGE) {
            $sticky = $this->discussionfields->sticky;
        }

        // Normalise entries to match db values
        $timestart = $timestart ? $timestart : 0;
        $timeend = $timeend ? $timeend : 0;
        $locked = $locked ? 1 : 0;
        $sticky = $sticky ? 1 : 0;
        $groupid = $groupid ? $groupid : null;

        // Start transaction in case there are multiple changes relating to
        // search
        forum_utils::start_transaction();

        $update = new StdClass;
        if ($groupid != $this->discussionfields->groupid) {
            $update->groupid = $groupid;

            // When group changes, need to redo the search data; must remove it
            // before changing group or it won't be able to find the old
            // search documents any more (because it looks for them under the
            // new group id).
            $this->ismakingsearchchange = true;
            $root = $this->get_root_post();
            $root->search_update();
            $root->search_update_children();
            $this->ismakingsearchchange = false;
        }
        if ($timestart != $this->discussionfields->timestart) {
            $update->timestart = $timestart;
        }
        if ($timeend != $this->discussionfields->timeend) {
            $update->timeend = $timeend;
        }
        if ($locked != $this->discussionfields->locked) {
            $update->locked = $locked;
        }
        if ($sticky != $this->discussionfields->sticky) {
            $update->sticky = $sticky;
        }
        if (count((array)$update)==0) {
            // No change
            return;
        }
        $update->id = $this->discussionfields->id;
        forum_utils::update_record('forumng_discussions', $update);

        // Update in memory (needed for the next group bit)
        $this->uncache();
        foreach ($update as $key=>$value) {
            $this->discussionfields->{$key} = $value;
        }

        // Update group if required
        if (isset($update->groupid)) {
            // When group has changed, must add items to the new group
            $root = $this->get_root_post();
            $root->search_update();
            $root->search_update_children();
        }

        // End transaction
        forum_utils::finish_transaction();
    }

    /**
     * Moves discussion to another forum. This will also move any attachments
     * in the filesystem. You can also use this method to change group.
     * (Note that once a discussion has been moved its data fields are no longer
     * valid and the object should be discarded.)
     * @param forum $targetforum Target forum for move
     * @param int $targetforumid New forum ID
     * @param int $targetgroupid New group ID
     */
    public function move($targetforum, $targetgroupid) {
        $update = new StdClass;
        if ($targetforum->get_id() != $this->discussionfields->forumid) {
            $update->forumid = $targetforum->get_id();
        }
        if ($targetgroupid != $this->discussionfields->groupid) {
            $update->groupid = $targetgroupid;
        }
        if (count((array)$update) == 0) {
            // No change
            return;
        }
        $update->id = $this->discussionfields->id;

        forum_utils::start_transaction();
        forum_utils::update_record('forumng_discussions', $update);

        if ($targetforum->get_id() != $this->forum->get_id()) {
            // Moving to different forum, we need to move attachments if any.
            $folder = $this->get_attachment_folder();
            if (is_dir($folder)) {
                $targetfolder = $this->get_attachment_folder(
                    $targetforum->get_course_id(), $targetforum->get_id());
                check_dir_exists(dirname($targetfolder), true, true);
                forum_utils::rename($folder, $targetfolder);
            }

            // Completion status may have changed in source and target forums
            // Performance optimise: only do this if completion is enabled
            if ($this->forum->is_auto_completion_enabled()) {
                $this->update_completion(false);
                $newdiscussion = forum_discussion::get_from_id($this->get_id(),
                    $this->get_forum()->get_course_module_id(), -1);
                $newdiscussion->update_completion(true);
            }
        }

        $this->uncache();
        forum_utils::finish_transaction();
    }
    /**
     * Copy the discussion and its  posts to another forum and/or group.
     * @param forum $targetforum Forum to copy the discussion to
     * @param int $groupid If 'All participants' has been selected from the
     *   Separate groups dropdown box, use default value 0
     */
    function copy($targetforum, $groupid) {
        global $SESSION, $CFG;
        $oldforum = $this->get_forum();
        $oldforumid = $oldforum->get_id();
        $oldcourseid = $oldforum->get_course_id();
        $targetforumid = $targetforum->get_id();
        $targetcourseid = $targetforum->get_course_id();
        //Clone the old discussion
        $discussionobj = clone($this->discussionfields);
        unset($discussionobj->id);

        //update the forumid and gruopid to the target forumid and selected groupid
        $discussionobj->forumid = $targetforumid;
        unset($discussionobj->groupid);
        if ($targetforum->get_group_mode() && $groupid) {
            $discussionobj->groupid = $groupid;
        } 
        forum_utils::start_transaction();
        $newdiscussionid =  forum_utils::insert_record('forumng_discussions', $discussionobj);
        $rs = forum_utils::get_recordset(
            'forumng_posts', 'discussionid', $this->get_id());
        //$newids and $parentused are temp arrays used to 
        //$newids is a array of new postids using the indices of its old postids
        //update the parentid of the post records copied over
        //$hasattachments is a temp array for record the posts which has attachments.
        $newids = array();
        $parentsused = array();
        $hasattachments = array();
        while($postrec = rs_fetch_next_record($rs)) {
            $oldpostid = $postrec->id;
            unset($postrec->id);
            $postrec->discussionid = $newdiscussionid;
            $postrec->mailstate = forum::MAILSTATE_DIGESTED;
            $postrec->subject = addslashes($postrec->subject);
            $postrec->message = addslashes($postrec->message);
            $newpostid = forum_utils::insert_record('forumng_posts', $postrec);
            $newids[$oldpostid] = $newpostid;
            if ($postrec->parentpostid) {
                $parentsused[$postrec->parentpostid] = true;
            }
            if ($postrec->attachments ==1) {
                $hasattachments[$oldpostid] = $newpostid;
            }
        }
        rs_close($rs);
        //Update the postid and lastpostid in the discussion table no matter if they are null or not
        $newpostid = $newids[$discussionobj->postid];
        $newlastpostid = $newids[$discussionobj->lastpostid];
        forum_utils::execute_sql("UPDATE {$CFG->prefix}forumng_discussions SET " .
            "postid=" . $newpostid . ", lastpostid=". $newlastpostid .
            " WHERE id=" . $newdiscussionid);
        foreach ($parentsused as $key=>$value) {
            $newparentpostid = $newids[$key];
            //Update the parentpostids which have just been copied over
            forum_utils::execute_sql("UPDATE {$CFG->prefix}forumng_posts SET " .
                "parentpostid=" . $newparentpostid .
                " WHERE parentpostid=" . $key . "AND discussionid = " . $newdiscussionid);
        }
        //Copy attachments
        foreach ($hasattachments as $key=>$value) {
            $oldfolder = forum_post::get_any_attachment_folder ($oldcourseid, $oldforumid, $this->get_id(), $key);
            $newfolder = forum_post::get_any_attachment_folder ($targetcourseid, $targetforumid, $newdiscussionid, $value);
            $handle = forum_utils::opendir($oldfolder);
            $created = false;
            while (false !== ($name = readdir($handle))) {
                //Get attachment file name one by one instead of using the get_attachment_names() to 
                //avoid creating a post object
                if ($name != '.' && $name != '..') {
                    if (!is_dir("$oldfolder/$name")) {
                        // creating target folders and copying files
                        if (!$created) {
                            if(!check_dir_exists($newfolder, true, true)) {
                                throw new forum_exception("Failed to create attachment folder $newfolder");
                            }
                            $created = true;
                        }
                        forum_utils::copy("$oldfolder/$name", "$newfolder/$name");
                    }
                }
            }
            closedir($handle);
        }
        forum_utils::finish_transaction();
    }

    /**
     * Clones this discussion but changes the post IDs, for internal use
     * only (in split).
     * @param int $postid First post in discussion
     * @param int $lastpostid Last post in discussion
     * @return int New discussion ID
     */
    function clone_for_split($postid, $lastpostid) {
        // Create new discussion
        $discussionobj = clone($this->discussionfields);
        unset($discussionobj->id);
        $discussionobj->postid = $postid;
        $discussionobj->lastpostid = $lastpostid;
        return forum_utils::insert_record('forumng_discussions', $discussionobj);
    }

    /**
     * Deletes this discussion.
     * @param bool $log True to log action
     */
    public function delete($log=true) {
        if($this->discussionfields->deleted) {
            return;
        }
        forum_utils::start_transaction();
        $update = new StdClass;
        $update->id = $this->discussionfields->id;
        $update->deleted = time();
        forum_utils::update_record('forumng_discussions', $update);
        $this->discussionfields->deleted = $update->deleted;

        // Update all the posts to remove them from search
        $this->get_root_post()->search_update();
        $this->get_root_post()->search_update_children();

        // Update completion status in case it needs marking false for anyone
        $this->update_completion(false);

        // Log delete
        if($log) {
            $this->log('delete discussion');
        }
        forum_utils::finish_transaction();

        $this->uncache();
    }

    /**
     * Undeletes this discussion.
     * @param bool $log True to log action
     */
    public function undelete($log=true) {
        if(!$this->discussionfields->deleted) {
            return;
        }
        forum_utils::start_transaction();
        $update = new StdClass;
        $update->id = $this->discussionfields->id;
        $update->deleted = 0;
        forum_utils::update_record('forumng_discussions', $update);
        $this->discussionfields->deleted = 0;

        // Update all the posts to add them back to search
        $this->get_root_post()->search_update();
        $this->get_root_post()->search_update_children();

        // Update completion status in case it needs marking true for anyone
        $this->update_completion(true);

        if($log) {
            $this->log('undelete discussion');
        }
        forum_utils::finish_transaction();

        $this->uncache();
    }

    /**
     * Deletes this discussion and its relevant data permanently. 
     * It can't be undeleted afterwards.
     * @param bool $log True to log action
     */
    public function permanently_delete($log=true) {
        global $CFG;
        forum_utils::start_transaction();

        //Deleting the relevant data in the forumng_subscriptions table
        forum_utils::delete_records('forumng_subscriptions', 'discussionid', $this->get_id());

        //Deleting the relevant data in the forumng_read table
        forum_utils::delete_records('forumng_read', 'discussionid', $this->get_id());

        //Deleting the relevant data in the forumng_ratings table
        $query = "WHERE postid IN (
SELECT fp.id
FROM
    {$CFG->prefix}forumng_posts fp
    INNER JOIN {$CFG->prefix}forumng_discussions fd ON fp.discussionid = fd.id
WHERE
    fd.id = {$this->discussionfields->id}
)";
        forum_utils::execute_sql("DELETE FROM {$CFG->prefix}forumng_ratings $query");

        //Deleting the relevant data in the forumng_flags table
        forum_utils::execute_sql("DELETE FROM {$CFG->prefix}forumng_flags $query");

        //Deleting the relevant posts in this discussion in the forumng_posts table
        forum_utils::delete_records('forumng_posts', 'discussionid', $this->get_id());

        //Finally deleting this discussion in the forumng_discussions table
        forum_utils::delete_records('forumng_discussions', 'id', $this->get_id());

        //Delete the entire attachment folder if any
        $folder = $this->get_attachment_folder();
        if (is_dir($folder)) {
            if (!remove_dir($folder)) {
                throw new forum_exception("Error deleting attachment folder: $folder");
            }
        }
        //Log delete
        if($log) {
            $this->log('permdelete discussion');
        }
        forum_utils::finish_transaction();

        $this->uncache();
    }
    
    /**
     * Locks a discussion with a final message.
     * @param string $subject Subject
     * @param string $message Message
     * @param int $format Moodle format used for message
     * @param array $attachments Array of paths to temporary files of
     *   attachments in post. [Note that these should have already been checked
     *   and renamed by the Moodle upload manager. They will be moved or
     *   deleted by the time this method returns.]
     * @param bool $mailnow If true, sends mail ASAP
     * @param int $userid User ID (0 = current)
     * @param bool $log True to log this action
     * @return int post ID
     */
    public function lock($subject, $message, $format,
        $attachments=array(), $mailnow=false, $userid=0, $log=true) {
        forum_utils::start_transaction();

        // Post reply
        $postid = $this->get_root_post()->reply($subject, $message, $format,
            $attachments, false, $mailnow, $userid, false);

        // Mark discussion locked
        $this->edit_settings(forum_discussion::NOCHANGE,
            forum_discussion::NOCHANGE,forum_discussion::NOCHANGE,
            true,forum_discussion::NOCHANGE);

        // Log
        if ($log) {
            $this->log('lock discussion p' . $postid);
        }

        forum_utils::finish_transaction();
        return $postid;
    }

    /**
     * Unlocks a discussion.
     * @param int $userid User ID (0 = current)
     * @param bool $log True to log this action
     */
    public function unlock($userid=0, $log=true) {
        forum_utils::start_transaction();

        // Delete lock post
        $lockpost = $this->get_lock_post();
        if (!$lockpost) {
            throw new forum_exception('Discussion not locked');
        }
        $lockpost->delete($userid, false);

        // Mark discussion unlocked
        $this->edit_settings(forum_discussion::NOCHANGE,
            forum_discussion::NOCHANGE,forum_discussion::NOCHANGE,
            false,forum_discussion::NOCHANGE);

        // Log
        if ($log) {
            $this->log('unlock discussion p' . $lockpost->get_id());
        }

        forum_utils::finish_transaction();
    }

    /**
     * Merges the contents of this discussion into another discussion.
     * @param forum_discussion $targetdiscussion Target discussion
     * @param int $userid User ID (0 = current)
     * @param bool $log True to log this action
     */
    public function merge_into($targetdiscussion, $userid=0, $log=true) {
        global $CFG;
        forum_utils::start_transaction();

        // Update parent post id of root post
        $record = new stdClass;
        $record->id = $this->discussionfields->postid;
        $record->parentpostid = $targetdiscussion->discussionfields->postid;
        forum_utils::update_record('forumng_posts', $record);

        // Move all posts into new discussion
        forum_utils::execute_sql("UPDATE {$CFG->prefix}forumng_posts SET " .
            "discussionid=" . $targetdiscussion->get_id() .
            " WHERE discussionid=" . $this->get_id());

        // Delete this discussion
        forum_utils::delete_records('forumng_discussions', 'id', $this->discussionfields->id);

        // Merge attachments (if any)
        $oldfolder = $this->get_attachment_folder();
        $newfolder = $targetdiscussion->get_attachment_folder();
        if (is_dir($oldfolder)) {
            $handle = forum_utils::opendir($oldfolder);
            $madenewfolder = false;
            while (false !== ($name = readdir($handle))) {
                if ($name != '.' && $name != '..') {
                    if (!$madenewfolder) {
                        check_dir_exists($newfolder, true, true);
                        $madenewfolder = true;
                    }
                    $oldname = $oldfolder . '/' . $name;
                    $newname = $newfolder . '/' . $name;
                    forum_utils::rename($oldname, $newname);
                }
            }
            closedir($handle);
        }

        // Merging the discussion into another might cause completion changes
        // (if there was a requirement for discussions and this is no longer
        // a discussion in its own right).
        $this->update_completion(false);

        if ($log) {
            $this->log('merge discussion d' . $targetdiscussion->get_id());
        }

        forum_utils::finish_transaction();
        $this->uncache();
        $targetdiscussion->uncache();
    }

    /**
     * Obtains a list of everybody who has read this discussion (only works
     * if the discussion is within the 'read' period). The list is in date order
     * (most recent first). Each returned item has ->time (time last read) and
     * ->user (Moodle user object) fields.
     * @param int $groupid Group ID or forum::ALL_GROUPS
     * @return array Array of information about readers
     */
    public function get_readers($groupid=forum::ALL_GROUPS) {
        global $CFG;
        $context = $this->get_forum()->get_context();
        // Create comma separated list of context ids
        $context_ids = str_replace('/',',', substr($context->path, 1));
        $id = $this->discussionfields->id;
        $groupjoin = $groupquery = '';
        $groupwhere = '';
        if ($groupid) {
            $groupjoin = " INNER JOIN {$CFG->prefix}groups_members gm ON gm.userid=fr.userid
    INNER JOIN {$CFG->prefix}groups g ON gm.groupid = g.id";
            $groupwhere = " AND g.id=$groupid";
        }

        $result = get_records_sql("
SELECT
    fr.id,
    " . forum_utils::select_username_fields('u', false) . ",
    fr.time,
    u.idnumber AS u_idnumber
FROM
    {$CFG->prefix}forumng_read fr
    INNER JOIN {$CFG->prefix}user u ON u.id = fr.userid
    $groupjoin
WHERE
    fr.discussionid = $id
    $groupquery
    AND fr.userid in(SELECT userid FROM {$CFG->prefix}role_assignments ra  
        WHERE ra.contextid in($context_ids)AND ra.roleid in ($CFG->forumng_monitorroles))
            $groupwhere
ORDER BY
    fr.time DESC");
        $result = $result ? $result : array();

        foreach ($result as $item) {
            $item->user = forum_utils::extract_subobject($item, 'u_');
        }

        return $result;
    }

    /**
     * @return bool True if read tracking is enabled for this discussion
     *   (it is not too old, and read tracking is turned on globally)
     */
    public function is_read_tracked() {
        $this->check_full();
        return forum::enabled_read_tracking() &&
            ($this->discussionfields->timemodified >=
                forum::get_read_tracking_deadline());
    }

    /**
     * Marks this discussion read.
     * @param int $time Time to mark it read at (0 = now)
     * @param int $userid User who's read the discussion (0=current)
     */
    public function mark_read($time=0, $userid=0) {
        $userid = forum_utils::get_real_userid($userid);
        if(!$time) {
            $time = time();
        }
        forum_utils::start_transaction();
        $existing = get_record('forumng_read', 'userid', $userid,
            'discussionid', $this->discussionfields->id);
        if ($existing) {
            $readrecord = new StdClass;
            $readrecord->id = $existing->id;
            $readrecord->time = $time;
            forum_utils::update_record('forumng_read', $readrecord);
        } else {
            $readrecord = new StdClass;
            $readrecord->userid = $userid;
            $readrecord->discussionid = $this->discussionfields->id;
            $readrecord->time = $time;
            forum_utils::insert_record('forumng_read', $readrecord);
        }
        forum_utils::finish_transaction();

        if ($this->incache) {
            $this->discussionfields->timeread = $time;
            $this->cache($this->incache->userid);
        }
    }

    /**
     * Marks this discussion unread.
     * @param int $userid User who's not read the discussion (0=current)
     */
    public function mark_unread($userid=0) {
        $userid = forum_utils::get_real_userid($userid);
        forum_utils::delete_records('forumng_read',
            'userid', $userid, 'discussionid', $this->discussionfields->id);

        if ($this->incache) {
            $this->discussionfields->timeread = null;
            $this->cache($this->incache->userid);
        }
    }

    /**
     * Called when a post is deleted or undeleted or modified, or there is a
     * larger change to the discussion
     * @param forum_post $post Post that has changed; null to always recalculate
     */
    function possible_lastpost_change($post=null) {
        $recalculate = false;
        if (!$post) {
            $recalculate = true;
        } else {
            if ($post->get_deleted()) {
                // For deleted posts, recalculate if this was previously
                // considered the latest post
                $recalculate =
                    $this->discussionfields->lastpostid == $post->get_id();
            } else {
                // For other posts, recalculate if this is now newer than the
                // stored last post
                $recalculate =
                    $post->get_modified() > $this->discussionfields->timemodified;
            }
        }

        // If necessary, recalculate the date
        if ($recalculate) {
            global $CFG;
            $change = new stdClass;
            $change->id = $this->get_id();

            $rs = forum_utils::get_recordset_sql("SELECT id " .
                "FROM {$CFG->prefix}forumng_posts WHERE discussionid = " .
                $this->get_id() . " AND deleted=0 AND oldversion=0 " .
                "ORDER BY modified DESC", 0, 1);
            if ($rec = rs_fetch_next_record($rs)) {
                $change->lastpostid = $rec->id;
            } else {
                throw new forum_exception('No last post');
            }
            rs_close($rs);
            if ($change->lastpostid != $this->discussionfields->lastpostid) {
                forum_utils::update_record('forumng_discussions', $change);
            }
        }
    }

    /**
     * Records an action in the Moodle log for current user.
     * @param string $action Action name - see datalib.php for suggested verbs
     *   and this code for example usage
     * @param string $replaceinfo Optional info text to replace default (which
     *   is just the discussion id again)
     */
    function log($action, $replaceinfo = '') {
        $info = $this->discussionfields->id;
        if ($replaceinfo !== '') {
            $info = $replaceinfo;
        }
        add_to_log($this->get_forum()->get_course_id(), 'forumng',
            $action, $this->get_log_url(), $info,
            $this->get_forum()->get_course_module_id());
    }

    /**
     * Checks whether this discussion is currently visible to students.
     * A discussion is visible to students if it is not deleted and is not
     * restricted to a non-current time period.
     * @return bool True if it's visible
     */
    function is_currently_visible() {
        // Deleted
        if($this->is_deleted()) {
            return false;
        }

        return $this->is_within_time_period();
    }

    /**
     * @return bool True if deleted
     */
    function is_deleted() {
        return $this->discussionfields->deleted ? true : false;
    }

    /**
     * @return bool True if discussion is within the given time period, or
     *   there isn't one
     */
    function is_within_time_period() {
        // Start/end time, if set
        $now = time();
        return ($this->discussionfields->timestart <= $now &&
            ((!$this->discussionfields->timeend) ||
                ($this->discussionfields->timeend > $now)));
    }

    /**
     * @return int NOT_SUBSCRIBED:0; PARTIALLY_SUBSCRIBED:1; FULLY_SUBSCRIBED:2; THIS_GROUP_SUBSCRIBED:5; THIS_GROUP_NOT_SUBSCRIBED:6;
     * @param int $userid User who's not read the discussion (0=current)
     */
    function is_subscribed($userid=0) {
        $userid = forum_utils::get_real_userid($userid);
        $subscription_info = $this->get_forum()->get_subscription_info($userid);
            if ($subscription_info->wholeforum) {
                //subscribed to the entire forum
                return forum::FULLY_SUBSCRIBED;
            } else if (count($subscription_info->discussionids) == 0) {
                if (count($subscription_info->groupids) == 0) {
                    //not subscribed at all
                    return forum::NOT_SUBSCRIBED;
                } else {
                    if ($this->get_forum()->get_group_mode()) {
                        //if the group mode turned on, we need to check if subscribed to the group 
                        //that the current discussion belongs to
                        foreach ($subscription_info->groupids as $id) {
                            if ($this->get_group_id() == $id) {
                                return forum::THIS_GROUP_SUBSCRIBED;
                            }
                        }
                        return forum::THIS_GROUP_NOT_SUBSCRIBED;
                    } else {
                        return forum::NOT_SUBSCRIBED;
                    }
                }

            } else {
                //discussionids array is not empty
                //No needs to check the groupids here assuming all the subscripiton data in the database is not messed up
                $discussionid = $this->get_id();
                foreach ($subscription_info->discussionids as $id => $groupid) {
                    if ($discussionid == $id) {
                        return forum::PARTIALLY_SUBSCRIBED;
                    }
                }
                return forum::NOT_SUBSCRIBED;
            }
    }

    /**
     * @return True if discussion contains data about whether the user has
     *   read it or not
     */
    public function has_unread_data() {
        return property_exists($this->discussionfields, 'timeread');
    }

    /**
     *
     * @return int User ID that unread data was requested for (-1 if none)
     */
    public function get_unread_data_user_id() {
        return empty($this->foruserid) ? -1 : $this->foruserid;
    }

    /**
     * Checks that data about whether or not the user has read this discussion
     * is available, throws exception otherwise.
     * @throws forum_exception If discussion does not contain unread data
     */
    private function check_unread_data() {
        if(!property_exists($this->discussionfields, 'timeread')) {
            throw new forum_exception(
              "Discussion does not contain unread data");
        }
    }

    /**
     * @return bool True if this entire discussion has not been read yet
     */
    public function is_entirely_unread() {
        $this->check_unread_data();
        return is_null($this->discussionfields->timeread);
    }

    /**
     * @return int Time (seconds since epoch) that this discussion was
     *   read by user, or null if it has never been read
     */
    public function get_time_read() {
        $this->check_unread_data();
        if ($this->pretendtimeread) {
            return $this->pretendtimeread;
        }
        return $this->discussionfields->timeread;
    }

    /**
     * Pretends that the discussion was read at a particular time. Future tests
     * to forum_post->is_unread() etc will use this data rather than anything
     * from the database.
     * @param $time Time you want discussion to have been read at, or 0 to
     *   stop pretending
     */
    public function pretend_time_read($time=0) {
        $this->pretendtimeread = $time;
    }

    /**
     * Use to obtain link parameters when linking to any page that has anything
     * to do with discussions.
     */
    public function get_link_params($type) {
        if ($type == forum::PARAM_FORM) {
            $d = '<input type="hidden" name="d" value="' .
                    $this->get_id() . '" />';
        } else {
            $d = 'd=' . $this->discussionfields->id;
        }
        return $d . $this->get_forum()->get_clone_param($type);
    }

    /**
     * Use to obtain link parameters when linking to any page that has anything
     * to do with discussions.
     * @return array Array of parameters e.g. ('d'=>317)
     */
    public function get_link_params_array() {
        $result = array('d' => $this->discussionfields->id);
        $this->get_forum()->add_clone_param_array($result);
        return $result;
    }

    /**
     * Obtains group info for a user in this discussion. Group info may be
     * cached in the discussion object in order to reduce DB queries.
     * @param int $userid User ID (must be a user who has posts in this discussion)
     *   May be 0 to pre-cache the data without returning anything
     * @param bool $cacheall If true, obtains data for all users in the
     *   discussion and caches it; set false if only one user's information
     *   is likely to be required, to do a single query
     * @return array Array of group objects containing id, name, picture
     *   (empty if none). False if $userid was 0.
     * @throws forum_exception If user is not in this discussion
     */
    public function get_user_groups($userid, $cacheall=true) {
        global $CFG;

        // If there is no cached data yet, and we are supposed to cache it,
        // then cache it now
        if (!$this->groupscache && $cacheall) {
            $this->groupscache = array();

            // Get list of users in discussion and initialise empty cache
            $userids = array();
            $this->get_root_post()->list_all_user_ids($userids);
            $userids = array_keys($userids);
            $inorequals = forum_utils::in_or_equals($userids);
            foreach ($userids as $auserid) {
                $this->groupscache[$auserid] = array();
            }

            // Basic IDs
            $courseid = $this->get_forum()->get_course_id();
            $discussionid = $this->get_id();

            // Grouping restriction
            if ($groupingid = $this->get_forum()->get_grouping()) {
                $groupingjoin = "INNER JOIN {$CFG->prefix}groupings_groups gg ON gg.groupid=g.id";
                $groupingcheck = "AND gg.groupingid = $groupingid";
            } else {
                $groupingjoin = $groupingcheck = '';
            }

            // Do query
            $rs = forum_utils::get_recordset_sql("
SELECT
    gm.userid, g.id, g.name, g.picture, g.hidepicture
FROM
    {$CFG->prefix}groups_members gm
    INNER JOIN {$CFG->prefix}groups g ON g.id=gm.groupid
    $groupingjoin
WHERE
    g.courseid=$courseid
    $groupingcheck
    AND gm.userid $inorequals
 ");
            while ($rec = rs_fetch_next_record($rs)) {
                $auserid = $rec->userid;
                unset($rec->userid);
                $this->groupscache[$auserid][] = $rec;
            }
            rs_close($rs);

            // Update cached version to include this data
            if ($this->incache) {
                $this->cache($this->incache->userid);
            }
        }

        // If caller only wants to cache data, return false
        if (!$userid) {
            return false;
        }

        // If there is cached data, use it
        if ($this->groupscache) {
            if (!array_key_exists($userid, $this->groupscache)) {
                throw new forum_exception("Unknown discussion user");
            }
            return $this->groupscache[$userid];
        }

        // Otherwise make a query just for this user
        $groups = groups_get_all_groups($this->get_forum()->get_course_id(),
            $userid, $this->get_course_module()->groupingid);
        return $groups ? $groups : array();
    }

    // Permissions
    //////////////

    /**
     * Checks if user can view this discussion, given that they can see the
     * forum as a whole.
     * @param int $userid User ID
     * @return bool True if user can view discusion
     */
    function can_view($userid=0) {
        // If this is a 'all groups' post, then we only require access to the
        // 'no groups' forum view (any group can see it)
        $groupid = is_null($this->discussionfields->groupid) ?
            forum::NO_GROUPS : $this->discussionfields->groupid;

        // Check forum view permission and group access
        if (!$this->forum->can_access_group($groupid, false, $userid)) {
            return false;
        }

        // Check viewdiscussion
        if (!has_capability('mod/forumng:viewdiscussion',
            $this->forum->get_context(), $userid)) {
            return false;
        }

        // Let forum type check permission too
        if (!$this->forum->get_type()->can_view_discussion($this, $userid)) {
            return false;
        }

        // Check time limits / delete
        if (!$this->is_currently_visible() && 
            !has_capability('mod/forumng:viewallposts', $this->forum->get_context(), $userid)) {
            return false;
        }

        return true;
    }

    /**
     * Makes security checks for viewing this discussion. Will not return if
     * user cannot view it.
     * This function should be a complete access check. It calls the forum's
     * equivalent method.
     * @param int $userid ID of user to check for
     */
    function require_view($userid=0) {
        // If this is a 'all groups' post, then we only require access to the
        // 'no groups' forum view (any group can see it)
        $groupid = is_null($this->discussionfields->groupid) ?
            forum::NO_GROUPS : $this->discussionfields->groupid;

        // Check forum view permission and group access
        $this->forum->require_view($groupid, $userid, true);

        // Check viewdiscussion
        require_capability('mod/forumng:viewdiscussion',
            $this->forum->get_context(), $userid);

        // Let forum type check permission too
        if (!$this->forum->get_type()->can_view_discussion($this, $userid)) {
            print_error('error_cannotviewdiscussion', 'forumng');
        }

        // Check time limits / delete
        if ($this->is_currently_visible()) {
            // Not deleted/no time limit, ordinary students are allowed to see
            return;
        }

        // The post is outside the permitted time limit, so you need
        // special permission to view it
        require_capability('mod/forumng:viewallposts',
            $this->forum->get_context(), $userid);
    }

    /**
     * Requires that the user can edit discussion options, otherwise prints
     * an error. (You need the managediscussions capability for this.)
     * Editing options is not affected by locks.
     */
    function require_edit() {
        $this->require_view();
        if (!$this->can_manage()) {
            print_error('error_cannotmanagediscussion', 'forumng');
        }
    }

    /**
     * Checks whether the user can split this discussion, assuming that they
     * can view it. (The split permission also works for join.)
     * @return bool True if they are allowed to split
     */
    public function can_split(&$whynot, $userid=0) {
        // Check if discussion is locked
        if ($this->is_locked()) {
            $whynot = 'edit_locked';
            return false;
        }

        // Check user has capability
        if (!has_capability('mod/forumng:splitdiscussions',
                $this->forum->get_context(), $userid)) {
            $whynot = 'edit_nopermission';
            return false;
        }

        return true;
    }
    
    /**
     * When carrying out actions on discussion, this permission should be 
     * checked to ensure that the user is allowed to write to that discussion's
     * group.
     * @param int $userid User ID, 0 = current
     * @return bool True if they're allowed
     */
    public function can_write_to_group($userid=0) {
        // Get group id
        $groupid = is_null($this->discussionfields->groupid) ?
            forum::NO_GROUPS : $this->discussionfields->groupid;

        // Check forum group access
        return $this->forum->can_access_group($groupid, true, $userid);
    }

    /**
     * Checks if you are allowed to manage settings of this discussion.
     * @param int $userid User ID, 0 = current
     * @return bool True if they're allowed
     */
    public function can_manage($userid=0) {
        return $this->can_write_to_group($userid) &&
            $this->forum->can_manage_discussions($userid);
    }
    /**
     * Checks whether the user can subscribe this discussion
     * @return bool True if this user is allowed to subscribe
     */
    public function can_subscribe($userid=0) {
        //if PARTIALLY_SUBSCRIBED:1 or FULLY_SUBSCRIBED:2 or THIS_GROUP_SUBSCRIBED:5 return false
        if ($this->is_subscribed($userid) != forum::NOT_SUBSCRIBED && 
            $this->is_subscribed($userid) != forum::THIS_GROUP_NOT_SUBSCRIBED) {
            return false;
        }
        if (!$this->get_forum()->can_change_subscription($userid)) {
            return false;
        }
        return true;
    }

    /**
     * Checks whether the user can unsubscribe this discussion
     * @return bool True if this user is allowed to unsubscribe
     */
    public function can_unsubscribe($userid=0) {
        $issubscribed = $this->is_subscribed($userid);
        if ($issubscribed == forum::PARTIALLY_SUBSCRIBED && 
            $this->get_forum()->can_change_subscription($userid)) {
                return true;
            }
        return false;
    }

    // UI
    /////

    /**
     * Displays a short version (suitable for including in discussion list)
     * of this discussion including a link to view the discussion and to
     * mark it read (if enabled).
     * @param int $groupid Group ID/constant being displayed
     * @param bool $last True if this is the last item in the list
     * @return string HTML code to print out for this discussion
     */
    function display_discussion_list_item($groupid, $last = false) {
        return $this->forum->get_type()->display_discussion_list_item(
            $this, $groupid, $last);
    }

    /**
     * Given a list of post IDs, displays these selected posts in a manner 
     * suitable for use in email. Note that this function is now used for
     * a number of other purposes in addition to email.
     * @param array $postids Array of IDs for posts to include, or false
     *   to include all posts
     * @param string $alltext Output variable; text of all posts will be 
     *   appended (text format)
     * @param string $allhtml Output variable; text of all posts will be 
     *   appended (HTML format)
     * @param bool $showuserimage True (default) to include user pictures
     * @param bool $printableversion True to use the printable-version flag to
     *   display posts.
     */
    function build_selected_posts_email($postids, &$alltext, &$allhtml,
        $showuserimage=true, $printableversion=false) {
        global $USER;
        $list = array();
        $rootpost = $this->get_root_post();
        $rootpost->list_child_ids($list);
        foreach($list as $postid) {
            if ($postids && !in_array($postid, $postids)) {
                continue;
            }
            $post = $rootpost->find_child($postid);
            $text = '';
            $html = '';
            $post->build_email(null, $subject, $text, $html, true,
                false, has_capability('moodle/site:viewfullnames', 
                    $this->get_forum()->get_context()), current_language(), 
                $USER->timezone, true, true, $showuserimage, $printableversion);

            if ($alltext != '') {
                $alltext .= "\n" . forum_cron::EMAIL_DIVIDER . "\n";
                $allhtml .= '<hr size="1" noshade="noshade" />';
            }
            $alltext .= $text;
            $allhtml .= $html;
        }

        // Remove crosslinks to posts that do not exist
        $this->posthtml = $allhtml;
        $allhtml = preg_replace_callback(
            '~<a class="forumng-parentlink" href="#p([0-9]+)">([0-9]+)</a>~', 
            array($this,'internal_build_selected_posts_replacer'), $allhtml);
    }

    function internal_build_selected_posts_replacer($matches) {
        if(strpos($this->posthtml, ' id="p' . $matches[1] . '"') === false) {
            return $matches[2];
        } else {
            return $matches[0];
        }
    }

    /**
     * Prints the header and breadcrumbs for a page 'within' a discussion.
     * @param string $pagename Name of page
     */
    public function print_subpage_header($pagename) {
        $navigation = array();
        $navigation[] = array(
            'name'=>shorten_text(htmlspecialchars(
                $this->get_subject())),
            'link'=>$this->get_url(), 'type'=>'forumng');
        $this->forum->print_subpage_header($pagename, $navigation);
    }

    /**
     * Displays row of buttons that go along the bottom of a discussion.
     * @return string HTML code for all feature buttons in this discussion
     */
    public function display_discussion_features() {
        // Get forum type
        $type = $this->get_forum()->get_type();

        // Print discussion features
        $features = '';
        foreach(discussion_feature::get_all() as $feature) {
            if ($feature->should_display($this) &&
                $type->allow_discussion_feature($this, $feature)) {
                $features .= $feature->display($this);
            }
        }
        if ($features) {
            print '<div id="forumng-features">' . $features . '</div>';
        }
    }

    /**
     * Display subscribe options for this discussion.
     * @return string HTML code for this area
     */
    public function display_subscribe_options() {
        if (!$this->can_subscribe() && !$this->can_unsubscribe()) {
            return '';
        } else {
            return $this->get_forum()->get_type()->display_discussion_subscribe_option(
                $this, $this->can_subscribe());
        }
    }

    public function display_link_back_to_forum() {
        // Print link back to discussion list
        print '<div id="forumng-arrowback">' .
            link_arrow_left($this->get_forum()->get_name(), 
                'view.php?' . $this->get_forum()->get_link_params(forum::PARAM_HTML)) .
                 '</div>';
    }


    /**
     * Subscribe a user to this discussion. (Assuming it permits manual subscribe/
     * unsubscribe.)
     * @param $userid User ID (default current)
     * @param $log True to log this
     */
    public function subscribe($userid=0, $log=true) {
        global $CFG;
        $userid = forum_utils::get_real_userid($userid);
        // For shared forums, we subscribe to a specific clone
        if ($this->get_forum()->is_shared()) {
            $clonecmid = $this->get_forum()->get_course_module_id();
            $clonevalue = '=' . $clonecmid;
        } else {
            $clonecmid = null;
            $clonevalue = 'IS NULL';
        }
        forum_utils::start_transaction();
        //Clear any previous subscriptions to this discussion from the same user if any 
        forum_utils::execute_sql(
            "DELETE FROM {$CFG->prefix}forumng_subscriptions " .
            "WHERE userid=" . $userid .
            " AND discussionid=" . $this->discussionfields->id .
            " AND clonecmid " . $clonevalue);

        $subrecord = new StdClass;
        $subrecord->userid = $userid;
        $subrecord->forumid = $this->get_forum()->get_id();
        $subrecord->subscribed = 1;
        $subrecord->discussionid = $this->discussionfields->id;
        $subrecord->clonecmid = $clonecmid;
        forum_utils::insert_record('forumng_subscriptions', $subrecord);
        forum_utils::finish_transaction();

        if ($log) {
            $this->log('subscribe', $userid . ' discussion ' . $this->get_id());
        }
    }

    /**
     * Unsubscribe a user from this discussion.
     * @param $userid User ID (default current)
     * @param $log True to log this
     */
    public function unsubscribe($userid=0, $log=true) {
        global $CFG;
        $userid = forum_utils::get_real_userid($userid);
        // For shared forums, we subscribe to a specific clone
        if ($this->get_forum()->is_shared()) {
            $clonecmid = $this->get_forum()->get_course_module_id();
            $clonevalue = '=' . $clonecmid;
        } else {
            $clonecmid = null;
            $clonevalue = 'IS NULL';
        }
        forum_utils::start_transaction();
        //Clear any previous subscriptions to this discussion from the same user if any 
        forum_utils::execute_sql(
            "DELETE FROM {$CFG->prefix}forumng_subscriptions " .
            "WHERE userid=" . $userid .
            " AND discussionid=" . $this->discussionfields->id .
            ' AND clonecmid ' . $clonevalue);
        forum_utils::finish_transaction();

        if ($log) {
            $this->log('unsubscribe', $userid . ' discussion ' . $this->get_id());
        }
    }
    
    /**
     * @return string HTML links for RSS/Atom feeds to this discussion (if
     *   enabled etc)
     */
    public function display_feed_links() {
        global $CFG;

        // Check they're allowed to see it
        if ($this->get_forum()->get_effective_feed_option()
            != forum::FEEDTYPE_ALL_POSTS) {
            return '';
        }

        // Icon (decoration only) and Atom link
        $strrss = get_string('rss');
        $stratom = get_string('atom', 'forumng');
        $feed = '<div class="forumng-feedlinks">';
        $feed .= '<a class="forumng-iconlink" href="'. htmlspecialchars(
            $this->get_feed_url(forum::FEEDFORMAT_ATOM)) . '">';
        $feed .= "<img src='$CFG->pixpath/i/rss.gif' alt=''/> " .
            '<span class="forumng-textbyicon">' . $stratom . '</span></a> ';
        $feed .= '<a href="'. htmlspecialchars($this->get_feed_url(
            forum::FEEDFORMAT_RSS)) . '">' . $strrss . '</a> ';
        $feed .= '</div>';
        return $feed;
    }

    // Feeds
    ////////

    /**
     * Gets URL for an Atom/RSS feed to this discussion.
     * @param int $feedformat FEEDFORMAT_xx constant
     * @param int $userid User ID or 0 for current
     * @return string URL for feed
     */
    public function get_feed_url($feedformat, $userid=0) {
        global $CFG;
        $userid = forum_utils::get_real_userid($userid);
        $groupid = $this->get_group_id();

        return $CFG->wwwroot . '/mod/forumng/feed.php?' .
            $this->get_link_params(forum::PARAM_PLAIN) .
            '&user=' . $userid .
            '&key=' . $this->get_forum()->get_feed_key($groupid, $userid) .
            '&format=' . ($feedformat == forum::FEEDFORMAT_RSS ? 'rss' : 'atom');
    }

    /**
     * Obtains list of posts to include in an Atom/RSS feed.
     * @param int $userid User ID
     * @return array Array of forum_post objects in date order (newest first)
     */
    public function get_feed_posts($userid) {
        return $this->forum->get_feed_posts(0, $userid, $this);
    }
    
    // Completion
    /////////////

    /**
     * Updates completion status based on changes made to entire discussion.
     * @param bool $positive True if the changes will make things complete
     *   that were previously incomplete; false if they will make things 
     *   incomplete that were previously complete
     */
    private function update_completion($positive) {
        // Get list of affected users (if any)
        $users = array();
        if ($this->forum->get_completion_replies() || 
            $this->forum->get_completion_posts()) {
            // Affected users = everyone who posted
            $rootpost = $this->get_root_post();
            $posts = array();
            $rootpost->build_linear_children($posts);
            foreach($posts as $post) {
                $users[$post->get_user()->id] = true;
            }
        } else if ($this->forum->get_completion_discussions()) {
            // Affected users = discussion poster only
            $users[$this->get_poster()->id] = true;
        }

        foreach($users as $userid => $junk) {
            $course = $this->get_course(); 
            $cm = $this->get_course_module();
            completion_update_state($course, $cm,
                $positive ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE,
                $userid);
        }
    }
}
?>
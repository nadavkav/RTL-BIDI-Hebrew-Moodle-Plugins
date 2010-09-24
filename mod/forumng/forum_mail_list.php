<?php
require_once(dirname(__FILE__).'/forum.php');

/**
 * Manages a list (based on a database recordset, so not all stored in memory)
 * of posts which need to be emailed to users.
 *
 * The list only includes posts which are due to be mailed.
 * It does not include:
 * - Posts older than 48 hours (this is to avoid cron sending out a million old
 *   posts if it has never been run before), with exception of timed posts that
 *   have just become due
 * - Posts which have been deleted, or old versions of edited posts.
 * - Any posts which don't belong to a valid discussion, forum, and
 *   course-module
 * - Posts which are timed and not yet due - even if user has permission to
 *   see them (it is more useful for the timed posts to be mailed out at the
 *   'right time' even to these users, plus is easier)
 */
class forum_mail_list {
    /** Do not mail posts more than 2 days old (in case cron isn't run) */
    const DO_NOT_MAIL_AFTER_HOURS = 48;

    /** Config flag used to prevent sending mails twice */
    const PENDING_MARK_MAILED = 'pending_mark_mailed';

    private $rs;
    private $time;

    private $forum, $discussion;
    private $storedrecord;

    private $postcount;

    /**
     * Creates the mail queue and runs query to obtain list of posts that should
     * be mailed.
     * @param bool $tracetimes True if it should call mtrace to display
     *   performance information
     */
    function __construct($tracetimes) {
        global $CFG;
        $this->time = time();
        $this->forum = null;
        $this->discussion = null;
        $this->storedrecord = null;
        $this->postcount = 0;

        // Check if an earlier run got aborted. In that case we mark all
        // messages as mailed anyway because it's better to skip some than
        // to send out double-posts.
        if ($pending = get_config('forumng', $this->get_pending_flag_name())) {
            $this->mark_mailed($pending);
        }
        // Note that we are mid-run
        set_config($this->get_pending_flag_name(), $this->time, 'forumng');

        $querychunk = $this->get_query_chunk($this->time);
        if (!($this->rs = get_recordset_sql($sql="
SELECT
    ".forum_utils::select_forum_fields('f').",
    ".forum_utils::select_discussion_fields('fd').",
    ".forum_utils::select_post_fields('discussionpost').",
    ".forum_utils::select_post_fields('fp').",
    ".forum_utils::select_post_fields('reply').",
    ".forum_utils::select_course_module_fields('cm').",
    ".forum_utils::select_context_fields('x').",
    ".forum_utils::select_username_fields('u', true).",
    ".forum_utils::select_username_fields('eu').",
    ".forum_utils::select_username_fields('replyu').",
    ".forum_utils::select_username_fields('replyeu').",
    ".forum_utils::select_course_fields('c')."
$querychunk
ORDER BY
    f.course, f.id, fd.id, fp.id" ))) {
            throw new forum_exception("Mail queue query failed");
        }
    }

    /**
     * Obtains the next post in current forum.
     * @param object &$post Output variable: Receives the post object
     * @param object &$inreplyto Output variable: Receives the post this one was
     *   replying to
     * @return bool True if a post could be retrieved, false if there are
     *   no more posts in this forum (call next_forum)
     */
    function next_post(&$post, &$inreplyto, $debug=0) {
    	print ($debug) ? ("\n    --in next->post"):'';
        // Make sure we have a forum/discussion setup
        if ($this->forum==null || $this->discussion==null)  {
            throw new forum_exception("Cannot call next_post when not inside
                forum and discussion");
        }

        // Get record
        if ($this->storedrecord) {
        	print ($debug) ? ('    this->storedrecord is true'):'';
            $record = $this->storedrecord;
            $this->storedrecord = null;
        } else {
        	print ($debug) ? ("    getting next record in rs"):'';
            $record = rs_fetch_next_record($this->rs);
            if (!$record) {
            	print ($debug) ? ("    end of the line- "):'';
                // End of the line. Mark everything as mailed
                $this->mark_mailed($this->time);
                rs_close($this->rs);
                $this->rs = null;
                $this->discussion = null;
                return false;
            }
        }

        // If record discussion is not the same as current discussion
        if ($record->fd_id != $this->discussion->get_id()) {
        	print ($debug) ? ("    record discussions differ"):'';
            $this->storedrecord = $record;
            $this->discussion = null;
            return false;
        }

        // Get post details including the joined user info
        $postfields = forum_utils::extract_subobject($record, 'fp_');
        forum_utils::copy_subobject($postfields, $record, 'u_');
        forum_utils::copy_subobject($postfields, $record, 'eu_');
        $post = new forum_post($this->discussion, $postfields);
        if ($record->reply_id) {
            $postfields = forum_utils::extract_subobject($record, 'reply_');
            forum_utils::copy_subobject($postfields, $record, 'replyu_', 'u_');
            forum_utils::copy_subobject($postfields, $record, 'replyeu_', 'eu_');
            $inreplyto = new forum_post($this->discussion, $postfields);
        } else {
            $inreplyto = null;
        }

        $this->postcount++;
        print ($debug) ? ("    --next->post return true"):'';
        return true;
    }

    function next_discussion(&$discussion, $debug=0) {
    	print ($debug) ? ("\n  --in next->discussion"):'';
        // Make sure we have a forum setup but no discussion
        if ($this->forum==null)  {
            throw new forum_exception("Cannot call next_discussion when not inside
                forum");
        }
        // Skip if required to get to new discussion
        while ($this->discussion!=null) {
        	print ($debug) ? ('calling next->post from next_discussion'):''; 
            $this->next_post($post, $inreplyto, $debug);
        }

        // Get record
        if ($this->storedrecord) {
        	print ($debug) ? ('    this->storedrecord is true'):'';
            $record = $this->storedrecord;
            $this->storedrecord = null;
        } else if(!$this->rs) {
        	print ($debug) ? ("    already used entire list"):'';
            // Already used entire list and closed recordset
            $this->forum = null;
            return false;
        } else {
        	print ($debug) ? ("    getting next record is rs"):'';
            $record = rs_fetch_next_record($this->rs);
            if (!$record) {
            	print ($debug) ? ("\n    end of the line"):'';
                // End of the line. Mark everything as mailed
                $this->mark_mailed($this->time);
                rs_close($this->rs);
                $this->forum = null;
                $this->rs = null;
                return false;
            }
        }
        
        // If record forums are not the same as current forum
        if ($record->f_id != $this->forum->get_id()) {
            print ($debug) ? ("    forums differ "):'';
            $this->storedrecord = $record;
            $this->forum = null;
            return false;
        }
        

        // Store record and check discussion
        $this->storedrecord = clone($record);
        $discussionfields = forum_utils::extract_subobject($record, 'fd_');
        $discussionfields->subject = $record->discussionpost_subject;
        $discussion = new forum_discussion($this->forum,
            $discussionfields, false, -1);
        $this->discussion = $discussion;
        print ($debug) ? ("    --next->discussion return true"):'';
        return true;
    }

    function next_forum(&$forum, &$cm, &$context, &$course, $debug=0) {
    	print ($debug) ? ("\n--in next_forum-- "):'';
        // Skip if required to get to new forum
        while ($this->forum!=null) {
        	print ($debug) ? ('calling next_discussion from next_forum'):''; 
            $this->next_discussion($discussion, $debug);
        }

        // Get record
        if ($this->storedrecord) {
            $record = $this->storedrecord;
            $this->storedrecord = null;
        } else if(!$this->rs) {
        	print ($debug) ? ("  --used entire list"):'';
            // Already used entire list and closed recordset
            return false;
        } else {
        	print ($debug) ? (' --getting next record'):'';
            $record = rs_fetch_next_record($this->rs);
            if (!$record) {
            	print ($debug) ? ("    end of the line"):'';
                // End of the line. Mark everything as mailed
                $this->mark_mailed($this->time);
                rs_close($this->rs);
                $this->rs = null;
                return false;
            }
        }

        // Set data
        $this->storedrecord = clone($record);
        $cm = forum_utils::extract_subobject($record, 'cm_');
        $course = forum_utils::extract_subobject($record, 'c_');
        $context = forum_utils::extract_subobject($record, 'x_');
        $forum = new forum($course, $cm, $context,
            forum_utils::extract_subobject($record, 'f_'));
        $this->forum = $forum;
        print ($debug) ? ("  --exiting next->forum true"):'';
        return true;
    }

    private function mark_mailed($time) {
        global $CFG;
        $querychunk = $this->get_query_chunk($time, 'forumng_posts');
        $before = microtime(true);
        mtrace('Marking processed posts: ', '');
        forum_utils::update_with_subquery_grrr_mysql("
UPDATE
    {$CFG->prefix}forumng_posts
SET
    mailstate = " . $this->get_target_mail_state() . "
WHERE
    id %'IN'%", "SELECT fp.id $querychunk");
        mtrace(round(microtime(true)-$before, 1) . 's.');

        unset_config($this->get_pending_flag_name(), 'forumng');
    }

    public function get_post_count_so_far() {
        return $this->postcount;
    }

    protected function get_pending_flag_name() {
        return self::PENDING_MARK_MAILED;
    }

    protected function get_target_mail_state() {
        return forum::MAILSTATE_MAILED;
    }

    protected function get_query_chunk($time) {
        global $CFG;

        // We usually only mail out posts after a delay of maxeditingtime.
        $mailtime = $time - $CFG->maxeditingtime;

        // In case cron has not run for a while,
        $safetynet = $time - self::DO_NOT_MAIL_AFTER_HOURS * 3600;

        global $CFG;
        return "
FROM
    {$CFG->prefix}forumng_posts fp
    INNER JOIN {$CFG->prefix}user u ON fp.userid=u.id
    LEFT JOIN {$CFG->prefix}user eu ON fp.edituserid=eu.id
    LEFT JOIN {$CFG->prefix}forumng_posts reply ON fp.parentpostid = reply.id
    LEFT JOIN {$CFG->prefix}user replyu ON reply.userid = replyu.id
    LEFT JOIN {$CFG->prefix}user replyeu ON reply.edituserid = replyeu.id
    INNER JOIN {$CFG->prefix}forumng_discussions fd ON fp.discussionid = fd.id
    INNER JOIN {$CFG->prefix}forumng_posts discussionpost ON fd.postid = discussionpost.id
    INNER JOIN {$CFG->prefix}forumng f ON fd.forumid = f.id
    INNER JOIN {$CFG->prefix}course_modules cm ON f.id = cm.instance
    INNER JOIN {$CFG->prefix}context x ON x.instanceid = cm.id
    INNER JOIN {$CFG->prefix}course c ON c.id = f.course
WHERE
    -- Skip future posts (this is more relevant when using the set state
    -- version of the query)...
    fp.created < $time

    -- Post must not have been mailed yet, also wait for editing delay if
    -- not set to mailnow
    AND ((fp.mailstate = " . forum::MAILSTATE_NOT_MAILED . "
        AND fp.created < $mailtime)
        OR fp.mailstate = " . forum::MAILSTATE_NOW_NOT_MAILED . ")

    -- Don't mail out really old posts (unless they were previously hidden)
    AND (fp.created > $safetynet OR fd.timestart > $safetynet)

    -- Discussion must meet time requirements
    AND fd.timestart < $time
    AND (fd.timeend = 0 OR fd.timeend > $time)

    -- Post and discussion must not have been deleted and we're only looking
    -- at original posts not edited old ones
    AND fp.deleted = 0
    AND fd.deleted = 0
    AND fp.oldversion = 0
    
    -- Course-module and context limitations
    AND cm.module = (SELECT id FROM {$CFG->prefix}modules WHERE name='forumng')
    AND x.contextlevel = 70";
    }
}
?>
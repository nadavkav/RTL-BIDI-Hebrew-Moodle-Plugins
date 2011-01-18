<?php
/**
 * Represents a draft forum post (reply or discussion), as stored in the 
 * forumng_drafts database table.
 * @see forum
 * @package forumng
 * @author sam marshall
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @copyright Copyright 2009 The Open University
 */
class forum_draft {
    private $draftfields;

    /**
     * Queries for draft posts, including necessary joins with other fields.
     * @param string $where Text of WHERE clause e.g. 'fdr.id=14'. May refer
     *   to aliases fdr (drafts), fd (discussions), fp (posts; post being 
     *   replied to), fpfirst (first post in discussion), and u (user being
     *   replied to)
     * @return array Array of forum_draft objects (empty if none)
     */
    static function query_drafts($where) {
        global $CFG;
        $result = array();
        $rs = get_recordset_sql("
SELECT
    fdr.*, fd.id AS discussionid, fpfirst.subject AS discussionsubject, 
    f.course AS courseid,
    " . forum_utils::select_username_fields('u', false) . "
FROM
    {$CFG->prefix}forumng_drafts fdr
    LEFT JOIN {$CFG->prefix}forumng_posts fp ON fdr.parentpostid = fp.id
    LEFT JOIN {$CFG->prefix}forumng_discussions fd ON fp.discussionid = fd.id
    LEFT JOIN {$CFG->prefix}forumng_posts fpfirst ON fd.postid = fpfirst.id
    LEFT JOIN {$CFG->prefix}user u ON fp.userid = u.id
    INNER JOIN {$CFG->prefix}forumng f ON fdr.forumid = f.id
WHERE
    $where
ORDER BY
    fdr.saved DESC
    ");
        if (!$rs) {
            throw new forum_exception("Failed to query for draft posts");
        }
        while($rec = rs_fetch_next_record($rs)) {
            $result[] = new forum_draft($rec);
        }
        rs_close($rs);
        return $result;
    }

    /**
     * @param int $draftid ID of draft
     * @return forum_draft Draft post
     */
    public static function get_from_id($draftid) {
        $posts = self::query_drafts("fdr.id = $draftid");
        if(count($posts) == 0) {
            throw new forum_exception("Draft post $draftid not found");
        }
        return reset($posts);
    }

    /**
     * Constructs draft post.
     * @param object $draftfields Fields from query_drafts query
     */
    private function __construct($draftfields) {
        $draftfields->replytouser = 
            forum_utils::extract_subobject($draftfields, 'u_');
        $this->draftfields = $draftfields;
    }

    /**
     * Saves a new draft message.
     * @param int $forumid ID of forum
     * @param int $groupid Group ID (null if none)
     * @param int $parentpostid ID of post this is in reply to, or 0 for 
     *   a new discussion
     * @param string $subject Subject of draft post
     * @param string $message Message of draft post
     * @param int $format Format (FORMAT_xx) of message
     * @param array $attachments Array of paths to attachments
     * @param string $options Options (null if none)
     * @param int $userid User ID or 0 for current
     * @return int ID of new draft
     */
    static function save_new($forum, $groupid, $parentpostid, $subject, 
        $message, $format, $attachments, $options, $userid=0) {
        $userid = forum_utils::get_real_userid($userid);
        $serializedoptions = $options ? addslashes(serialize($options)) : null;
        $record = (object)array('userid' => $userid, 'forumid' => $forum->get_id(),
            'parentpostid' => ($parentpostid ? $parentpostid : null),
            'subject' => addslashes($subject), 'message' => addslashes($message),
            'format' => $format, 
            'attachments' => count($attachments) > 0 ? 1 : 0,
            'saved' => time(), 'groupid' => $groupid, 'options' => $serializedoptions);
        forum_utils::start_transaction();
        $draftid = forum_utils::insert_record('forumng_drafts', $record);
        foreach($attachments as $path) {
            self::add_attachment($path, $forum->get_course()->id,
                $forum->get_id(), $draftid);
        }
        forum_utils::finish_transaction();

        return $draftid;
    }

    /**
     * Updates an existing draft message.
     * @param string $subject Subject of draft post
     * @param string $message Message of draft post
     * @param int $format Format (FORMAT_xx) of message
     * @param mixed $deleteattachments Array of names (not paths) of attachments
     *   to be deleted, if any; true = delete all
     * @param array $newattachments Array of paths to new attachments
     * @param int $groupid Group ID (null if none)
     * @param object $options Options (null if none)
     */
    function update($subject, $message, 
        $format, $deleteattachments, $newattachments, $groupid, $options) {
        $currentattachments = $this->get_attachment_names();
        $remainingattachments = $currentattachments;
        foreach($remainingattachments as $key=>$name) {
            if($deleteattachments===true || in_array($name, $deleteattachments)) {
                unset($remainingattachments[$key]);
            }
        }
        $someattachments = count($remainingattachments) > 0 || 
            count($newattachments) > 0;

        $serializedoptions = $options ? addslashes(serialize($options)) : null;

        $record = (object)array(
            'id' => $this->get_id(),
            'subject' => addslashes($subject), 'message' => addslashes($message),
            'format' => $format, 'attachments' => $someattachments ? 1 : 0,
            'groupid' => $groupid, 'options' => $serializedoptions, 'saved' => time());

        forum_utils::start_transaction();

        // Do database update
        forum_utils::update_record('forumng_drafts', $record);

        // Delete requested attachments
        $folder = $this->get_attachment_folder();
        foreach($currentattachments as $name) {
            if($deleteattachments===true || in_array($name, $deleteattachments)) {
                forum_utils::unlink("$folder/$name");
            }
        }

        // Add new attachments
        foreach($newattachments as $path) {
            $this->add_attachment($path);
        }

        forum_utils::finish_transaction();
    }

    /**
     * Deletes an existing draft message.
     */
    function delete() {
        forum_utils::start_transaction();

        // Delete record
        forum_utils::delete_records('forumng_drafts', 'id', 
            $this->draftfields->id);

        // Delete attachments
        $folder = $this->get_attachment_folder();
        if(is_dir($folder)) {
            $handle = forum_utils::opendir($folder);
            while (false !== ($name = readdir($handle))) {
                if ($name != '.' && $name != '..') {
                    forum_utils::unlink("$folder/$name");
                }
            }
            closedir($handle);
            forum_utils::rmdir($folder);
        }

        forum_utils::finish_transaction();
    }

    // Direct fields
    ////////////////

    /**
     * @return int ID of this draft
     */
    function get_id() {
        return $this->draftfields->id;
    }

    /**
     * @return int ID of user making draft
     */
    function get_user_id() {
        return $this->draftfields->userid;
    }

    /**
     * @return int ID of forum containing draft
     */
    function get_forum_id() {
        return $this->draftfields->forumid;
    }

    /**
     * @return int Time (seconds since epoch) this draft was saved
     */
    function get_saved() {
        return $this->draftfields->saved;
    }

    /**
     * @return string Message subject
     */
    function get_subject() {
        return $this->draftfields->subject;
    }

    /**
     * @return string Message content
     */
    function get_message() {
        return $this->draftfields->message;
    }

    /**
     * @return int Format (FORMAT_xx) of message content
     */
    function get_format() {
        return $this->draftfields->format;
    }

    /**
     * @return object Options object (may be null)
     */
    function get_options() {
        return $this->draftfields->options 
            ? unserialize($this->draftfields->options) : null;
    }

    // Discussion-related information from joins
    ////////////////////////////////////////////

    /**
     * @return bool True if this is a new discussion, false if it's a reply
     */
    function is_new_discussion() {
        return is_null($this->draftfields->discussionid);
    }

    /**
     * @return bool True if this is a reply, false if it's a new discussion
     */
    function is_reply() {
        return !is_null($this->draftfields->discussionid);
    }

    /**
     * @return int ID of group for new discussion (this field is not set for
     *   replies)
     */
    function get_group_id() {
        return $this->draftfields->groupid;
    }

    /**
     * Utility function to check this draft is about a reply in an existing
     * discussion.
     * @throws forum_exception If this is a new discussion (so no id yet)
     */
    private function check_discussion_exists() {
        if(!$this->draftfields->discussionid) {
            throw new forum_exception("Draft message does not have discussion");
        }
    }

    /**
     * @return int Discussion id
     * @throws forum_exception If this is a new discussion (so no id yet)
     */
    function get_discussion_id() {
        $this->check_discussion_exists();
        return $this->draftfields->discussionid;
    }

    /**
     * @return string Discussion subject
     * @throws forum_exception If this is a new discussion
     */
    function get_discussion_subject() {
        $this->check_discussion_exists();
        return $this->draftfields->discussionsubject;
    }

    /**
     * @return object Moodle user object (selected fields) for post being 
     *   replied to
     * @throws forum_exception If this is a new discussion
     */
    function get_reply_to_user() {
        $this->check_discussion_exists();
        return $this->draftfields->replytouser;
    }

    /**
     * @return int Parent post that is being replied to
     * @throws forum_exception If this is a new discussion
     */
    function get_parent_post_id() {
        $this->check_discussion_exists();
        return $this->draftfields->parentpostid;
    }

    // Attachments
    ///////////////

    /**
     * @return bool True if this draft has any attachments
     */
    public function has_attachments() {
        return $this->draftfields->attachments ? true : false;
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

    public function get_attachment_folder($courseid=0, $forumid=0, $draftid=0) {
        global $CFG;

        if(!$draftid) {
            $draftid = $this->draftfields->id;
            $forumid = $this->draftfields->forumid;
            $courseid = $this->draftfields->courseid;
        }

        return $CFG->dataroot . '/' . $courseid . '/moddata/forumng/drafts/' .
            $draftid;
    }

    private function add_attachment($path, $courseid=0, $forumid=0, $draftid=0) {
        // Check source file exists
        if(!file_exists($path)) {
            throw new forum_exception("Missing file $path");
        }

        // Get folder
        if (isset($this)) {
            $folder = $this->get_attachment_folder();
        } else {
            $folder = self::get_attachment_folder($courseid, $forumid, $draftid);
        }
        if(!check_dir_exists($folder, true, true)) {
            throw new forum_exception(
                "Failed to create attachment folder $folder");
        }

        // Check target path doesn't already exist. If it does, delete existing
        // file.
        $target = $folder.'/'.basename($path);
        if(file_exists($target)) {
            forum_utils::unlink($target);
        }

        // Move new file into place
        forum_utils::rename($path, $target);
    }
    
    // UI
    /////

    /**
     * Displays this draft as an item on the list.
     * @param forum $forum Forum that owns item
     * @param bool $last True if this is last in list
     * @return string HTML code for the item
     */
    public function display_draft_list_item($forum, $last) {
        return $forum->get_type()->display_draft_list_item($forum, $this, $last);
    }

    /**
     * Prints the content of this draft as a JavaScript variable (including
     * surrounding script tag).
     * @param int $playspaceid If set, this playspace contains the attachment
     *   files
     */
    public function print_js_variable($playspaceid=0) {
        print "<script type='text/javascript'>\n" .
            "var forumng_draft = { attachments: [";
        $first = true;
        foreach ($this->get_attachment_names() as $name) {
            if($first) {
                $first = false;
            } else {
                print ',';
            }
            print '"' . addslashes_js($name) . '"';
        }
        print '],attachmentplayspace:' . $playspaceid;

        foreach ($this->draftfields as $key=>$value) {
            $skip = false;
            switch($key) {
                // Skip unnecessary fields
                case 'discussionid' :
                case 'discussionsubject' :
                case 'courseid' :
                case 'replytouser' :
                case 'options' :
                case 'attachments' :
                    $skip = true;
                    break;
            }
            if ($skip) {
                continue;
            }
            print ',' . $key . ':"' . addslashes_js($value) . '"';
        }
        foreach( (array)($this->get_options()) as $key=>$value) {
            print ',' . $key . ':"' . addslashes_js($value) . '"';
        }

        print "};\n</script>";        
    }
}
?>
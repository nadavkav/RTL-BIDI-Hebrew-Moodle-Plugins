<?php
require_once(dirname(__FILE__).'/forum.php');

/**
 * Manages a list (based on a database recordset, so not all stored in memory)
 * of posts which need to be included in digests sent to users.
 *
 * The list only includes posts which are due to be included in digests. The
 * same caveats apply as to forum_mail_list.
 */
class forum_digest_list extends forum_mail_list {
    /** Config flag used to prevent sending mails twice */
    const PENDING_MARK_DIGESTED = 'pending_mark_digested';

    function __construct($tracetimes) {
        parent::__construct($tracetimes);
    }

    protected function get_pending_flag_name() {
        return self::PENDING_MARK_DIGESTED;
    }

    protected function get_target_mail_state() {
        return forum::MAILSTATE_DIGESTED;
    }

    protected function get_safety_net($time) {
        // The digest safety net is 24 hours earlier because digest posts may
        // be delayed by 24 hours.
        return parent::get_safety_net($time) - 24 * 3600;
    }
    
    protected function get_query_chunk($time) {
        global $CFG;

        // In case cron has not run for a while
        $safetynet = $this->get_safety_net($time);

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
    INNER JOIN {$CFG->prefix}forumng clonef 
        ON (clonef.originalcmid = cm.id OR (f.shared=1 AND clonef.id = f.id))
    INNER JOIN {$CFG->prefix}course_modules clonecm ON clonef.id = clonecm.instance
    INNER JOIN {$CFG->prefix}modules m ON m.id = cm.module AND m.id = clonecm.module
WHERE
    -- Post must be waiting for digest
    fp.mailstate = " . forum::MAILSTATE_MAILED . "

    -- Don't mail out really old posts (unless they were previously hidden)
    AND (fp.created > $safetynet OR fd.timestart > $safetynet)

    -- Post and discussion must not have been deleted and we're only looking
    -- at original posts not edited old ones
    AND fp.deleted = 0
    AND fd.deleted = 0
    AND fp.oldversion = 0

    -- Course-module and context limitations
    AND m.name='forumng'
    AND x.contextlevel = 70";
    }
}
?>
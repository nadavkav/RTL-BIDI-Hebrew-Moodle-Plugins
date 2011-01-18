<?php
require_once(dirname(__FILE__).'/forum_utils.php');
require_once(dirname(__FILE__).'/forum_discussion.php');
require_once(dirname(__FILE__).'/forum_discussion_list.php');
require_once(dirname(__FILE__).'/forum_post.php');
require_once(dirname(__FILE__).'/forum_draft.php');
require_once(dirname(__FILE__).'/forum_exception.php');
require_once(dirname(__FILE__).'/type/forum_type.php');
require_once(dirname(__FILE__).'/feature/forum_feature.php');

/**
 * Represents a forum. This class contains:
 * 1. A constructor and methods for handling information about a specific forum,
 *    such as obtaining a list of discussions.
 * 2. Static methods related to multiple forums across the course or site, or
 *    to forums in general.
 * @see forum_discussion_list
 * @see forum_discussion
 * @see forum_post
 * @package forumng
 * @author sam marshall
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @copyright Copyright 2008 The Open University
 */
class forum {

    // Constants
    ////////////

    /** Subscription: Nobody is allowed to subscribe to the forum. */
    const SUBSCRIPTION_NOT_PERMITTED = 0;
    /** Subscription: Anyone who can see the forum can choose to subscribe to it. */
    const SUBSCRIPTION_PERMITTED = 1;
    /** Subscription: Anybody who can see the forum can choose to subscribe to it,
        and users with certain roles are automatically subscribed (but can
        unsubscribe). */
    const SUBSCRIPTION_INITIALLY_SUBSCRIBED = 2;
    /** Subscription: Anyone who can see the forum can choose to subscribe to it.
        and users with certain roles are forced to be subscribed (and cannot
        unsubsribe). */
    const SUBSCRIPTION_FORCED = 3;

    /** NOT_SUBSCRIBED, PARTIALLY_SUBSCRIBED and FULLY_SUBSCRIBED are only used in a none group mode or all group mode
     *  FULLY_SUBSCRIBED_GROUPMODE (view a group page when fully subscribed),
        THIS_GROUP_PARTIALLY_SUBSCRIBED(subscribed some discussions in this group), THIS_GROUP_SUBSCRIBED,
        THIS_GROUP_NOT_SUBSCRIBED are only used in individual group mode.*/
    const NOT_SUBSCRIBED = 0;
    const PARTIALLY_SUBSCRIBED = 1;
    const FULLY_SUBSCRIBED = 2;
    const FULLY_SUBSCRIBED_GROUPMODE = 3;
    const THIS_GROUP_PARTIALLY_SUBSCRIBED = 4;
    const THIS_GROUP_SUBSCRIBED = 5;
    const THIS_GROUP_NOT_SUBSCRIBED = 6;

    /** Grading: No grade for this activity. */
    const GRADING_NONE = 0;
    /** Grading: Average of ratings. */
    const GRADING_AVERAGE = 1;
    /** Grading: Count of ratings. */
    const GRADING_COUNT = 2;
    /** Grading: Max rating. */
    const GRADING_MAX = 3;
    /** Grading: Min rating. */
    const GRADING_MIN = 4;
    /** Grading: Sum of ratings. */
    const GRADING_SUM = 5;

    /** Feed type: No feeds provided. */
    const FEEDTYPE_NONE = 0;
    /** Feed type: Feed contains only the posts that start discussions. */
    const FEEDTYPE_DISCUSSIONS = 1;
    /** Feed type: Feed contains all forum posts. */
    const FEEDTYPE_ALL_POSTS = 2;

    /** Feed format: Atom */
    const FEEDFORMAT_ATOM = 1;
    /** Feed format: RSS */
    const FEEDFORMAT_RSS = 2;

    /** Mail state: Post not mailed yet. */
    const MAILSTATE_NOT_MAILED = 0;
    /** Mail state: Post not mailed (and is set to mail now). */
    const MAILSTATE_NOW_NOT_MAILED = 4;
    /** Mail state: Post already mailed. */
    const MAILSTATE_MAILED = 1;
    /** Mail state: Post sent in digests. */
    const MAILSTATE_DIGESTED = 2;

    /** Constant referring to posts from all groups. */
    const ALL_GROUPS = null;

    /**
     * Special constant indicating that groups are not used (does not apply
     * to posts).
     */
    const NO_GROUPS = -1;

    /** Discussion sort: by date. */
    const SORT_DATE = 0;
    /** Discussion sort: by subject. */
    const SORT_SUBJECT = 1;
    /** Discussion sort: by author. */
    const SORT_AUTHOR = 2;
    /** Discussion sort: by replies. */
    const SORT_POSTS = 3;
    /** Discussion sort: by unread replies. */
    const SORT_UNREAD = 4;
    /** Discussion sort: by group. */
    const SORT_GROUP = 5;

    /** Obtain no unread info */
    const UNREAD_NONE = 0;
    /** Obtain binary (yes there are unread messages) unread info */
    const UNREAD_BINARY = 1;
    /** Obtain the count of unread discussions */
    const UNREAD_DISCUSSIONS = 2;

    /** Length in characters of intro when abbreviated for index page etc */
    const INTRO_ABBREVIATED_LENGTH = 200;

    /** Constant used if there is no post quota in effect */
    const QUOTA_DOES_NOT_APPLY = -1;

    /** Link constant: HTML link (&amp;) */
    const PARAM_HTML = 1;
    /** Link constant: standard link (&) */
    const PARAM_PLAIN = 2;
    /** Link constant: HTML form input fields */
    const PARAM_FORM = 3;
    /** Link bitfield: HTML link (&amp;) with 'guess' for clone */
    const PARAM_UNKNOWNCLONE = 16;

    /**
     * Special parameter used when requesting a forum 'directly' from a course
     * (so that we know it will either have no clone id, or the clone id will
     * be the same as the cmid).
     */
    const CLONE_DIRECT = -1;
    /**
     * Special parameter used when requesting a forum in a situation where we
     * do not know what is the appropriate clone to use. In that case the
     * system will 'guess' based on the user's access permissions
     */
    const CLONE_GUESS = -2;

    // Static methods
    /////////////////

    /**
     * Obtains list of available per-forum subscription type options.
     * @return array Array from subscription constant (integer) => description
     *   in current language
     */
    public static function get_subscription_options() {
        return array(
            self::SUBSCRIPTION_PERMITTED =>
                get_string('subscription_permitted', 'forumng'),
            self::SUBSCRIPTION_FORCED =>
                get_string('subscription_forced', 'forumng'),
            self::SUBSCRIPTION_INITIALLY_SUBSCRIBED =>
                get_string('subscription_initially_subscribed', 'forumng'),
            self::SUBSCRIPTION_NOT_PERMITTED =>
                get_string('subscription_not_permitted','forumng'));
    }

    /**
     * Obtains list of available per-forum feed type options.
     * @return array Array from feedtype constant (integer) => description
     *   in current language
     */
    public static function get_feedtype_options() {
        return array(
            self::FEEDTYPE_NONE=>get_string('feedtype_none', 'forumng'),
            self::FEEDTYPE_DISCUSSIONS=>get_string('feedtype_discussions', 'forumng'),
            self::FEEDTYPE_ALL_POSTS=>get_string('feedtype_all_posts', 'forumng')
        );
    }

    /**
     * Obtains list of available per-forum feed item count options.
     * @return array Array from feed item value (integer) => description
     *   in current language (probably just the same integer)
     */
    public static function get_feeditems_options() {
        return array(
            1=>1,
            2=>2,
            3=>3,
            4=>4,
            5=>5,
            10=>10,
            15=>15,
            20=>20,
            25=>25,
            30=>30,
            40=>40,
            50=>50);
    }

    /**
     * @param bool $midsentence True if the result is being used in the middle
     *   of a sentence (then we use 'day' rather than '1 day')
     * @return array Array of available post-period options (keys) to the text
     *   versions of those options (values).
     */
    public static function get_max_posts_period_options($midsentence = false) {
        $options = array();
        $options[60*60*24] = ($midsentence ? '' : '1 ') . get_string('day');
        $options[60*60*24*2] = '2 '.get_string('days');
        $options[60*60*24*7] = '7 '.get_string('days');
        $options[60*60*24*14] = '14 '.get_string('days');
        return $options;
    }

    /**
     * @param bool $text True if we want in text format not number
     * @param bool $midsentence True if the result is being used in the middle
     *   of a sentence (then we use 'day' rather than '1 day')
     * @return mixed The number (seconds) or text description of the max-posts
     *   period of the current foru (only valid if there is one)
     */
    public function get_max_posts_period($text = false, $midsentence = false) {
        if ($text) {
            $options = self::get_max_posts_period_options($midsentence);
            return $options[$this->forumfields->maxpostsperiod];
        } else {
            return $this->forumfields->maxpostsperiod;
        }
    }

    /**
     * @return array Array of grading option => description
     */
    public static function get_grading_options() {
        return array (
            self::GRADING_NONE => get_string('grading_none', 'forumng'),
            self::GRADING_AVERAGE => get_string('grading_average', 'forumng'),
            self::GRADING_COUNT => get_string('grading_count', 'forumng'),
            self::GRADING_MAX => get_string('grading_max', 'forumng'),
            self::GRADING_MIN => get_string('grading_min', 'forumng'),
            self::GRADING_SUM => get_string('grading_sum', 'forumng'));
    }

    /** @return bool True if read-tracking is enabled */
    public static function enabled_read_tracking() {
        global $CFG;
        return $CFG->forumng_trackreadposts ? true : false;
    }

    /** @return int Number of days that read-tracking data is kept for */
    public static function get_read_tracking_days() {
        global $CFG;
        return $CFG->forumng_readafterdays;
    }

    /** @return int The oldest time (seconds since epoch) for which
     *     read-tracking data should be kept */
    public static function get_read_tracking_deadline() {
        return time()-self::get_read_tracking_days()*24*3600;
    }

    /**
     * @return bool True if the current user has the option selected to
     *   automatically mark discussions as read
     */
    public static function mark_read_automatically() {
        return !get_user_preferences('forumng_manualmark', '0');
    }

    /**
     * @param int $sort SORT_xx constant
     * @return string 'Sort by xxx' text in current language
     */
    static function get_sort_title($sort) {
        return get_string('sortby', 'forumng', forum::get_sort_field($sort));
    }

    /**
     * @param int $sort SORT_xx constant
     * @return string Title (in lower-case) of the field in current language
     */
    static function get_sort_field($sort) {
        switch ($sort) {
        case self::SORT_DATE:
            return get_string('lastpost', 'forumng');
        case self::SORT_SUBJECT:
            return get_string('discussion', 'forumng');
        case self::SORT_AUTHOR:
            return get_string('startedby', 'forumng');
        case self::SORT_POSTS:
            return get_string('posts', 'forumng');
        case self::SORT_UNREAD:
            return get_string('unread', 'forumng');
        case self::SORT_GROUP:
            return get_string('group', 'forumng');
        default:
            throw new forum_exception("Unknown sort constant: $sort");
        }
    }

    /**
     * @param int $sort SORT_xx constant
     * @return string Letter used to identify this sort type
     */
    static function get_sort_letter($sort) {
        switch ($sort) {
        case self::SORT_DATE: return 'd';
        case self::SORT_SUBJECT: return 's';
        case self::SORT_AUTHOR: return 'a';
        case self::SORT_POSTS: return 'p';
        case self::SORT_UNREAD: return 'u';
        case self::SORT_GROUP: return 'g';
        default:
            throw new forum_exception("Unknown sort constant: $sort");
        }
    }

    /**
     * @param string $letter Letter used to identify sort type
     * @return int SORT_xx constant
     */
    static function get_sort_code($letter) {
        switch ($letter) {
        case 'd' : return self::SORT_DATE;
        case 's' : return self::SORT_SUBJECT;
        case 'a' : return self::SORT_AUTHOR;
        case 'p' : return self::SORT_POSTS;
        case 'u' : return self::SORT_UNREAD;
        case 'g' : return self::SORT_GROUP;
        default:
            throw new forum_exception("Unknown sort letter: $letter");
        }
    }

    /**
     * Obtains currently selected group for an activity, in the format that
     * forum methods want. (Which is slightly different to standard Moodle.)
     * @param object $cm Course-module
     * @param bool $update If true, updates group based on URL parameter
     * @return int Group ID; ALL_GROUPS if all groups; NO_GROUPS if no groups used
     */
    static function get_activity_group($cm, $update=false) {
        $result = groups_get_activity_group($cm, $update);
        if($result === false) {
            return forum::NO_GROUPS;
        } else if($result === 0) {
            return forum::ALL_GROUPS;
        } else {
            return $result;
        }
    }

    /**
     * Obtains the forum type based on its 'info' object in modinfo (e.g. from
     * $modinfo->instances['forumng'][1234]). Usually this comes from a CSS
     * class inserted in the 'extra' field.
     * <p>
     * (To be honest the CSS class is not needed, it is mostly there as it's
     * the only place we could safely throw in random information so that we
     * can get this data without making extra queries!)
     * @param object $info Info object
     * @return string Forum type
     */
    private static function get_type_from_modinfo_info($info) {#
        if (isset($info->forumtype)) {
            // Only set when using get_modinfo_special for shared activity
            // modules
            return $info->forumtype;
        }
        return str_replace('"', '',
                str_replace('class="forumng-type-', '', $info->extra));
    }

    /**
     * This special function is required only because of the OU shared
     * activities system. On the shared activities course, modinfo is not
     * available. We can provide a fake version, but only if specific IDs
     * are given
     * @param object $course Moodle course object
     * @param array $specificids List of course-module IDs; empty array = all
     * @return object Moodle modinfo object
     * @throws forum_exception If this is shared activities course and you're
     *   trying to list all forums on it
     */
    private static function get_modinfo_special($course, $specificids=array()) {
        global $CFG, $FORUMNG_CACHE;
        $modinfo = get_fast_modinfo($course);
        if (class_exists('ouflags') && !count($modinfo->cms)) {
            // OU shared activities system requires a hack here so that this
            // can work on the shared activities course, which doesn't have
            // modinfo. It can only work if specific IDs are listed.
            if (count($specificids)) {
                if (!empty($FORUMNG_CACHE->modinfo_special) &&
                        $FORUMNG_CACHE->modinfo_special->specificids == $specificids) {
                    return $FORUMNG_CACHE->modinfo_special->modinfo;
                }
                $inorequals = forum_utils::in_or_equals($specificids);
                $modinfo->cms = forum_utils::get_records_sql("
SELECT
    cm.*, m.name AS modname, f.type AS forumtype
FROM
    {$CFG->prefix}course_modules cm
    INNER JOIN {$CFG->prefix}modules m ON m.id = cm.module
    INNER JOIN {$CFG->prefix}forumng f ON f.id = cm.instance
WHERE
    cm.id $inorequals AND m.name='forumng'");
                $modinfo->instances['forumng'] = $modinfo->cms;
                $FORUMNG_CACHE->modinfo_special->modinfo = $modinfo;
                $FORUMNG_CACHE->modinfo_special->specificids = $specificids;
            } else {
                throw new forum_exception('Cannot get_course_forums on ' .
                        'shared activities course without specific ID list');
            }
        }
        return $modinfo;
    }

    // Object variables and accessors
    /////////////////////////////////

    private $course, $cm, $context, $clonecourse, $clonecm, $clonecontext,
            $forumfields, $type, $cache;

    /** @return bool True if ratings are enabled */
    public function has_ratings() {
        return $this->forumfields->ratingscale!=0;
    }

    /**
     * @param int $created Date that post was created; use 0 to obtain
     *   a 'general' value supposing that posts are in range
     * @return bool True if current user can rate a post in this forum
     */
    public function can_rate($created=0) {
        return $this->has_ratings()
            && ($created == 0 || $created > $this->forumfields->ratingfrom)
            && ($created == 0 || $this->forumfields->ratinguntil==0
                || $created<$this->forumfields->ratinguntil)
            && has_capability('mod/forumng:rate', $this->get_context());
    }

    /** @return int ID of course that contains this forum */
    public function get_course_id() {
        return $this->forumfields->course;
    }

    /**
     * Obtains course object. For non-shared forums this is
     * straightforward. For shared forums this usually returns the course
     * of the *clone* forum that is currently relevant, not directly of the
     * original forum.
     * @param bool $forcereal If set, always returns the course of the
     *   original forum and not of any clone
     * @return object Course object
     */
    public function get_course($forcereal = false) {
        if ($this->is_shared() && !$forcereal) {
            if (!$this->clonecourse) {
                $cm = $this->get_course_module();
                $this->clonecourse = get_record('course', 'id', $cm->course);
                if (!$this->clonecourse) {
                    throw new forum_exception('Cannot find clone course ' .
                            $cm->course);
                }
            }
            return $this->clonecourse;
        }
        return $this->course;
    }

    /**
     * Obtains course-module id. For non-shared forums this is
     * straightforward. For shared forums this usually returns the id
     * of the *clone* forum that is currently relevant, not directly of the
     * original forum.
     * @param bool $forcereal If set, always returns the id of the
     *   original forum and not of any clone
     * @return int ID of course-module instance
     */
    public function get_course_module_id($forcereal = false) {
        return $this->get_course_module($forcereal)->id;
    }

    /**
     * Obtains course-module instance. For non-shared forums this is
     * straightforward. For shared forums this usually returns the course-module
     * of the *clone* forum that is currently relevant, not directly of the
     * original forum.
     * @param bool $forcereal If set, always returns the course-module of the
     *   original forum and not of any clone
     * @return object Course-module instance
     */
    public function get_course_module($forcereal = false) {
        global $CFG, $SESSION;
        if(empty($this->cm)) {
            throw new forum_exception('Course-module not set for this forum');
        }
        if ($this->is_shared() && !$forcereal) {
            if (!$this->clonecm) {
                throw new forum_exception('Clone reference not defined');
            }
            return $this->clonecm;
        }
        return $this->cm;
    }

    /**
     * Retrieves contexts for all the clones of this forum. (If any.)
     * @return array Array of context objects (each one has an extra ->courseid,
     *   ->courseshortname, and ->forumname) for clones of this forum
     */
    public function get_clone_contexts() {
        global $CFG;
        $contexts = get_records_sql("
SELECT
    x.*, c.id AS courseid, c.shortname AS courseshortname, f.name AS forumname
FROM
    {$CFG->prefix}forumng f
    INNER JOIN {$CFG->prefix}course_modules cm ON f.id = cm.instance
    INNER JOIN {$CFG->prefix}course c ON cm.course = c.id
    INNER JOIN {$CFG->prefix}modules m ON cm.module = m.id
    INNER JOIN {$CFG->prefix}context x ON x.instanceid = cm.id
WHERE
    f.originalcmid = {$this->cm->id}
    AND m.name = 'forumng'
    AND x.contextlevel = 70
ORDER BY
    c.shortname, f.name");
        return $contexts ? $contexts : array();
    }
    
    /**
     * Sets up the clone reference. The clone reference is used for shared
     * forums only. If a forum is a shared forum, you can access it from several
     * different course-module instances. The id of these instances is known as
     * the 'clone id'. We store the clone course-module in the forum object
     * so that when displaying links etc., these can retain the clone
     * information.
     * @param int $cloneid Clone id
     * @param object $clonecourse Optional clone course object (improves
     *   performance in cases where it needs to get the cm entry)
     */
    public function set_clone_reference($cloneid, $clonecourse=null) {
        global $SESSION, $CFG;
        if ($cloneid == $this->cm->id || $cloneid == self::CLONE_DIRECT) {
            $this->clonecm = $this->cm;
            return;
        }
        if ($cloneid == self::CLONE_GUESS) {
            // We had better cache guesses in session because this is
            // time-consuming
            if (!isset($SESSION->forumng_cache)) {
                $SESSION->forumng_cache = new stdClass;
            }
            if (!isset($SESSION->forumng_cache->guesses)) {
                $SESSION->forumng_cache->guesses = array();
            }
            if (isset($SESSION->forumng_cache->guesses[$this->get_id()])) {
                return $SESSION->forumng_cache->guesses[$this->get_id()];
            }
            // Okay, no cached guess. First let's see if they can write to the
            // original forum because if so let's just use that
            if (has_capability('mod/forumng:replypost', $this->get_context(true))) {
                $this->clonecm = $this->cm;
                return;
            }

            // See if they can write to any context
            $contexts = $this->get_clone_contexts();
            foreach ($contexts as $context) {
                if (has_capability('mod/forumng:replypost', $context)) {
                    $this->clonecm = self::get_modinfo_cm(
                            $context->instanceid);
                    break;
                }
            }

            // No? Well see if they can read to one
            if (!$this->clonecm) {
                if (has_capability('moodle/course:view', $context)) {
                    $this->clonecm = self::get_modinfo_cm($context->instanceid);
                    break;
                }
            }

            // Default, just use original
            if (!$this->clonecm) {
                $this->clonecm = $this->cm;
            }

            // Cache guess
            $SESSION->forumng_cache->guesses[$this->get_id()] = $this->clonecm;
            return;
        } else {
            // Get course-module record
            $this->clonecm = self::get_modinfo_cm($cloneid);
            // Security check that specifed cm is indeed a clone of this forum
            if (get_field('forumng', 'originalcmid', 'id',
                    $this->clonecm->instance) != $this->cm->id) {
                throw new forum_exception("Not a clone of this forum: $cloneid");
            }
        }
    }

    /**
     * Gets a course-module object using get_fast_modinfo (so that it includes
     * additional data not in the actual table).
     * @param int $cmid ID of course-module
     * @param object $course Optional $course object to improve performance
     * @return Course-module object
     * @throws forum_exception If the cm isn't found or not in that course
     */
    private static function get_modinfo_cm($cmid, $course=null) {
        global $CFG;
        if (!$course) {
            $course = forum_utils::get_record_sql("
SELECT
    c.*
FROM
    {$CFG->prefix}course_modules cm
    INNER JOIN {$CFG->prefix}course c ON c.id = cm.course
WHERE
    cm.id = $cmid");
        }
        $modinfo = get_fast_modinfo($course);
        if (!array_key_exists($cmid, $modinfo->cms)) {
            throw new forum_exception(
                    "Course $course->id does not contain cm $cmid");
        }
        return $modinfo->cms[$cmid];
    }

    /**
     * Obtains context object. For non-shared forums this is
     * straightforward. For shared forums this usually returns the context
     * of the *clone* forum that is currently relevant, not directly of the
     * original forum.
     * @param bool $forcereal If set, always returns the context of the
     *   original forum and not of any clone
     * @return object Context object
     */
    public function get_context($forcereal = false) {
        if ($this->is_shared() && !$forcereal) {
            if (!$this->clonecontext) {
                $this->clonecontext = get_context_instance(CONTEXT_MODULE,
                    $this->get_course_module_id());
            }
            return $this->clonecontext;
        }
        return $this->context;
    }

    /** @return int ID of this forum */
    public function get_id() {
        return $this->forumfields->id;
    }

    /** @return Name of forum */
    public function get_name() {
        return $this->forumfields->name;
    }

    /** @return reporting email of form */
    public function get_reportingemail() {
        return $this->forumfields->reportingemail;
    }

    /** @return posting from of form */
    public function get_postingfrom() {
        return $this->forumfields->postingfrom;
    }
    /** @return posting until of form */
    public function get_postinguntil() {
        return $this->forumfields->postinguntil;
    }

    /**
     * @param $abbreviated If true, cuts down the length
     * @return string Intro text
     */
    public function get_intro($abbreviated=false) {
        if($abbreviated) {
            return shorten_text($this->forumfields->intro, self::INTRO_ABBREVIATED_LENGTH);
        } else {
            return $this->forumfields->intro;
        }
    }

    /** @return int GRADING_xx constant */
    public function get_grading() {
        return $this->forumfields->grading;
    }

    /**
     * @return int Scale used for ratings; 0 = disable,
     *   positive integer = 0..N scale, negative integer = defined scale
     */
    public function get_rating_scale() {
        return $this->forumfields->ratingscale;
    }

    /**
     * @return array Array (in choose_from_menu format) of available rating
     *   options as value=>text
     */
    public function get_rating_options() {
        return forum_utils::make_grades_menu($this->forumfields->ratingscale);
    }

    /**
     * @return int Number of ratings a post must have in order to 'count'
     */
    public function get_rating_threshold() {
        return $this->forumfields->ratingthreshold;
    }

    /**
     * @return bool True if this forum is shared (has the 'allow sharing' flag
     *   set)
     */
    public function is_shared() {
        return $this->forumfields->shared ? true : false;
    }

    /**
     * @return bool True if this forum is a clone (has the 'original cmid'
     *   value set)
     */
    public function is_clone() {
        return $this->forumfields->originalcmid != null;
    }

    /**
     * If this forum is a clone, obtains the real one; otherwise just returns
     * this again.
     * @return forum Forum object (same or different)
     */
    public function get_real_forum() {
        if ($this->is_clone()) {
            return forum::get_from_cmid($this->forumfields->originalcmid, $this->cm->id);
        } else {
            return $this;
        }
    }
    /**
     * @return int Number of discussions containing unread posts
     */
    public function get_num_unread_discussions() {
        if(!isset($this->forumfields->numunreaddiscussions)) {
            throw new forum_exception('Unread discussion count not obtained');
        }
        return $this->forumfields->numunreaddiscussions;
    }

    /**
     * @return int Number of discussions
     */
    public function get_num_discussions() {
        if(!isset($this->forumfields->numdiscussions)) {
            throw new forum_exception('Discussion count not obtained');
        }
        return $this->forumfields->numdiscussions;
    }

    /**
     * @return bool True if any discussions have unread posts
     */
    public function has_unread_discussions() {
        if(isset($this->forumfields->numunreaddiscussions)) {
            return $this->forumfields->numunreaddiscussions > 0;
        } else if(isset($this->forumfields->hasunreaddiscussions)) {
            return $this->forumfields->hasunreaddiscussions > 0;
        } else {
            throw new forum_exception('Unread discussion flag not obtained');
        }
    }

    /**
     * Gets a Moodle upload manager for forum attachments
     * @param $field Field name or leave blank for all
     * @return upload_manager Upload manager set up with this forum's options
     */
    function get_upload_manager($field = '') {
        $maxbytes = $this->forumfields->attachmentmaxbytes;
        return new upload_manager($field, false, false,
            $this->get_course(), false, $maxbytes, true, true);
    }

    /**
     * @return int Activity group mode; may be VISIBLEGROUPS, SEPARATEGROUPS, or 0
     */
    public function get_group_mode() {
        if($this->forumfields->shared) {
            // Performance up: shared forums never have groups
            return 0;
        }
        return groups_get_activity_groupmode($this->get_course_module(),
            $this->get_course());
    }

    /**
     * @return int Grouping in use for this activity; 0 for 'all groupings'
     *   or if groupings are disabled
     */
    public function get_grouping() {
        global $CFG;
        if ($CFG->enablegroupings) {
            return $this->get_course_module()->groupingid;
        } else {
            return 0;
        }
    }

    /** @return bool True if either site level or forum level reporting email is not null */
    public function has_reporting_email() {
        global $CFG;
        return $this->forumfields->reportingemail!= null ||
            (!empty($CFG->forumng_reportunacceptable) && validate_email($CFG->forumng_reportunacceptable));
    }

    /**
     * Use to obtain link parameters when linking to any page that has anything
     * to do with forums.
     * @return string e.g. 'id=1234'
     */
    public function get_link_params($type) {
        if ($type == forum::PARAM_FORM) {
            $id = '<input type="hidden" name="id" value="' . $this->cm->id . '" />';
        } else {
            $id = 'id=' . $this->cm->id;
        }
        return $id . $this->get_clone_param($type);
    }

    /**
     * Use to obtain link parameters as an array instead of as a string.
     * @return array e.g. ('id'=>123)
     */
    public function get_link_params_array() {
        $result = array('id' => $this->cm->id);
        $this->add_clone_param_array($result);
        return $result;
    }

    /**
     * Adds the clone parameter to an array of parameters, if it is necessary.
     * @param array $result Array that may have key 'clone' set
     */
    public function add_clone_param_array($result) {
        if ($this->is_shared()) {
            $result['clone'] = $this->get_course_module_id();
        }
    }

    /**
     * @param int $type PARAMS_xx constant
     * @return string Full URL to this forum
     */
    public function get_url($type) {
        global $CFG;
        return $CFG->wwwroot . '/mod/forumng/view.php?' .
                $this->get_link_params($type);
    }

    /**
     * @param int $type Parameter type (whether you want it escaped or not)
     * @return Either empty string or some variant of '&clone=N'
     */
    public function get_clone_param($type) {
        if (!$this->is_shared()) {
            return '';
        }
        if ($type & forum::PARAM_UNKNOWNCLONE) {
            $cloneid = -2; // Special 'guess' vale
        } else {
            $cloneid = $this->get_course_module_id();
        }

        if ($type == forum::PARAM_FORM) {
            return '<input type="hidden" name="clone" value="' .
                    $cloneid . '" />';
        }
        if (($type & 0xf) == forum::PARAM_HTML) {
            $params = '&amp;';
        } else {
            $params = '&';
        }
        return $params . 'clone=' . $cloneid;
    }

    // Factory methods
    //////////////////

    /**
     * Creates a forum object and all related data from a single forum ID.
     * Note this is a forum ID and not a course-module ID.
     * @param int $id ID of forum
     * @param int $cloneid Clone identifier (0 if not a shared forum) or
     *   CLONE_DIRECT constant
     * @param bool $requirecm True if we require that the forum object
     *   has a valid course-module and context; false if the forum has only
     *   just been created so it doesn't have one yet
     * @return forum Forum object
     */
    public static function get_from_id($id, $cloneid, $requirecm=true) {
        global $COURSE;

        // Note that I experimented with code that retrieved this information
        // in a single query with some joins. It turned out to be fractionally
        // slower when working on a single machine, and only fractionally faster
        // when the database was on a separate machine, so we decided it wasn't
        // worth the maintenance effort over single queries.

        // Get forum data
        $forumfields = forum_utils::get_record('forumng', 'id', $id);

        // Get course
        $courseid = $forumfields->course;
        if(!empty($COURSE->id) && $COURSE->id === $courseid) {
            $course = $COURSE;
        } else {
            $course = forum_utils::get_record('course', 'id', $courseid);
        }

        // NOTE: We obtain $cm via get_fast_modinfo. Reasons to do it this way:
        // * Modinfo has already been loaded since it comes from course table.
        // * The PHP loop search could be slow if there are many activities,
        //   but there would have to be quite a lot to make it slower than
        //   2 additional database queries (note: I did not performance-test
        //   this assumption).
        // * Other parts of the page might require the full $cm info that is
        //   only provided by get_fast_modinfo, so may as well call it now.
        $cm = null;
        $modinfo = get_fast_modinfo($course);
        foreach($modinfo->cms as $possiblecm) {
            if($possiblecm->instance==$id && $possiblecm->modname==='forumng') {
                $cm = $possiblecm;
                break;
            }
        }
        if(!$cm && $requirecm) {
            // Just in case this is because the forum has only just been
            // created
            $cm = get_coursemodule_from_instance('forumng', $id, $course->id);
            if(!$cm) {
                throw new forum_exception(
                    "Couldn't find matching course-module entry for forum $id");
            }
        }

        // Get context
        $context = null;
        if($cm) {
            $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        }

        // Construct forum
        $result = new forum($course, $cm, $context, $forumfields);
        if ($result->is_shared()) {
            if (!$cloneid) {
                throw new forum_exception(
                    "Shared forum {$cm->id} requires a clone id");
            }
            // This is not available when forum was only just created, so
            // don't call it
            if ($cm) {
                $result->set_clone_reference($cloneid);
            }
        }
        return $result;
    }

    /**
     * Creates a forum object and all related data from a single course-module
     * ID. Intended to be used from pages that refer to a particular forum.
     * @param int $cmid Course-module ID of forum
     * @param int $cloneid Clone identifier (0 if not a shared forum) or
     *   CLONE_DIRECT constant
     * @return forum Forum object
     */
    public static function get_from_cmid($cmid, $cloneid) {
        global $COURSE;

        // Get modinfo for current course, because we usually already have it
        $modinfo = get_fast_modinfo($COURSE);
        if(array_key_exists($cmid, $modinfo->cms)) {
            // It's in the current course, no need for another query
            $courseid = $COURSE->id;
        } else {
            // Get courseid
            $courseid = forum_utils::get_field('course_modules', 'course', 'id', $cmid);
        }

        // Get course
        if(!empty($COURSE->id) && $COURSE->id === $courseid) {
            $course = $COURSE;
        } else {
            $course = forum_utils::get_record('course', 'id', $courseid);
        }

        // Get course-module
        $modinfo = forum::get_fast_modinfo($course, $cmid);
        if(!array_key_exists($cmid, $modinfo->cms)) {
            throw new forum_exception(
                "Couldn't find forum with course-module ID $cmid");
        }
        $cm = $modinfo->cms[$cmid];
        if($cm->modname != 'forumng') {
            throw new forum_exception(
                "Course-module ID $cmid is not a forum");
        }

        // Get forum data
        $forumfields = forum_utils::get_record('forumng', 'id', $cm->instance);

        // Get context
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);

        // Construct forum
        $result = new forum($course, $cm, $context, $forumfields);
        if ($result->is_shared()) {
            if (!$cloneid) {
                throw new forum_exception(
                    "Shared forum $cmid requires a clone id");
            }
            $result->set_clone_reference($cloneid);
        }
        return $result;
    }

    // Object methods
    /////////////////

    /**
     * Construct the forum's in-memory representation.
     * @param object $course Moodle course object. Optionally, can include only
     *   the 'id' field. (Otherwise should include all fields.)
     * @param object $cm Moodle course-module object. TODO Document required fields
     * @param object $forumfields Moodle forumng table record. Should include all fields.
     */
    function __construct($course, $cm, $context, $forumfields) {
        $this->course = $course;
        $this->cm = $cm;
        $this->context = $context;
        $this->forumfields = $forumfields;
        $this->cache = new StdClass;
    }

    /**
     * Called by add_instance when the forum has just been created.
     * Note that $cm and $context are unavailable.
     * @param string $idnumber ID-number from create form
     */
    function created($idnumber) {
        // Set up grade item if required
        $this->update_grades(0, $idnumber);

        // TODO Perform any initialisation required by forum type (single
        // discussion = create discussion)
    }

    /**
     * Called by update_instance when the forum has been updated.
     * @param $previousfields Previous copy of forum record
     */
    function updated($previousfields) {
        // If rating scale or grading on/off changes, we need to update
        // the grade information
        $gradechanged = false;
        if($previousfields->grading != $this->forumfields->grading ||
            $previousfields->ratingscale != $this->forumfields->ratingscale) {
            $this->update_grades();
        }

        // TODO Call forum type for additional handling

        // If name changes and this is a shared forum, we need to go change
        // all the clones
        if ($previousfields->name !== $this->forumfields->name &&
            $this->is_shared()) {
            // Get clones
            $clones = get_records(
                    'forumng', 'originalcmid', $this->get_course_module_id());
            $clones = $clones ? $clones : array();
            foreach($clones as $clone) {
                set_field('forumng', 'name', addslashes($this->forumfields->name),
                        'id', $clone->id);
                rebuild_course_cache($clone->course, true);
            }
        }
    }

    /**
     * Called by delete_instance. Deletes all the forum's data (but
     * not the actual forum record, delete_instance handles that).
     */
    function delete_all_data() {
        global $CFG;

        // Delete per-post data
        $postquery = "
SELECT
    fp.id
FROM
    {$CFG->prefix}forumng_discussions fd
    INNER JOIN {$CFG->prefix}forumng_posts fp on fp.discussionid=fd.id
WHERE
    fd.forumid = {$this->forumfields->id}";
        execute_sql("DELETE FROM {$CFG->prefix}forumng_ratings
            WHERE postid IN ($postquery)", false);

        // Delete per-discussion data
        $discussionquery = "SELECT id FROM {$CFG->prefix}forumng_discussions
            WHERE forumid = {$this->forumfields->id}";
        execute_sql("DELETE FROM {$CFG->prefix}forumng_read
            WHERE discussionid IN ($discussionquery)", false);
        execute_sql("DELETE FROM {$CFG->prefix}forumng_posts
            WHERE discussionid IN ($discussionquery)", false);

        // Delete per-forum data
        delete_records('forumng_subscriptions', 'forumid', $this->forumfields->id);
        delete_records('forumng_discussions', 'forumid', $this->forumfields->id);
    }

    /**
     * Records an action in the Moodle log for current user.
     * @param string $action Action name - see datalib.php for suggested verbs
     *   and this code for example usage
     * @param string $replaceinfo Optional info text to replace default (which
     *   is just the forum id again)
     */
    function log($action, $replaceinfo = '') {
        $info = $this->forumfields->id;
        if ($replaceinfo !== '') {
            $info = $replaceinfo;
        }
        add_to_log($this->get_course_id(), 'forumng',
            $action, $this->get_log_url(), $info,
            $this->get_course_module_id());
    }

    /**
     * @return string URL of this discussion for log table, relative to the
     *   module's URL
     */
    function get_log_url() {
        return 'view.php?' . $this->get_link_params(forum::PARAM_PLAIN);
    }

    /**
     * Retrieves a list of discussions.
     * @param int $groupid Group ID or ALL_GROUPS
     * @param bool $viewhidden True if user can view hidden discussions
     * @param int $page Page to retrieve (1 = first page)
     * @param int $sort Sort order (SORT_xx constant)
     * @param bool $sortreverse Reverses the chosen sort
     * @param int $userid User ID, 0 = default, -1 if unread count not required
     * @return forum_discussion_list
     */
    public function get_discussion_list(
        $groupid=self::ALL_GROUPS, $viewhidden=false, $viewdeleted=false,
        $page=1, $sort=self::SORT_DATE, $sortreverse=false, $userid=0, $ignoreinvalidpage=true) {
        global $CFG;

        // Build list of SQL conditions
        ///////////////////////////////

        // Correct forum, not deleted.
        $conditions ="fd.forumid={$this->forumfields->id}";
        if (!$viewdeleted) {
            $conditions .= " AND fd.deleted=0";
        }

        // Group restriction
        if ($groupid) {
            $conditions .= " AND (fd.groupid=$groupid OR fd.groupid IS NULL)";
        }

        // View hidden posts
        if (!$viewhidden) {
            $now = time();
            $conditions .= " AND (fd.timestart=0 OR fd.timestart <= $now)".
              " AND (fd.timeend=0 OR fd.timeend > $now)";
        }

        // Count all discussions
        ////////////////////////

        // Get count
        $count = count_records_sql(
            "SELECT COUNT(1) FROM {$CFG->prefix}forumng_discussions fd WHERE ".
            $conditions);

        // Check page index makes sense
        $pagecount = ceil($count / $CFG->forumng_discussionsperpage);
        if ($pagecount < 1) {
            $pagecount = 1;
        }
        if (($page > $pagecount || $page < 1) ) {
            if ($ignoreinvalidpage) {
                $page = 1;
            } else {
                throw new forum_exception("Invalid page $page, expecting 1..$pagecount");
            }
        }

        // Special case for no results
        if ($count == 0) {
            return new forum_discussion_list($page, $pagecount, $count);
        }

        // Retrieve selected discussions
        ////////////////////////////////

        // Ordering
        $orderby = 'sticky DESC';
        switch ($sort) {
            case self::SORT_DATE:
                $orderby .= ', timemodified DESC';
                break;
            case self::SORT_SUBJECT:
                $orderby .= ', subject ASC';
                break;
            case self::SORT_AUTHOR:
                // This logic is based on code in fullname().
                $override = has_capability('moodle/site:viewfullnames',
                    $this->get_context(), $userid);
                if ($CFG->fullnamedisplay == 'firstname lastname' ||
                    ($override && $CFG->fullnamedisplay == 'firstname')) {
                    $orderby .= ', fu_firstname ASC, fu_lastname ASC';
                } else if ($CFG->fullnamedisplay == 'lastname firstname') {
                    $orderby .= ', fu_lastname ASC, fu_firstname ASC';
                } else if ($CFG->fullnamedisplay == 'firstname') {
                    $orderby .= ', fu_firstname ASC';
                }
                if (!$override) {
                    if (!empty($CFG->forcefirstname)) {
                        $orderby = preg_replace('~, fu_firstname(ASC)?~', '', $orderby);
                    }
                    if (!empty($CFG->forcelastname)) {
                        $orderby = preg_replace('~, fu_lastname(ASC)?~', '', $orderby);
                    }
                }
                break;
            case self::SORT_POSTS:
                $orderby .= ', numposts DESC';
                break;
            case self::SORT_UNREAD:
                $orderby .= ', numposts-numreadposts DESC';
                break;
            case self::SORT_GROUP:
                $orderby .= ', groupname ASC';
                break;
            default:
                throw new forum_exception("Unknown SORT_xx constant $sort");
        }

        // swap ASC/DESC according to $sortreverse
        if ($sortreverse) {
            $orderby = str_replace('DESC', 'ASX', $orderby);
            $orderby = str_replace('ASC', 'DESC', $orderby);
            $orderby = str_replace('ASX', 'ASC', $orderby);
            $orderby = str_replace('sticky ASC', 'sticky DESC', $orderby);
        }

        // Ensure consistency by adding id ordering
        $orderby .= ', id DESC';

        // Limits
        $limitfrom = ($page-1) * $CFG->forumng_discussionsperpage;
        $limitnum = $CFG->forumng_discussionsperpage;

        // Do query
        $rs = forum_discussion::query_discussions($conditions, $userid,
            $orderby, $limitfrom, $limitnum);

        $result = new forum_discussion_list($page, $pagecount, $count);
        while ($rec = rs_fetch_next_record($rs)) {
            // Create a new discussion from the database details
            $discussion = new forum_discussion($this, $rec, true,
                forum_utils::get_real_userid($userid));

            // Give the discussion a chance to invalidate discussion
            // cache. This is so that if the user looks at a discussion
            // list, and it shows a newer post, then they click into the
            // discussion, they don't end up not seeing it!
            $discussion->maybe_invalidate_cache();

            // Add to results
            $result->add_discussion($discussion);
        }
        rs_close($rs);
        return $result;
    }

    /**
     * Creates a new discussion in this forum.
     * @param int $groupid Group ID for the discussion or null if it should show
     *   to all groups
     * @param string $subject Subject of message
     * @param string $message Message content
     * @param int $format Format of message content
     * @param array $attachments Array of attachment files. These should have
     *   already been checked and renamed etc by a Moodle upload manager.
     * @param bool $mailnow True to mail ASAP, else false
     * @param int $timestart Visibility time of discussion (seconds since epoch) or null
     * @param int $timeend Time at which discussion disappears (seconds since epoch) or null
     * @param bool $locked True if discussion should be locked
     * @param bool $sticky True if discussion should be sticky
     * @param int $userid User ID or 0 for current user
     * @param bool $log True to log this
     * @return array Array with 2 elements ($discussionid, $postid)
     */
    public function create_discussion($groupid,
        $subject, $message, $format, $attachments=array(), $mailnow=false,
        $timestart=0, $timeend=0, $locked=false, $sticky=false,
        $userid=0, $log=true) {
        $userid = forum_utils::get_real_userid($userid);

        // Prepare discussion object
        $discussionobj = new StdClass;
        $discussionobj->forumid = $this->forumfields->id;
        $discussionobj->groupid =
            ($groupid == self::ALL_GROUPS || $groupid==self::NO_GROUPS)
            ? null : $groupid;
        $discussionobj->postid = null; // Temporary until we create that first post
        $discussionobj->lastpostid = null;
        $discussionobj->timestart = $timestart;
        $discussionobj->timeend = $timeend;
        $discussionobj->deleted = 0;
        $discussionobj->locked = $locked ? 1 : 0;
        $discussionobj->sticky = $sticky ? 1 : 0;

        // Create discussion
        forum_utils::start_transaction();
        $discussionobj->id = forum_utils::insert_record('forumng_discussions', $discussionobj);
        $newdiscussion = new forum_discussion($this, $discussionobj, false, -1);

        // Create initial post
        $postid = $newdiscussion->create_root_post(
            $subject, $message, $format, $attachments, $mailnow, $userid);

        // Update discussion so that it contains the post id
        $changes = new StdClass;
        $changes->id = $discussionobj->id;
        $changes->postid = $postid;
        $changes->lastpostid = $postid;
        forum_utils::update_record('forumng_discussions', $changes);

        $newdiscussion->log('add discussion');

        if (forum::search_installed()) {
            forum_post::get_from_id($postid,
                    $this->get_course_module_id())->search_update();
        }

        forum_utils::finish_transaction();
        return array($newdiscussion->get_id(), $postid);
    }

    /**
     * @return string Hash of the settings of this forum which could possibly
     *   affect cached discussion objects
     */
    function get_settings_hash() {
        return md5(
            $this->forumfields->ratingscale .
            $this->forumfields->ratingfrom .
            $this->forumfields->ratinguntil .
            $this->forumfields->ratingthreshold .
            $this->forumfields->grading .
            $this->forumfields->ratingthreshold .
            $this->forumfields->typedata);
    }

    // Unread data
    //////////////

    /**
     * Marks all discussions in this forum as read.
     * @param int $groupid Group user is looking at (will mark all discussions
     *   in this group, plus all in the 'all/no groups' section; ALL_GROUPS
     *   marks regardless of group; NO_GROUPS marks those without group)
     * @param int $time Time to mark it read at (0 = now)
     * @param int $userid User who's read the discussion (0=current)
     */
    public function mark_read($groupid, $time=0, $userid=0) {
        if(!$userid) {
            global $USER;
            $userid = $USER->id;
        }
        if(!$time) {
            $time = time();
        }
        forum_utils::start_transaction();

        // Work out group condition
        switch($groupid) {
            case self::ALL_GROUPS :
                $groupcondition = '';
                break;
            case self::NO_GROUPS :
                $groupcondition = 'AND fd.groupid IS NULL';
                break;
            default:
                $groupcondition = 'AND (fd.groupid IS NULL OR fd.groupid=' .
                    $groupid . ')';
                break;
        }

        // Get all discussions that are within read-tracking deadline
        global $CFG;
        $forumid = $this->get_id();
        $deadline = self::get_read_tracking_deadline();
        if ($this->get_type()->has_unread_restriction()) {
            $typejoin = " INNER JOIN {$CFG->prefix}forumng_posts fpfirst ON fd.postid=fpfirst.id";
            $typecondition = $this->get_type()->get_unread_restriction_sql($this, $userid);
            if ($typecondition) {
                $typecondition = ' AND ' . $typecondition;
            } else {
                $typecondition = '';
            }
        } else {
            $typejoin = '';
            $typecondition = '';

        }
        $rs = forum_utils::get_recordset_sql("
SELECT
    fd.id
FROM
    {$CFG->prefix}forumng_discussions fd
    INNER JOIN {$CFG->prefix}forumng_posts lp ON fd.lastpostid=lp.id
    $typejoin
WHERE
    fd.forumid=$forumid
    AND lp.modified >= $deadline
    $groupcondition
    $typecondition");
        $discussions = array();
        while($rec = rs_fetch_next_record($rs)) {
            $discussions[] = $rec->id;
        }
        rs_close($rs);

        if (count($discussions) > 0) {
            // Delete any existing records for those discussions
            $inorequals = forum_utils::in_or_equals($discussions);
            forum_utils::execute_sql("
DELETE FROM {$CFG->prefix}forumng_read
WHERE userid=$userid AND discussionid $inorequals");

            // Add new record for each discussion
            foreach($discussions as $discussionid) {
                $readrecord = new StdClass;
                $readrecord->userid = $userid;
                $readrecord->discussionid = $discussionid;
                $readrecord->time = $time;
                forum_utils::insert_record('forumng_read', $readrecord);
            }
        }

        forum_utils::finish_transaction();
    }

    // Subscriptions
    ////////////////

    /**
     * Subscribes a user to this forum. (Assuming it permits manual subscribe/
     * unsubscribe.)
     * @param $userid User ID (default current)
     * @param $groupid Group ID to unsubscribe to (default null = whole forum)
     * @param $log True to log this
     */
    public function subscribe($userid=0, $groupid=null, $log=true) {
        global $CFG;
        $userid = forum_utils::get_real_userid($userid);
        // For shared forums, we subscribe to a specific clone
        if ($this->is_shared()) {
            $clonecmid = $this->get_course_module_id();
            $clonevalue = '=' . $clonecmid;
        } else {
            $clonecmid = null;
            $clonevalue = 'IS NULL';
        }
        forum_utils::start_transaction();
        //delete all the subscriptions to the discussions in the entire forum or the discussions in the specified group if any
        if (!$groupid) {
            //delete all the subscriptions to the discussions/groups in the entire forum
            forum_utils::execute_sql(
                "DELETE FROM {$CFG->prefix}forumng_subscriptions " .
                "WHERE userid=" . $userid . " AND forumid=" . $this->forumfields->id .
                " AND clonecmid $clonevalue AND subscribed=1 " .
                "AND (discussionid IS NOT NULL OR groupid IS NOT NULL)");
            $existing = get_record('forumng_subscriptions',
                'userid', $userid, 'forumid', $this->forumfields->id, 'clonecmid', $clonecmid);
            if (!$existing) {
                $subrecord = new StdClass;
                $subrecord->userid = $userid;
                $subrecord->forumid = $this->forumfields->id;
                $subrecord->subscribed = 1;
                $subrecord->clonecmid = $clonecmid;
                forum_utils::insert_record('forumng_subscriptions', $subrecord);
            } else if (!$existing->subscribed) {
                // See if this is initial-subscription and we are subscribed by
                // default, if so just remove the record
                if ($this->is_initially_subscribed($userid, true)) {
                    forum_utils::delete_records(
                        'forumng_subscriptions', 'id', $existing->id);
                } else {
                    $subchange = new StdClass;
                    $subchange->id = $existing->id;
                    $subchange->subscribed = 1;
                    forum_utils::update_record('forumng_subscriptions', $subchange);
                }
            }
        } else {
            ////delete all the subscriptions to the discussions in the the specified group if any
            $discussionquery = "SELECT id FROM {$CFG->prefix}forumng_discussions
                WHERE forumid = {$this->forumfields->id} AND groupid = $groupid";
            //Share forum doesn't support group mode so we don't check clonecmid
            forum_utils::execute_sql(
                "DELETE FROM {$CFG->prefix}forumng_subscriptions " .
                "WHERE userid=" . $userid . " AND forumid=" . $this->forumfields->id .
                " AND subscribed=1 " .
                "AND discussionid IS NOT NULL AND discussionid IN ($discussionquery)");
            //Do some housekeeping in case some invalid data
            //deleting any group subscription if any (shouldn't have any records to be deleted ideally)
            forum_utils::delete_records('forumng_subscriptions', 'userid', $userid,
                    'forumid', $this->forumfields->id, 'groupid', $groupid);
            $subrecord = new StdClass;
            $subrecord->userid = $userid;
            $subrecord->forumid = $this->forumfields->id;
            $subrecord->subscribed = 1;
            $subrecord->groupid = $groupid;

            forum_utils::insert_record('forumng_subscriptions', $subrecord);
        }
        forum_utils::finish_transaction();
        if ($log) {
            $this->log('subscribe', $userid . ' ' .
                    ($groupid ? 'group ' . $groupid : 'all'));
        }
    }

    /**
     * Unsubscribes a user from this forum.
     * @param $userid User ID (default current)
     * @param $groupid Group ID to unsubscribe from (default null = whole forum)
     * @param $log True to log this
     */
    public function unsubscribe($userid=0, $groupid=null, $log=true) {
        global $CFG;
        $userid = forum_utils::get_real_userid($userid);
        // For shared forums, we subscribe to a specific clone
        if ($this->is_shared()) {
            $clonecmid = $this->get_course_module_id();
            $clonevalue = '=' . $clonecmid;
        } else {
            $clonecmid = null;
            $clonevalue = 'IS NULL';
        }
        if (!$groupid) {
            //Unsubscribe from the whole forum; deleting all the discussion/group subscriptions
            forum_utils::execute_sql(
                "DELETE FROM {$CFG->prefix}forumng_subscriptions " .
                "WHERE userid=" . $userid . " AND forumid=" . $this->forumfields->id .
                " AND clonecmid $clonevalue AND subscribed=1 " .
                "AND (discussionid IS NOT NULL OR groupid IS NOT NULL)");
            if ($this->is_initially_subscribed($userid, true)) {
                $existing = get_record('forumng_subscriptions',
                    'userid', $userid, 'forumid', $this->forumfields->id,
                    'clonecmid', $clonecmid);
                if (!$existing) {
                    $subrecord = new StdClass;
                    $subrecord->userid = $userid;
                    $subrecord->forumid = $this->forumfields->id;
                    $subrecord->subscribed = 0;
                    $subrecord->clonecmid = $clonecmid;
    
                    forum_utils::insert_record('forumng_subscriptions', $subrecord);
                } else if ($existing->subscribed) {
                    $subchange = new StdClass;
                    $subchange->id = $existing->id;
                    $subchange->subscribed = 0;
    
                    forum_utils::update_record('forumng_subscriptions', $subchange);
                }
            } else {
                forum_utils::delete_records('forumng_subscriptions', 'userid', $userid,
                    'forumid', $this->forumfields->id, 'clonecmid', $clonecmid);
            }
        } else {
            //Unsubscribe from the specified group; remove all the subscritions to the discussions which belongs to the group if any
            $discussionquery = "SELECT id FROM {$CFG->prefix}forumng_discussions
                WHERE forumid = {$this->forumfields->id} AND groupid = $groupid";
            forum_utils::execute_sql(
                "DELETE FROM {$CFG->prefix}forumng_subscriptions " .
                "WHERE userid=" . $userid . " AND forumid=" . $this->forumfields->id .
                " AND subscribed=1 " .
                "AND discussionid IS NOT NULL AND discussionid IN ($discussionquery)");
            forum_utils::delete_records('forumng_subscriptions', 'userid', $userid,
                    'forumid', $this->forumfields->id, 'groupid', $groupid);
        }
        if ($log) {
            $this->log('unsubscribe', $userid . ' ' .
                    ($groupid ? 'group ' . $groupid : 'all'));
        }
    }

    /**
     * Determines whether a user can subscribe/unsubscribe to a forum.
     * @param int $userid User ID, 0 for default
     * @return bool True if user is allowed to change their subscription
     */
    public function can_change_subscription($userid=0) {
        switch ($this->get_effective_subscription_option()) {
            case self::SUBSCRIPTION_NOT_PERMITTED:
                return false;

            case self::SUBSCRIPTION_FORCED:
                if ($this->is_forced_to_subscribe($userid)) {
                    return false;
                }

                // Fall through
            default:
                return $this->can_be_subscribed($userid);
        }
    }

    /**
     * Checks whether a user can be subscribed to the forum, regardless of
     * subscription option. Includes a variety of other checks. [These are
     * supposed to be the same as checks done when building the list of people
     * for email.]
     * @param int $userid User ID or 0 for current
     * @return bool True if user can be subscribed
     */
    private function can_be_subscribed($userid=0) {
        global $USER;
        $userid = forum_utils::get_real_userid($userid);
        $cm = $this->get_course_module();
        $course = $this->get_course();
        $context = $this->get_context();

        // Guests cannot subscribe
        if(isguest($userid)) {
            return false;
        }

        // Get from cache if possible
        if (!isset($this->cache->can_be_subscribed)) {
            $this->cache->can_be_subscribed = array();
        }
        if (array_key_exists($userid, $this->cache->can_be_subscribed)) {
            return $this->cache->can_be_subscribed[$userid];
        }

        // This is not a loop, just so I can use break
        do {
            // Check user can see forum
            if (!has_capability('mod/forumng:viewdiscussion', $context,
                $userid)) {
                $result = false;
                break;
            }
            // For current user, can take shortcut
            if ($userid == $USER->id) {
                if (empty($cm->uservisible)) {
                    $uservisible = false;
                } else {
                    $uservisible = true;
                }
                if (!$uservisible) {
                    $result = false;
                    break;
                }
            } else {
                $visible = $cm->visible;
                if(class_exists('ouflags')) {
                    // OU extra access restrictions
                    require_once($CFG->libdir . '/conditionlib.php');
                    require_once($CFG->dirroot . '/local/module_access.php');
                    $conditioninfo = new condition_info($cm);
                    $visible = $visible &&
                        $conditioninfo->is_available($crap, false, $userid) &&
                        is_module_student_accessible($cm, $course);
                }
                if (!$visible && !has_capability(
                    'moodle/site:viewhiddenactivities', $context, $userid)) {
                    $result = false;
                    break;
                }
                if ($cm->groupmembersonly && !has_capability(
                    'moodle/site:accessallgroups', $context, $userid)) {
                    // If the forum is restricted to group members only, then
                    // limit it to people within groups on the course - or
                    // groups in the grouping, if one is selected
                    $groupobjs = groups_get_all_groups($course->id, $userid,
                        $cm->groupingid, 'g.id');
                    if (!$groupobjs || count($groupobjs)==0) {
                        $result = false;
                        break;
                    }
                }
            }
            $result = true;
            break;
        } while(false);

        $this->cache->can_be_subscribed[$userid] = $result;
        return $result;
    }

    /**
     * Determines whether a user is forced to subscribe.
     * @param int $userid User ID or 0 for current
     * @param bool $expectingquery True if expecting query (note this
     *   value is ignored if you specify a non-current userid, then it will
     *   always make queries)
     * @return bool True if forced to subscribe
     */
    public function is_forced_to_subscribe($userid=0, $expectingquery=false) {

        // Only for forced-subscription forums, duh
        $subscriptionoption = $this->get_effective_subscription_option();
        if ($subscriptionoption != self::SUBSCRIPTION_FORCED) {
            return false;
        }

        return $this->is_in_auto_subscribe_list($userid, $expectingquery);
    }

    /**
     * Determines whether a user is initially subscribed.
     * @param int $userid User ID or 0 for current
     * @param bool $expectingquery True if expecting query (note this
     *   value is ignored if you specify a non-current userid, then it will
     *   always make queries)
     * @return bool True if initially subscribe
     */
    public function is_initially_subscribed($userid=0, $expectingquery=false) {

        // Only for initial-subscription forums, duh
        $subscriptionoption = $this->get_effective_subscription_option();
        if ($subscriptionoption != self::SUBSCRIPTION_INITIALLY_SUBSCRIBED) {
            return false;
        }

        return $this->is_in_auto_subscribe_list($userid, $expectingquery);
    }

    /**
     * Determines whether a user is in the auto-subscribe list for this forum
     * (applies in initial/forced subscription forums).
     * @param int $userid User ID or 0 for current
     * @param bool $expectingquery True if expecting query (note this
     *   value is ignored if you specify a non-current userid, then it will
     *   always make queries)
     * @return bool True if forced to subscribe
     */
    public function is_in_auto_subscribe_list($userid=0, $expectingquery=false) {
        global $CFG, $USER;
        $userid = forum_utils::get_real_userid($userid);
        $context = $this->get_context();

        // Check capability without doanything
        if(!has_capability('mod/forumng:viewdiscussion', $context,
            $userid, false)) {
            return false;
        }

        // Check user is in permitted group
        $groups = $this->get_permitted_groups();
        if ($groups) {
            if(isset($USER) && $userid == $USER->id) {
                $ok = false;
                foreach ($USER->groupmember as $courseid=>$values) {
                    if ($courseid == $this->get_course_id()) {
                        foreach($values as $groupid) {
                            if (in_array($groupid, $groups)) {
                                $ok = true;
                                break;
                            }
                        }
                        if ($ok) {
                            break;
                        }
                    }
                }
            } else {
                if (!$expectingquery) {
                    debugging('DB query required for is_in_auto_subscribe_list. ' .
                        'Set $expectingquery to true or check code',
                        DEBUG_DEVELOPER);
                }
                $ok = count_records_sql("
SELECT
    COUNT(1)
FROM
    {$CFG->prefix}groups_members
WHERE
    userid=$userid AND groupid " . forum_utils::in_or_equals($groups));
            }
            if (!$ok) {
                return false;
            }
        }

        // Check user has role in subscribe roles
        $roleids = forum_utils::safe_explode(',', $CFG->forumng_subscriberoles);
        if(isset($USER) && $userid == $USER->id) {
            // Get all context paths - this and ancestors
            $path = $context->path;
            do {
                $contextpaths[$path] = true;
                $path = substr($path, 0, strrpos($path, '/'));
            } while($path != '');

            // Scan in-memory representation for required roles in these
            // contexts
            $allowedroles = array_fill_keys($roleids, true);
            foreach($USER->access['ra'] as $context=>$roles) {
                if(array_key_exists($context, $contextpaths)) {
                    foreach($roles as $roleid) {
                        if(array_key_exists($roleid, $allowedroles)) {
                            return true;
                        }
                    }
                }
            }
        } else {
            $roleidcheck = forum_utils::in_or_equals($roleids);
            $contextids = forum_utils::safe_explode('/', $context->path);
            $contextidcheck = forum_utils::in_or_equals($contextids);
            if (!$expectingquery) {
                debugging('DB query required for is_in_auto_subscribe_list. ' .
                    'Set $expectingquery to true or check code',
                    DEBUG_DEVELOPER);
            }
            $gotrole = get_field_sql("
SELECT
    COUNT(1)
FROM
    {$CFG->prefix}role_assignments
WHERE
    contextid $contextidcheck AND roleid $roleidcheck AND userid=$userid");
            if ($gotrole > 0) {
                return true;
            }
        }
    }

    /**
     * Return the subscription info of the user.
     * @param int $userid User ID or 0 for current
     * @param bool $expectingquery True if expecting query (note this
     *   value is ignored if you specify a non-current userid, then it will
     *   always make queries)
     * @return object with three fields, $wholeforum, $discussionids (associated array with discussion id as the key and its group id as value
     * and $groupids
     * If $wholeforum = true and both $discussionids and $groupids is empty, subscribed to the whole forum;
     * If $wholeforum = false and $discussionids isn't empty while the groupids is empty, subscribed to a list of discussions
     * If $wholeforum = false and $discussionids is empty while the groupids is not empty, subscribed to a list of groups
     * If $wholeforum = false and both $discussionids and groupids is not empty, subscribed to both a list of discussions and a list of groups
     */
    public function get_subscription_info($userid=0, $expectingquery=false) {
        global $CFG, $FORUMNG_CACHE;
        $userid = forum_utils::get_real_userid($userid);

        if(!isset($FORUMNG_CACHE->subscriptioninfo)) {
            $FORUMNG_CACHE->subscriptioninfo = array();
        }
        $key = $userid . ':' . $this->get_id();
        if(array_key_exists($key, $FORUMNG_CACHE->subscriptioninfo)) {
            return $FORUMNG_CACHE->subscriptioninfo[$key];
        }

        $user = (object)(array('wholeforum'=>false, 'discussionids'=>array(), 'groupids'=>array()));

        // If subscription's banned, you ain't subscribed
        $subscriptionoption = $this->get_effective_subscription_option();
        if ($subscriptionoption == self::SUBSCRIPTION_NOT_PERMITTED) {
            $FORUMNG_CACHE->subscriptioninfo[$userid] = $user;
            return $user;
        }

        // Make extra checks that subscription is allowed
        $userid = forum_utils::get_real_userid($userid);
        if (!$this->can_be_subscribed($userid)) {
            $FORUMNG_CACHE->subscriptioninfo[$userid] = $user;
            return $user;
        }

        // Forced subscription
        if ($this->is_forced_to_subscribe($userid, $expectingquery)) {
            $user->wholeforum = true;
            $FORUMNG_CACHE->subscriptioninfo[$userid] = $user;
            return $user;
        }

        if ($this->is_initially_subscribed($userid, $expectingquery)) {
            $user->wholeforum = true;
        }

        // For shared forums, we subscribe to a specific clone
        if ($this->is_shared()) {
            $clonevalue = '=' . $this->get_course_module_id();
        } else {
            $clonevalue = 'IS NULL';
        }
        $rs = get_recordset_sql($sql = "
SELECT s.subscribed, s.discussionid, s.groupid, fd.groupid AS discussiongroupid, discussiongm.id AS discussiongroupmember, subscriptiongm.id AS subscriptiongroupmember
FROM
    {$CFG->prefix}forumng_subscriptions s
    LEFT JOIN {$CFG->prefix}forumng_discussions fd ON fd.id = s.discussionid
    LEFT JOIN {$CFG->prefix}groups_members discussiongm ON fd.groupid = discussiongm.groupid AND s.userid = discussiongm.userid
    LEFT JOIN {$CFG->prefix}groups_members subscriptiongm ON s.groupid = subscriptiongm.groupid AND s.userid = subscriptiongm.userid
WHERE
    s.forumid={$this->forumfields->id} 
    AND s.userid={$userid} 
    AND (fd.forumid={$this->forumfields->id} OR s.discussionid IS NULL)
    AND s.clonecmid $clonevalue");
        if(!$rs) {
            throw new forum_exception('Failed to get subscriber list [' . $sql . ']');
        }

        $context = $this->get_context();
        $canviewdiscussion = has_capability('mod/forumng:viewdiscussion', $context, $userid);
        $canaccessallgroups = has_capability('moodle/site:accessallgroups', $context, $userid);
        while($rec = rs_fetch_next_record($rs)) {

            if ($rec->subscribed) {
                //has_capability('mod/forumng:viewdiscussion', $this->get_context());
                //Rewrite the whole block
                if ($rec->groupid) {
                    //Subscrbied to a list of groups only
                    // Only allow this row to count if the user has access to subscribe to group
                    // 1. User must have mod/forumng:viewdiscussion
                    // 2. One of the following must be true:
                    //    a. Forum is set to visible groups (if forum is set for no groups, we will ignore this group subscription
                    //    b. User belongs to the group (check the field)
                    //    c. User has accessallgroups
                    $groupok = $this->get_group_mode() == VISIBLEGROUPS || $rec->subscriptiongroupmember || $canaccessallgroups;
                    if ($canviewdiscussion && $groupok ) {
                        $user->groupids[$rec->groupid] = $rec->groupid;
                    }
                } else if ($rec->discussionid) {
                    //$groupok if disucssion belong to all groups or the user in the same group as the discussion belongs to or
                    //the forum is set to be visible groups
                    $groupok = !$rec->discussiongroupid || $rec->discussiongroupmember ||
                        $this->get_group_mode() == VISIBLEGROUPS || $canaccessallgroups;
                    if ($canviewdiscussion && $groupok) {
                        $user->discussionids[$rec->discussionid] = $rec->discussiongroupid;
                    }
                } else {
                    //Subscribed to the whole forum, quit the loop as no more records should match if the database data isn't messed up
                    // Only allow this row to count if the user has access to subscribe to whole forum
                        // 1. User must have mod/forumng:viewdiscussion
                        // 2. One of the following must be true:
                        //    a. Forum is set to no groups, or to visible groups
                        //    b. User has accessallgroups
                        $groupok = $this->get_group_mode() == VISIBLEGROUPS ||
                            $this->get_group_mode() == NOGROUPS || $canaccessallgroups;
                        if ($canviewdiscussion && $groupok) {
                            $user->wholeforum = true;
                            break;
                        }
                }
            } else if ($subscriptionoption == self::SUBSCRIPTION_INITIALLY_SUBSCRIBED) {
                // This is an 'unsubscribe' request. These are only allowed
                // for initial-subscription, otherwise ignored
                $user->wholeforum = false;
            }
        }
        rs_close($rs);

        // clear the discussions array if wholeforum is true
        if ($user->wholeforum) {
            $user->discussionids = array ();
            $user->groupids = array ();
        }

        $FORUMNG_CACHE->subscriptioninfo[$userid] = $user;
        return $user;
    }

    /**
     * Obtains current forum subscription option, taking into account global
     * setting as well as this forum.
     * @return int SUBSCRIPTION_xx constant
     */
    public function get_effective_subscription_option() {
        global $CFG;

        // Global 'force' option overrides local option if set
        $result = $CFG->forumng_subscription;
        if ($result == -1) {
            $result = $this->forumfields->subscription;
        }
        return $result;
    }

    /**
     * Obtains current forum feed type option, taking into account global
     * setting as well as this forum.
     * @return int FEEDTYPE_xx constant
     */
    public function get_effective_feed_option() {
        global $CFG;

        // Global 'force' used if set
        $result = $CFG->forumng_feedtype;

        // Feeds can be disabled globally or for whole module
        if (!($CFG->forumng_enablerssfeeds && $CFG->enablerssfeeds)) {
            $result = forum::FEEDTYPE_NONE;
        }

        // If none of the above applied, use the module's setting
        if ($result == -1) {
            $result = $this->forumfields->feedtype;
        }

        return $result;
    }

    /**
     * Obtains the list of people who are forced to subscribe to the forum
     * (if forced) or are by default subscribed (if initial).
     * @param int $groupid If specified, restricts list to this group id
     * @return array Array of partial user objects (with enough info to send
     *   email and display them)
     */
    public function get_auto_subscribers($groupid=forum::ALL_GROUPS) {
        global $CFG;
        switch ($this->get_effective_subscription_option()) {
        case self::SUBSCRIPTION_FORCED :
        case self::SUBSCRIPTION_INITIALLY_SUBSCRIBED :
            break;
        default:
            return array();
        }

        $groups = $this->get_permitted_groups();
        $context = $this->get_context();

        // Get all users (limited to the specified groups if applicable)
        // who are allowed to view discussions in this forum
        $fields = '';
        foreach(forum_utils::get_username_fields(true) as $field) {
            if($fields) {
                $fields .= ',';
            }
            $fields.= 'u.' . $field;
        }
        $users = get_users_by_capability($context,
            'mod/forumng:viewdiscussion', $fields, '', '', '', $groups, '', false);
        $users = $users ? $users : array();

        // Now filter list to include only people in the subscriberoles
        // list. Big IN clauses can be slow, so rather than doing that,
        // let's just get the list of people on those roles, and then
        // intersect in PHP. It's a shame you can't add
        // joins/restrictions to get_users_by_capability :(
        $roleids = forum_utils::safe_explode(',', $CFG->forumng_subscriberoles);
        $roleidcheck = forum_utils::in_or_equals($roleids);
        $contextids = forum_utils::safe_explode('/', $context->path);
        $contextidcheck = forum_utils::in_or_equals($contextids);
        if ($groupid == forum::ALL_GROUPS || $groupid == forum::NO_GROUPS) {
            $groupcheck = '';
        } else {
            $groupcheck = "INNER JOIN {$CFG->prefix}groups_members gm ON gm.userid=ra.userid AND gm.groupid=$groupid";
        }
        $rs = get_recordset_sql("
SELECT
    ra.userid
FROM
    {$CFG->prefix}role_assignments ra
    $groupcheck
WHERE
    contextid $contextidcheck AND roleid $roleidcheck");
        if (!$rs) {
            throw new forum_exception('Failed to get users with subscribe role');
        }
        $allowedusers = array();
        while($rec = rs_fetch_next_record($rs)) {
            $allowedusers[$rec->userid] = true;
        }
        rs_close($rs);
        foreach($users as $id=>$user) {
            if(!array_key_exists($id, $allowedusers)) {
                unset($users[$id]);
            }
        }
        return $users;
    }

    /**
     * Obtains a list of group IDs that are permitted to use this forum.
     * @return mixed Either an array of IDs, or '' if all groups permitted
     */
    private function get_permitted_groups() {
        $groups = '';
        $cm = $this->get_course_module();
        if ($cm->groupmembersonly) {
            // If the forum is restricted to group members only, then
            // limit it to people within groups on the course - or
            // groups in the grouping, if one is selected
            $groupobjs = groups_get_all_groups($this->get_course()->id, 0,
                $cm->groupingid, 'g.id');
            $groups = array();
            foreach ($groupobjs as $groupobj) {
                $groups[] = $groupobj->id;
            }
        }
        return $groups;
    }

    /**
     * Obtains list of forum subscribers.
     * @param int $groupid If specified, restricts list to this group id
     * @return array Array of partial user objects (with enough info to send
     *   email and display them); additionally, if the forum is in group mode,
     *   this includes an ->accessallgroups boolean
     */
    public function get_subscribers($groupid=forum::ALL_GROUPS) {
        global $CFG;

        // Array that will contain result
        $users = array();

        // Get permitted groups
        $groups = $this->get_permitted_groups();

        $subscriptionoption = $this->get_effective_subscription_option();
        switch($subscriptionoption) {
            case self::SUBSCRIPTION_NOT_PERMITTED:
                return array();

            case self::SUBSCRIPTION_FORCED:
            case self::SUBSCRIPTION_INITIALLY_SUBSCRIBED:
                $users = $this->get_auto_subscribers($groupid);
                //add $wholeforum = 1 and an empty array() for discussionid
                //for people who initially subscribed
                foreach($users as $user) {
                    $user->wholeforum = true;
                    $user->discussionids = array ();
                    $user->groupids = array ();
                }
                break;

            default:
                // The other two cases (initial subscribe, and manual subscribe)
                // fall through to the standard code below.
        }

        $context = $this->get_context();

        // For shared forums, we only return the subscribers for the current
        // clone
        $clonecheck = "";
        if ($this->is_shared()) {
            $clonecheck = 'AND s.clonecmid = ' . $this->get_course_module_id();
        }

        // Obtain the list of users who have access all groups on the forum,
        // unless it's in no-groups mode
        $groupmode = $this->get_group_mode();
        if ($groupmode) {
            //Get a list of user who can access all groups
            $aagusers = get_users_by_capability($context,
                'moodle/site:accessallgroups', 'u.id');
            $aagusers = $aagusers ? $aagusers : array();
        }
        // Get the list of subscribed users.
        if ($groupid == forum::ALL_GROUPS || $groupid == forum::NO_GROUPS) {
            $groupcheck = '';
        } else {
            $groupcheck = "INNER JOIN {$CFG->prefix}groups_members gm ON gm.userid=u.id AND gm.groupid=$groupid";
        }

        $rs = get_recordset_sql($sql = "
SELECT
    ".forum_utils::select_username_fields('u', true).",
    s.subscribed, s.discussionid, s.groupid, fd.groupid AS discussiongroupid, discussiongm.id AS discussiongroupmember,
    subscriptiongm.id AS subscriptiongroupmember
FROM
    {$CFG->prefix}forumng_subscriptions s
    INNER JOIN {$CFG->prefix}user u ON u.id=s.userid
    $groupcheck
    LEFT JOIN {$CFG->prefix}forumng_discussions fd ON fd.id = s.discussionid
    LEFT JOIN {$CFG->prefix}groups_members discussiongm ON fd.groupid = discussiongm.groupid AND s.userid = discussiongm.userid
    LEFT JOIN {$CFG->prefix}groups_members subscriptiongm ON s.groupid = subscriptiongm.groupid AND s.userid = subscriptiongm.userid
WHERE
    s.forumid={$this->forumfields->id}
    AND (fd.forumid={$this->forumfields->id} OR s.discussionid IS NULL)
    $clonecheck");
        if(!$rs) {
            throw new forum_exception('Failed to get subscriber list [' . $sql . ']');
        }

        // Filter the result against the list of allowed users
        $allowedusers = null;
        while($rec = rs_fetch_next_record($rs)) {
            //subscribed to the whole forum when subscribed == 1 and disucssionid =='';
            // *** Put the allowedusers checks in same part of code so not duplicated
            if ($rec->subscribed) {
                // This is a 'subscribe' request
                if (!$allowedusers) {
                    // Obtain the list of users who are allowed to see the forum.
                    // As get_users_by_capability can be expensive, we only do this
                    // once we know there actually are subscribers.
                    $allowedusers = get_users_by_capability($context,
                        'mod/forumng:viewdiscussion', 'u.id', '', '', '',
                        $groups, '', true, false, true);
                    $allowedusers = $allowedusers ? $allowedusers : array();
                }
                // Get reference to current user, or make new object if required
                if (!array_key_exists($rec->u_id, $users)) {
                    $user = forum_utils::extract_subobject($rec, 'u_');
                    $user->wholeforum = false;
                    $user->discussionids = array();
                    $user->groupids = array();
                    $newuser = true;
                } else {
                    $user = $users[$rec->u_id];
                    $newuser = false;
                }
                $ok = false;
                //Subscribed to a discussion
                if ($rec->discussionid) {
                    $groupok = !$rec->discussiongroupid || $rec->discussiongroupmember ||
                        $groupmode==VISIBLEGROUPS || array_key_exists($user->id, $aagusers);
                    if (array_key_exists($user->id, $allowedusers) && $groupok) {
                        $ok = true;
                        $user->discussionids[$rec->discussionid] = $rec->discussiongroupid;
                    }
                //Subscribed to a group
                } else if ($rec->groupid) {
                    $groupok = $groupmode == VISIBLEGROUPS ||
                        ($groupmode == SEPARATEGROUPS &&
                        ($rec->subscriptiongroupmember || array_key_exists($user->id, $aagusers)));
                    if (array_key_exists($user->id, $allowedusers) && $groupok) {
                        $user->groupids[$rec->groupid] = $rec->groupid;
                        $ok = true;
                    }
                //Subscribed to the whole forum
                } else {
                    // extra conditions for forum not separate groups or accessallgroups
                    $groupok = $groupmode != SEPARATEGROUPS || array_key_exists($user->id, $aagusers);
                    if (array_key_exists($user->id, $allowedusers) && $groupok) {
                        $user->wholeforum = true;
                        $ok = true;
                    }
                }
                // If this is a new user object, add it to the array provided the row was valid
                if($newuser && $ok) {
                    $users[$user->id] = $user;
                }
            } else {
                // This is an 'unsubscribe' request. These are only allowed
                // for initial-subscription, otherwise ignored
                if ($subscriptionoption == self::SUBSCRIPTION_INITIALLY_SUBSCRIBED
                    && array_key_exists($user->id, $users)) {
                    // set wholeforum = false for user (if they are in the array)
                    $users[$rec->u_id]->unsubscribe = true;
                    $users[$rec->u_id]->wholeforum = false;
                }
            }
        }
        rs_close($rs);

        //1. loop through array and clear the discussions/groupids array if wholeforum is true
        //2. Find any user unsubscribed from initial subscribed forum. If the user has been subscribed to discussions/groups
        //   remove the $user->unsubscribe flag; Otherwise remove the user from the list.
        foreach($users as $key=>$user) {
            if ($user->wholeforum) {
                $user->discussionids = array ();
                $user->groupids = array ();
            }
            // Remove discussionids for discussions that are already covered by group subscriptions
            // TODO
            if (count($user->discussionids) != 0 && count($user->groupids) != 0) {
                foreach ($user->discussionids as  $id => $dgroupid) {
                    if(!$dgroupid || array_key_exists($dgroupid, $user->groupids)) {
                        unset($user->discussionids[$id]);
                    }
                }
            }
            // If the user has unsubscribed from an initial subscription, then remove the entry
            // from the results array unless there are s subscriptions to discussions or groups
            if (!empty($user->unsubscribe)) {
                //Remove the unsubscribe as the user is likely to subscribed to discussions or groups
                unset($user->unsubscribe);
                if (count($user->discussionids) == 0 && count($user->groupids) == 0) {
                    unset($users[$key]);
                }
            }
        }

        // Add access-all-groups information if applicable
        if ($groupmode) {
            foreach ($users as $key=>$user) {
                $user->accessallgroups = array_key_exists($user->id, $aagusers);
            }
        }

        return $users;
    }

    // Permissions
    //////////////

    /**
     * Makes security checks for viewing this forum. Will not return if user
     * cannot view it.
     * This function calls Moodle require_login, so should be a complete
     * access check. It should be placed near the top of a page.
     * Note that this function only works for the current user when used in
     * interactive mode (ordinary web page view). It cannot be called in cron,
     * web services, etc.
     *
     * @param int $groupid Group ID user is attempting to view (may also be
     *   ALL_GROUPS or NO_GROUPS or null)
     * @param int $userid User ID or 0 for current; only specify user ID when
     *   there is no current user and normal login process is not required -
     *   do NOT set this to the current user id, always user 0
     * @param int $autologinasguest whether to get the require_login call to
     *   automatically log user in as guest
     */
    function require_view($groupid, $userid=0, $autologinasguest=false) {
        global $CFG;

        $cm = $this->get_course_module();
        $course = $this->get_course();
        $context = $this->get_context();
        if (!$userid) {
            // User must be logged in and able to access the activity. (This
            // call sets up the global course and checks various other access
            // restrictions that apply at course-module level, such as visibility.)
            if (count((array)$course) == 1) {
                require_login($course->id, $autologinasguest, $cm);
            } else {
                require_login($course, $autologinasguest, $cm);
            }
        } else {
            // For non-logged-in user we check basic course permission and
            // a couple of the 'hidden' flags
            require_capability('moodle/course:view', $context, $userid);

            // This check makes 2 DB queries :(
            if (!($course->visible
                && course_parent_visible($course))) {
                require_capability('moodle/course:viewhiddencourses',
                    $context);
            }
            if (!$cm->visible) {
                require_capability('moodle/course:viewhiddenactivities',
                    $context);
            }

            // Check OU custom restrictions (start/end dates)
            if (class_exists('ouflags')) {
                require_once($CFG->dirroot . '/local/module_access.php');
                define('SKIP_SAMS_CHECK', true);
                require_module_access($cm, $course, $userid);
            }
        }

        // Check they have the forumng view capability (this is there largely
        // so that we can override it to prevent prisoners from accessing)
        require_capability('mod/forumng:view', $context, $userid);

        // Note: There is no other capability just to view the forum front page,
        // so just check group access
        if ($groupid!==self::NO_GROUPS
            && !$this->can_access_group($groupid, false, $userid)) {
            // We already know they don't have this capability, but it's
            // a logical one to use to give an error message.
            require_capability('moodle/site:accessallgroups', $context, $userid);
        }
    }

    /**
     * Makes security checks for starting a discussion. Will not return if user
     * is not allowed to.
     * @param int $groupid Group ID (or ALL_GROUPS) where discussion is
     *   to be started
     */
    function require_start_discussion($groupid) {
        // Require forum view
        $this->require_view($groupid);

        // Check if they are allowed to start discussion
        $whynot = '';
        if (!$this->can_start_discussion($groupid, $whynot)) {
            print_error($whynot, 'forumng',
                    $this->get_url(forum::PARAM_HTML));
        }
    }

    /**
     * Checks whether user can access the given group.
     * @param $groupid Group ID
     * @param $write True if write access is required (this makes a difference
     *   if group mode is visible, when you can see other groups, but not write
     *   to them).
     * @param $userid User ID (0 = current user)
     * @return bool True if user can access group
     */
    function can_access_group($groupid, $write=false, $userid=0) {
        global $USER;

        // Check groupmode.
        $groupmode = groups_get_activity_groupmode($this->get_course_module());
        if (!$groupmode) {
            // No groups - you can only view 'all groups' mode
            return $groupid === self::NO_GROUPS;
        }

        // In visible groups, everyone can see everything (but not write to it)
        if ($groupmode==VISIBLEGROUPS && !$write) {
            return true;
        }

        // If you have access all groups, you can see it
        if (has_capability('moodle/site:accessallgroups', $this->get_context(), $userid)) {
            return true;
        }

        // Check if you're trying to view 'all groups'
        if ($groupid == self::ALL_GROUPS) {
            return false;
        }

        // Trying to view a specific group, must be a member
        if (isset($USER->groupmember) && (!$userid || $USER->id==$userid)
            && array_key_exists($this->get_course()->id, $USER->groupmember)) {
            // Current user, use cached value
            return array_key_exists($groupid, $USER->groupmember[$this->get_course()->id]);
        } else {
            // Not current user, test in database
            return groups_is_member($groupid, $userid);
        }
    }

    /**
     * @param $userid
     * @return bool True if user can view discussions in this forum
     */
    function can_view_discussions($userid=0) {
        return has_capability('mod/forumng:viewdiscussion', $this->get_context(),
            $userid);
    }

    /**
     * @param $userid
     * @return bool True if user can view a list of subscribers in this forum
     */
    function can_view_subscribers($userid=0) {
        if ($this->get_effective_subscription_option() ==
            self::SUBSCRIPTION_NOT_PERMITTED) {
                return false;
        }
        return has_capability('mod/forumng:viewsubscribers', $this->get_context(),
            $userid);
    }

    /**
     * @return bool True if user should see unread data in this forum
     */
    function can_mark_read($userid=0) {
        global $CFG, $USER;
        $user = forum_utils::get_user($userid);
        return $this->can_view_discussions($userid)
                && $CFG->forumng_trackreadposts && !isguestuser($user);
    }

    /**
     * @return bool True if user can view hidden discussions in this forum
     */
    function can_view_hidden($userid=0) {
        return has_capability('mod/forumng:viewallposts', $this->get_context(),
            $userid);
    }

    /**
     * @param int $userid User ID to check for (0 = current)
     * @return bool True if the forum is outside its 'posting from/until'
     *   times and the current user does not have permission to bypass that
     */
    function is_read_only($userid=0) {
        $now = time();
        return (($this->forumfields->postingfrom > $now) ||
            ($this->forumfields->postinguntil &&
                $this->forumfields->postinguntil <= $now)) &&
            !has_capability('mod/forumng:ignorepostlimits', $this->get_context());
    }

    /**
     * Checks whether this forum has a post quota which applies to a specific
     * user.
     * @param int $userid User ID to check for (0 = current)
     * @return bool True if post limit quota is enabled for this forum and user
     */
    public function has_post_quota($userid = 0) {
        return ($this->forumfields->maxpostsblock &&
            !has_capability('mod/forumng:ignorepostlimits', $this->get_context()))
            ? true : false;
    }

    /**
     * Counts number of remaining permitted posts in current time period.
     * @param int $userid User ID to check for (0 = current)
     * @return int How many more posts you can make; QUOTA_DOES_NOT_APPLY if
     *   no limit
     */
    public function get_remaining_post_quota($userid = 0) {

        // Check quota is turned on and applies to current user.
        if (!$this->has_post_quota($userid)) {
            return self::QUOTA_DOES_NOT_APPLY;
        }

        // Cache data for current user during request only
        global $CFG, $USER, $FORUMNG_POSTQUOTA;

        $userid = forum_utils::get_real_userid($userid);
        $usecache = $userid == $USER->id;
        if ($usecache && $FORUMNG_POSTQUOTA &&
            array_key_exists($this->forumfields->id, $FORUMNG_POSTQUOTA)) {
            return $FORUMNG_POSTQUOTA[$this->forumfields->id];
        }

        // OK, quota applies. Need to check how many posts they made, to this
        // forum, within the given timescale, which have not been deleted
        $threshold = time() - $this->forumfields->maxpostsperiod;
        $count = count_records_sql("
SELECT
    COUNT(1)
FROM
    {$CFG->prefix}forumng_posts fp
    INNER JOIN {$CFG->prefix}forumng_discussions fd ON fp.discussionid = fd.id
WHERE
    fd.forumid = {$this->forumfields->id}
    AND fp.userid = {$userid}
    AND fp.created > {$threshold}
    AND fp.deleted = 0
    AND fp.oldversion = 0");
        $result = $this->forumfields->maxpostsblock - $count;
        if ($result < 0) {
            $result = 0;
        }

        if ($usecache) {
            // Cache result
            if (!$FORUMNG_POSTQUOTA) {
                $FORUMNG_POSTQUOTA = array();
            }
            $FORUMNG_POSTQUOTA[$this->forumfields->id] = $result;
        }

        // Return result
        return $result;
    }

    /**
     * Checks if user is permitted to post new discussions to this forum.
     * @param int $groupid Group ID user wants to post to
     * @param string &$whynot Why user cannot post; will be set to '' or else
     *   to a language string name
     * @param int $userid User ID or 0 for current
     * @return bool True if user can post
     */
    function can_start_discussion($groupid, &$whynot, $userid=0) {
        $whynot = '';

        // Dates
        if ($this->is_read_only($userid)) {
            return false;
        }

        // Capability
        if (!has_capability('mod/forumng:startdiscussion',
            $this->get_context(), $userid)) {
            $whynot = 'startdiscussion_nopermission';
            return false;
        }

        // Forum type
        $type = $this->get_type();
        if (!$type->can_post($this, $whynot)) {
            return false;
        }

        // Group access
        if (!$this->can_access_group($groupid, true, $userid)) {
            $whynot = 'startdiscussion_groupaccess';
            return false;
        }

        // Throttling
        if ($this->get_remaining_post_quota($userid) == 0) {
            $whynot = 'startdiscussion_postquota';
            return false;
        }

        return true;
    }

    /**
     * @param int $userid User ID or 0 for default
     * @return bool True if user is allowed to set 'mail now' option
     */
    function can_mail_now($userid=0) {
        return has_capability('mod/forumng:mailnow', $this->get_context(), $userid);
    }

    /**
     * @param int $userid User ID or 0 for current
     * @return True if user can set posts as important
     */
    function can_set_important($userid=0) {
        return has_capability('mod/forumng:setimportant', $this->get_context(), $userid);
    }

    /**
     * @param int $userid User ID or 0 for default
     * @return bool True if user is allowed to set discussion options
     */
    function can_manage_discussions($userid=0) {
        return has_capability('mod/forumng:managediscussions',
            $this->get_context(), $userid);
    }

    /**
     * @param int $userid User ID, 0 for default
     * @return bool True if user has capability
     */
    public function can_manage_subscriptions($userid=0) {
        if ($this->get_effective_subscription_option() ==
            self::SUBSCRIPTION_NOT_PERMITTED) {
                return false;
        }
        return has_capability('mod/forumng:managesubscriptions', $this->get_context(),
            $userid);
    }

    /**
     * @param int $userid User ID, 0 for default
     * @return bool True if user has capability
     */
    public function can_create_attachments($userid=0) {
        return has_capability('mod/forumng:createattachment', $this->get_context(),
            $userid);
    }

    // Forum type
    /////////////

    /**
     * Obtains a forum type object suitable for handling this forum.
     * @return forum_type Type object
     */
    function get_type() {
        if (!$this->type) {
            $this->type = forum_type::get_new($this->forumfields->type);
        }

        return $this->type;
    }

    // Grades
    /////////

    /**
     * Updates the current forum grade(s), creating grade items if required,
     * or recalculating grades or deleting them.
     * (Should be based on forum_update_grades.)
     * @param int $userid User whose grades need updating, or 0 for all users
     * @param string $idnumber May be specified during forum creation when
     *   there isn't a course-module yet; otherwise leave blank to get from
     *   course-module
     */
    function update_grades($userid = 0, $idnumber=null) {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        forum_utils::start_transaction();

        // Calculate grades for requested user(s)
        if ($this->get_grading() == self::GRADING_NONE) {
            // Except don't bother if grading is not enabled
            $grades = array();
        } else {
            $grades = $this->get_user_grades($userid);

            // For specific user, add in 'null' item when updating grade - this
            // allows it to 'clear' the grade if you are 'un-rating' a post
            if (count($grades) == 0 && $userid) {
                $grade = new object();
                $grade->userid = $userid;
                $grade->rawgrade = NULL;
                $grades[$userid] = $grade;
            }
        }

        // Update grade item and grades
        $this->grade_item_update($grades, $idnumber);

        forum_utils::finish_transaction();
    }

    /**
     * Gets grades in this forum for all users or a specified user.
     * @param int $userid Specific user or 0 = all
     * @return array Grade objects as specified
     */
    private function get_user_grades($userid = 0) {
        global $CFG;

        // Part of query that is common to all aggregation types
        $forumid = $this->get_id();
        $baseselect = "SELECT fp.userid AS userid";
        $basemain = "
FROM {$CFG->prefix}forumng_discussions fd
INNER JOIN {$CFG->prefix}forumng_posts fp ON fp.discussionid = fd.id
INNER JOIN {$CFG->prefix}forumng_ratings fr ON fr.postid = fp.id
WHERE fd.forumid = $forumid";
        if ($userid) {
            $basemain .= " AND fp.userid = $userid";
        }
        $basemain .= " GROUP BY fp.userid";

        $aggtype = $this->get_grading();
        switch ($aggtype) {
        case self::GRADING_COUNT :
            $customselect = ", COUNT(fr.rating) AS rawgrade";
            break;
        case self::GRADING_MAX :
            $customselect = ", MAX(fr.rating) AS rawgrade";
            break;
        case self::GRADING_MIN :
            $customselect = ", MIN(fr.rating) AS rawgrade";
            break;
        case self::GRADING_SUM :
            $customselect = ", SUM(fr.rating) AS rawgrade";
            break;
        default : //avg
            $customselect = ", AVG(fr.rating) AS rawgrade";
            break;
        }

        // Work out the max grade
        $scale = $this->get_rating_scale();
        if ($scale >= 0) {
            //numeric
            $max = $scale;
        } else {
            //scale
            $scale = forum_utils::get_record('scale', 'id', -$scale);
            $scale = explode(',', $scale->scale);
            $max = count($scale);
        }

        $sql = $baseselect . $customselect . $basemain;
        $rs = forum_utils::get_recordset_sql($sql);
        $results = array();
        while($result = rs_fetch_next_record($rs)) {
            // it could throw off the grading if count and sum returned a
            // rawgrade higher than scale so to prevent it we review the
            // results and ensure that rawgrade does not exceed the scale,
            // if it does we set rawgrade = scale (i.e. full credit)
            if ($result->rawgrade > $max) {
                $result->rawgrade = $max;
            }
            $results[$result->userid] = $result;
        }

        return $results;
    }

    /**
     * Updates the grade item and (if given) the associated grades.
     * @param array $grades Array of grade objects which will be updated.
     *   (Alternatively may be the string 'reset' to reset grades - this is
     *   not currently used.)
     * @param string $idnumber May be specified during forum creation when
     *   there isn't a course-module yet; otherwise leave blank to get from
     *   course-module
     */
    private function grade_item_update($grades = array(), $idnumber=null) {
        if (is_null($idnumber)) {
            $cm = $this->get_course_module();
            // When $cm has been retrieved via get_fast_modinfo, it doesn't include
            // the idnumber field :(
            if (!property_exists($cm, 'idnumber')) {
                $cm->idnumber = get_field('course_modules', 'idnumber', 'id', $cm->id);
            }
            $idnumber = $cm->idnumber;
        }
        $params = array(
            'itemname' => $this->get_name(),
            'idnumber' => $idnumber);

        $scale = $this->get_rating_scale();
        if (!$this->get_grading()) {
            $params['gradetype'] = GRADE_TYPE_NONE;
        } else if ($scale > 0) {
            $params['gradetype'] = GRADE_TYPE_VALUE;
            $params['grademax'] = $scale;
            $params['grademin'] = 0;
        } else if ($scale < 0) {
            $params['gradetype'] = GRADE_TYPE_SCALE;
            $params['scaleid'] = -$scale;
        }

        if ($grades  === 'reset') {
            $params['reset'] = true;
            $grades = NULL;
        }

        $ok = grade_update('mod/forumng', $this->forumfields->course,
            'mod', 'forumng', $this->forumfields->id, 0, $grades, $params);
        if ($ok != GRADE_UPDATE_OK) {
            throw new forum_exception("Grade update failed");
        }
    }

    // Bulk forum requests
    //////////////////////

    /**
     * Queries for all forums on a course, including additional data about unread
     * posts etc.
     * NOTE: If shared forums are in use, this will usually return the CLONE
     * forum object, which doesn't hold any data about the actual forum;
     * the exception is that unread data will be obtained from the real forum.
     * If you would like to obtain the real forum instead, please make sure
     * $realforums is set to true. This has a performance cost.
     * @param object $course Moodle course object
     * @param int $userid User ID, 0 = current user, -1 = no unread data is needed
     * @param bool $unreadasbinary If true, unread data MAY BE binary (1/0)
     *   instead of containing the full number; this improves performance but
     *   only works on some databases
     * @param array $specificids If array has no entries, returns all forums
     *   on the course; if it has at least one entry, returns only those forums 
     *   with course-module ID listed in the array
     * @param bool $realforums Set this to true to obtain real forums
     *   if any are clones; has a performance cost if shared forums are used
     * @return array Array of forum objects (keys are forum IDs; in the case of
     *   shared forums, the id is of the clone not the forum, even if
     *   $realforums is set)
     */
    static function get_course_forums($course, $userid = 0,
        $unread = self::UNREAD_DISCUSSIONS, $specificids = array(),
        $realforums = false) {
        global $USER, $CFG;

        $userid = forum_utils::get_real_userid($userid);
        $result = array();
        $modinfo = self::get_modinfo_special($course, $specificids);

        // Obtains extra information needed only when acquiring unread data
        $aagforums = array();
        $viewhiddenforums = array();
        $groups = array();
        if ($unread != self::UNREAD_NONE) {
            foreach($modinfo->cms as $cmid => $cm) {
                if (count($specificids) && !in_array($cmid, $specificids)) {
                    continue;
                }
                $context = get_context_instance(CONTEXT_MODULE, $cmid);
                if ($cm->modname == 'forumng') {
                    if(has_capability(
                        'moodle/site:accessallgroups', $context, $userid)) {
                        $aagforums[] = $cm->instance;
                    }
                    if(has_capability(
                        'mod/forumng:viewallposts', $context, $userid)) {
                        $viewhiddenforums[] = $cm->instance;
                    }
                }
            }
            if ($userid == $USER->id) {
                if (array_key_exists($course->id, $USER->groupmember)) {
                    $groups = $USER->groupmember[$course->id];
                } // Else do nothing - groups list should be empty
            } else {
                $rs = forum_utils::get_recordset_sql("
SELECT
    g.id
FROM
    {$CFG->prefix}groups g
    INNER JOIN {$CFG->prefix}groups_members gm ON g.id=gm.groupid
WHERE
    g.courseid = $course->id
    AND gm.userid = $userid");
                while($rec = rs_fetch_next_record($rs)) {
                    $groups[] = $rec->id;
                }
                rs_close($rs);
            }
        }

        $rows = self::query_forums($specificids, $course, $userid,
            $unread, $groups, $aagforums, $viewhiddenforums);
        foreach ($rows as $rec) {
            // Check course-module exists
            if(!array_key_exists($rec->cm_id, $modinfo->cms)) {
                continue;
            }
            $cm = $modinfo->cms[$rec->cm_id];
            if ($cm->modname != 'forumng') {
                continue;
            }

            // Mess about with binary setting to ensure result is same, whatever
            // the database
            if ($unread == self::UNREAD_BINARY
                && isset($rec->f_numunreaddiscussions)) {
                $rec->f_hasunreaddiscussions =
                    $rec->f_numunreaddiscussions>0 ? 1 : 0;
                unset($rec->f_numunreaddiscussions);
            }

            // Create a new forum object from the database details
            $forumfields = forum_utils::extract_subobject($rec, 'f_');
            $forum = new forum($course, $cm,
                get_context_instance(CONTEXT_MODULE, $cm->id), $forumfields);
            $result[$forumfields->id] = $forum;
            if ($forum->is_shared()) {
                $forum->set_clone_reference(self::CLONE_DIRECT);
            }

            // For clone forums (only pointers to genuine shared forums)
            if ($forum->is_clone()) {
                // If we are retrieving the real forum, get it individually
                if ($realforums) {
                    $othercourse = forum_utils::get_record_sql("
SELECT
    c.*
FROM
    {$CFG->prefix}course_modules cm
    INNER JOIN {$CFG->prefix}course c ON c.id = cm.course
WHERE
    cm.id = {$forumfields->originalcmid}");
                    $extra = self::get_course_forums($othercourse, $userid,
                        $unread, array($forumfields->originalcmid));
                    if (count($extra) != 1) {
                        throw new forum_exception(
                            'Unable to find shared forum ' .
                            $forumfields->originalcmid);
                    }
                    foreach ($extra as $extraforum) {
                        $extraforum->set_clone_reference($cm->id);
                        $result[$forumfields->id] = $extraforum;
                    }
                } else if ($unread != self::UNREAD_NONE) {
                    // Even if not retrieving the real forum, we still use
                    // its undead data when unread data is on
                    $forum->init_unread_from_original($unread);
                }
            }
        }
        return $result;
    }

    private static function sort_forum_result($a, $b) {
        return strcasecmp($a->f_name, $b->f_name);
    }

    /**
     * Internal method. Queries for a number of forums, including additional
     * data about unread posts etc. Returns the database result.
     * @param array $cmids If specified, array of course-module IDs of desired
     *   forums
     * @param object $course If specified, course object
     * @param int $userid User ID, 0 = current user
     * @param int $unread Type of unread data to obtain (UNREAD_xx constant).
     * @param array $groups Array of group IDs to which the given user belongs
     *   (may be null if unread data not required)
     * @param array $aagforums Array of forums in which the user has
     *   'access all groups' (may be null if unread data not required)
     * @param array $viewhiddenforums Array of forums in which the user has
     *   'view hidden discussions' (may be null if unread data not required)
     * @return array Array of row objects
     */
    private static function query_forums($cmids=array(), $course=null,
        $userid, $unread, $groups, $aagforums, $viewhiddenforums) {
        global $CFG, $USER;
        if ((!count($cmids) && !$course)) {
            throw new forum_exception(
                "forum::query_forums requires course id or cmids");
        }
        if (count($cmids)) {
            $conditions = "cm.id " . forum_utils::in_or_equals($cmids);
        } else {
            $conditions = "f.course = {$course->id}";
        }

        $singleforum = count($cmids) == 1 ? reset($cmids) : false;
        $inviewhiddenforums = forum_utils::in_or_equals($viewhiddenforums);

        // This array of additional results is used later if combining
        // standard results with single-forum calls.
        $plusresult = array();

        // For read tracking, we get a count of total number of posts in
        // forum, and total number of read posts in the forum (this
        // is so we can display the number of UNread posts, but the query
        // works that way around because it will return 0 if no read
        // information is stored).
        if($unread!=self::UNREAD_NONE && forum::enabled_read_tracking()) {
            // Work out when unread status ends
            $endtime = time() - $CFG->forumng_readafterdays*24*3600;
            if (!$userid) {
                $userid = $USER->id;
            }

            $ingroups = forum_utils::in_or_equals($groups);
            $inaagforums = forum_utils::in_or_equals($aagforums);

            $restrictionsql = '';
            if ($singleforum) {
                // If it is for a single forum, get the restriction from the
                // forum type
                $forum = forum::get_from_cmid($singleforum, forum::CLONE_DIRECT);
                $type = $forum->get_type();
                if ($type->has_unread_restriction()) {
                    $value = $type->get_unread_restriction_sql($forum);
                    if ($value) {
                        $restrictionsql = 'AND ' . $value;
                    }
                }
            } else {
                // When it is not for a single forum, we can only group together
                // results for types that do not place restrictions on the
                // unread count.
                $modinfo = self::get_modinfo_special($course, $cmids);
                $okayids = array();
                if (array_key_exists('forumng', $modinfo->instances)) {
                    foreach ($modinfo->instances['forumng'] as $info) {
                        if (count($cmids) && !in_array($info->id, $cmids)) {
                            continue;
                        }
                        $type = self::get_type_from_modinfo_info($info);
                        if (forum_type::get_new($type)->has_unread_restriction()) {
                            // This one's a problem! Do it individually
                            $problemresults = self::query_forums(
                                array($info->id), null, $userid, $unread,
                                $groups, $aagforums, $viewhiddenforums);
                            foreach($problemresults as $problemresult) {
                                $plusresult[$problemresult->f_id] = $problemresult;
                            }
                        } else {
                            $okayids[] = $info->id;
                        }
                    }
                }

                if(count($okayids) == 0) {
                    // There are no 'normal' forums, so return result so far
                    // after sorting it
                    uasort($plusresult, 'forum::sort_forum_result');
                    return $plusresult;
                } else {
                    // Fall through to normal calculation, but change conditions
                    // to include only the 'normal' forums
                    $conditions .= " AND cm.id " . forum_utils::in_or_equals(
                        $okayids);
                }
            }

            // NOTE fpfirst is used only by forum types, not here
            $now = time();
            $sharedquerypart = "
FROM
    {$CFG->prefix}forumng_discussions fd
    INNER JOIN {$CFG->prefix}forumng_posts fplast ON fd.lastpostid = fplast.id
    INNER JOIN {$CFG->prefix}forumng_posts fpfirst ON fd.postid = fpfirst.id
    LEFT JOIN {$CFG->prefix}forumng_read fr ON fd.id = fr.discussionid AND fr.userid=$userid
WHERE
    fd.forumid=f.id AND fplast.modified>$endtime
    AND (
        (fd.groupid IS NULL)
        OR (fd.groupid $ingroups)
        OR cm.groupmode=" . VISIBLEGROUPS . "
        OR (fd.forumid $inaagforums)
    )
    AND fd.deleted=0
    AND (
        ((fd.timestart=0 OR fd.timestart <= $now)
        AND (fd.timeend=0 OR fd.timeend > $now))
        OR (fd.forumid $inviewhiddenforums)
    )
    AND ((fplast.edituserid IS NOT NULL AND fplast.edituserid<>$userid)
        OR fplast.userid<>$userid)
    AND (fr.time IS NULL OR fplast.modified>fr.time)
    $restrictionsql";

            // Note: There is an unusual case in which this number can
            // be inaccurate. It is to do with ignoring messages the user
            // posted. We consider a discussion as 'not unread' if the last
            // message is by current user. In actual fact, a discussion could
            // contain unread messages if messages were posted by other users
            // after this user viewed the forum last, but before they posted
            // their reply. Since this should be an infrequent occurrence I
            // believe this behaviour is acceptable.
            if($unread==self::UNREAD_BINARY &&
                ($CFG->dbtype=='postgres7' || $CFG->dbtype=='mysql')) {
                // Query to get 0/1 unread discussions count
                $readtracking = "
(SELECT
    COUNT(1)
FROM (
    SELECT
        1
    $sharedquerypart
    LIMIT 1) innerquery
) AS f_hasunreaddiscussions";
            } else {
                // Query to get full unread discussions count
                $readtracking = "
(SELECT
    COUNT(1)
$sharedquerypart
) AS f_numunreaddiscussions";
            }
        } else {
            $readtracking = "NULL AS numreadposts, NULL AS timeread";
        }
        $now = time();
        $orderby = "LOWER(f.name)";

        // Main query. This retrieves:
        // - Full forum fields
        // - Basic course-module and course data (not whole tables)
        // - Discussion count
        // - Unread data, if enabled
        // - User subscription data
        $rs = get_recordset_sql($sql = "
SELECT
    " . forum_utils::select_forum_fields('f') . ",
    " . forum_utils::select_course_module_fields('cm') . ",
    " . forum_utils::select_course_fields('c') . ",
    (SELECT COUNT(1)
        FROM {$CFG->prefix}forumng_discussions cfd
        WHERE cfd.forumid=f.id AND cfd.deleted=0
        AND (
            ((cfd.timestart=0 OR cfd.timestart <= $now)
            AND (cfd.timeend=0 OR cfd.timeend > $now))
            OR (cfd.forumid $inviewhiddenforums)
        )
        ) AS f_numdiscussions,
    $readtracking
FROM
    {$CFG->prefix}forumng f
    INNER JOIN {$CFG->prefix}course_modules cm ON cm.instance=f.id
        AND cm.module=(SELECT id from {$CFG->prefix}modules WHERE name='forumng')
    INNER JOIN {$CFG->prefix}course c ON c.id=f.course
WHERE
    $conditions
ORDER BY
    $orderby");
        if(!$rs) {
            throw new forum_exception("Failed to retrieve forums");
        }
        $result = recordset_to_array($rs);
        rs_close($rs);
        if(count($plusresult) > 0) {
            foreach ($plusresult as $key=>$value) {
                $result[$key] = $value;
            }
            uasort($result, 'forum::sort_forum_result');
        }
        return $result;
    }

    // Attachment playspaces (for AJAX)
    ///////////////////////////////////

    /**
     * Gets folder corresponding to a particular playspace.
     * @param string $playspaceid Two numbers e.g. "12345,390923423"
     * @return string Folder address
     */
    static function get_attachment_playspace_folder($playspaceid) {
        global $CFG;
        return $CFG->dataroot . '/moddata/forumng/playspaces/' . $playspaceid;
    }

    /**
     * Creates an attachment playspace for editing attachments. The playspace
     * contains all current attachments of the post, if specified.
     * @param forum_post $post Optional post to copy attachments from
     * @return int Unique playspace ID
     */
    static function create_attachment_playspace($post=null, $userid=0) {
        // Pick random ID and create folder
        do {
            $playspaceid = forum_utils::get_real_userid($userid) . ',' . mt_rand();
            $folder = self::get_attachment_playspace_folder($playspaceid);
        } while(is_dir($folder));
        if(!check_dir_exists($folder, true, true)) {
            throw new forum_exception("Failed to create playspace folder $folder");
        }

        // Copy files into it
        if ($post && $post->has_attachments()) {
            $postfiles = $post->get_attachment_names();
            $postfolder = $post->get_attachment_folder();
            foreach ($postfiles as $name) {
                forum_utils::copy("$postfolder/$name", "$folder/$name");
            }
        }

        // Return id
        return $playspaceid;
    }

    const DUPLICATESFOLDER = '___duplicates';

    /**
     * Lists all files in a playspace.
     * @param string $playspaceid ID of playspace (two comma-separated numbers)
     * @param bool $duplicates If true, actually lists duplicate files
     * @return array Array of full names including paths
     */
    static function get_attachment_playspace_files($playspaceid, $duplicates) {
        $folder = self::get_attachment_playspace_folder($playspaceid);
        if ($duplicates) {
            self::delete_attachment_playspace($playspaceid, true);
            forum_utils::mkdir("$folder/" . self::DUPLICATESFOLDER);
        }
        $handle = forum_utils::opendir($folder);
        $result = array();
        while (false !== ($name = readdir($handle))) {
            if ($name != '.' && $name != '..' && $name != self::DUPLICATESFOLDER) {
                $path = "$folder/$name";
                if ($duplicates) {
                    $duplicate = "$folder/" . self::DUPLICATESFOLDER . "/$name";
                    forum_utils::copy($path, $duplicate);
                    $result[] = $duplicate;
                } else {
                    $result[] = $path;
                }
            }
        }
        closedir($handle);
        return $result;
    }

    /**
     * Deletes an unnecessary attachment playspace.
     * @param string $playspaceid ID of playspace
     * @param bool $duplicates If true, only deletes duplicates folder
     */
    static function delete_attachment_playspace($playspaceid, $duplicates) {
        // Delete playspace folder and all files
        $folder = self::get_attachment_playspace_folder($playspaceid);
        if ($duplicates) {
            $folder = "$folder/" . self::DUPLICATESFOLDER;
        }
        if (!remove_dir($folder)) {
            throw new forum_exception("Error deleting playspace: $folder");
        }
    }

    // Search
    /////////

    /** @return True if the OU search extension is available */
    public static function search_installed() {
        return @include_once(dirname(__FILE__) .
            '/../../blocks/ousearch/searchlib.php');
    }

    /**
     * Update all documents for ousearch.
     * @param bool $feedback If true, prints feedback as HTML list items
     * @param int $courseid If specified, restricts to particular courseid
     * @param int $cmid If specified, restricts to particular cmid
     */
    static function search_update_all($feedback=false, $courseid=0, $cmid=0) {
        global $CFG;

        // If cmid is specified, only retrieve that one
        if ($cmid) {
            $cmrestrict = "cm.id = $cmid AND";
        } else {
            $cmrestrict = '';
        }
        // Get module-instances that need updating
        $cms = get_records_sql("
SELECT
    cm.id, cm.course, cm.instance, f.name
FROM
    {$CFG->prefix}forumng f
    INNER JOIN {$CFG->prefix}course_modules cm ON cm.instance=f.id
WHERE
    $cmrestrict
    cm.module = (SELECT id FROM {$CFG->prefix}modules m WHERE name='forumng')".
            ($courseid ? " AND f.course=$courseid" : ''));
        $cms = $cms ? $cms : array();

        // Print count
        if($feedback && !$cmid) {
            print '<li>' . get_string('search_update_count', 'forumng',
                '<strong>'.count($cms).'</strong>') . '</li>';
        }

        // This can take a while, so let's be sure to reset the time limit.
        // Store the existing limit; we will set this existing value again
        // each time around the loop. Note: Despite the name, ini_get returns
        // the most recently set time limit, not the one from php.ini.
        $timelimitbefore = ini_get('max_execution_time');

        // Loop around updating
        foreach ($cms as $cm) {
            forum_utils::start_transaction();

            // Wipe existing search data, if any
            ousearch_document::delete_module_instance_data($cm);

            // Get all discussions for this forum
            $discussions = get_records('forumng_discussions',
                'forumid', $cm->instance, '', 'id, postid');
            $discussions = $discussions ? $discussions : array();
            if($feedback) {
                print '<li><strong>' . $cm->name . '</strong> (' . count($discussions) . '):';
            }

            // Process each discussion
            foreach ($discussions as $discussionrec) {
                // Ignore discussion with no postid
                // (This should not happen, where ther is a $discussionrec->id 
                // it also shopuld have a $discussionrec->postid. This if-statement 
                // fixes bug 10497 and would not have any side-effect.)
                if (!$discussionrec->postid) {
                    continue;
                }
                set_time_limit($timelimitbefore);
                $discussion = forum_discussion::get_from_id($discussionrec->id,
                    forum::CLONE_DIRECT, -1);
                $root = $discussion->get_root_post();
                $root->search_update();
                $root->search_update_children();
                print '. ';
                flush();
            }

            forum_utils::finish_transaction();

            if($feedback) {
                print '</li>';
            }
        }
    }

    // UI
    /////

    /**
     * Returns HTML for search form, or blank if there is no search facility
     * in this forum.
     * @param string $querytext Text of query (not escaped)
     * @return string HTML code for search form
     */
    public function display_search_form($querytext='') {
        $cmid = $this->get_course_module_id();
        $strsearchthisactivity = get_string('searchthisforum', 'forumng');
        $queryesc = htmlspecialchars($querytext);
        $linkfields = $this->get_link_params(forum::PARAM_FORM);
        $buttontext = !forum::search_installed() ? '' : <<<EOF
<form action="search.php" method="get"><div>
$linkfields
  <label class="accesshide" for="forumng-searchquery">{$strsearchthisactivity}</label>
  <input type="text" name="query" id="forumng-searchquery" value="{$queryesc}"/>
  <input type="submit" value="{$strsearchthisactivity}"/>
</div></form>
EOF;
        return $buttontext;
    }
    /**
     * Opens table tag and displays header row ready for calling
     * display_discussion_list_item() a bunch of times.
     * @param int $groupid Group ID for display
     * @param string $baseurl Base URL of current page
     * @param int $sort forum::SORT_xx constant for sort order
     * @return string HTML code for start of table
     */
    public function display_discussion_list_start($groupid, $baseurl, $sort, $sortreverse) {
        return $this->get_type()->display_discussion_list_start(
            $this, $groupid, $baseurl, $sort, $sortreverse);
    }

    /**
     * Prints special row (if used) that divides between sticky and normal
     * discussions in the table.
     * @param int $groupid Group ID for display
     * @return string HTML code for divider row
     */
    public function display_discussion_list_divider($groupid) {
        return $this->get_type()->display_discussion_list_divider(
            $this, $groupid);
    }

    /**
     * Closes table tag after calling display_discussion_list_start().
     * @param int $groupid Group ID for display
     * @return string HTML code for end of table
     */
    public function display_discussion_list_end($groupid) {
        return $this->get_type()->display_discussion_list_end(
            $this, $groupid);
    }

    /**
     * Prints heading and start of table for draft posts.
     * @return string HTML code for start of draft area
     */
    public function display_draft_list_start() {
        return $this->get_type()->display_draft_list_start($this);
    }

    /**
     * Prints end of table for draft posts.
     * @return string HTML code for end of draft area
     */
    public function display_draft_list_end() {
        return $this->get_type()->display_draft_list_end($this);
    }

    /**
     * Prints heading and start of table for flagged posts
     * @return string HTML code for start of flagged area
     */
    public function display_flagged_list_start() {
        return $this->get_type()->display_flagged_list_start($this);
    }

    /**
     * Prints end of table for flagged posts
     * @return string HTML code for end of flagged area
     */
    public function display_flagged_list_end() {
        return $this->get_type()->display_flagged_list_end($this);
    }

    /**
     * Displays the intro field, if present.
     * @return string HTML code for intro or empty string if none
     */
    public function display_intro() {
        return $this->get_type()->display_intro($this);
    }

    /**
     * Displays the post button, if user is permitted to post.
     * @param int $groupid Group ID being shown
     * @return string HTML code for post button or empty string if none
     */
    public function display_post_button($groupid) {
        if ($this->can_start_discussion($groupid, $whynot)) {
            return $this->get_type()->display_post_button($this, $groupid);
        } else {
            if ($whynot) {
                return '<p class="forumng-cannotstartdiscussion">' .
                    get_string($whynot, 'forumng') . '</p>';
            } else {
                return '';
            }
        }
    }

    /**
     * Displays discussion list features for this forum. Features are the
     * plugins in the 'feature' subfolder - basically a row of buttons along
     * the bottom.
     * @param int $groupid Group ID
     * @return string HTML code for discussion list features
     */
    public function display_discussion_list_features($groupid) {
        // Print discussion list feature buttons (userposts button)
        $features = '';
        foreach(discussion_list_feature::get_all() as $feature) {
            if ($feature->should_display($this, $groupid)) {
                $features .= $feature->display($this, $groupid);
            }
        }
        if ($features) {
            return '<div id="forumng-features">' . $features . '</div>';
        } else {
            return '';
        }
    }

    /**
     * Displays subscribe options for this forum.
     * @param bool $expectquery True if we expect this to make a DB query
     * @return string HTML code for subscribe information section
     */
    public function display_subscribe_options($expectquery = false) {
        // Is user subscribed to this forum?
        $text = '';
        $subscribed = self::NOT_SUBSCRIBED;
        $canchange = false;
        $canview = false;
        $type = $this->get_effective_subscription_option();
        $cm = $this->get_course_module();
        if ($type == self::SUBSCRIPTION_NOT_PERMITTED) {
            // Subscription not allowed
            $text = get_string('subscribestate_not_permitted', 'forumng');
        } else if (!$this->can_be_subscribed() || isguest()) {
            // Current user not allowed to subscribe
            $text = get_string('subscribestate_no_access', 'forumng');
        } else {
            global $USER;
            $subscription_info = $this->get_subscription_info(0, $expectquery);
            if (!$this->get_group_mode()) {
                if ($subscription_info->wholeforum) {
                    //subscribed to the entire forum
                    $subscribed = self::FULLY_SUBSCRIBED;
                    $text = get_string('subscribestate_subscribed', 'forumng',
                        '<strong>' . $USER->email . '</strong>');
                } else if (count($subscription_info->discussionids) == 0) {
                    //not subscribed at all
                    $text = get_string('subscribestate_unsubscribed', 'forumng');
                } else {
                    //subscribed to one or more discussions
                    $subscribed = self::PARTIALLY_SUBSCRIBED;
                    $text = get_string('subscribestate_partiallysubscribed', 'forumng',
                        '<strong>' . $USER->email . '</strong>');
                }
            } else {
                $currentgroupid = $this->get_activity_group($cm, true);
                if ($subscription_info->wholeforum) {
                    //subscribed to the entire forum
                    if ($currentgroupid == forum::ALL_GROUPS) {
                        $text = get_string('subscribestate_subscribed', 'forumng',
                        '<strong>' . $USER->email . '</strong>');
                        $subscribed = self::FULLY_SUBSCRIBED;
                    } else {
                        $text = get_string('subscribestate_subscribed', 'forumng',
                                '<strong>' . $USER->email . '</strong>') . ' ' .
                                ($canchange ? get_string(
                                    'subscribestate_subscribed_notinallgroup',
                                    'forumng') : '');
                        $subscribed = self::FULLY_SUBSCRIBED_GROUPMODE;
                    }
                } else if (count($subscription_info->groupids) == 0) {
                    if (count($subscription_info->discussionids) == 0) {
                        //not subscribed at all
                        if ($currentgroupid == forum::ALL_GROUPS) {
                            //return the default value NOT_SUBSCRIBED
                            $text = get_string('subscribestate_unsubscribed', 'forumng');
                        } else {
                            $text = get_string('subscribestate_unsubscribed_thisgroup', 'forumng');
                            $subscribed = self::THIS_GROUP_NOT_SUBSCRIBED;
                        }
                    } else {
                        //only subscribed to discussions;
                        if ($currentgroupid == forum::ALL_GROUPS) {
                            $subscribed = self::PARTIALLY_SUBSCRIBED;
                            $text = get_string('subscribestate_partiallysubscribed', 'forumng',
                                '<strong>' . $USER->email . '</strong>');
                        } else {
                            //Set default that the discussions do not belong to the current group
                            $text = get_string('subscribestate_unsubscribed_thisgroup', 'forumng');
                            $subscribed = self::THIS_GROUP_NOT_SUBSCRIBED;
                            //Check if any of the discussions belongs to the current group
                            foreach ($subscription_info->discussionids as $discussionid => $groupid) { 
                                if ($groupid == $currentgroupid) {
                                    $text = get_string('subscribestate_partiallysubscribed_thisgroup', 'forumng',
                                        '<strong>' . $USER->email . '</strong>');
                                    $subscribed = self::THIS_GROUP_PARTIALLY_SUBSCRIBED;
                                    break;
                                }
                            }
                        }
                    }
                    
                } else {
                    //subscribed to one or more groups as the groupids array are not empty
                    if ($currentgroupid == forum::ALL_GROUPS) {
                        $text = get_string('subscribestate_groups_partiallysubscribed', 'forumng',
                            '<strong>' . $USER->email . '</strong>');
                        //treat this scenario the same as discussions partically subscribed since they all give the same options which is
                        //subscribe to the whole forum or unsubscribe from the whole forum
                        $subscribed = self::PARTIALLY_SUBSCRIBED;
                    } else {
                        //Check if have subscribed to the current group
                        $currentgroup_subscription_status = false;
                        //Check if any of the discussions belong to the current group
                        foreach ($subscription_info->groupids as $id) { 
                            if ($id == $currentgroupid) {
                                $text = get_string('subscribestate_subscribed_thisgroup', 'forumng',
                                    '<strong>' . $USER->email . '</strong>');
                                $subscribed = self::THIS_GROUP_SUBSCRIBED;
                                $currentgroup_subscription_status = true;
                                break;
                            }
                        }
                        if (!$currentgroup_subscription_status) {
                            //not subscribed to the current group. 
                            if (count($subscription_info->discussionids) == 0) {
                                $text = get_string('subscribestate_unsubscribed_thisgroup', 'forumng');
                                $subscribed = self::THIS_GROUP_NOT_SUBSCRIBED;
                            } else {
                                //Check if any discussions subscribed belong to this group
                                //Set default that the discussions do not belong to the current group
                                $text = get_string('subscribestate_unsubscribed_thisgroup', 'forumng');
                                $subscribed = self::THIS_GROUP_NOT_SUBSCRIBED;
                                //Check if any of the discussions belong to the current group
                                foreach ($subscription_info->discussionids as $discussionid => $groupid) { 
                                    if ($groupid == $currentgroupid) {
                                        $text = get_string('subscribestate_partiallysubscribed_thisgroup', 'forumng',
                                            '<strong>' . $USER->email . '</strong>');
                                        $subscribed = self::THIS_GROUP_PARTIALLY_SUBSCRIBED;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Display extra information if they are forced to subscribe
            if ($this->is_forced_to_subscribe()) {
                $text .= ' ' . get_string('subscribestate_forced', 'forumng');
            } else {
                $canchange = true;
            }
        }

        return $this->get_type()->display_subscribe_options($this, $text,
            $subscribed, $canchange, $this->can_view_subscribers());
    }

    /**
     * @param object $user User object
     * @return string HTML that contains a link to the user's profile, with
     *   their name as text
     */
    public function display_user_name($user) {
        return fullname($user, has_capability(
            'moodle/site:viewfullnames', $this->get_context()));
    }

    /**
     * @param object $user User object
     * @return string HTML that contains a link to the user's profile, with
     *   their name as text
     */
    public function display_user_link($user) {
        global $CFG;
        if ($this->is_shared()) {
            $coursepart = '';
        } else {
            $coursepart = '&amp;course=' . $this->get_course()->id;
        }
        return "<a href='{$CFG->wwwroot}/user/view.php?id={$user->id}" .
            "$coursepart'>" . $this->display_user_name($user) . "</a>";
    }

    /**
     * @param int $groupid Group ID
     * @return string HTML links for RSS/Atom feeds to this discussion (if
     *   enabled etc)
     */
    public function display_feed_links($groupid) {
        global $CFG;

        // Check they're allowed to see it
        if ($this->get_effective_feed_option() == forum::FEEDTYPE_NONE) {
            return '';
        }

        // Icon (decoration only) and Atom link
        $strrss = get_string('rss');
        $stratom = get_string('atom', 'forumng');
        $feed = '<div class="forumng-feedlinks">';
        $feed .= '<a class="forumng-iconlink" href="'. htmlspecialchars(
            $this->get_feed_url(forum::FEEDFORMAT_ATOM, $groupid)) . '">';
        $feed .= "<img src='$CFG->pixpath/i/rss.gif' alt=''/> " .
            '<span class="forumng-textbyicon">' . $stratom . '</span></a> ';
        $feed .= '<a href="'. htmlspecialchars($this->get_feed_url(
            forum::FEEDFORMAT_RSS, $groupid)) . '">' . $strrss . '</a> ';
        $feed .= '</div>';
        return $feed;
    }

    /**
     * Displays warnings for the invalid forum archive setting.
     * @return string HTML code for the warning message
     */
    public function display_archive_warning() {
        $course = $this->get_course();
        if (has_capability('moodle/course:manageactivities', $this->get_context(), 0)) {
            if ($this->forumfields->removeafter && $this->forumfields->removeto) {
                $modinfo = get_fast_modinfo($course);
                $warningtext = '';
                if (!($this->can_archive_forum($modinfo, $warningtext))) {
                    return '<div class="forumng-archivewarning">' . $warningtext . '</div>';
                }
            }
        }
        return '';
    }

    public function display_sharing_info() {
        global $CFG;
        // If it's not shared, nothing to show
        if (!$this->is_shared()) {
            return '';
        }
        // Only show this to people who can edit and stuff
        if (!has_capability('moodle/course:manageactivities', $this->get_context(), 0)) {
            return '';
        }
        // OK, let's show!
        $out = '<div class="forumng-shareinfo">';
        if ($this->get_course_module_id() != $this->get_course_module_id(true)) {
            // We are looking at a clone. Show link to original, if user can 
            // see it, otherwise text.
            $a = (object)array(
                'url' => $CFG->wwwroot . '/mod/forumng/view.php?id=' .
                        $this->get_course_module_id(true),
                'shortname' => s($this->get_course(true)->shortname)
            );
            $out .= get_string('sharedviewinfoclone', 'forumng', $a);
        } else {
            // We are looking at an original.
            // I want to display the idnumber here - unfortuantely this requires
            // an extra query because it is not included in get_fast_modinfo.
            $idnumber = get_field('course_modules', 'idnumber', 'id',
                $this->get_course_module_id(true));
            $out .= get_string('sharedviewinfooriginal', 'forumng', $idnumber);
            $out .= ' ';

            // Show links to each clone, if you
            // can see them.
            $contexts = $this->get_clone_contexts();
            if (count($contexts) == 0) {
                $out .= get_string('sharedviewinfonone', 'forumng');
            } else {
                $list = '';
                foreach ($contexts as $context) {
                    if ($list) {
                        $list .= ', ';
                    }

                    // Make it a link if you have access
                    if ($link = has_capability('moodle/course:view', $context)) {
                        $list .= '<a href="' . $CFG->wwwroot .
                                '/mod/forumng/view.php?id=' .
                                $context->instanceid . '">';
                    }
                    $list .= s($context->courseshortname);
                    if ($link) {
                        $list .= '</a>';
                    }
                }
                $out .= get_string('sharedviewinfolist', 'forumng', $list);
            }
        }
        $out .= '</div>';
        return $out;
    }

    /**
     * Prints the header and breadcrumbs for a page 'within' a forum.
     * @param string $pagename Name of page
     * @param array $navigation If specified, adds extra elements before the
     *   page name
     */
    public function print_subpage_header($pagename, $navigation=array()) {
        global $PAGEWILLCALLSKIPMAINDESTINATION;
        $PAGEWILLCALLSKIPMAINDESTINATION = true;

        $navigation[] = array(
            'name'=>$pagename, 'type'=>'forumng');

        print_header_simple(format_string($this->get_name()) . ': ' . $pagename,
            "", build_navigation($navigation, $this->get_course_module()), "", "", true,
            '', navmenu($this->get_course(), $this->get_course_module()));

        print skip_main_destination();
    }

    /**
     * Prints out (immediately; must be after header) script tags and JS code
     * for the forum's JavaScript library, and required YUI libraries.
     * @param int $cmid If specified, passes this through to JS
     * @param int $absolute If true, use absolute path to embed the JS file. Default set to false
     */
    public function print_js($cmid=0, $absolute = false) {
        $simple = get_user_preferences('forumng_simplemode','');
        if (forum_utils::is_bad_browser() || $simple) {
            return;
        }

        global $CFG;
        if(ajaxenabled() || class_exists('ouflags')) {
            // YUI and basic script
            require_js(array('yui_yahoo', 'yui_event', 'yui_connection',
                'yui_dom', 'yui_animation'));
            if ($absolute) {
                print '<script type="text/javascript" src="'. $CFG->wwwroot . '/mod/forumng/forumng.js"></script>';
            } else {
                print '<script type="text/javascript" src="forumng.js"></script>';
            }

            // Language strings and other data for JS
            $strings = '';
            $mainstrings = array(
                'showadvanced' => null,
                'hideadvanced' => null,
                'rate' => null,
                'expand' => '#',
                'jserr_load' => null,
                'jserr_save' => null,
                'jserr_alter' => null,
                'confirmdelete' => null,
                'confirmundelete' => null,
                'deletepostbutton' => null,
                'undeletepostbutton' => null,
                'js_nratings' => null,
                'js_nratings1' => null,
                'js_nopublicrating' => null,
                'js_publicrating' => null,
                'js_nouserrating' => null,
                'js_userrating' => null,
                'js_outof' => null,
                'js_clicktosetrating' => null,
                'js_clicktosetrating1' => null,
                'js_clicktoclearrating' => null,
                'edit_timeout' => null,
                'selectlabel' => null,
                'selectintro' => null,
                'confirmselection' => null,
                'selectedposts' => null,
                'discussion' => null,
                'selectorall' => null,
                'flagon' => null,
                'flagoff' => null,
                'clearflag' => null,
                'setflag' => null);
            if ($this->has_post_quota()) {
                $mainstrings['quotaleft_plural'] = (object)array(
                    'posts'=>'#', 'period' => $this->get_max_posts_period(true, true));
                $mainstrings['quotaleft_singular'] = (object)array(
                    'posts'=>'#', 'period' => $this->get_max_posts_period(true, true));
            }
            foreach($mainstrings as $string => $value) {
                if ($strings !== '') {
                    $strings .= ',';
                }
                $strings .= $string . ':"' .
                    addslashes_js(get_string($string, 'forumng', $value)) . '"';
            }
            foreach (array('cancel', 'delete', 'add', 'selectall',
                'deselectall') as $string) {
                $strings .= ',core_' . $string . ':"' .
                    addslashes_js(get_string($string)) . '"';
            }

            // Use star ratings where the scale is between 2 and 5 (3 and 6 stars)
            $scale = $this->get_rating_scale();
            if ($scale > 1 && $scale < 6) {
                $ratingstars = $scale;
            } else {
                $ratingstars = 0;
            }

            print '<script type="text/javascript">//<!--' . "\n" .
                'forumng_pixpath="' . addslashes_js(
                    $CFG->pixpath ) . '";' .
                'forumng_modpixpath="' . addslashes_js(
                    $CFG->modpixpath . '/forumng') . '";' .
                'forumng_strings={' . $strings . '};' .
                'forumng_ratingstars=' . $ratingstars . ';'.
                ($cmid ? 'forumng_cmid=' . $cmid . ';' : '') . "\n" .
                '//--></script>';
        }
    }

    // Feeds
    /////////

    /**
     * Key that allows access to this forum's Atom/RSS feeds
     * @param int $groupid Group ID/constant
     * @param int $userid User ID or 0 for current
     * @return Value of required authentication key
     */
    public function get_feed_key($groupid, $userid=0) {
        $userid = forum_utils::get_real_userid($userid);
        switch ($groupid) {
            case self::ALL_GROUPS:
                if ($this->get_group_mode()) {
                    $group = 'all';
                    break;
                }
                // Otherwise not in group mode, so actually fall through
            case self::NO_GROUPS:
                $group = 'none';
                break;
            default:
                $group = $groupid;
                break;
        }
        $text = $this->forumfields->magicnumber . $group . '_' . $userid;
        return sha1($text);
    }

    /**
     * @return int Number of items that should be included in Atom/RSS feeds
     *   for this forum
     */
    public function get_effective_feed_items() {
        global $CFG;

        // Global 'force' used if set
        $result = $CFG->forumng_feeditems;
        if ($result == -1) {
            // Otherwise use module setting
            $result = $this->forumfields->feeditems;
        }

        return $result;
    }

    /**
     * Gets URL for an Atom/RSS feed.
     * @param int $feedformat FEEDFORMAT_xx constant
     * @param int $groupid Group ID
     * @param int $userid User ID or 0 for current
     * @return string URL for feed
     */
    public function get_feed_url($feedformat, $groupid, $userid=0) {
        global $CFG;
        $userid = forum_utils::get_real_userid($userid);

        return $CFG->wwwroot . '/mod/forumng/feed.php?' .
            $this->get_link_params(forum::PARAM_PLAIN) .
            '&user=' . $userid . ($groupid == self::ALL_GROUPS
                || $groupid == self::NO_GROUPS ? '' : '&group=' . $groupid) .
            '&key=' . $this->get_feed_key($groupid, $userid) . '&format=' .
            ($feedformat == self::FEEDFORMAT_RSS ? 'rss' : 'atom');
    }

    /**
     * Obtains list of discussions to include in an Atom/RSS feed (the kind
     * that lists discussions only and not full posts).
     * @param int $groupid Group ID (may be ALL_GROUPS)
     * @param int $userid User ID
     * @return array Array of forum_discussion objects
     */
    public function get_feed_discussions($groupid, $userid=0) {
        // Number of items to output
        $items = $this->get_effective_feed_items();

        // Get most recent N discussions from db
        $rs = forum_discussion::query_discussions(
            'fd.forumid = ' . $this->get_id() . ' AND fd.deleted = 0', -1,
            'timemodified DESC', 0, $items);
        $result = array();
        while ($rec = rs_fetch_next_record($rs)) {
            // Create a new discussion from the database details
            $discussion = new forum_discussion($this, $rec, true, -1);
            if ($this->get_type()->can_view_discussion($discussion, $userid)) {
                $result[$discussion->get_id()] = $discussion;
            }
        }
        rs_close($rs);
        return $result;
    }

    /**
     * Obtains list of posts to include in an Atom/RSS feed.
     * @param int $groupid Group ID (may be ALL_GROUPS)
     * @param int $userid User ID
     * @param forum_discussion $discussion Discussion object (intended only
     *   for calls via the forum_discussion method)
     * @return array Array of forum_post objects
     */
    public function get_feed_posts($groupid, $userid, $discussion=null) {
        // Don't let user view any posts in a discussion feed they can't see
        // (I don't think they should be given a key in this case, but just
        // to be sure).
        if ($discussion &&
            !$this->get_type()->can_view_discussion($discussion, $userid)) {
            return array();
        }

        // Number of items to output
        $items = $this->get_effective_feed_items();

        // Get most recent N posts from db
        if ($discussion) {
            $where = 'fd.id=' . $discussion->get_id();
        } else {
            $where = 'fd.forumid=' . $this->get_id();
            if ($this->get_group_mode() && $groupid!=self::ALL_GROUPS) {
                $where .= ' AND fd.groupid=' . $groupid;
            }
        }

        // Don't include deleted or old-version posts
        $where .= ' AND fp.oldversion=0 AND fp.deleted=0 AND fd.deleted=0';
        // Or ones out of time
        $now = time();
        $where .= " AND (fd.timestart < $now)" .
            " AND (fd.timeend = 0 OR fd.timeend > $now)";

        $postrecs = forum_post::query_posts($where,
            'GREATEST(fp.created, fd.timestart) DESC',
            false, false, false, $userid, true, false, 0, $items);
        if (count($postrecs) == 0) {
            // No posts!
            return array();
        }

        $result = array();
        if ($discussion) {
            foreach ($postrecs as $rec) {
                $post = new forum_post($discussion, $rec, null);
                $result[$rec->id] = $post;
            }
        } else {
            // Based on these posts, get all mentioned discussions
            $discussionids = array();
            $discussionposts = array();
            foreach ($postrecs as $rec) {
                $discussionids[] = $rec->discussionid;
                $discussionposts[$rec->discussionid][] = $rec->id;
            }

            $discussionpart = forum_utils::in_or_equals($discussionids);
            $rs = forum_discussion::query_discussions(
                "fd.id " . $discussionpart, -1, 'id');

            // Build the discussion and post objects
            $posts = array();
            while ($rec = rs_fetch_next_record($rs)) {
                $discussion = new forum_discussion($this, $rec, true, -1);
                if ($discussion->can_view($userid)) {
                    foreach ($discussionposts[$discussion->get_id()]
                        as $postid) {
                        $post = new forum_post($discussion,
                            $postrecs[$postid], null);
                        $posts[$postid] = $post;
                    }
                }
            }
            rs_close($rs);

            // Put them back in order of the post records, and return
            foreach ($postrecs as $rec) {
                // Records might be excluded if user can't view discussion
                if(array_key_exists($rec->id, $posts)) {
                    $result[$rec->id] = $posts[$rec->id];
                }
            }
        }
        return $result;
    }

    /**
     * Obtains all draft posts in this forum by the given or current user,
     * in reverse date order.
     * @param int $userid User whose drafts will be retrieved. If zero,
     *   retrieves draft for current user
     * @return array Array of forum_draft objects
     */
    public function get_drafts($userid=0) {
        $userid = forum_utils::get_real_userid($userid);
        return forum_draft::query_drafts("fdr.forumid = " . $this->get_id() .
            " AND fdr.userid = $userid");
    }

    /**
     * Obtains all flagged post in this forum by the given or current user,
     * in reverse data order (of when they were flagged).
     * @param int $userid User whose flags will be retrieved; 0 = current
     * @return array Array of forum_post objects
     */
    public function get_flagged_posts($userid=0) {
        // Get all flagged posts. Note that we request the discussion row as
        // well, this is necessary (a) so we can include its forumid field in
        // the query, and (b) because we will use that data to construct
        // basic discussion objects (without having to do another query).
        $records = forum_post::query_posts('fd.forumid = ' . $this->get_id() .
            ' AND ff.flagged IS NOT NULL AND fp.deleted = 0', 'ff.flagged DESC',
            false, true, false, $userid, true, true);

        // Construct post object for each one
        $result = array();
        foreach($records as $record) {
            // Get discussion details from record
            $discussionfields = forum_utils::extract_subobject($record, 'fd_');
            $discussion = new forum_discussion($this, $discussionfields, false, -1);

            // Create post object
            $post = new forum_post($discussion, $record);
            $result[$record->id] = $post;
        }

        return $result;
    }

    /**
     * @param bool $mustusecounter True if this function should return false
     *   unless one or more of the three types of post counters are in use
     * @return bool True if automatic completion is enabled for this forum
     */
    public function is_auto_completion_enabled($mustusecounter=false) {
        // If this check is really checking that one of the actual counters
        // is on, then do those first as they're simple field checks
        if ($mustusecounter && !$this->forumfields->completionposts
            && !$this->forumfields->completionreplies
            && !$this->forumfields->completiondiscussions) {
            return false;
        }

        // Note: In 1.9, completion facilities do not exist except in the OU
        // version.
        return class_exists('ouflags') &&
            completion_is_enabled($this->get_course(), $this->get_course_module()) ==
                COMPLETION_TRACKING_AUTOMATIC;
    }

    /**
     * @return int Number of posts required for this forum to be marked
     *   complete, or 0 if posts are not required for completion/completion
     *   is turned off.
     */
    public function get_completion_posts() {
        return $this->is_auto_completion_enabled()
            ? $this->forumfields->completionposts : 0;
    }

    /**
     * @return int Number of posts required for this forum to be marked
     *   complete, or 0 if posts are not required for completion/completion
     *   is turned off.
     */
    public function get_completion_discussions() {
        return $this->is_auto_completion_enabled()
            ? $this->forumfields->completiondiscussions: 0;
    }

    /**
     * @return int Number of posts required for this forum to be marked
     *   complete, or 0 if posts are not required for completion/completion
     *   is turned off.
     */
    public function get_completion_replies() {
        return $this->is_auto_completion_enabled()
            ? $this->forumfields->completionreplies : 0;
    }

    /**
     * Used by lib.php forumng_get_completion_state.
     * @param int $userid User ID
     * @param bool $type Type of comparison (or/and; can be used as return
     *   value if no conditions)
     * @return bool True if completed, false if not (if no conditions, then
     *   return value is $type)
     */
    public function get_completion_state($userid, $type) {
        global $CFG;
        $result = $type; // Default return value

        $forumid = $this->get_id();
        $postcountsql="
SELECT
    COUNT(1)
FROM
    {$CFG->prefix}forumng_posts fp
    INNER JOIN {$CFG->prefix}forumng_discussions fd ON fp.discussionid=fd.id
WHERE
    fp.userid=$userid AND fd.forumid=$forumid AND fp.deleted=0 AND fd.deleted=0";

        if ($this->forumfields->completiondiscussions) {
            $value = $this->forumfields->completiondiscussions <=
              count_records('forum_discussions', 'forum', $forumid, 'userid', $userid, 'deleted', 0);
              if($type==COMPLETION_AND) {
                $result = $result && $value;
            } else {
                $result = $result || $value;
            }
        }
        if ($this->forumfields->completionreplies) {
            $value = $this->forumfields->completionreplies <=
                get_field_sql( $postcountsql . ' AND fp.parent<>0');
            if ($type==COMPLETION_AND) {
                $result = $result && $value;
            } else {
                $result = $result || $value;
            }
        }
        if ($this->forumfields->completionposts) {
            $value = $this->forumfields->completionposts <= get_field_sql($postcountsql);
            if($type==COMPLETION_AND) {
                $result = $result && $value;
            } else {
                $result = $result || $value;
            }
        }

        return $result;
    }

    /**
     * Created to accomodate forumng on shared activities
     * where the shared activites course does not hold cm information
     * in the course table's modinfo field
     * @param $course
     * @param $cmid
     * @return $modinfo
     */
    private function get_fast_modinfo($course, $cmid) {
        global $CFG;
        if (class_exists('ouflags')) {
            require_once($CFG->dirroot.'/course/format/sharedactv/sharedactv.php');
            if (sharedactv_is_magic_course($course)) {
                // get_fast_modinfo will only ever return a minimal object, so build own
                $modinfo = new object();
                $modinfo->courseid  = $course->id;
                $modinfo->userid    = 0;
                $modinfo->sections  = array();
                $modinfo->instances = array();
                $modinfo->groups    = null;
                if (!($cm = get_coursemodule_from_id('forumng', $cmid, $course->id))) {
                    throw new forum_exception('Could not find the forum course module.');
                }
                if(!empty($CFG->enableavailability)) {
                    $cm->conditionscompletion = array();
                    $cm->conditionsgrade  = array();
                    // Unfortunately the next call really wants to call
                    // get_fast_modinfo, but that would be recursive, so we fake up a
                    // modinfo for it already
                    if(empty($minimalmodinfo)) {
                        $minimalmodinfo=new stdClass();
                        $minimalmodinfo->cms=array();
                        $minimalcm=new stdClass();
                        $minimalcm->id=$cmid;
                        $minimalcm->name='forumng';
                        $minimalmodinfo->cms[$cmid]=$minimalcm;
                    }
                    // Get availability information
                    $ci = new condition_info($cm);
                    $cm->available=$ci->is_available($cm->availableinfo,true,0,$minimalmodinfo);
                } else {
                    $cm->available=true;
                }
                $cm->uservisible = true;
                $modcontext = get_context_instance(CONTEXT_MODULE,$cm->id);
                if ((!$cm->visible or !$cm->available) and !has_capability('moodle/course:viewhiddenactivities', $modcontext, 0)) {
                    $cm->uservisible = false;
                } else if (!empty($CFG->enablegroupings) and !empty($cm->groupmembersonly)
                        and !has_capability('moodle/site:accessallgroups', $modcontext, 0)) {
                    if (is_null($modinfo->groups)) {
                        $modinfo->groups = groups_get_user_groups($course->id, 0);
                    }
                    if (empty($modinfo->groups[$cm->groupingid])) {
                        $cm->uservisible = false;
                    }
                }
                $modinfo->cms       = array($cmid=>$cm);
            } else {
                $modinfo = get_fast_modinfo($course);
            }
        } else {
            $modinfo = get_fast_modinfo($course);
        }
        return $modinfo;
    }

    // Conversion
    //////////////

    /**
     * Creates a new ForumNG by copying data (including all messages etc) from
     * an old forum. The old forum will be hidden.
     *
     * Behaviour is undefined if the old forum wasn't eligible for conversion
     * (forum_utils::get_convertible_forums).
     * @param object $course Moodle course object
     * @param int $forumcmid Old forum to convert
     * @param bool $progress If true, print progress to output
     * @param bool $hide If true, newly-created forum is also hidden
     * @param bool $nodata If true, no user data (posts, subscriptions, etc)
     *   is copied; you only get a forum with same configuration
     * @param bool $insection If true, remeber to create the new forumNG in the same section.
     * @throws forum_exception If any error occurs
     */
    public static function create_from_old_forum($course, $forumcmid, $progress, $hide, $nodata, $insection=true) {
        global $CFG;

        // Start the clock and a database transaction
        $starttime = microtime(true);
        forum_utils::start_transaction();

        // Note we do not use get_fast_modinfo because it doesn't contain the
        // complete $cm object.
        $cm = forum_utils::get_record('course_modules', 'id', $forumcmid);
        $forum = forum_utils::get_record('forum', 'id', $cm->instance);
        if ($progress) {
            print_heading(s($forum->name), '', 3);
            print '<ul><li>' . get_string('convert_process_init', 'forumng');
            flush();
        }

        // Hide forum
        forum_utils::update_record('course_modules', (object)array(
            'id' => $cm->id, 'visible'=>0));

        // Table for changed subscription constants
        $subscriptiontranslate = array(0=>1, 1=>3, 2=>2, 3=>0);

        // Get, convert, and create forum table data
        $forumng = (object)array(
            'course' => $course->id,
            'name' => addslashes($forum->name),
            'type' => 'general',
            'intro' => addslashes($forum->intro),
            'ratingscale' => $forum->scale,
            'ratingfrom' => $forum->assesstimestart,
            'ratinguntil' => $forum->assesstimefinish,
            'ratingthreshold' => 1,
            'grading' => $forum->assessed,
            'attachmentmaxbytes' => $forum->maxbytes,
            'subscription' => $subscriptiontranslate[$forum->forcesubscribe],
            'feedtype' => $forum->rsstype,
            'feeditems' => $forum->rssarticles,
            'maxpostsperiod' => $forum->blockperiod,
            'maxpostsblock' => $forum->blockafter,
            'postingfrom' => 0,
            'postinguntil' => 0,
            'typedata' => null);
        require_once($CFG->dirroot . '/mod/forumng/lib.php');

        // Note: The idnumber is required. We cannot copy it because then there
        // would be a duplicate idnumber. Let's just leave blank, people will
        // have to configure this manually.
        $forumng->cmidnumber = '';
        if (!($newforumid = forumng_add_instance($forumng))) {
            throw new forum_exception("Failed to add forumng instance");
        }

        // Create and add course-modules entry
        $newcm = new stdClass;
        $newcm->course = $course->id;
        $newcm->module = get_field('modules', 'id', 'name', 'forumng');
        if (!$newcm->module) {
            throw new forum_exception("Cannot find forumng module id");
        }
        $newcm->instance = $newforumid;
        $newcm->section = $cm->section;
        $newcm->added = time();
        $newcm->score = $cm->score;
        $newcm->indent = $cm->indent;
        $newcm->visible = 0; // Forums are always hidden until finished
        $newcm->groupmode = $cm->groupmode;
        $newcm->groupingid = $cm->groupingid;
        $newcm->idnumber = $cm->idnumber;
        $newcm->groupmembersonly = $cm->groupmembersonly;

        // Include extra OU-specific data
        if (class_exists('ouflags')) {
            $newcm->showto = $cm->showto;
            $newcm->stealth = $cm->stealth;
            $newcm->parentcmid = $cm->parentcmid;
            $newcm->completion = $cm->completion;
            $newcm->completiongradeitemnumber = $cm->completiongradeitemnumber;
            $newcm->completionview = $cm->completionview;
            $newcm->availablefrom = $cm->availablefrom;
            $newcm->availableuntil = $cm->availableuntil;
            $newcm->showavailability = $cm->showavailability;
            $newcm->parentpagename = $cm->parentpagename;
        }

        // Add
        $newcm->id = forum_utils::insert_record('course_modules', $newcm);

        // Update section
        if ($insection) {
            $section = forum_utils::get_record('course_sections', 'id', $newcm->section);
            $updatesection = (object)array(
                'id' => $section->id,
                'sequence' => str_replace(
                    $cm->id, $cm->id . ',' . $newcm->id, $section->sequence));
            if ($updatesection->sequence == $section->sequence) {
                throw new forum_exception("Unable to update sequence");
            }
            forum_utils::update_record('course_sections', $updatesection);
         }
        // Construct forum object for new forum
        $newforum = self::get_from_id($forumng->id, forum::CLONE_DIRECT);

        if ($progress) {
            print ' ' . get_string('convert_process_state_done', 'forumng') . '</li>';
        }

        if (!$nodata) {
            // Convert subscriptions
            switch ($newforum->get_effective_subscription_option()) {
                case self::SUBSCRIPTION_PERMITTED:
                    if ($progress) {
                        print '<li>' . get_string(
                            'convert_process_subscriptions_normal', 'forumng');
                        flush();
                    }
                    // Standard subscription - just copy subscriptions
                    $rs = forum_utils::get_recordset(
                        'forum_subscriptions', 'forum', $forum->id);
                    while($rec = rs_fetch_next_record($rs)) {
                        forum_utils::insert_record('forumng_subscriptions', (object)array(
                            'forumid' => $forumng->id,
                            'userid' => $rec->userid,
                            'subscribed' => 1));
                    }
                    rs_close($rs);
                    if ($progress) {
                        print ' ' . get_string(
                            'convert_process_state_done', 'forumng') . '</li>';
                    }
                    break;

                case self::SUBSCRIPTION_INITIALLY_SUBSCRIBED:
                    // Initial subscription is handled differently; the old forum
                    // stores all the subscriptions in the database, while in this
                    // forum we only store people who chose to unsubscribe
                    if ($progress) {
                        print '<li>' . get_string(
                            'convert_process_subscriptions_initial', 'forumng');
                        flush();
                    }

                    // Get list of those subscribed on old forum
                    $rs = forum_utils::get_recordset(
                        'forum_subscriptions', 'forum', $forum->id);
                    $subscribedbefore = array();
                    while($rec = rs_fetch_next_record($rs)) {
                        $subscribedbefore[$rec->userid] = true;
                    }
                    rs_close();

                    // Get list of those subscribed on new forum
                    $new = $newforum->get_subscribers();

                    // For anyone in the new list but not the old list, add an
                    // unsubscribe
                    foreach ($new as $user) {
                        if (!array_key_exists($user->id , $subscribedbefore)) {
                            forum_utils::insert_record('forumng_subscriptions', (object)array(
                                'forumid' => $forumng->id,
                                'userid' => $user->id,
                                'subscribed' => 0));
                        }
                    }

                    if ($progress) {
                        print ' ' . get_string(
                            'convert_process_state_done', 'forumng') . '</li>';
                    }
                    break;
            }

            // Convert discussions
            if ($progress) {
                print '<li>' . get_string(
                    'convert_process_discussions', 'forumng');
                flush();
            }
            $rsd = forum_utils::get_recordset(
                'forum_discussions', 'forum', $forum->id);
            $count = 0;
            while($recd = rs_fetch_next_record($rsd)) {
                // Convert discussion options
                $newd = (object)array(
                    'forumid' => $forumng->id,
                    'timestart' => $recd->timestart,
                    'timeend' => $recd->timeend,
                    'deleted' => 0,
                    'locked' => 0,
                    'sticky' => 0
                );
                if ($recd->groupid == -1 || !$newcm->groupmode) {
                    $newd->groupid = null;
                } else {
                    $newd->groupid = $recd->groupid;
                }

                // Save discussion
                $newd->id = forum_utils::insert_record(
                    'forumng_discussions', $newd);

                // Convert posts
                $lastposttime = -1;
                $discussionupdate = (object)array('id' => $newd->id);
                $postids = array(); // From old post id to new post id
                $parentposts = array(); // From new post id to old parent id
                $subjects = array(); // From new id to subject text (no slashes)
                $rsp = forum_utils::get_recordset(
                    'forum_posts', 'discussion', $recd->id);
                while ($recp = rs_fetch_next_record($rsp)) {
                    // Convert post
                    $newp = (object)array(
                        'discussionid' => $newd->id,
                        'userid' => $recp->userid,
                        'created' => $recp->created,
                        'modified' => $recp->modified,
                        'deleted' => 0,
                        'deleteuserid' => null,
                        'mailstate' => self::MAILSTATE_DIGESTED,
                        'oldversion' => 0,
                        'edituserid' => null,
                        'subject' => addslashes($recp->subject),
                        'message' => addslashes($recp->message),
                        'format' => $recp->format,
                        'important' => 0);

                    // Are there any attachments?
                    $attachments = array();
                    if (class_exists('ouflags')) {
                        // OU has customisation for existing forum that supports
                        // multiple attachments
                        $attachmentrecords = forum_utils::get_records(
                            'forum_attachments', 'postid', $recp->id);
                        foreach ($attachmentrecords as $reca) {
                            $attachments[] = $reca->attachment;
                        }
                    } else {
                        // Standard forum uses attachment field for filename
                        if ($recp->attachment) {
                            $attachments[] = $recp->attachment;
                        }
                    }
                    $newp->attachments = count($attachments) ? 1 : 0;

                    // Add record
                    $newp->id = forum_utils::insert_record('forumng_posts', $newp);

                    // Remember details for later parent update
                    $postids[$recp->id] = $newp->id;
                    if ($recp->parent) {
                        $parentposts[$newp->id] = $recp->parent;
                    } else {
                        $discussionupdate->postid = $newp->id;
                    }
                    if ($newp->created > $lastposttime) {
                        $discussionupdate->lastpostid = $newp->id;
                    }
                    $subjects[$newp->id] = $recp->subject;

                    // Copy attachments
                    $oldfolder = $CFG->dataroot .
                        "/{$course->id}/{$CFG->moddata}/forum/{$forum->id}/{$recp->id}";
                    $newfolder = forum_post::get_any_attachment_folder(
                        $course->id, $forumng->id, $newd->id, $newp->id);
                    $filesok = 0;
                    $filesfailed = 0;
                    foreach ($attachments as $attachment) {
                        // Create folder if it isn't there
                        $attachment = clean_filename($attachment);
                        check_dir_exists($newfolder, true, true);

                        // Copy file
                        try {
                            forum_utils::copy("$oldfolder/$attachment",
                                "$newfolder/$attachment");
                            $filesok ++;
                        } catch(forum_exception $e) {
                            if ($progress) {
                                print "[<strong>Warning</strong>: file copy failed for post " . $recp->id .
                                    " => " . $newp->id . ", file " . s($attachment) . "]";
                            }
                            $filesfailed ++;
                        }
                    }

                    // If all files failed, clean up
                    if ($filesfailed && !$filesok) {
                        rmdir($newfolder);
                        $noattachments = (object)array(
                            'id'=>$newp->id, 'attachments'=>0);
                        forum_utils::update_record(
                        'forumng_posts', $noattachments);
                    }

                    // Convert ratings
                    if ($forumng->ratingscale) {
                        $rsr = get_recordset('forum_ratings', 'post', $recp->id);
                        while ($recr = rs_fetch_next_record($rsr)) {
                            forum_utils::insert_record('forumng_ratings', (object)array(
                                'postid' =>  $newp->id,
                                'userid' => $recr->userid,
                                'time' => $recr->time,
                                'rating' => $recr->rating));
                        }
                        rs_close($rsr);
                    }
                }
                rs_close($rsp);

                // Update parent numbers
                $newparentids = array();
                foreach ($parentposts as $newid => $oldparentid) {
                    if (!array_key_exists($oldparentid, $postids)) {
                        throw new forum_exception(
                            "Unknown parent post $oldparentid");
                    }
                    $newparentid = $postids[$oldparentid];
                    forum_utils::update_record('forumng_posts', (object)array(
                        'id' => $newid,
                        'parentpostid' => $newparentid));
                    $newparentids[$newid] = $newparentid;
                }

                // Update subjects
                $removesubjects = array(); // Array of ints to cancel subjects
                foreach($newparentids as $newid => $newparentid) {
                    $subject = $subjects[$newid];
                    $parentsubject = $subjects[$newparentid];
                    if ($subject &&
                        ($subject == get_string('re', 'forum') . ' ' . $parentsubject
                        || $subject == $parentsubject)) {
                        $removesubjects[] = $newid;
                    }
                }
                if (count($removesubjects)) {
                    $in = forum_utils::in_or_equals($removesubjects);
                    forum_utils::execute_sql(
                        "UPDATE {$CFG->prefix}forumng_posts SET subject=NULL WHERE id $in");
                }

                // Update first/last post numbers
                forum_utils::update_record('forumng_discussions', $discussionupdate);

                // Convert read data
                $rsr = forum_utils::get_recordset_sql("
SELECT
    userid, MAX(lastread) AS lastread
FROM
    {$CFG->prefix}forum_read
WHERE
    discussionid = {$recd->id}
GROUP BY
    userid");
                while ($recr = rs_fetch_next_record($rsr)) {
                    forum_utils::insert_record('forumng_read', (object)array(
                        'discussionid' => $newd->id,
                        'userid' => $recr->userid,
                        'time' => $recr->lastread));
                }
                rs_close($rsr);

                // Display dot for each discussion
                if ($progress) {
                    print '.';
                    $count++;
                    if ($count % 10 == 0) {
                        print $count;
                    }
                    flush();
                }
            }
            rs_close($rsd);
            if ($progress) {
                print ' ' . get_string(
                    'convert_process_state_done', 'forumng') . '</li>';
            }
        }

        // Show forum
        if (!$hide && $cm->visible) {
            if ($progress) {
                print '<li>' . get_string('convert_process_show', 'forumng');
                flush();
            }
            $updatecm = (object)array(
                'id' => $newcm->id,
                'visible' => 1);
            forum_utils::update_record('course_modules', $updatecm);
            if ($progress) {
                print ' ' . get_string('convert_process_state_done', 'forumng') . '</li>';
            }
        }

        // Transfer role assignments
        $oldcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
        $newcontext = get_context_instance(CONTEXT_MODULE, $newcm->id);
        $roles = get_records('role_assignments', 'contextid', $oldcontext->id);
        if ($roles) {
            if ($progress) {
                print '<li>' . get_string('convert_process_assignments', 'forumng');
                flush();
            }
            foreach ($roles as $role) {
                $newrole = $role;
                $newrole->contextid = $newcontext->id;
                $newrole->enrol = addslashes($newrole->enrol);
                forum_utils::insert_record('role_assignments', $newrole);
            }
            if ($progress) {
                print ' ' . get_string('convert_process_state_done', 'forumng') . '</li>';
            }
        }
        // Transfer capabilities
        $capabilities = array(
            'moodle/course:viewhiddenactivities' => 'moodle/course:viewhiddenactivities',
            'moodle/site:accessallgroups' => 'moodle/site:accessallgroups',
            'moodle/site:trustcontent' => 'moodle/site:trustcontent',
            'moodle/site:viewfullnames' => 'moodle/site:viewfullnames',

            'mod/forum:viewdiscussion' => 'mod/forumng:viewdiscussion',
            'mod/forum:startdiscussion' => 'mod/forumng:startdiscussion',
            'mod/forum:replypost' => 'mod/forumng:replypost',
            'mod/forum:viewrating' => 'mod/forumng:viewrating',
            'mod/forum:viewanyrating' => 'mod/forumng:viewanyrating',
            'mod/forum:rate'=> 'mod/forumng:rate',
            'mod/forum:createattachment' => 'mod/forumng:createattachment',
            'mod/forum:deleteanypost' => 'mod/forumng:deleteanypost',
            'mod/forum:splitdiscussions' => 'mod/forumng:splitdiscussions',
            'mod/forum:movediscussions' => 'mod/forumng:movediscussions',
            'mod/forum:editanypost' => 'mod/forumng:editanypost',
            'mod/forum:viewsubscribers' => 'mod/forumng:viewsubscribers',
            'mod/forum:managesubscriptions' => 'mod/forumng:managesubscriptions',
            'mod/forum:viewhiddentimedposts' => 'mod/forumng:viewallposts'
        );
        $caps = get_records('role_capabilities', 'contextid', $oldcontext->id);
        if ($caps) {
            if ($progress) {
                print '<li>' . get_string('convert_process_overrides', 'forumng');
                flush();
            }
            foreach ($caps as $cap) {
                foreach ($capabilities as $key=>$capability) {
                    if ($cap->capability != $key) {
                        continue;
                    }
                    $newcap = $cap;
                    $newcap->contextid = $newcontext->id;
                    $newcap->capability = $capability;
                    $newcap->capability = addslashes($newcap->capability);
                    forum_utils::insert_record('role_capabilities', $newcap);
                }
            }
            if ($progress) {
                print ' ' . get_string('convert_process_state_done', 'forumng') . '</li>';
            }
        }

        // Do course cache
        rebuild_course_cache($course->id, true);

        // Update search data
        if (self::search_installed()) {
            if ($progress) {
                print '<li>' . get_string('convert_process_search', 'forumng') . '</li>';
                flush();
            }
            self::search_update_all($progress, $course->id, $newcm->id);
        }

        // OU only: Transfer external dashboard details to new forum
        if (class_exists('ouflags')) {
            if ($progress) {
                print '<li>' . get_string(
                    'convert_process_dashboard', 'forumng');
                flush();
            }
            require_once($CFG->dirroot . '/local/externaldashboard/external_dashboard.php');
            $a = new stdClass;
            list($a->yay, $a->nay) = external_dashboard::transfer_favourites(
                $forumcmid, $newcm->id);
            if ($progress) {
                print ' ' . get_string('convert_process_dashboard_done', 
                        'forumng', $a) . '</li>';
            }
        }
        if ($progress) {
            print '<li>' . get_string('convert_process_update_subscriptions', 'forumng');
            flush();
        }
        self::group_subscription_update(false, $newcm->id);
        if ($progress) {
            print ' ' . get_string('convert_process_state_done', 'forumng') . '</li>';
        }
        forum_utils::finish_transaction();

        if ($progress) {
            $a = (object)array(
                'seconds' => round(microtime(true)-$starttime, 1),
                'link' => '<a href="view.php?id=' . $newcm->id . '">' .
                    get_string('convert_newforum', 'forumng') . '</a>');
            print '</ul><p>' . get_string('convert_process_complete', 'forumng',
                $a) . '</p>';
        }
    }

    /**
     * Returns user activity report information.
     * @param int $forumid forumng id
     * @param int $userid Moodle user id
     * @return object or false
     */
    public static function get_user_activityreport($forumid, $userid) {
        global $CFG;
        $sql = 'SELECT COUNT(p.id) AS postcount, MAX(p.modified) AS lastpost
            FROM '.$CFG->prefix.'forumng_discussions d, '.$CFG->prefix.'forumng_posts p
            WHERE d.forumid = '.$forumid.'
            AND p.userid = '.$userid.'
            AND d.deleted=0 AND p.deleted=0 AND p.oldversion=0
            AND p.discussionid = d.id';
        try {
            $posts = forum_utils::get_record_sql($sql);
            return $posts;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Gets all users within this forum who are supposed to be 'monitored'
     * (that means users who are in the monitorroles setting).
     *
     * Note: In Moodle 2 we should be able to replace this with getting the
     * enrolled users for the course, I think?
     * @param int $groupid Group ID or ALL_GROUPS/NO_GROUPS to get all users
     */
    function get_monitored_users($groupid) {
        global $CFG;
        if ($groupid > 0) {
            //Get all users from the chosen group
            $sql = "SELECT u.id, u.lastname, u.firstname, u.username, gm.groupid
                FROM " . $CFG->prefix . "groups_members gm
                JOIN " . $CFG->prefix . "user u ON u.id = gm.userid
                WHERE gm.groupid = $groupid 
                ORDER BY u.lastname, u.firstname";

            if ($users = get_records_sql($sql)) {
                return $users;
            }
        }
        else {
            // Get roleids from the monitor roles setting
            if (!$roleids = forum_utils::safe_explode(',',
                    $CFG->forumng_monitorroles)) {
                return array();
            }
            $roleidfind = forum_utils::in_or_equals($roleids);
            $context = $this->get_context();
            $contextids = forum_utils::safe_explode('/', $context->path);
            $contextidfind = forum_utils::in_or_equals($contextids);
            $sql = "SELECT u.id, u.lastname, u.firstname, u.username
                    FROM " . $CFG->prefix . "role_assignments ra
                    JOIN " . $CFG->prefix . "user u ON u.id = ra.userid
                    WHERE ra.roleid $roleidfind AND ra.contextid $contextidfind
                    ORDER BY u.lastname, u.firstname";
            if ($users = get_records_sql($sql)) {
                return $users;
            }
        }
        return array();
    }

    /**
     * Gets all user post counts
     * @param int $groupid
     * @param int $userid
     * @param string $type
     * @return array An associative array of $userid => (info object)
     *   where info object has ->discussions and ->replies values
     */
    public function get_all_user_post_counts($groupid, $userid, $type='discussion') {
        try {
            global $CFG;
            $forumid = $this->get_id();
            $where = " WHERE d.forumid = $forumid";
            $where .= "  AND p.userid = $userid";

            // Check whether it is a new discussion or a reply
            if ($type ==='reply') {
                $where .= " AND p.parentpostid IS NOT NULL";
            } else {
                $where .= " AND p.parentpostid IS NULL";
            }

            $sql = 'SELECT p.id, p.userid, p.discussionid, p.parentpostid 
                FROM '.$CFG->prefix.'forumng_discussions d
                JOIN '.$CFG->prefix.'forumng_posts p
                ON d.id = p.discussionid' .
                $where;
            if ($posts = get_records_sql($sql)) {
                $userposts = array();
                $userposts[$userid] = count($posts); 
                return $userposts;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns true if OK to archive the old discussions to the target forum.
     * @param object $modinfo Moodle get_fast_modinfo data
     * @param string $message Throwing warning if the forum cannot be archived
     * @return bool True if settings are OK
     */
    public function can_archive_forum($modinfo, &$message) {
        global $CFG;
        $forumid = $this->get_id();
        $groupmode = $this->get_group_mode();
        $groupingid = $this->get_grouping();
        $targetforumid = $this->forumfields->removeto;
        if (isset($modinfo->instances['forumng'][$targetforumid])) {
            $targetcm = $modinfo->instances['forumng'][$targetforumid];
            $targetgroupmode = groups_get_activity_groupmode($targetcm, $this->get_course());
            $targetgroupingid = $CFG->enablegroupings ? $targetcm->groupingid : 0;
            if (!$targetgroupmode) {
                return true;
            } else {
                if (($groupingid == $targetgroupingid) && $groupmode) {
                    return true;
                }
                $message = get_string('archive_errorgrouping', 'forumng');
                return false;
            }
        } else {
            $message = get_string('archive_errortargetforum', 'forumng');
            return false;
        }
    }

    // Shared/clone forums
    //////////////////////

    /**
     * Redirects to the original forum that this is a clone of, setting
     * session to indicate that user came from this forum. Does not return.
     * @throws forum_exception If this is not a clone forum I
     */
    public function redirect_to_original() {
        global $CFG, $SESSION;
        $cmid = $this->forumfields->originalcmid;
        if (!$cmid) {
            throw new forum_exception('This forum is not a clone');
        }
        if (!isset($SESSION->forumng_sharedforumcm)) {
            $SESSION->forumng_sharedforumcm = array();
        }
        $SESSION->forumng_sharedforumcm[$cmid] = $this->get_course_module();
        redirect($CFG->wwwroot . '/mod/forumng/view.php?id=' . $cmid .
                '&clone=' . $this->get_course_module()->id);
    }

    /**
     * Gets unread data from original forum.
     * @param int $unread UNREAD_xx constant
     * @throws forum_exception If this is not a clone forum
     */
    public function init_unread_from_original($unread) {
        $cmid = $this->forumfields->originalcmid;
        if (!$cmid) {
            throw new forum_exception('This forum is not a clone');
        }
        $viewhiddenforums = array();
        if (has_capability('mod/forumng:viewallposts', get_context_instance(
                CONTEXT_MODULE, $cmid))) {
            $viewhiddenforums[] = get_field(
                    'course_modules', 'instance', 'id', $cmid);
        }
        $rows = forum::query_forums(array($cmid), null, 0, $unread,
                array(), array(), $viewhiddenforums);
        if (count($rows) != 1) {
            throw new forum_exception('Unexpected data extracting base forum');
        }
        switch ($unread) {
        case self::UNREAD_BINARY:
            $this->forumfields->hasunreaddiscussions =
                    reset($rows)->f_hasunreaddiscussions;
            break;
        case self::UNREAD_DISCUSSIONS:
            $this->forumfields->numunreaddiscussions =
                    reset($rows)->f_numunreaddiscussions;
            break;
        }
    }

    /**
     * Obtains the course-module for a shared forum, or false if there isn't
     * one, based on the idnumber.
     * @param string $idnumber ID number (text, no slashes)
     * @return object Course-module object (raw from database) or false if not
     *   found / not a forum / etc
     */
    public static function get_shared_cm_from_idnumber($idnumber) {
        global $CFG;
        $idnumbersl = addslashes($idnumber);
        return get_record_sql("
SELECT
    cm.*
FROM
    {$CFG->prefix}course_modules cm
    INNER JOIN {$CFG->prefix}modules m ON m.id = cm.module
    INNER JOIN {$CFG->prefix}forumng f ON f.id = cm.instance
WHERE
    cm.idnumber = '$idnumbersl'
    AND m.name = 'forumng'
    AND f.shared = 1");
    }

    /**
     * Update the forumng_subscription table to incorporate the group subscription feature.
     * @param bool $moodleupdate If this is true, the function is running as part of the 
     *   moodle upgrade.php for Sep 2010 release. In this case, the database queries must
     *   not be changed and other code must work the same way (avoid calls to functions
     *   except Moodle standard ones)
     */
    public function group_subscription_update($moodleupdate=false, $cmid=0) {
        global $CFG;
        forum_utils::start_transaction();

        if ($cmid) {
            //only update one forum
            $optionalquery = "AND cm.id = $cmid";
        } else {
            $optionalquery = '';
        }
        // Query get the distinct forums
        $sql_count = "
SELECT
    COUNT(DISTINCT cm.id) AS totalnumberforum
FROM 
    {$CFG->prefix}forumng_subscriptions fs
    INNER JOIN {$CFG->prefix}course_modules cm on fs.forumid = cm.instance 
    INNER JOIN {$CFG->prefix}modules m on cm.module = m.id 
    INNER JOIN {$CFG->prefix}course c on c.id = cm.course 
WHERE 
    discussionid IS NULL AND m.name='forumng' $optionalquery
    AND (CASE WHEN c.groupmodeforce=1 THEN c.groupmode ELSE cm.groupmode END ) = 1";

        //Query lists all subscriptions to forums that have separate groups
        $sql_sub = "
SELECT
    cm.id AS cmid, fs.id AS subid, fs.userid, fs.forumid, c.id AS courseid, cm.groupingid 
FROM
    {$CFG->prefix}forumng_subscriptions fs
    INNER JOIN {$CFG->prefix}course_modules cm on fs.forumid = cm.instance 
    INNER JOIN {$CFG->prefix}modules m on cm.module = m.id 
    INNER JOIN {$CFG->prefix}course c on c.id = cm.course 
WHERE 
    discussionid IS NULL and m.name='forumng' $optionalquery
    AND (CASE WHEN c.groupmodeforce=1 THEN c.groupmode ELSE cm.groupmode END ) = 1 
ORDER BY cm.id, fs.id";

        //Query lists all groups that the user belongs to from the above query
        $sql_group = "
SELECT
    subs.subid, g.id AS groupid
FROM
    ($sql_sub) subs 
    INNER JOIN {$CFG->prefix}groups_members gm ON gm.userid = subs.userid 
    INNER JOIN {$CFG->prefix}groups g ON gm.groupid = g.id AND g.courseid = subs.courseid 
    LEFT JOIN {$CFG->prefix}groupings_groups gg ON gg.groupid = g.id AND subs.groupingid = gg.groupingid 
WHERE
    (subs.groupingid = 0 or gg.id IS NOT NULL)
ORDER BY
    subs.cmid, subs.subid";
        $rs = forum_utils::get_recordset_sql($sql_group);
        $results = array();
        while($rec = rs_fetch_next_record($rs)) {
            if (!array_key_exists($rec->subid, $results)) {
                $results[$rec->subid] = array();
            }
            $results[$rec->subid][] = $rec->groupid;
        }
        rs_close($rs);
        $rs = forum_utils::get_recordset_sql($sql_sub);
        $lastcmid = 0;
        $forumcount = 1;
        $totalforumcount = 0;
        $totalforumcount = count_records_sql($sql_count);

        while($rec = rs_fetch_next_record($rs)) {
            if ($lastcmid != $rec->cmid) {
                if ($moodleupdate) {
                    print "Updating the subscriptions $forumcount/$totalforumcount (current cmid:$rec->cmid) <br />";
                }
                $context = get_context_instance(CONTEXT_MODULE, $rec->cmid);
                $aagusers = get_users_by_capability($context,
                    'moodle/site:accessallgroups', 'u.id');
                $aagusers = $aagusers ? $aagusers : array();
                $lastcmid = $rec->cmid;
                $forumcount++;
            }
            if (!array_key_exists($rec->userid, $aagusers)) {
                //Delete the whole forum subscription
                forum_utils::delete_records('forumng_subscriptions', 'id', $rec->subid);
                //check if the subid exists in the results array
                if (array_key_exists($rec->subid, $results)) {
                    foreach($results[$rec->subid] as $groupid) {
                        $subrecord = new StdClass;
                        $subrecord->userid = $rec->userid;
                        $subrecord->forumid = $rec->forumid;
                        $subrecord->subscribed = 1;
                        $subrecord->groupid = $groupid;
                        forum_utils::insert_record('forumng_subscriptions', $subrecord);
                    }
                }
            }
        }
        forum_utils::finish_transaction();
    }
}
?>
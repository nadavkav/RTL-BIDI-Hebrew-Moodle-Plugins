<?php
/**
 * Base class for forum types.
 *
 * A forum type can control display of the view and discussion pages. (It
 * cannot control display of the index page because that is shared by all
 * forums so has no type!)
 *
 * For example, this could be used to add extra text or features.
 *
 * It can also control whether users can post or reply in the forum. These
 * restrictions are in addition to any applied by the normal capability system.
 */
abstract class forum_type {
    /**
     * Displays the view page (usually showing a list of discussions).
     * @param forum $forum Forum
     * @param int $groupid Group ID
     */
    abstract public function print_view_page($forum, $groupid);

    /**
     * Displays the discussion page.
     * @param forum_discussion $discussion Discussion
     */
    abstract public function print_discussion_page($discussion);

    /**
     * Displays a discussion (main part of discussion page) with given options.
     * @param forum_discussion $discussion 
     * @param object $options
     * @return string HTML content of discussion
     */
    public function display_discussion($discussion, $options) {
        // Get main bit of discussion
        $content = $discussion->get_root_post()->display_with_children($options);

        // Get lock post, if any
        $lockpost = $discussion->get_lock_post();
        if ($lockpost) {
            $content = '<div class="forumng-lockmessage">' . 
                $lockpost->display(true, 
                    array(forum_post::OPTION_NO_COMMANDS=>true)) . 
            '</div>' . $content;
        }

        return $content;
    }

    /**
     * Checks if user is allowed to post to this forum (if capabilities permit).
     * Default implementation just returns true.
     * @param forum $forum Forum
     * @param string &$whynot Output parameter - set to a language string name
     *   to give a specific reason for failure that is displayed on view
     *   screen where the button would be (otherwise will not report one)
     * @param int $userid User ID or 0 for current user
     * @return bool False to prevent user posting; true to allow it subject
     *   to normal restrictions
     */
    public function can_post($forum, &$whynot, $userid=0) {
        return true;
    }

    /**
     * Checks if user is allowed to view a discussion on this forum (if
     * capabilities/groups permit). Default implementation just returns true.
     * 
     * Note that implementing this function usually also requires implementation
     * of get_unread_restriction_sql.
     * 
     * @param forum_discussion $discussion Discussion
     * @param int $userid User ID or 0 for current user
     * @return bool False to prevent user viewing; true to allow it subject
     *   to normal restrictions
     */
    public function can_view_discussion($discussion, $userid=0) {
        return true;
    }
    
    /**
     * Forum types can change the way unread status is calculated. If this
     * is done, then extra SQL queries will be required when accessing forum 
     * unread data on courses that include forums of this type. The function
     * get_unread_restriction_sql must be implemented.
     * @return bool True if this forum changes the way 'unread' status is
     *   calculated 
     */
    public function has_unread_restriction() {
        return false;
    }
    
    /**
     * Obtains additional SQL used to restrict the list of discussions counted
     * in the 'unread' queries.
     * 
     * Valid aliases: 'fd' (forumng_discussions), 'fplast' (forumng_post; most
     *   recent post in discussion), 'fpfirst' (forumng_post; first post in 
     *   discussion), f (forummg), cm (course_modules), c (course).
     * @param forum Forum object
     * @param int $userid
     * @return string SQL code
     */
    public function get_unread_restriction_sql($forum, $userid=0) {
        return '';
    }

    /**
     * Checks if user is allowed to reply to a post on this forum (if
     * capabilities permit). Default implementation just returns true.
     * @param forum_post $inreplyto Post being replied to
     * @param int $userid User ID or 0 for current user
     * @return bool False to prevent user posting; true to allow it subject
     *   to normal restrictions
     */
    public function can_reply($inreplyto, $userid=0) {
        return true;
    }

    /**
     * Checks whether a discussion feature is allowed for this forum type.
     * Default just returns true. This could be used to veto selected features.
     * @param forum_discussion $discussion
     * @param discussion_feature $feature
     * @return bool True to allow
     */
    public function allow_discussion_feature($discussion, $feature) {
        return true;
    }

    // UI display
    /////////////

    /**
     * Opens table tag and displays header row ready for calling
     * display_discussion_list_item() a bunch of times.
     * @param forum $forum
     * @param int $groupid Group ID for display; may be NO_GROUPS or ALL_GROUPS
     * @param string $baseurl Base URL of current page
     * @param int $sort forum::SORT_xx constant for sort order
     * @return string HTML code for start of table
     */
    public function display_discussion_list_start($forum, $groupid, $baseurl,
        $sort, $sortreverse=false) {
        global $CFG;
        $th = "<th scope='col' class='header c";

        // Work out sort headers
        $baseurl = preg_replace('~&sort=[a-z]~', '', $baseurl);
        $baseurl = preg_replace('~&page=[0-9]+~', '', $baseurl);
        $sortdata = array();
        $reversechar = ($sortreverse) ? '' : 'r';
        foreach(array(forum::SORT_DATE, forum::SORT_SUBJECT, forum::SORT_AUTHOR,
            forum::SORT_POSTS, forum::SORT_UNREAD, forum::SORT_GROUP) as $possiblesort) {
            $data = new stdClass;
            if($sort == $possiblesort) {
                $data->before = '<a ' . 'id="sortlink_' . forum::get_sort_letter($possiblesort) . 
                    '" href="' . s($baseurl) . '&amp;sort=' .
                    forum::get_sort_letter($possiblesort) . $reversechar . 
                    '&amp;sortlink=' . forum::get_sort_letter($possiblesort) .
                    '" class="forumng-sortlink" '.
                    'title="'. forum::get_sort_title($possiblesort) . ' ' .
                    $this->get_sort_order_text($sort, !$sortreverse) . '">';
                $data->after = '</a>' . $this->get_sort_arrow($sort, $sortreverse);
            } else {
                $data->before = '<a ' . 'id="sortlink_' . forum::get_sort_letter($possiblesort) .
                    '" href="' . s($baseurl) . '&amp;sort=' .
                    forum::get_sort_letter($possiblesort) . '&amp;sortlink=' .
                    forum::get_sort_letter($possiblesort) . '" class="forumng-sortlink" '.
                    'title="'. forum::get_sort_title($possiblesort) . ' ' .
                    $this->get_sort_order_text($possiblesort) . '">';
                $data->after = '</a>';
            }

            $sortdata[$possiblesort] = $data;
        }

        // Check group header
        if ($groupid == forum::ALL_GROUPS) {
            $grouppart = $sortdata[forum::SORT_GROUP]->before .
                get_string('group') . $sortdata[forum::SORT_GROUP]->after .
                "</th>{$th}3'>";
            $nextnum = 4;
        } else {
            $grouppart = '';
            $nextnum = 3;
        }
        $afternum = $nextnum + 1;

        if(class_exists('ouflags') && ou_get_is_mobile()){
        	$unreadpart = ' ('.$sortdata[forum::SORT_UNREAD]->before .
                get_string('unread', 'forumng') .
                $sortdata[forum::SORT_UNREAD]->after.')'
                ."</th>{$th}$nextnum lastcol'>";
        }
        else if($forum->can_mark_read()) {
            $unreadpart = "</th>{$th}$nextnum forumng-unreadcount'>" .
                $sortdata[forum::SORT_UNREAD]->before .
                get_string('unread', 'forumng') .
                $sortdata[forum::SORT_UNREAD]->after .
                "</th>{$th}$afternum lastcol'>";

        } else {
            $unreadpart = "</th>{$th}$nextnum lastcol'>";
        }

        return "<table class='generaltable forumng-discussionlist'><tr>" .
            "{$th}0'>" .
            $sortdata[forum::SORT_SUBJECT]->before .
            get_string('discussion', 'forumng') .
            $sortdata[forum::SORT_SUBJECT]->after .
            "</th>{$th}1'>" .
            $sortdata[forum::SORT_AUTHOR]->before .
            get_string('startedby', 'forumng') .
            $sortdata[forum::SORT_AUTHOR]->after .
            "</th>{$th}2'>" .
            $grouppart .
            $sortdata[forum::SORT_POSTS]->before .
            get_string('posts', 'forumng') .
            $sortdata[forum::SORT_POSTS]->after .
            $unreadpart .
            $sortdata[forum::SORT_DATE]->before .
            get_string('lastpost', 'forumng') .
            $sortdata[forum::SORT_DATE]->after .
            '</th></tr>';
    }

    /**
     * Displays a short version (suitable for including in discussion list)
     * of this discussion including a link to view the discussion and to
     * mark it read (if enabled).
     * @param forum_discussion $discussion Discussion
     * @param int $groupid Group ID for display; may be NO_GROUPS or ALL_GROUPS
     * @param bool $last True if this is the last item in the list
     * @return string HTML code to print out for this discussion
     */
    public function display_discussion_list_item($discussion, $groupid, $last) {
        global $CFG;
        $showgroups = $groupid == forum::ALL_GROUPS;

        // Work out CSS classes to use for discussion
        $classes = '';
        $alts = array();
        $icons = array();
        if ($discussion->is_deleted()) {
            $classes .= ' forumng-deleted';
            $alts[] = get_string('alt_discussion_deleted', 'forumng');
            $icons[] = ''; // No icon, text will be output on its own
        }
        if (!$discussion->is_within_time_period()) {
            $classes .= ' forumng-timeout';
            $icon = 'timeout';
            $alts[] = get_string('alt_discussion_timeout', 'forumng');
            $icons[] = $CFG->modpixpath . '/forumng/timeout.png';
        }
        if ($discussion->is_sticky()) {
            $classes .= ' forumng-sticky';
            $alts[] = get_string('alt_discussion_sticky', 'forumng');
            $icons[] = $CFG->modpixpath . '/forumng/sticky.png';
        }
        if ($discussion->is_locked()) {
            $classes .= ' forumng-locked';
            $alts[] = get_string('alt_discussion_locked', 'forumng');
            $icons[] = $CFG->pixpath . '/i/unlock.gif';
        }

        // Classes for Moodle table styles
        static $rownum = 0;
        $classes .= ' r' . $rownum;
        $rownum = 1 - $rownum;
        if ($last) {
            $classes .= ' lastrow';
        }

        $courseid = $discussion->get_forum()->get_course_id();

        // Start row
        $result = "<tr class='forumng-discussion-short$classes'>";

        // Subject, with icons
        $result .= "<td class='forumng-subject cell c0'>";
        foreach($icons as $index=>$icon) {
            $alt = $alts[$index];
            if($icon) {
                $result .= "<img src='$icon' alt='$alt' title='$alt' /> ";
            } else {
                $result .= "<span class='accesshide'>$alt:</span> ";
            }
        }
        $result .=
            "<a href='discuss.php?" . $discussion->get_link_params(forum::PARAM_HTML) . "'>" .
            format_string($discussion->get_subject(), true, $courseid) .
            "</a></td>";

        // Author
        $poster = $discussion->get_poster();
        $result .= "<td class='forumng-startedby cell c1'>" .
            print_user_picture($poster, $courseid, null, 0, true) .
            $discussion->get_forum()->display_user_link($poster) . "</td>";

        $num = 2;

        // Group
        if ($showgroups) {
            $result .= '<td class="cell c' . $num . '">'
                . ($discussion->get_group_name()) . '</td>';
            $num++;
        }

        // Number of posts
        $result .= '<td class="cell c' . $num . '">'
            . ($discussion->get_num_posts());

        if(!class_exists('ouflags') || !ou_get_is_mobile()){
            $result .= '</td>';
        }
             
        $num++;

        // Number of unread posts
        if ($discussion->get_forum()->can_mark_read()) {
            $unreadposts = $discussion->get_num_unread_posts();
            if(!class_exists('ouflags') || !ou_get_is_mobile()){
                $result .= '<td class="cell forumng-unreadcount c3">';
            }
            else {
            	$result .= '&nbsp;(';
            }
            if ($unreadposts) {
                $result .=
                '<a href="discuss.php?' . $discussion->get_link_params(forum::PARAM_HTML) . 
                '#firstunread">' . $unreadposts . '</a>' .
                '<form method="post" action="markread.php"><div>&nbsp;&nbsp;&nbsp;'.
                $discussion->get_link_params(forum::PARAM_FORM) .
                '<input type="hidden" name="back" value="view" />' .
                '<input type="image" title="' .
                    get_string('markdiscussionread', 'forumng') .
                    '" src="' . $CFG->pixpath . '/t/clear.gif" ' .
                    'class="iconsmall" alt="' .
                    get_string('markdiscussionread', 'forumng') .
                '" /></div></form>';
            } else {
                $result .= $unreadposts;
            }
            
            if(class_exists('ouflags') && ou_get_is_mobile()){
                $result .= ')';
            }
            
            $result .= '</td>';
            $num = 4;
        }

        // Last post
        $last = $discussion->get_last_post_user();

        $result .= '<td class="cell c' . $num .' lastcol forumng-lastpost">' .
            forum_utils::display_date($discussion->get_time_modified()) . "<br/>" .
            "<a href='{$CFG->wwwroot}/user/view.php?id={$last->id}&amp;" .
            "course=$courseid'>" . fullname($last, has_capability(
                'moodle/site:viewfullnames',
                $discussion->get_forum()->get_context())) . "</a></td>";

        $result .= "</tr>";
        return $result;
    }

    /**
     * Closes table tag after calling display_discussion_list_start() and
     * display_discussion_list_end().
     * @param forum $forum
     * @param int $groupid Group ID for display; may be NO_GROUPS or ALL_GROUPS
     * @return string HTML code for end of table
     */
    public function display_discussion_list_divider($forum, $groupid) {
        $showgroups = $groupid == forum::ALL_GROUPS;
        $count = 4 + ($showgroups ? 1 : 0) + ($forum->can_mark_read() ? 1 : 0);
        return '<tr class="forumng-divider"><td colspan="' .
            $count . '"></td></tr>';
    }

    /**
     * Closes table tag after calling display_discussion_list_start() and
     * display_discussion_list_end().
     * @param forum $forum
     * @param int $groupid Group ID for display; may be NO_GROUPS or ALL_GROUPS
     * @return string HTML code for end of table
     */
    public function display_discussion_list_end($forum, $groupid) {
        return '</table>';
    }

    /**
     * Opens table tag and displays header row ready for calling
     * display_draft_list_item() a bunch of times.
     * @return string HTML code for start of table
     */
    public function display_draft_list_start() {
        $result = '<div class="forumng-drafts"><h3>' . 
            get_string('drafts', 'forumng') . '</h3>';

        $th = "<th scope='col' class='header c";
        $result .= "<table class='generaltable'><tr>" .
            "{$th}0'>" . get_string('draft', 'forumng') .
            "</th>{$th}1'>" . get_string('discussion', 'forumng') .
            "</th>{$th}2 lastcol'>" . get_string('date') . '</th></tr>';
        
        return $result;
    }

    private static function get_post_summary($subject, $message, $format) {
        $summary = '<strong>' . format_string($subject) . '</strong> ' .
            strip_tags(format_text($message, $format));
        $summary = str_replace('<strong></strong>', '', $summary);
        $summary = self::nice_shorten_text($summary);
        return $summary;
    }

    public function display_draft_list_item($forum, $draft, $last) {
        global $CFG;

        // Classes for Moodle table styles
        static $rownum = 0;
        $classes = ' r' . $rownum;
        $rownum = 1 - $rownum;
        if ($last) {
            $classes .= ' lastrow';
        }

        $summary = self::get_post_summary($draft->get_subject(), 
            $draft->get_message(), $draft->get_format());

        $result = '<tr class="' . $classes . '">';
        $link = '<a href="editpost.php?draft=' . $draft->get_id() . '"' .
            ($draft->is_reply() 
                ? ' class="forumng-draftreply-' . $draft->get_discussion_id() . '-' .
                    $draft->get_parent_post_id() . '"' 
                : '') . '>';
        $result .= '<td class="cell c0">'. $link . $summary . '</a> '.
            '<a href="deletedraft.php?draft=' . $draft->get_id() . 
            '" title="' . get_string('deletedraft', 'forumng') .
            '"><img src="' . $CFG->pixpath . '/t/delete.gif" alt="' .
            get_string('deletedraft', 'forumng') . '"/></a></td>';

        if ($draft->is_reply()) {
            $result .= '<td class="cell c1">' .
                format_string($draft->get_discussion_subject()) . ' ';
            $result .= '<span class="forumng-draft-inreplyto">' .
                get_string('draft_inreplyto', 'forumng', 
                    $forum->display_user_link($draft->get_reply_to_user()));
            $result .= '</span></td>';
        } else {
            $result .= '<td class="cell c1">' .
                get_string('draft_newdiscussion', 'forumng') . '</td>';
        }

        $result .= '<td class="cell c2 lastcol">' . 
            forum_utils::display_date($draft->get_saved()) . '</td>';

        $result .= '</tr>';
        return $result;
    }

    /**
     * Closes table tag after draft list.
     * @return string HTML code for end of table
     */
    public function display_draft_list_end() {
        return '</table></div>';
    }

    /**
     * Opens table tag and displays header row ready for calling.
     * display_draft_list_item() a bunch of times.
     * @return string HTML code for start of table
     */
    public function display_flagged_list_start() {
        global $CFG;

        $result = '<div class="forumng-flagged"><h3>' . 
            get_string('flaggedposts', 'forumng') . '</h3>';

        $th = "<th scope='col' class='header c";
        
        $result .= "<table class='generaltable'><tr>" .
            "{$th}0'>" . get_string('post', 'forumng') .
            "</th>{$th}1'>" . get_string('discussion', 'forumng') .
            "</th>{$th}2 lastcol'>" . get_string('date') . '</th></tr>';

        return $result;
    }

    /**
     * Displays a flagged item.
     * @param forum_post $post
     * @param bool $last
     * @return string HTML code for table row
     */
    public function display_flagged_list_item($post, $last) {
        global $CFG;

        // Classes for Moodle table styles
        static $rownum = 0;
        $classes = ' r' . $rownum;
        $rownum = 1 - $rownum;
        if ($last) {
            $classes .= ' lastrow';
        }

        $result = '<tr class="' . $classes . '">';

        // Post cell
        $result .= '<td class="cell c0">';

        // Get post URL
        $discussion = $post->get_discussion();
        $link = '<a href="discuss.php?' .
                $discussion->get_link_params(forum::PARAM_HTML) .
                '#p' . $post->get_id() . '">';

        // Get post summary
        $summary = self::get_post_summary($post->get_subject(), 
            $post->get_message(), $post->get_format());
        $result .= $link . $summary . '</a>';

        $result .= '<small> ' . get_string('postby', 'forumng', 
            $post->get_forum()->display_user_link($post->get_user())) .
            '</small>';

        // Show flag icon. (Note: I tried to use &nbsp; before this so the
        // icon never ends up on a line of its own, but it does not work.)
        $result .= ' <form class="forumng-flag" action="flagpost.php" method="post"><div>' . 
            '<input type="hidden" name="p" value="' . $post->get_id() . '" />'.
            '<input type="hidden" name="back" value="view" />'.
            '<input type="hidden" name="flag" value="0" />'.
            '<input type="image" title="' . get_string('clearflag', 'forumng') . 
            '" src="' . $CFG->modpixpath . '/forumng/flag.on.png" alt="' . 
            get_string('flagon', 'forumng') .
            '" /></div></form></td>';

        // Discussion cell
        $result .= '<td class="cell c1"><a href="discuss.php?' .
                $discussion->get_link_params(forum::PARAM_HTML) .
                $discussion->get_id() . '">' . 
                format_string($discussion->get_subject()) . '</a></td>';

        // Date cell
        $result .= '<td class="cell c2 lastcol">' .
            forum_utils::display_date($post->get_created()) . '</td></tr>';
        return $result;
    } 

    /**
     * Closes table tag after flagged post list.
     * @return string HTML code for end of table
     */
    public function display_flagged_list_end() {
        return '</table></div>';
    }
    
    /**
     * Display intro section for forum.
     * @param forum $forum Forum
     * @return string Intro HTML or '' if none
     */
    public function display_intro($forum) {
        $text = $forum->get_intro();
        if (trim($text) === '') {
            return '';
        }
        $options = (object)array('trusttext'=>true);
        return '<div class="forumng-intro">' . format_text($text, FORMAT_HTML,
            $options, $forum->get_course_id()) . '</div>';
    }

    /**
     * Display post button for forum.
     * @param forum $forum Forum
     * @param int $groupid Group
     * @return string Post button
     */
    public function display_post_button($forum, $groupid) {
        return '<div id= "forumng-buttons"><form action="editpost.php" method="get" class="forumng-post-button"><div>' .
                $forum->get_link_params(forum::PARAM_FORM) .
                ($groupid != forum::NO_GROUPS
                ? '<input type="hidden" name="group" value="' . (int)$groupid . '" />'
                : '') .
            '<input type="submit" value="' .
                get_string('addanewdiscussion', 'forumng') . '" /></div></form>' . $this->display_paste_button($groupid) . '</div>';
    }

    /**
     * Display paste button for forum.
     * @param forum $forum Forum
     * @param int $groupid Group
     * @return string Paste discussion button
     */
    public function display_paste_button($groupid) {
        global $SESSION;
        if (isset($SESSION->forumng_copyfrom)) {
            $cmid = required_param('id', PARAM_INT);
            return '<form action="feature/copy/paste.php" method="get" class="forumng-paste-buttons">' .
                '<div><input type="submit" name="paste" value="' .
                get_string('pastediscussion', 'forumng') . '" />' .
                '<input type="submit" name="cancel" value="' .
                get_string('cancel') . '" />' .
                '<input type="hidden" name="cmid" value="' . $cmid . '" />' . 
                '<input type="hidden" name="clone" value="' . $SESSION->forumng_copyfromclone . '" />' . 
                ($groupid != forum::NO_GROUPS
                ? '<input type="hidden" name="group" value="' . (int)$groupid . '" />'
                : '') . '</div></form>';
        } else {
            return '';
        }
    }

    /**
     * Display 'Switch to simple/standard view' link
     * @param string $simple is empty or unset if currently in standard view. Or
     * it it is equal to 'y' if in simple view
     * @return string HTML for the switch link.
     */
    public function display_switch_link() {
        $simple = get_user_preferences('forumng_simplemode','');
        if ($simple) {
            return '<div class="forumng-switchlinkblock">' . get_string('switchto_standard_text', 'forumng') .
                ' ' . '<a href="viewmode.php?simple=0">' . get_string('switchto_standard_link', 'forumng') .
                '</a></div>' ;
        } else {
            return '<div class="accesshide forumng-switchlinkblock">' . get_string('switchto_simple_text', 'forumng') .
                ' ' . '<a id="forumng-switchlinkid" class="forumng-switchlink" href="viewmode.php?simple=1">' .
                get_string('switchto_simple_link', 'forumng') . '</a></div>' ;
        }
    }

    /**
     * Display subscribe options.
     * @param forum $forum Forum
     * @param string $text Textual note
     * @param int $subscribed
     * @param bool $button True if subscribe/unsubscribe button should be shown
     * @param bool $viewlink True if 'view subscribers' link should be shown
     * @return string HTML code for this area
     */
    public function display_subscribe_options($forum, $text, $subscribed,
        $button, $viewlink) {
        $out = '<div class="forumng-subscribe-options">' .
            '<h3>' . get_string('subscription', 'forumng') . '</h3>' .
            '<p>' . $text . '</p>';
        $cm = $forum->get_course_module();
        if ($button) {
            $outsubmit = '';
            $currentgroupid = forum::get_activity_group($cm, true);
            if ($currentgroupid == forum::NO_GROUPS) {
                $currentgroupid = 0;
            }
            if ($subscribed == forum::FULLY_SUBSCRIBED || $subscribed == forum::FULLY_SUBSCRIBED_GROUPMODE) {
                $outsubmit .= '<input type="submit" name="submitunsubscribe" value="' . get_string('unsubscribeshort','forumng') . '" />';
            } else if ($subscribed == forum::PARTIALLY_SUBSCRIBED) {
                //print both subscribe button and unsubscribe button
                $outsubmit .= '<input type="submit" name="submitsubscribe" value="' .
                    get_string('subscribelong','forumng') . '" />' .
                    '<input type="submit" name="submitunsubscribe" value="' .
                    get_string('unsubscribelong','forumng') . '" />';
            } else if ($subscribed == forum::NOT_SUBSCRIBED) {
                //default unsubscribed, print subscribe button
                $outsubmit .= '<input type="submit" name="submitsubscribe" value="' . get_string('subscribeshort','forumng') . '" />';
            } else if ($subscribed == forum::THIS_GROUP_PARTIALLY_SUBSCRIBED) {
                $outsubmit .= '<input type="submit" name="submitsubscribe_thisgroup" value="' .
                    get_string('subscribegroup','forumng') . '" />' .
                    '<input type="submit" name="submitunsubscribe_thisgroup" value="' .
                    get_string('unsubscribegroup_partial','forumng') . '" />'.
                    '<input type="hidden" name="g" value="' . $currentgroupid . '" />';
            } else if ($subscribed == forum::THIS_GROUP_SUBSCRIBED) {
                $outsubmit .= '<input type="submit" name="submitunsubscribe_thisgroup" value="' .
                    get_string('unsubscribegroup','forumng') . '" />'.
                    '<input type="hidden" name="g" value="' . $currentgroupid . '" />';
            } else if ($subscribed == forum::THIS_GROUP_NOT_SUBSCRIBED) {
                $outsubmit .= '<input type="submit" name="submitsubscribe_thisgroup" value="' .
                    get_string('subscribegroup','forumng') . '" />'.
                    '<input type="hidden" name="g" value="' . $currentgroupid . '" />';
            } 

            $out .= '<form action="subscribe.php" method="post"><div>' .
                $forum->get_link_params(forum::PARAM_FORM) .
                '<input type="hidden" name="back" value="view" />' .
                $outsubmit . '</div></form>';
        }
        if ($viewlink) {
            $out .= ' <div class="forumng-subscribe-admin">' .
                '<a href="subscribers.php?' .
                $forum->get_link_params(forum::PARAM_HTML) . '">' .
                get_string('viewsubscribers', 'forumng') . '</a></div>';
        }
        $out .= '</div>';
        return $out;
    }
    /**
     * Display subscribe option for discussions.
     * @param discussion $discussion Forum
     * @param string $text Textual note
     * @param bool $subscribe True if user can subscribe, False if user can unsubscribe
     * @return string HTML code for this area
     */
    function display_discussion_subscribe_option($discussion, $subscribe) {
        global $USER;
        if ($subscribe) {
            $status = get_string('subscribestate_discussionunsubscribed', 'forumng');
            $submit = 'submitsubscribe';
            $button = get_string('subscribediscussion', 'forumng');
        } else {
            $status = get_string('subscribestate_discussionsubscribed', 'forumng', '<strong>' . $USER->email . '</strong>' );
            $submit = 'submitunsubscribe';
            $button = get_string('unsubscribediscussion', 'forumng');
        }
        return '<div class="forumng-subscribe-options" id="forumng-subscribe-options">' .
            '<h3>' . get_string('subscription', 'forumng') . '</h3>' .
            '<p>' . $status .
            '</p>' . '<form action="subscribe.php" method="post"><div>' .
            $discussion->get_link_params(forum::PARAM_FORM) .
            '<input type="hidden" name="back" value="discuss" />' .
            '<input type="submit" name="' . $submit . '" value="' .
            $button . '" /></div></form></div>';
    }

    /**
     * Display a post. This method is used for:
     * - The normal HTML display of a post
     * - HTML email of a post
     * - Text-only email of a post
     * These are all combined in one method since ordinarily they change at
     * the same time (i.e. if adding/hiding information it is usually added to
     * or hidden from all views).
     *
     * $options is an associative array from a forum_post::OPTION_xx constant.
     * All available options are always set - if they were not set by
     * the user, they will have been set to false before this call happens,
     * so there is no need to use empty() or isset().
     *
     * Options are as follows. These are available in email mode:
     *
     * OPTION_TIME_ZONE (int) - Moodle time zone
     * OPTION_VIEW_FULL_NAMES (bool) - If user is allowed to see full names
     * OPTION_EMAIL (bool) - True if this is an email (false = standard view)
     * OPTION_DIGEST (bool) - True if this is part of an email digest
     * OPTION_COMMAND_REPLY (bool) - True if 'Reply' link should be displayed
     *   (available in email too)
     *
     * These options only apply in non-email usage:
     *
     * OPTION_SUMMARY (bool) - True if the entire post should not be displayed,
     *   only a short summary
     * OPTION_NO_COMMANDS (bool) - True if this post is being printed on its own
     * OPTION_COMMAND_EDIT (bool) - Display 'Edit' command
     * OPTION_COMMAND_DELETE (bool) - Display 'Edit' command
     * OPTION_COMMAND_SPLIT (bool) - Display 'Split' command
     * OPTION_RATINGS_VIEW (bool) - True to display current ratings
     * OPTION_RATINGS_EDIT (bool) - True to display ratings edit combo
     * OPTION_LEAVE_DIV_OPEN (bool) - True to not close post div (means that
     *   child posts can be added within).
     * OPTION_EXPANDED (bool) - True to show full post, otherwise abbreviate
     * OPTION_DISCUSSION_SUBJECT (bool) - If true, and only IF post is a 
     *   discussion root, includes subject (HTML, shortened as it would be for
     *   header display) as a hidden field.
     *
     * @param forum_post $post Post object
     * @param bool $html True if using HTML, false to output in plain text
     * @param array $options Associative array of name=>option, as above
     * @return string HTML or text of post
     */
    public function display_post($post, $html, $options) {
        global $CFG, $USER, $THEME;
        $discussion = $post->get_discussion();

        $expanded = $options[forum_post::OPTION_EXPANDED];
        $export = $options[forum_post::OPTION_EXPORT];
        $email = $options[forum_post::OPTION_EMAIL];

        // When posts are deleted we hide a lot of info - except when the person
        // viewing it has the ability to view deleted posts.
        $deletedhide = $post->get_deleted()
            && !$options[forum_post::OPTION_VIEW_DELETED_INFO];
        // Hide deleted messages if they have no replies
        if ($deletedhide && !$email && !$post->has_children()) {
            // note: !email check is to deal with posts that are deleted
            // between when the mail list finds them, and when it sends out
            // mail. It would be confusing to send out a blank email so let's
            // not do that. Also, ->has_children() is not safe to call during
            // email processing because it doesn't load the whole discussion.
            return '';
        }

        // Save some bandwidth by not sending link full paths except in emails
        if ($options[forum_post::OPTION_FULL_ADDRESSES]) {
            $linkprefix = $CFG->wwwroot . '/mod/forumng/';
        } else {
            $linkprefix = '';
        }

        $postnumber = (($options[forum_post::OPTION_NO_COMMANDS] || $email) && 
            !$options[forum_post::OPTION_VISIBLE_POST_NUMBERS])
            ? '' : $post->get_number();

        $lf = "\n";

        // Initialise result
        $out = '';
        if ($html) {
            if ($export) {
                $out .= '<hr />';
            }
            // Basic intro
            $classes = $expanded ? ' forumng-full' : ' forumng-short';
            $classes .= $post->is_important() ? ' forumng-important' : '';
            $classes .= (!$email && !$options[forum_post::OPTION_UNREAD_NOT_HIGHLIGHTED] && 
                $post->is_unread()) ? ' forumng-unread' : ' forumng-read';
            $classes .= $post->get_deleted() ? ' forumng-deleted' : '';
            $classes .= ' forumng-p' .$postnumber;
            $out .= $lf . '<div class="forumng-post' . $classes . '"><a id="p' .
                $post->get_id() . '"></a>';
            if ($options[forum_post::OPTION_FIRST_UNREAD]) {
                $out .= '<a id="firstunread"></a>';
            }

            // Theme hooks
            if (!empty($THEME->forumng_post_hooks)) {
                for ($i=1; $i<=$THEME->forumng_post_hooks; $i++) {
                    $out .= '<div class="forumng-'. $i .'"></div>';
                }
            }
        }

        if ($html || $options[forum_post::OPTION_VISIBLE_POST_NUMBERS]) {
            // Accessible text giving post a number so we can make links unique
            // etc.
            if ($postnumber) {
                $data = new stdClass;
                $data->num = $postnumber;
                if ($post->get_parent()) {
                    if ($html) {
                        $data->parent = '<a class="forumng-parentlink" href="#p' .
                            $post->get_parent()->get_id() .
                            '">' . $post->get_parent()->get_number() . '</a>';
                    } else {
                        $data->parent = $post->get_parent()->get_number();
                    }
                    $data->info = '';
                    if ($post->is_unread()) {
                        $data->info = get_string('postinfo_unread', 'forumng');
                    }
                    if (!$expanded) {
                        $data->info .= ' ' . get_string('postinfo_short', 'forumng');
                    }
                    if ($post->get_deleted()) {
                        $data->info .= ' ' . get_string('postinfo_deleted', 'forumng');
                    }
                    $data->info = trim($data->info);
                    if ($data->info) {
                        $data->info = ' (' . $data->info . ')';
                    }
                    $info = get_string('postnumreply', 'forumng', $data);
                } else {
                    $info = get_string('postnum', 'forumng', $data);
                }
                if ($options[forum_post::OPTION_VISIBLE_POST_NUMBERS]) {
                    if (!$html) {
                        $out .= "## " . $info . "\n";
                    }
                }
            }
        }

        // Discussion subject (root only)
        if ($options[forum_post::OPTION_DISCUSSION_SUBJECT] &&
            $post->is_root_post()) {
            $out .= '<input type="hidden" name="discussion_subject" value="' .
                shorten_text(htmlspecialchars($post->get_subject())) .
                '" />';
        }

        // Pictures (HTML version only)
        if ($html && !$export && $options[forum_post::OPTION_USER_IMAGE]) {
            $out .= $lf . '<div class="forumng-pic">';

            // User picture
            $out .= $deletedhide ? '' : $post->display_user_picture();

            // Group pictures if any - only for expanded version
            if ($expanded) {
                $grouppics = $post->display_group_pictures();
                if ($grouppics) {
                    $out .= '<div class="forumng-grouppicss">' . $grouppics .
                      '</div>';
                }
            }

            $out .=  '</div>';
        }

        // Link used to expand post
        $expandlink = '';
        if (!$expanded && !$deletedhide) {
            $expandlink = '&nbsp;[<a class="forumng-expandlink" ' .
                'href="' . $linkprefix . 'discuss.php?' . 
                $discussion->get_link_params(forum::PARAM_HTML) .
                '&amp;expand=1#p' .
                $post->get_id() . '">' . get_string('expandall', 'forumng') .
                '</a>] <img src="' . $CFG->pixpath .
                '/spacer.gif" width="16" height="16" alt="" />';
        }

        // Byline
        $by = new stdClass;
        $by->name = $deletedhide ? '' : fullname($post->get_user(),
            $options[forum_post::OPTION_VIEW_FULL_NAMES]);
        $by->date = $deletedhide ? '' : userdate($post->get_created(), get_string('strftimedatetime', 'langconfig'),
            $options[forum_post::OPTION_TIME_ZONE]);

        if ($html) {
            $out .= $lf . '<div class="forumng-info"><h2 class="forumng-author">';
            $out .= $post->is_important() ? '<img src="'. $CFG->modpixpath . '/forumng/exclamation_mark.gif" alt="'
            . get_string('important', 'forumng') . '" ' .
            'title = "' . get_string('important', 'forumng') . '"/>' : '';
            if ($export) {
                $out .=  $by->name;
            } else {
                $out .= '<a href="' . $CFG->wwwroot . '/user/view.php?id=' .
                    $post->get_user()->id .
                    ($post->get_forum()->is_shared() ? '' : '&amp;course=' .
                    $post->get_forum()->get_course_id()) .
                    '">' . $by->name . '</a>';
            }
            if ($postnumber) {
                if ($options[forum_post::OPTION_VISIBLE_POST_NUMBERS]) {
                    $out .= '<span class="accesshide" style="position:static"> ' . $info . ' </span>';
                } else {
                    $out .= '<span class="accesshide"> ' . $info . ' </span>';
                }
            }
            $out .= $deletedhide ? '' : '</h2> <span class="forumng-separator">&#x2022;</span> ';
            $out .= '<span class="forumng-date">' . $by->date . '</span>';
            if ($edituser = $post->get_edit_user()) {
                $out .= ' <span class="forumng-separator">&#x2022;</span> ' .
                    '<span class="forumng-edit">';
                $edit = new stdClass;
                $edit->date = userdate($post->get_modified(),
                    get_string('strftimedatetime', 'langconfig'),
                    $options[forum_post::OPTION_TIME_ZONE]);
                $edit->name = fullname($edituser,
                    $options[forum_post::OPTION_VIEW_FULL_NAMES]);
                if ($edituser->id == $post->get_user()->id) {
                    $out .= get_string('editbyself', 'forumng', $edit->date);
                } else {
                    $out .= get_string('editbyother', 'forumng', $edit);
                }

                if ($options[forum_post::OPTION_COMMAND_HISTORY]) {
                    $out .= ' (<a href="history.php?' . $post->get_link_params(forum::PARAM_HTML) .
                        '">' . get_string('history', 'forumng') . '</a>)';
                }
                $out .= '</span>';
            }
            if ($options[forum_post::OPTION_SELECTABLE]) {
                $out .= ' &#x2022; <input type="checkbox" name="selectp' .
                    $post->get_id() . '" id="id_selectp' . $post->get_id() .
                    '" /><label class="accesshide" for="id_selectp' .
                    $post->get_id() . '">' .
                    get_string('selectlabel', 'forumng', $postnumber) . '</label>';
            }
            if ($options[forum_post::OPTION_FLAG_CONTROL]) {
                $out .= '<div class="forumng-flag">' . 
                    '<input type="image" title="' . get_string(
                        $post->is_flagged() ? 'clearflag' : 'setflag', 'forumng') . 
                    '" src="' . $CFG->modpixpath . '/forumng/flag.' . 
                        ($post->is_flagged() ? 'on' : 'off') . '.png" alt="' . 
                        get_string($post->is_flagged() ? 'flagon' : 'flagoff', 
                            'forumng') . 
                    '" name="action.flag.p_' . $post->get_id() . '.timeread_' .
                        $options[forum_post::OPTION_READ_TIME] . '.flag_' . 
                        ($post->is_flagged() ? 0 : 1) .
                    '"/></div>';
            }
            $out .= '</div>';
        } else {
            $out .= $by->name . ' - ' . $by->date . $lf;

            $out .= forum_cron::EMAIL_DIVIDER;
        }

        if ($post->get_deleted()) {
            $out .= '<p class="forumng-deleted-info"><strong>' .
                get_string('deletedpost', 'forumng') . '</strong> ';
            if ($deletedhide) {
                $out .= get_string($post->get_delete_user()->id == $post->get_user()->id
                    ? 'deletedbyauthor' : 'deletedbymoderator', 'forumng',
                    userdate($post->get_deleted()));
            } else {
                $a = new stdClass;
                $a->date = userdate($post->get_deleted());
                $a->user = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' .
                    $post->get_delete_user()->id . '&amp;course=' .
                    $post->get_forum()->get_course_id() . '">'  .
                    fullname($post->get_delete_user(),
                        $options[forum_post::OPTION_VIEW_FULL_NAMES]) . '</a>';
                $out .= get_string('deletedbyuser', 'forumng', $a);
            }
            $out .= '</p>';
        }

        // Get subject. This may make a db query when showing a single post
        // (which includes parent subject).
        if ($options[forum_post::OPTION_EMAIL]
            || $options[forum_post::OPTION_NO_COMMANDS]) {
            $subject = $post->get_effective_subject(true);
        } else {
            $subject = $post->get_subject();
        }

        // Otherwise, subject is only displayed if it has changed
        if ($subject !== null && $expanded && !$deletedhide) {
            if ($html) {
                $out .= $lf . '<h3 class="forumng-subject">';
                if ($options[forum_post::OPTION_DIGEST]) {
                    // Digest contains link to original post
                    $out .=
                        '<a href="' . $linkprefix .
                        'discuss.php?' . $discussion->get_link_params(forum::PARAM_HTML) .
                        '#p' . $post->get_id() . '">' .
                        format_string($subject) . '</a>';
                } else {
                    $out .= format_string($subject);
                }
                $out .= '</h3>';
            } else {
                $out .= format_string($subject, true);
                if ($options[forum_post::OPTION_DIGEST]) {
                    // Link to original post
                    $out .= " <{$linkprefix}discuss.php?" . $discussion->get_link_params(forum::PARAM_HTML) .
                        $discussion->get_id() . '#p' . $post->get_id() . '>';
                }
                $out .= $lf;
            }
        }
        
        // Get content of actual message in HTML
        if ($html) {
            $textoptions = new stdClass();
            // Don't put a <p> tag round post
            $textoptions->para = false;
            // Does not indicate that we trust the text, only that the
            // TRUSTTEXT marker is supported.
            $textoptions->trusttext = true;
            $message = format_text($post->get_message(), $post->get_format(),
                    $textoptions, $post->get_forum()->get_course_id());

            if (!$expanded && !$deletedhide) {
                // When not expanded and no subject, we include a summary of the
                // message
                $stripped = strip_tags(
                    preg_replace('~<script.*?</script>~s', '', $message));
                $messagetosummarise = $subject !== null
                    ? '<h3>' . $subject . '</h3>&nbsp;' . $stripped
                    : $stripped;
                $summary = self::nice_shorten_text($messagetosummarise, 50);
                $out .= $lf . '<div class="forumng-summary"><div class="forumng-text">' .
                     $summary . '</div> ' . $expandlink . '</div>';
            }
        }

        // Start of post main section
        if ($expanded && !$deletedhide) {
            if ($html) {
                $out .= '<div class="forumng-postmain">';
            }

            // Attachments
            $attachments = $post->get_attachment_names();
            if (count($attachments)) {
                if ($html) {
                    $out .= $lf . '<ul class="forumng-attachments">';
                }
                if (count($attachments) == 1) {
                    $attachmentlabel = get_string('attachment', 'forumng'); 
                } else {
                    $attachmentlabel = get_string('attachments', 'forumng');
                }
                $out .= '<span class="accesshide">'.$attachmentlabel.'</span>';
                foreach ($attachments as $attachment) {
                    if ($html) {
                        require_once($CFG->libdir . '/filelib.php');
                        $iconsrc = $CFG->pixpath . '/f/' .
                            mimeinfo('icon', $attachment);
                        $alt = get_mimetype_description(
                            mimeinfo('type', $attachment));

                        $out .= '<li><a href="' . $linkprefix .
                            'attachment.php?' . $post->get_link_params(forum::PARAM_HTML) .
                            '&amp;file=' . $attachment . '">' . '<img src="' .
                            $iconsrc . '" alt="' . $alt . '" /> <span>' .
                            htmlspecialchars($attachment) . '</span></a></li>';
                    } else {
                        // Right-align the entry to 70 characters
                        $padding = 70 - strlen($attachment);
                        if ($padding > 0) {
                            $out .= str_repeat(' ', $padding);
                        }

                        // Add filename
                        $out .= $attachment . $lf;
                    }
                }

                if ($html) {
                    $out .= '</ul>' . $lf;
                } else {
                    $out .= $lf; // Extra line break after attachments
                }
            }

            // Display actual content
            if ($html) {
                if ($options[forum_post::OPTION_PRINTABLE_VERSION]) {
                    $message = preg_replace('~<a[^>]*\shref\s*=\s*[\'"](http:.*?)[\'"][^>]*>' .
                    '(?!(http:|www\.)).*?</a>~', "$0 [$1]", $message);
                }
                $out .= $lf . '<div class="forumng-message">' . $message . '</div>';
            } else {
                $out .= format_text_email(trusttext_strip($post->get_message()),
                    $post->get_format());
                $out .= "\n\n";
            }

            if ($html) {
                $out .= $lf . '<div class="forumng-postfooter">';
            }

            // Ratings
            $ratings = '';
            $ratingclasses = '';
            if ($options[forum_post::OPTION_RATINGS_VIEW]) {
                $ratingclasses .= ' forumng-canview';
                if ($post->get_num_ratings() >=
                    $post->get_forum()->get_rating_threshold()) {
                    if ($html) {
                        $ratings .= '<div class="forumng-rating">';
                        $a = new stdClass;
                        $a->avg = '<strong id="rating_for_' . $post->get_id() . '">' .
                            $post->get_average_rating(true) . '</strong>';
                        $a->num = '<span class="forumng-count">' .
                            $post->get_num_ratings() . '</span>';
                        $ratings .= get_string('averagerating', 'forumng', $a);
                        $ratings .= '</div>';
                    } else {
                        $ratings .= strip_tags($post->get_average_rating(true));
                    }
                }
            }
            if ($options[forum_post::OPTION_RATINGS_EDIT] && $html) {
                $ratingclasses .= ' forumng-canedit';
                $ratings .= '<div class="forumng-editrating">' .
                    get_string('yourrating', 'forumng') . ' ';
                $ratings .= choose_from_menu(
                    $post->get_forum()->get_rating_options(),
                    'rating' . $post->get_id(),
                    $post->get_own_rating(),
                    '-', '', forum_post::NO_RATING, true);
                $ratings .= '</div>';
            }
            if ($ratings) {
                $out .= '<div class="forumng-ratings' . $ratingclasses .
                  '">' . $ratings . '</div>';
            }

            // Commands at bottom of mail

            if(class_exists('ouflags') && ou_get_is_mobile_from_cookies()){
            	$mobileclass = ' class="forumng-mobilepost-link"';
            }
            else {
            	$mobileclass = '';
            }
            
            if ($html) {
                $commands = '';
                $expires = $post->can_ignore_edit_time_limit() ? '' :
                    '&amp;expires=' . ($post->get_edit_time_limit()-time());

                // Jump box
                if ($options[forum_post::OPTION_JUMP_PREVIOUS] || 
                    $options[forum_post::OPTION_JUMP_NEXT] ||
                    $options[forum_post::OPTION_JUMP_PARENT]) {
                    $commands .= '<li class="forumng-jumpto">'. get_string('jumpto', 'forumng');
                    if ($nextid = $options[forum_post::OPTION_JUMP_NEXT]) {
                        $commands .= ' <a href="#p'. $nextid . '" class="forumng-next">' .
                            get_string('jumpnext', 'forumng') . '</a>';
                    }
                    if ($pid = $options[forum_post::OPTION_JUMP_PREVIOUS]) {
                        if ($nextid) {
                            $commands .= ' (<a href="#p'. $pid . '" class="forumng-prev">' .
                                get_string('jumppreviousboth', 'forumng') . '</a>)';
                        } else {
                            $commands .= ' <a href="#p'. $pid . '" class="forumng-prev">' .
                                get_string('jumpprevious', 'forumng') . '</a>';
                        }
                    }
                    if ($parentid = $options[forum_post::OPTION_JUMP_PARENT]) {
                        $commands .= ' <a href="#p'. $parentid . '" class="forumng-parent">' .
                            get_string('jumpparent', 'forumng') . '</a>';
                    }
                    $commands .= '</li>';
                }

                //Direct link
                if ($options[forum_post::OPTION_COMMAND_DIRECTLINK]) {
                    $commands .= '<li class="forumng-permalink"><a href="discuss.php?' . $discussion->get_link_params(forum::PARAM_HTML) . '#p' . $post->get_id() .
                        '" title="' . get_string('directlinktitle', 'forumng').'">' .
                        get_string('directlink', 'forumng', $postnumber) .
                        '</a></li>';
                }

                // Alert link
                if ($options[forum_post::OPTION_COMMAND_REPORT]) {
                    $commands .= '<li><a href="' . $linkprefix . 'alert.php?' .
                            $post->get_link_params(forum::PARAM_HTML) .
                            '" title="'.get_string('alert_linktitle', 'forumng').'">' .
                            get_string('alert_link', 'forumng', $postnumber) .
                            '</a></li>';
                }

                // Split link
                if ($options[forum_post::OPTION_COMMAND_SPLIT]) {
                    $commands .= '<li class="forumng-split"><a href="' . $linkprefix .
                            'splitpost.php?' .
                            $post->get_link_params(forum::PARAM_HTML) . '">' .
                            get_string('split', 'forumng', $postnumber) .
                            '</a></li>';
                }

                // Delete link
                if ($options[forum_post::OPTION_COMMAND_DELETE]) {
                    $commands .= '<li><a' . $mobileclass . ' href="' . $linkprefix .
                            'deletepost.php?' .
                            $post->get_link_params(forum::PARAM_HTML) .
                            $expires . '">' .
                            get_string('delete', 'forumng', $postnumber) .
                            '</a></li>';
                }

                // Undelete link
                if ($options[forum_post::OPTION_COMMAND_UNDELETE]) {
                    $commands .= '<li><a href="' . $linkprefix .
                            'deletepost.php?' .
                            $post->get_link_params(forum::PARAM_HTML) .
                            '&amp;delete=0">' .
                            get_string('undelete', 'forumng', $postnumber) .
                            '</a></li>';
                }

                // Edit link
                if ($options[forum_post::OPTION_COMMAND_EDIT]) {
                    $commands .= '<li><a' . $mobileclass . ' href="' . $linkprefix .
                            'editpost.php?' .
                            $post->get_link_params(forum::PARAM_HTML) .
                            $expires. '">' .
                            get_string('edit', 'forumng', $postnumber) .
                            '</a></li>';
                }

                // Reply link
                if ($options[forum_post::OPTION_COMMAND_REPLY]) {
                    $commands .= '<li class="forumng-replylink"><a' . $mobileclass . ' href="' . $linkprefix .
                            'editpost.php?replyto=' . $post->get_id() .
                            $post->get_forum()->get_clone_param(forum::PARAM_HTML) .
                            '">' . get_string('reply', 'forumng', $postnumber) .
                            '</a></li>';
                }

                if ($commands) {
                    $out .= $lf . '<ul class="forumng-commands">' .
                        $commands . '</ul>';
                }
            } else {
                // Reply link
                if ($options[forum_post::OPTION_COMMAND_REPLY]) {
                    $out .= forum_cron::EMAIL_DIVIDER;
                    if ($options[forum_post::OPTION_EMAIL]) {
                        $course = $post->get_forum()->get_course();
                        $out .= get_string("postmailinfo", "forumng",
                            $course->shortname) . $lf;
                    }
                    $out .= "{$linkprefix}editpost.php?replyto=" .
                            $post->get_id() .
                            $post->get_forum()->get_clone_param(forum::PARAM_PLAIN) .
                            $lf;
                }

                // Only the reply command is available in text mode
            }

            // End of post footer and main section
            if ($html) {
                $out .= '</div></div>';
            }
        }

        // End of post div
        if ($html) {
            $out .= '<div class="forumng-endpost"></div></div>';
            if ($export) {
                $out .= '<br /><br />';
            }
        }

        return $out;
    }

    private static function nice_shorten_text($text, $length=40) {
        $text = trim($text);
        $summary = shorten_text($text, $length);
        $summary = preg_replace('~\s*\.\.\.(<[^>]*>)*$~', '$1', $summary);
        $dots = $summary != $text ? '...' : '';
        return $summary. $dots;
    }

    /**
     * Called when displaying a group of posts together on one page.
     * @param forum_discussion $discussion Forum object
     * @param string $html HTML that has already been created for the group
     *   of posts
     * @return string Modified (if necessary) HTML
     */
    public function display_post_group($discussion, $html) {
        // Add rating form if there are any rating selects
        $hasratings = strpos($html, '<div class="forumng-editrating">') !== false;
        $hasflags = strpos($html, '<div class="forumng-flag">') !== false;
        if($hasflags || $hasratings) {
            $script = '<script type="text/javascript">' .
                'document.getElementById("forumng-actionform").autocomplete=false;' .
                '</script>';
            $html = '<form method="post" id="forumng-actionform" ' .
                'action="action.php"><div>' . $script . $html .
                $discussion->get_link_params(forum::PARAM_FORM);
            if ($hasratings) {
                $html .= '<input type="submit" id="forumng-saveallratings" value="' .
                    get_string('saveallratings', 'forumng') . '" name="action.rate"/>';
            }
            $html .=  '</div></form>';
        }
        return $html;
    }

    /**
     * Displays the reply/edit form on a discussion page. Usually this form is
     * hidden by CSS and only displayed when JavaScript activates it.
     * @param forum $forum
     * @return string HTMl for form
     */
    public function display_ajax_forms($forum) {
        global $CFG;
        if(!ajaxenabled() && !class_exists('ouflags')) {
            return '';
        }

        require_once($CFG->dirroot . '/mod/forumng/editpost_form.php');
        // Reply form
        $mform = new mod_forumng_editpost_form('editpost.php',
            array('params'=>array(), 'isdiscussion'=>false, 'ispost'=>true,
                'islock'=>false, 'forum'=>$forum, 'edit'=>false, 'post'=>null,
                'ajaxversion'=>1, 'isroot'=>false));
        $result = $mform->_form->toHtml();
        // Edit form
        $mform = new mod_forumng_editpost_form('editpost.php',
            array('params'=>array(), 'isdiscussion'=>false, 'ispost'=>true,
                'islock'=>false, 'forum'=>$forum, 'edit'=>true, 'post'=>null,
                'ajaxversion'=>2, 'isroot'=>false));
        $result .= $mform->_form->toHtml();
        // Edit form (discussion)
        $mform = new mod_forumng_editpost_form('editpost.php',
            array('params'=>array(), 'isdiscussion'=>false, 'ispost'=>true,
                'islock'=>false, 'forum'=>$forum, 'edit'=>true, 'post'=>null,
                'ajaxversion'=>3, 'isroot'=>true));
        $result .= $mform->_form->toHtml();

        if (can_use_html_editor()) {
            // Kill form textarea so that TinyMCE doesn't initialise it (it doesn't
            // work if we let it do that)
            $result = preg_replace(
                '~<textarea .*?id="(.*?)" name="(.*?)".*?</textarea>~s',
                '<input type="hidden" id="$1" name="$2" value="" />',
                $result);

            // Turn HTMLArea JavaScript into functions we can call to init it
            $result = preg_replace(
                '~<script type="text/javascript" defer="defer">[^e]*editor.*?\(\'(.*?)\'\);[^;]*;([^/]*?)editor_[a-f0-9]*\.generate[^<]*</script>~',
'<script type="text/javascript">
//<![CDATA[
document.getElementById(\'$1\').form.inithtmlarea = function() {
var form = document.getElementById(\'$1\').form;
form.htmlarea = new HTMLArea("$1");
var config = form.htmlarea.config;
$2
form.htmlarea.generate();
};
//]]>
</script>',
                $result);
        }
        return '<div id="forumng-formhome">' . $result . '</div>';
    }

    // Type plugin basics
    /////////////////////

    /**
     * Obtains the ID of this forum type. Default implementation cuts
     * '_forum_type' off the class name and returns that.
     * @return string ID
     */
    public function get_id() {
        return str_replace('_forum_type', '', get_class($this));
    }

    /**
     * Obtains the display name of this forum type. Default implementation
     * gets string type_(whatever) from forumng language file.
     * @return string Name
     */
    public function get_name() {
        return get_string('type_' . $this->get_id(), 'forumng');
    }

    /**
     * Creates a new object of the given named type.
     * @param $type Type name (may be null for default)
     * @return forum_type Type
     * @throws forum_exception If the name isn't valid
     */
    public static function get_new($type) {
        // Get type name
        if (!$type) {
            $type = 'general';
        }
        if (!preg_match('~^[a-z][a-z0-9_]*$~', $type)) {
            throw new forum_exception("Invalid forum type name: $type");
        }
        $classname = $type . '_forum_type';

        // Require library
        require_once(dirname(__FILE__) . "/$type/$classname.php");

        // Create and return type object
        return new $classname;
    }

    /**
     * Returns a new object of each available type.
     * @return array Array of forum_type objects
     */
    public static function get_all() {
        global $CFG;
        // Get directory listing (excluding simpletest, CVS, etc)
        $list = get_list_of_plugins('type', '', $CFG->dirroot . '/mod/forumng');
        $results = array();
        foreach ($list as $name) {
            $results[] = self::get_new($name);
        }
        return $results;
    }
    
    /**
     * Returns the full img tag for the sort arrow gif.
     * @return string
     */
    public function get_sort_arrow($sort, $sortreverse=false) {
        global $CFG;
        $letter = forum::get_sort_letter($sort);
        $up = 'sortorder-up.gif';
        $down = 'sortorder-down.gif';
        $imgtag = '<span class="forumng-sortcurrent"><img src="' . 
            $CFG->modpixpath . '/forumng/';
        switch ($letter) {
            case 'd' :
                $imgtag .= ($sortreverse) ? $up : $down;
                break;
            case 's' :
                $imgtag .= ($sortreverse) ? $down : $up;
                break;
            case 'a' :
                $imgtag .= ($sortreverse) ? $down : $up;
                break;
            case 'p' :
                $imgtag .= ($sortreverse) ? $up : $down;
                break;
            case 'u' :
                $imgtag .= ($sortreverse) ? $up : $down;
                break;
            case 'g' :
                $imgtag .= ($sortreverse) ? $down : $up;
                break;
            default:
                throw new forum_exception("Unknown sort letter: $letter");
        }

        $imgtag .= '" alt="' . get_string('sorted', 'forumng') . ' ' .
                    $this->get_sort_order_text($sort, $sortreverse) . '"/></span>';
        return $imgtag;
    }
    
    /**
     * Returns the apropriate language string text for the current sort.
     * e.g. a-Z or Z-a for text columns, recent first or oldest first for date columns and
     * highest first or lowest first for numeric columns. 
     * @return string
     */
    public function get_sort_order_text($sort, $sortreverse=false) {
        global $CFG;
        $letter = forum::get_sort_letter($sort);
        switch ($letter) {
            case 'd' :
            	return (!$sortreverse) ? get_string('date_desc', 'forumng') : get_string('date_asc', 'forumng');
            case 's' :
                return (!$sortreverse) ? get_string('text_asc', 'forumng') : get_string('text_desc', 'forumng');
            case 'a' :
                return (!$sortreverse) ? get_string('text_asc', 'forumng') : get_string('text_desc', 'forumng');
            case 'p' :
                return (!$sortreverse) ? get_string('numeric_desc', 'forumng') : get_string('numeric_asc', 'forumng');
            case 'u' :
                return (!$sortreverse) ? get_string('numeric_desc', 'forumng') : get_string('numeric_asc', 'forumng');
            case 'g' :
                return (!$sortreverse) ? get_string('text_asc', 'forumng') : get_string('text_desc', 'forumng');
            default:
                throw new forum_exception("Unknown sort letter: $letter");
        }
    }

    /**
     * Provided so that forum types can override certain language strings.
     * @param forum $forum Forum object
     * @param string $string Language string id (note: must be from forumng
     *   language file)
     * @param mixed $a Value or null
     * @return string Evaluated string
     */
    protected function get_string($forum, $string, $a=null) {
        return get_string($string, 'forumng', $a);
    }

    /**
     * @return bool True if the user is allowed to select this type, false
     *   if it's only used internally
     */
    public function is_user_selectable() {
        return true;
    }
}
?>
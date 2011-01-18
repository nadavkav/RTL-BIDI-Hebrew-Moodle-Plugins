<?php
/**
 * Standard forum type.
 */
class general_forum_type extends forum_type {
    /**
     * Displays the view page (usually showing a list of discussions).
     * @param forum $forum Forum
     * @param int $groupid Group ID
     */
    public function print_view_page($forum, $groupid) {
        global $SESSION;
        $forumid = $forum->get_id();
        $baseurl = 'view.php?' . $forum->get_link_params(forum::PARAM_PLAIN);

        if(isset($SESSION->forumng_discussionlist[$forumid]) &&
            property_exists($SESSION->forumng_discussionlist[$forumid], 'groupid') && 
            $SESSION->forumng_discussionlist[$forumid]->groupid != $groupid) {
            unset($SESSION->forumng_discussionlist[$forumid]->page);
            unset($SESSION->forumng_discussionlist[$forumid]->groupid);
        }

        //Remember the sort order and page number in session variables
        //Unset the page session variable when the sort links are clicked
        // or groupid has been changed (using the group dropdown box)
        $sortorder = optional_param('sort', '' , PARAM_ALPHA);
        if (!$sortorder) {
            if (isset($SESSION->forumng_discussionlist[$forumid]->sort)) {
                $sortorder = $SESSION->forumng_discussionlist[$forumid]->sort;
            } else {
                $sortorder = 'd';
            }
        } else {
            if (optional_param('sortlink', '' , PARAM_ALPHA)) {
                $SESSION->forumng_discussionlist[$forumid]->sort = $sortorder;
                unset ($SESSION->forumng_discussionlist[$forumid]->page);
            }
        }

        $page = optional_param('page', 0, PARAM_INT);
        if (!$page) {
            if (isset($SESSION->forumng_discussionlist[$forumid]->page)) {
                $page = $SESSION->forumng_discussionlist[$forumid]->page;
            } else {
                $page = 1;
            }
        } else {
            $SESSION->forumng_discussionlist[$forumid]->page = $page;
            $SESSION->forumng_discussionlist[$forumid]->groupid = $groupid;
        }

        $baseurl .= '&page='.$page;

        $sortchar = substr($sortorder, 0, 1);
        if (strlen($sortorder) == 2) {
            $sortreverse = (substr($sortorder, 1, 1) == 'r') ? true : false; 
        } else {
            $sortreverse = false;
        }
        
        $baseurl .= '&sort='.$sortchar;
        $baseurl .= ($sortreverse) ? 'r':'';
        
        $sort = forum::get_sort_code($sortchar);

        $list = $forum->get_discussion_list($groupid, $forum->can_view_hidden(),
            $forum->can_manage_discussions(), $page, $sort, $sortreverse);
        $sticky = $list->get_sticky_discussions();
        $normal = $list->get_normal_discussions();

        // Remove discussions from list if the forumtype thinks we can't see 
        // them
        foreach ($sticky as $key=>$value) {
            if (!$this->can_view_discussion($value)) {
                unset($sticky[$key]);
            }
        }
        foreach ($normal as $key=>$value) {
            if (!$this->can_view_discussion($value)) {
                unset($normal[$key]);
            }
        }

        // Intro
        print $forum->display_intro();

        // Draft posts
        $drafts = $forum->get_drafts();
        if(count($drafts) > 0) {
            print $forum->display_draft_list_start();
            foreach($drafts as $draft) {
                print $draft->display_draft_list_item($forum, 
                    $draft==end($drafts));
            }
            print $forum->display_draft_list_end();
        }

        //print info about the start and end dtates of the forum from the form setting;
        $stringend = 
            has_capability('mod/forumng:ignorepostlimits', $forum->get_context())
            ? 'capable' : '';
        $startdate = $forum->get_postingfrom();
        $enddate = $forum->get_postinguntil();

        // Before start date
        if (time() < $startdate) {
            $message = get_string('beforestartdate' . $stringend, 'forumng', forum_utils::display_date($startdate));
            print "<div class='forumng-show-dates'>$message</div>";
        } else if (time() < $enddate) {
            $message = get_string('beforeenddate' . $stringend, 'forumng', forum_utils::display_date($enddate));
            print "<div class='forumng-show-dates'>$message</div>";
        }

        // After end date
        if ($enddate && time() >= $enddate) {
            $message = get_string('afterenddate' . $stringend, 'forumng', forum_utils::display_date($enddate));
            print "<div class='forumng-show-dates'>$message</div>";
        }

        // Post button - temporarily disabled when in all-groups mode
        print ($groupid == NULL) ? '':$forum->display_post_button($groupid);

        print $list->display_paging_bar($baseurl);

        if (count($sticky) + count($normal) > 0) {
            print $forum->display_discussion_list_start(
                $groupid, $baseurl, $sort, $sortreverse);
            foreach ($sticky as $discussion) {
                print $discussion->display_discussion_list_item($groupid);
            }
            if (count($sticky) > 0 && count($normal) > 0) {
                print $forum->display_discussion_list_divider($groupid);
            }
            foreach ($normal as $discussion) {
                print $discussion->display_discussion_list_item($groupid,
                    $discussion == end($normal));
            }
            print $forum->display_discussion_list_end($groupid);
        } else {
            print '<p class="forumng-nodiscussions">' .
                $this->get_string($forum, 'nodiscussions') . '</p>';
        }

        print $list->display_paging_bar($baseurl);

        print $forum->display_discussion_list_features($groupid);

        // Flagged posts
        $flagged = $forum->get_flagged_posts();
        if (count($flagged) > 0) {
            print $forum->display_flagged_list_start();
            foreach($flagged as $post) {
                print $post->display_flagged_list_item(
                    $post===end($flagged));
            }
            print $forum->display_flagged_list_end();
        }

        // Subscribe and view subscribers links
        print $forum->display_subscribe_options();

        // Atom/RSS links
        print $forum->display_feed_links($groupid);

        // display the warning message for invalid archive setting
        print $forum->display_archive_warning();

        // Display sharing information
        print $forum->display_sharing_info();
    }

    /**
     * Displays the discussion page.
     * @param forum_discussion $discussion Discussion
     */
    public function print_discussion_page($discussion) {
        $previousread = (int)$discussion->get_time_read();

        // 'Read date' option (used when viewing all posts so that they keep
        // their read/unread colouring)
        $timeread = optional_param('timeread', 0, PARAM_INT);
        if ($timeread) {
            $discussion->pretend_time_read($timeread);
            $previousread = $timeread;
        }

        // 'Expand all' option (always chosen for non-JS browsers)
        $expandall = optional_param('expand', 0, PARAM_INT) 
            || forum_utils::is_bad_browser();
        // 'Expand all' option (always chosen for non-JS browsers)
        $collapseall = optional_param('collapse', 0, PARAM_INT);

        // Magic expand tracker (for use in JS only, never set server-side).
        // This tracks expanded posts, and makes the Back button 'work' in
        // the sense that it will expand these posts again.
        print '<form method="post" action="."><div>'.
            '<input type="hidden" id="expanded_posts" name="expanded_posts" ' .
            'value="" /></div></form>';
        
        // Get content for all posts in the discussion
        $options = array();
        if ($expandall) {
            $options[forum_post::OPTION_CHILDREN_EXPANDED] = true;
        }
        if ($collapseall) {
            $options[forum_post::OPTION_CHILDREN_COLLAPSED] = true;
        }
        $content = $this->display_discussion($discussion, $options);

        // Some post display options use the read time to construct links
        // (usually for non-JS version) so that unread state is maintained.
        $options[forum_post::OPTION_READ_TIME] = $previousread;

        // Display expand all option if there are any 'Expand' links in content
        $fakedate = '&amp;timeread=' . $previousread ;
        print '<div id="forumng-expandall">';
        $showexpandall = preg_match(
            '~<a [^>]*href="discuss\.php\?d=[0-9]+[^"]*&amp;expand=1#p[0-9]+">~',
            $content);
        $showcollapseall = preg_match(
            '~<div class="forumng-post forumng-full.*<div class="forumng-post forumng-full~s',
            $content);
        if ($showexpandall) {
            print '<a href="' .
                $discussion->get_url(forum::PARAM_HTML) . '&amp;expand=1' . $fakedate . '">' .
                get_string('expandall', 'forumng') . '</a>';
            if ($showcollapseall) {
                print ' &#x2022; ';
            }
        }
        if ($showcollapseall) {
            print '<a href="' . $discussion->get_url(forum::PARAM_HTML) . '&amp;collapse=1' . $fakedate .
                '">' . get_string('collapseall', 'forumng') . '</a> ';
        }
        print '</div>';

        // Display content
        print $content;

        // Print reply/edit forms for AJAX
        print $this->display_ajax_forms($discussion->get_forum());
        
        // Link back to forum
        print $discussion->display_link_back_to_forum();

        // Display discussion features (row of buttons)
        print $discussion->display_discussion_features();

        // Display the subscription options to this disucssion if available
        print $discussion->display_subscribe_options();

        // Atom/RSS links
        print $discussion->display_feed_links();

        // Set read data [shouldn't this logic be somewhere else as it is not
        // part of display?]
        if (forum::mark_read_automatically()) {
            $discussion->mark_read();
        }
    }
}
?>
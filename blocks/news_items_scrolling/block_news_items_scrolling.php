<?PHP

class block_news_items_scrolling extends block_base {
    function init() {
        $this->title = get_string('latestnewsscrolling','block_news_items_scrolling');
        $this->version = 2010090101;
    }

    function get_content() {
        global $CFG, $USER, $COURSE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }


        if ($COURSE->newsitems) {   // Create a nice listing of recent postings

            require_once($CFG->dirroot.'/mod/forum/lib.php');   // We'll need this


            if (!$forum = forum_get_course_forum($COURSE->id, 'news')) {
                return '';
            }

            $modinfo = get_fast_modinfo($COURSE);
            if (empty($modinfo->instances['forum'][$forum->id])) {
                return '';
            }
            $cm = $modinfo->instances['forum'][$forum->id];

            $context = get_context_instance(CONTEXT_MODULE, $cm->id);

        /// First work out whether we can post to this group and if so, include a link
            $groupmode    = groups_get_activity_groupmode($cm);
            $currentgroup = groups_get_activity_group($cm, true);

        /// Get all the recent discussions we're allowed to see

            if (! $discussions = forum_get_discussions($cm, 'p.modified DESC', false,
                                                       $currentgroup, $COURSE->newsitems) ) {
                $this->content->text = '('.get_string('nonews', 'forum').')';
                // add a link to add "a new news item" (nadavkav)
                if (forum_user_can_post_discussion($forum, $currentgroup, $groupmode, $cm, $context)) {
                    $this->content->footer  = '<div class="newlink"><a href="'.$CFG->wwwroot.'/mod/forum/post.php?forum='.$forum->id.'">'.
                            get_string('addanewitem', 'block_news_items').'</a>...</div>';
                }

                return $this->content;
            }
            // add scrolling effect <marquee> (nadavkav)
            $text = '<marquee width="100%" height="120" align="right" direction="up" scrolldelay="50" scrollamount="1" onmouseout="this.start();" style="padding-top: 2px;" onmouseover="this.stop();" dir="rtl">';

        /// Actually create the listing now

            $strftimerecent = get_string('strftimerecent');
            $strmore = get_string('more', 'block_news_items');

        /// Accessibility: markup as a list.
            $text .= "\n<ul class='unlist'>\n";
            foreach ($discussions as $discussion) {

                $discussion->subject = $discussion->name;

                $discussion->subject = format_string($discussion->subject, true, $forum->course);

                //if (! $post = forum_get_post_full($discussion->discussion)) {
                    //error("Could not find the first post in this forum");
                //}
                $post = get_record("forum_posts", "discussion", $discussion->discussion);

                $text .= '<li class="post">'.
                         '<div class="head">'.
                         //'<div class="date">'.userdate($discussion->modified, $strftimerecent).'</div>'.
                         //'<div class="name">'.fullname($discussion).'</div></div>'.
                         '<div class="info">'.format_text($post->message).' '.
                         '<a href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion->discussion.'">'.
                         $strmore.'...</a></div>'.
                         "</li>\n";
            }
            $text .= "</ul>\n";
            $text .= '</marquee>';
            $this->content->text = $text;


            $this->content->footer = '<a href="'.$CFG->wwwroot.'/mod/forum/view.php?f='.$forum->id.'">'.
                                      get_string('olditems', 'block_news_items').'</a> ...';

            if (forum_user_can_post_discussion($forum, $currentgroup, $groupmode, $cm, $context)) {
                $this->content->footer  = '<div class="newlink"><a href="'.$CFG->wwwroot.'/mod/forum/post.php?forum='.$forum->id.'">'.
                          get_string('addanewitem', 'block_news_items').'</a>...</div>';
            }

        /// If RSS is activated at site and forum level and this forum has rss defined, show link
            if (isset($CFG->enablerssfeeds) && isset($CFG->forum_enablerssfeeds) &&
                $CFG->enablerssfeeds && $CFG->forum_enablerssfeeds && $forum->rsstype && $forum->rssarticles) {
                require_once($CFG->dirroot.'/lib/rsslib.php');   // We'll need this
                if ($forum->rsstype == 1) {
                    $tooltiptext = get_string('rsssubscriberssdiscussions','forum',format_string($forum->name));
                } else {
                    $tooltiptext = get_string('rsssubscriberssposts','forum',format_string($forum->name));
                }
                if (empty($USER->id)) {
                    $userid = 0;
                } else {
                    $userid = $USER->id;
                }
                $this->content->footer .= '<br />'.rss_get_link($COURSE->id, $userid, 'forum', $forum->id, $tooltiptext);
            }

        }

        return $this->content;
    }
}

?>

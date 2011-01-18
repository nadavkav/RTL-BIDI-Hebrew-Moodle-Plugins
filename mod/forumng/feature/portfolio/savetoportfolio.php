<?php
// Script saves selected posts, or whole discussion, to MyStuff portfolio.
require_once('../post_selector.php');
require_once($CFG->dirroot . '/mod/portfolio/index.php');

class portfolio_post_selector extends post_selector {
    function get_button_name() {
        return get_string('savetoportfolio', 'forumng');
    }

    function apply($discussion, $all, $selected, $formdata) {
        global $COURSE, $USER, $CFG;

        // Get HTML
        $poststext = '';
        $postshtml = '';
        $discussion->build_selected_posts_email($selected, $poststext, $postshtml, false);

        // Remove all styles
        $postshtml = preg_replace('~(<[^>]*)\sclass\s*=\s*("[^"]*")|(\'[^\']*\')([^>*]>)~', '$1$4', $postshtml);
        $postshtml = preg_replace('~(<[^>]*)\sstyle\s*=\s*("[^"]*")|(\'[^\']*\')([^>*]>)~', '$1$4', $postshtml);
        $postshtml = preg_replace('~<hr[^>]*/>~', '', $postshtml);

        // Add link back to discussion
        $postshtml .= '<div><a href="' . $CFG->wwwroot . 
            '/mod/forumng/discuss.php?' . $discussion->get_link_params(forum::PARAM_HTML) . '">' .
            get_string('savedposts_original', 'forumng') . '</a></div>';

        // Get title
        if ($all) {
            $title = get_string('savedposts_all', 'forumng', $discussion->get_subject());
            $tags = get_string('savedposts_all_tag', 'forumng');
        } else if(count($selected) == 1) {
            $post = $discussion->get_root_post()->find_child(reset($selected));
            $a = (object)array(
                'subject' => $post->get_effective_subject(),
                'name' => $post->get_forum()->display_user_name($post->get_user()));
            $title = get_string('savedposts_one', 'forumng', $a);
            $tags = get_string('savedposts_one_tag', 'forumng');
        } else {
            $title = get_string('savedposts_selected', 'forumng', $discussion->get_subject());
            $tags = get_string('savedposts_selected_tag', 'forumng');
        }

        raise_memory_limit('512M');

        // Do portfolio save
        $username = portfolioGetUsername();
        $itemid = "forumngposts" . portfolioGetUUID();
        $dataid = portfolioFormPutContent($itemid, 
            array('ouportfolio:title' => $title,
                'ouportfolio:tags' => $tags), 
            $postshtml);

        // Redirect back to discussion
        $discussionurl = $CFG->wwwroot . '/mod/forumng/discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN);
        if ($dataid === FALSE) {
            print_error('error_portfoliosave', 'forumng', $discussionurl);
        } else {
            redirect($discussionurl, get_string('savedtoportfolio', 'forumng'));
        }
    }
}

post_selector::go(new portfolio_post_selector());
?>
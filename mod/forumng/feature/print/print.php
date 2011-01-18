<?php
// Scripts for generating the printable version of the discussion or selected posts. 
// This uses the post selector infrastructure to 
// handle the situation when posts are being selected.
require_once('../post_selector.php');

class print_post_selector extends post_selector {
    function get_button_name() {
        return get_string('print', 'forumng');
    }
    function get_page_name() {
        return get_string('print_pagename', 'forumng');
    }
    function apply($discussion, $all, $selected, $formdata) {
        global $COURSE, $USER, $CFG;
        $d = $discussion->get_id();
        $forum = $discussion->get_forum();
        print_header($this->get_page_name());
        $printablebacklink = $CFG->wwwroot . '/mod/forumng/discuss.php?' . $discussion->get_link_params(forum::PARAM_HTML) ;
        print '
<div class="forumng-printable-header">
<div class="forumng-printable-backlink">' . link_arrow_left($discussion->get_subject(), $printablebacklink) . '</div>
<div class="forumng-printable-date">' . get_string('printedat','forumng', userdate(time())) . '</div>
<div class="clearer"></div></div>' . "\n" . '<div class="forumng-showprintable">';
        if ($all) {
            print $forum->get_type()->display_discussion($discussion, array(
                forum_post::OPTION_NO_COMMANDS => true,
                forum_post::OPTION_CHILDREN_EXPANDED => true,
                forum_post::OPTION_PRINTABLE_VERSION => true));
        } else {
            $allhtml = '';
            $alltext = '';
            $discussion->build_selected_posts_email($selected, $alltext, $allhtml, true, true);
            print $allhtml;
        }

        print "</div>";
        $forum->print_js(0, true);
        print "\n</body>\n<html>";
    }
}

post_selector::go(new print_post_selector());
?>
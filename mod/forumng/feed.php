<?php
// This code tells OU authentication system to let the public access this page
// (subject to Moodle restrictions below and with the accompanying .sams file).
global $DISABLESAMS;
$DISABLESAMS=true;

require_once('../../config.php');
require_once('forum.php');

require_once($CFG->libdir . '/rsslib.php');
require_once('atomlib.php');

// Parameters identify desired forum and group
$d = optional_param('d', 0, PARAM_INT);
if (!$d) {
    $cmid = required_param('id', PARAM_INT);
    $groupid = optional_param('group', 'unspecified', PARAM_INT);
}
$cloneid = optional_param('clone', 0, PARAM_INT);

// User identification
$userid = required_param('user', PARAM_INT);
$key = required_param('key', PARAM_ALPHANUM);

// Feed format
$format = required_param('format', PARAM_ALPHA);
$rss = $format == 'rss';

try {
    // Load forum
    if ($d) {
        $discussion = forum_discussion::get_from_id($d, $cloneid);
        $forum = $discussion->get_forum();
        $groupid = $discussion->get_group_id();
        $url = $discussion->get_url(forum::PARAM_PLAIN);
    } else {
        $forum = forum::get_from_cmid($cmid, $cloneid);
        $url = $forum->get_url(forum::PARAM_PLAIN);
        if ($groupid == 'unspecified') {
            $groupid = $forum->get_group_mode() == SEPARATEGROUPS 
                ? forum::ALL_GROUPS : forum::NO_GROUPS;
        } else {
            $url .= '&group=' . $groupid;
        }
    }

    // Check it allows feeds
    $feedtype = $forum->get_effective_feed_option();
    switch ($feedtype) {
        case forum::FEEDTYPE_DISCUSSIONS:
            if (!$d) {
                break;
            }
            // Fall through
        case forum::FEEDTYPE_NONE:
            print_error('feed_notavailable', 'forumng');
    }

    // Check that the key is valid
    $correctkey = $forum->get_feed_key($groupid, $userid);
    if ($correctkey != $key) {
        print_error('feed_nopermission', 'forumng');
    }

    // Get most recent posts or discussions
    if ($feedtype == forum::FEEDTYPE_DISCUSSIONS) {
        $discussions = $forum->get_feed_discussions($groupid, $userid);
        $latest = count($discussions)
            ? reset($discussions)->get_time_modified() : time();
    } else {
        $posts = $d ? $discussion->get_feed_posts($userid)
            : $forum->get_feed_posts($groupid, $userid);
        $latest = count($posts)
            ? reset($posts)->get_created() : time();
    }

    $since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
        ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
    if ($since && strtotime($since) >= $latest) {
        header('HTTP/1.0 304 Not Modified');
        exit;
    }
    header('Last-Modified: ' .gmdate('D, d M Y H:i:s', $latest) . ' GMT');

    // Check they still have permission to the forum.
    // Note that making these checks is a bit expensive so we might have
    // a performance concern, will deal with that later if needed. This is done
    // after the last-modified check so we can skip it if possible.
    if ($d) {
        $discussion->require_view($userid);
    } else {
        $forum->require_view($groupid, $userid);
    }

    // Unless the feed is of discussion titles only, you can't view it except
    // if you can view the content of discussions
    if ($feedtype != forum::FEEDTYPE_DISCUSSIONS &&
        !$forum->can_view_discussions($userid)) {
        print_error('feed_nopermission', 'forumng');
    }

    // Place data into standard format for atomlib/rsslib
    if ($d) {
        $feedname = format_string($forum->get_name()) . ': ' .
            format_string($discussion->get_subject());
        $feedsummary = '';
    } else {
        $feedname = format_string($forum->get_name());
        $feedsummary = $forum->get_intro();
    }

    $feeddata = array();
    if (isset($discussions)) {
        foreach ($discussions as $discussion) {
            $data = new stdClass;

            $data->title = format_string($discussion->get_subject());
            $data->description = '';
            $data->author = $forum->display_user_name(
                $discussion->get_poster());
            $data->link = $discussion->get_url();
            $data->pubdate = $discussion->get_time_modified();

            $feeddata[] = $data;
        }
    } else {
        foreach ($posts as $post) {
            $data = new stdClass;

            // Title is post subject, if any...
            $data->title = format_string($post->get_subject());
            if ($data->title===null) {
                $data->title = '';
            }
            // ...plus discussion subject (but not for discussion feed)
            if (!$d) {
                $data->title =
                    format_string($post->get_discussion()->get_subject()) .
                    ': ' . $data->title;
            }

            // Remaining details straightforward
            $data->description = format_text($post->get_message(), $post->get_format());
            $data->author = $forum->display_user_name($post->get_user());
            $data->link = $post->get_url();
            $data->pubdate = $post->get_modified();

            $feeddata[] = $data;
        }
    }

    // Now output all posts
    if ($rss) {
        header('Content-type: application/rss+xml');
        echo rss_standard_header($feedname, $url, $feedsummary);
        echo rss_add_items($feeddata);
        echo rss_standard_footer();
    } else {
        header('Content-type: application/atom+xml');
        $updated = count($feeddata)==0 ? time() : reset($feeddata)->pubdate;
        echo atom_standard_header($FULLME ,$FULLME, $updated, $feedname, $feedsummary);
        echo atom_add_items($feeddata);
        echo atom_standard_footer();
    }
} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
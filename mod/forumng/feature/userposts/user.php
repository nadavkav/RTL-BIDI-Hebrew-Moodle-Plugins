<?php
/**
 * This page (user.php) displays the all the posts for a chosen user
 * in chronological order (modified date). It is also indicates whether 
 * a post is a new discussion or a reply. If it is a reply then a link 
 * link is printed for displaying the original post.
 * @copyright &copy; July 2010 The Open University
 * @author Mahmoud Kassaei m.kassaei@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package forumNG
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/forum.php');

$cmid = required_param('id', PARAM_INT);
$userid = required_param('user', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

try {
    $forum = forum::get_from_cmid($cmid, $cloneid);
    $cm = $forum->get_course_module();
    $course = $forum->get_course();
    $forumid = $forum->get_id();
    $context = $forum->get_context();
    if ($forum->is_shared() || $forum->is_clone()) {
        throw new forum_exception("<strong>View post by user</strong> doesn't work for a shared forum.");
    }
    // Require view using currently selected forum group
    $forumgroupid = forum::get_activity_group($cm, false);
    $forum->require_view($forumgroupid);

    //TODO: Use/define a more accurate capability. This will do for now.
    require_capability('mod/forumng:viewallposts', $forum->get_context());

    // Check the current user has access to view the selected users
    if ($course->groupmode == SEPARATEGROUPS &&
            !has_capability('moodle/site:accessallgroups', $context)) {
        // Must share a group with the user within the selected grouping
        if ($course->defaultgroupingid) {
            $groupingjoin = "INNER JOIN {$CFG->prefix}groupings_groups gg " .
                    "ON gg.groupid = g.id";
            $groupingwhere = "AND gg.groupingid = $course->defaultgroupingid";
        }
        $ok = record_exists_sql("
SELECT
    g.id
FROM
    {$CFG->prefix}groups g
    INNER JOIN {$CFG->prefix}groups_members gm1 ON gm1.groupid = g.id
    INNER JOIN {$CFG->prefix}groups_members gm2 ON gm2.groupid = g.id
    $groupingjoin
WHERE
    g.courseid = {$course->id}
    AND gm1.userid = {$USER->id}
    AND gm2.userid = {$userid}
    $groupingwhere");
        if (!$ok) {
            // We know they don't have this, but call require to get the
            // appropriate error message
            require_capability('moodle/site:accessallgroups', $context);
        }
    }

    $where = " fd.forumid = $forumid AND fp.userid = $userid AND fp.oldversion = 0 AND fp.deleted = 0";
    $order = 'fp.modified';
    $posts = forum_post::query_posts($where, $order, true, false, false, $userid, true, true);

    // Set pagename
    if ($posts) {
        $post = reset($posts);
        $pagename = $post->u_firstname . ' ' . $post->u_lastname;
        $pagename .= $CFG->forumng_showusername ? ' (' . $post->u_username . ')' : '';
    } else {
        if (!$user = get_record('user', 'id', $userid)) {
            throw new forum_exception("Cannot find user (id=$userid) in the user table");
        }
        $pagename = $user->firstname . ' ' . $user->lastname . ' (' . $user->username . ')';
    }

    // Print page header
    $prevpage = get_string('userposts', 'forumng');
    $navigation = array();
    $navigation[] = array(
        'name'=>$prevpage,
        'link'=>$CFG->wwwroot . '/mod/forumng/feature/userposts/list.php?id='.$cmid);
    $forum->print_subpage_header($pagename, $navigation);

    foreach ($posts as $postid=>$post) {
        $fp = forum_post::get_from_id($postid, $cloneid, false, false, $userid);
        print "<div class='forumng-userpostheading'>";
        // If this post is a reply, then print a link to the discussion
        if (isset($post->parentpostid)) {
            $url = $CFG->wwwroot . '/mod/forumng/discuss.php?d='.$post->discussionid; 
            $title = $post->fd_subject;
            print get_string('re', 'forumng', "<a href='$url'>$title</a>");
        } else {
            print get_string('newdiscussion', 'forumng');
        }
        print "</div>";

        // Display this post
        $options = array(
           forum_post::OPTION_NO_COMMANDS => true,
           forum_post::OPTION_FIRST_UNREAD => false,
           forum_post::OPTION_UNREAD_NOT_HIGHLIGHTED => true);
        print $fp->display(true, $options);
    }
    // Display link to the discussion 
    print link_arrow_left($prevpage, 'list.php?id=' . $cmid);

    // Display footer
    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}

?>

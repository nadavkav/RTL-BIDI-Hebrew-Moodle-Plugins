<?php
/**
 * This page (list.php) lists the users with in alphabetical order of their
 * last-name with number of discussions and replies. If there are any 
 * discussions and/or replies a link is printed for displaying all the posts
 * from that a given user depending on chosen group
 * @copyright &copy; July 2010 The Open University
 * @author Mahmoud Kassaei m.kassaei@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package forumNG
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/forum.php');

$cmid = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

try {
    $forum = forum::get_from_cmid($cmid, $cloneid);
    $course = $forum->get_course();
    $cm = $forum->get_course_module();
    $context = $forum->get_context();
    if ($forum->is_shared() || $forum->is_clone()) {
        throw new forum_exception("<strong>View post by user</strong> doesn't work for a shared forum.");
    }
    // Check forum access (using forum group, if required)
    $forumgroupid = forum::get_activity_group($cm, false);
    $forum->require_view($forumgroupid);

    // TODO: Use/define a more accurate capability. This will do for now.
    require_capability('mod/forumng:viewallposts', $forum->get_context());

    // Print page header
    $forum->print_subpage_header(get_string('userposts', 'forumng'));

    // This section uses custom groups rather than the normal forum groups.
    // We are using the groups that are set for the course and not the ones
    // for the forum. This is because we want it so that even if the forum
    // does not have groups, or it has weird groups, the control for who can
    // see which posts is based on the 'real' tutor group which should be
    // defined at course level. 
    $groups = null;
    if ($course->groupmode) {
        // Get groups. Because the logic for this is slightly weird (we're
        // using course groups regardless of activity group setting; etc) it
        // is not possible to use the standard functions.
        $aag = has_capability('moodle/site:accessallgroups', $context);
        $groups = groups_get_all_groups($course->id,
                ($course->groupmode == VISIBLEGROUPS || $aag) ? 0 : $USER->id,
                $course->defaultgroupingid);
        if (!$groups) {
            $groups = array();
        }
        foreach ($groups as $id=>$values) {
            $groups[$id] = $values->name;
        }
        // Default to selected forum group (will be NO_GROUPS if none)
        $groupid = optional_param('group', $forumgroupid, PARAM_INT);
        if($groupid > 0 && !array_key_exists($groupid, $groups)) {
            // They selected a group which isn't in the course grouping or
            // which they don't have access to; clear it
            $groupid = forum::NO_GROUPS;
        }
        if ($groupid == forum::NO_GROUPS && count($groups)>0) {
            // They didn't select a valid group yet; pick their first one
            reset($groups);
            $groupid = key($groups);
        }
        if ($groupid <= 0) {
            // They don't have a group, so pick 'all groups'.
            $groupid = forum::ALL_GROUPS;
            if($course->groupmode != VISIBLEGROUPS) {
                // In separate groups mode, you need access all groups to
                // view the 'all groups' page
                require_capability('moodle/site:accessallgroups',$context);
            }
        }
    } else {
        // No groups in use at all
        $groupid = forum::NO_GROUPS;
    }

    // If $groups is not null, print a group selector dropdown
    if ($groups) {
        if ($aag || $course->groupmode == VISIBLEGROUPS) {
            // Put the 'all groups' option in at the top of the list
            $oldgroups = $groups;
            $groups = array(forum::ALL_GROUPS => get_string('allparticipants'));
            $groups = $groups + $oldgroups;
        }

        // Get label for selector
        if ($course->groupmode == VISIBLEGROUPS) {
            $grouplabel = get_string('groupsvisible');
        } else {
            $grouplabel = get_string('groupsseparate');
        }

        // Draw group selector
        $url = $CFG->wwwroot . "/mod/forumng/feature/userposts/list.php?" .
                $forum->get_link_params(forum::PARAM_HTML);

        if(count($groups)== 1) {
            print ('<div class="groupselector">'. $grouplabel .': '. end($groups).'</div>');
        } else {
            // do only if we have more than one group
            $popupform = popup_form($url . '&amp;group=', $groups,
                'forumng-groupselector',  $groupid, '', '', '', false,
                'self', $grouplabel);
       }
    }
    print '<div class="clearer"></div>';

    // Get all users
    if(!$users = $forum->get_monitored_users($groupid)) {
        print_string('nothingtodisplay', 'forumng');

        // Display link to the discussion 
        print link_arrow_left($forum->get_name(), '../../view.php?id=' . $cmid);

        // Display footer
        print_footer($course);
        return;
    }
    $data = array();
    foreach ($users as $id => $u){
        // Set table-row colour to gray for users without data (default)
        $span = "<span style='color:gray'>";

        // Get all discussions for this user
        $userdiscussions = $forum->get_all_user_post_counts($groupid, $id);
        $numberofdiscussions = $userdiscussions ? $userdiscussions[$id] : 0;

        // Get all replies for this user
        $userreplies = $forum->get_all_user_post_counts($groupid, $id, 'reply');
        $numberofreplies = $userreplies ? $userreplies[$id] : 0;

        $row = array();
        $username = $u->firstname . ' ' . $u->lastname;
        $username .= $CFG->forumng_showusername ? ' (' . $u->username . ')' : '';
        $showallpostsby = null;
        if ($numberofdiscussions || $numberofreplies) {
            // Set table-row colour to black for students with data
            $span = "<span style='color:black'>";
            $showallpostsby = get_string('showallpostsby', 'forumng', $username);

            // Build url and the params
            $url = $CFG->wwwroot . 
                    "/mod/forumng/feature/userposts/user.php?" .
                    $forum->get_link_params(forum::PARAM_HTML) .
                    '&amp;user=' . $id;
            $showallpostsby = "<a href='$url'>$showallpostsby</a>";
        }
        $row[0] = $span . $username . "</span>";
        $row[1] = $span . $numberofdiscussions . "</span>";
        $row[2] = $span . $numberofreplies . "</span>";
        $row[3] = $span . $showallpostsby . "</span>";
        
        $data[] = $row;
    }
    // Setup the table layout
    $user = get_string('user', 'forumng');
    $discussions = get_string('discussions', 'forumng');
    $replies = get_string('replies', 'forumng');
    $action = get_string('action', 'forumng');
    $table = new object();
    $table->head  = array($user, $discussions, $replies, "<span class='accesshide'>$action</span>");
    $table->size  = array('30%', '10%', '10%', '40%');
    $table->align = array('left', 'right', 'right', 'left');
    $table->width = '90%';
    $table->data  = $data;
    
    // Display the table
    print "<div class='forumng-userpoststable'>";
    print_table($table);
    print '</div>';
    

    // Display link to the discussion 
    $url = '../../view.php?id=' . $cmid;
    print link_arrow_left($forum->get_name(), $url);

    // Display footer
    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>

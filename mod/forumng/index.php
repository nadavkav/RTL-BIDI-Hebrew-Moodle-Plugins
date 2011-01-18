<?php
require_once('../../config.php');
require_once('forum.php');
if (class_exists('ouflags')) {
    $DASHBOARD_COUNTER = DASHBOARD_FORUMNG_VIEW;
}

// Require ID parameter for course
$id = required_param('id', PARAM_INT);
$course = get_record('course', 'id', $id);
if (!$course) {
    print_error('invalidcourse');
}
// Support for OU shared activities system, if installed 
$grabindex=$CFG->dirroot.'/course/format/sharedactv/grabindex.php';
if(file_exists($grabindex)) {
    require_once($grabindex);
}
require_login($course);

// No additional parameters required for course view (hmm)

// Get some strings
$strforums = get_string('forums', 'forumng');
$strforum = get_string('forum', 'forumng');
$strdescription = get_string('description');
$strsubscribed = get_string('subscribed', 'forumng');
$strdiscussionsunread = get_string('discussionsunread', 'forumng');
$strsubscribe = get_string('subscribeshort', 'forumng');
$strunsubscribe = get_string('unsubscribeshort', 'forumng');
$stryes = get_string('yes');
$strno = get_string('no');
$strpartial = get_string('partialsubscribed', 'forumng');
$strfeeds = get_string('feeds', 'forumng');
$strweek = get_string('week');
$strsection = get_string('section');

$coursecontext = get_context_instance(CONTEXT_COURSE, $id);
$canmaybesubscribe = (!isguestuser()
    && has_capability('moodle/course:view', $coursecontext));

try {
    // TODO Add search form to button
    $buttontext = '';

    // Display header
    $PAGEWILLCALLSKIPMAINDESTINATION = true;
    $strforums = get_string('forums', 'forumng');
    $navlinks = array();
    $navlinks[] = array('name' => $strforums, 'link' => '', 'type' => 'activity');
    print_header_simple($strforums, '',
        build_navigation($navlinks), '', '', true, $buttontext, navmenu($course));

    print '<div class="forumng-main">';
    print skip_main_destination();

    // Decide what kind of course format it is
    $useweeks = $course->format == 'weeks' || $course->format == 'weekscss';
    $usesections = $course->format == 'topics';

    // Set up table to include all forums
    $table = new StdClass;
    $table->head  = array ($strforum, $strdescription, $strdiscussionsunread);
    $table->align = array ('left', 'left', 'center');
    if ($useweeks || $usesections) {
        array_unshift($table->head, $useweeks ? $strweek : $strsection);
        array_unshift($table->align, 'left');
    }
    if ($canmaybesubscribe) {
        $table->head[] = $strsubscribed;
        $table->align[] = 'center';
    }

    if ($showrss = (($canmaybesubscribe || $course->id == SITEID) &&
        !empty($CFG->enablerssfeeds) && !empty($CFG->forumng_enablerssfeeds))) {
        $table->head[] = $strfeeds;
        $table->align[] = 'center';
    }

    // Construct forums array
    $forums = forum::get_course_forums($course, 0, forum::UNREAD_DISCUSSIONS,
        array(), true);

    // Display all forums
    $currentsection = 0;
    $cansubscribesomething = false;
    $canunsubscribesomething = false;
    foreach($forums as $forum) {
        $cm = $forum->get_course_module();

        // Skip forum if it's not visible or you can't read discussions there
        if(!$cm->uservisible ||
            !has_capability('mod/forumng:viewdiscussion', $forum->get_context())) {
            continue;
        }

        // Additional OU access restrictions
        if(class_exists('ouflags')) {
            list($accessible, $visible, $message) = is_module_student_accessible($cm, $course);
            if (!$accessible) {
                continue;
            }
        }

        $row = array();

//        $options = new StdClass;
//        $options->para=false;

        // Get section number
        if ($cm->sectionnum != $currentsection) {
            $printsection = $cm->sectionnum;
            // Between each section add a horizontal gap (copied this code,
            // can't say I like it)
            if ($currentsection) {
                $learningtable->data[] = 'hr';
            }
            $currentsection = $cm->sectionnum;
        } else {
            $printsection = '';
        }
        if ($useweeks || $usesections) {
            $row[] = $printsection;
        }

        if ($cm->visible) {
            $style = '';
        } else {
            $style = 'class="dimmed"';
        }

        // Get name and intro
        $row[] =   "<a href='view.php?id={$cm->id}' $style>" .
            format_string($forum->get_name()) . '</a>';
        $row[] = format_text($forum->get_intro(true), FORMAT_HTML);

        // Get discussion count
        $discussions = $forum->get_num_discussions();
        $unread = $forum->get_num_unread_discussions();
        $row[] = "$discussions ($unread)";

        $subscription_info = $forum->get_subscription_info();
        $subscribed = $subscription_info->wholeforum || count($subscription_info->discussionids) > 0 || 
            count($subscription_info->groupids) > 0;
        if ($subscription_info->wholeforum) {
            //subscribed to the entire forum
            $strtemp = $stryes;
        } else if (count($subscription_info->discussionids) == 0 && count($subscription_info->groupids) == 0) {
            $strtemp = $strno;
        } else {
            //treat partial subscribe the same as subscribe on the index page but display 'Partial' instead of 'Yes'
            $strtemp = $strpartial;
        }

        // If you have option to subscribe, show subscribed and possibly
        // subscribe/unsubscribe button
        if ($canmaybesubscribe) {
            $subscribetext = "<div class='forumng-subscribecell'>";
            $subscribetext .= $strtemp;
            $option = $forum->get_effective_subscription_option();
            if ($forum->can_change_subscription()) {
                if($subscribed) {
                    //Here print unsubscribe button for full subscribed or partial subscribed forum
                    $canunsubscribesomething = true;
                    $submitbutton = "<input type='submit' name='submitunsubscribe' value='$strunsubscribe' />";
                } else {
                    $cansubscribesomething = true;
                    $submitbutton = "<input type='submit' name='submitsubscribe' value='$strsubscribe' />";
                }
                $subscribetext .= "&nbsp;" .
"<form method='post' action='subscribe.php'><div>" .
$forum->get_link_params(forum::PARAM_FORM) .
"<input type='hidden' name='back' value='index' />" . $submitbutton . "</div></form>";
            }
            $subscribetext .= '</div>';
            $row[] = $subscribetext;
        }

        // If this forum has RSS/Atom feeds, show link
        if ($showrss) {
            if ($type = $forum->get_effective_feed_option()) {
                // Get group (may end up being none)
                $groupid = forum::get_activity_group(
                    $forum->get_course_module(), false);

                $row[] = $forum->display_feed_links($groupid);
            } else {
                $row[] = '&nbsp;';
            }
        }

        $table->data[] = $row;
    }

    print_table($table);

    // 'Subscribe all' links
    if ($canmaybesubscribe) {
        print '<div class="forumng-allsubscribe">';

        $subscribedisabled = $cansubscribesomething ? '' : 'disabled="disabled"';
        $unsubscribedisabled = $canunsubscribesomething ? '' : 'disabled="disabled"';

        print "<form method='post' action='subscribe.php'><div>" .
"<input type='hidden' name='course' value='{$course->id}' />" .
"<input type='hidden' name='back' value='index' />" .
"<input type='submit' name='submitsubscribe' value='" .
    get_string('allsubscribe', 'forumng') . "' $subscribedisabled/>" .
"<input type='submit' name='submitunsubscribe' value='" .
    get_string('allunsubscribe', 'forumng') . "' $unsubscribedisabled/>" .
"</div></form> ";

        print '</div>';
    }

    print '</div>';

    // Display footer
    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
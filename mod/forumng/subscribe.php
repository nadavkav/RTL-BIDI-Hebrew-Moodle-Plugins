<?php
require_once('../../config.php');
require_once('forum.php');

// This script handles requests to subscribe/unsubscribe from a forum or a discussion.
// It operates in two modes: 'go back' mode, where after subscribing it
// redirects, and 'full' mode (normally used only for links in email) where
// it displays information about the action.

// Specify either course (id) or (course-module) id or discussion (d). If you specify a course
// then it subscribes/unsubscribes to everything you have access to on that
// course.
$courseid = optional_param('course', 0, PARAM_INT);
$cmid = optional_param('id', 0, PARAM_INT);
$discussionid = optional_param('d', 0, PARAM_INT);
$requestingsubscribe = optional_param('submitsubscribe', '', PARAM_ALPHA);
$requestingunsubscribe = optional_param('submitunsubscribe', '', PARAM_ALPHA);
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Get request always does unsubscribe
    $requestingunsubscribe = 'y';
    $requestingsubscribe = '';
}

//Only one of the $courseid, $discussionid and $cmid must be true, also subscribe/unsubscribe
$options = ($courseid ? 1 : 0) + ($cmid ? 1 : 0) + ($discussionid ? 1 : 0);
$subscribeoptions = ($requestingsubscribe ? 1 : 0) + ($requestingunsubscribe ? 1 : 0);
if ($options != 1 || $subscribeoptions != 1) {
    print_error('error_subscribeparams', 'forumng');
}

// Permitted values 'index', 'view', 'discuss', nothing
$back = optional_param('back', '', PARAM_ALPHA);
if (!preg_match('~^(index|view|discuss)$~', $back)) {
    $back = '';
}
if (($back=='index' && !($cmid || $courseid))) {
    $back = '';
}
if (($back=='view' && !$cmid)) {
    $back = '';
}
if (($back=='discuss' && !$discussionid)) {
    $back = '';
}

try {
    //decide the subscription confirmation string for not directing
    if ($requestingsubscribe) {
        $subscribe = true;
    } else {
        $subscribe = false;
    }
    $confirmtext = get_string(
        $subscribe ? 'subscribe_already' : 'unsubscribe_already', 'forumng');

    // Handle single discussion
    if ($discussionid) {
        $discussion = forum_discussion::get_from_id($discussionid);
        $discussion->require_view();
        $forum = $discussion->get_forum();
        if (!$discussion->can_subscribe() && !$discussion->can_unsubscribe()) {
            print_error('error_cannotchangediscussionsubscription', 'forumng');
        }
        if ($requestingsubscribe && $discussion->can_subscribe()) {
            $discussion->subscribe();
            $confirmtext = get_string('subscribe_confirm', 'forumng');
        } else if ($requestingunsubscribe && $discussion->can_unsubscribe()) {
            $discussion->unsubscribe();
            $confirmtext = get_string('unsubscribe_confirm', 'forumng');
        }
    }

    // Handle single forum
    if ($cmid) {
        $forum = forum::get_from_cmid($cmid);
        $forum->require_view(forum::NO_GROUPS);

        if (isguestuser()) {
            // This section allows users who are responding to the unsubscribe
            // email link yet who may have already got guest access to the site.
            // The display of the yes/no option is similar to other module behaviour
            // though we could just redirect to login instead.
            $wwwroot = $CFG->wwwroot.'/login/index.php';
            if (!empty($CFG->loginhttps)) {
                $wwwroot = str_replace('http:', 'https:', $wwwroot);
            }
            $PAGEWILLCALLSKIPMAINDESTINATION = true;
            $forum->print_subpage_header(get_string('unsubscribeshort', 'forumng'));
            notice_yesno(get_string('noguestsubscribe', 'forumng').'<br /><br />'.get_string('liketologin'), $wwwroot, $CFG->wwwroot);
            print_footer($forum->get_course());
            exit;
        }

        if (!$forum->can_change_subscription()) {
            print_error('error_cannotchangesubscription', 'forumng');
        }
        $subscription_info = $forum->get_subscription_info();
        if ($subscription_info->wholeforum) {
            //subscribed to the entire forum
            $subscribed = forum::FULLY_SUBSCRIBED;
        } else if (count($subscription_info->discussionids) == 0) {
            $subscribed = forum::NOT_SUBSCRIBED;
        } else {
            $subscribed = forum::PARTIALLY_SUBSCRIBED;
        }
        if ($requestingsubscribe && $subscribed != forum::FULLY_SUBSCRIBED) {
            $forum->subscribe();
            $confirmtext = get_string('subscribe_confirm', 'forumng');
        } else if ($requestingunsubscribe && $subscribed != forum::NOT_SUBSCRIBED) {
            $forum->unsubscribe();
            $confirmtext = get_string('unsubscribe_confirm', 'forumng');
        }
    }

    // Handle whole course
    if ($courseid) {
        $course = get_record('course', 'id', $courseid);
        require_login($course);
        $forums = forum::get_course_forums($course, 0, forum::UNREAD_NONE);
        foreach ($forums as $forum) {
            $subscription_info = $forum->get_subscription_info();
            if ($subscription_info->wholeforum) {
                //subscribed to the entire forum
                $subscribed = forum::FULLY_SUBSCRIBED;
            } else if (count($subscription_info->discussionids) == 0) {
                $subscribed = forum::NOT_SUBSCRIBED;
            } else {
                $subscribed = forum::PARTIALLY_SUBSCRIBED;
            }
            if ($forum->can_change_subscription()) {
                if ($requestingsubscribe && $subscribed != forum::FULLY_SUBSCRIBED) {
                    $forum->subscribe();
                    $confirmtext = get_string('subscribe_confirm', 'forumng');
                } else if ($requestingunsubscribe && $subscribed != forum::NOT_SUBSCRIBED) {
                    $forum->unsubscribe();
                    $confirmtext = get_string('unsubscribe_confirm', 'forumng');
                }
            }
        }
    }

    // Redirect back
    $backurl ='';
    if ($back == 'index') {
        if (!$courseid) {
            $courseid = $forum->get_course()->id;
        }
        $backurl = $CFG->wwwroot . '/mod/forumng/index.php?id=' . $courseid;
        redirect('index.php?id=' . $courseid);
    }
    if ($back == 'view') {
        $backurl = $CFG->wwwroot . '/mod/forumng/view.php?id=' . $cmid;
        redirect('view.php?id=' . $cmid);
    }
    if ($back == 'discuss') {
        $backurl = $CFG->wwwroot . '/mod/forumng/discussion.php?d=' . $discussionid;
        redirect('discuss.php?d=' . $discussionid);
    }

    // Not redirecting? OK, confirm
    if ($cmid || $discussionid) {
        $backurl = $CFG->wwwroot . '/mod/forumng/view.php?id=' . 
            $forum->get_course_module_id();
        $PAGEWILLCALLSKIPMAINDESTINATION = true;
        $forum->print_subpage_header(get_string(
            $subscribe ? 'subscribeshort' : 'unsubscribeshort', 'forumng'));
        notice($confirmtext, $backurl, $forum->get_course());
        print_footer($forum->get_course());
    } else {
        $backurl = $CFG->wwwroot . '/course/view.php?id=' . $courseid;
        print_header_simple();
        notice($confirmtext, $backurl, $COURSE);
        print_footer($COURSE);
    }

} catch(Exception $e) {
    forum_utils::handle_exception($e);
}

?>
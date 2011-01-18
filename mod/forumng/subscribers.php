<?php
require_once('../../config.php');
require_once('forum.php');

function my_link_sort($a, $b) {
    $tl = textlib_get_instance();
    $a = $tl->strtolower(substr($a->link, strpos($a->link, '>')+1));
    $b = $tl->strtolower(substr($b->link, strpos($b->link, '>')+1));
    return strcmp($a, $b);
}

$cmid = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

try {
    $forum = forum::get_from_cmid($cmid, $cloneid);
    $cm = $forum->get_course_module();
    $course = $forum->get_course();

    $groupid = forum::get_activity_group($cm, true);
    $forum->require_view($groupid);
    if (!$forum->can_view_subscribers()) {
        print_error('subscribers_nopermission', 'forumng');
    }
    $canmanage = $forum->can_manage_subscriptions();

    // Get subscribers
    $subscriptionoption = $forum->get_effective_subscription_option();
    if ($subscriptionoption == forum::SUBSCRIPTION_FORCED) {
        $forcedsubscribers = $forum->get_auto_subscribers();
    } else {
        $forcedsubscribers = array();
    }
    if ($forum->is_forced_to_subscribe()) {
        $forcedsubscribers = $forum->get_auto_subscribers();
    }

    // If they clicked the unsubscribe button, do something different
    if (optional_param('unsubscribe', '', PARAM_RAW)) {
        if (!$canmanage) {
            print_error('unsubscribe_nopermission', 'forumng');
        }

        // Display header
        $navigation = array();
        $navigation[] = array(
            'name'=>get_string('unsubscribeselected', 'forumng'),
            'type'=>'forumng');

        print_header_simple(format_string($forum->get_name()), '',
            build_navigation($navigation, $cm), '', '', true, '',
            navmenu($course, $cm));
            
        $confirmarray = array('id'=>$cmid, 'confirmunsubscribe'=>1);
        $list = '<ul>';
        foreach(array_keys($_POST) as $key) {
            $matches = array();
            if (preg_match('~^user([0-9]+)$~', $key, $matches)) {
                $confirmarray[$key] = 1;
                $user = get_record('user', 'id', $matches[1], '','','','',
                    'id, username, firstname, lastname');
                $list .= '<li>' . $forum->display_user_link($user) . '</li>';
            }
        }
        $list .= '</ul>';

        notice_yesno(get_string('confirmbulkunsubscribe', 'forumng'), 
            'subscribers.php', 'subscribers.php',
            $confirmarray, array('id'=>$cmid),
            'post', 'get');
            
        print $list;

        print_footer($course);
        exit;
    }
    if (optional_param('confirmunsubscribe', 0, PARAM_INT)) {
        if (!$canmanage) {
            print_error('unsubscribe_nopermission', 'forumng');
        }
        $subscribers = $forum->get_subscribers($groupid);
        forum_utils::start_transaction();
        foreach(array_keys($_POST) as $key) {
            $matches = array();
            if (preg_match('~^user([0-9]+)$~', $key, $matches)) {
                // Use the subscribe list to check this user is on it. That
                // means they can't unsubscribe users in different groups.
                if(array_key_exists($matches[1], $subscribers)) {
                    $forum->unsubscribe($matches[1]);
                }
            }
        }
        forum_utils::finish_transaction();
        redirect('subscribers.php?' . $forum->get_link_params(forum::PARAM_PLAIN));
    }

    // Display header
    $navigation = array();
    $navigation[] = array(
        'name'=>get_string('subscribers', 'forumng'),
        'type'=>'forumng');

    print_header_simple(format_string($forum->get_name()), '',
        build_navigation($navigation, $cm), '', '', true, '',
        navmenu($course, $cm));
    $forum->print_js();

    // Display group selector if required
    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/forumng/subscribers.php?' .
            $forum->get_link_params(forum::PARAM_PLAIN));

    // Get all subscribers
    $subscribers = $forum->get_subscribers();
    $individualgroup = $groupid != forum::ALL_GROUPS && $groupid != forum::NO_GROUPS;

    //Remove the subscribers to other groups and discussions which don't belong to this group
    if ($individualgroup) {
        foreach ($subscribers as $key => $user) {
            $removeuser = true;
            if (array_key_exists($groupid, $user->groupids)) {
                $removeuser = false;
            }
            if (in_array($groupid, $user->discussionids)) {
                    $removeuser = false;
                    break;
            }
            if ($user->wholeforum) {
                $removeuser = false;
            }
            if ($removeuser) {
                unset($subscribers[$key]);
            }
        }
    }
    if (count($subscribers) == 0) {
        print '<p>' . get_string('nosubscribers' . 
            ($groupid==forum::ALL_GROUPS || $groupid==forum::NO_GROUPS
            ? '' : 'group'), 'forumng') . '</p>';
    } else {
        // Get name/link for each subscriber (this is used twice)
        foreach ($subscribers as $user) {
            $user->link = $forum->display_user_link($user);
        }

        // Sort subscribers into name order
        uasort($subscribers, 'my_link_sort');

        // Build table of subscribers
        $table = new stdClass;
        $table->head = array(get_string('user'));
        if ($CFG->forumng_showusername) {
            $table->head[] = get_string('username');
        }
        if ($CFG->forumng_showidnumber) {
            $table->head[] = get_string('idnumber');
        }
        $table->head[] = get_string('subscriptions', 'forumng');
        $table->data = array();

        if ($canmanage) {
            // Note: This form has to be a post because if there are a lot of
            // subscribers, the list will be too long to fit in a GET
            print '<form action="subscribers.php" method="post"><div>' .
                $forum->get_link_params(forum::PARAM_FORM);
        }

        $gotsome = false;
        foreach ($subscribers as $user) {
            $row = array();
            $name = $user->link;
            if ($canmanage && !array_key_exists($user->id, $forcedsubscribers)) {
                $name = "<input type='checkbox' name='user{$user->id}' " .
                    "value='1' id='check{$user->id}'/> " .
                    "<label for='check{$user->id}'>$name</label>";
                $gotsome = true;
            }
            $row[] = $name;
            if ($CFG->forumng_showusername) {
                $row[] = htmlspecialchars($user->username);
            }
            if ($CFG->forumng_showidnumber) {
                $row[] = htmlspecialchars($user->idnumber);
            }
            if ($user->wholeforum) {
                $row[] = get_string('subscribeddiscussionall', 'forumng');
            } else {
                if ($individualgroup) {
                    $numberofdiscussions = 0;
                    if (in_array($groupid, $user->discussionids)) {
                            $numberofdiscussions++;
                    }
                    $numberofdiscussions = $numberofdiscussions>0 ? $numberofdiscussions . '<br />' : '';
                    $grouplist = '';
                    foreach ($user->groupids as $id) {
                        if ($id == $groupid) {
                            $grouplist = get_string('subscribedthisgroup', 'forumng');
                            break;
                        }
                    }
                } else {
                    $numberofdiscussions = count($user->discussionids)>0 ? count($user->discussionids) . '<br />' : '';
                    $grouplist = '';
                    if (count($user->groupids)) {
                        foreach ($user->groupids as $id) {
                            $grouplist .= groups_get_group_name($id) . '<br />';
                        }
                    }
                }
                
                $row[] = $numberofdiscussions . $grouplist;
            }
            if ($user->link)
            $table->data[] = $row;
        }

        print_table($table);

        if ($canmanage) {
            if ($gotsome) {
                print '<div id="forumng-buttons"><input type="submit" ' .
                    'name="unsubscribe" value="' . 
                    get_string('unsubscribeselected', 'forumng') . '" /></div>';
            }
            print '</div></form>';
        }
    }

    print link_arrow_left($forum->get_name(), $forum->get_url(forum::PARAM_HTML));

    print_footer($course);
} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
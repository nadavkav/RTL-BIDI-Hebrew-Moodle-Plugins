<?php
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/forum.php');

$d = required_param('d', PARAM_INT);
$target = required_param('target', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
if (!$target) {
    print_error('move_notselected', 'forumng');
}

try {
    $discussion = forum_discussion::get_from_id($d, $cloneid);

    // Get target forum
    $targetforum = forum::get_from_cmid($target, forum::CLONE_DIRECT);

    // If it is a clone, find the original
    $targetforum = $targetforum->get_real_forum();

    // Check permission for move
    $discussion->require_view();
    require_capability('mod/forumng:movediscussions',
        $discussion->get_forum()->get_context());
    require_capability('mod/forumng:movediscussions',
        $targetforum->get_context());
    $aag = has_capability('moodle/site:accessallgroups',
        $targetforum->get_context());

    // Work out target group for move
    $targetgroup = $discussion->get_group_id();
    if ($targetforum->get_group_mode() == 0 ||
        (!$targetgroup && $aag && $discussion->get_forum()->get_group_mode() != 0)) {
        // Either target forum doesn't have groups, or it does have groups but
        // so does the source forum and this is already an all-groups post,
        // and you have access all groups, so it can be all-groups
        $targetgroup = null;
    } else {
        // Target forum has groups :( Need to decide a group
        if($targetgroup && 
            ($targetforum->get_grouping() != $discussion->get_forum()->get_grouping())) {
            // Had source group, but grouping has changed. 
            // See if old group belongs to new grouping.
            $allowedgroups = groups_get_all_groups(
                $targetforum->get_course_id(), 0, $targetforum->get_grouping(), 'g.id');
            if (!array_key_exists($targetgroup, $allowedgroups)) {
                // Old group not in new grouping, so don't know where to put it
                $targetgroup = null;
            }
        }

        // If we don't actually have a target group, get the list of allowed
        // groups and see if there is only one option - if so we will use it
        if (!$targetgroup) {
            // Work out list of allowed groups for current user
            $cm = $targetforum->get_course_module();
            $groups = groups_get_all_groups(
                $cm->course, $aag ? 0 : $USER->id, $cm->groupingid);
            $options = array();
            if ($groups) {
                foreach($groups as $group) {
                    $options[$group->id] = format_string($group->name);
                }
            }
            if ($aag) {
                $options[forum::ALL_GROUPS] = get_string('allparticipants');
            }

            // If there's only one then we'll use it
            if (count($options) == 1) {
                reset($options);
                $targetgroup = key($options);
            } else if(count($options) == 0) {
                print_error('move_nogroups', 'forumng');
            }
        }

        if (!$targetgroup) {
            // User needs to choose one from form
            require_once(dirname(__FILE__) . '/group_form.php');
            $mform = new mod_forumng_group_form('move.php', (object)array(
                'targetforum' => $targetforum, 'discussionid' => $d,
                'cloneid' => $cloneid, 'groups' => $options));
            if ($mform->is_cancelled()) {
                redirect('../../discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN));
            }
            if (($fromform = $mform->get_data()) && 
                array_key_exists($fromform->group, $options)) {
                $targetgroup = $fromform->group;
            } else {
                $pagename = get_string('move');
                $discussion->print_subpage_header($pagename);
                $mform->display();
                print_footer($discussion->get_forum()->get_course());
                return;
            }
        }
    }

    // Perform move
    $discussion->move($targetforum, $targetgroup);

    // Redirect to new forum
    redirect($targetforum->get_url(forum::PARAM_PLAIN));
} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
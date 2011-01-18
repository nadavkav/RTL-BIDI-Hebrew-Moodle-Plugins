<?php
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/forum.php');

$d = required_param('d', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
$stage = optional_param('stage', 1, PARAM_INT);

try {
    $discussion = forum_discussion::get_from_id($d, $cloneid);
    $forum = $discussion->get_forum();
    $cm = $forum->get_course_module();
    $course = $forum->get_course();

    // Require that you can see this discussion (etc) and merge them
    $discussion->require_view();
    if (!$discussion->can_split($whynot)) {
        print_error($whynot, 'forumng');
    }

    if ($stage == 2) {
        if (!confirm_sesskey()) {
            print_error('invalidsesskey');
        }

        if(!isset($_POST['cancel'])) {
            // Get source discussion and check permissions
            $sourcediscussion = forum_discussion::get_from_id(
                    $SESSION->forumng_mergefrom, $SESSION->forumng_mergefromclone);
            $sourcediscussion->require_view();
            if (!$sourcediscussion->can_split($whynot)) {
                print_error($whynot, 'forumng');
            }

            // Do actual merge
            $sourcediscussion->merge_into($discussion);
        }

        unset($SESSION->forumng_mergefrom);
        redirect('../../discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN));
    }

    // Create form
    require_once('merge_form.php');
    $mform = new mod_forumng_merge_form('merge.php', array('d'=>$d, 'clone'=>$cloneid));

    if ($mform->is_cancelled()) {
        redirect('../../discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN));
    } else if (($fromform = $mform->get_data(false)) ||
        get_user_preferences('forumng_hidemergehelp', 0)) {
        // Remember in session that the discussion is being merged
        $SESSION->forumng_mergefrom = $d;
        $SESSION->forumng_mergefromclone = $cloneid;

        if (!empty($fromform->hidelater)) {
            set_user_preference('forumng_hidemergehelp', 1);
        }

        // Redirect back to view page
        redirect($forum->get_url(forum::PARAM_PLAIN));
    }

    // Work out navigation for header
    $pagename = get_string('merge', 'forumng');

    $navigation = array();
    $navigation[] = array(
        'name'=>shorten_text(htmlspecialchars(
            $discussion->get_subject())),
        'link'=>$discussion->get_url(), 'type'=>'forumng');
    $navigation[] = array(
        'name'=>$pagename, 'type'=>'forumng');

    $PAGEWILLCALLSKIPMAINDESTINATION = true;
    print_header_simple(format_string($forum->get_name()) . ': ' . $pagename,
        "", build_navigation($navigation, $cm), "", "", true,
        '', navmenu($course, $cm));

    print skip_main_destination();

    // Print form
    $mform->display();
    print_footer($course);



} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
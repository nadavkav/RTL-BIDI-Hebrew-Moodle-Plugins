<?php
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/forum.php');

$d = required_param('d', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

try {
    $discussion = forum_discussion::get_from_id($d, $cloneid);
    $forum = $discussion->get_forum();
    $cm = $forum->get_course_module();
    $course = $forum->get_course();

    // Require that you can see this discussion (etc) and copy them
    $discussion->require_view();
    require_capability('mod/forumng:copydiscussion',
        $discussion->get_forum()->get_context());
    // Create form
    require_once('copy_form.php');
    $mform = new mod_forumng_copy_form('copy.php', array('d'=>$d, 'clone'=>$cloneid));

    if ($mform->is_cancelled()) {
        redirect('../../discuss.php?' . $discussion->get_link_params(forum::PARAM_PLAIN));
    } else if (($fromform = $mform->get_data(false)) ||
        get_user_preferences('forumng_hidecopyhelp', 0)) {
        // Remember in session that the discussion is being copied
        $SESSION->forumng_copyfrom = $d;
        $SESSION->forumng_copyfromclone = $cloneid;

        if (!empty($fromform->hidelater)) {
            set_user_preference('forumng_hidecopyhelp', 1);
        }
        // Redirect back to view page
        redirect($forum->get_url(forum::PARAM_PLAIN));
    }

    $pagename = get_string('copy_title', 'forumng');
    $discussion->print_subpage_header($pagename);

    // Print form
    $mform->display();
    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
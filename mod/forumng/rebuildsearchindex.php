<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/forumng/forum.php');
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

// This script is for use only temporarily to respond to a glitch in the
// forum -> ForumNG conversion script where it didn't build search indexes.
// This file lets the search index be manually rebuilt. We should probably 
// delete it later.
$cmid = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

try {
    $forum = forum::get_from_cmid($cmid, $cloneid);
    $cm = $forum->get_course_module();
    forum::search_installed();

    // This script is not very user friendly. Once it finishes, it's done...
    print_header();
    forum::search_update_all(true, $cm->course, $cm->id);
    print_footer();
} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}

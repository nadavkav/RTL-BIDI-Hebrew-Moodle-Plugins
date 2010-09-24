<?php
require_once('../../config.php');
require_once('forum.php');

// TODO Support course searches too
$cmid = required_param('id', PARAM_INT);
$querytext = stripslashes(required_param('query', PARAM_RAW));

try {
    $forum = forum::get_from_cmid($cmid);
    $cm = $forum->get_course_module();
    $course = $forum->get_course();
    $groupid = forum::get_activity_group($cm, true);
    $forum->require_view($groupid, 0, true);
    forum::search_installed();

    // Search form for header
    $buttontext = $forum->display_search_form($querytext);

    // Display header
    $navigation = array();
    $navigation[] = array(
        'name'=>get_string('searchfor', 'block_ousearch', $querytext),
        'type'=>'forumng');

    print_header_simple(format_string($forum->get_name()), '',
        build_navigation($navigation, $cm), '', '', true, $buttontext,
        navmenu($course, $cm));

    // Display group selector if required
    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/forumng/search.php?id=' . 
        $cmid . '&amp;query=' . rawurlencode($querytext));

    $searchurl = 'search.php?id=' . $cmid;
    $query = new ousearch_search($querytext);
    $query->set_coursemodule($cm);
    if($groupid && $groupid!=forum::NO_GROUPS) {
        $query->set_group_id($groupid);
    }
    ousearch_display_results($query,$searchurl);

    //Print advanced search link
    $options = ($cmid) ? "?id=$cmid" : '';
    $options .= '&amp;action=0';
    $options .= ($querytext) ? "&amp;query=$querytext" : '';
    $url = $CFG->wwwroot .'/mod/forumng/advancedsearch.php' . $options;
    $strlink = get_string('moresearchoptions', 'forumng');
    print "<div class='advanced-search-link'>
            <a href=\"$url\">$strlink</a></div>";

    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
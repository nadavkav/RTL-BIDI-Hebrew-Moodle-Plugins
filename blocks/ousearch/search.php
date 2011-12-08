<?php
// Lets users search everything (that uses ousearch) on a course.
require_once('../../config.php');
require_once('searchlib.php');

// User must be logged in
$courseid=required_param('course',PARAM_INT);
$plugin = optional_param('plugin', '',PARAM_RAW);

// User must be logged in
$coursecontext=get_context_instance(CONTEXT_COURSE,$courseid);
require_login($courseid);

$extranavigation=array();
$extranavigation[]=array('name'=>get_string('searchresults'),'type'=>'misc');
$navigation=build_navigation($extranavigation);
print_header_simple(get_string('searchresults'), "", $navigation);

$querytext=stripslashes(required_param('query',PARAM_RAW));
$query=new ousearch_search($querytext);
if (strpos($plugin, 'mod/') === 0) {
    $modname = substr($plugin, 4);
} else {
    $modname = null;
}
$query->set_visible_modules_in_course($COURSE, $modname);

// Restrict them to the groups they belong to
if (!isset($USER->groupmember[$courseid])) {
    $query->set_group_ids(array());
} else {
    $query->set_group_ids($USER->groupmember[$courseid]);
}

// Add exceptions where they can see other groups
$query->set_group_exceptions(ousearch_get_group_exceptions($courseid));

$query->set_user_id($USER->id);

$query->set_plugin($plugin);

ousearch_display_results(
    $query,'search.php?course=' . $courseid . '&plugin=' . $plugin );

//Print advanced search link
if ($plugin == 'mod/forumng') {
    $querytext = rawurlencode($querytext);
    $options = "course=$courseid&amp;query=$querytext";
    $url = $CFG->wwwroot .'/mod/forumng/advancedsearch.php?' . $options;
    $strlink = get_string('moresearchoptions', 'forumng');
    print "<div class='advanced-search-link'>
            <a href=\"$url\">$strlink</a></div>";
}
// Footer
print_footer();
?>

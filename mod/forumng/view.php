<?php
require_once('../../config.php');

if (class_exists('ouflags')) {
	require_once('../../local/mobile/ou_lib.php');
	
	global $OUMOBILESUPPORT;
	$OUMOBILESUPPORT = true;
	ou_set_is_mobile(ou_get_is_mobile_from_cookies());
}


require_once('forum.php');
if (class_exists('ouflags')) {
    $DASHBOARD_COUNTER = DASHBOARD_FORUMNG_VIEW;
}
// Require ID parameter here. Other parameters may be required in forum type.
$id = required_param('id', PARAM_INT);

// On the view page ONLY we allow a default for the clone parameter that won't
// cause an error if it's omitted. All other pages have default 0, which will
// show up any errors caused if the parameter is omitted somewhere.
$cloneid = optional_param('clone', forum::CLONE_DIRECT, PARAM_INT);

try {
    // Construct forum variable (will check id is valid)
    $forum = forum::get_from_cmid($id, $cloneid);
    $course = $forum->get_course();
    $cm = $forum->get_course_module();

    // If this is a clone, redirect to original
    if ($forum->is_clone()) {
        $forum->redirect_to_original();
    }

    // Check that forum can be viewed [Handles all other permissions]
    $groupid = forum::get_activity_group($cm, true);
    $forum->require_view($groupid, 0, true);

    // Get update button, if allowed for current user
    $strforum = get_string("modulename", "forum");

    $buttontext = $forum->display_search_form();

    // Atom header meta tag
    $feedtype = $forum->get_effective_feed_option();
    if ($feedtype == forum::FEEDTYPE_DISCUSSIONS ||
        ($feedtype == forum::FEEDTYPE_ALL_POSTS
            && $forum->can_view_discussions())) {
        $atomurl = $forum->get_feed_url(forum::FEEDFORMAT_ATOM, $groupid);
        $meta = '<link rel="alternate" type="application/atom+xml" ' .
          'title="Atom feed" href="' . htmlspecialchars($atomurl) . '" />';
    } else {
        $meta = '';
    }

    // Initialize $PAGE, compute blocks
    require_once($CFG->dirroot . '/mod/forumng/pagelib.php');
    global $CURRENTFORUM;
    $CURRENTFORUM = $forum;
    $PAGE = page_create_instance($forum->get_id());
    $pageblocks = blocks_setup($PAGE);
    
    if (class_exists('ouflags') && ou_get_is_mobile()){
        ou_mobile_configure_theme();
    }

    // Check for editing on/off button presses
    if ($PAGE->user_allowed_editing()) {
        $edit = optional_param('edit', -1, PARAM_BOOL);
        $goback = false;

        if (class_exists('ouflags')) {
            // OU only: support edit locking and per-course editing
            $resetlock = optional_param('resetlock', 0, PARAM_BOOL);

            if ($edit != -1 || $resetlock == 1) {
                // Update user editing status
                updateediting($edit, $resetlock, $course);
                $goback = true;
            }
        } else if ($edit != -1) {
            // Non-OU: just turn on edit flag
            $USER->editing = $edit;
            $goback = true;
        }

        if ($goback) {
            $url = preg_replace('~&edit=[^&]*~', '', $FULLME);
            redirect($url);
        }
    }

    // Note: Course ID is ignored outside OU
    $editing = class_exists('ouflags') ? isediting($cm->course) : isediting();

    // Display header. Because this pagelib class doesn't actually have a
    // $buttontext parameter, there has to be a really evil hack
    $PAGEWILLCALLSKIPMAINDESTINATION = true;
    $PAGE->print_header(
        $course->shortname . ': ' . format_string($forum->get_name()),
        null, '', $meta, $buttontext);
    $forum->print_js($cm->id);

    // The left column ...
    if($hasleft = !empty($CFG->showblocksonmodpages)
        && (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $editing)) {
        print '<div id="left-column">';
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
        print '</div>';
    }
    if($hasright = !empty($CFG->showblocksonmodpages)
        && (blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $editing)) {
        print '<div id="right-column">';
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
        print '</div>';
    }

    $classes = trim(
        ($hasleft ? 'has-left-column ' : '') .
        ($hasright ? 'has-right-column' : ''));
    print "<div id='middle-column' class='$classes'>";

    //adding a link to the computing guide
    if(!(@include_once $CFG->dirroot.'/local/utils_shared.php')) {
        //Only used for forumng within Core Moodle (not OU Moodle)
        require_once('local/utils_shared.php');
    }
    $computingguidelink = get_link_to_computing_guide('forumng');
    print '<span class="computing-guide"> '.$computingguidelink.'</span>';

    // Display group selector if required
    groups_print_activity_menu($cm, $forum->get_url(forum::PARAM_HTML));

    print '<div class="forumng-main">';
    print $forum->get_type()->display_switch_link();
    print skip_main_destination();
    // Get forum type to display main part of page
    $forum->get_type()->print_view_page($forum, $groupid);

    // Show dashboard feature if enabled
    if (class_exists('ouflags')) {
        require_once($CFG->dirroot . '/local/externaldashboard/external_dashboard.php');
        external_dashboard::print_favourites_button($cm);
    }

    print '</div></div>';

    // Log request
    $forum->log('view');

    // Update completion 'viewed' flag if in use
    if (class_exists('ouflags')) {
        completion_set_module_viewed($course, $cm);
    }

    // Display footer
    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
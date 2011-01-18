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

// Require discussion parameter here. Other parameters may be required in forum
// type.
$discussionid = required_param('d', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

try {
    // Construct discussion variable (will check id is valid)
    // Retrieve new copy of discussion from database, but store it in cache
    // for further use.
    $discussion = forum_discussion::get_from_id($discussionid, $cloneid,
            0, false, true);
    $forum = $discussion->get_forum();
    $course = $forum->get_course();

    $cm = $forum->get_course_module();
    $context = $forum->get_context();

    $draftid = optional_param('draft', 0, PARAM_INT);
    if ($draftid) {
        $draft = forum_draft::get_from_id($draftid);
        if (!$draft->is_reply() || 
            $draft->get_discussion_id() != $discussionid) {
            print_error('draft_mismatch', 'forumng', 
                $forum->get_url(forum::PARAM_HTML));
        }
        $root = $discussion->get_root_post();
        $inreplyto = $root->find_child($draft->get_parent_post_id(), false);
        if (!$inreplyto || !$inreplyto->can_reply($whynot) ||
            !$discussion->can_view()) {
            print_error('draft_cannotreply', 'forumng', 
                $forum->get_url(forum::PARAM_HTML),
                get_string($whynot, 'forumng'));
        }
        $inreplyto->force_expand();
        $draftplayspaceid = 0;
        if ($draft->has_attachments()) {
            $draftplayspaceid = forum::create_attachment_playspace();
            $target = forum::get_attachment_playspace_folder($draftplayspaceid);
            $source = $draft->get_attachment_folder();
            foreach($draft->get_attachment_names() as $name) {
                forum_utils::copy("$source/$name", "$target/$name");
            }
        }
    }

    // Check that discussion can be viewed [Handles all other permissions]
    $discussion->require_view();

    // Search form for header
    $buttontext = $forum->display_search_form();

    // Atom header meta tag
    $feedtype = $forum->get_effective_feed_option();
    if ($feedtype == forum::FEEDTYPE_ALL_POSTS) {
        $atomurl = $discussion->get_feed_url(forum::FEEDFORMAT_ATOM);
        $meta = '<link rel="alternate" type="application/atom+xml" ' .
          'title="Atom feed" href="' . htmlspecialchars($atomurl) . '" />';
    } else {
        $meta = '';
    }

    // Display header
    $navigation = array();
    $navigation[] = array(
        'name'=>shorten_text(htmlspecialchars($discussion->get_subject())),
        'type'=>'forumng');

    if(class_exists('ouflags') && ou_get_is_mobile()){
        ou_mobile_configure_theme();
    }

    $PAGEWILLCALLSKIPMAINDESTINATION = true;
    print_header_simple(format_string($forum->get_name()), '',
        build_navigation($navigation, $cm), '', $meta, true, $buttontext,
        navmenu($course, $cm));
    $forum->print_js($cm->id);

    if ($draftid) {
        $draft->print_js_variable($draftplayspaceid);
    }

    print '<div id="forumng-main" class="forumng-discuss forumng-nojs' .
        ($discussion->is_deleted() ? ' forumng-deleted-discussion' : '' ) . '">';
    print $forum->get_type()->display_switch_link();
    print skip_main_destination();

    // Get forum type to display main part of page
    $type = $forum->get_type();
    $type->print_discussion_page($discussion);

    print '</div>';

    if ($bad = forum_utils::is_bad_browser()) {
        print '<div class="forumng-bad-browser">'. 
            get_string('badbrowser', 'forumng', $bad) . '</div>';
    }

    // Log request
    $discussion->log('view discussion');

    // Display footer
    print_footer($course);

} catch(forum_exception $e) {
    forum_utils::handle_exception($e);
}
?>
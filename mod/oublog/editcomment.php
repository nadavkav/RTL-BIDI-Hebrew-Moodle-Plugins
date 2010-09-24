<?php
/**
 * This page allows a user to add and edit blog comments
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */
// This code tells OU authentication system to let the public access this page
// (subject to Moodle restrictions below and with the accompanying .sams file).
global $DISABLESAMS;
$DISABLESAMS = 'opt';

    require_once("../../config.php");
    require_once("locallib.php");
    require_once('comment_form.php');

define('OUBLOG_CONFIRMED_COOKIE', 'OUBLOG_REALPERSON');

    $blog = required_param('blog', PARAM_INT);              // Blog ID
    $postid = required_param('post', PARAM_INT);            // Post ID for editing
    $commentid = optional_param('comment', 0, PARAM_INT);   // Comment ID for editing

    if(class_exists('ouflags')) {
	    require_once('../../local/mobile/ou_lib.php');
	    
	    global $OUMOBILESUPPORT;
	    $OUMOBILESUPPORT = true;
	    ou_set_is_mobile(ou_get_is_mobile_from_cookies());
	
	    $blogdets = optional_param('blogdets', null, PARAM_TEXT); 
    }
    
    if (!$oublog = get_record("oublog", "id", $blog)) {
        error('Blog parameter is incorrect');
    }
    if (!$cm = get_coursemodule_from_instance('oublog', $blog)) {
        error('Course module ID was incorrect');
    }
    if (!$course = get_record("course", "id", $oublog->course)) {
        error('Course is misconfigured');
    }
    if (!$post = get_record('oublog_posts', 'id', $postid)) {
        error('Post not found');
    }
    if (!$oubloginstance = get_record('oublog_instances', 'id', $post->oubloginstancesid)) {
        error('Blog instance not found');
    }

/// Check security
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    oublog_check_view_permissions($oublog, $context, $cm);
    $post->userid=$oubloginstance->userid; // oublog_can_view_post needs this
    if(!oublog_can_view_post($post,$USER,$context,$oublog->global)) {
        print_error('accessdenied','oublog');
    }

    oublog_get_activity_groupmode($cm, $course);
    if (!oublog_can_comment($cm, $oublog, $post)) {
        print_error('accessdenied','oublog');
    }

    if ($oublog->allowcomments == OUBLOG_COMMENTS_PREVENT || $post->allowcomments == OUBLOG_COMMENTS_PREVENT) {
        error('Comments are not allowed');
    }

    $viewurl = 'viewpost.php?post='.$post->id;
    if ($oublog->global) {
        $blogtype = 'personal';
        if (!$oubloguser = get_record('user', 'id', $oubloginstance->userid)) {
            error("User not found");
        }
    } else {
        $blogtype = 'course';
    }

/// Get strings
    $stroublogs  = get_string('modulenameplural', 'oublog');
    $stroublog   = get_string('modulename', 'oublog');
    $straddcomment  = get_string('newcomment', 'oublog');
    $streditcomment = get_string('editcomment', 'oublog');

    $moderated = !(isloggedin() && !isguestuser());
    $confirmed = isset($_COOKIE[OUBLOG_CONFIRMED_COOKIE]) &&
            $_COOKIE[OUBLOG_CONFIRMED_COOKIE] == get_string(
                'moderated_confirmvalue', 'oublog');
    $mform = new mod_oublog_comment_form('editcomment.php', array(
            'maxvisibility' => $oublog->maxvisibility,
            'edit' => !empty($commentid),
            'blogid' => $blog,
            'postid' => $postid,
            'moderated' => $moderated,
            'confirmed' => $confirmed
            ));

    if ($mform->is_cancelled()) {
        redirect($viewurl);
        exit;
    }

    if (!$comment = $mform->get_data()) {

        $comment = new stdClass;
        $comment->general = $straddcomment;
        $comment->blog = $blog;
        $comment->post = $postid;
        $mform->set_data($comment);

/// Generate extra navigation
        $extranav = array();
        $extranav[] = oublog_get_post_extranav($post);
        $extranav[] = array('name' => $comment->general, 'link' => '', 'type' => 'misc');
        
/// Print the header
        if (class_exists('ouflags') && ou_get_is_mobile()){
            ou_mobile_configure_theme();
        }
        
        if ($blogtype == 'personal') {
            $navigation = oublog_build_navigation($cm, $oublog, $oubloginstance, $oubloguser, $extranav);
            print_header_simple(format_string($oublog->name), "", $navigation, "", "", true);
        } else {
            $navigation = oublog_build_navigation($cm, $oublog, $oubloginstance, null, $extranav);
            print_header_simple(format_string($oublog->name), "", $navigation, "", "", true,
                      update_module_button($cm->id, $course->id, $stroublog), navmenu($course, $cm));
        }

        echo '<br />';
        $mform->display();

        print_footer();

    } else {
        if(class_exists('ouflags')) {
            $DASHBOARD_COUNTER=DASHBOARD_BLOG_COMMENT;
        }

        // Prepare comment for database
        unset($comment->id);
        $comment->userid = $USER->id;
        $comment->postid = $postid;

        // Special behaviour for moderated users
        if ($moderated) {
            // Check IP address
            if (oublog_too_many_comments_from_ip()) {
                print_error('error_toomanycomments', 'oublog');
            }

            // Set the confirmed cookie if they haven't got it yet
            if (!$confirmed) {
                setcookie(OUBLOG_CONFIRMED_COOKIE, $comment->confirm, 
                        time() + 365 * 24 * 3600); // Expire in 1 year
            }

            if (!oublog_add_comment_moderated($oublog, $oubloginstance, $post, $comment)) {
                error('Could not add comment');
            }
            $approvaltime = oublog_get_typical_approval_time($post->userid);

            $extranav = array();
            $extranav[] = oublog_get_post_extranav($post);
            $extranav[] = array(
                'name' => get_string('moderated_submitted', 'oublog'), 
                'link' => '', 'type' => 'misc');
            $nav = oublog_build_navigation($cm, $oublog, $oubloginstance, 
                isset($oubloguser) ? $oubloguser : null, $extranav);
            print_header_simple(format_string($oublog->name), '', $nav);
            notice(get_string('moderated_addedcomment', 'oublog') .
                    ($approvaltime ? ' ' .  
                        get_string('moderated_typicaltime', 'oublog', $approvaltime) 
                    : ''), 'viewpost.php?post=' . $postid, $course);
            // does not continue
        }

        $comment->userid = $USER->id;
        
        if (!oublog_add_comment($course,$cm,$oublog,$comment)) {
            error('Could not add comment');
        }
        add_to_log($course->id, "oublog", "add comment", $viewurl, $oublog->id, $cm->id);
        redirect($viewurl);
    }

?>
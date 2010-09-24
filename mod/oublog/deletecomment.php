<?php
/**
 * This page allows a user to delete a blog comments
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */
    require_once("../../config.php");
    require_once("locallib.php");

    $commentid  = required_param('comment', PARAM_INT);    // Comment ID to delete
    $confirm = optional_param('confirm', 0, PARAM_INT);    // Confirm that it is ok to delete comment

    if(class_exists('ouflags')) {
        require_once('../../local/mobile/ou_lib.php');
        
        global $OUMOBILESUPPORT;
        $OUMOBILESUPPORT = true;
        ou_set_is_mobile(ou_get_is_mobile_from_cookies());
    }
    
    if (!$comment = get_record('oublog_comments', 'id', $commentid)) {
        error('Comment ID was incorrect');
    }

    if (!$post = oublog_get_post($comment->postid)) {
        error("Post ID was incorrect");
    }

    if (!$cm = get_coursemodule_from_instance('oublog', $post->oublogid)) {
        error("Course module ID was incorrect");
    }

    if (!$course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    if (!$oublog = get_record("oublog", "id", $cm->instance)) {
        error("Course module is incorrect");
    }

/// Check security
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    oublog_check_view_permissions($oublog, $context, $cm);

    // You can always delete your own comments, or any comment on your own
    // personal blog
    if(!($comment->userid==$USER->id || 
        ($oublog->global && $post->userid == $USER->id))) {
        require_capability('mod/oublog:managecomments', $context);
    }

    if ($oublog->global) {
        $blogtype = 'personal';
        // Get blog user from the oublog_get_post result (to save making an
        // extra query); this is only used to display their name anyhow
        $oubloguser = (object)array('id'=>$post->userid, 
            'firstname'=>$post->firstname, 'lastname'=>$post->lastname);
    } else {
        $blogtype = 'course';

    }
    $viewurl = 'viewpost.php?post='.$post->id;

    if (!empty($commentid) && !empty($confirm)) {
        $updatecomment = (object)array(
            'id' => $commentid,
            'deletedby' => $USER->id,
            'timedeleted' => time());
        update_record('oublog_comments', $updatecomment);

        // Inform completion system, if available
        if(class_exists('ouflags')) {
            if(completion_is_enabled($course,$cm) && ($oublog->completioncomments)) {
                completion_update_state($course,$cm,COMPLETION_INCOMPLETE,$comment->userid);
            }    
        }
        
        redirect($viewurl);
        exit;
    }

/// Get Strings
    $stroublogs  = get_string('modulenameplural', 'oublog');
    $stroublog   = get_string('modulename', 'oublog');

/// Print the header
    if (class_exists('ouflags') && ou_get_is_mobile()){
        ou_mobile_configure_theme();
    }
    
    if ($blogtype == 'personal') {

        $navlinks = array();
        $navlinks[] = array('name' => fullname($oubloguser), 'link' => "../../user/view.php?id=$oubloguser->id", 'type' => 'misc');
        $navlinks[] = array('name' => format_string($oublog->name), 'link' => '', 'type' => 'activityinstance');

        $navigation = build_navigation($navlinks);
        print_header_simple(format_string($oublog->name), "", $navigation, "", "", true);

    } else {
        $navlinks = array();
        $navigation = build_navigation($navlinks, $cm);

        print_header_simple(format_string($oublog->name), "", $navigation, "", "", true,
                      update_module_button($cm->id, $course->id, $stroublog), navmenu($course, $cm));
    }

    notice_yesno(get_string('confirmdeletecomment', 'oublog'), 'deletecomment.php?comment='.$commentid.'&amp;confirm=1', $viewurl);

?>
<?php
/**
 * This page allows a user to delete a blog posts
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */
    require_once("../../config.php");
    require_once("locallib.php");

    $blog    = required_param('blog', PARAM_INT);    // Blog ID
    $postid  = required_param('post', PARAM_INT);    // Post ID for editing
    $confirm = optional_param('confirm', 0, PARAM_INT); // Confirm that it is ok to delete post

    if(class_exists('ouflags')) {
        require_once('../../local/mobile/ou_lib.php');
        
        global $OUMOBILESUPPORT;
        $OUMOBILESUPPORT = true;
        ou_set_is_mobile(ou_get_is_mobile_from_cookies());
    }
    
    if (!$oublog = get_record("oublog", "id", $blog)) {
        error('Blog parameter is incorrect');
    }
    if (!$cm = get_coursemodule_from_instance('oublog', $blog)) {
        error('Course module ID was incorrect');
    }
    if (!$course = get_record("course", "id", $oublog->course)) {
        error("Course is misconfigured");
    }

/// Check security
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    oublog_check_view_permissions($oublog, $context, $cm);

    $postauthor=get_field_sql("
SELECT 
    i.userid 
FROM 
    {$CFG->prefix}oublog_posts p
    INNER JOIN {$CFG->prefix}oublog_instances i on p.oubloginstancesid=i.id
WHERE
    p.id=$postid");
    if($postauthor!=$USER->id) {    
        require_capability('mod/oublog:manageposts', $context);
    }

    if ($oublog->global) {
        $blogtype = 'personal';
        $oubloguser = $USER;
        $viewurl = 'view.php?user='.$USER->id;
    } else {
        $blogtype = 'course';
        $viewurl = 'view.php?id='.$cm->id;
    }
    
    if (!empty($postid) && !empty($confirm)) {
        $expost=get_record('oublog_posts','id',$postid);

        $updatepost = (object)array(
            'id' => $postid,
            'deletedby' => $USER->id,
            'timedeleted' => time()
        );

        $tw=new transaction_wrapper();
        update_record('oublog_posts', $updatepost);
        if(!oublog_update_item_tags($expost->oubloginstancesid, $expost->id, array(),$expost->visibility)) {
            $tw->rollback();
            error('Failed to update tags');
        }
        if(oublog_search_installed()) {
            $doc=oublog_get_search_document($updatepost, $cm);
            $doc->delete();
        }
        // Inform completion system, if available
        if(class_exists('ouflags')) {
            if(completion_is_enabled($course,$cm) && ($oublog->completionposts)) {
                completion_update_state($course,$cm,COMPLETION_INCOMPLETE,$postauthor);
            }    
        }                
        $tw->commit();
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

    notice_yesno(get_string('confirmdeletepost', 'oublog'), 'deletepost.php?blog='.$blog.'&amp;post='.$postid.'&amp;confirm=1', $viewurl);

?>
<?php
/**
 * This page allows a user to delete a blog comments
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */
    require_once("../../config.php");
    require_once("locallib.php");

    $linkid  = required_param('link', PARAM_INT);          // Link ID to delete
    $confirm = optional_param('confirm', 0, PARAM_INT);    // Confirm that it is ok to delete link

    if(class_exists('ouflags')) {
        require_once('../../local/mobile/ou_lib.php');
        
        global $OUMOBILESUPPORT;
        $OUMOBILESUPPORT = true;
        ou_set_is_mobile(ou_get_is_mobile_from_cookies());
    }
    
    if (!$link = get_record('oublog_links', 'id', $linkid)) {
        error('Link ID was incorrect');
    }

    if (!$cm = get_coursemodule_from_instance('oublog', $link->oublogid)) {
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

    $oubloginstance = $link->oubloginstancesid ? get_record('oublog_instances', 'id', $link->oubloginstancesid) : null;
    oublog_require_userblog_permission('mod/oublog:managelinks', $oublog,$oubloginstance,$context);
    
    if ($oublog->global) {
        $blogtype = 'personal';
        $oubloguser = $USER;
    } else {
        $blogtype = 'course';
    }
    
    if (class_exists('ouflags') && ou_get_is_mobile()){
        $viewurl = 'view.php?blogdets=show&user='.$oubloginstance->userid;
    }
    else {
        $viewurl = 'view.php?id='.$cm->id;
    }

    if (!empty($linkid) && !empty($confirm)) {
        oublog_delete_link($oublog, $link);
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

    notice_yesno(get_string('confirmdeletelink', 'oublog'), 'deletelink.php?link='.$linkid.'&amp;confirm=1', $viewurl);

?>
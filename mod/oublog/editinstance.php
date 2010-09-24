<?php
/**
 * This page allows a user to edit their personal blog
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */
    define('OUBLOG_EDIT_INSTANCE', true);

    require_once('../../config.php');
    require_once('locallib.php');
    require_once('lib.php');
    require_once('mod_form.php');

    $bloginstancesid = required_param('instance', PARAM_INT);        // Bloginstance
    $postid = optional_param('post', 0, PARAM_INT);   // Post ID for editing

    if (!$oubloginstance = get_record('oublog_instances', 'id', $bloginstancesid)) {
        error('Instance parameter is incorrect');
    }
    if (!$oublog = get_record("oublog", "id", $oubloginstance->oublogid)) {
        error('Blog parameter is incorrect');
    }
    if (!$oublog->global) {
        error('Instance parameter is incorrect');
    }
    if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
        error('Course module ID was incorrect');
    }
    if (!$course = get_record("course", "id", $oublog->course)) {
        error("Course is misconfigured");
    }

/// Check security
    if (!$oublog->global) {
        error('Only works for personal blogs');
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    oublog_check_view_permissions($oublog, $context, $cm);
    $oubloguser = get_record('user','id',$oubloginstance->userid);
    $viewurl = 'view.php?user='.$oubloginstance->userid;

    if ($USER->id != $oubloginstance->userid && !has_capability('mod/oublog:manageposts', $context)) {
        print_error('accessdenied','oublog');
    }

/// Get strings
    $stroublogs     = get_string('modulenameplural', 'oublog');
    $stroublog      = get_string('modulename', 'oublog');
    $straddpost     = get_string('newpost', 'oublog');
    $streditpost    = get_string('editpost', 'oublog');
    $strblogoptions = get_string('blogoptions', 'oublog');

/// Set-up groups
    $currentgroup = oublog_get_activity_group($cm, true);
    $groupmode = oublog_get_activity_groupmode($cm);

    $mform = new mod_oublog_mod_form('editinstance.php', array('maxvisibility' => $oublog->maxvisibility, 'edit' => !empty($postid)));

    if ($mform->is_cancelled()) {
        redirect($viewurl);
        exit;
    }

    if (!$frmoubloginstance = $mform->get_data()) {

        $oubloginstance->instance = $oubloginstance->id;
        $mform->set_data($oubloginstance);


    /// Print the header
        $navigation = oublog_build_navigation($cm, $oublog, $oubloginstance, 
            $oubloguser,
            array('name' => $strblogoptions, 'link' => '', 'type' => 'misc'));
        print_header_simple(format_string($oublog->name), "", $navigation, "", "", true);


        echo '<br />';
        $mform->display();

        print_footer();

    } else {

    /// Handle form submission
        $frmoubloginstance->id = $frmoubloginstance->instance;
        $frmoubloginstance->message = $frmoubloginstance->summary;
        update_record('oublog_instances', $frmoubloginstance);

        redirect($viewurl);
    }

?>
<?php // $Id: lock.php,v 1.2 2007/04/27 09:10:51 janne Exp $
/// This script increases page editing lock when requested.
/// This script is only used by javascript XHTTPRequest method
/// and it's called periodically when editing eg. every 20 mins.

    require_once("../../config.php");

    if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
        error("Invalid request of script!", $CFG->wwwroot);
    }

    require_once("lib.php");

    $id        = required_param('id',  PARAM_INT); // module id
    $articleid = required_param('aid', PARAM_INT); // article id POST method
    $publishid = required_param('pid', PARAM_INT); // Netpublish instance
    $skey      = required_param('sesskey');        // Session key

    if ($id) {
        // Get all that I need using only one query

        if (! $info = netpublish_get_record($id) ) {
            error("Course Module ID was incorrect",
                  sprintf("%s/course/view.php?id=%d", $CFG->wwwroot, $COURSE->id));
        }
    } else {
        // Get all that I need using only one query
        if (! $info = netpublish_get_record($a) ) {
            error("Course Module ID was incorrect");
        }
    }

    // Construct objects used in Moodle
    netpublish_set_std_classes ($cm, $course, $mod, $info);
    unset($info);

    require_login($course->id);

    $isteacher = has_capability('moodle/legacy:teacher', get_context_instance(CONTEXT_COURSE, $course->id));
    $isstudent = has_capability('moodle/legacy:student', get_context_instance(CONTEXT_COURSE, $course->id));

    $canedit   = false;

    if (!$isteacher && !$isstudent) {
        echo 'false';
        exit;
    }

    $permissions = get_record_sql("SELECT id, userid, rights
                                   FROM {$CFG->prefix}netpublish_articles
                                   WHERE id = $articleid");

    if ($isteacher) {
        $canedit = true;
    }

    if (!empty($permissions) && intval($permissions->userid) == intval($USER->id)) {
        $canedit = true;
    }

    if (!empty($permissions) && !empty($permissions[$USER->id]) &&
        $nperm->can_write($permissions[$USER->id])) {

        $canedit = true;
    }

    if (! $canedit) {
        echo 'false';
        exit;
    }

    if ($data = data_submitted()) {
        if (confirm_sesskey($skey)) {
            if (netpublish_set_lock($articleid, $USER->id, $publishid)) {
                echo 'OK';
            } else {
                echo 'false';
            }
        } else {
            echo 'false';
        }
    } else {
        echo 'false';
    }
?>
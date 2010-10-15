<?php // $Id: delete.php,v 1.2 2007/04/27 09:10:51 janne Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id      = required_param('id',         PARAM_INT); // module id
    $article = required_param('article',    PARAM_INT); // article id
    $skey    = required_param('s');

    if (!confirm_sesskey($skey)) {
        error("Session error!", $CFG->wwwroot .'/mod/netpublish/view.php?id='. $id);
    }

    if ($id) {

        // Get all that I need using only one query
        if (! $info = netpublish_get_record($id) ) {
            error("Course Module ID was incorrect");
        }
    }

    // Construct objects used in Moodle
    netpublish_set_std_classes ($cm, $course, $mod, $info);
    unset($info);

    require_login($course->id);

    $redirectonerror = $CFG->wwwroot .'/mod/netpublish/view.php?id='. $cm->id;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if ( !has_capability('mod/netpublish:deletearticle', $context) ) {
        error(get_string('errorpermissiondeletearticle','netpublish'),
              $redirectonerror);
    }

    $form = get_record("netpublish_articles", "id", $article);

    // Only article owner or teacher can remove article.

    $canremove = false;

    if (intval($form->publishid) != intval($cm->instance)) {
            error("The article you're trying to delete is not part of this instance!",
                   $redirectonerror);
    }

    if ( has_capability('moodle/legacy:teacher',
         get_context_instance(CONTEXT_COURSE, $course->id)) ) {
        $canremove = true;
    }

    if (intval($form->userid) == intval($USER->id)) {
        $canremove = true;
    }

    if (!$canremove) {
        error("You can't remove other authors articles!", $redirectonerror);
    }

    if ($data = data_submitted()) {

        $errors = 0;

        $articleid = clean_param($form->id, PARAM_INT);

        // Delete corresponding locks
        if (! delete_records('netpublish_lock','pageid', $articleid) ) {
            $errors++;
        }

        // Delete corresponding status records

        if (! delete_records('netpublish_status_records', 'articleid', $articleid) ) {
            $errors++;
        }

        if ($errors < 1) {
            if (! delete_records('netpublish_articles', 'id', $articleid)) {
                error("Couldn't delete requested article!", $redirectonerror);
            }
        } else {
            error("Could not delete corresponding values! Can't delete article record!",
                  $redirectonerror);
        }

        $streditsuccess = get_string("deletesuccessful","netpublish", $data->title);
        redirect($CFG->wwwroot ."/mod/netpublish/view.php?id=$cm->id", $streditsuccess, 2);

    } else {

        $strnetpublishes  = get_string("modulenameplural","netpublish");
        $strnetpublish    = get_string("modulename","netpublish");
        $strdeletearticle = get_string("deletearticle","netpublish");

        if ($course->category) {
            $navigation = sprintf("<a href=\"../../course/view.php?id=%d\">%s</a> ->",
                                  $course->id, $course->shortname);
        }

        $navigation .= sprintf(" <a href=\"index.php?id=%d\">%s</a> -> ", $course->id, $strnetpublishes);
        $navigation .= sprintf("<a href=\"view.php?id=%d\">%s</a> -> %s", $cm->id, $mod->name, $strdeletearticle);

        $strdeletearticleconfirm = get_string("deletearticleconfirm","netpublish", $form->title);

        print_header("$course->shortname: $mod->name", "$course->fullname",
                    "$navigation",
                    "", "", true, "");
        print_heading($strdeletearticle);
        print_simple_box_start("center");
        if (!empty($form)) {
            include_once('delete.html');
        }
        print_simple_box_end();
        print_footer($course);
    }
?>
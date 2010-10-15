<?php // $Id: editarticle.php,v 1.3.4.1 2007/09/24 08:46:22 janne Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id        = required_param('id',           PARAM_INT); // module id
    $a         = optional_param('a',         0, PARAM_INT); // module id
    $article   = optional_param('article',   0, PARAM_INT); // article id GET method
    $articleid = optional_param('articleid', 0, PARAM_INT); // article id POST method

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

    $context  = get_context_instance(CONTEXT_MODULE, $cm->id);
    $toplevel = false;
    $publish  = false;
    $canedit  = false;

    if ( !has_capability('mod/netpublish:editarticle', $context) ) {
        error(get_string('errorpermissioneditarticle','netpublish'),
              sprintf("%s/mod/netpublish/view.php?id=%d", $CFG->wwwroot, $cm->id));
    }

    $getthis = !empty($articleid) ? $articleid : $article;

    $form   = get_record("netpublish_articles","id", $getthis);
    $rights = netpublish_get_rights($form->rights);

    if ( has_capability('moodle/legacy:editingteacher',
         get_context_instance(CONTEXT_COURSE, $course->id)) or
         has_capability('moodle/legacy:teacher',
         get_context_instance(CONTEXT_COURSE, $course->id)) ) {
        $canedit = true;
    }

    if (intval($form->userid) == intval($USER->id)) {
        $canedit = true;
    }

    if (!empty($rights) && !empty($rights[$USER->id]) && $nperm->can_write($rights[$USER->id])) {
        $canedit = true;
    }

    if (!$canedit) {
        $strerror = get_string('noeditpermissions', 'netpublish', $form->title);
        error($strerror, sprintf("%s/mod/netpublish/view.php?id=%d", $CFG->wwwroot, $cm->id));
    }

    if (!empty($mod->locktime)) {
        if($lock = netpublish_get_lock($form->id, $USER->id)) {
            $timenow = time();
            $lockend = intval($lock->lockstart + $mod->locktime);

            if ($lockend >= $timenow) {
                $strerror = get_string('articlelocked','netpublish', $lock->username);
                error($strerror, sprintf("%s/mod/netpublish/view.php?id=%d", $CFG->wwwroot, $cm->id));
            }
        }
    }

    if ($data = data_submitted()) {

        $skey = required_param('sesskey');

        if (!confirm_sesskey($skey)) {
            error("Session error!", sprintf("%s/mod/netpublish/view.php?id=%d", $CFG->wwwroot, $cm->id));
        }

        $data->id        = $articleid;
        $data->publishid = required_param('publishid', PARAM_INT);

        if (intval($data->publishid) != intval($cm->instance)) {
            error("You cannot edit article in other netpublish instance!",
                  sprintf("%s/mod/netpublish/view.php?id=%d", $CFG->wwwroot, $cm->id));
        }

        if ($data->statusid == 4 && empty($data->timepublished)) {
            $data->timepublished = time();
        }

        if (! empty($data->authors) ) {

            $authorids  = explode(",", $data->authors);
            $authorstmp = array();
            foreach ($authorids as $authorid) {
                $authorid = clean_param($authorid, PARAM_INT);
                array_push($authorstmp, $authorid);
            }
            $data->authors = implode(",", $authorstmp);
            unset($authorids, $authorstmp);

        }

        // Clean user input
        $data               = netpublish_clean_userinput($data);
        $data->timemodified = time();
        $data->rights       = !empty($data->rights) ? netpublish_set_rights($data->rights): '';

        $article = get_record('netpublish_articles', 'id', $articleid);

        if ($data->statusid == 4 and $article->statusid != 4) {
            // getting published, put it at the end of the list
            if ($lastarticle = netpublish_get_last_article($article->sectionid, $mod->id)) {
                $data->prevarticle = $lastarticle->id;
                if (!set_field('netpublish_articles', 'nextarticle', $articleid, 'id', $lastarticle->id)) {
                    error("Could not move article!");
                }
            } else {
                $data->prevarticle = 0;
            }
            $data->nextarticle = 0;
        } else if ($data->statusid != 4 and $article->statusid == 4) {
            // removing article from publish status
            // so remove it from the linked list
            if ($article->prevarticle !=0) {
                if (!set_field('netpublish_articles', 'nextarticle', $article->nextarticle, 'id', $article->prevarticle)) {
                    error("Could not move article!");
                }
            }
            if ($article->nextarticle !=0) {
                if (!set_field('netpublish_articles', 'prevarticle', $article->prevarticle, 'id', $article->nextarticle)) {
                    error("Could not move article!");
                }
            }
            $data->prevarticle = 0;
            $data->nextarticle = 0;
        }

        if (!update_record("netpublish_articles", $data)) {
            error("Couldn't update article $data->title", $CFG->wwwroot ."/mod/netpublish/view.php?id=$cm->id");
        }

        if (!empty($mod->locktime)) {
            netpublish_unset_lock ($data->id);
        }

        // Statuscounter stuff

        if ( !empty($mod->statuscount) ) {
            // Get current status
            $statcount = get_record("netpublish_status_records", "articleid", $data->id);

            if (! empty($statcount) ) {
                $mod->statuscount    = (int) $mod->statuscount;
                $statcount->counter  = (int) $statcount->counter;
                $statcount->statusid = (int) $statcount->statusid;

                if ( !has_capability('mod/netpublish:changestatus', $context) ) {

                    if ( $mod->statuscount > $statcount->counter ) {
                        $statcount->counter++;
                    } else if ( $mod->statuscount <= $statcount->counter ) {
                        $statcount->counter = 0;
                        if ($statcount->statusid > 3) {
                            $statcount->statusid = 5;
                        } else {
                            $statcount->statusid += 1;
                        }
                    }

                } else {
                    // If teacher edits, reset counter and set
                    // status as defined by teacher.
                    $statcount->counter  = 0;
                    $statcount->statusid = $data->statusid;

                }

                update_record('netpublish_status_records', $statcount);

            } else {

                // Add new record
                $statcount = new stdClass;
                $statcount->articleid = $data->id;
                $statcount->statusid  = $data->statusid;
                $statcount->counter   = (!$isteacher) ? 1 : 0;

                insert_record('netpublish_status_records', $statcount);

            }
        }

        $timemessage    = 2;
        $streditsuccess = get_string("editsuccessful","netpublish", stripslashes($data->title));
        redirect($CFG->wwwroot ."/mod/netpublish/view.php?id=$cm->id", $streditsuccess, $timemessage);

    } else {

        // Lock article for editing
        if (!empty($mod->locktime)) {
            if (! netpublish_set_lock($form->id, $USER->id, $form->publishid)) {
                $strerror = get_string('lockingfailed','netpublish', $form->title);
                error($strerror, $CFG->wwwroot .'/mod/netpublish/view.php?id=' . $cm->id);
            }
        }

        if ( has_capability('moodle/legacy:teacher',
             get_context_instance(CONTEXT_COURSE, $course->id)) ) {
            $toplevel = true;
            $publish  = true;
        }

        if (! empty($mod->statuscount) &&
            !has_capability('moodle/legacy:teacher',
            get_context_instance(CONTEXT_COURSE, $course->id)) ) {

            $statcount = get_record("netpublish_status_records","articleid", $getthis);

            if ( !empty($statcount) ) {
                $statcount->statusid = intval($statcount->statusid);
                $statcount->counter  = intval($statcount->counter);
                $mod->statuscount    = intval($mod->statuscount);

                if ($statcount->statusid < 4 && $statcount->counter < $mod->statuscount) {
                    $statcount->counter++;
                } else if ($statcount->statusid < 4 && $statcount->counter >= $mod->statuscount) {
                    $statcount->statusid++;
                    $statcount->counter = 0;
                }

                for ($i = 1; $i < $statcount->statusid; $i++) {
                    $exclude[] = $i;
                }

                $statusmenu = netpublish_print_status_list ("statusid", $publish, $form->statusid, '', true, $exclude);
            }

        }

        if (empty($statusmenu)) {

            $statusmenu = netpublish_print_status_list ("statusid", $publish, $form->statusid, '', true);

        }

        if ($course->category) {
            $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
        }

        $strnetpublishes = get_string("modulenameplural","netpublish");
        $strnetpublish   = get_string("modulename","netpublish");
        $streditarticle  = get_string("editarticle","netpublish");

        $navigation .= " <a href=\"index.php?id=$course->id\">$strnetpublishes</a> -> ";
        $navigation .= "<a href=\"view.php?id=$cm->id\">$mod->name</a> -> ";

        $form->authors = netpublish_construct_userids($form->authors, $form->userid, $USER->id);

        $students      = get_course_students($course->id, "u.firstname ASC, u.lastname ASC", "", 0, 99999,
                                         '', '', NULL, '', 'u.id,u.firstname,u.lastname');


        print_header("$course->shortname: $mod->name", "$course->fullname",
                    "$navigation $streditarticle",
                    "", "", true, "");
        print_heading($streditarticle);

        $usehtmleditor = can_use_html_editor();

        $courseid = clean_param($course->id, PARAM_INT);
        print_simple_box_start('center');
        include_once('editarticle.html');
        print_simple_box_end();

        if ($usehtmleditor) {
        ?>
<script type="text/javascript" src="nbdialog.js"></script>
<script type="text/javascript">
//<![CDATA[

function __insert_image (editor) {

    nbDialog("<?php echo "$CFG->wwwroot/mod/netpublish/insert_image.php?id=$course->id&sesskey=$USER->sesskey" ?>",774,550, function (param) {

        if (!param) {
            return false;
        }

        var sel   = editor._getSelection();
        var range = editor._createRange(sel);
        var img   = editor._doc.createElement("img");

        img.setAttribute("src",""+ param.f_url +"");
        img.setAttribute("width",""+ param.f_width +"");
        img.setAttribute("height",""+ param.f_height +"");
        img.setAttribute("alt",""+ param.f_alt +"");
        img.setAttribute("title",""+ param.f_title +"");
        img.setAttribute("align","" + param.f_align +"");

        if (HTMLArea.is_ie) {
            range.pasteHTML(img.outerHTML);
        } else {
            editor.insertNodeAtSelection(img);
        }
    });
}

    <?php
    // Since database module seem to be fu..... everything
    // this bull.... hack is necessary.
    $releaseinfo = explode(".", $CFG->release);
    $major = intval($releaseinfo[0]);
    $minor = preg_replace("/([^0-9])/", "", $releaseinfo[1]);
    $minor = intval($minor);
    $sub   = !empty($releaseinfo[2]) ? $releaseinfo[2] : 0;
    $plus = false;

    if ( preg_match("/\+$/", $sub) ) {
        $plus = true;
        $sub = preg_replace("/([^0-9])/","", $sub);
        $sub = intval($sub);
        if ( $plus ) {
            $sub += 0.1;
        }
    }
    $release = floatval($major . $minor . $sub);

    if ( $release > 153 ) {
        echo "\nvar config = new HTMLArea.Config();\n";
        print_editor_config();
    } else {
        print_editor_config();
    }
    ?>
// Register our custom buttons
config.registerButton("imagebank",  "<?php print_string("imagebank","netpublish");?>", "ed_image.gif", false, __insert_image);
config.toolbar.push(["imagebank"]);
HTMLArea.replaceAll(config);

//]]>
</script>
        <?php
        }
        print_footer($course);
    }
?>
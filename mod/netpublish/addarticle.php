<?php // $Id: addarticle.php,v 1.4.4.1 2007/09/24 08:46:22 janne Exp $

    // kom
    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id',   PARAM_INT);     // module id

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

    //$isteacher = isteacher($course->id);
    //$isstudent = isstudent($course->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $toplevel  = false;
    $publish   = false;

    if ( !has_capability('mod/netpublish:addarticle', $context) ) {
        error(get_string('errorpermissionaddarticle','netpublish'),
              $CFG->wwwroot .'/mod/netpublish/view.php?id='. $cm->id);
    }

    if ($form = data_submitted()) {

        $skey = required_param('sesskey');

        if (!confirm_sesskey($skey)) {
            error("Session error!",
                 sprintf("%s/mod/netpublish/view.php?id=%d", $CFG->wwwroot, $cm->id));
        }

        if (intval($form->publishid) != intval($cm->instance)) {
            error("You cannot add article in other netpublish instance!",
                  sprintf("%s/mod/netpublish/view.php?id=%d", $CFG->wwwroot, $cm->id));
        }

        $cm->id     = clean_param($form->id, PARAM_INT);
        $form->id   = '';

        // Check and clean user input
        $form = netpublish_clean_userinput($form);

        // Atleast there must be a title for this article
        if (strlen($form->title) < 3) {
            $err['missingtitle'] = get_string("missingtitle","netpublish");
        }

        $form->timecreated  = time();
        $form->timemodified = time();
        $form->rights       = !empty($form->rights) ? netpublish_set_rights($form->rights) : '';

        // add this article to the end of the linked list
        if ($form->statusid == 4 and $lastarticle = netpublish_get_last_article($form->sectionid, $mod->id)) {
            $form->prevarticle = $lastarticle->id;
        } else {
            $form->prevarticle = 0;
        }
        $form->nextarticle = 0;

        if ( has_capability('mod/netpublish:changestatus', $context) ) {

            // Test publish status if author is a teacher.
            if ($form->statusid == 4 && empty($form->timepublished)) {
                $form->timepublished = time();
            }

        }

        if (empty($err)) {
            if (!$id = insert_record("netpublish_articles", $form)) {
                error("Couldn't add new article $form->title", $CFG->wwwroot ."/mod/netpublish/view.php?id=$cm->id");
            }
            // update the linked list if needed
            if ($form->statusid == 4 and !empty($lastarticle)) {
                if (!set_field('netpublish_articles', 'nextarticle', $id, 'id', $lastarticle->id)) {
                    error("Could not move article!");
                }
            }

            // Add statuscount if in use. Also if teacher
            // adds a new article do not add status record.
            if (!empty($mod->statuscount) && !$isteacher ) {
                $statuscount = new stdClass;
                $statuscount->articleid = clean_param($id, PARAM_INT);
                $statuscount->statusid  = clean_param($form->statusid, PARAM_INT);
                $statuscount->counter   = 0;

                insert_record("netpublish_status_records", $statuscount);

            }

            $addmessage  = get_string("addsuccessful","netpublish", stripslashes($form->title));
            redirect($CFG->wwwroot ."/mod/netpublish/view.php?id=$cm->id", $addmessage, 2);

        }

    }

    // Print add article form

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strnetpublishes = get_string("modulenameplural","netpublish");
    $strnetpublish   = get_string("modulename","netpublish");
    $straddnew       = get_string("addnewarticle","netpublish");

    $usehtmleditor = can_use_html_editor();

    print_header("$course->shortname: $mod->name", "$course->fullname",
                "$navigation <a href=index.php?id=$course->id>$strnetpublishes</a> -> <a href=\"view.php?id=$cm->id\">$mod->name</a> -> $straddnew",
                "", "", true, "");
    print_heading($straddnew);

    if (has_capability('moodle/legacy:teacher',
        get_context_instance(CONTEXT_COURSE, $course->id)) ) {
        $toplevel = true;
        $publish  = true;
    }

    $statusmenu = netpublish_print_status_list ("statusid", $publish, 1, '', true);

    // Get students of this course
    $students = get_course_students($course->id, "u.firstname ASC, u.lastname ASC", "", 0, 99999,
                                         '', '', NULL, '', 'u.id,u.firstname,u.lastname');
    $form->publishid = $mod->id;
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

    if (isset($CFG->debug) and $CFG->debug > 7) {
        echo '<div style="text-align: center;">';
        echo 'Release: '. $release;
        echo ($plus) ? ' is plus version' : '';
        echo '</div>';
    }

    print_footer($course);
?>
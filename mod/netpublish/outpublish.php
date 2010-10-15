<?php // $Id: outpublish.php,v 1.3 2007/07/18 21:45:45 janne Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id   = required_param('id', PARAM_INT); // module id
    $skey = required_param('sesskey');        // Session key

    if ($id) {
        // Get necessary info from database
        if (! $info = netpublish_get_record($id) ) {
            error("Course Module ID was incorrect");
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

    if (!file_exists($CFG->dirroot .'/netpublish/view.php')) {
        error("Can't publish publication <strong>$mod->name</strong>
               outside of Moodle! Required plugin missing!",
              $CFG->wwwroot .'/mod/netpublish/view.php?id='. $cm->id);
    }

    require_login($course->id);

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if ( !has_capability('mod/netpublish:outpublish', $context) ) {
        error(get_string('errorpermissionoutpublish','netpublish',
              $CFG->wwwroot .'/mod/netpublish/view.php?id=' . $cm->id));
    }

    if (!confirm_sesskey($skey)) {
        error("Session key error!",
              $CFG->wwwroot .'/mod/netpublish/view.php?id=' . $cm->id);
    }

    if (data_submitted()) {
        $publishmeid = required_param('instance', PARAM_INT);

        if (empty($publishmeid)) {
            error("Required variable missing!");
        }

        if (empty($mod->published)) {
            // publish
            if (! set_field("netpublish", "published", 1, "id", $publishmeid)) {
                error("Set field error! $mod->name not published outside!",
                      $CFG->wwwroot .'/mod/netpublish/view.php?id='. $cm->id);
            }
        } else {
            // unpublish
            if (! set_field("netpublish", "published", 0, "id", $publishmeid)) {
                error("Set field error! $mod->name be unpublished!",
                      $CFG->wwwroot .'/mod/netpublish/view.php?id='. $cm->id);
            }
        }

        $strsuccess = empty($mod->published) ? get_string("publishedsuccessfully","netpublish", $mod->name):
                                               get_string("unpublishedsuccessfully","netpublish", $mod->name);
        redirect($CFG->wwwroot .'/mod/netpublish/view.php?id='. $cm->id, $strsuccess, 3);

    } else {
        // Ask confirmation!
        /// Print the page header

        if ($course->category) {
            $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
        }

        // Get strings
        $strnetpublishes = get_string("modulenameplural", "netpublish");
        $strnetpublish   = get_string("modulename", "netpublish");
        $strareyousure   = !empty($mod->published) ? get_string("doyouwishtounpublish","netpublish", $mod->name):
                                                     get_string("doyouwishtopublish","netpublish", $mod->name);

        $strpublishstate = !empty($mod->published) ? get_string('outunpublish','netpublish') :
                                                    get_string('outpublish','netpublish');

        $navigation .= "<a href=\"index.php?id=$course->id\">$strnetpublishes</a> -> ";
        $navigation .= "<a href=\"view.php?id=$cm->id\">$mod->name</a> ->";

        print_header("$course->shortname: $mod->name", "$course->fullname",
                    "$navigation $strpublishstate",
                    "", "", true);

        print_simple_box_start("center");
        print_heading($strpublishstate, "center");
        ?>
        <form method="post" action="<?php print(basename(__FILE__)); ?>">
        <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
        <input type="hidden" name="sesskey" value="<?php p($USER->sesskey) ?>" />
        <input type="hidden" name="instance" value="<?php p($cm->instance) ?>" />
        <div align="center"><?php echo $strareyousure ?></div>
        <br />
        <div align="center"><input type="submit" value="<?php print_string("yes"); ?>" />
        <input type="button" value="<?php print_string("no"); ?>" onclick="javascript: void(history.go(-1))" /></div>
        <br />
        </form>
        <?php
        print_simple_box_end();
        print_footer($course);
    }

?>
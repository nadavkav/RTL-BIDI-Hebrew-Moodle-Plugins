<?php // $Id: confirm.php,v 1.2.4.1 2007/09/24 08:46:22 janne Exp $
// Holds a routines for several confirmation tasks for netpublish
// All parameters are extracted from base64_encoded string that
// holds an array of parameters
// Required params
// $<arrayname>['id']
// $<arrayname>['courseid']
//

    require_once('../../config.php');
    require_once('lib.php');

    if (!empty($_SERVER['QUERY_STRING'])) {
        $options = $_SERVER['QUERY_STRING'];
    } else if (!empty($HTTP_SERVER_VARS['QUERY_STRING'])) {
        $options = $HTTP_SERVER_VARS['QUERY_STRING'];
    } else {
        if (!empty($_SERVER['REQUEST_URI'])) {
            list($key, $value) = split("\?", $_SERVER['REQUEST_URI']);
            $options = $value;
        } else if (!empty($HTTP_SERVER_VARS['REQUEST_URI'])) {
            list($key, $value) = split('?', $_SERVER['REQUEST_URI']);
            $options = $value;
        } else {
            $options = '';
        }
   }

   if (empty($options)) {
       error("Undefined querystring error! Can't continue!");
   }

   $options = unserialize(base64_decode($options));

   $id       = clean_param($options['id'],     PARAM_INT);
   $action   = clean_param($options['action'], PARAM_ACTION);
   $courseid = clean_param($options['course'], PARAM_INT);

   require_login($courseid);

   if ($id) {
        // Get all that I need using only one query
        if (! $info = netpublish_get_record($id) ) {
            error("Course Module ID was incorrect");
        }
    }

    // Construct objects used in Moodle
    netpublish_set_std_classes ($cm, $course, $mod, $info);
    unset($info);

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    switch ($action) {
        case "removesection":

            if ( has_capability('mod/netpublish:deletesection', $context) ) {
                $parentid  = clean_param($options['pid'], PARAM_INT);
                $publishid = clean_param($options['publishid'], PARAM_INT);
                if (empty($parentid)) {
                    error("Required variable missing!");
                }

                $strpublish   = get_string("modulename","netpublish");
                $strpublishes = get_string("modulenameplural","netpublish");
                $straddnewordelete = get_string("addnewordelete","netpublish");
                $strconfirm   = get_string("confirmsectiondelete","netpublish");
                $strmovearticles = get_string("moveralatedarticles","netpublish");
                $strdeletearticles = get_string("deleterelatedarticles","netpublish");

                if ($course->category) {
                    $navigation = sprintf("<a href=\"../../course/view.php?id=%d\">%s</a> ->",
                                          $course->id, $course->shortname);
                }

                $navigation .= sprintf(" <a href=\"index.php?id=%d\">%s</a> -> ",
                                       $course->id, $strpublishes);
                $navigation .= sprintf("<a href=\"view.php?id=%d\">%s</a> -> ",
                                       $cm->id, $mod->name);


                print_header("$course->shortname: $mod->name", "$course->fullname",
                             "$navigation $straddnewordelete",
                             "", "", true, update_module_button($cm->id, $course->id, $strpublish));
                print_simple_box_start("center");

                echo '<p style="text-align: center">'. $strconfirm .'</p>' . "\n";
                echo '<form method="post" action="sections.php">' . "\n";
                echo '<input type="hidden" name="id" value="'. $cm->id .'" />' . "\n";
                echo '<input type="hidden" name="publishid" value="'. $publishid .'" />'. "\n";
                echo '<input type="hidden" name="sesskey" value="'. $USER->sesskey .'" />' . "\n";
                echo '<input type="hidden" name="parentid" value="'. $parentid .'" />' . "\n";
                echo '<input type="hidden" name="remove" value="on" />'. "\n";
                echo '<p>';
                echo '<input type="checkbox" name="movearticles" checked="true" /> '. $strmovearticles;
                netpublish_print_section_list($cm->instance, "newsection");
                echo '<br />'. "\n";
                echo '</p>';
                echo '<p style="text-align: center">';
                echo '<input type="submit" name="confirmed" value="'. get_string('yes') . '" />' . "\n";
                echo '<input type="submit" name="cancelled" value="'. get_string('cancel') .'" />' . "\n";
                echo '</p>' . "\n";
                echo '</form>' . "\n";
                print_simple_box_end();
                $excluded = netpublish_get_excluded_sections ($parentid, $publishid);
                ?>
                <script type="text/javascript">
                //<![CDATA[
                var excluded = new Array('<?php print(implode("','", $excluded)); ?>');
                var elem = document.getElementById('newsection');
                var choices = elem.options;
                var rem = new Array();
                for (var i = 0; i < choices.length; i++) {
                    var id = choices[i].value;

                    for (j = 0; j < excluded.length; j++) {
                        if (id == excluded[j]) {
                            rem.push(i);
                        }
                    }
                }

                for (i = rem.length - 1; i >= 0; --i) {
                    elem.removeChild(elem.options[rem[i]]);
                }
                //]]>
                </script>
                <?php
                print_footer($course);
            } else {
                error(get_string('errorpermissiondeletesection','netpublish'),
                      $CFG->wwwroot .'/mod/netpublish/view.php?id='. $cm->id);
            }

            break;
        default:
            $theurl = !empty($_SERVER['HTTP_REFERER']) ?
                      $_SERVER['HTTP_REFERER'] :
                      $CFG->wwwroot .'/mod/netpublish/view.php?id='. $cm->id;
            redirect($theurl);
    }

?>
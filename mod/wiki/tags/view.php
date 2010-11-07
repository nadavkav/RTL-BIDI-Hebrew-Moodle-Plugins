<?php

/**
 * This page prints the wiki pages related to certain wikitag
 *
 * @author Gonzalo Serrano
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version $Id: view.php,v 1.2 2008/05/26 18:40:16 gonzaloserrano Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

    require_once('../../../config.php');
    //require_once($CFG->dirroot.'/mod/wiki/weblib.php');
    //require_once($CFG->dirroot.'/lib/weblib.php');
    //require_once($CFG->dirroot.'/tag/lib.php');
    //require_once($CFG->dirroot.'/tag/pagelib.php');
    require_once('tags.lib.php');
    //require_once($CFG->dirroot.'/mod/wiki/wikitags/wikitags.lib.php');

    global $CFG;

    if (empty($CFG->usetags)) {
            print_error('tagsaredisabled', 'tag');
    }

    $cmid  = required_param('cmid', PARAM_INT); // Course Module ID
    $cid   = required_param('cid',  PARAM_INT); // Course Id

    require_login($cid);

    if (! $course = get_record('course', 'id', $cid)) {
        error("$cid Course ID is incorrect");
    }

    $tagid = optional_param('tagid', 0, PARAM_INT);   // tag id

    if ($tagid) {
        $tag = tag_get('id', $tagid, '*');
    } else
        redirect($CFG->wwwroot.'/tag/search.php');

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    $navlinks   = array();
    $navlinks[] = array('name' => get_string('tags'),  'link' => $CFG->wwwroot.'/tag/search.php', 'type' => 'misc');
    $navlinks[] = array('name' => format_string(tag_display_name($tag)), 'link' => $CFG->wwwroot.
                        '/mod/wiki/tags/view.php?;amp;cid='.$cid.'&amp;tagid='.$tagid, 'type' => 'misc');

    $navigation = build_navigation($navlinks);
    $title = get_string('tag', 'tag') .' - '. tag_display_name($tag);

    print_header($title, $course->fullname, $navigation);

    $modcontext = get_context_instance(CONTEXT_MODULE, $cmid);
    if (has_capability('moodle/tag:manage', $modcontext)) {
            //echo '<div class="managelink"><a href="'.$CFG->wwwroot.
            echo '<div style="text-align:right; padding:10px;"><a href="'.$CFG->wwwroot.
                 '/tag/manage.php">'. get_string('managetags', 'tag').
                 '</a></div>';
    }


/// Print the page content

    echo('<br/>');
    print_heading(tag_display_name($tag), '', 2, 'headingblock header tag-heading');
    tag_print_management_box($tag);
    tag_print_description_box($tag);
    wiki_tags_print_wikipages($tag);

//// Print the page footer
    print_footer($course);


?>

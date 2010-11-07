<?php

/**
 * HTML Export
 * this file contains the wiki export html frontend.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: exporthtml.php,v 1.4 2007/06/15 11:43:18 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package HTML_export
 */
 
 
    require_once("../../../config.php");
    require_once("../lib.php");
    require_once("exporthtmllib.php");

	global $WS;

	$id = optional_param('id',NULL,PARAM_INT);    // Course Module ID

    if (! $WS->cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $WS->cm->course)) {
        error("Course is misconfigured");
    }

    if (! $WS->dfwiki = get_record("wiki", "id", $WS->cm->instance)) {
        error("Course module is incorrect");
    }

    require_login($course->id);

	$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);
	require_capability('mod/wiki:adminactions',$context);

	global $COURSE;

    if ($COURSE->category) {
        $navigation = "<a href=\"../../../course/view.php?id=$COURSE->id\">$COURSE->shortname</a> ->";
    }

    $strdfwikis = get_string("modulenameplural", "wiki");// update translation file name (nadavkav patch)
    $strdfwiki  = get_string("modulename", "wiki");// update translation file name (nadavkav patch)

    print_header("$COURSE->shortname: {$WS->dfwiki->name}", "$COURSE->fullname",
                 "$navigation <a href=../index.php?id=$COURSE->id>$strdfwikis</a> -> {$WS->dfwiki->name}");

    print_heading(get_string("exportinghtml","wiki")); // update translation file name (nadavkav patch)

    $prop = null;
    $prop->class = "box generalbox generalboxcontent boxaligncenter";
    wiki_div_start($prop);

    wiki_export_html($WS);

    wiki_div_end();

    //Print footer
    print_footer();

?>

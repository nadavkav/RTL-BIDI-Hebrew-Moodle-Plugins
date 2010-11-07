<?php

/**
 * XML Backups
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: exportxml.php,v 1.9 2008/01/23 09:14:53 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package XML_backups
 */
 
 
//Created by Antonio Casta�o & Juan Casta�o

    require_once("../../../config.php");
    require_once("../lib.php");
    require_once("exportxmllib.php");
	require_once("../class/wikistorage.class.php");

    global $WS;

	$id = optional_param('id',NULL,PARAM_INT);    // Course Module ID
    $WS->path = optional_param('path',NULL,PARAM_CLEAN);
    $file = optional_param('file',NULL,PARAM_PATH);
    $WS->type = optional_param('type',NULL,PARAM_ALPHA);

    if (! $WS->cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $WS->cm->course)) {
        error("Course is misconfigured");
    }

    if (! $WS->dfwiki = get_record('wiki', "id", $WS->cm->instance)) {
        error("Course module is incorrect");
    }

    require_login($course->id);

	$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);
	require_capability('mod/wiki:adminactions',$context);

	global $COURSE;

	$cancel = optional_param('dfformcancel',NULL,PARAM_ALPHA);
    if (isset($cancel)){
        redirect('../view.php?id='.$WS->cm->id);
    }

    if ($COURSE->category) {
        $navigation = "<a href=\"../../../course/view.php?id=$COURSE->id\">$COURSE->shortname</a> ->";
    }

    $strdfwikis = get_string("modulenameplural", 'wiki');
    $strdfwiki  = get_string("modulename", 'wiki');

    print_header("$COURSE->shortname: {$WS->dfwiki->name}", "$COURSE->fullname",
                 "$navigation <a href=\"../index.php?id=$COURSE->id\">$strdfwikis</a> -> {$WS->dfwiki->name}");

    $WS->pageaction = optional_param('pageaction',NULL,PARAM_ALPHA);

    if ($WS->pageaction == 'exportxml') print_heading(get_string("exporting",'wiki'));
    else print_heading(get_string("importing",'wiki'));

    $prop = null;
    $prop->class = 'box generalbox generalboxcontent boxaligncenter';
    wiki_div_start($prop);

    switch ($WS->pageaction){
    	case 'exportxml':
    		wiki_export_content($WS);
    		break;
        case 'importxml':
    		wiki_import_content($WS);
    		break;
        case 'importewiki':
            //if($WS->type == 'wiki') wiki_import_wiki($WS);
            //else if($WS->type == 'dfwiki') wiki_import_dfwiki($WS);
            wiki_import_wiki($WS);
    		break;
    	default:
    		error ('Exportxml.php: $WS->pageaction not properly configured');
    		break;
    }

    wiki_div_end();

    //Print footer
    print_footer();

?>

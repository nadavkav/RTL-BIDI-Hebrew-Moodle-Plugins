<?php // $Id: tabs_edit.php,v 1.3 2007/04/27 09:10:51 janne Exp $
/**
* Tabs for administration view of logi.
* @author Janne Mikkonen
* @package logi
*/

    $currenttab  = NULL;
    $inactive    = NULL;
    $activetwo   = NULL;
    $inactive = array();
    $pagename = basename($_SERVER['SCRIPT_NAME'], '.php');

    switch ($pagename) {
        case 'sections':
            $currenttab = 'sections';
            break;
        case 'addarticle':
            $currenttab = 'addarticle';
            break;
        case 'drafts':
            $currenttab = 'drafts';
            break;
        case 'grades':
            $currenttab = 'grades';
            break;
        case 'outpublish':
            $currenttab = 'outputblish';
            break;
        default:
    }

    /*if (empty($currenttab)) {
        error('You cannot call this script in that way');
    }*/

    $baseurl = $CFG->wwwroot .'/mod/netpublish/';

    $sectionsurl   = $baseurl .'sections.php?id='. $cm->id;
    $addarticleurl = $baseurl .'addarticle.php?id='. $cm->id;
    $draftsurl     = $baseurl .'drafts.php?id='. $cm->id;
    $gradesurl     = $baseurl .'grades.php?id='. $cm->id .'&amp;sesskey='. $USER->sesskey;
    $outpublishurl = $baseurl .'outpublish.php?id='. $cm->id .'&amp;sesskey='. $USER->sesskey;

    $tabrow   = array();
    // new tabobject(name, url,  translationstring);
    if ( has_capability('mod/netpublish:editsection', $context) ) {
        $tabrow[] = new tabobject('sections', $sectionsurl, get_string('managesections','netpublish'));
    }

    if ( has_capability('mod/netpublish:addarticle', $context) ) {
        $tabrow[] = new tabobject('addarticle', $addarticleurl, get_string("addnewarticle","netpublish"));
    }

    $tabrow[] = new tabobject('drafts', $draftsurl, get_string("pendingarticles","netpublish"));

    if ( !empty($mod->scale) && has_capability('mod/netpublish:viewownrating') ) {
        $tabrow[] = new tabobject('grades', $gradesurl, get_string('grades'));
    }

    if (has_capability('mod/netpublish:outpublish', $context) and $canbepublished) {
        $tabrow[] = new tabobject('outpublish', $outpublishurl, get_string('outpublish','netpublish'));
    }

    $tabs = array($tabrow);
    print_tabs($tabs, $currenttab, $inactive);

?>
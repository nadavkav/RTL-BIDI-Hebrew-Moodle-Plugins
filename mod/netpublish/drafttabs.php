<?php // $Id: drafttabs.php,v 1.2 2007/04/27 09:10:51 janne Exp $

// Print out nicely formatted tabs in drafts page.

    if (! defined('MOODLE_INTERNAL') ) die("This page cannot be viewed independently");

    $currenttab = NULL;
    $inactive   = NULL;

    switch ($tab) {
        case 1:
            $currenttab = 'myarticles';
            $inactive   = array();
        break;
        case 2;
            $currenttab = 'otherarticles';
            $inactive   = array();
        break;
        default:
            $currenttab = 'myarticles';
            $inactive = array();
    }

    if (empty($currenttab)) {
        error('You cannot call this script in that way');
    }

    $baseurl = sprintf("%s/mod/netpublish/drafts.php?id=%d&amp;sesskey=%s",
                       $CFG->wwwroot,
                       $cm->id,
                       $USER->sesskey);

    $strmyarticles    = get_string('myarticles','netpublish');
    $strotherarticles = get_string('otherarticles','netpublish');
    $toprow   = array();
    // new tabobject(name, url,  translationstring);
    $toprow[] = new tabobject('myarticles', $baseurl . '&amp;tab=1', $strmyarticles);
    $toprow[] = new tabobject('otherarticles', $baseurl . '&amp;tab=2', $strotherarticles);

    $tabs = array($toprow);

    print_tabs($tabs, $currenttab, $inactive);

?>
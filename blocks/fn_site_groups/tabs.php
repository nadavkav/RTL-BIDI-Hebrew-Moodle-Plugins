<?php  // $Id: tabs.php,v 1.1 2009/06/22 21:30:52 mchurch Exp $
/// This file to be included so we can assume config.php has already been included.
/// We also assume that $user, $course, $currenttab have been set

    if (empty($currenttab) or empty($course)) {
        //error('You cannot call this script in that way');
    }

    if (empty($CFG->block_fn_site_groups_enabled)) {
        return;
    }

    $inactive = NULL;
    $toprow = array();

    $baseurl = $CFG->wwwroot.'/blocks/fn_site_groups/sitegroups.php?id='.$id.'&amp;courseid='.$course->id;
    if (!empty($sgenrolment)) {
        $toprow[] = new tabobject('enrol', $baseurl.'&amp;action=enrol', get_string('courseenrolment','block_fn_site_groups'));
    }

    $toprow[] = new tabobject('groups', $baseurl.'&amp;action=groups', get_string('groups','block_fn_site_groups'));

    $toprow[] = new tabobject('users', $baseurl.'&amp;action=users', get_string('users','block_fn_site_groups'));
    $tabs = array($toprow);

    print_tabs($tabs, $currenttab, $inactive);

?>

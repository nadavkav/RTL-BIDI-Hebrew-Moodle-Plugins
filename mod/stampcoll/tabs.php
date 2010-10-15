<?php  // $Id: tabs.php,v 1.2 2008/05/13 19:41:54 mudrd8mz Exp $

/**
 * Prints navigation tabs in the Stamp Collection module
 * 
 * @uses Variables $cap_* defined in {@link mod/stampcoll/caps.php}
 * @author David Mudrak
 * @package mod/stampcoll
 */

    if (empty($currenttab) or empty($stampcoll) or empty($context) or empty($cm)) {
        die('You cannot call this script in that way');
    }
    $inactive = NULL;
    $activetwo = NULL;
    $tabs = array();
    $row = array();

    if ($cap_viewotherstamps) {
        $row[] = new tabobject('view', $CFG->wwwroot.'/mod/stampcoll/view.php?id='.$cm->id,
                                                    get_string('viewstamps','stampcoll'));
    }
    if ($cap_viewownstamps) {
        $row[] = new tabobject('viewown', $CFG->wwwroot.'/mod/stampcoll/view.php?view=own&amp;id='.$cm->id,
                                                    get_string('ownstamps','stampcoll'));
    }
    if ($cap_editstamps) {
        $row[] = new tabobject('edit', $CFG->wwwroot.'/mod/stampcoll/editstamps.php?id='.$cm->id,
                                                    get_string('editstamps', 'stampcoll'));
    }

    $tabs[] = $row;
/// Print out the tabs and continue!
    print_tabs($tabs, $currenttab, $inactive, $activetwo);
?>

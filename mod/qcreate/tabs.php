<?php  // $Id: tabs.php,v 1.8 2008/12/01 13:21:24 jamiesensei Exp $
/**
 * Sets up the tabs used by the quiz pages based on the users capabilites.
 *
 * @author Tim Hunt and others.
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package quiz
 */

if (empty($qcreate)) {
    error('You cannot call this script in that way');
}
if (!isset($currenttab)) {
    $currenttab = '';
}

$tabs = array();

$row  = array();

$row[] = new tabobject('overview', "$CFG->wwwroot/mod/qcreate/overview.php?".$thispageurl->get_query_string(),
                        get_string('overview', 'qcreate'), get_string('overview', 'qcreate'));
if ($contexts->have_one_edit_tab_cap('questions')) {
    $row[] = new tabobject('editq', "$CFG->wwwroot/mod/qcreate/edit.php?".$thispageurl->get_query_string(),
                            get_string('grading', 'qcreate'), get_string('gradequestions', 'qcreate'));
}
questionbank_navigation_tabs($row, $contexts, $thispageurl->get_query_string());
foreach ($row as $key => $tab){
    if ($tab->id == 'export'){
        unset($row[$key]);
    }
}
if ($contexts->have_one_edit_tab_cap('export')) {
    $row[] = new tabobject('exportgood', "$CFG->wwwroot/mod/qcreate/exportgood.php?".$thispageurl->get_query_string(),
                            get_string('export', 'quiz'), get_string('exportgoodquestions', 'qcreate'));
}
$tabs[] = $row;

print_tabs($tabs, $mode);

?>

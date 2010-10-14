<?php // $Id: tabs.php,v 1.2 2007/03/02 03:06:53 mark-nielsen Exp $
/**
 * Tabs for Quickmail
 *
 * @author Mark Nielsen
 * @version $Id: tabs.php,v 1.2 2007/03/02 03:06:53 mark-nielsen Exp $
 * @package quickmail
 **/

    if (empty($course)) {
        error('Programmer error: cannot call this script without $course set');
    }
    if (!isset($instanceid)) {
        $instanceid = 0;
    }
    if (empty($currenttab)) {
        $currenttab = 'compose';
    }

    $rows = array();
    $row = array();

    $row[] = new tabobject('compose', "$CFG->wwwroot/blocks/quickmail/email.php?id=$course->id&amp;instanceid=$instanceid", get_string('compose', 'block_quickmail'));
    $row[] = new tabobject('history', "$CFG->wwwroot/blocks/quickmail/emaillog.php?id=$course->id&amp;instanceid=$instanceid", get_string('history', 'block_quickmail'));
    $rows[] = $row;

    print_tabs($rows, $currenttab);
?>
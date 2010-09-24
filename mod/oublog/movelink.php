<?php
/**
 * This page allows a user to change a links position in the list
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */

    require_once("../../config.php");
    require_once("locallib.php");

    $link = required_param('link', PARAM_INT);
    $down = required_param('down', PARAM_INT);
    $returnurl = required_param('returnurl', PARAM_RAW);

    if (!$link = get_record('oublog_links', 'id', $link)) {
        error('Link not found');
    }
    if (!$oublog = get_record("oublog", "id", $link->oublogid)) {
        error('Blog parameter is incorrect');
    }
    if (!$cm = get_coursemodule_from_instance('oublog', $link->oublogid)) {
        error('Course module ID was incorrect');
    }
    if (!confirm_sesskey()) {
        error('Bad Session Key');
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    
    $oubloginstance = $link->oubloginstancesid ? get_record('oublog_instances', 'id', $link->oubloginstancesid) : null;
    oublog_require_userblog_permission('mod/oublog:managelinks', $oublog,$oubloginstance,$context);

    if ($oublog->global) {
        $where = "oubloginstancesid = {$link->oubloginstancesid} ";
    } else {
        $where = "oublogid = {$link->oublogid} ";
    }

    // Get the max sort order
    $maxsortorder = get_field_sql("SELECT MAX(sortorder) FROM {$CFG->prefix}oublog_links WHERE $where");


    if ($down == 1) { // Move link down
        if ($link->sortorder != $maxsortorder) {
            $sql = "UPDATE {$CFG->prefix}oublog_links SET sortorder = ".$link->sortorder."
                    WHERE $where AND sortorder = ".($link->sortorder+1);

            execute_sql($sql, false);

            $sql = "UPDATE {$CFG->prefix}oublog_links SET sortorder = ".($link->sortorder+1)."
                    WHERE id = {$link->id} ";

            execute_sql($sql, false);
        }
    } else { // Move link up
        if ($link->sortorder != 1) {
            $sql = "UPDATE {$CFG->prefix}oublog_links SET sortorder = ".$link->sortorder."
                    WHERE $where AND sortorder = ".($link->sortorder-1);

            execute_sql($sql, false);

            $sql = "UPDATE {$CFG->prefix}oublog_links SET sortorder = ".($link->sortorder-1)."
                    WHERE id = {$link->id} ";

            execute_sql($sql, false);
        }
    }

    redirect($returnurl);
?>
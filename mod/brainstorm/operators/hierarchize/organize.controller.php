<?php

/**
* Module Brainstorm V2
* Operator : locate
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");
include_once("$CFG->dirroot/mod/brainstorm/treelib.php");

/********************************** make tree from scratch ********************************/
// take all the response in your own group
if ($action == 'maketree'){
    // first delete all old location data - the fastest way to do it
    if (!delete_records('brainstorm_operatordata', 'brainstormid', $brainstorm->id, 'userid', $USER->id, 'operatorid', 'hierarchize')){
        error("Could not delete order records");
    }
    
    $responses = brainstorm_get_responses($brainstorm->id, 0, $currentgroup, false);
    if ($responses){
        $treerecord->brainstormid = $brainstorm->id;
        $treerecord->userid = $USER->id;
        $treerecord->groupid = $currentgroup;
        $treerecord->operatorid = 'hierarchize';
        $treerecord->itemdest = 0;
        $treerecord->intvalue = 1;
        $treerecord->timemodified = time();
        foreach($responses as $response){
            $treerecord->itemsource = $response->id;
            if (!insert_record('brainstorm_operatordata', $treerecord)){
                error("Could not insert tree record");
            }
            $treerecord->intvalue++;
        }
    }
}
/********************************** reset tree data for your own ********************************/
if ($action == 'clearall'){
    // first delete all old location data - the fastest way to do it
    if (!delete_records('brainstorm_operatordata', 'brainstormid', $brainstorm->id, 'userid', $USER->id, 'operatorid', 'hierarchize')){
        error("Could not delete order records");
    }
}
if ($action == 'up'){
    $itemid = required_param('item', PARAM_INT);
    brainstorm_tree_up($brainstorm->id, $USER->id, $currentgroup, $itemid, 1);
}
if ($action == 'down'){
    $itemid = required_param('item', PARAM_INT);
    brainstorm_tree_down($brainstorm->id, $USER->id, $currentgroup, $itemid, 1);
}
if ($action == 'left'){
    $itemid = required_param('item', PARAM_INT);
    brainstorm_tree_left($brainstorm->id, $USER->id, $currentgroup, $itemid);
}
if ($action == 'right'){
    $itemid = required_param('item', PARAM_INT);
    brainstorm_tree_right($brainstorm->id, $USER->id, $currentgroup, $itemid);
}
?>
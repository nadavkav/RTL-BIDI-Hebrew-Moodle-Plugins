<?php

/**
* Module Brainstorm V2
* Operator : locate
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");

/********************************** Saves locations ********************************/
if ($action == 'savelocations'){
    // first delete all old location data - the fastest way to do it
    if (!delete_records('brainstorm_operatordata', 'brainstormid', $brainstorm->id, 'userid', $USER->id, 'operatorid', 'locate')){
        error("Could not delete records");
    }

    $keys = preg_grep("/^xquantifier_/", array_keys($_POST));
    $current_operator = new BrainstormOperator($brainstorm->id, $page);
    foreach($keys as $key){        
        preg_match("/^xquantifier_(.*)/", $key, $matches);
        $locaterecord->itemsource = $matches[1];
        $locaterecord->brainstormid = $brainstorm->id;
        $locaterecord->operatorid = $page;
        $locaterecord->userid = $USER->id;
        $locaterecord->groupid = $currentgroup;
        switch ($current_operator->configdata->quantifiertype){
            case 'integer' :
                // will floor numbers
                $quantifiers->x = (int)required_param('xquantifier_'.$locaterecord->itemsource, PARAM_INT);
                $quantifiers->y = (int)required_param('yquantifier_'.$locaterecord->itemsource, PARAM_INT);
                // avoid negative numbers
                $quantifiers->x = max(0, $quantifiers->x);
                $quantifiers->y = max(0, $quantifiers->y);
                break;
            case 'float' :
                $quantifiers->x = (double)required_param('xquantifier_'.$locaterecord->itemsource, PARAM_NUMBER);
                $quantifiers->y = (double)required_param('yquantifier_'.$locaterecord->itemsource, PARAM_NUMBER); 
                break;
            default :
                $quantifiers->x = (string)required_param('xquantifier_'.$locaterecord->itemsource, PARAM_TEXT);
                $quantifiers->y = (string)required_param('yquantifier_'.$locaterecord->itemsource, PARAM_TEXT); 
        }
        $quantifiers->x = min($quantifiers->x, $current_operator->configdata->xmaxrange);
        $quantifiers->x = max($quantifiers->x, $current_operator->configdata->xminrange);
        $quantifiers->y = min($quantifiers->y, $current_operator->configdata->ymaxrange);
        $quantifiers->y = max($quantifiers->y, $current_operator->configdata->yminrange);
        $locaterecord->blobvalue = serialize($quantifiers);
        $locaterecord->timemodified = time();
        if (!insert_record('brainstorm_operatordata', $locaterecord)){
            error("Could not create location record");
        }
    }
}
?>
<?php

/**
* Module Brainstorm V2
* Operator : map
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/

include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");

/********************************** Saves locations ********************************/
if ($action == 'savemappings'){
    // first delete all old location data - the fastest way to do it
    if (!delete_records('brainstorm_operatordata', 'brainstormid', $brainstorm->id, 'userid', $USER->id, 'operatorid', 'map')){
        error("Could not delete records");
    }

    $keys = preg_grep("/^map_/", array_keys($_POST));
    $current_operator = new BrainstormOperator($brainstorm->id, $page);
    foreach($keys as $key){        
        preg_match("/^map_(.*)_(.*)/", $key, $matches);
        unset($maprecord);
        $maprecord->itemsource = $matches[1];
        $maprecord->itemdest = $matches[2];
        $maprecord->brainstormid = $brainstorm->id;
        $maprecord->operatorid = $page;
        $maprecord->userid = $USER->id;
        $maprecord->groupid = $currentgroup;
        if (!@$current_operator->configdata->quantified){
            $maprecord->intvalue = 1;
        }
        else{
            switch ($current_operator->configdata->quantifiertype){
                case 'integer' :
                    $input = required_param($key, PARAM_TEXT);
                    if (!empty($input))
                        $maprecord->intvalue = (int)$input;
                    break;
                case 'float' :
                    $input = required_param($key, PARAM_TEXT);
                    if (!empty($input))
                        $maprecord->floatvalue = (double)$input;
                    break;
                default :
                    $maprecord->blobvalue = required_param($key, PARAM_RAW);
            }
        }
        $maprecord->timemodified = time();
        if (!insert_record('brainstorm_operatordata', $maprecord)){
            error("Could not create location record");
        }
    }
}
if ($action == 'clearmappings'){
    // delete all old location data - the fastest way to do it
    if (!delete_records('brainstorm_operatordata', 'brainstormid', $brainstorm->id, 'userid', $USER->id, 'operatorid', 'map')){
        error("Could not delete records");
    }
}
if ($action == 'updatemultiple'){
    $form->brainstormid = $brainstorm->id;    
    $form->itemsource = required_param('source', PARAM_INT);    
    $form->itemdest = required_param('dest', PARAM_INT);    
    
    $select = "
        brainstormid = {$brainstorm->id} AND
        userid = {$USER->id} AND
        itemsource = {$form->itemsource} AND
        itemdest = {$form->itemdest}
    ";
    $maprecord = get_record_select('brainstorm_operatordata', $select);
    if($maprecord){
        $mapobject = unserialize($maprecord->blobvalue);
        $maparray = get_object_vars($mapobject);
        $i = 0;
        foreach($maparray as $key => $value){
            $keykey = 'item_name'.$i;
            $valuekey = 'item_value'.$i;
            $form->$keykey = $key;
            $form->$valuekey = $value;
            $i++;
        }
        $form->numparam = (($i % 3) + 1) * 3;    
    }
    $action = 'inputmultiple';
}
if ($action == 'inputmultiple'){
    $form->brainstormid = $brainstorm->id;
    $form->itemsource = required_param('source', PARAM_INT);
    $form->itemdest = required_param('dest', PARAM_INT);
    $form->numparam = optional_param('numparam', 3, PARAM_INT);
    $keys = preg_grep('/^item_name/', array_keys($_POST));
    if($keys){
        foreach($keys as $key){
            preg_match('/^item_name(.*)/', $key, $matches);
            $keyid = $matches[1];
            $keykey = "item_name{$keyid}";
            $valuekey = "item_value{$keyid}";
            $form->$keykey = required_param("item_name{$keyid}", PARAM_TEXT);
            $form->$valuekey = required_param("item_value{$keyid}", PARAM_CLEANHTML);
        }
    }
    include "{$CFG->dirroot}/mod/brainstorm/operators/map/inputmultiple.html";
    return -1;
}
if ($action == 'doinputmultiple'){
    $form->brainstormid = $brainstorm->id;    
    $form->itemsource = required_param('source', PARAM_INT);    
    $form->itemdest = required_param('dest', PARAM_INT);    
    $keys = preg_grep('/^item_name/', array_keys($_POST));
    foreach($keys as $key){
        preg_match('/^item_name(.*)/', $key, $matches);
        $keyid = $matches[1];
        $keyvalue = required_param("item_name{$keyid}", PARAM_TEXT);
        if (empty($keyvalue)) continue; // discard empty field names
        
        $valuevalue = required_param("item_value{$keyid}", PARAM_CLEANHTML);
        $multiple->$keyvalue = $valuevalue;
    }
    if (isset($multiple)){
        $maprecord->brainstormid = $brainstorm->id;
        $maprecord->userid = $USER->id;
        $maprecord->groupid = $currentgroup;
        $maprecord->operatorid = 'map';
        $maprecord->itemsource = $form->itemsource;
        $maprecord->itemdest = $form->itemdest;
        $maprecord->blobvalue = serialize($multiple);
        $maprecord->timemodified = time();
        $select = "
            brainstormid = {$brainstorm->id} AND
            userid = {$USER->id} AND
            itemsource = {$maprecord->itemsource} AND
            itemdest = {$maprecord->itemdest}
        ";
        if ($oldid = get_field_select('brainstorm_operatordata', 'id', $select)){
            $maprecord->id = $oldid;
            if (!update_record('brainstorm_operatordata', $maprecord)){
                error("Could not update map record");
            }
        }
        else{
            if (!insert_record('brainstorm_operatordata', $maprecord)){
                error("Could not insert map record");
            }
        }
    }
}
if ($action == 'deletemultiple'){
    $form->itemsource = required_param('source', PARAM_INT);    
    $form->itemdest = required_param('dest', PARAM_INT);
    $select = "
        brainstormid = {$brainstorm->id} AND
        userid = {$USER->id} AND
        itemsource = {$form->itemsource} AND
        itemdest = {$form->itemdest}
    ";    
    if (!delete_records_select('brainstorm_operatordata', $select)){
        error("Could not delete mapping");
    }
}
?>
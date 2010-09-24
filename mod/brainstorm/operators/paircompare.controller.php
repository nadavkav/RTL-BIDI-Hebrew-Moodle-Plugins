<?php
/**
* Module Brainstorm V2
* Operator : filter,ordering.
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/

/*********************************** Choosing randomly a first pair and initializing ************************/
if ($action == 'startpaircompare'){
    // delete all old ordering data
    if (!delete_records('brainstorm_operatordata', 'brainstormid', $brainstorm->id, 'userid', $USER->id, 'operatorid', $page)){
        // NOT AN ERROR : there was nothing to clear here
    }
    $responses = brainstorm_get_responses($brainstorm->id, 0, $currentgroup, false);
    $responseskeys = array_keys($responses);
    $rix = array_rand($responseskeys);    
    $form->response1 = $responseskeys[$rix];
    $form->value1 = $responses[$form->response1]->response;
    unset($responses[$form->response1]);
    $responseskeys = array_keys($responses);
    $rix = array_rand($responseskeys);
    $form->response2 = $responseskeys[$rix];
    $form->value2 = $responses[$form->response2]->response;
    
    /// preparing all pair list
    $responseskeys = array_keys($responses);
    $pairs = array();
    for($i = 0 ; $i < count($responseskeys) - 1 ; $i++){
        for($j = $i + 1 ; $j < count($responseskeys) ; $j++){
            if ($responseskeys[$i] == $form->response1 and $responseskeys[$j] == $form->response2) continue;
            if ($responseskeys[$i] == $form->response2 and $responseskeys[$j] == $form->response1) continue;
            $pairs[] = $responseskeys[$i].'_'.$responseskeys[$j];
        }
    }
    $form->remains = count($pairs);
    $record->userid = $USER->id;
    $record->groupid = $currentgroup;
    $record->operatorid = $page;
    $record->brainstormid = $brainstorm->id;
    $record->timemodified = time();
    $record->itemsource = 0;
    $record->blobvalue = implode(",", $pairs);
    if (!insert_record('brainstorm_operatordata', $record)){
        error("Could not insert marking record");
    }

    print_heading(get_string('paircompare','brainstorm'));
    include "{$CFG->dirroot}/mod/brainstorm/operators/order/paircompare.html";
    return -1;
}
/*********************************** Choosing randomly a pair and propose it ************************/
if ($action == 'nextpaircompare'){
    $response1 = required_param('rep1', PARAM_INT);
    $response2 = required_param('rep2', PARAM_INT);
    $choice = required_param('choice', PARAM_INT);

    /// set or update counters
        
    $select = "
       brainstormid = {$brainstorm->id} AND
       operatorid = '{$page}' AND
       itemsource = {$response1} AND
       userid = {$USER->id}
    ";
    $orderrecord1 = get_record_select('brainstorm_operatordata', $select);
    if (!$orderrecord1){
        $orderrecord1->userid = $USER->id;
        $orderrecord1->groupid = $currentgroup;
        $orderrecord1->operatorid = $page;
        $orderrecord1->brainstormid = $brainstorm->id;
        $orderrecord1->timemodified = time();
        $orderrecord1->itemsource = $response1;        
        $orderrecord1->intvalue = ($choice == $response1) ? 1 : 0 ;
        if (!insert_record('brainstorm_operatordata', $orderrecord1)){
            error("Could not insert record");
        }
    }
    else{
        if ($choice == $response1){
            $orderrecord1->intvalue++;
            if (!update_record('brainstorm_operatordata', $orderrecord1)){
                error("Could not update record");
            }
        }
    }

    $select = "
       brainstormid = {$brainstorm->id} AND
       operatorid = '{$page}' AND
       itemsource = {$response2} AND
       userid = {$USER->id}
    ";
    $orderrecord2 = get_record_select('brainstorm_operatordata', $select);
    if (!$orderrecord2){
        $orderrecord2 = &$orderrecord1;
        $orderrecord2->itemsource = $response2;
        $orderrecord2->intvalue = ($choice == $response2) ? 1 : 0 ;
        if (!insert_record('brainstorm_operatordata', $orderrecord2)){
            error("Could not insert record");
        }
    }
    else{
        if ($choice == $response2){
            $orderrecord2->intvalue++;
            if (!update_record('brainstorm_operatordata', $orderrecord2)){
                error("Could not update record");
            }
        }
    }

    /// getting marking record
    $select = "
       brainstormid = {$brainstorm->id} AND
       operatorid = '{$page}' AND
       itemsource = 0 AND
       userid = {$USER->id}
    ";
    if (!$marking = get_record_select('brainstorm_operatordata', $select)){
        error("Could not get marking record");
    }
    $markarray = explode(",", $marking->blobvalue);
    
    /// randomize new pair
    // We generate all possible pairs, and eliminate successively pairs that where already choosed.
    $pairs = array();
    $responses = brainstorm_get_responses($brainstorm->id, 0, $currentgroup, false);
    $guard = 0;
    if ($responses){
        $responseskeys = array_keys($responses);
        for($i = 0 ; $i < count($responseskeys) - 1 ; $i++){
            for($j = $i + 1 ; $j < count($responseskeys) ; $j++){
                if ($responseskeys[$i] == $response1 and $responseskeys[$j] == $response2) continue;
                if ($responseskeys[$i] == $response2 and $responseskeys[$j] == $response1) continue;
                $pairs[] = $responseskeys[$i].'_'.$responseskeys[$j];
            }
        }
        do{
            $rix = array_rand($pairs);
            $pair = $pairs[$rix];
            list($form->response1, $form->response2) = split('_', $pair);
            unset($pairs[$rix]);
        } while (!empty($pairs) and (!in_array($form->response1.'_'.$form->response2, $markarray) and !in_array($form->response2.'_'.$form->response1, $markarray)));
    }

    /// updating mark record
    $pairkey1 = $form->response1.'_'.$form->response2;
    $pairkey2 = $form->response2.'_'.$form->response1;
    $pos = array_search($pairkey1, $markarray);
    if ($pos !== false){
        if (count($markarray) == 1){
            $markarray = array();
        }
        else{
            unset($markarray[$pos]);
        }
    }
    // echo " searching $pairkey2 in ". implode(",", $markarray).'<br/>';
    $pos = array_search($pairkey2, $markarray);  
    if ($pos !== false){
        if (count($markarray) == 1){
            $markarray = array();
        }
        else{        
            unset($markarray[$pos]);
        }
    }

    $form->remains = count($markarray);
    if (empty($markarray)){
        // bounce to last stage
        $processfinished = true;
        $action = 'resumepaircompare';
    }
    else{
        $marking->blobvalue = implode(",", $markarray);
        if (!update_record('brainstorm_operatordata', $marking)){
            error("Could not update marking record");
        }
        $form->value1 = $responses[$form->response1]->response;
        $form->value2 = $responses[$form->response2]->response;
    
        print_heading(get_string('paircompare','brainstorm'));
        include "{$CFG->dirroot}/mod/brainstorm/operators/order/paircompare.html";
        return -1;
    }
}
?>
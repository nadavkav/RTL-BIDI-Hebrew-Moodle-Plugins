<?php

/**
* Module Brainstorm V2
* Operator : filter
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");

/********************************** Saves a new filter ********************************/
if ($action == 'savefiltering' || $action == 'saveandreduce'){
    // first delete all old ordering data - the fastest way to do it
    if (!delete_records('brainstorm_operatordata', 'brainstormid', $brainstorm->id, 'groupid', $currentgroup, 'operatorid', 'filter')){
        error("Could not delete records");
    }

    $inserted = array();
        
    $keys = preg_grep("/^keep_shadow_/", array_keys($_POST));
    foreach($keys as $key){        
        if ($_POST[$key] == 0) continue;
        preg_match("/^keep_shadow_(.*)/", $key, $matches);
        $filterrecord->itemsource = $matches[1];
        $inserted[] = $matches[1];
        $filterrecord->brainstormid = $brainstorm->id;
        $filterrecord->operatorid = 'filter';
        $filterrecord->userid = $USER->id;
        $filterrecord->groupid = $currentgroup;
        $filterrecord->intvalue = 1;
        $filterrecord->timemodified = time();
        if (!insert_record('brainstorm_operatordata', $filterrecord)){
            error("Could not create filtering record");
        }
    }
}
/********************************** Reduces entries ********************************/
if ($action == 'saveandreduce'){
    // first delete all old ordering data - the fastest way to do it
    
    $nottodeletelist = implode("','", $inserted);
    
    $groupClause = ($groupmode && $currentgroup) ? " AND groupid = $currentgroup " : '' ;

    /// deleting all instances of those entries in operatordata    
    $select = "
        brainstormid = $brainstorm->id AND
        operatorid = 'filter' AND
        (itemsource NOT IN ('$nottodeletelist') AND
        itemdest NOT IN ('$nottodeletelist'))
        $groupClause
    ";
    if (!delete_records_select('brainstorm_operatordata', $select)){
        error("Could not delete operatordata records");
    }

    /// deleting in responses
    $select = "
        brainstormid = $brainstorm->id AND
        id NOT IN ('$nottodeletelist')
        $groupClause
    ";
    if (!delete_records_select('brainstorm_responses', $select)){
        error("Could not delete responses records");
    }

    /// deleting in filter
    $select = "
        brainstormid = $brainstorm->id AND
        responseid NOT IN ('$nottodeletelist')
        $groupClause
    ";
    if (!delete_records_select('brainstorm_categorize', $select)){
        error("Could not delete categorization records");
    }        
}
// use the generic pair comparison ordering procedure
$result = include "{$CFG->dirroot}/mod/brainstorm/operators/paircompare.controller.php";
if ($result == -1) return $result;
/*********************************** Resuming pair compare procedure ************************/
// this use case is specific to filter operator as we need producing a valid operator data set for filtering
// we set as deletable the lower ranked entries
if ($action == 'resumepaircompare'){
    $responses = brainstorm_get_responses($brainstorm->id, 0, $currentgroup, false);
    $current_operator = new BrainstormOperator($brainstorm->id, $page);
    
    if (@$processedfinished){
        print_simple_box(get_string('finished', 'brainstorm'));
    }

    // get ordered
    $sql = "
        SELECT
            r.id,
            r.response,
            od.intvalue,
            od.id as odid,
            od.operatorid
        FROM
            {$CFG->prefix}brainstorm_responses as r
        LEFT JOIN
            {$CFG->prefix}brainstorm_operatordata as od
        ON
            r.id = od.itemsource AND 
            od.operatorid = 'filter' AND
            od.userid = {$USER->id} AND 
            od.itemsource != 0
        WHERE
            r.brainstormid = {$brainstorm->id}
        ORDER BY
            od.intvalue DESC
    ";
    $ordered = get_records_sql($sql);
    if ($ordered){
        $table->head = array(get_string('response', 'brainstorm'), get_string('rank', 'brainstorm'), get_string('keepit', 'brainstorm'));
        $table->size = array('80%', '10%', '10%');
        $table->align = array('left', 'center', 'center');
        $datatoresponses = array();
        $i = 0;
        foreach($ordered as $response){
            $table->data[] = array($response->response, $response->intvalue, '');
            $datatoresponses[$i] = $response->id;
            $i++;
        }
        $tokeep = false;
        $keepodids = null;
        for ($i = count($ordered) - 1 ; $i >= 0 ; $i--){
            if (!isset($lastknownrank)) $lastknownrank = $table->data[$i][1];
            if (!$tokeep){
                if ($i > $current_operator->configdata->maxideasleft){
                    if ($table->data[$i][1] != $lastknownrank){
                        $lastknownrank = $table->data[$i][1];
                    }
                }
                else{ // rank changes upper the maxideas number
                    if ($table->data[$i][1] != $lastknownrank){
                        $lastknownrank = $table->data[$i][1];
                        $tokeep = true;
                    }
                }
            }
            if ($tokeep){
                $keepodids[] = $datatoresponses[$i];
                $table->data[$i][2] = "<img src=\"{$CFG->wwwroot}/mod/brainstorm/operators/filter/pix/check.gif\">"; 
            }
        }
        print_table($table);
        
        print_string('recording filter...', 'brainstorm');
    
        /// clean up database of all temp records
        $select = "
           brainstormid = {$brainstorm->id} AND
           operatorid = 'filter' AND
           userid = {$USER->id}
        ";
        delete_records_select('brainstorm_operatordata', $select);

        /// record back all keep markers
        if($keepodids){
            $orderrecord->userid = $USER->id;
            $orderrecord->groupid = $currentgroup;
            $orderrecord->operatorid = 'filter';
            $orderrecord->brainstormid = $brainstorm->id;
            $orderrecord->timemodified = time();
            $orderrecord->intvalue = 1;
            foreach($keepodids as $keepid){
                $orderrecord->itemsource = $keepid;        
                if (!insert_record('brainstorm_operatordata', $orderrecord)){
                    error("Could not insert reordered record");
                }            
            }
        }
    }
    

    /// print final continue button
    print_continue("{$CFG->wwwroot}/mod/brainstorm/view.php?id={$cm->id}&amp;operator={$page}");
        
    return -1;    
}

?>
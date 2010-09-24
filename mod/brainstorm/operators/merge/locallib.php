<?php

/**
* Module Brainstorm V2
* Operator : merge
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/

/**
*
* @uses CFG, USER
* @param int $brainstormid
* @param int $userid
* @param int $groupid
* @param boolean $excludemyself
* @param object $configdata
*/
function merge_get_unassigned($brainstormid, $userid=null, $groupid=0, $excludemyself=false, $configdata){
    global $CFG;

    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);
    
    $sql = "
        SELECT
            r.*, 
            od.itemsource
        FROM
            {$CFG->prefix}brainstorm_responses as r
        LEFT JOIN
            {$CFG->prefix}brainstorm_operatordata as od
        ON
            r.id = od.itemsource AND
            operatorid = 'merge'
        WHERE
            r.brainstormid = {$brainstormid} 
            {$accessClause}
            AND od.itemsource IS NULL
    ";
    if (!$records = get_records_sql($sql)){
        return array();
    }
    return $records;
}

/**
*
* @uses CFG, USER
* @param int $brainstormid
* @param int $userid
* @param int $groupid
* @param boolean $excludemyself
* @param object $configdata
*/
function merge_get_assignations($brainstormid, $userid=null, $groupid=0, $excludemyself=false, $configdata){
    global $CFG;

    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);
    
    $sql = "
        SELECT
            r.*,
            od.intvalue as slotid,
            od.itemdest as choosed,
            od.blobvalue as merged
        FROM
            {$CFG->prefix}brainstorm_responses as r,
            {$CFG->prefix}brainstorm_operatordata as od
        WHERE
            r.id = od.itemsource AND
            r.brainstormid = {$brainstormid} AND
            od.operatorid = 'merge'
            {$accessClause}
    ";
    if (!$records = get_records_sql($sql)){
        return array();
    }
    
    $assignations = array();
    foreach($records as $record){
        $assignations[$record->slotid][] = $record;
    }
    return $assignations;
}

/**
*
* @uses CFG
* @param int $brainstormid
* @param int $userid
* @param int $groupid
* @param boolean $excludemyself
* @param object $configdata
*/
function merge_get_merges($brainstormid, $userid=null, $groupid=0, $excludemyself=false, $configdata){
    global $CFG;

    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);
    
    $sql = "
        SELECT
            r.*,
            od.intvalue as slotid,
            od.blobvalue as merged
        FROM
            {$CFG->prefix}brainstorm_responses as r,
            {$CFG->prefix}brainstorm_operatordata as od
        WHERE
            r.id = od.itemsource AND
            r.brainstormid = {$brainstormid} AND
            od.operatorid = 'merge' AND
            od.itemdest IS NOT NULL
            {$accessClause}
        ORDER BY
            od.intvalue,
            userid
    ";
    if (!$merges = get_records_sql($sql)){
        return array();
    }
    return $merges;
}

/**
*
* @uses CFG, USER
* @param int $brainstormid
* @param int $slotid
* @param int $userid
* @param int $groupid
* @param boolean $excludemyself
*/
function merge_get_customentries($brainstormid, $slotid, $userid=null, $groupid=0, $excludemyself=false){
    global $CFG;

    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);

    $select = "
        brainstormid = {$brainstormid} AND
        operatorid = 'merge' AND
        intvalue = {$slotid} AND
        itemsource = 0
        {$accessClause}
    ";
    $records = get_records_select('brainstorm_operatordata AS od', $select);
    return $records;
}

/**
*
*
*/
function merge_get_dataset_from_query($prefix){
    $keys = preg_grep("/^$prefix/", array_keys($_POST));
    $dataset = array();
    if ($keys){
        foreach($keys as $key){
            preg_match("/^$prefix(.*)/", $key, $matches);
            $dataset[$matches[1]] = required_param($key, PARAM_RAW);
        }
    }
    return $dataset;
}

?>
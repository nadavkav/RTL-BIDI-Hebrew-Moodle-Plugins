<?php

/**
* Module Brainstorm V2
* Operator : map
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/

$MAP_MAX_DATA = 50;

function map_get_cells($brainstormid, $userid=null, $groupid=0, $configdata=null){
    global $CFG;
    
    $accessClause = brainstorm_get_accessclauses($userid, $groupid);
    
    $sql = "
        SELECT
            id,
            itemsource,
            itemdest,
            intvalue,
            floatvalue,
            blobvalue
         FROM
            {$CFG->prefix}brainstorm_operatordata AS od
         WHERE
            brainstormid = $brainstormid AND
            operatorid = 'map'
            {$accessClause}
    ";
    $map = array();
    if ($maprecords = get_records_sql($sql)){
        foreach($maprecords as $record){
            if (!$configdata || !@$configdata->quantified){
                $map[$record->itemsource][$record->itemdest] = 1;
            }
            else{
                switch($configdata->quantifiertype){
                    case 'integer':
                        $map[$record->itemsource][$record->itemdest] = $record->intvalue;
                        break;
                    case 'float':
                        $map[$record->itemsource][$record->itemdest] = $record->floatvalue;
                        break;
                    case 'multiple':
                        $map[$record->itemsource][$record->itemdest] = unserialize($record->blobvalue);
                        break;
                     default:
                }
            }
        }
    }
    return $map;
}

/**
*
* @param object $object
*/
function map_print_multiple_value($object){
    /// convert object into array and print key=>value list
    if (is_object($object)){
        $itemasarray = get_object_vars($object);
        $mapitem = '';
        foreach(array_keys($itemasarray) as $akey){
            $mapitem .= "<b>$akey</b> : " . $itemasarray[$akey]. '<br/>';
        }
    }
    else{
        $mapitem = '';
    }
    return $mapitem;
}

/**
*
*
*/
function map_multiply($brainstormid, $groupid=0, &$map1, &$map2, $configdata, $cycleop=null){

    /// get column set for converting
    $groupClause = ($groupid) ? " AND groupid = $groupid " : '';
    $select = "
       brainstormid = $brainstormid
       $groupClause
    ";
    $colset = get_records_select('brainstorm_responses', $select, 'id,id');

    /// convert both maps into real matrix
    $matrix1 = map_map_to_matrix($map1, $colset);
    $matrix2 = map_map_to_matrix($map2, $colset);

    /// calculate operator
    if (empty($configdata) || !$configdata->quantified){
        $prodinit = 0;
        $evaluated = "\$prod |= (boolean)\$matrix1[\$i][\$k] & (boolean)\$matrix2[\$k][\$j];";
    }
    else{
        if ($configdata->quantifiertype == 'multiple'){
            $evaluated = "\$prod = map_aggreg(\$prod, map_aggreg(\$matrix1[\$i][\$k], \$matrix2[\$k][\$j]));";
        } 
        else{
            if ($cycleop == '>'){ // default
                $prodinit = '';
                $evaluated = "\$prod = \$prod . ',(' . (boolean)\$matrix1[\$i][\$k].':'.(boolean)\$matrix2[\$k][\$j].')';";
            }
            else{
                $prodinit = null;
                $evaluated = "\$prod = (!empty(\$matrix1[\$i][\$k]) && (!empty(\$matrix2[\$k][\$j]))) ? (int)\$prod + \$matrix1[\$i][\$k] * \$matrix2[\$k][\$j] : \$prod ;";
            }
        }               
    }

    /// multiply matrix
    $matrix = array();
    for ($i = 0 ; $i < count($matrix1) ; $i++){
        for ($j = 0 ; $j < count($matrix1) ; $j++){
            $prod = $prodinit;
            for ($k = 0 ; $k < count($matrix1) ; $k++){
                @eval($evaluated);
            }            
            $matrix[$i][$j] = $prod;
        }
    }   
    return map_map_from_matrix($matrix, $colset);
}

/**
* converts a connection map to full square matrix
* @param array $map the input map
* @param array $colset the column reference definition as an array
* @returns a full square matrix
*/
function map_map_to_matrix($map, $colset){
    if (empty($colset)) return array();
    if (empty($map)) return array();
    
    $matrix = array();
    $i = 0;
    foreach($colset as $row){
        $j = 0;
        foreach($colset as $col){
            $matrix[$i][$j] = (isset($map[$row->id][$col->id])) ? $map[$row->id][$col->id] : null ;
            $j++;
        }
        $i++;
    }
    return $matrix;
}

/**
* converts a full square matrix into connection map
* @param array $matrix the input matrix
* @param array $colset the column reference definition as an array
* @returns the extracter connection map
*/
function map_map_from_matrix($matrix, $cols){
    if (empty($cols)) return array();
    if (empty($matrix)) return array();
    
    /// make a reverse numbering index
    $i = 0;
    foreach($cols as $key => $value){
        $colset[$i] = $key;
        $i++;
    }
    
    $map = array();
    for($i = 0 ; $i < count($matrix); $i++){
        for($j = 0 ; $j < count($matrix); $j++){
            if (isset($matrix[$i][$j])){
                $map[$colset[$i]][$colset[$j]] = $matrix[$i][$j];
            }
        }
    }
    return $map;
}

/**
*
*
*/
function map_aggreg($object1, $object2){
    if (!is_object($object2)) return $object1;
    $members = get_object_vars($object2);
    foreach($members as $key => $value){
        $object1->$key = $value;
    }
    return $object1;
}
?>
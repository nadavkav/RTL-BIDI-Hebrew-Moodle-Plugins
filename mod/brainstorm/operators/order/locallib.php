<?php

/**
* Module Brainstorm V2
* Operator : order
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/

/**
* get ordering on distinct contexts. Knows how to get an incomplete ordering.
* @param int $brainstormid
* @param int $userid
* @param int $groupid
* @param boolean $excludemyself
*/
function order_get_ordering($brainstormid, $userid=null, $groupid=0, $excludemyself=false){
    global $CFG;

    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);

    $sql = "
        SELECT
            r.id,
            r.response,
            od.intvalue,
            od.userid,
            od.groupid
        FROM
            {$CFG->prefix}brainstorm_responses as r
        LEFT JOIN
            {$CFG->prefix}brainstorm_operatordata as od
        ON
            r.id = od.itemsource AND
            (od.operatorid = 'order'
            {$accessClause})
        WHERE
            r.brainstormid = {$brainstormid}
         ORDER BY
            od.intvalue, 
            od.userid
    ";
    if (!$records = get_records_sql($sql)){
        return array();
    }
    return $records;
}

/**
* checks if there are ordering data for the given user context
* @param int $brainstormid
* @param int $userid
* @param int $groupid
* @param boolean $excludemyself
*/
function has_ordering_data($brainstormid, $userid=null, $groupid=0, $excludemyself=false){
    global $CFG;

    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);

    $sql = "
        SELECT
            COUNT(*)
        FROM
            {$CFG->prefix}brainstorm_responses as r,
            {$CFG->prefix}brainstorm_operatordata as od
        WHERE
            r.brainstormid = {$brainstormid} AND
            r.id = od.itemsource AND
            (od.operatorid = 'order'
            {$accessClause})
    ";
    return count_records_sql($sql);
}

/**
*
*
*/
function order_get_otherorderings($brainstormid, $orderedresponsekeys, $groupid=0){
    $orderings = order_get_ordering($brainstormid, 0, $groupid, true);
    $agree = array();
    $disagree = array();
    if ($orderings){
        foreach($orderings as $ordering){
            if (array_key_exists($ordering->intvalue, $orderedresponsekeys)) {
                if ($orderedresponsekeys[$ordering->intvalue] == $ordering->id){
                    $agree[$ordering->intvalue] = @$agree[$ordering->intvalue] + 1;
                }
                else{
                    $disagree[$ordering->intvalue] = @$disagree[$ordering->intvalue] + 1;
                }
            }
        }
    }
    $result->agree = &$agree;
    $result->disagree = &$disagree;
    return $result;
}

/**
*
*
*/
function order_display(&$brainstorm, $userid, $groupid){
    $responses = brainstorm_get_responses($brainstorm->id, 0, $groupid, false, 'timemodified,id');
    $myordering = order_get_ordering($brainstorm->id, $userid, 0, false);
?>
<center>
<style>
.match { background-color : #54DE57 }
</style>
<table>
    <tr>
        <th>
            <?php print_string('original', 'brainstorm'); ?>
        </th>
        <th>
            <?php print_string('myordering', 'brainstorm'); ?>
        </th>
    </tr>
    <tr>
        <td>
<?php
if ($responses){
    $i = 0;
    echo '<table cellspacing="10">';
    $myorderingkeys = array_keys($myordering);
    foreach($responses as $response){
        $matchclass = ($response->id == @$myorderingkeys[$i]) ? 'match' : '';
?>
                <tr>
                    <th class="<?php echo $matchclass ?>">
                        <?php echo $i + 1 ?>.
                    </th>
                    <td>
                        <?php echo $response->response ?>
                    </td>
                </tr>
<?php
        $i++;
    }
    echo '</table>';
}
else{
    print_simple_box(get_string('noresponses', 'brainstorm'));    
}
?>
        </td>
        <td>
<?php
if ($myordering){
    $i = 0;
    echo '<table cellspacing="10">';
    $responsekeys = array_keys($responses);
    foreach($myordering as $response){
        $matchclass = ($response->id == @$responsekeys[$i]) ? 'match' : '';
?>
                <tr>
                    <th class="<?php echo $matchclass ?>">
                        <?php echo $i + 1 ?>.
                    </th>
                    <td>
                        <?php echo $response->response ?>
                    </td>
                </tr>
<?php
        $i++;
    }
    echo '</table>';
}
else{
    print_simple_box(get_string('noorderset', 'brainstorm'));
}
?>
        </td>
    </tr>
</table>
<?php
}
?>
<?php

/**
* Module Brainstorm V2
* Operator : filter
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
*/
function filter_get_status($brainstormid, $userid=null, $groupid=0, $excludemyself=false){
    global $CFG;
    
    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);
    
    $sql = "
        SELECT
            itemsource,
            intvalue,
            od.userid,
            od.groupid,
            response
         FROM
            {$CFG->prefix}brainstorm_operatordata AS od,
            {$CFG->prefix}brainstorm_responses AS r
         WHERE
            od.brainstormid = {$brainstormid} AND
            od.itemsource = r.id AND
            operatorid = 'filter'
            {$accessClause}
    ";
    if (!$statusrecords = get_records_sql($sql)){
        $statusrecords = array();
    }    
    return $statusrecords;
}

/**
* displays filter information for a user
*
*/
function filter_display(&$brainstorm, $userid, $groupid){
    $responses = brainstorm_get_responses($brainstorm->id, 0, $groupid, false);
    $responsesids = array_keys($responses);
    $statuses = filter_get_status($brainstorm->id, $userid, $groupid, false);
    $statusesids = array_keys($statuses);
    $strsource = get_string('sourcedata', 'brainstorm');
    $strfiltered = get_string('filtereddata', 'brainstorm');    
?>
<style>
.match{ background-color : #25B128 }
.nomatch{ background-color : #CE0909 }
</style>
<table cellspacing="5">
    <tr valign="top">
        <td>
            <table cellspacing="5">
                <tr>
                    <th>
                        &nbsp;
                    </th>
                    <th>
                        <?php echo $strsource ?>
                    </th>
                 </tr>
<?php
        $i = 0;
        foreach($responses as $response){
            $match = (in_array($response->id, $statusesids)) ? 'match' : '' ;
?>
                <tr>
                    <th class="<?php echo $match ?>">
                        <b><?php echo $i + 1?>.</b>
                    </th>
                    <td>
                        <?php echo $response->response ?>
                    </td>
                 </tr>
<?php
        $i++;
    }
?>
            </table>
        </td>
        <td>
<?php
    if ($statuses){
?>
            <table cellspacing="5">
                <tr>
                    <th>
                        &nbsp;
                    </th>
                    <th>
                        <?php echo $strfiltered ?>
                    </th>
                 </tr>
<?php
        $i = 0;
        foreach($statuses as $status){
                    $match = (in_array($status->itemsource, $responsesids)) ? 'match' : '' ;
?>
                <tr>
                    <th class="<?php echo $match ?>">
                        <b><?php echo $i + 1?>.</b>
                    </th>
                    <td>
                        <?php echo $status->response ?>
                    </td>
                 </tr>
<?php
        $i++;
    }
?>
            </table>
<?php
    }
    else{
        
        print_simple_box(get_string('nofilteringinprogress', 'brainstorm'));
    }
?>
        </td>
    </tr>
</table>
<?php
}
?>
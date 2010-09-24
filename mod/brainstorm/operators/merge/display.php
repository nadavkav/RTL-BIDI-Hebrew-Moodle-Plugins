<?php

/**
* Module Brainstorm V2
* Operator : merge
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/
include_once ($CFG->dirroot."/mod/brainstorm/operators/{$page}/locallib.php");
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");
?>
<center>
<?php
$current_operator = new BrainstormOperator($brainstorm->id, $page);
$responses = brainstorm_get_responses($brainstorm->id, 0, $currentgroup, false);

$merges = merge_get_merges($brainstorm->id, null, $currentgroup, false, $current_operator->configdata);
$othermerges = merge_get_merges($brainstorm->id, null, $currentgroup, true, $current_operator->configdata);
/// sorting and dispatching
foreach($othermerges as $amerge){
    $others[$amerge->userid][] = $amerge;
}

$strsource = get_string('sourcedata', 'brainstorm');
$strmerged = get_string('mergeddata', 'brainstorm');

print_heading(get_string('myfilter', 'brainstorm'));
print_simple_box_start('center');
?>
<table cellspacing="5">
    <tr>
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
?>
                <tr>
                    <th>
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
if ($merges){
?>
            <table cellspacing="5">
                <tr>
                    <th>
                        &nbsp;
                    </th>
                    <th>
                        <?php echo $strmerged ?>
                    </th>
                 </tr>
<?php
    $i = 0;
    foreach($merges as $merge){
?>
                <tr>
                    <th>
                        <b><?php echo $i + 1?>.</b>
                    </th>
                    <td>
                        <?php echo $merge->merged ?>
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
    
    print_simple_box(get_string('nomergeinprogress', 'brainstorm'));
}
?>
        </td>
    </tr>
</table>
<?php
print_simple_box_end();

print_heading(get_string('othermerges', 'brainstorm'));
print_simple_box_start('center');
$cols = 0;
if (!empty($others)){
?>
<table>
    <tr>
        <td>
<?php
foreach(array_keys($others) as $userid){
    $user = get_record('user', 'id', $userid);
    print_heading(fullname($user), 'h2');
    echo '<table>';
    $i = 0;
    foreach($others[$userid] as $merge){
        echo '<tr><td>'.($i + 1).'</td><td align="left">'.$merge->merged.'</td></tr>';
        $i++;
    }
    echo '</table>';
    if ($cols && $cols % $brainstorm->numcolumns == 0){
        echo "</td></tr><tr><td>";
    }
    else{
        echo "</td><td>";
    }
    $cols++;
}
?>
        </td>
    </tr>
</table>
<?php
}
else{
    print_string('noothermerges', 'brainstorm');
}
print_simple_box_end();
?>
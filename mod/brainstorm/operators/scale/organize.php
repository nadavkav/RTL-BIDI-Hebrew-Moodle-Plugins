<?php

/**
* Module Brainstorm V2
* Operator : scale
* @author Valery Fremaux
* @package Brainstorm
* @date 20/12/2007
*/
include_once ($CFG->dirroot."/mod/brainstorm/operators/{$page}/locallib.php");
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");

if (!isset($current_operator))
    $current_operator = new BrainstormOperator($brainstorm->id, $page);
$responses = brainstorm_get_responses($brainstorm->id, 0, $currentgroup, false);
$scalings = scale_get_scalings($brainstorm->id, null, $currentgroup, false, $current_operator->configdata);
print_heading("<img src=\"{$CFG->wwwroot}/mod/brainstorm/operators/{$page}/pix/enabled_small.gif\" align=\"left\" width=\"40\" /> " . get_string('givingweightstoideas','brainstorm'));
?>
<center>
<?php
if (isset($current_operator->configdata->requirement))
    print_simple_box($current_operator->configdata->requirement);
?>
<style>
.response{ border : 1px solid gray ; background-color : #E1E1E1 }
</style>
<form name="scaleform" action="view.php" method="POST">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="operator" value="<?php p($page) ?>" />
<input type="hidden" name="what" value="savescalings" />
<table cellspacing="5" width="80%">
<?php
if ($responses){
    $i = 0;
    foreach($responses as $response){
?>
    <tr valign="top">
        <th>
            <?php echo ($i + 1) ?>.
        </th>
        <td align="right">
            <?php echo $response->response ?>
        </td>
        <td align="right">
<?php
        switch($current_operator->configdata->quantifiertype){
            case 'moodlescale':
                break;
            case 'integer':
                $value = (isset($scalings[$response->id])) ? $scalings[$response->id]->intvalue : '' ;
                echo "<input type=\"text\" size=\"10\" name=\"scale_{$response->id}\" value=\"{$value}\" />";
                break;
            default:
                $value = (isset($scalings[$response->id])) ? sprintf("%.2f", $scalings[$response->id]->floatvalue) : '' ;
                echo "<input type=\"text\" size=\"10\" name=\"scale_{$response->id}\" value=\"{$value}\" />";
        }
?>
        </td>
<?php
    }
?>
    <tr>
        <td colspan="3">
            <input type="submit" name="go_btn" value="<?php print_string('savescaling', 'brainstorm') ?>" />
            &nbsp;<input type="button" name="clear_btn" value="<?php print_string('clearall', 'brainstorm') ?>" onclick="document.forms['scaleform'].what.value='clearall';document.forms['scaleform'].submit();" />
        </td>
    </tr>
<?php
}
else{
    echo '<tr><td>';
    print_simple_box(get_string('noresponses', 'brainstorm'));
    echo '</td></tr>';
}
?>
</table>
<script type="text/javascript">
<?php
if ($current_operator->configdata->absolute) {
?>
var responsekeys = '<?php echo implode(",", array_keys($responses)) ?>';

function checkabsolute(fieldobj){
    resplist = responsekeys.split(/,/);
    for (respid in resplist){
        afield = document.forms['scaleform'].elements['scale_' + resplist[respid]];
        if (afield.value == fieldobj.value && afield.name != fieldobj.name){
            alert("<?php print_string('absoluteconstraint', 'brainstorm') ?>");
            fieldobj.value = '';
            fieldobj.focus();
        }
    }
}
<?php
}
else{
?>
function checkabsolute(fieldobj){
}
<?php
}
?>
</script>
</center>
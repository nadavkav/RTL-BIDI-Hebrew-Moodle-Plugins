<?php

/**
* Module Brainstorm V2
* Operator : map
* @author Valery Fremaux
* @package Brainstorm
* @date 20/12/2007
*/

include_once ($CFG->dirroot."/mod/brainstorm/operators/{$page}/locallib.php");
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");

$responses = brainstorm_get_responses($brainstorm->id);
if (count($responses) > $MAP_MAX_DATA){
    notice(get_string('toomuchdata', 'brainstorm', $MAP_MAX_DATA));
    return;
}

$current_operator = new BrainstormOperator($brainstorm->id, $page);
$map = map_get_cells($brainstorm->id, $USER->id, $currentgroup, $current_operator->configdata);

/// special alternative
if ($action == 'display_cycles'){
    $maporigin = $map;

    $order = optional_param('order', 2, PARAM_INT);
    $cycleop = optional_param('cycleop', '*', PARAM_RAW);

    for($i = 1; $i < $order ; $i++){
        $map = map_multiply($brainstorm->id, $currentgroup, $map, $maporigin, $current_operator->configdata, $cycleop);
    }
}
/// /special alternative

print_heading(get_string('mapofresponses', 'brainstorm'));
print_simple_box_start('center');
?>
<style>
.maptable{border : 1px solid gray}
.maptablecell{border : 1px solid gray}
.maptablediagonal{border : 1px solid gray ; background-color : #D6D6D6 }
.maptablecycle{border : 1px solid #D20000 ; background-color : #F99B9B }
</style>
<center>
<?php
if ($responses){
    $width = 100 / count($responses) + 2;
    $titlewidth = $width * 2;

    /// draw top title line
    echo '<table class="maptable">';
    echo "<tr>\n";
    echo "<th width=\"{$titlewidth}%\" class=\"maptablecell\">&nbsp;</th>\n";
    foreach($responses as $responsecol){
        echo "<td width=\"{$width}%\" class=\"maptablecell\">{$responsecol->response}</td>\n";
    }
    echo "</tr>\n";

    foreach($responses as $responserow){
        echo "<tr>\n";
        echo "<th width=\"{$titlewidth}%\" class=\"maptablecell\">{$responserow->response}</th>\n";
        foreach($responses as $responsecol){
            $mapitem = '';
            if (@$current_operator->configdata->quantified && $current_operator->configdata->quantifiertype == 'multiple'){
                $mapitem = map_print_multiple_value(@$map[$responserow->id][$responsecol->id]);
            }
            else{
                if (@$current_operator->configdata->quantified){
                    $mapitem = @$map[$responserow->id][$responsecol->id];
                }
                else{
                    if (@$map[$responserow->id][$responsecol->id]){
                        $mapitem = "<img src=\"{$CFG->wwwroot}/mod/brainstorm/operators/map/pix/check.gif\"/>";
                    }
                }
            }
            if ($responserow->id == $responsecol->id){
                if (!empty($mapitem) && $action == 'display_cycles'){
                    $cellclass = 'maptablecycle';
                }
                else{
                    $cellclass = 'maptablediagonal';
                }
            }
            else{
                $cellclass = 'maptablecell' ;
            }
            echo "<td width=\"{$width}%\" class=\"$cellclass\">$mapitem</td>\n";
        }
        echo "</tr>\n";
    }
    echo '</table>';
}
else{
    print_string('noresponses', 'brainstorm');
}
if (@$current_operator->configdata->allowcheckcycles){
?>
<form action="view.php" name="orderform" method="POST">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="what" value="display_cycles" />
<input type="hidden" name="operator" value="map" />
<input type="hidden" name="cycleop" value="<?php p($cycleop) ?>" />
<input type="hidden" name="order" value="<?php echo (empty($order)) ? 2 : $order ; ?>" />
<table>
    <tr>
        <td>
<?php
if (empty($order)){
?>
            <input type="submit" name="checkcycles_btn" value="<?php print_string('checkcycles','brainstorm') ?>" />
            &nbsp;<?php print_string('showmatrixproduct', 'brainstorm') ?>: <input type="checkbox" name="cycleop" value=">" />
<?php
}
else{
    if ($order > 1){
?>
            <input type="button" name="orderminus_btn" value="<?php print_string('orderminusone','brainstorm', $order - 1) ?>" onclick="document.forms['orderform'].order.value = <?php echo $order - 1; ?>;document.forms['orderform'].submit();" />
<?php
    }
?>
            &nbsp;<input type="button" name="orderplus_btn" value="<?php print_string('orderplusone','brainstorm', $order + 1) ?>" onclick="document.forms['orderform'].order.value = <?php echo $order + 1; ?>;document.forms['orderform'].submit();" />
            &nbsp;<input type="button" name="display_btn" value="<?php print_string('displaynormal','brainstorm') ?>" onclick="document.forms['orderform'].what.value = '';document.forms['orderform'].submit();" />
<?php
}
?>
        </td>
     </tr>
</table>
</form>
<?php
}
print_simple_box_end();
?>
</center>


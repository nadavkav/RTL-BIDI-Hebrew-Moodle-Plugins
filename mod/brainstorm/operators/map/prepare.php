<?php

/**
* Module Brainstorm V2
* Operator : map
* @author Valery Fremaux
* @package Brainstorm
* @date 20/12/2007
*/
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");

if (!brainstorm_legal_include()){
    error("The way you loaded this page is not the way this script is done for.");
}

$currentoperator = new BrainstormOperator($brainstorm->id, $page);
$usehtmleditor = can_use_html_editor();

if (!isset($currentoperator->configdata->quantified)){
    $currentoperator->configdata->quantified = 1;
}
if (!isset($currentoperator->configdata->allowcheckcycles)){
    $currentoperator->configdata->allowcheckcycles = 1;
}
if (!isset($currentoperator->configdata->quantifiertype)){
    $currentoperator->configdata->quantifiertype = 'float';
}
if (!isset($currentoperator->configdata->procedure)){
    $currentoperator->configdata->procedure = 'gridediting';
}
if (!isset($currentoperator->configdata->requirement)){
    $currentoperator->configdata->requirement = '';
}

$noselected0 = (!$currentoperator->configdata->quantified) ? 'checked="checked"' : '' ;
$yesselected0 = ($currentoperator->configdata->quantified) ? 'checked="checked"' : '' ;

$integerselected = ($currentoperator->configdata->quantifiertype == 'integer') ? 'checked="checked"' : '' ;
$floatselected = ($currentoperator->configdata->quantifiertype == 'float') ? 'checked="checked"' : '' ;
$multipleselected = ($currentoperator->configdata->quantifiertype == 'multiple') ? 'checked="checked"' : '' ;

$noselected1 = (!$currentoperator->configdata->allowcheckcycles) ? 'checked="checked"' : '' ;
$yesselected1 = ($currentoperator->configdata->allowcheckcycles) ? 'checked="checked"' : '' ;

print_heading(get_string("{$page}settings", 'brainstorm'));
?>
<center>
<img src="<?php echo "$CFG->wwwroot/mod/brainstorm/operators/{$page}/pix/enabled.gif" ?>" align="left" />

<form name="addform" method="post" action="view.php">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="operator" value="<?php echo $page ?>" />
<input type="hidden" name="what" value="saveconfig" />
<table width="80%" cellspacing="10">
    <tr valign="top">
        <td align="left"><b><?php print_string('requirement', 'brainstorm') ?>:</b></td>
        <td align="right">
			<?php
			if ($brainstorm->oprequirementtype == 0){
			?>
            <input type="text" size="60" name="config_requirement" value="<?php echo stripslashes($currentoperator->configdata->requirement); ?>" />
			<?php
			}
			elseif ($brainstorm->oprequirementtype == 2){
				print_textarea($usehtmleditor, 20, 50, 680, 400, 'config_requirement', stripslashes($currentoperator->configdata->requirement));
				if (!$usehtmleditor){
					$brainstorm->oprequirementtype = 1;
				}
				else{
					$htmleditorneeded = true;
				}
			}
			elseif ($brainstorm->oprequirementtype == 1){
			?>
            <textarea style="width:100%;height:150px" name="config_requirement"><?php echo stripslashes($currentoperator->configdata->requirement); ?></textarea>
			<?php
			}
			?>
            <?php helpbutton('requirement', get_string('requirement', 'brainstorm'), 'brainstorm'); ?>
        </td>
    </tr>
    <tr valign="top">
        <td align="left"><b><?php print_string('quantifiedmap', 'brainstorm') ?>:</b></td>
        <td align="right">
            <input type="radio" name="config_quantified" value="0" <?php echo $noselected0 ?> /> <?php print_string('no') ?>&nbsp;-&nbsp;
            <input type="radio" name="config_quantified" value="1" <?php echo $yesselected0 ?> /> <?php print_string('yes') ?>
            <?php helpbutton('operatorrouter.html&amp;operator=map&amp;helpitem=quantified', get_string('quantified', 'brainstorm'), 'brainstorm'); ?>
        </td>
    </tr>
    <tr valign="top">
        <td align="left"><b><?php print_string('quantifiertype', 'brainstorm') ?>:</b></td>
        <td align="right">
            <input type="radio" name="config_quantifiertype" value="integer" <?php echo $integerselected ?> /> <?php print_string('integer', 'brainstorm') ?>&nbsp;-&nbsp;
            <input type="radio" name="config_quantifiertype" value="float" <?php echo $floatselected ?> /> <?php print_string('float', 'brainstorm') ?>&nbsp;-&nbsp;
            <input type="radio" name="config_quantifiertype" value="multiple" <?php echo $multipleselected ?> /> <?php print_string('multiple', 'brainstorm') ?>&nbsp;-&nbsp;
            <?php helpbutton('operatorrouter.html&amp;operator=map&amp;helpitem=quantifiertype', get_string('quantifiertype', 'brainstorm'), 'brainstorm'); ?>
        </td>
    </tr>
    <tr valign="top">
        <td align="left"><b><?php print_string('procedure', 'brainstorm') ?>:</b></td>
        <td align="right">
            <?php
            $procedure_options['gridediting'] = get_string('gridediting', 'brainstorm');
            $procedure_options['picktwoandqualify'] = get_string('picktwoandqualify', 'brainstorm');
            $procedure_options['onetoonerandom'] = get_string('onetoonerandom', 'brainstorm');
            choose_from_menu($procedure_options, 'config_procedure', $currentoperator->configdata->procedure);
            helpbutton('procedure', get_string('procedure', 'brainstorm'), 'brainstorm');
            ?>
        </td>
    </tr>
    <tr valign="top">
        <td align="left"><b><?php print_string('allowcheckcycles', 'brainstorm') ?>:</b></td>
        <td align="right">
            <input type="radio" name="config_allowcheckcycles" value="0" <?php echo $noselected1 ?> /> <?php print_string('no') ?>&nbsp;-&nbsp;
            <input type="radio" name="config_allowcheckcycles" value="1" <?php echo $yesselected1 ?> /> <?php print_string('yes') ?>
            <?php helpbutton('operatorrouter.html&amp;operator=map&amp;helpitem=allowcheckcycles', get_string('allowcheckcycles', 'brainstorm'), 'brainstorm'); ?>
        </td>
    </tr>
    <tr valign="top">
        <td colspan="2">
            <br/><input type="submit" name="go_btn" value="<?php print_string('saveconfig', 'brainstorm') ?>" />
        </td>
    </tr>
</table>
</form>
</center>
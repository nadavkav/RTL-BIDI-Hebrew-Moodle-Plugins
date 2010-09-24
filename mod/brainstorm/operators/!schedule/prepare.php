<?PHP  // $Id: categorise.php,v 1.1 2004/08/24 16:40:57 cmcclean Exp $

/**
* Module Brainstorm V2
* Operator : schedule
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");

$currentoperator = new BrainstormOperator($brainstorm->id, $page);
$usehtmleditor = can_use_html_editor();

if (!isset($currentoperator->configdata->quantifyedges)){
    $currentoperator->configdata->quantifyedges = 0;
}
if (!isset($currentoperator->configdata->quantifiertype)){
    $currentoperator->configdata->quantifiertype = 'float';
}

$noselected = (!@$currentoperator->configdata->quantifyedges) ? 'CHECKED' : '' ;
$yesselected = (@$currentoperator->configdata->quantifyedges) ? 'CHECKED' : '' ;
$multipleselected = ($currentoperator->configdata->quantifiertype == 'multiple') ? 'CHECKED' : '' ;
$integerselected = ($currentoperator->configdata->quantifiertype == 'integer') ? 'CHECKED' : '' ;
$floatselected = ($currentoperator->configdata->quantifiertype == 'float') ? 'CHECKED' : '' ;

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
        <td align="right"><b><?php print_string('requirement', 'brainstorm') ?>:</b></td>
        <td align="left">
<?php
if ($brainstorm->oprequirementtype == 0){ 
?>       
            <input type="text" size="80" name="config_requirement" value="<?php echo stripslashes($currentoperator->configdata->requirement); ?>" />
<?php
}
elseif ($brainstorm->oprequirementtype == 2){ 
    print_textarea($usehtmleditor, 20, 50, 680, 400, 'config_requirement', @$form->description);
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
    <tr>
        <td align="right"><b><?php print_string('quantifyedges', 'brainstorm') ?>:</b></td>
        <td align="left">
            <input type="radio" name="config_quantifyedges" value="0" <?php echo $noselected ?> /> <?php print_string('no') ?>&nbsp;-&nbsp;
            <input type="radio" name="config_quantifyedges" value="1" <?php echo $yesselected ?> /> <?php print_string('yes') ?>
        </td>
    </tr>
    <tr>
        <td align="right"><b><?php print_string('quantifiertype', 'brainstorm') ?>:</b></td>
        <td align="left">
            <input type="radio" name="config_quantifiertype" value="integer" <?php echo $integerselected ?> /> <?php print_string('integer', 'brainstorm') ?>&nbsp;-&nbsp;
            <input type="radio" name="config_quantifiertype" value="float" <?php echo $floatselected ?> /> <?php print_string('float', 'brainstorm') ?>
            <input type="radio" name="config_quantifiertype" value="multiple" <?php echo $multipleselected ?> /> <?php print_string('multiple', 'brainstorm') ?>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <br/><input type="submit" name="go_btn" value="<?php print_string('saveconfig', 'brainstorm') ?>" />
        </td>
    </tr>
</table>
</form>
<?php
echo '</center>';
?>
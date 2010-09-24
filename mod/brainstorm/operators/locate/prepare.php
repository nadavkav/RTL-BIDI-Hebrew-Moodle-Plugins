<?PHP  // $Id: categorise.php,v 1.1 2004/08/24 16:40:57 cmcclean Exp $

/**
* Module Brainstorm V2
* Operator : locate
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

if (!isset($currentoperator->configdata->xquantifier)){
    $currentoperator->configdata->xquantifier = get_string('criteriax', 'brainstorm');
}
if (!isset($currentoperator->configdata->xminrange)){
    $currentoperator->configdata->xminrange = '0.0';
}
if (!isset($currentoperator->configdata->xmaxrange)){
    $currentoperator->configdata->xmaxrange = '10.0';
}
if (!isset($currentoperator->configdata->yquantifier)){
    $currentoperator->configdata->yquantifier = get_string('criteriay', 'brainstorm');
}
if (!isset($currentoperator->configdata->yminrange)){
    $currentoperator->configdata->yminrange = '0.0';
}
if (!isset($currentoperator->configdata->ymaxrange)){
    $currentoperator->configdata->ymaxrange = '10.0';
}
if (!isset($currentoperator->configdata->quantifiertype)){
    $currentoperator->configdata->quantifiertype = 'float';
}
if (!isset($currentoperator->configdata->width)){
    $currentoperator->configdata->width = 400;
}
if (!isset($currentoperator->configdata->height)){
    $currentoperator->configdata->height = 400;
}
if (!isset($currentoperator->configdata->showlabels)){
    $currentoperator->configdata->showlabels = 1;
}
if (!isset($currentoperator->configdata->requirement)){
    $currentoperator->configdata->requirement = '';
}

$noselected = (!$currentoperator->configdata->showlabels) ? 'checked="checked"' : '' ;
$yesselected = ($currentoperator->configdata->showlabels) ? 'checked="checked"' : '' ;
$integerselected = ($currentoperator->configdata->quantifiertype == 'integer') ? 'checked="checked"' : '' ;
$floatselected = ($currentoperator->configdata->quantifiertype == 'float') ? 'checked="checked"' : '' ;

print_heading(get_string("{$page}settings", 'brainstorm'));
?>
<center>
<img src="<?php echo "$CFG->wwwroot/mod/brainstorm/operators/{$page}/pix/enabled.gif" ?>" align="left" />

<form name="addform" method="post" action="view.php">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="operator" value="<?php echo $page ?>" />
<input type="hidden" name="what" value="saveconfig" />
<table width="80%" cellspacing="5">
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
</table>
<table width="80%" cellspacing="5">
    <tr valign="top">
        <td>
            <table width="100%" cellspacing="5">
                <tr valign="top">
                    <td align="left"><b><?php print_string('xquantifier', 'brainstorm') ?>:</b></td>
                    <td align="right">
                        <input type="text" name="config_xquantifier" size="30" value="<?php echo $currentoperator->configdata->xquantifier ?>" />
                        <?php helpbutton('operatorrouter.html&amp;operator=locate&amp;helpitem=quantifiers', get_string('xquantifier', 'brainstorm'), 'brainstorm'); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <td align="left" ><b><?php print_string('xminrange', 'brainstorm') ?>:</b></td>
                    <td align="right" <?php print_error_class(@$errors, 'xrange') ?> >
                        <input type="text" name="config_xminrange" size="10" value="<?php echo $currentoperator->configdata->xminrange ?>" />
                        <?php helpbutton('operatorrouter.html&amp;operator=locate&amp;helpitem=ranges', get_string('xminrange', 'brainstorm'), 'brainstorm'); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <td align="left"><b><?php print_string('xmaxrange', 'brainstorm') ?>:</b></td>
                    <td align="right" <?php print_error_class(@$errors, 'xrange') ?> >
                        <input type="text" name="config_xmaxrange" size="10" value="<?php echo $currentoperator->configdata->xmaxrange ?>" />
                        <?php helpbutton('operatorrouter.html&amp;operator=locate&amp;helpitem=ranges', get_string('xmaxrange', 'brainstorm'), 'brainstorm'); ?>
                    </td>
                </tr>
            </table>
        </td>
        <td>
            <table width="100%" cellspacing="5">
                <tr valign="top">
                    <td align="left"><b><?php print_string('yquantifier', 'brainstorm') ?>:</b></td>
                    <td align="right">
                        <input type="text" name="config_yquantifier" size="30" value="<?php echo $currentoperator->configdata->yquantifier ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <td align="left" ><b><?php print_string('yminrange', 'brainstorm') ?>:</b></td>
                    <td align="right" <?php print_error_class(@$errors, 'yrange') ?> >
                        <input type="text" name="config_yminrange" size="10" value="<?php echo $currentoperator->configdata->yminrange ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <td align="left"><b><?php print_string('ymaxrange', 'brainstorm') ?>:</b></td>
                    <td align="right" <?php print_error_class(@$errors, 'yrange') ?> >
                        <input type="text" name="config_ymaxrange" size="10" value="<?php echo $currentoperator->configdata->ymaxrange ?>" />
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="left"><b><?php print_string('quantifiertype', 'brainstorm') ?>:</b></td>
        <td align="right" <?php print_error_class(@$errors, 'yrange') ?> >
            <input type="radio" name="config_quantifiertype" value="integer" <?php echo $integerselected ?> /> <?php print_string('integer', 'brainstorm') ?>&nbsp;-&nbsp;
            <input type="radio" name="config_quantifiertype" value="float" <?php echo $floatselected ?> /> <?php print_string('float', 'brainstorm') ?>
            <?php helpbutton('operatorrouter.html&amp;operator=locate&amp;helpitem=quantifiertype', get_string('quantifiertype', 'brainstorm'), 'brainstorm'); ?>
        </td>
    </tr>
</table>
<?php
if (has_capability('mod/brainstorm:manage', $context)){
?>
<fieldset class="privateform">
<legend><?php print_string('foradminsonly', 'brainstorm') ?></legend>
<table width="80%" cellspacing="5">
    <tr valign="top">
        <td align="left"><b><?php print_string('displaysize', 'brainstorm') ?>:</b></td>
        <td align="right" <?php print_error_class(@$errors, 'width,height') ?> >
            <?php print_string('width', 'brainstorm') ?>: <input type="text" name="config_width" size="10" value="<?php echo $currentoperator->configdata->width ?>" /><br/>
            <?php print_string('height', 'brainstorm') ?>: <input type="text" name="config_width" size="10" value="<?php echo $currentoperator->configdata->width ?>" />
            <?php helpbutton('operatorrouter.html&amp;operator=locate&amp;helpitem=displaysize', get_string('displaysize', 'brainstorm'), 'brainstorm'); ?>
        </td>
    </tr>
    </tr>
        <td align="left"><b><?php print_string('showlabels', 'brainstorm') ?>:</b></td>
        <td align="right">
            <input type="radio" name="config_showlabels" value="0" <?php echo $noselected ?> /> <?php print_string('no') ?>&nbsp;-&nbsp;
            <input type="radio" name="config_showlabels" value="1" <?php echo $yesselected ?> /> <?php print_string('yes') ?>
            <?php helpbutton('operatorrouter.html&amp;operator=locate&amp;helpitem=showlabels', get_string('showlabels', 'brainstorm'), 'brainstorm'); ?>
        </td>
    </tr>
</table>
</fieldset>
<?php
}
else{
// not very secure, but might be enough for the security context we are in
?>
<input type="hidden" name="config_width" value="<?php echo $currentoperator->configdata->width ?>" />
<input type="hidden" name="config_height" value="<?php echo $currentoperator->configdata->height ?>" />
<input type="hidden" name="config_showlabels" value="<?php echo $currentoperator->configdata->showlabels ?>" />
<?php
}
?>
<table width="80%" cellspacing="5">
    <tr>
        <td colspan="2">
            <br/><input type="submit" name="go_btn" value="<?php print_string('saveconfig', 'brainstorm') ?>" />
        </td>
    </tr>
</table>
</form>
</center>
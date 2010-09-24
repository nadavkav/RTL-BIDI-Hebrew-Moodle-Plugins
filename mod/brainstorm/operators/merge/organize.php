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

$current_operator = new BrainstormOperator($brainstorm->id, $page);
$responsesnum = brainstorm_count_responses($brainstorm->id, 0, $currentgroup, false);
if (!isset($current_operator->configdata->maxideasleft)){
    $current_operator->configdata->maxideasleft = $responsesnum;
    notice(get_string('filterlimitundefined', 'brainstorm'));
    
}

$unassignedresponses = merge_get_unassigned($brainstorm->id, 0, $currentgroup, false, $current_operator->configdata);
$assignations = merge_get_assignations($brainstorm->id, null, $currentgroup, false, $current_operator->configdata);

/// get currently selected status for non data moving commands
$current_target = optional_param('to', null, PARAM_INT);

if(isset($nochecks)){
    $checks = array();
}
if (!isset($checks)){ // might have been setup within the controller
    $checks = array_keys(merge_get_dataset_from_query('choose_'));
}

$strsource = get_string('sourcedata', 'brainstorm');
$strmerge = get_string('mergedata', 'brainstorm');
$strchoice = get_string('choicedata', 'brainstorm');
$strmerged = get_string('mergeddata', 'brainstorm');

$toeliminate = max(0, $current_operator->configdata->maxideasleft - count($assignations));

print_heading("<img src=\"{$CFG->wwwroot}/mod/brainstorm/operators/{$page}/pix/enabled_small.gif\" align=\"left\" width=\"40\" /> " . get_string("organizing$page", 'brainstorm'));
?>
<center>
<?php
$intro = (isset($current_operator->configdata->requirement)) ? $current_operator->configdata->requirement . '<br/>' : '' ;
$intro .= "<span id=\"leftcount\">".$toeliminate.'</span> '. get_string('responsestokeep', 'brainstorm');
print_simple_box($intro);
print_simple_box_start('center');
?>
<form name="mergeform" method="post" action="view.php">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="operator" value="<?php p($page) ?>" />
<input type="hidden" name="what" value="savemerges" />
<input type="hidden" name="unassigned" value="" />
<style>
.selection{ background-color : #A0A0A0 ; height : 80px}
.unassignedslot {background-color : #E0E0E0 }
.assignedslot {background-color : #F8F8F8 ; border : 1px solid #404040 }
</style>
<script type="text/javascript">
function choosethis(ix, text){
    document.forms['mergeform'].elements['merge_'+ix].value = text;
}

function setcustom(ix){
    numradio = document.forms['mergeform'].elements['choice_'+ix].length;
    document.forms['mergeform'].elements['choice_'+ix][numradio - 1].checked = true;
}
</script>
<table width="80%" cellspacing="5">
    <tr>
        <th>
            <?php echo $strsource ?>
        </th>
        <th>
        </th>
        <th>
            <?php echo $strmerge ?>
        </th>    
    <tr>
        <td>
            <table cellspacing="5">
<?php
foreach($unassignedresponses as $response){
    $checked = (in_array($response->id, $checks)) ? 'checked="checked"' : '' ;
?>
                <tr valign="middle">
                    <td align="left">
                        <?php echo $response->response ?>
                    </td>
                    <td align="right" class="selection">
                        <input type="checkbox" name="choose_<?php p($response->id); ?>" value="1" <?php echo $checked; ?> />
                    </td>
                 </tr>
<?php
}
?>
            </table>
        </td>
        <td>
            <input type="button" name="assign_btn" value=">>" onclick="document.forms['mergeform'].what.value = 'assign'; document.forms['mergeform'].submit();" /><br/>
        </td>
        <td>
            <table cellspacing="5">
                <tr>
                    <th>&nbsp;</th>
                    <th>
                        <?php echo $strchoice ?>
                    </th>
                    <th>&nbsp;</th>
                    <th>
                        <?php echo $strmerged ?>
                    </th>
                </tr>
<?php
for($i = 0 ; $i < $current_operator->configdata->maxideasleft ; $i++){
    $mergedvalue = '';
    $checked = ($i == $current_target) ? 'checked="checked" ' : '' ;
?>
                <tr>
                    <td>
                        <input type="radio" name="to" value="<?php p($i) ?>" <?php echo $checked ?>  class="selection" /><br/>
                        <br/>
                        <input type="button" name="unassign_btn" value="<<" onclick="document.forms['mergeform'].what.value = 'unassign'; document.forms['mergeform'].unassigned.value = <?php p($i) ?>; document.forms['mergeform'].submit();" />
                    </td>
                    <td class="<?php echo (@$assignations[$i]) ? 'assignedslot' : 'unassignedslot' ; ?>">
                        <table cellspacing="5">
<?php
    $choosed = false;
    if (@$assignations[$i]){
        foreach($assignations[$i] as $response){
            $checked = ($response->choosed) ? 'checked="checked"' : '' ;
            $choosed &= $checked;
            if ($checked && $response->merged) $mergedvalue = $response->merged;
?>
                            <tr>
                                <td>
                                    <input type="radio" name="choice_<?php p($i) ?>" value="<?php p($response->id) ?>" <?php echo $checked ?>  onclick="choosethis('<?php p($i) ?>', '<?php echo addslashes($response->response) ?>')" />                        
                                </td>
                                <td align="left">    
                                    "<?php echo $response->response ?>"
                                </td>
                            </tr>
<?php
        }
        $response = merge_get_customentries($brainstorm->id, $i, null, $currentgroup, false);
        $customchecked = '';
        if ($response){
            $customrecords = array_values($response);
            // watch the subtility of the === operator here.
            $customchecked = ($customrecords[0]->itemdest === '0') ? ' checked="checked" ' : '' ;
            $mergedvalue = (!empty($customchecked)) ? $customrecords[0]->blobvalue : '' ;
        }
?>
                            <tr>
                                <td>
                                    <input type="radio" name="choice_<?php p($i) ?>" value="0" <?php echo $customchecked ?> />                        
                                </td>
                                <td align="left">
                                    <?php print_string('custommerge', 'brainstorm') ?>
                                </td>
                            </tr>
<?php
    }
?>
                        </table>
                    </td>
                    <td>
                        <img src="<?php echo $CFG->wwwroot ?>/mod/brainstorm/operators/merge/pix/mergeop.gif" height="100%" style="height:100%">
                    </td>
                    <td class="<?php echo (@$assignations[$i]) ? 'assignedslot' : 'unassignedslot' ; ?>">
                        &nbsp;<b><?php p($i + 1) ?></b> <input type="text" name="merge_<?php p($i) ?>" value="<?php echo $mergedvalue ?>" onchange="setcustom('<?php p($i) ?>')" />
                    </td>
                </tr>
<?php
}
?>
            </table>
        </td>
    </tr>    
    <tr>
        <td colspan="3">
            <input type="submit" id="go1" name="save_btn" value="<?php print_string('savemerges', 'brainstorm') ?>" />
<?php
if ($current_operator->configdata->allowreducesource){
?>
            &nbsp;<input type="button" id="go2" name="reduce_btn" value="<?php print_string('saveandreduce', 'brainstorm') ?>" onclick="document.forms['mergeform'].what.value='saveandreduce';document.forms['mergeform'].submit();" />
<?php
}
?>
        </td>
    </tr>
</table>
</form>
<?php
print_simple_box_end();
?>
</center>
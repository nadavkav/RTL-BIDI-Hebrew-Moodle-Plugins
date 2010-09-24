<?php

/**
* Module Brainstorm V2
* Operator : filter
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/
include_once ($CFG->dirroot."/mod/brainstorm/operators/{$page}/locallib.php");
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");
?>
<center>
<?php
print_heading("<img src=\"{$CFG->wwwroot}/mod/brainstorm/operators/{$page}/pix/enabled_small.gif\" align=\"left\" width=\"40\" /> " . get_string("organizing$page", 'brainstorm'));

$responses = brainstorm_get_responses($brainstorm->id, 0, 0);
if (!isset($current_operator)) // if was not set by a controller
    $current_operator = new BrainstormOperator($brainstorm->id, $page);
$filterstatus = filter_get_status($brainstorm->id);

/// module seems it is not configured
if (!isset($current_operator->configdata->maxideasleft)){
    print_simple_box(get_string('notconfigured', 'brainstorm'));
    return;
}

/// print organizing interface
$toeliminate = max(0, count($responses) - $current_operator->configdata->maxideasleft);
print_simple_box(get_string('responses', 'brainstorm')." <span id=\"leftcount\">$toeliminate</span>".' '.get_string('responsestoeliminate', 'brainstorm'));
if (isset($current_operator->configdata->requirement))
    print_simple_box($current_operator->configdata->requirement);
?>
<form name="filterform" method="post" action="view.php">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="operator" value="<?php p($page) ?>" />
<input type="hidden" name="what" value="savefiltering" />
<style>
.kept{background-color : #EAEAEA ; color : #414141}
.deleted{background-color : #C0C0C0 ; color : #808080 }
</style>
<script type="text/javascript">
var maxleft = <?php echo 0 + $current_operator->configdata->maxideasleft ?>;
var total = <?php echo 0 + count($responses) ?>;
var checks = new Array();

function initChecks(){
    for(i = 0 ; i < total ; i++){
        checkbox = document.getElementById('sel_' + i);
        if (checkbox.checked){
            checks[i] = 1;
        }
        else{
            checks[i] = 0;
        }
    }
}

function countchecks(){
    checkcount = 0;
    for(i = 0 ; i < total ; i++){
        checkcount += checks[i];
    }
    return checkcount;
}

function toggleCheck(checkobj, ix){
    if (checkobj.checked == true){
        checks[ix] = 1;
        obj = document.getElementById('tdc_' + ix);
        obj.className = 'kept';
        obj = document.getElementById('tdr_' + ix);
        obj.className = 'kept';
    }
    else{
        checks[ix] = 0;
        obj = document.getElementById('tdc_' + ix);
        obj.className = 'deleted';
        obj = document.getElementById('tdr_' + ix);
        obj.className = 'deleted';
    }
    initChecksStates();
}

function initChecksStates(){
    initChecks();
    realleft = countchecks();
    spanobj = document.getElementById('leftcount');
    spanobj.innerHTML = '' + realleft - maxleft;
    gobuttonobj = document.getElementById('go1');
    reducebuttonobj = document.getElementById('go2');
    if (realleft <= maxleft){
<?php
if (!@$current_operator->configdata->candeletemore){
?>
        lockChecked();
<?php
}
?>
        gobuttonobj.disabled = false;
        if (reducebuttonobj)
            reducebuttonobj.disabled = false;
    }
    else{
        unlockAll();
        gobuttonobj.disabled = true;
        if (reducebuttonobj)
          reducebuttonobj.disabled = true;
    }
}

function lockChecked(){
    for(i = 0 ; i < total ; i++){
        checkbox = document.getElementById('sel_' + i);
        shadow = document.getElementById('shadow_' + i);
        if (checkbox.checked){
            checkbox.disabled = true;
            shadow.value = 1 ;
        }
        else{
            shadow.value = 0 ;
        }
    }
}

function unlockAll(){
    for(i = 0 ; i < total ; i++){
        checkbox = document.getElementById('sel_' + i);
        checkbox.disabled = false;
        shadow = document.getElementById('shadow_' + i);
        if (checkbox.checked){
            shadow.value = 1 ;
        }
        else{
            shadow.value = 0 ;
        }
    }
}

</script>
<table width="80%" cellspacing="5">    
<?php
$i = 0;
foreach($responses as $response){
    $checked = (@$filterstatus[$response->id]->intvalue || empty($filterstatus)) ? "checked=\"checked\"" : '' ;
    $class = (@$filterstatus[$response->id]->intvalue || empty($filterstatus)) ? "kept" : "deleted" ;
?>
    <tr valign="top">
        <td align="right" class="<?php echo $class ?>" id="tdc_<?php echo $i?>">
            <input type="checkbox" id="sel_<?php echo $i ?>" name="keep_<?php p($response->id); ?>" value="1" <?php echo $checked ?> onclick="toggleCheck(this, <?php echo $i ?>)" />
            <input type="hidden" id="shadow_<?php echo $i ?>" name="keep_shadow_<?php p($response->id); ?>" value="" />
        </td>
        <td align="left" class="<?php echo $class ?>" id="tdr_<?php echo $i?>">
            <?php echo $response->response ?>
        </td>
     </tr>
<?php
    $i++;
}
?>
    <tr>
        <td colspan="2">
            <input type="submit" id="go1" name="go_btn" value="<?php print_string('saveordering', 'brainstorm') ?>" />
            &nbsp;<input type="button" name="startproc_btn" value="<?php print_string('startpaircompare', 'brainstorm') ?>" onclick="confirmprocedure();" />
            <script language="">
                function confirmprocedure(){
                    if (confirm("<?php print_string('confirmpaircompare', 'brainstorm') ?>")){
                        document.forms['filterform'].what.value='startpaircompare';
                        document.forms['filterform'].submit();                
                    }
                }
            </script>
<?php
if ($current_operator->configdata->allowreducesource){
?>
            &nbsp;<input type="button" id="go2" name="reduce_btn" value="<?php print_string('saveorderingandreduce', 'brainstorm') ?>" onclick="document.forms['filterform'].what.value='saveandreduce';document.forms['filterform'].submit();" />
<?php
}
?>
        </td>
    </tr>
</table>
</form>
<script type="text/javascript">
initChecksStates();
</script>
</center>
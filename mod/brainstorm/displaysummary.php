<?php

/**
* Module Brainstorm V2
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/

$allresponses = brainstorm_count_responses($brainstorm->id, 0, 0);
$responsesinyourgroup = brainstorm_count_responses($brainstorm->id, 0, $currentgroup);
$lang = current_language();
$alloperatordata = brainstorm_count_operatorinputs($brainstorm->id, 0, 0);
$operatordatainyourgroup = brainstorm_count_operatorinputs($brainstorm->id, 0, $currentgroup);
?>
<div style="padding-left : 200px">
<?php 
include "lang/{$lang}/displayresults.html";

echo '<p>';
if ($groupmode == VISIBLEGROUPS || has_capability('mod/brainstorm:manage', $context)){
    echo '<b>'.get_string('responsesinallgroups', 'brainstorm'). ':</b> '.$allresponses.'<br/>';
}
if ($groupmode){
    echo '<b>'.get_string('responsesinyourgroup', 'brainstorm'). ':</b> '.$responsesinyourgroup.'<br/>';
}
else{
    echo '<b>'.get_string('allresponses', 'brainstorm'). ':</b> '.$responsesinyourgroup.'<br/>';
}
if ($groupmode == VISIBLEGROUPS || has_capability('mod/brainstorm:manage', $context)){
    echo '<b>'.get_string('opdatainallgroups', 'brainstorm'). ':</b> '.$alloperatordata.'<br/>';
}
if ($groupmode){
    echo '<b>'.get_string('opdatainyourgroup', 'brainstorm'). ':</b> '.$operatordatainyourgroup.'<br/>';
}
else{
    echo '<b>'.get_string('allopdata', 'brainstorm'). ':</b> '.$operatordatainyourgroup.'<br/>';
}
echo '</p>';
?>
</div>


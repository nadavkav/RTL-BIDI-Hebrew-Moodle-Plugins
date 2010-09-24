<?php

/**
* Module Brainstorm V2
* Operator : order
* @author Valery Fremaux
* @package Brainstorm
* @date 20/12/2007
*/
include_once ($CFG->dirroot."/mod/brainstorm/operators/{$page}/locallib.php");
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");

if (!isset($current_operator)){
    $current_operator = new BrainstormOperator($brainstorm->id, $page);
}

$responses = brainstorm_get_responses($brainstorm->id, 0, $currentgroup, false, $sort = 'timemodified,id');
$responses = hierarchize_refresh_tree($brainstorm->id, $currentgroup);
$tree = hierarchize_get_childs($brainstorm->id, null, $currentgroup, false, 0);

print_heading("<img src=\"{$CFG->wwwroot}/mod/brainstorm/operators/{$page}/pix/enabled_small.gif\" align=\"left\" width=\"40\" /> " . get_string('tree', 'brainstorm'));
?>
<center>
<?php
if (isset($current_operator->configdata->requirement))
    print_simple_box($current_operator->configdata->requirement);
?>
<style>
.response{ border : 1px solid gray ; background-color : #E1E1E1 }
</style>
<form name="treeform" action="view.php" method="POST">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="operator" value="<?php p($page) ?>" />
<input type="hidden" name="what" value="maketree" />
<table width="80%" cellspacing="5">
<?php
if ($tree){
    $i = 0;
    $indent = 25;
    $level = 1;
    $subscount = 0;
    foreach($tree as $child){
        $prefix = $i + 1;
        $up = ($i) ? "<a href=\"view.php?id={$cm->id}&amp;operator=hierarchize&amp;what=up&amp;item={$child->odid}\"><img src=\"{$CFG->pixpath}/t/up.gif\"></a>" : '&nbsp;' ;
        $down = ($i < count($tree) - 1) ? "<a href=\"view.php?id={$cm->id}&amp;operator=hierarchize&amp;what=down&amp;item={$child->odid}\"><img src=\"{$CFG->pixpath}/t/down.gif\"></a>" : '&nbsp;' ;
        $left = ($indent > 25) ? "<a href=\"view.php?id={$cm->id}&amp;operator=hierarchize&amp;what=left&amp;item={$child->odid}\"><img src=\"{$CFG->pixpath}/t/left.gif\"></a>" : '&nbsp;' ;
        if ((@$current_operator->configdata->maxarity && $subscount >= $current_operator->configdata->maxarity) || (@$current_operator->configdata->maxlevels && $level > $current_operator->configdata->maxlevels)){
            $right = '';
        }
        else{
            $right = ($i) ? "<a href=\"view.php?id={$cm->id}&amp;operator=hierarchize&amp;what=right&amp;item={$child->odid}\"><img src=\"{$CFG->pixpath}/t/right.gif\"></a>" : '&nbsp;' ;
        }
?>
                <tr>
                    <td>
                        <table cellspacing="3">
                            <tr>
                                <td width="10">
                                    <?php echo $left ?>
                                 </td>
                                <td width="10">
                                    <?php echo $up ?>
                                 </td>
                                <td width="10">
                                    <?php echo $down ?>
                                 </td>
                                <td width="10">
                                    <?php echo $right ?>
                                 </td>
                             </tr>
                         </table>
                    </td>
                    <td style="text-align : right; padding-right: <?php echo ($indent - 23) ?>px" class="response">
                        <b><?php echo $prefix ?>.</b> <?php echo $child->response ?>
                    </td>
                </tr>
<?php
        hierarchize_print_level($brainstorm->id, $cm, null, $currentgroup, false, $child->odid, $i+1, $indent, $current_operator->configdata);
        $i++;
        $subscount = brainstorm_count_subs($child->odid); // get subs status of previous entry
    }
?>
    <tr>
        <td colspan="2">
            <br/><input type="button" name="clear_btn" value="<?php print_string('clearalldata', 'brainstorm') ?>" onclick="document.forms['treeform'].what.value='clearall';document.forms['treeform'].submit();" />
        </td>
    </tr>
<?php
}
else{
    echo '<tr><td>';
    print_simple_box_start('center');
    print_string('notreeset', 'brainstorm');
?>

    <p><center><input type="submit" name="go_btn" value="<?php print_string('maketree', 'brainstorm') ?>" /></center></p>
<?php
    print_simple_box_end();
    echo '</td></tr>';
}
?>
</table>
</form>
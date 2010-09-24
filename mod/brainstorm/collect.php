<?php

/**
* @package brainstorm
* @author Martin Ellermann
* @review Valery Fremaux / 1.8
* @date 22/12/2007
*
* This page shows view for collecting interface. View may change whether 
* we are 'teacher' or 'student'. 
* 
* If we are student we have following states :
* * We work in parallel mode OR we are in sequential and this phase is allowed to students
* - recollection is closed. We can see collected ideas depending on group and
* privacy switch
* - recollection is open.
*    - we did not yet provide ideas and work in privacy against other users : we are inclined to 
*             and rerouted to input form
*    - we did not yet provide ideas and work in collaboration with other users : we see ideas of the group
*             and a call to add own.
*    - we provided ideas : we can see them and add more if max number of imputs has not been reached
*
* Teacher view
* teacher view is sightly different, because he will have a monitoring capbility
*/

/// get viewable responses
$myresponses = brainstorm_get_responses($brainstorm->id, $USER->id);

if (has_capability('mod/brainstorm:manage', $context)){
    $otherresponses = brainstorm_get_responses($brainstorm->id, 0, $currentgroup, true);
}
else{
    if ($groupmode && $currentgroup){
        $otherresponses = brainstorm_get_responses($brainstorm->id, 0, $currentgroup, true);
    }
    else if ($groupmode == 0 && !$brainstorm->privacy){
        $otherresponses = brainstorm_get_responses($brainstorm->id, 0, 0, true);
    }
    else{
        $otherresponses = array();
    }
}

/// Just display responses, sorted in alphabetical order
print_heading(get_string('collectingideas', 'brainstorm'));
print_simple_box_start('center');
if (!empty($myresponses) || !empty($otherresponses)){
    if (has_capability('mod/brainstorm:manage', $context)){
?>
<form name="deleteform" method="post">
<input type="hidden" name="id" value="<?php p($cm->id)?>" />
<input type="hidden" name="what" value="deleteitems" />
<?php
    }
?>
<p><table align="center" width="80%">
    <tr>
        <td colspan="<?php echo $brainstorm->numcolumns * 2 ?>">
            <?php print_heading(get_string('myresponses', 'brainstorm')) ?>
        </td>
    </tr>
    <tr valign="top">
<?php
    brainstorm_print_responses_cols($brainstorm, $myresponses, false, has_capability('mod/brainstorm:manage', $context));
?>
    </tr>
</table></p>
<?php
    if (!$brainstorm->privacy){
?>
<p>
<table align="center" width="80%">
    <tr>
        <td colspan="<?php echo $brainstorm->numcolumns ?>">
            <?php print_heading(get_string('otherresponses', 'brainstorm')) ?>
        </td>
    </tr>
    <tr>
<?php
    $index = 0;
    foreach ($otherresponses as $response){
        $deletecheckbox = (has_capability('mod/brainstorm:manage', $context)) ? "<input type=\"checkbox\" name=\"items[]\" value=\"{$response->id}\" /> " : '' ;
        if ($index && $index % $brainstorm->numcolumns == 0){
            echo '</tr><tr>';
        }
        echo '<th>' . ($index+1) . '</th>';
        echo '<td>' . $deletecheckbox.$response->response . '</td>';
        $index++;
    }
?>
    </tr>
</table></p>
<?php
    if (has_capability('mod/brainstorm:manage', $context)) echo '</form>';
    }
}
else{
    print_string('notresponded', 'brainstorm');
}

/// now we check if we need fetching more responses
/*
* We should get more responses if :
*    We have not reached max responses required && there is limitation AND
*    Collecting phase is not over for us (timed or manually switched)
*/

if ((($brainstorm->flowmode == 'parallel' || $brainstorm->phase == PHASE_COLLECT) and
     (($brainstorm->numresponses > count($myresponses)) || $brainstorm->numresponses == 0)) or has_capability('mod/brainstorm:manage', $context)){
    echo '<br/><center><table><tr>';
    // $options = array ('id' => "$cm->id", 'view' => 'collect', 'what' => 'collect');
    // guest froup not member should not interfer here, although he could se our ideas.
    if (!$groupmode or groups_is_member($currentgroup) or has_capability('mod/brainstorm:manage', $context)){
?>
    <td>
        <form action="view.php" method="post" name="collect">
        <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
        <input type="hidden" name="view" value="collect" />
        <input type="hidden" name="what" value="collect" />
        <input type="submit" name="go_btn" value="<?php print_string('addmoreresponses','brainstorm') ?>" />
        </form>
    </td>
<?php
    }
    if (has_capability('mod/brainstorm:manage', $context)){
?>
    <td>
        <form action="view.php" method="post" name="collect">
        <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
        <input type="hidden" name="view" value="collect" />
        <input type="hidden" name="what" value="clearall" />
        <input type="submit" name="go_btn" value="<?php print_string('clearall','brainstorm') ?>" />
        </form>
    </td>
    <td>
        <input type="button" name="deleteitems_btn" value="<?php print_string('deleteselection','brainstorm') ?>" onclick="document.forms['deleteform'].submit();" />
    </td>
<?php
    }
    if (has_capability('mod/brainstorm:import', $context)){
?>
    <td>
        <form action="view.php" method="post" name="collect">
        <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
        <input type="hidden" name="view" value="collect" />
        <input type="hidden" name="what" value="import" />
        <input type="submit" name="go_btn" value="<?php print_string('importideas','brainstorm') ?>" />
        </form>
    </td>
<?php
    }
    echo '</tr></table>';
}
print_simple_box_end();
?>
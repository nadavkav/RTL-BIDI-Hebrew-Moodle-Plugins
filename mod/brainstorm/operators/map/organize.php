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

print_heading("<img src=\"{$CFG->wwwroot}/mod/brainstorm/operators/{$page}/pix/enabled_small.gif\" align=\"left\" width=\"40\" /> " . get_string("organizing$page", 'brainstorm'));

$responses = brainstorm_get_responses($brainstorm->id, 0, 0);
if (count($responses) > $MAP_MAX_DATA){
    notice(get_string('toomuchdata', 'brainstorm', $MAP_MAX_DATA));
    return;
}

if (!isset($current_operator)){
    $current_operator = new BrainstormOperator($brainstorm->id, $page);
}
$map = map_get_cells($brainstorm->id, $USER->id, $currentgroup, $current_operator->configdata);
?>
<center>
<?php
if (isset($current_operator->configdata->requirement))
    print_simple_box($current_operator->configdata->requirement);
?>
<form name="mapform" method="post" action="view.php">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="operator" value="<?php p($page) ?>"/>
<input type="hidden" name="what" value="savemappings" />
<style>
.maptable{border : 1px solid gray}
.maptablecell{border : 1px solid gray}
</style>
<center>
<table cellspacing="5">
<?php
if ($responses){
    $width = 100 / count($responses) + 2;
    $titlewidth = $width * 2;

    /// draw top title line
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
            if (!@$current_operator->configdata->quantified){
                $checked = (@$map[$responserow->id][$responsecol->id]) ? 'checked="checked"' : '' ;
                $mapcheck = "<input type=\"checkbox\" name=\"map_{$responserow->id}_{$responsecol->id}\" value=\"1\" $checked /> ";
                echo "<td width=\"{$width}%\" class=\"maptablecell\">$mapcheck</td>\n";
            }
            else{
                switch($current_operator->configdata->quantifiertype){
                    case 'multiple':
                        $itemdata = map_print_multiple_value(@$map[$responserow->id][$responsecol->id]);
                        if (!empty($itemdata)){
                            $itemdata .= '<br/>';
                            $maplink = "<a href=\"view.php?id={$cm->id}&amp;operator=map&amp;what=updatemultiple&amp;source={$responserow->id}&amp;dest={$responsecol->id}\"><img src=\"{$CFG->pixpath}/t/edit.gif\" /></a>";
                            $maplink .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;operator=map&amp;what=deletemultiple&amp;source={$responserow->id}&amp;dest={$responsecol->id}\"><img src=\"{$CFG->pixpath}/t/delete.gif\" /></a>";
                        }
                        else{
                            $maplink = "<a href=\"view.php?id={$cm->id}&amp;operator=map&amp;what=inputmultiple&amp;source={$responserow->id}&amp;dest={$responsecol->id}\">".get_string('inputdata', 'brainstorm').'</a>';
                        }
                        echo "<td width=\"{$width}%\" class=\"maptablecell\">{$itemdata}{$maplink}</td>\n";
                        break;                    
                    default:
                        $itemvalue = (isset($map[$responserow->id][$responsecol->id])) ? $map[$responserow->id][$responsecol->id] : '' ;
                        $mapinput = "<input type=\"text\" size=\"5\" name=\"map_{$responserow->id}_{$responsecol->id}\" value=\"{$itemvalue}\" /> ";
                        echo "<td width=\"{$width}%\" class=\"maptablecell\">$mapinput</td>\n";
                        break;
                }
            }
        }
        echo "</tr>\n";
    }
}
else{
    echo '<tr><td coslpan="3">';
    print_string('noresponses', 'brainstorm');
    echo '</td></tr>';
}
?>
    <tr>
        <td colspan="<?php echo count($responses) + 1; ?>">
            <br/><input type="submit" name="go_btn" value="<?php print_string('saveconnections', 'brainstorm') ?>" />
            &nbsp;<input type="button" name="clear_btn" value="<?php print_string('clearconnections', 'brainstorm') ?>" onclick="document.forms['mapform'].what.value='clearmappings';document.forms['mapform'].submit();" />
        </td>
    </tr>
</table>
</form>
</center>
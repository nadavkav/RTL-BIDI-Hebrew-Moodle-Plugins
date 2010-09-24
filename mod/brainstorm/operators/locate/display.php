<?php

/**
* Module Brainstorm V2
* @author Martin Ellermann
* @reengineering Valery Fremaux
* @package Brainstorm
* @date 20/12/2007
*/
include_once ($CFG->dirroot."/mod/brainstorm/operators/{$page}/locallib.php");
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");

$responses = brainstorm_get_responses($brainstorm->id, 0, $currentgroup, false);

$current_operator = new BrainstormOperator($brainstorm->id, $page);
$responses_bounds = locate_get_bounds($brainstorm->id);

if (!isset($current_operator->configdata->width)){
    $current_operator->configdata->width = 400;
}
if (!isset($current_operator->configdata->height)){
    $current_operator->configdata->height = 400;
}

$responses_locations = locate_get_locations($brainstorm->id, null, $currentgroup);
$w = $current_operator->configdata->width;
$h = $current_operator->configdata->height;

/// graphic coordinates converter
function g($absx, $absy, $configdata, $xshift=0, $yshift=0){
    $xabsoffset=0;
    $yabsoffset=0;
    $xfactor = ($configdata->width / ($configdata->xmaxrange - $configdata->xminrange));
    $yfactor = ($configdata->height / ($configdata->ymaxrange - $configdata->yminrange));
    $p->x = $xfactor * ($absx - $configdata->xminrange) + $xabsoffset + $xshift;
    $p->y = $configdata->height - $yfactor * ($absy - $configdata->yminrange) + $yabsoffset + $yshift;
    return $p;
}

?>
<style>
.spot { background-color : #5CCA59 ; border : 1px solid #217218 }
.spot1 { background-color : #FFFF00 ; border : 1px solid #968612 }
.axis { background-color : #091C09 }
.inbounds { background-color : #68FA58 ; border : 1px solid #008000 ; opacity : .5 ; filter: alpha(opacity=50)}
.outbounds { background-color : #FF8080 ; border : 1px solid #A00707 ; opacity : .5 ; filter: alpha(opacity=50)}
</style>
<script type="text/javascript">
function showbounds(id){
    obj = document.getElementById('bounds_'.id);
    obj.style.visibility = "visible";
}

function hidebounds(id){
    obj = document.getElementById('bounds_'.id);
    obj.style.visibility = "hidden";
}
</script>
<center>
<?php
if (isset($current_operator->configdata->xminrange)){
    echo "<div style=\"width: {$w}px; height: {$h}px; right: 0px; position: relative ; text-align : right\">";
    $pleft = g($current_operator->configdata->xminrange, 0, $current_operator->configdata);
    $ptop = g(0, $current_operator->configdata->xmaxrange, $current_operator->configdata);
    echo "<div class=\"axis\" style=\"position:absolute; right: {$pleft->x}px; top: {$pleft->y}px; width: {$w}px; height: 1px;\"></div>";
    echo "<div class=\"axis\" style=\"position:absolute; right: {$ptop->x}px; top: {$ptop->y}px; width: 1px; height: {$h}px;\"></div>";
    if ($responses_locations){
        $i = 0;
        if (!empty($responses_bounds->min) || !empty($responses_bounds->max)){
            foreach($responses_locations as $located){
                $abs->x  = $responses_bounds->min[$located->itemsource]['x'];
                $abs->y  = $responses_bounds->max[$located->itemsource]['y'];
                $size->x  = $responses_bounds->max[$located->itemsource]['x'] - $responses_bounds->min[$located->itemsource]['x'];
                $size->y  = $responses_bounds->max[$located->itemsource]['y'] - $responses_bounds->min[$located->itemsource]['y'];
                if ($size->x < 10 || $size->x < 10) continue ;
                $p = g($abs->x, $abs->y, $current_operator->configdata);
                $d = g($size->x, $size->y, $current_operator->configdata);
                echo "<div class=\"inbounds\" style=\"position:absolute; right: {$p->x}px; top: {$p->y}px; width: {$d->x}px; height: {$d->y}px;\" title=\"({$abs->x},{$abs->y}) {$responses[$located->itemsource]->response}\"></div>";
            }
        }
        foreach($responses_locations as $located){
            $spot = 'spot';
            $abs = unserialize($located->blobvalue);
            $p = g($abs->x, $abs->y, $current_operator->configdata,0,-15);
            echo "<div class=\"$spot\" style=\"position:absolute; right: {$p->x}px; top: {$p->y}px; width: 15px; height: 15px;\" title=\"({$abs->x},{$abs->y}) {$responses[$located->itemsource]->response}\"></div>";
            if (@$current_operator->configdata->showlabels){
                $p->x += 20 + rand(-20,20);
                $p->y += 20 + rand(-20,20);
                echo "<div style=\"position:absolute; right: {$p->x}px; top: {$p->y}px;\" >{$responses[$located->itemsource]->response}</div>";
            }
            $i++;
        }
    }
}
else{
    print_simple_box(get_string('notconfigured', 'brainstorm'));
}
?>
</div>
</center>



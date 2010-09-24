<?php

/**
* Module Brainstorm V2
* Operator : locate
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/

/**
*
*
*/
function locate_get_locations($brainstormid, $userid=null, $groupid=0, $excludemyself=false){
    global $CFG, $USER;
    
    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);
     
    $select = "
        brainstormid = {$brainstormid} AND
        operatorid = 'locate'
        $accessClause
    ";
    if (!$locations = get_records_select('brainstorm_operatordata AS od', $select, '', 'itemsource,blobvalue')){
        $locations = array();
    }
    return $locations;
}

/**
*
*
*/
function locate_get_means($brainstormid, $userid=null, $groupid=0, $excludemyself=false){
    global $CFG;
    
    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);

    $select = "
        brainstormid = {$brainstormid} AND
        operatorid = 'locate'
        {$accessClause}
    ";
    if (!$locations = get_records_select('brainstorm_operatordata AS od', $select, '', 'itemsource,blobvalue')){
        $locations = array();
    }

    $means = array();
    $sigmas = array();
    $locationdatas = array();
    
    /// calculate mean
    foreach ($locations as $responseid => $locationblob){
        $locationdata = unserialize($locationblob->blobvalue);
        $locationdatas[$responseid][] = $locationdata;
        $means[$responseid]['x'] = @$means[$responseid]['x'] + $locationdata->x;
        $means[$responseid]['y'] = @$means[$responseid]['y'] + $locationdata->y;
    }

    /// calculate sigmas square sums
    foreach (array_keys($locationdatas) as $responseid){
        foreach($locationdatas[$responseid] as $asample){
            $deltax = $asample->x - $means[$responseid]['x'];
            $deltay = $asample->y - $means[$responseid]['y'];
            $sigmasum[$responseid]['x'] = @$sigmasum[$responseid]['x'] + $deltax * $deltax;
            $sigmasum[$responseid]['y'] = @$sigmasum[$responseid]['y'] + $deltay * $deltay;
            $sigmasum[$responseid]['n'] = @$sigmasum[$responseid]['n'] + 1; // sample count
        }
    }

    /// calculate sigmas
    foreach (array_keys($locationdatas) as $responseid){
        $sigmas[$responseid]['x'] = sqrt($sigmasum[$responseid]['x'] / $sigmasum[$responseid]['n']);
        $sigmas[$responseid]['y'] = sqrt($sigmasum[$responseid]['y'] / $sigmasum[$responseid]['n']);
    }
    $result->mean = &$means;
    $result->sigma = &$sigmas;
}

/**
* calculates bounds of record set given for any responses
* @param int $brainstormid
* @param int $userid
* @param int $groupid
*/
function locate_get_bounds($brainstormid, $userid=null, $groupid=0, $excludemyself=false){
    global $CFG;
    
    $operator = new BrainstormOperator($brainstormid, 'locate');
    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);

    $select = "
        brainstormid = {$brainstormid} AND
        operatorid = 'locate'
        {$accessClause}
    ";
    if (!$locations = get_records_select('brainstorm_operatordata AS od', $select, '', 'itemsource,blobvalue')){
        $locations = array();
    }

    $maxs = array();
    $mins = array();
    
    /// calculate bounds
    foreach ($locations as $responseid => $locationblob){
        $locationdata = unserialize($locationblob->blobvalue);
        // $locationdatas[$responseid][] = $locationdata;
        if (!isset($maxs[$responseid]['x'])) $maxs[$responseid]['x'] = $operator->configdata->xminrange;
        if (!isset($mins[$responseid]['x'])) $mins[$responseid]['x'] = $operator->configdata->xmaxrange;
        if (!isset($maxs[$responseid]['y'])) $maxs[$responseid]['y'] = $operator->configdata->yminrange;
        if (!isset($mins[$responseid]['y'])) $mins[$responseid]['y'] = $operator->configdata->ymaxrange;
        $maxs[$responseid]['x'] = ($maxs[$responseid]['x'] - $locationdata->x < 0) ? $locationdata->x : $maxs[$responseid]['x'] ;
        $mins[$responseid]['x'] = ($mins[$responseid]['x'] - $locationdata->x > 0) ? $locationdata->x : $mins[$responseid]['x'] ;
        $maxs[$responseid]['y'] = ($maxs[$responseid]['y'] - $locationdata->y < 0) ? $locationdata->y : $maxs[$responseid]['y'] ;
        $mins[$responseid]['y'] = ($mins[$responseid]['y'] - $locationdata->y < 0) ? $locationdata->y : $mins[$responseid]['y'] ;
    }
    $result->max = &$maxs;
    $result->min = &$mins;
    return $result;
}

/**
*
*
*/
function locate_display(&$brainstorm, $userid, $groupid){

    $responses = brainstorm_get_responses($brainstorm->id, 0, $groupid, false);
    $responses_locations = locate_get_locations($brainstorm->id, $userid, $groupid);
    $current_operator = new BrainstormOperator($brainstorm->id, 'locate');
    $current_operator->configdata->width = $w = 200;
    $current_operator->configdata->height = $h = 200;

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
</style>
<center>
<?php
    if (!isset($current_operator->configdata->xminrange)){
        print_simple_box(get_string('notconfigured', 'brainstorm'));
    }
    else{
        echo "<div style=\"width: {$w}px; height: {$h}px; left: 0px; position: relative ; text-align : left\">";
        $pleft = g($current_operator->configdata->xminrange, 0, $current_operator->configdata);
        $ptop = g(0, $current_operator->configdata->xmaxrange, $current_operator->configdata);
        echo "<div class=\"axis\" style=\"position:absolute; left: {$pleft->x}px; top: {$pleft->y}px; width: {$w}px; height: 1px;\"></div>";
        echo "<div class=\"axis\" style=\"position:absolute; left: {$ptop->x}px; top: {$ptop->y}px; width: 1px; height: {$h}px;\"></div>";
        if ($responses_locations){
            $i = 0;
            foreach($responses_locations as $located){
                $spot = 'spot';
                $abs = unserialize($located->blobvalue);
                $p = g($abs->x, $abs->y, $current_operator->configdata,0,-15);
                echo "<div class=\"$spot\" style=\"position:absolute; left: {$p->x}px; top: {$p->y}px; width: 15px; height: 15px;\" title=\"({$abs->x},{$abs->y}) {$responses[$located->itemsource]->response}\"></div>";
                if (@$current_operator->configdata->showlabels){
                    $p->x += 20 + rand(-20,20);
                    $p->y += 20 + rand(-20,20);
                    echo "<div style=\"position:absolute; left: {$p->x}px; top: {$p->y}px;\" >{$responses[$located->itemsource]->response}</div>";
                }
                $i++;
            }    
        }
    }
?>
</div>
</center>
<?php
}
?>
<?php

/**
* Module Brainstorm V2
* Operator : categorize
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/
include_once ($CFG->dirroot."/mod/brainstorm/operators/{$page}/locallib.php");
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");

print_heading("<img src=\"{$CFG->wwwroot}/mod/brainstorm/operators/{$page}/pix/enabled_small.gif\" align=\"left\" width=\"40\" /> " . get_string("organizing$page", 'brainstorm'));
$categories = categorize_get_categories($brainstorm->id, 0, $currentgroup);
$categorization = categorize_get_categoriesperresponses($brainstorm->id, null, $currentgroup);
$responses = brainstorm_get_responses($brainstorm->id, 0, 0);
$current_operator = new BrainstormOperator($brainstorm->id, $page);

foreach($categories as $category){
  $category_menu[$category->id] = $category->title;
}

$matchgroup = (!$groupmode) ? 0 : $currentgroup ;
$matchings = categorize_get_matchings($brainstorm->id, $USER->id, $matchgroup);
$maxspan = 2;
?>
<center>
<?php
if (isset($current_operator->configdata->requirement))
    print_simple_box($current_operator->configdata->requirement);
?>
<form name="categorizationform" method="post" action="view.php">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="operator" value="<?php p($page) ?>"/>
<input type="hidden" name="what" value="savecategorization" />
<table width="90%" cellspacing="5">
<?php
if (count($responses)){
    $counts = array();
    foreach($responses as $response){
?>
    <tr valign="top">
        <td align="right">
            <?php echo $response->response ?>
        </td>
        <td align="left">
            <?php
            if (!@$current_operator->configdata->allowmultiple){
                $categoryvalue = (!empty($categorization[$response->id]->categories)) ? $categorization[$response->id]->categories[0] : 0 ;
                $counts[$categoryvalue] = 0 + @$counts[$categoryvalue] + 1;
                choose_from_menu($category_menu, 'cat_'.$response->id, $categoryvalue, 'choose', 'checkmaxrange(this)');            
            }
            else{
                choose_multiple_from_menu($category_menu, 'cat_'.$response->id.'[]', @$categorization[$response->id]->categories, 'choose', '',
                           '0', false, false, 0, '', round(count($categories) / 2));                        
            }
            ?>
        </td>
<?php
        if (!@$current_operator->configdata->blindness){
            $maxspan = 4;
?>
        <td align="left">
            <?php
            if (!empty($matchings->match)){
                if (array_key_exists($response->id, $matchings->match)){
                    if ($matchings->match[$response->id] == 1)
                        print_string('agreewithyousingle', 'brainstorm', $matchings->match[$response->id]);
                    else
                        print_string('agreewithyou', 'brainstorm', $matchings->match[$response->id]);
                }
            }
            ?>
        </td>
        <td align="left">
            <?php
            if (!empty($matchings->unmatch)){
                if (array_key_exists($response->id, $matchings->unmatch)){
                    if ($matchings->unmatch[$response->id] == 1)
                        print_string('disagreewithyousingle', 'brainstorm', $matchings->unmatch[$response->id]);
                    else
                        print_string('disagreewithyou', 'brainstorm', $matchings->unmatch[$response->id]);
                }
            }
            ?>
        </td>
<?php
        }
?>
    </tr>    
<?php
    }
}
?>
    <tr>
        <td colspan="<?php echo $maxspan ?>">
            <br/><input type="submit" name="go_btn" value="<?php print_string('savecategorization', 'brainstorm') ?>" />
        </td>
</table>
</form>
<script type="text/javascript">
// check for more than allowed items per category
<?php
if (!empty($current_operator->configdata->maxitemspercategory) && !@$currentoperator->configdata->allowmultiple){
?>
var responsekeys = '<?php echo implode(",", array_keys($responses)) ?>';

function countvalues(value){
    resplist = responsekeys.split(/,/);
    cnt = 0;
    for (respid in resplist){
        listobj = document.forms['categorizationform'].elements['cat_' + resplist[respid]];
        if (listobj.options[listobj.selectedIndex].value == value){
            cnt++;
        }
    }
    return cnt;
}

function checkmaxrange(listobj){
    if (countvalues(listobj.options[listobj.selectedIndex].value) > <?php echo $current_operator->configdata->maxitemspercategory ?>) {
       alert("<?php print_string('exceedspercategorylimit', 'brainstorm', 0 + @$current_operator->configdata->maxitemspercategory ) ?>");
       listobj.selectedIndex = 0; 
       listobj.focus();
    }
}
<?php
}
else{
?>
function checkmaxrange(listobj){
}
<?php
}
?>
</script>
</center>
<?php

/**
* @package mod-tracker
* @category mod
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* From for showing used element list
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/tracker
}

print_simple_box_start('center', '100%', '', '', 'generalbox', 'description');
print_simple_box_end();
print_simple_box_start('center', '100%', '', '', 'generalbox', 'description');
tracker_loadelementsused($tracker, $used);

print_heading(get_string('elementsused', 'tracker'));

$orderstr = get_string('order', 'tracker');
$namestr = get_string('name');
$typestr = get_string('type', 'tracker');
$cmdstr = get_string('action', 'tracker');

$table->head = array("<b>$orderstr</b>", "<b>$namestr</b>", "<b>$typestr</b>", "<b>$cmdstr</b>");
$table->width = 400;
$table->size = array(20, 250, 50, 100);
$table->align = array('left', 'center', 'center', 'center');

if (!empty($used)){
	foreach ($used as $element){
	    $icontype = "<img src=\"{$CFG->wwwroot}/mod/tracker/pix/types/{$element->type}.gif\" />";
	    if ($element->sortorder > 1){
    	    $actions = "<a href=\"view.php?id={$cm->id}&amp;what=raiseelement&amp;elementid={$element->id}\"><img src=\"{$CFG->pixpath}/t/up.gif\" /></a>";
    	} else {
    	    $actions = "<img src=\"{$CFG->wwwroot}/mod/tracker/pix/up_shadow.gif\" />";
    	}
    	if ($element->sortorder < count($used)){
    	    $actions .= "<a href=\"view.php?id={$cm->id}&amp;what=lowerelement&amp;elementid={$element->id}\"><img src=\"{$CFG->pixpath}/t/down.gif\" /></a>";
    	} else {
    	    $actions .= "<img src=\"{$CFG->wwwroot}/mod/tracker/pix/down_shadow.gif\" />";
    	}
	    $actions .= "<a href=\"view.php?id={$cm->id}&amp;what=editelement&amp;elementid={$element->id}\"><img src=\"{$CFG->pixpath}/t/edit.gif\" /></a>";
	    $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;what=viewelementoptions&amp;elementid={$element->id}\" title=\"".get_string('editoptions', 'tracker')."\"><img src=\"{$CFG->wwwroot}/mod/tracker/pix/editoptions.gif\" /></a>";
	    $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;what=removeelement&amp;usedid={$element->id}\"><img src=\"{$CFG->pixpath}/i/cross_red_small.gif\" /></a>";
        $table->data[] = array($element->sortorder, format_string($element->description), $icontype, $actions);
    }
    print_table($table);
} else {
    echo '<center>';
    print_string('noelements', 'tracker');
    echo '<br/></center>';
}

print_simple_box_end();

?>
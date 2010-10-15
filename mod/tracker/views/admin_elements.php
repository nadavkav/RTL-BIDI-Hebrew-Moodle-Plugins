<?php 

/**
* @package mod-tracker
* @category mod
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* From for showing element list
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/tracker
}

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // course ID	
print_simple_box_start('center', '100%', '', '', 'generalbox', 'description');
?>
<form name="addelement" method="post" action="view.php">
<table border="0" width="400">
	<tr>
		<td valign="top">
			<b><?php print_string('createnewelement', 'tracker') ?>:</b>
		</td>
		<td valign="top">
				<?php
					echo "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />";
					echo "<input type=\"hidden\" name=\"what\" value=\"createelement\" />";
		            $types = tracker_getelementtypes();
		            foreach($types as $type){
		                $elementtypesmenu[$type] = get_string($type, 'tracker');
		            }
		            choose_from_menu($elementtypesmenu, 'type', '', 'choose', 'document.forms[\'addelement\'].submit();');
				?>
		</td>
	</tr>
</table>
</form>

<?php
print_simple_box_end(); 
print_simple_box_start('center', '100%', '', '', 'generalbox', 'description');
tracker_loadelements($tracker, $elements);	
print_heading(get_string('elements', 'tracker'));

$localstr = get_string('local', 'tracker');
$namestr = get_string('name');
$typestr = get_string('type', 'tracker');
$cmdstr = get_string('action', 'tracker');

unset($table);
$table->head = array("<b>$cmdstr</b>", "<b>$namestr</b>", "<b>$localstr</b>", "<b>$typestr</b>");
$table->width = 400;
$table->size = array(100, 250, 50, 50);
$table->align = array('left', 'center', 'center', 'center'); 

if (!empty($elements)){
    /// clean list from used elements
    foreach($elements as $id => $element){
        if (in_array($element->id, array_keys($used))){
            unset($elements[$id]);
        }
    }
    
    /// make list
	foreach ($elements as $element){

		$name = format_string($element->description);
		$name .= '<br />';
		$name .= '<span style="font-size:70%">';
		$name .= $element->name;
		$name .= '</span>';
		if ($element->hasoptions() && empty($element->options)){
		    $name .= ' <span class="error">('.get_string('nooptions', 'tracker').')</span>';
		}
		
		$actions = "<a href=\"view.php?id={$cm->id}&amp;what=addelement&amp;elementid={$element->id}\" title=\"".get_string('addtothetracker', 'tracker')."\" ><img src=\"{$CFG->pixpath}/t/moveleft.gif\" /></a>";
        $actions .= "<a href=\"view.php?id={$cm->id}&amp;what=viewelementoptions&amp;elementid={$element->id}\" title=\"".get_string('editoptions', 'tracker')."\"><img src=\"{$CFG->wwwroot}/mod/tracker/pix/editoptions.gif\" /></a>";
        $actions .= "<a href=\"view.php?id={$cm->id}&amp;what=editelement&amp;elementid={$element->id}\" title=\"".get_string('editproperties', 'tracker')."\"><img src=\"{$CFG->pixpath}/t/edit.gif\" /></a>";
        $actions .= "<a href=\"view.php?id={$cm->id}&amp;what=deleteelement&amp;elementid={$element->id}\" title=\"".get_string('delete')."\"><img src=\"{$CFG->pixpath}/t/delete.gif\" /></a>";

        $local = '';
        if ($element->course == $COURSE->id){
    	    $local = "<img src=\"{$CFG->pixpath}/i/course.gif\" />";
    	}
		$type = "<img src=\"{$CFG->wwwroot}/mod/tracker/pix/types/{$element->type}.gif\" />";
		$table->data[] = array($actions, $name, $local, $type);
	}
	print_table($table);
} else {
    echo '<center>';
    print_string('noelements', 'tracker');
    echo '<br /></center>';
}
print_simple_box_end(); 
?>
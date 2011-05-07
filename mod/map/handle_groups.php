<?php
/**
 * handle_groups.php
 * 
 * @package map
 * @author Ted Bowman <ted@tedbow.com>
 * @version 0.1
 * Check to see if groups are being used in this map
 *
*/
$groupmode = groupmode($course, $cm);
$currentGroup = setup_and_print_groups($course, $groupmode, 'view.php?id='.$id);
$memberOfGroup = $currentGroup == 0 ? true :groups_is_member($currentGroup);
//echo "currentGroup=" .$currentGroup . " groupmode=" . $groupmode . " memberOfGroup=" . $memberOfGroup;
?>
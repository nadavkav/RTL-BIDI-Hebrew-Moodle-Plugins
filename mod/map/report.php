<?php  // $Id: report.php,v 1.1 2008/08/13 17:05:47 arborrow Exp $

require_once("../../config.php");
require_once("lib.php");

require_once("map_security_check.php");



print_header_simple(format_string($map->name), "",
"<a href=\"index.php?id=$course->id\">$strmaps</a> -> ".format_string($map->name), "", "", true,
"", navmenu($course, $cm));

require_once("handle_groups.php");

//echo "currentGroup=" .$currentGroup . " groupmode=" . $groupmode . " memberOfGroup=" . $memberOfGroup;
echo '<div class="clearer"></div>';
if ($map->text) {
	print_box(format_text($map->text, $map->format), 'generalbox', 'intro');
}
$map_locations = map_get_locations($map->id,$currentGroup);
if($map_locations){
	$table->head = array(get_string("name"),get_string("location"),get_string("uploadedby","map"));
	foreach($map_locations as $location){
		if(map_isStudentLocation($location)){
			$studentLocations[] = array($location->user->firstname . " " . $location->user->lastname,map_addressString($location),"");
		}else{
			$otherLocations[] = array($location->title,map_addressString($location),$location->user->firstname . " " . $location->user->lastname);
		}
	}
	echo "<br />";
	$table->data = array();
	if(isset($studentLocations)){
		$table->data[] = array(get_string("studnetlocations","map"));
		$table->data = array_merge($table->data,$studentLocations);
	}
	if(isset($otherLocations)){
		$table->data[] = array(get_string("otherlocations","map"));
		$table->data = array_merge($table->data,$otherLocations);
	}
	
    print_table($table);
}else{
	print_box(format_text(get_string("emptymap","map"),FORMAT_PLAIN), 'generalbox');
}

print_footer($course);

?>


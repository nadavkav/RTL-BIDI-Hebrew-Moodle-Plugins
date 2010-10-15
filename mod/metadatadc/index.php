<?PHP // $Id: index.php,v 1.3 2006/04/29 22:22:27 skodak Exp $

/// This page lists all the instances of metadatadc in a particular course
/// Replace metadatadc with the name of your module
/*
function get_cm_from_instance($modulename, $instance, $courseid=0) {

    global $CFG;

    $courseselect = ($courseid) ? "cm.course = '$courseid' AND " : '';

    return get_record_sql("SELECT cm.*, m.name, md.name as modname
                           FROM {$CFG->prefix}course_modules cm,
                                {$CFG->prefix}modules md,
                                {$CFG->prefix}$modulename m
                           WHERE $courseselect
                                 cm.instance = m.id AND
                                 md.name = '$modulename' AND
                                 md.id = cm.module AND
                                 m.id = '$instance'");

}
*/
    require_once("../../config.php");
    require_once("../../course/lib.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "metadatadc", "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strmetadatadcs = get_string("modulenameplural", "metadatadc");
    $strmetadatadc  = get_string("modulename", "metadatadc");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    print_header("$course->shortname: $strmetadatadcs", "$course->fullname", "$navigation $strmetadatadcs", "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $metadatadcs = get_all_instances_in_course("metadatadc", $course)) {
        notice("There are no metadatadcs", "../../course/view.php?id=$course->id");
        die;
    }


/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("names","metadatadc");
    $strresource  = get_string("resources","metadatadc");	
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname, $strresource, " " , "Metadata Dublin Core", " " );
        $table->align = array ("center", "left", "center", "center", "center");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname, $strresource, " " , "Metadata Dublin Core", " " );
        $table->align = array ("center", "left", "left", "center", "center", "center");
    } else {
        $table->head  = array ($strname, $strresource, " " , "Metadata Dublin Core", " " );
        $table->align = array ("left", "left", "center", "center", "center");
    }

	if (isteacheredit($course->id)) {
		array_push($table->head, $strupdate);
		array_push($table->align, "center");
	}
	
    foreach ($metadatadcs as $metadatadc) {
	
		$lines = get_array_of_activities($course->id); 
		foreach ($lines as $key => $line) {
			$cmlo[$key] = $line->cm; //LO course module id
			$modlo[$key] = $line->mod; //LO module name	
			$namelo[$key] = trim(strip_tags(urldecode($line->name))); //LO name	(instance name)
		}
			
		//get_field($table, $return, $field1, $value1, $field2='', $value2='', $field3='', $value3='')
		//$resource_name = get_field("resource", "name", "id", $metadatadc->resource, "course", $course->id);	
		//$cmr = get_cm_from_instance('resource', $metadatadc->resource, $resource->course);	
		
	if ($CFG->version > 2005000000) {
	
        if (!$metadatadc->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$metadatadc->coursemodule\">$metadatadc->name</a>";	
			$link1 = "<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?update=".$metadatadc->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/edit.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strupdate."\" /></a>&nbsp;&nbsp;<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?delete=".$metadatadc->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/delete.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strdelete."\" /></a>&nbsp;&nbsp;<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?show=".$metadatadc->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/show.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strshow."\" /></a>";			
            $link2 = "<a class=\"dimmed\" href=\"$CFG->wwwroot/mod/".$modlo[$metadatadc->resource]."/view.php?id=".$metadatadc->resource."\">".$namelo[$metadatadc->resource]."  (".$modlo[$metadatadc->resource].")</a>";					
            $link3 = "<a class=\"dimmed\" href=\"view.php?id=$metadatadc->coursemodule\"><b>Simple DC</b></a>";
			$link4 = "<a class=\"dimmed\" href=\"view_qdc.php?id=$metadatadc->coursemodule\"><b>Qualified DC</b></a>";
            $link5 = "<a class=\"dimmed\" href=\"view_qdclom.php?id=$metadatadc->coursemodule\"><b>Qualified DC+LOM<b></a>";		
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$metadatadc->coursemodule\">$metadatadc->name</a>";
			$link1 = "<a href=\"$CFG->wwwroot/course/mod.php?update=".$metadatadc->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/edit.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strupdate."\" /></a>&nbsp;&nbsp;<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?delete=".$metadatadc->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/delete.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strdelete."\" /></a>&nbsp;&nbsp;<a href=\"$CFG->wwwroot/course/mod.php?hide=".$metadatadc->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/hide.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strshow."\" /></a>";			
            $link2 = "<a href=\"$CFG->wwwroot/mod/".$modlo[$metadatadc->resource]."/view.php?id=".$metadatadc->resource."\">".$namelo[$metadatadc->resource]."  (".$modlo[$metadatadc->resource].")</a>";	
            $link3 = "<a href=\"view.php?id=$metadatadc->coursemodule\"><b>Simple DC</b></a>";
			$link4 = "<a href=\"view_qdc.php?id=$metadatadc->coursemodule\"><b>Qualified DC</b></a>";
            $link5 = "<a href=\"view_qdclom.php?id=$metadatadc->coursemodule\"><b>Qualified DC+LOM</b></a>";							
        }
		
	} else {
	
        if (!$metadatadc->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$metadatadc->coursemodule\">$metadatadc->name</a>";			
			$link1 = "<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?update=".$metadatadc->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/edit.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strupdate."\" /></a>&nbsp;&nbsp;<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?delete=".$metadatadc->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/delete.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strdelete."\" /></a>&nbsp;&nbsp;<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?show=".$metadatadc->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/show.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strshow."\" /></a>";			
            $link2 = "<a class=\"dimmed\" href=\"$CFG->wwwroot/mod/".$modlo[$metadatadc->resource]."/view.php?id=".$metadatadc->resource."\">".$namelo[$metadatadc->resource]."  (".$modlo[$metadatadc->resource].")</a>";	
            $link3 = "<a class=\"dimmed\" href=\"view.php?id=$metadatadc->coursemodule\"><b>Simple DC</b></a>";
			$link4 = "<a class=\"dimmed\" href=\"view_qdc.php?id=$metadatadc->coursemodule\"><b>Qualified DC</b></a>";
            $link5 = "<a class=\"dimmed\" href=\"view_qdclom.php?id=$metadatadc->coursemodule\"><b>Qualified DC+LOM</b></a>";		
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$metadatadc->coursemodule\">$metadatadc->name</a>";
			$link1 = "<a href=\"$CFG->wwwroot/course/mod.php?update=".$metadatadc->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/edit.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strupdate."\" /></a>&nbsp;&nbsp;<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?delete=".$metadatadc->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/delete.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strdelete."\" /></a>&nbsp;&nbsp;<a href=\"$CFG->wwwroot/course/mod.php?hide=".$metadatadc->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/hide.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strshow."\" /></a>";			
            $link2 = "<a href=\"$CFG->wwwroot/mod/".$modlo[$metadatadc->resource]."/view.php?id=".$metadatadc->resource."\">".$namelo[$metadatadc->resource]."  (".$modlo[$metadatadc->resource].")</a>";	
            $link3 = "<a href=\"view.php?id=$metadatadc->coursemodule\"><b>Simple DC</b></a>";
			$link4 = "<a href=\"view_qdc.php?id=$metadatadc->coursemodule\"><b>Qualified DC</b></a>";
            $link5 = "<a href=\"view_qdclom.php?id=$metadatadc->coursemodule\"><b>Qualified DC+LOM</b></a>";							
        }
	}	

	if ((isteacheredit($course->id)) & ($course->format == "weeks" or $course->format == "topics")) {
            $table->data[] = array ($metadatadc->section, $link, $link2, $link3, $link4, $link5, $link1);	
	} elseif ((!isteacheredit($course->id)) & ($course->format == "weeks" or $course->format == "topics")) {
            $table->data[] = array ($metadatadc->section, $link, $link2, $link3, $link4, $link5);	
    } elseif ((isteacheredit($course->id)) & (!$course->format == "weeks" or !$course->format == "topics")) {
            $table->data[] = array ($link, $link2, $link3, $link4, $link5, $link1);	
	} elseif ((!isteacheredit($course->id)) & (!$course->format == "weeks" or !$course->format == "topics")) {			
            $table->data[] = array ($link, $link2, $link3, $link4, $link5);	
    } else {
            $table->data[] = array ($link, $link2, $link3, $link4, $link5);	
	}		
  }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>

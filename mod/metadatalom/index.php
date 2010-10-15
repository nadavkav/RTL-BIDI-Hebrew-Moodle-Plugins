<?PHP // $Id: index.php,v 1.3 2006/04/29 22:22:27 skodak Exp $

/// This page lists all the instances of metadatalom in a particular course
/// Replace metadatalom with the name of your module
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

    add_to_log($course->id, "metadatalom", "view all", "index.php?id=$course->id", "");

	
/// Get all required strings

    $strmetadataloms = get_string("modulenameplural", "metadatalom");
    $strmetadatalom  = get_string("modulename", "metadatalom");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    print_header("$course->shortname: $strmetadataloms", "$course->fullname", "$navigation $strmetadataloms", "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $metadataloms = get_all_instances_in_course("metadatalom", $course)) {
        notice("There are no metadataloms", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strnames  = get_string("names","metadatalom");
	$strupdate = get_string("update");
	$strdelete = get_string("delete");
	$strshow = get_string("show");
	$strhide = get_string("hide");	
    $strresource  = get_string("resources","metadatalom");	
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");


    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strnames, $strresource, " " , "IEEE - Learning Object Metadata", " " );
        $table->align = array ("center", "left", "center", "center", "center");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strnames, $strresource, " " , "IEEE - Learning Object Metadata", " " );
        $table->align = array ("center", "left", "left", "center", "center", "center");
    } else {
        $table->head  = array ($strnames, $strresource, " " , "IEEE - Learning Object Metadata", " " );
        $table->align = array ("left", "left", "center", "center", "center");
    }

	if (isteacheredit($course->id)) {
		array_push($table->head, $strupdate);
		array_push($table->align, "center");
	}

    foreach ($metadataloms as $metadatalom) {

		$lines = get_array_of_activities($course->id); 
		foreach ($lines as $key => $line) {
			$cmlo[$key] = $line->cm; //LO course module id
			$modlo[$key] = $line->mod; //LO module name	
			$namelo[$key] = trim(strip_tags(urldecode($line->name))); //LO name	(instance name)
		}
		
		//get_field($table, $return, $field1, $value1, $field2='', $value2='', $field3='', $value3='')
		//$resource_name = get_field("resource", "name", "id", $metadatalom->resource, "course", $course->id);
		//$cmr = get_cm_from_instance('resource', $metadatalom->resource, $resource->course);	

	if ($CFG->version > 2005000000) {

        if (!$metadatalom->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$metadatalom->coursemodule\">$metadatalom->name</a>";
			$link1 = "<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?update=".$metadatalom->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/edit.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strupdate."\" /></a>&nbsp;&nbsp;<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?delete=".$metadatalom->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/delete.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strdelete."\" /></a>&nbsp;&nbsp;<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?show=".$metadatalom->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/show.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strshow."\" /></a>";			
            $link2 = "<a class=\"dimmed\" href=\"$CFG->wwwroot/mod/".$modlo[$metadatalom->resource]."/view.php?id=".$metadatalom->resource."\">".$namelo[$metadatalom->resource]."  (".$modlo[$metadatalom->resource].")</a>";
            $link3 = "<a class=\"dimmed\" href=\"view.php?id=$metadatalom->coursemodule\"><b>Simple LOM</b></a>";
			$link4 = "<a class=\"dimmed\" href=\"view_lom.php?id=$metadatalom->coursemodule\"><b>Complete LOM</b></a>";
            $link5 = "<a class=\"dimmed\" href=\"view_imslrm.php?id=$metadatalom->coursemodule\"><b>IMS-LRM to LOM</b></a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$metadatalom->coursemodule\">$metadatalom->name</a>";
			$link1 = "<a href=\"$CFG->wwwroot/course/mod.php?update=".$metadatalom->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/edit.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strupdate."\" /></a>&nbsp;&nbsp;<a href=\"$CFG->wwwroot/course/mod.php?delete=".$metadatalom->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/delete.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strdelete."\" /></a>&nbsp;&nbsp;<a  href=\"$CFG->wwwroot/course/mod.php?hide=".$metadatalom->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/hide.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strshow."\" /></a>";				
            $link2 = "<a href=\"$CFG->wwwroot/mod/".$modlo[$metadatalom->resource]."/view.php?id=".$metadatalom->resource."\">".$namelo[$metadatalom->resource]."  (".$modlo[$metadatalom->resource].")</a>";
            $link3 = "<a href=\"view.php?id=$metadatalom->coursemodule\"><b>Simple LOM</b></a>";
			$link4 = "<a href=\"view_lom.php?id=$metadatalom->coursemodule\"><b>Complete LOM</b></a>";
            $link5 = "<a href=\"view_imslrm.php?id=$metadatalom->coursemodule\"><b>IMS-LRM to LOM</b></a>";							
        }
		
        } else {

		if (!$metadatalom->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$metadatalom->coursemodule\">$metadatalom->name</a>";
			$link1 = "<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?update=".$metadatalom->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/edit.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strupdate."\" /></a>&nbsp;&nbsp;<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?delete=".$metadatalom->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/delete.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strdelete."\" /></a>&nbsp;&nbsp;<a class=\"dimmed\" href=\"$CFG->wwwroot/course/mod.php?show=".$metadatalom->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/show.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strshow."\" /></a>";			
            $link2 = "<a class=\"dimmed\" href=\"$CFG->wwwroot/mod/".$modlo[$metadatalom->resource]."/view.php?id=".$metadatalom->resource."\">".$namelo[$metadatalom->resource]."  (".$modlo[$metadatalom->resource].")</a>";
            $link3 = "<a class=\"dimmed\" href=\"view.php?id=$metadatalom->coursemodule\"><b>Simple LOM</b></a>";
			$link4 = "<a class=\"dimmed\" href=\"view_lom.php?id=$metadatalom->coursemodule\"><b>Complete LOM</b></a>";
            $link5 = "<a class=\"dimmed\" href=\"view_imslrm.php?id=$metadatalom->coursemodule\"><b>IMS-LRM to LOM</b></a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$metadatalom->coursemodule\">$metadatalom->name</a>";
			$link1 = "<a href=\"$CFG->wwwroot/course/mod.php?update=".$metadatalom->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/edit.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strupdate."\" /></a>&nbsp;&nbsp;<a href=\"$CFG->wwwroot/course/mod.php?delete=".$metadatalom->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/delete.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strdelete."\" /></a>&nbsp;&nbsp;<a  href=\"$CFG->wwwroot/course/mod.php?hide=".$metadatalom->coursemodule."&amp;sesskey=".$USER->sesskey."\"><img src=\"".$CFG->pixpath."/t/hide.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"".$strshow."\" /></a>";				
            $link2 = "<a href=\"$CFG->wwwroot/mod/".$modlo[$metadatalom->resource]."/view.php?id=".$metadatalom->resource."\">".$namelo[$metadatalom->resource]."  (".$modlo[$metadatalom->resource].")</a>";
            $link3 = "<a href=\"view.php?id=$metadatalom->coursemodule\"><b>Simple LOM</b></a>";
			$link4 = "<a href=\"view_lom.php?id=$metadatalom->coursemodule\"><b>Complete LOM</b></a>";
            $link5 = "<a href=\"view_imslrm.php?id=$metadatalom->coursemodule\"><b>IMS-LRM to LOM</b></a>";							
        }
	}

	if ((isteacheredit($course->id)) & ($course->format == "weeks" or $course->format == "topics")) {
            $table->data[] = array ($metadatalom->section, $link, $link2, $link3, $link4, $link5, $link1);	
	} elseif ((!isteacheredit($course->id)) & ($course->format == "weeks" or $course->format == "topics")) {
            $table->data[] = array ($metadatalom->section, $link, $link2, $link3, $link4, $link5);	
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

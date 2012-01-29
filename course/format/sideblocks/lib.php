<?php  	// $Id: lib.php,v 1.538.2.80 2010/05/05  Lea Cohen, ORT Israel
		// Library of useful functions for topicsLimitedResources format.
		// Included by format.php
		
/**
 * Prints the menus to add activities and resources. Creates on combo box for both activities and resources.
 * Resources to be included in format are stored in $allowedresources; Activities to be included in format are stored in $allowedactivities
 */
function print_section_add_menus_format($course, $section, $modnames, $vertical=false, $return=false) {
    global $CFG;

    // check to see if user can add menus
    if (!has_capability('moodle/course:manageactivities', get_context_instance(CONTEXT_COURSE, $course->id))) {
        return false;
    }
	static $allowedresources = array('resource&amp;type=html','resource&amp;type=file','resource&amp;type=directory','label'); // Add resources as needed
	static $allowedactivities = array('questionnaire','wiki','glossary','simpleblog','game','forum','chat','choice','lesson','quiz','swf'); // Add activities as needed
	
    static $resources 	= false;
    static $activities 	= false;
	static $tasks		= false;

    if ($resources === false) {
        $resources 	= array();
        $activities = array();
		$tasks 		= array();// for activities who's $type->modclass != MOD_CLASS_RESOURCE
		
		/* begin with optgroup name*/
		$resources[] = '--'.get_string('resources');
		$activities[] = '--'.get_string('activities');
		
        foreach($modnames as $modname=>$modnamestr) {
            if (!course_allowed_module($course, $modname)) {
                continue;
            }

            $libfile = "$CFG->dirroot/mod/$modname/lib.php";
            if (!file_exists($libfile)) {
                continue;
            }
            include_once($libfile);
            $gettypesfunc =  $modname.'_get_types';
            if (function_exists($gettypesfunc)) {
                $types = $gettypesfunc();
                foreach($types as $type) {
                    if (!isset($type->modclass) or !isset($type->typestr)) {
                        debugging('Incorrect activity type in '.$modname);
                        continue;
                    }
                    if ($type->modclass == MOD_CLASS_RESOURCE) {
						if (in_array($type->type,$allowedresources)){
							// filter resources according to list in $allowedresources
							$resources[$type->type] = $type->typestr;
							}
                    } else {
                        $tasks[$type->type] = $type->typestr;
                    }
                }
            } else {
                // all mods without type are considered activity
				// filter activities according to list in $allowedactivities
				if (in_array($modname,$allowedactivities))
					$activities[$modname] = $modnamestr;
            }
        }
    }

    $straddcontenttype = get_string('addcontenttype'); 

	
    $output  = '<div class="section_add_menus">';

    if (!$vertical) {
        $output .= '<div class="horizontal">';
    }
	
	// End optgroups
	$resources[] = '--';
	$activities[] = '--';
	
	//  combine activities and resources, and group accordingly
	$contenttype = array_merge($resources,$activities,$tasks);
	
	
    if (!empty($contenttype)) {
        $output .= popup_form("$CFG->wwwroot/course/mod.php?id=$course->id&amp;section=$section&amp;sesskey=".sesskey()."&amp;add=",
                              $contenttype, "ressection$section", "", $straddcontenttype, 'resource/types', $straddcontenttype, true);
    }

    if (!$vertical) {
        $output .= '</div>';
    }

    $output .= '</div>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}
/// Enable only the following blocks in this format: calendar_upcoming, messages, admin_begin_teacher, table_of_contents, communication_and_sharing
function teachbegin_get_blocks($missingblocks){
	$allowedblocks = array(8,16,42,43,45);
	$formatmissingblocks = array();
	foreach ($missingblocks as $missingblock) {
	if (in_array($missingblock,$allowedblocks)){
		$formatmissingblocks[] = $missingblock;
		}
	}
	return $formatmissingblocks;
}

?>
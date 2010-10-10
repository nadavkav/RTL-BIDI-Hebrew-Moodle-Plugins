<?php  
/**
 * Library of local functions and constants for module webquestscorm

 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: locallib.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/
global $CFG, $USER;
function webquestscorm_get_basics($cmid = 0, $wqid = 0) {
    if ($cmid) {
        if (!$cm = get_coursemodule_from_id('webquestscorm', $cmid)) {
            error('Course Module ID was incorrect');
        }
        if (!$course = get_record('course', 'id', $cm->course)) {
            error('Course is misconfigured');
        }
        if (!$webquestscorm = get_record('webquestscorm', 'id', $cm->instance)) {
            error('Course module is incorrect');
        }
    } else if ($wqid) {
        if (!$webquestscorm = get_record('webquestscorm', 'id', $wqid)) {
            error('Course module is incorrect');
        }
        if (!$course = get_record('course', 'id', $webquestscorm->course)) {
            error('Course is misconfigured');
        }
        if (!$cm = get_coursemodule_from_instance('webquestscorm', $webquestscorm->id, $course->id)) {
            error('Course Module ID was incorrect');
        }
    } else {
        error('No course module ID or lesson ID were passed');
    }
    
    return array($cm, $course, $webquestscorm);
}
 
    function webquestscorm_print_header($name, $currenttab = '', $course=NULL,  $cm=NULL, $cmid = 0) {
        global $CFG;
        if ($cmid) {
            if (!isset($cm)) { 
                if (! $cm = get_coursemodule_from_id('webquestscorm', $cmid)) {
                    error('Course Module ID was incorrect');
                }
            }
            if (!isset($course)) {
                if (! $course = get_record('course', 'id', $this->cm->course)) {
                    error('Course is misconfigured');
                }
            }  
            if (!isset($name)) {
                if (! $webquestscorm = get_record('webquestscorm', 'id', $this->cm->instance)) {
                    error('webquestscorm ID was incorrect');
                }
                $name = $webquestscorm->name;
            } 
        }
				else {
            if (!isset($cm)) {
                error('Course Module ID was incorrect');
            }
            if (!isset($course)) {
                error('Course is misconfigured');
            }  
            if (!isset($name)) {
                error('webquestscorm name was incorrect');
            } 				
				}						          
        $strwebquestscorms = get_string('modulenameplural', 'webquestscorm');
        $strwebquestscorm  = get_string('modulename', 'webquestscorm');
        $strname    = format_string($name);
        $button = update_module_button($cm->id, $course->id, $strwebquestscorm);
        $meta = '';
        
	if ($CFG->version < 2007101500){
		print_header_simple(format_string($strname), "","<a href=\"index.php?id=$course->id\">$strwebquestscorms</a> -> ".format_string($name), "", "", true,update_module_button($cm->id, $course->id, $strwebquestscorm), navmenu($course, $cm));
	}else{
		$navigation = build_navigation('', $cm);
		print_header($course->shortname.':'.$strname, $course->fullname,$navigation,'', $meta, true, $button, navmenu($course, $cm));

	}

                   
        print_heading(format_string($name, true));
        require_once("tabs.php");
    }

    function webquestscorm_update($webquestscorm){
        $result = update_record("webquestscorm", $webquestscorm);
        return $result;
    }


    function webquestscorm_update_one_record($webquestscorm,$element){
   
    GLOBAL $CFG;

    if ($CFG -> dbtype == 'mysql') {	 
	$sql= "update mdl_webquestscorm set ".$element."='".$webquestscorm->$element."' where id=".$webquestscorm->id;

    	return mysql_query($sql);  	

    }else{

    	return update_record('webquestscorm',$webquestscorm,'id=$webquestscorm->id');
    }


    }

   

    /**
     * Top-level function for handling of submissions called by submissions.php
     *
     * This is for handling the teacher interaction with the grading interface
     * This should be suitable for most assignment types.
     *
     * @param $mode string Specifies the kind of teacher interaction taking place
     */
    function submissions( $mode) {
        ///The main switch is changed to facilitate
        ///1) Batch fast grading
        ///2) Skip to the next one on the popup
        ///3) Save and Skip to the next one on the popup
        
        //make user global so we can use the id

    }    
 
 

?>  
  
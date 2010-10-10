<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: editdata.php,v 2.0 2009/25/04
 * @package webquestscorm
 **/
    
require_once("../../config.php");

$cmid = optional_param('cmid', 0, PARAM_INT);  // Course Module ID
$element  = optional_param('element', 0);  
    

require ("$CFG->dirroot/mod/webquestscorm/webquestscorm.class.php"); 
$webquestscormclass = "webquestscorm";   
$webquestscorminstance = new $webquestscormclass($cmid); 
require_login($webquestscorminstance->course->id);

if (has_capability('mod/webquestscorm:manage', $webquestscorminstance->context)) {  
	if ($form = data_submitted()){
		$webquestscorminstance->webquestscorm->$element = $form->data;	
		//$return = webquestscorm_update($webquestscorminstance->webquestscorm);
		$return = webquestscorm_update_one_record($webquestscorminstance->webquestscorm,$element);
         	if (!$return) {
            		error("Could not update the webquestscorm $webquestscorminstance->webquestscorm->name", "view.php?id=$webquestscorminstance->course->id");
          	}
          	if (is_string($return)) {
             		error($return, "view.php?id=$webquestscorminstance->course->id");
          	}        
      	} 
      	$webquestscorminstance->edit_data($element); 
    	} else if (has_capability('mod/webquestscorm:preview', $webquestscorminstance->context)) {
        	$webquestscorminstance->preview('introduction');
    	}

?>
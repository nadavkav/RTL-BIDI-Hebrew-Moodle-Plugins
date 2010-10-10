<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: editpreview.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/

    require_once("../../config.php");
  
    $cmid = optional_param('cmid', 0, PARAM_INT);  // Course Module ID
    $element = optional_param('element', 'introduction'); 

    require ("$CFG->dirroot/mod/webquestscorm/webquestscorm.class.php"); 
		$webquestscormclass = "webquestscorm";
    $webquestscorminstance = new $webquestscormclass($cmid);    
    require_login($webquestscorminstance->course->id);

		if (has_capability('mod/webquestscorm:preview', $webquestscorminstance->context)) {
        $webquestscorminstance->preview($element);
    }
?>

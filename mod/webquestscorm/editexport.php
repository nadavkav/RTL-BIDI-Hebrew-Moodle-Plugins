<?php  
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: editexport.php,v 2.0 2009/25/04
 * @package webquestscorm
 **/

require_once("../../config.php");

$cmid = optional_param('cmid', 0, PARAM_INT); 
    

require ("$CFG->dirroot/mod/webquestscorm/webquestscorm.class.php"); 

$webquestscormclass = "webquestscorm";
$webquestscorminstance = new $webquestscormclass($cmid);    
require_login($webquestscorminstance->course->id);

if (has_capability('mod/webquestscorm:manage', $webquestscorminstance->context)) {
	if ($form = data_submitted()){

        	$webquestscorminstance->export();
        } 
 	$webquestscorminstance->edit_export(); 
} else if (has_capability('mod/webquestscorm:preview', $webquestscorminstance->context)) {
        $webquestscorminstance->preview('introduction');
}


?>
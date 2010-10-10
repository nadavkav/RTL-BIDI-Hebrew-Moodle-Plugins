<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: edittemplate.php, v 2.0 2009/25/04
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
            $webquestscorminstance->webquestscorm->template = $form->template;
            $return = webquestscorm_update_one_record($webquestscorminstance->webquestscorm,'template');
            if (!$return) {
                 error("Could not update the webquestscorm $webquestscorminstance->webquestscorm->name", "view.php?id=$webquestscorminstance->course->id");
            }
            if (is_string($return)) {
                 error($return, "view.php?id=$webquestscorminstance->course->id");
            }   
						$webquestscorminstance->preview('introduction');
        } else {
            $webquestscorminstance->edit_template(); 
        }
    } else if (has_capability('mod/webquestscorm:preview', $webquestscorminstance->context)) {
        $webquestscorminstance->preview('introduction');
    }
  



?>

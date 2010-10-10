<?php  
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: view.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/


    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");

    $cmid = optional_param('id', 0, PARAM_INT);  // Course Module ID
    $wqid  = optional_param('wqid', 0, PARAM_INT);   // Assignment ID

    if (!empty($cmid)){
        list($cm, $course, $webquestscorm) = webquestscorm_get_basics($cmid);
    } else if (!empty($wqid)) {
        list($cm, $course, $webquestscorm) = webquestscorm_get_basics(0, $wqid);
        $cmid = $cm->id;
    }


   
    require_login($course->id);
    require ("$CFG->dirroot/mod/webquestscorm/webquestscorm.class.php"); 
		$webquestscormclass = "webquestscorm";
    $webquestscorminstance = new $webquestscormclass($cmid, $webquestscorm, $cm, $course);
    if (!$webquestscorminstance->exists_manifest()){
        $webquestscorminstance->create_manifest($cmid);
    }
  
    if (has_capability('mod/webquestscorm:manage', $webquestscorminstance->context)) { 
        $webquestscorminstance->edit_data('introduction');
    } else if (has_capability('mod/webquestscorm:preview', $webquestscorminstance->context)) {
        $webquestscorminstance->preview('introduction');
    }
?>

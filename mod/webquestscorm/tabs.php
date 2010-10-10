<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: tabs.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/

    
    $tabs      = array();
    $row       = array();
    $inactive  = array();
    $activated = array();
    

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        
    if (has_capability('mod/webquestscorm:manage', $context)) {
       
        $row[] = new tabobject('template', "$CFG->wwwroot/mod/webquestscorm/edittemplate.php?cmid=".$cm->id, get_string('template', 'webquestscorm'));
        $row[] = new tabobject('edit', "$CFG->wwwroot/mod/webquestscorm/editdata.php?cmid=".$cm->id."&element=introduction", get_string('edit', 'webquestscorm'));
        $row[] = new tabobject('metadata', "$CFG->wwwroot/mod/webquestscorm/editmetadata.php?cmid=".$cm->id."&element=general", get_string('metadata', 'webquestscorm'));
        $row[] = new tabobject('export', "$CFG->wwwroot/mod/webquestscorm/editexport.php?cmid=".$cm->id, get_string('export', 'webquestscorm'));
    }
    if (has_capability('mod/webquestscorm:preview', $context)) { 
        $row[] = new tabobject('preview', "$CFG->wwwroot/mod/webquestscorm/editpreview.php?cmid=".$cm->id, get_string('modulename', 'webquestscorm'));                
        $row[] = new tabobject('uploadTasks', "$CFG->wwwroot/mod/webquestscorm/editsubmissions.php?cmid=".$cm->id."&element=uploadTasks", get_string('uploadTasks', 'webquestscorm'));                
    }		   
    if (count($row) == 1) {
        // Don't show only an info tab (e.g. to students).
        $currenttab = 'preview';
    } else {
        $tabs[] = $row;
    }    
    if (($currenttab == 'edit' || $currenttab == 'introduction' || $currenttab == 'task' 
		         || $currenttab == 'process' || $currenttab == 'evaluation' || $currenttab == 'conclusion' || $currenttab == 'credits')  
		         && has_capability('mod/webquestscorm:manage', $context)) {
        $inactive[] = 'edit'; 
        $activated[] = 'edit';
        $row  = array();
        $row[] = new tabobject('introduction', "$CFG->wwwroot/mod/webquestscorm/editdata.php?cmid=".$cm->id."&element=introduction", get_string('introduction', 'webquestscorm'));
        $row[] = new tabobject('task', "$CFG->wwwroot/mod/webquestscorm/editdata.php?cmid=".$cm->id."&element=task", get_string('task', 'webquestscorm'));
        $row[] = new tabobject('process', "$CFG->wwwroot/mod/webquestscorm/editdata.php?cmid=".$cm->id."&element=process", get_string('process', 'webquestscorm'));
        $row[] = new tabobject('evaluation', "$CFG->wwwroot/mod/webquestscorm/editdata.php?cmid=".$cm->id."&element=evaluation", get_string('evaluation', 'webquestscorm'));
        $row[] = new tabobject('conclusion', "$CFG->wwwroot/mod/webquestscorm/editdata.php?cmid=".$cm->id."&element=conclusion", get_string('conclusion', 'webquestscorm'));
	$row[] = new tabobject('credits', "$CFG->wwwroot/mod/webquestscorm/editdata.php?cmid=".$cm->id."&element=credits", get_string('credits', 'webquestscorm'));
        if ($currenttab == 'edit') {
            $currenttab = 'introduction';
        }
				$tabs[] = $row;				  
		} 
		if (($currenttab == 'metadata' || $currenttab == 'general' || $currenttab == 'lifecycle' || $currenttab == 'metametadata' || $currenttab == 'technical'
		        || $currenttab == 'educational' || $currenttab == 'rights' || $currenttab == 'relation' || $currenttab == 'annotation' || $currenttab == 'classification')
		        && has_capability('mod/webquestscorm:manage', $context)) {
        $inactive[] = 'metadata'; 
  	  $activated[] = 'metadata';
        $row  = array();
				$row[] = new tabobject('general', "$CFG->wwwroot/mod/webquestscorm/editmetadata.php?cmid=".$cm->id."&element=general", get_string('general', 'webquestscorm'));
				$row[] = new tabobject('lifecycle', "$CFG->wwwroot/mod/webquestscorm/editmetadata.php?cmid=".$cm->id."&element=lifecycle", get_string('lifecycle', 'webquestscorm'));
				$row[] = new tabobject('metametadata', "$CFG->wwwroot/mod/webquestscorm/editmetadata.php?cmid=".$cm->id."&element=metametadata", get_string('metametadata', 'webquestscorm'));
				$row[] = new tabobject('technical', "$CFG->wwwroot/mod/webquestscorm/editmetadata.php?cmid=".$cm->id."&element=technical", get_string('technical', 'webquestscorm'));
				$row[] = new tabobject('educational', "$CFG->wwwroot/mod/webquestscorm/editmetadata.php?cmid=".$cm->id."&element=educational", get_string('educational', 'webquestscorm'));
				$row[] = new tabobject('rights', "$CFG->wwwroot/mod/webquestscorm/editmetadata.php?cmid=".$cm->id."&element=rights", get_string('rights', 'webquestscorm'));
				$row[] = new tabobject('relation', "$CFG->wwwroot/mod/webquestscorm/editmetadata.php?cmid=".$cm->id."&element=relation", get_string('relation', 'webquestscorm'));
				$row[] = new tabobject('annotation', "$CFG->wwwroot/mod/webquestscorm/editmetadata.php?cmid=".$cm->id."&element=annotation", get_string('annotation', 'webquestscorm'));
				$row[] = new tabobject('classification', "$CFG->wwwroot/mod/webquestscorm/editmetadata.php?cmid=".$cm->id."&element=classification", get_string('classification', 'webquestscorm'));
        if ($currenttab == 'metadata') {
            $currenttab = 'general';
        }
				$tabs[] = $row;				  		    
		}

    print_tabs($tabs, $currenttab, $inactive, $activated); 


?>

<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez 
 * @version $Id: editmetadata.php,v 2.0 2009/25/04
 * @package webquestscorm
 **/

require_once("../../config.php");

$cmid = optional_param('cmid', 0, PARAM_INT); 
$element  = optional_param('element', 0);  

require ("$CFG->dirroot/mod/webquestscorm/metadata.class.php");
$metadatainstance = new metadata($cmid);    
require_login($metadatainstance->course->id);

if (has_capability('mod/webquestscorm:manage', $metadatainstance->context)) { 
	if ($form = data_submitted()){
        	if ($form->mode=='metadata'){
                	switch ($form->submode) {
				case 'general':
					$metadatainstance->set_general($form); break;
				case 'lifecycle':
					$metadatainstance->set_lifecycle($form); break;
				case 'metametadata':
					$metadatainstance->set_metametadata($form); break;		
				case 'technical':
					$metadatainstance->set_technical($form); break;			
				case 'educational':
					$metadatainstance->set_educational($form); break;			
				case 'rights':
					$metadatainstance->set_rights($form); break;			
				case 'relation':
                      		$metadatainstance->set_relation($form); break;			
				case 'annotation':
					$metadatainstance->set_annotation($form); break;			
				case 'classification':
					$metadatainstance->set_classification($form); break;																							 
              		}   
          	}
       
       	}
        $metadatainstance->edit_metadata($element);  
} else if (has_capability('mod/webquestscorm:preview', $metadatainstance->context)) {
        $webquestscorminstance->preview('introduction');
}
?>

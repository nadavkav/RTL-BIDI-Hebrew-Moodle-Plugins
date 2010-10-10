<?php 
/**
 * Metadata Class
 *
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: metadata.class.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/
require_once("locallib.php"); 

class metadata {
   
    var $cm;
    var $course;
    var $manifest;
    var $path;
    var $context;
    var $webquestscorm;
   

    /**
     * Constructor for the webquestscorm class
     *
     * Constructor for the base assignment class.
     * If cm->id is set create the cm, course, assignment objects.
     * If the assignment is hidden and the user is not a teacher then
     * this prints a page header and notice.
     *
     * @param cm->id   integer, the current course module id - not set for new assignments
     * @param assignment   object, usually null, but if we have it we pass it to save db access
     * @param cm   object, usually null, but if we have it we pass it to save db access
     * @param course   object, usually null, but if we have it we pass it to save db access
     */
    function metadata($cmid) {
    
        global $CFG;
        
        if (!$this->cm = get_coursemodule_from_id('webquestscorm', $cmid)) {
            error('Course Module ID was incorrect');
        }
        if (!$this->course = get_record('course', 'id', $this->cm->course)) {
            error('Course is misconfigured');
        }

				$this->context = get_context_instance(CONTEXT_MODULE,$this->cm->id);		
				
				// GET MANIFEST
				$this->manifest = new DOMDocument;
        $this->path = $CFG->dataroot.'/'.$this->course->id.'/'.$CFG->moddata.'/webquestscorm'; 
        $this->manifest->load($this->path.'/'.$this->cm->id.'/'.'imsmanifest.xml');	      


	if (! $this->webquestscorm = get_record('webquestscorm', 'id', $this->cm->instance)) {
                error('webquestscorm ID was incorrect');
        }
    
    }
    
    function edit_metadata($element=NULL) {
        global $CFG;
        if (!isset($element)) {
            $element = 'general';
        }
        webquestscorm_print_header($this->webquestscorm->name, $element, $this->course,  $this->cm);
        switch ($element) {
            case 'metadata': //METADATA
						case 'general':
                require_once("general.php"); 
                break;
						case 'lifecycle':
                require_once("lifecycle.php"); 
                break;  
						case 'metametadata':
                require_once("metametadata.php"); 
                break; 
						case 'technical':
                require_once("technical.php"); 
                break; 
						case 'educational':
                require_once("educational.php"); 
                break; 
						case 'rights':
                require_once("rights.php"); 
                break; 
						case 'relation':
                require_once("relation.php"); 
                break; 
						case 'annotation':
                require_once("annotation.php"); 
                break; 		                
						case 'classification':
                require_once("classification.php"); 
                break; 																																																						              																	        
        }
        print_footer(); 
    }
    
function set_manifest(){
    global $CFG;
    $this->path=$CFG->dataroot.'/'.$this->course->id.'/'.$CFG->moddata.'/webquestscorm';
    $this->manifest->save($this->path.'/'.$this->cm->id.'/'.'imsmanifest.xml');
}

function set_metadata($general, $node){
    $xpath = new DOMXpath($this->manifest);
    $q = '/*[local-name()="manifest" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="metadata" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
    $q = $q.'/*[local-name()="lom" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';
    $q = $q.'/*[local-name()="'.$node.'" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';	        
    $nodelist = $xpath->query($q);

		$position['general'] = NULL;
		$position['lifecycle'] = 'general';
		$position['metaMetadata'] = 'lifecycle';
		$position['technical'] = 'metaMetadata';
		$position['educational'] = 'technical';
		$position['rights'] = 'educational';
		$position['relation'] = 'rights';
		$position['annotation'] = 'relation';
		$position['classification'] = 'annotation';

				    
    if ($nodelist->length == 0){

        $q = '/*[local-name()="manifest" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
        $q = $q.'/*[local-name()="metadata" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
        $q = $q.'/*[local-name()="lom" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';
    		
        $nodelist2 = $xpath->query($q);	
        $lomNode = $nodelist2->item(0);  								            
        $lomNode->appendChild($this->manifest->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:'.$node));
        $this->manifest->firstChild->firstChild->appendChild($lomNode); 
        $q = '/*[local-name()="manifest" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
        $q = $q.'/*[local-name()="metadata" and namespace-uri()="http://www.imsglobal.org/xsd/imscp_v1p1"]';
        $q = $q.'/*[local-name()="lom" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';
        $q = $q.'/*[local-name()="'.$node.'" and namespace-uri()="http://ltsc.ieee.org/xsd/LOM"]';	            
        $nodelist = $xpath->query($q);
        $oldnode = $nodelist->item(0);
    } else {
        $oldnode = $nodelist->item(0);
    }
    $newnode = $this->manifest->importNode($general->firstChild->firstChild, true);
    $oldnode->parentNode->replaceChild($newnode, $oldnode);
}

function set_general($form){
    $general = new DomDocument;
    $generalRootNode = $general ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:root');
    $generalNode = $general ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:general');
    if (!empty($form->language)){
        $lang = $form->language;
    } else {
        $lang = 'es';
    }
    if (!empty($form->catalog) && !empty($form->entry)){
        $identifierNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:identifier');  					            
        $catalogNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:catalog', $form->catalog);
        $entryNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:entry', $form->entry);
        $identifierNode->appendChild($catalogNode);
        $identifierNode->appendChild($entryNode);
        $generalNode->appendChild($identifierNode);    					            
    }    					        
    if (!empty($form->title)){
        $Node = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:title');
        $stringNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->title);

        $stringNode->setAttribute('language',$lang);
        $Node->appendChild($stringNode);
        $generalNode->appendChild($Node);
    }					      
    if (!empty($form->language)){
        $Node = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:language',$form->language);
        $generalNode->appendChild($Node);
    }   					        
    if (!empty($form->description)){
        $Node = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:description');
        $stringNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->description);

        $stringNode->setAttribute('language',$lang);
        $Node->appendChild($stringNode);
        $generalNode->appendChild($Node);
    }		
    if (!empty($form->keyword)){
        $Node = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:keyword');
        $stringNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->keyword);

        $stringNode->setAttribute('language',$lang);
        $Node->appendChild($stringNode);
        $generalNode->appendChild($Node);
    }							
    if (!empty($form->coverage)){
        $Node = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:coverage');
        $stringNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->coverage);

        $stringNode->setAttribute('language',$lang);
        $Node->appendChild($stringNode);
        $generalNode->appendChild($Node);
    }			
    if (!empty($form->structure)){
        $structureNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:structure');  					            
        $sourceNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
        $valueNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', $form->structure);
        $structureNode->appendChild($sourceNode);
        $structureNode->appendChild($valueNode);
        $generalNode->appendChild($structureNode);  
    }		
    if (!empty($form->aggregationLevel)){
        $aggregationLevelNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:aggregationLevel');  					            
        $sourceNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
        $valueNode = $general->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', $form->aggregationLevel);
        $aggregationLevelNode->appendChild($sourceNode);
        $aggregationLevelNode->appendChild($valueNode);
        $generalNode->appendChild($aggregationLevelNode); 
    }																																																	  
    $generalRootNode->appendChild($generalNode);
    $general->appendChild($generalRootNode);
    $this->set_metadata($general, "general");
    $this->set_manifest();
}
    
function set_lifecycle($form){
						        
    $lifecycle = new DomDocument;
    $lifecycleRootNode = $lifecycle ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:root');
    $lifecycleNode = $lifecycle ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:lifeCycle');
    	
		$lang='es';

    if (!empty($form->version)){
        $Node = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:version');  
        $stringNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->version);

        $stringNode->setAttribute('language',$lang);
        $Node->appendChild($stringNode);
        $lifecycleNode->appendChild($Node);
    }		
  
    if (!empty($form->status)){
        $statusNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:status');  					            
        $sourceNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
        $valueNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', $form->status);
        $statusNode->appendChild($sourceNode);
        $statusNode->appendChild($valueNode);
        $lifecycleNode->appendChild($statusNode);  
    }	
    if (!empty($form->role) && !empty($form->entity) && !empty($form->dateTime)){
        $contributeNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:contribute');  					            
        $roleNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:role');
        $sourceNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
        $valueNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', $form->role);
        $roleNode->appendChild($sourceNode);
        $roleNode->appendChild($valueNode);        
        $entityNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:entity', $form->entity);
        $dateNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:date');
        $dateTimeNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:dateTime',  $form->dateTime);
        $descriptionNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:description');
        $stringNode = $lifecycle->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->description);
        $stringNode->setAttribute('language',$lang);
        $descriptionNode->appendChild($stringNode);        
        $dateNode->appendChild($dateTimeNode);
        $dateNode->appendChild($descriptionNode);        
        
        $contributeNode->appendChild($roleNode);
        $contributeNode->appendChild($entityNode);
        $contributeNode->appendChild($dateNode);
        $lifecycleNode->appendChild($contributeNode);    					            
    } 		
																	
																																							  
    $lifecycleRootNode->appendChild($lifecycleNode);
    $lifecycle->appendChild($lifecycleRootNode);
    					        
    $this->set_metadata($lifecycle, "lifeCycle"); 
    $this->set_manifest();
}

function set_metametadata($form){
							        
    $metametadata = new DomDocument;
    $metametadataRootNode = $metametadata ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:root');
    $metametadataNode = $metametadata ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:metaMetadata');
    	
    if (!empty($form->language)){
        $lang = $form->language;
    } else {
        $lang = 'es';
    }
    if (!empty($form->catalog) && !empty($form->entry)){
    
        $identifierNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:identifier');  					            
        $catalogNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:catalog', $form->catalog);
        $entryNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:entry', $form->entry);
        $identifierNode->appendChild($catalogNode);
        $identifierNode->appendChild($entryNode);
        $metametadataNode->appendChild($identifierNode);    					            
    } 

    if (!empty($form->role) && !empty($form->entity) && !empty($form->dateTime)){
        $contributeNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:contribute');  					            
        $roleNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:role');
        $sourceNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
        $valueNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', $form->role);
        $roleNode->appendChild($sourceNode);
        $roleNode->appendChild($valueNode);        
        $entityNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:entity', $form->entity);
        $dateNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:date');
        $dateTimeNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:dateTime',  $form->dateTime);
        $descriptionNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:description');
        $stringNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->description);
        $stringNode->setAttribute('language',$lang);
        $descriptionNode->appendChild($stringNode);        
        $dateNode->appendChild($dateTimeNode);
        $dateNode->appendChild($descriptionNode);        
        
        $contributeNode->appendChild($roleNode);
        $contributeNode->appendChild($entityNode);
        $contributeNode->appendChild($dateNode);
        $metametadataNode->appendChild($contributeNode);    					            
    } 			 
    if (!empty($form->metadataSchema1)){    
        $metadataSchemaNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:metadataSchema',$form->metadataSchema1);
        $metametadataNode->appendChild($metadataSchemaNode);   
    }	
    if (!empty($form->metadataSchema2)){  
        $metadataSchemaNode = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:metadataSchema','ADLv1.0');
        $metametadataNode->appendChild($metadataSchemaNode);   
    }    	
    if (!empty($form->language)){
        $Node = $metametadata->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:language',$form->language);
        $metametadataNode->appendChild($Node);
    }   	 
    $metametadataRootNode->appendChild($metametadataNode);
    $metametadata->appendChild($metametadataRootNode);				        
    $this->set_metadata($metametadata, "metaMetadata"); 
    $this->set_manifest();
}

function set_technical($form){
							        
    $technical = new DomDocument;
    $technicalRootNode = $technical ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:root');
    $technicalNode = $technical ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:technical');
       	
    $lang = 'es';
		 
    $formatNode = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:format','text/html');
    $technicalNode->appendChild($formatNode);   

    if (!empty($form->size)){  
        $sizeNode = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:size',$form->size);
        $technicalNode->appendChild($sizeNode);       
    }
    
    if (!empty($form->location)){  
        $locationNode = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:location',$form->location);
        $technicalNode->appendChild($locationNode);   
    }    		
 	
    if (!empty($form->type)&&!empty($form->name)){
        $requirementNode = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:requirement');
        $orCompositeNode = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:orComposite');
        $type = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:type');
        $source = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source','LOMv1.0');
        $value = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value','browser');
        $type->appendChild($source);
        $type->appendChild($value);
        $orCompositeNode->appendChild($type);
        $name = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:name');
        $source = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source','LOMv1.0');
        $value = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value','any');
				$name->appendChild($source);
        $name->appendChild($value);  
				$orCompositeNode->appendChild($name);      
        $requirementNode->appendChild($orCompositeNode);
        $technicalNode->appendChild($requirementNode);
    }	  
    if (!empty($form->installationRemarks)){
        $installationRemarksNode = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:installationRemarks');
        $stringNode = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->installationRemarks);

        $stringNode->setAttribute('language',$lang);
        $installationRemarksNode->appendChild($stringNode);
        $technicalNode->appendChild($installationRemarksNode);
    }		
  
    if (!empty($form->otherPlatformRequirements)){
        $otherPlatformRequirementsNode = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:otherPlatformRequirements');
        $stringNode = $technical->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->otherPlatformRequirements);

        $stringNode->setAttribute('language',$lang);
        $otherPlatformRequirementsNode->appendChild($stringNode);
        $technicalNode->appendChild($otherPlatformRequirementsNode);
    }				 	
    $technicalRootNode->appendChild($technicalNode);
    $technical->appendChild($technicalRootNode);				        
    $this->set_metadata($technical, "technical"); 
    $this->set_manifest();
}

function set_educational($form){
							        
    $educational = new DomDocument;
    $educationalRootNode = $educational ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:root');
    $educationalNode = $educational ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:educational');
       	
    if (!empty($form->language)){
        $lang = $form->language;
    } else {
        $lang = 'es';
    }

    $interactivityTypeNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:interactivityType');  					            
    $sourceNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
    $valueNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', 'active');
    $interactivityTypeNode->appendChild($sourceNode);
    $interactivityTypeNode->appendChild($valueNode);
    $educationalNode->appendChild($interactivityTypeNode);

    $learningResourceTypeNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:learningResourceType');  					            
    $sourceNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
    $valueNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', 'exercise');
    $learningResourceTypeNode->appendChild($sourceNode);
    $learningResourceTypeNode->appendChild($valueNode);
    $educationalNode->appendChild($learningResourceTypeNode);

    $interactivityLevelNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:interactivityLevel');  					            
    $sourceNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
    $valueNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', 'very low');
    $interactivityLevelNode->appendChild($sourceNode);
    $interactivityLevelNode->appendChild($valueNode);
    $educationalNode->appendChild($interactivityLevelNode);
		    
    if (!empty($form->semanticDensity)){
        $semanticDensityNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:semanticDensity');  					            
        $sourceNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
        $valueNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', $form->semanticDensity);
        $semanticDensityNode->appendChild($sourceNode);
        $semanticDensityNode->appendChild($valueNode);
        $educationalNode->appendChild($semanticDensityNode);  
    }				 

    $intendedEndUserRoleNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:intendedEndUserRole');  					            
    $sourceNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
    $valueNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', 'learner');
    $intendedEndUserRoleNode->appendChild($sourceNode);
    $intendedEndUserRoleNode->appendChild($valueNode);
    $educationalNode->appendChild($intendedEndUserRoleNode);

    if (!empty($form->context)){
        $contextNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:context');  					            
        $sourceNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
        $valueNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', $form->context);
        $contextNode->appendChild($sourceNode);
        $contextNode->appendChild($valueNode);
        $educationalNode->appendChild($contextNode);  
    }	

    if (!empty($form->typicalAgeRange)){
        $typicalAgeRangeNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:typicalAgeRange');
        $stringNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->typicalAgeRange);

        $stringNode->setAttribute('language',$lang);
        $typicalAgeRangeNode->appendChild($stringNode);
        $educationalNode->appendChild($typicalAgeRangeNode);
    }	

    if (!empty($form->difficulty)){
        $difficultyNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:difficulty');  					            
        $sourceNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
        $valueNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', $form->difficulty);
        $difficultyNode->appendChild($sourceNode);
        $difficultyNode->appendChild($valueNode);
        $educationalNode->appendChild($difficultyNode);  
    }

    if (!empty($form->duration) && !empty($form->description)){
        $typicalLearningTimeNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:typicalLearningTime');
        $durationNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:duration', $form->duration);
        $descriptionNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:description');
        $stringNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->description);

        $stringNode->setAttribute('language',$lang);
        $descriptionNode->appendChild($stringNode);
        $typicalLearningTimeNode->appendChild($durationNode);
        $typicalLearningTimeNode->appendChild($descriptionNode);
        $educationalNode->appendChild($typicalLearningTimeNode);
    }    
				
    if (!empty($form->language)){
        $languageNode = $educational->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:language',$form->language);
        $educationalNode->appendChild($languageNode);
    }   
									 	
    $educationalRootNode->appendChild($educationalNode);
    $educational->appendChild($educationalRootNode);				        
    $this->set_metadata($educational, "educational"); 
    $this->set_manifest();
}

function set_rights($form){
							        
    $rights = new DomDocument;
    $rightsRootNode = $rights ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:root');
    $rightsNode = $rights ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:rights');
       	
    $lang = 'es';
    if (!empty($form->cost)){
        $costNode = $rights->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:cost');  					            
        $sourceNode = $rights->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
        $valueNode = $rights->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', $form->cost);
        $costNode->appendChild($sourceNode);
        $costNode->appendChild($valueNode);
        $rightsNode->appendChild($costNode);  
    }
    if (!empty($form->copyrightAndOtherRestrictions)){
        $copyrightAndOtherRestrictionsNode = $rights->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:copyrightAndOtherRestrictions');  					            
        $sourceNode = $rights->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
        $valueNode = $rights->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', $form->copyrightAndOtherRestrictions);
        $copyrightAndOtherRestrictionsNode->appendChild($sourceNode);
        $copyrightAndOtherRestrictionsNode->appendChild($valueNode);
        $rightsNode->appendChild($copyrightAndOtherRestrictionsNode);  
    }
    if (!empty($form->description)){
        $descriptionNode = $rights->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:description');
        $stringNode = $rights->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->description);

        $stringNode->setAttribute('language',$lang);
        $descriptionNode->appendChild($stringNode);
        $rightsNode->appendChild($descriptionNode);
    }									 	
    $rightsRootNode->appendChild($rightsNode);
    $rights->appendChild($rightsRootNode);				        
    $this->set_metadata($rights, "rights"); 
    $this->set_manifest();
}
function set_relation( $form){
							        
    $relation = new DomDocument;
    $relationRootNode = $relation ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:root');
    $relationNode = $relation ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:relation');
       	
    $lang = 'es';
    if (!empty($form->kind)){
        $kindNode = $relation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:kind');  					            
        $sourceNode = $relation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
        $valueNode = $relation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', $form->kind);
        $kindNode->appendChild($sourceNode);
        $kindNode->appendChild($valueNode);
        $relationNode->appendChild($kindNode);  
    }
    if (!empty($form->catalog)&&!empty($form->entry)){
        $resourceNode = $relation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:resource');  	
					
        $identifierNode = $relation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:identifier');  					            
        $catalogNode = $relation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:catalog', $form->catalog);
        $entryNode = $relation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:entry', $form->entry);
        $identifierNode->appendChild($catalogNode);
        $identifierNode->appendChild($entryNode);
        $resourceNode->appendChild($identifierNode);  
				if (!empty($form->description)){
				    $descriptionNode = $relation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:description');
						$stringNode = $relation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->description);      
            $stringNode->setAttribute('language',$lang);
            $descriptionNode->appendChild($stringNode);
            $resourceNode->appendChild($descriptionNode);
				}
        $relationNode->appendChild($resourceNode);  
    }
    				 	
    $relationRootNode->appendChild($relationNode);
    $relation->appendChild($relationRootNode);				        
    $this->set_metadata($relation, "relation"); 
    $this->set_manifest();
}
function set_annotation($form){
							        
    $annotation = new DomDocument;
    $annotationRootNode = $annotation ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:root');
    $annotationNode = $annotation ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:annotation');
       	
    $lang = 'es';		 	
    
    if (!empty($form->entity)){
        $entityNode = $annotation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:entity', $form->entity);  					            
        $annotationNode->appendChild($entityNode);  
    }    
    if (!empty($form->dateTime)){
        $dateNode = $annotation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:date');  	
        $dateTimeNode = $annotation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:dateTime', $form->dateTime);  					            
        $dateNode->appendChild($dateTimeNode);  
        if (!empty($form->dateDescription)){
            $dateDescriptionNode = $annotation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:description');  					            
			      $stringNode = $annotation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->dateDescription);      
            $stringNode->setAttribute('language',$lang);
            $dateDescriptionNode->appendChild($stringNode);
            $dateNode->appendChild($dateDescriptionNode);  
        }   
				$annotationNode->appendChild($dateNode);      
    }    
  
    if (!empty($form->description)){
        $descriptionNode = $annotation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:description');  					            
			  $stringNode = $annotation->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->description);      
        $stringNode->setAttribute('language',$lang);
        $descriptionNode->appendChild($stringNode);
        $annotationNode->appendChild($descriptionNode);  
    }   		   
    $annotationRootNode->appendChild($annotationNode);
    $annotation->appendChild($annotationRootNode);				        
    $this->set_metadata($annotation, "annotation"); 
    $this->set_manifest();
}
function set_classification($form){
							        
    $classification = new DomDocument;
    $classificationRootNode = $classification ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:root');
    $classificationNode = $classification ->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:classification');
       	
    $lang = 'es';		 	

    if (!empty($form->purpose)){
        $purposeNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:purpose');  					            
        $sourceNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source', "LOMv1.0");
        $valueNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:value', $form->purpose);
        $purposeNode->appendChild($sourceNode);
        $purposeNode->appendChild($valueNode);
        $classificationNode->appendChild($purposeNode);  
    }
    if (!empty($form->source)){
        $taxonPathNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:taxonPath');
        $sourceNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:source');       
        $stringNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->source);
        $taxonNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:taxon');       
        $idNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:id', $form->id);       
        $entryNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:entry');     
				$stringEntryNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->entry);  
        $stringNode->setAttribute('language',$lang);
        $stringEntryNode->setAttribute('language',$lang);
        $sourceNode->appendChild($stringNode);
        $entryNode->appendChild($stringEntryNode);
        $taxonNode->appendChild($idNode);
        $taxonNode->appendChild($entryNode);
        $taxonPathNode->appendChild($sourceNode);
        $taxonPathNode->appendChild($taxonNode);
        $classificationNode->appendChild($taxonPathNode);
    }		  
		  
    if (!empty($form->description)){
        $descriptionNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:description');
        $stringNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->description);

        $stringNode->setAttribute('language',$lang);
        $descriptionNode->appendChild($stringNode);
        $classificationNode->appendChild($descriptionNode);
    }		    
    if (!empty($form->keyword)){
        $keywordNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:keyword');
        $stringNode = $classification->createElementNS('http://ltsc.ieee.org/xsd/LOM','imsmd:string', $form->keyword);
        $stringNode->setAttribute('language',$lang);
        $keywordNode->appendChild($stringNode);
        $classificationNode->appendChild($keywordNode);
    }		       
    $classificationRootNode->appendChild($classificationNode);
    $classification->appendChild($classificationRootNode);				        
    $this->set_metadata($classification, "classification"); 
    $this->set_manifest();
}    
}

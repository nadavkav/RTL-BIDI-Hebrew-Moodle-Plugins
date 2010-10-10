<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez 
 * @version $Id: editsubmissions.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/

    require_once("../../config.php");
    require_once("locallib.php");
    global $USER;

    $cmid = optional_param('cmid', 0, PARAM_INT); 
    $element  = optional_param('element', 0);  
        
    require_once ("submissions.class.php"); 
    $submissionsinstance = new submissions($cmid); 

    require_login($submissionsinstance->course->id);
    webquestscorm_print_header($submissionsinstance->wqname, 'uploadTasks', $submissionsinstance->course, $submissionsinstance->cm);
    switch ($element) {
				case 'uploadTasks':
				    echo '<div class="reportlink">'.$submissionsinstance->submittedlink().'</div>';
				    require_once("uploadTasks.php");		
				  	print_footer();	
						break;					
				case 'uploadedTasks':
				    require_once("submissions.php");			
				  	break;																			        
    }

?>

<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id:access.php,v 2.0 2009/25/04 
 * @package webquestscorm
 **/
$mod_webquestscorm_capabilities = array(

    // Edit the webquestscorm settings
    'mod/webquestscorm:preview' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'guest' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),   
      
    'mod/webquestscorm:manage' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,            
            'admin' => CAP_ALLOW				        
        )
    ),
    'mod/webquestscorm:submit' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'student' => CAP_ALLOW
        )
    ),    
    'mod/webquestscorm:grade' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,        
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    )    
);
?>

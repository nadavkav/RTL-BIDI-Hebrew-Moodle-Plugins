<?php 

/**
* Module Brainstorm V2
* Operator : locate
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/

/********************************** Save operator config ********************************/
if ($action == 'saveconfig'){
    $operator = required_param('operator', PARAM_ALPHA);
    
    $errors = array();

    /// make some controls
    $xminrange = required_param('config_xminrange', PARAM_RAW);
    $xmaxrange = required_param('config_xmaxrange', PARAM_RAW);
    if ($xminrange >= $xmaxrange){
        $error->message = get_string('invertedrange', 'brainstorm');
        $error->on = 'xrange';
        $errors[] = $error;
    }

    /// make some controls
    $yminrange = required_param('config_yminrange', PARAM_RAW);
    $ymaxrange = required_param('config_ymaxrange', PARAM_RAW);
    if ($yminrange >= $ymaxrange){
        unset($error);
        $error->message = get_string('invertedrange', 'brainstorm');
        $error->on = 'yrange';
        $errors[] = $error;
    }
    
    if (empty($errors)){
        brainstorm_save_operatorconfig($brainstorm->id, $operator);
    }
    else{
        print_error_box($errors);
    }
}
?>
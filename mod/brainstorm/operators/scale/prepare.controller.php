<?php 

/**
* Module Brainstorm V2
* Operator : scale
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/
/********************************** Save operator config ********************************/
if ($action == 'saveconfig'){
    $operator = required_param('operator', PARAM_CLEANHTML);
    brainstorm_save_operatorconfig($brainstorm->id, $operator);
}
?>
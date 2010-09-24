<?php

/**
* Module Brainstorm V2
* Operator : order
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/
include_once ($CFG->dirroot."/mod/brainstorm/operators/{$page}/locallib.php");
// include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");

print_heading(get_string('myordering', 'brainstorm'));
order_display($brainstorm, null, 0);
?>
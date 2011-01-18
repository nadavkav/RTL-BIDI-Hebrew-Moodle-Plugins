<?php  // $Id: view.php,v 1.6.2.3 2009/04/17 22:06:25 skodak Exp $

require_once('../../config.php');
require_once('lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // newmodule instance ID

if ($id) {
    redirect("$CFG->wwwroot/mod/quiz/view.php?id=$id");
} else if ($a) {
    redirect("$CFG->wwwroot/mod/quiz/view.php?a=$a");
} else {
    error('You must specify a course_module ID or an instance ID');
}
?>

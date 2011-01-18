<?php 

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);   // course

if (! $course = get_record('course', 'id', $id)) {
    error('Course ID is incorrect');
}

redirect("$CFG->wwwroot/mod/quiz/index.php?id=$id");
?>

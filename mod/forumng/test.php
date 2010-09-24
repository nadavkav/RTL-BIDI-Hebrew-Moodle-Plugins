<?php
require_once('../../config.php');

print_object($USER); exit;

print_object(get_fast_modinfo(get_record('course','id',7)));
?>
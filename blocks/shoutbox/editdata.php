<?php
require_once('../../config.php');
global $CFG, $COURSE;

$data=$_GET['textarea_val'];
$id=$_GET['id'];
mysql_query("update {$CFG->prefix}block_shoutbox_shoutbox  set data=\"$data\" where id=\"$id\"") or die(mysql_error());
echo  $id.",";
echo $data;

?>
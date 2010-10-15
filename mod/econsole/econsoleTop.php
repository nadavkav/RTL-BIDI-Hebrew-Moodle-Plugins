<?php
require_once("../../config.php"); 
require_once("lib.php");

//Require login
require_login($_REQUEST['courseid']);

//Header
if($_REQUEST['showunit'] && $_REQUEST['showlesson']){
	$header = $_REQUEST['unitstring']." ".$_REQUEST['topic']." - ".$_REQUEST['lessonstring']." ".$_REQUEST['position']." ".get_string("econsoleof", "econsole")." ".$_REQUEST['pages'].": ".strip_tags($_REQUEST['name']);
}elseif(!$_REQUEST['showunit'] && $_REQUEST['showlesson']){
	$header = $_REQUEST['lessonstring']." ".$_REQUEST['position']." ".get_string("econsoleof", "econsole")." ".$_REQUEST['pages'].": ".strip_tags($_REQUEST['name']);
}elseif($_REQUEST['showunit'] && !$_REQUEST['showlesson']){
	$header = $_REQUEST['unitstring']." ".$_REQUEST['topic'].": ".strip_tags($_REQUEST['name']);
}else{
	$header = strip_tags($_REQUEST['name']);
}
?>
<html>
<head>
<title>E-Console</title>
<meta http-equiv="Content-Type" content="text/html;  charset=utf-8">
<link href="theme/<?=$_REQUEST["thm"];?>/css/econsole.css" rel="stylesheet" type="text/css">
<head>
	<script src="js/econsole.php" type="text/javascript"></script>
</head>
<body onLoad="Javascript: \*putTime();*\">
<div id="top">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td class="linetop"><div id="econsoletitle">&nbsp;&nbsp;&nbsp;<?=$header?>
        </div></td>
    <td class="linetop"><div id="clock"><span id="time"></span></div></td>
	<td align="right" class="linetop"><img src="theme/<?=$_REQUEST["thm"];?>/img/logo.gif" name="logo" id="logo">&nbsp;&nbsp;&nbsp;</td>
  </tr>
</table>
</div>
</body>
</html>

<?php
require_once("../../config.php");
require_once("lib.php");

//Get econsole content
$econsole = get_record("econsole", "id", $_REQUEST['id'], "", "", "", "", "imagebartop, imagebarbottom");

//Require login
require_login($_REQUEST['course']);
?>
<html>
<head>
<title>E-Console</title>
<meta http-equiv="Content-Type" content="text/html;  charset=utf-8">
<link href="theme/<?=$_REQUEST["thm"];?>/css/econsole.css" rel="stylesheet" type="text/css">
</head>
<frameset cols="<?=(empty($econsole->imagebartop) && empty($econsole->imagebarbottom)) ? "0" : "70";?>,*" frameborder="no" border="0" framespacing="0">
  <frame src="econsoleMainLeft.php?id=<?=$_REQUEST["id"];?>&course=<?=$_REQUEST["course"];?>&coursemodule=<?=$_REQUEST["coursemodule"];?>&thm=<?=$_REQUEST["thm"];?>" name="leftFrame" scrolling="no" noresize="noresize" id="leftFrame" title="" />
  <frame src="econsoleMainCenter.php?id=<?=$_REQUEST["id"];?>&course=<?=$_REQUEST["course"];?>&coursemodule=<?=$_REQUEST["coursemodule"];?>&thm=<?=$_REQUEST["thm"];?>" name="centerFrame" id="centerFrame" title="" />
</frameset>
<noframes>
<body>
</body>
</noframes></html>

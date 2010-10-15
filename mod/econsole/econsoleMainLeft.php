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
<body bottommargin="0px" leftmargin="0px" rightmargin="0px" topmargin="0px">
<table width="100%" height="100%" border="0px" cellpadding="0px" cellspacing="0px">
	<tr width="100%" height="70px">
		<td><div id="marginlefttop" style="background-image: url(../../file.php/<?=$_REQUEST['course'];?>/<?=$econsole->imagebartop;?>)"></div>
		</td>
	</tr>
	<tr width="100%" height="100%">
		<td><div id="marginleftbottom" style="background-image: url(../../file.php/<?=$_REQUEST['course'];?>/<?=$econsole->imagebarbottom;?>)"></div>
		</td>
	</tr>	
</table>	
</body>
</html>

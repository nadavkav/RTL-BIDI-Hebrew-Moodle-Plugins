<?php
require_once("../../config.php");
require_once("lib.php");

//Get econsole content
$econsole = get_record("econsole", "id", $_REQUEST['id'], "", "", "", "", "name, content");

//Require login
require_login($_REQUEST['course']);
?>
<html>
<head>
<title>E-Console</title>
<meta http-equiv="Content-Type" content="text/html;  charset=utf-8">
<link href="theme/<?=$_REQUEST["thm"];?>/css/econsole.css" rel="stylesheet" type="text/css">
<head>
</head>
<body>
<div id="content"><?=$econsole->content;?></div>
</body>
</html>

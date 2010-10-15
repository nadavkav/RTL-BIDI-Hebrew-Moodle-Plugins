<?php
require_once("../../config.php");
require_once("lib.php");

//Require login
require_login($_REQUEST['course']);

//Get strings
$strlesson = get_string("econsolelesson", "econsole");
$strof = get_string("econsoleof", "econsole");
$strhelp = get_string("econsolehelp", "econsole");
$strprevious = get_string("econsoleprevious", "econsole");
$strreload = get_string("econsolereload", "econsole");
$strnext = get_string("econsolenext", "econsole");
$strclose = get_string("econsoleclose", "econsole");
/*
$strglossary = get_string("econsoleglossary", "econsole");
$strjournal = get_string("econsolejournal", "econsole");
$strforum = get_string("econsoleforum", "econsole");
$strchat = get_string("econsolechat", "econsole");
$strquiz = get_string("econsolequiz", "econsole");
$strassignment = get_string("econsoleassignment", "econsole");
$strwiki = get_string("econsolewiki", "econsole");
*/

//Module buttons
/************************************************************************************/
//Chat buttons
$btnchat = $_REQUEST['chats'] == "false" ? "" : econsole_get_buttons("chat", $_REQUEST['chats']);

//Forum buttons
$btnforum = $_REQUEST['forums'] == "false" ? "" : econsole_get_buttons("forum", $_REQUEST['forums']);

//Glossary buttons
$btnglossary = $_REQUEST['glossaries'] == "false" ? "" : econsole_get_buttons("glossary", $_REQUEST['glossaries']);

//Wiki buttons
$btnwiki = $_REQUEST['wikis'] == "false" ? "" : econsole_get_buttons("wiki", $_REQUEST['wikis']);

//Assignment buttons
$btnassignment = $_REQUEST['assignments'] == "false" ? "" : econsole_get_buttons("assignment", $_REQUEST['assignments']);

//Journal buttons
$btnjournal = $_REQUEST['journals'] == "false" ? "" : econsole_get_buttons("journal", $_REQUEST['journals']);

//Choice buttons
$btnchoice = $_REQUEST['choices'] == "false" ? "" : econsole_get_buttons("choice", $_REQUEST['choices']);

//Quiz buttons 
$btnquiz = $_REQUEST['quizzes'] == "false" ? "" : econsole_get_buttons("quiz", $_REQUEST['quizzes']);

//URL buttons
$btnurl = econsole_get_buttons_urls($_REQUEST['id']);
/************************************************************************************/

//Navigation buttons title
/************************************************************************************/
//previous
$previousInstance = get_record("course_modules", "id", $_REQUEST['previous'], "", "", "", "", "instance");
$previousTitle = !isset($previousInstance->instance) ? "" : get_record("econsole", "id", $previousInstance->instance, "", "", "", "", "name");	

//next
$nextInstance = get_record("course_modules", "id", $_REQUEST['next'], "", "", "", "", "instance");
$nextTitle = !isset($nextInstance->instance) ? "" : get_record("econsole", "id", $nextInstance->instance, "", "", "", "", "name");			
/************************************************************************************/
?>
<html>
<head>
<title>E-Console</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="theme/<?=$_REQUEST["thm"];?>/css/econsole.css" rel="stylesheet" type="text/css">
<script src="js/econsole.php" type="text/javascript"></script>
</head>
<body marginheight="0px" marginwidth="0px" leftmargin="0px" rightmargin="0px">
  <div id="bottom">
    <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td align="left" class="linebottom">
		&nbsp;&nbsp;&nbsp;<a href="#" onClick="Javascript: window.parent.document.getElementById('mainFrame').src='theme/<?=$_REQUEST["thm"];?>/help/index.php';"><img class="btn" src="theme/<?=$_REQUEST["thm"];?>/img/btn/help.gif" alt="" title="" border="0" onMouseOver="Javascript: replaceImage(this, 'theme/<?=$_REQUEST["thm"];?>/img/btn/helpover.gif');" onMouseOut="Javascript: replaceImage(this, 'theme/<?=$_REQUEST["thm"];?>/img/btn/help.gif'); hideTitle();" onMouseMove="Javascript: showTitleRight(event, '<?=$strhelp;?>', '');"></a>&nbsp;<?=$btnglossary.$btnjournal.$btnchat.$btnforum.$btnchoice.$btnwiki.$btnassignment.$btnquiz.$btnurl;?></td>	
	 <td align="right" class="linebottom">
<?php
if(!empty($_REQUEST['previous'])){
?><?php /*?><?php */?>
<a href="econsole.php?id=<?=$_REQUEST['previous'];?>" target="_parent">
<img class="btn" src="theme/<?=$_REQUEST["thm"];?>/img/btn/previous.gif" alt="" title="" border="0" onMouseOver="Javascript: replaceImage(this, 'theme/<?=$_REQUEST["thm"];?>/img/btn/previousover.gif');" onMouseOut="Javascript: replaceImage(this, 'theme/<?=$_REQUEST["thm"];?>/img/btn/previous.gif'); hideTitle();" onMouseMove="Javascript: showTitleLeft(event, '<?=$strprevious;?>:', '<?=$previousTitle->name;?>');"></a>
<?php
}else{
?>
<img src="theme/<?=$_REQUEST["thm"];?>/img/btn/previous.gif" alt="" title="" border="0" class="transparent">
<?php
}
?>
<a href="econsole.php?id=<?=$_REQUEST['id'];?>" target="_parent"><img class="btn" src="theme/<?=$_REQUEST["thm"];?>/img/btn/reload.gif" alt="" title="" border="0" onMouseOver="Javascript: replaceImage(this, 'theme/<?=$_REQUEST["thm"];?>/img/btn/reloadover.gif');" onMouseOut="Javascript: replaceImage(this, 'theme/<?=$_REQUEST["thm"];?>/img/btn/reload.gif'); hideTitle();" onMouseMove="Javascript: showTitleLeft(event, '<?=$strreload;?>', '');"></a>
<?php
if(!empty($_REQUEST['next'])){
?>
<a href="econsole.php?id=<?=$_REQUEST['next'];?>" target="_parent"><img class="btn" src="theme/<?=$_REQUEST["thm"];?>/img/btn/next.gif" alt="" title="" border="0" onMouseOver="Javascript: replaceImage(this, 'theme/<?=$_REQUEST["thm"];?>/img/btn/nextover.gif');" onMouseOut="Javascript: replaceImage(this, 'theme/<?=$_REQUEST["thm"];?>/img/btn/next.gif'); hideTitle();" onMouseMove="Javascript: showTitleLeft(event, '<?=$strnext;?>:', '<?=$nextTitle->name;?>');"></a>
<?php
}else{
?>
<img src="theme/<?=$_REQUEST["thm"];?>/img/btn/next.gif" alt="" title="" border="0" class="transparent"><?php
}
?>&nbsp;&nbsp;&nbsp;
<!--
<a href="#"><img class="btn" src="theme/<?=$_REQUEST["thm"];?>/img/btn/close.gif" alt="" title="" border="0" onClick="Javascript: window.parent.close();" onMouseOver="Javascript: replaceImage(this, 'theme/<?=$_REQUEST["thm"];?>/img/btn/closeover.gif');" onMouseOut="Javascript: replaceImage(this, 'theme/<?=$_REQUEST["thm"];?>/img/btn/close.gif'); hideTitle();" onMouseMove="Javascript: showTitleLeft(event, '<?=$strclose;?>', '');"></a>&nbsp;--></td>
      </tr>
    </table>	
</div>
<div id="boxTitle" style="position: absolute; visibility: hidden;">
<table border="0" cellspacing="2" cellpadding="0" class="alert">
<tr>
  <td>
  <span id="title"></span><br>
  <span id="description"></span>
  </td>
</tr>
</table>
</div>  
</body>
</html>

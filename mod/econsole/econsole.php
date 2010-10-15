<?php
require_once("../../config.php");
require_once("lib.php");

//Get econsole ids
$courseid = get_record("course_modules", "id", $_REQUEST['id'], "", "", "", "", "course, section");
$coursemodinfo = get_record("course", "id", $courseid->course);
$consolestrers = get_all_instances_in_course("econsole", $coursemodinfo);

$i = 1;
foreach($consolestrers as $econsole){
	//Number of consolestrers in topic
	$econsolepages[$econsole->section] = !isset($econsolepages[$econsole->section]) ? 1 : ++$econsolepages[$econsole->section];
	
	//Position of current econsole in topic
	$position[$econsole->section] = !isset($position[$econsole->section]) ? 1 : ++$position[$econsole->section];
	
	//Section
	$econsolesection[$i] = $econsole->section;	
	
	//Match
	if($econsole->coursemodule == $_REQUEST['id']){
		$match = $i;
		$matchposition = $position[$econsole->section];
//		$econsolesection[$i] = $econsole->section;
		$econsoleid[$i][$econsole->section]['id'] = $econsole->id;
		$econsoleid[$i][$econsole->section]['name'] = $econsole->name;
		$econsoleid[$i][$econsole->section]['unitstring'] = $econsole->unitstring;
		$econsoleid[$i][$econsole->section]['showunit'] = $econsole->showunit;
		$econsoleid[$i][$econsole->section]['lessonstring'] = $econsole->lessonstring;
		$econsoleid[$i][$econsole->section]['showlesson'] = $econsole->showlesson;
		$econsoleid[$i][$econsole->section]['glossary'] = $econsole->glossary;
		$econsoleid[$i][$econsole->section]['journal'] = $econsole->journal;
		$econsoleid[$i][$econsole->section]['forum'] = $econsole->forum;
		$econsoleid[$i][$econsole->section]['chat'] = $econsole->chat;
		$econsoleid[$i][$econsole->section]['quiz'] = $econsole->quiz;
		$econsoleid[$i][$econsole->section]['choice'] = $econsole->choice;		
		$econsoleid[$i][$econsole->section]['assignment'] = $econsole->assignment;
		$econsoleid[$i][$econsole->section]['wiki'] = $econsole->wiki;
		$econsoleid[$i][$econsole->section]['theme'] = is_dir("theme/".$econsole->theme) ? $econsole->theme : "default";
	}
	$econsoleid[$i++][$econsole->section]['coursemodule'] = $econsole->coursemodule;
}

//Previous econsole
$previous = !isset($econsoleid[$match-1][$econsolesection[$match]]['coursemodule']) ? "" : $econsoleid[$match-1][$econsolesection[$match]]['coursemodule'];
for($j=$match-1; empty($previous) && $j>0; $j--){
	$previous = $econsoleid[$j][$econsolesection[$j]]['coursemodule'];
}
/*
if(empty($previous)){
	$previous = $econsoleid[$match-1][$econsolesection[$match]-1]['coursemodule'];
}
*/

//Next econsole
$next = !isset($econsoleid[$match+1][$econsolesection[$match]]['coursemodule']) ? "" : $econsoleid[$match+1][$econsolesection[$match]]['coursemodule'];
for($j=$match+1; empty($next) && $j<$i; $j++){
	$next = $econsoleid[$j][$econsolesection[$j]]['coursemodule'];
}
/*
if(empty($next)){
	$next = $econsoleid[$match+1][$econsolesection[$match]+1]['coursemodule'];
}
*/

//Instance modules sequence in match topic
$sequence = get_record("course_sections", "course", $courseid->course, "section", $econsolesection[$match]);
$modulessequence = split(",",$sequence->sequence);

//First instance module in interval
$start = array_search($econsoleid[$match][$econsolesection[$match]]['coursemodule'], $modulessequence)+1;

//Last instance module in interval
$econsoleid[$match+1][$econsolesection[$match]]['coursemodule'] = !isset($econsoleid[$match+1][$econsolesection[$match]]['coursemodule']) ? "" : $econsoleid[$match+1][$econsolesection[$match]]['coursemodule'];
$finish =  array_search($econsoleid[$match+1][$econsolesection[$match]]['coursemodule'], $modulessequence)-1;
if($finish == -1){
	$finish = count($modulessequence);
}

//Match sequence
$i = 0;
for($i=$start; $i<=$finish; $i++){
	$matchsequence[$i-$start] = !isset($modulessequence[$i]) ? "" : $modulessequence[$i];
}

//Course whole
/************************************************************************************/
//Glossary ids
$glossaryids = $econsoleid[$match][$econsolesection[$match]]['glossary'] ? econsole_get_all_instances_in_course("glossary", $coursemodinfo) : "false";

//Chat ids
$chatids =  $econsoleid[$match][$econsolesection[$match]]['chat'] ? econsole_get_all_instances_in_course("chat", $coursemodinfo) : "false";

//Journal id
global $USER;
$journal = get_all_instances_in_course("journal", $coursemodinfo, $USER->id);
$journal[0]->coursemodule = !isset($journal[0]->coursemodule) ? "" : $journal[0]->coursemodule;
$journalid = $econsoleid[$match][$econsolesection[$match]]['journal'] ? $journal[0]->coursemodule : "false";
/************************************************************************************/

//Topic whole
/************************************************************************************/
//Forum ids
$forumids = $econsoleid[$match][$econsolesection[$match]]['forum'] ? econsole_get_all_instances_in_topic("forum", $econsolesection[$match], $coursemodinfo) : 

"false";
/*
//Chat ids
$chatids =  $econsoleid[$match][$econsolesection[$match]]['chat'] ? econsole_get_all_instances_in_topic("chat", $econsolesection[$match], $coursemodinfo) : 

"false";
*/
/************************************************************************************/

//Console only
/************************************************************************************/
//Quiz ids
$matchsequence[] = !isset($matchsequence) ? "" : $matchsequence;
$quizids =  $econsoleid[$match][$econsolesection[$match]]['quiz'] ? econsole_get_all_instances_in_econsole("quiz", $econsolesection[$match], $coursemodinfo, $matchsequence) : "false";

//Wiki ids
$wikiids =  $econsoleid[$match][$econsolesection[$match]]['wiki'] ? econsole_get_all_instances_in_econsole("wiki", $econsolesection[$match], $coursemodinfo, $matchsequence) : "false";

//Choice ids
$choiceids =  $econsoleid[$match][$econsolesection[$match]]['choice'] ? econsole_get_all_instances_in_econsole("choice", $econsolesection[$match], $coursemodinfo, $matchsequence) : "false";

//Assignment ids
$assignmentids =  $econsoleid[$match][$econsolesection[$match]]['assignment'] ? econsole_get_all_instances_in_econsole("assignment", $econsolesection[$match], $coursemodinfo, $matchsequence) : "false";
/************************************************************************************/

//Require login
require_login($courseid->course);

//Log
add_to_log($courseid->course, "econsole", "view", "econsole.php?id=".$_REQUEST['id'], $econsoleid[$match][$econsolesection[$match]]['id'], $_REQUEST['id']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>E-Console</title>
</head>
<frameset rows="30,*,40" frameborder="no" border="0" framespacing="0">
  <frame src="econsoleTop.php?name=<?=$econsoleid[$match][$econsolesection[$match]]['name'];?>&topic=<?=$econsolesection[$match];?>&position=<?=$matchposition;?>&pages=<?=$econsolepages[$econsolesection[$match]];?>&courseid=<?=$courseid->course;?>&thm=<?=$econsoleid[$match][$econsolesection[$match]]['theme'];?>&unitstring=<?=$econsoleid[$match][$econsolesection[$match]]['unitstring'];?>&showunit=<?=$econsoleid[$match][$econsolesection[$match]]['showunit'];?>&lessonstring=<?=$econsoleid[$match][$econsolesection[$match]]['lessonstring'];?>&showlesson=<?=$econsoleid[$match][$econsolesection[$match]]['showlesson'];?>" name="econsoleTop" scrolling="No" noresize="noresize" id="topFrame" title="" />
  <frame src="econsoleMain.php?id=<?=$econsoleid[$match][$econsolesection[$match]]['id'];?>&course=<?=$courseid->course;?>&coursemodule=<?=$econsoleid[$match][$econsolesection[$match]]['coursemodule'];?>&thm=<?=$econsoleid[$match][$econsolesection[$match]]['theme'];?>" name="econsoleMain" id="mainFrame" title="" />
  <frame src="econsoleBottom.php?id=<?=$_REQUEST['id'];?>&journals=<?=$journalid;?>&chats=<?=$chatids;?>&forums=<?=$forumids;?>&glossaries=<?=$glossaryids;?>&wikis=<?=$wikiids;?>&assignments=<?=$assignmentids;?>&quizzes=<?=$quizids;?>&choices=<?=$choiceids;?>&previous=<?=$previous;?>&next=<?=$next;?>&course=<?=$courseid->course;?>&thm=<?=$econsoleid[$match][$econsolesection[$match]]['theme'];?>" name="econsoleBottom" scrolling="No" noresize="noresize" id="bottomFrame" title="" />
</frameset> 
<noframes>
<body>
</body>
</noframes>
</html>

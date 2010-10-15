<?php
require_once("../../config.php");
require_once("lib.php");

$id = optional_param('id', 0, PARAM_INT); //Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  //console ID

if ($id) {
    if (! $cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    if (! $econsole = get_record("econsole", "id", $cm->instance)) {
        error("Course module is incorrect");
    }

} else {
    if (! $econsole = get_record("econsole", "id", $a)) {
        error("Course module is incorrect");
    }
    if (! $course = get_record("course", "id", $econsole->course)) {
        error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("econsole", $econsole->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
}
 
//Require login
require_login($course->id);

//Define reload page
$reload = $_REQUEST['index'] ? "index.php?id=".$course->id : "../../course/view.php?id=".$course->id;
?>
<html>
<head>
<title>E-Console</title>
<script language="javascript">
	var console;
	//Open console
	console = window.open("econsole.php?id=<?=$_REQUEST['id'];?>","econsole","status=0,toolbar=0,location=0,directories=0,menubar=0,scrollbars=1,fullscreen=0,resizable=1,width="+screen.width*0.95+",height="+screen.height*0.85+",top=0, left=0");
	//Console window focus
	console.focus();
	//Reload this window
	window.location="<?=$reload;?>";
</script>
</head>
<body>
</body>
</html>

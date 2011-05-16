<?php

#################################################################################
## This versin is not present in any Moodle CVS and was generated from:
##    $Id: dlg_ins_dragmath.php,v 1.2.4.1 2008/05/14 16:04:28 net-buoy Exp $
## to accomodate changes made in DragMath 0.7.8.1 and relocation of DragMath
## for Moodle 2 purposes to /lib/DragMath.
## In Moodle 2 this file becomes dragmath.php and is located in
##   /lib/editor/tinymce/plugins/dragmath
## while DragMath itself is located in
##   /lib/DragMath
##
#################################################################################

    require_once("../../../../../config.php");

    $id = optional_param('id', SITEID, PARAM_INT);
    $exp = optional_param('exp', '', PARAM_RAW);

    require_course_login($id);
    $urlforcodebase = $CFG->wwwroot.'/lib/editor/htmlarea/custom_plugins/dragmath/DragMath/applet/';
    $drlang = str_replace('_utf8', '', current_language());     // use more standard language codes
    $drlangmapping = array('cs'=>'cz', 'pt_br'=>'pt-br');
    // fix non-standard lang names
    if (array_key_exists($drlang, $drlangmapping)) {
        $drlang = $drlangmapping[$drlang];
    }
    if (!file_exists("$CFG->dirroot/lib/editor/htmlarea/custom_plugins/dragmath/DragMath/applet/lang/$drlang.xml")) {
        $drlang = 'en';
    }
    @header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>"DragMath Equation Editor</title>
<link rel="stylesheet" href="dialog.css" type="text/css" />

<script type="text/javascript">
//<![CDATA[
function Init() {
  // nothing to do :-)
}
function insert(mathExpression) {
  opener.nbWin.retFunc(mathExpression);
  window.close();
  return false;
};

function cancel() {
  window.close();
  return false;
};
function getExportAndInsert(){
    var mathExpression = document.DragMath.getMathExpression();
	insert(mathExpression);

}
//]]>
</script>
</head>
<body onload="Init()">

<applet
    width="540" height="333"
    archive="DragMath.jar"
    code="Display/MainApplet.class"
    codebase="<?php echo $urlforcodebase; ?>"
    name="DragMath">
    <param name="language" value="<?php echo $drlang; ?>" />
    <param name= openWithExpression value= "<?php echo $exp; ?>" >
    <!--param name="outputFormat" value="MoodleTex" /-->
    <param name="outputFormat" value="ASCIIMathML" />
    To use this page you need a current Java-enabled browser.
    Download the latest Java plug-in from
    <a href="http://www.java.com/">Java.com</a>
</applet>


<form name="form">
	<div>
	<button type="button" onclick="return getExportAndInsert();">Insert</button>
	<button type="button" onclick="return cancel();">Cancel</button>
	</div>
</form>

</body>
</html>

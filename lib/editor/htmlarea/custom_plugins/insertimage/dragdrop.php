<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 2/17/11 Time: 2:55 PM
 *
 * Description:
 *
 */
 
    require_once("../../../../../config.php");

    $courseid = optional_param('courseid', SITEID, PARAM_INT);
    $userid = optional_param('userid', -1, PARAM_INT);

    require_login($id);
    require_capability('moodle/course:managefiles', get_context_instance(CONTEXT_COURSE, $courseid,$userid));

    //@header('Content-Type: text/html; charset=utf-8');

    $upload_max_filesize = get_max_upload_file_size($CFG->maxbytes);

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>StandupWeb Drag'n'drop with mootools 1.2</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="description" content="Drag'n'drop script based on mootools 1.2"/>
        <meta name="keywords" content="drag drop dragndrop javascript mootools html5 gmail file upload"/>
        <link rel="shortcut icon" href="images/favicon.ico" />
        <!--[if IE]>
            <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
        <style type='text/css'>
* { margin: 0; padding: 0; }

div#swDragndrop {
    -webkit-border-radius: 5px;
    -moz-border-radius: 5px;
    border-radius: 5px;
	display: block;
	margin:12px;
	padding:0;
	width: 540px;
	height: 200px;
	background-color: #339;
    text-decoration: none;
}

div#swDragndrop ul {
    list-style-type: none;
    margin-top:0;
}

div#swDragndrop ul li {
	width: 220px;
	height: 150px;
	background-color: #55B;
	padding:12px;
	margin: 12px;
	text-align: center;
    -webkit-border-radius: 5px;
    -moz-border-radius: 5px;
    border-radius: 5px;
    float: left;
}

div#swDragndrop ul li form, div#swDragndrop ul li p {
	margin-top: 50px;
	padding-top:0;
	font-size: 14px;
}

div#swDragndrop ul li span {
	display: block;
	margin-top: 30px;
}

div#swDragndrop ul li span.ajax-loader {
    padding-left: 25px;
    margin-left: 30px;
    text-align: left;
    background: url(images/ajax-loader-submit.gif) no-repeat top left;
}

div#swDragndrop.dragndrop:hover {
	background-color: #55F;
    border: 2px solid #fff;
}

div#swDragndrop.dragndrop
{
	text-align:center;
	opacity: 0.5;
    border: 2px dotted #fff;
	left: 0;
	z-index: 1;
}

div#swDragndrop p
{
	color: #fff;
	padding-top: 30px;
	font-size: 25px;
	font-weight: bold;
	text-shadow: 1px 1px #000;
}

div#swDragndrop.dragndrop ul li
{
	opacity: 0.5;
}

div#swDragndrop.dragndrop ul li.loaded
{
	opacity: 1;
}

div#swDragndrop.dragndrop ul li.loaded .progressBar
{
	display: none;
}

div#swDragndrop.dragndrop ul li .progressBar
{
	margin: 5px 0 0 7px;
	width: 200px;
	height: 20px;
	border: 1px solid #000;
	-moz-border-radius: 10px;
	-moz-box-shadow: 1px 1px 2px #fff;
}

div#swDragndrop.dragndrop ul li .progressBar p
{
	width: 20px;
	height: 20px;
	-moz-border-radius: 10px;
	background-color: #1E528C;
	margin-top: 0;
}

div#drag-error {
	padding:0;
	margin:0;
	height:20px;
}
</style>

<script type="text/javascript">
//<![CDATA[
var uploadedfiles = new Array();

function onOK() {

  var param = new Object();
  //var inputs = document.getElementsByTagName('input');
  for(var i = 0; i < uploadedfiles.length; i++) {
     param[i] = '<img src="<?php echo $CFG->wwwroot; ?>/file.php/<?php echo "$courseid/users/$userid/"; ?>'+uploadedfiles[i]+'">';
  }

  opener.nbWin.retFunc(param);
  window.close();
  return false;
};

function onCancel() {
  window.close();
  return false;
};
//[[>
</script>


    </head>
    <body>
		<div id='swDragndrop'></div>
        <script src="lib/mootools/mootools-1.2.4-core-jm.js" type="text/javascript"></script>
        <script type="text/javascript" src="js/swDragndrop.js.php<?php echo "?courseid=".$_GET['courseid']."&userid=".$_GET['userid']; ?>"></script>
        <script language="JavaScript">
	    	window.addEvent('domready',function() {
		    	var dd = new SwDragndrop('swDragndrop');
	    	});
        </script>
        <input type="button" onclick="onOK();" value="Use Images">
    </body>
</html>

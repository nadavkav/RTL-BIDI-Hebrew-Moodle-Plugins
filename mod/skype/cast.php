<?php

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);                 // Course Module ID


    if (! $cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    require_course_login($course, false, $cm);

    if (!$skype = skype_get_skype($cm->instance)) {
        error("Course module is incorrect");
    }

	echo '
	<script type="text/javascript" src="http://skypecasts.skype.com/i/js/Skypecasts.js"></script>
	<script language="javascript" type="text/javascript">
	//<![CDATA[
	document.write("<style type=\"text/css\">");
	document.write("div#skypecasts-block { border: 1px solid #666666; padding: 6px; }");
	document.write("div#skypecasts-block { font-family:arial; font-size: 10px; line-height: 1.5; text-align: center; border: 1px solid #666666; padding: 6px; background-color: #f1f1f1; }");
	document.write("div#skypecasts-block h2 { font-size: 14px; color: #333333; font-weight: bold; }");
	document.write("div#skypecasts-block a, div#skypecasts-block a:hover { color: #006699; text-decoration: none; }");
	document.write("div#skypecasts-block a:hover { text-decoration: underline; }");
	document.write("div#skypecasts-block p { margin: 0 3px; }");
	document.write("div#skypecasts-block p.skypecast-host, div#skypecasts-block p.skypecast-date {  font-size: 9px; color: #999999; }");
	document.write("div#skypecasts-block p.skypecast-date { color: #666666; margin-bottom: 5px; }");
	document.write("div#skypecasts-block p.skypecast-title { font-size: 11px; font-weight: bold; }");
	document.write("div#skypecasts-block hr { background-color: #cccccc; height: 1px; margin: 7px 0; border: none; }");
	document.write("div#skypecasts-block img { border: 1px solid #333333; margin: 7px 0 3px 0; }");
	document.write("</style>");
	//]]></script>

	<div id="skypecasts-block">
	<a target="_blank" href="http://skypecasts.skype.com">
	<img src="pics/skypecast_logo.png" width="146" height="35" style="border: 0;" alt="Skypecasts" />
	</a>
	<hr />

	'.skype_show($skype, $USER, $cm, 'casts').'

	</div>
	';



    exit;

?>
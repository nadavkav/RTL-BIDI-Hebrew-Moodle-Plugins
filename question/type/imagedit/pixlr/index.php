<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Pixlr - development manual</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<!--
		Original script, if you want to look at the source, remove if live.
		<script type="text/javascript" src="/_script/pixlr.js"></script>
	-->
	<script type="text/javascript" src="/moodle-devel/question/type/fileresponse/pixlr/_script/pixlr_minified.js"></script>
	<script type="text/javascript">
		//Global setting edit these
		pixlr.settings.target = 'http://developer.pixlr.com/save_post_modal.php';
		pixlr.settings.exit = 'http://developer.pixlr.com/exit_modal.php';
		pixlr.settings.credentials = true;
		pixlr.settings.method = 'post';
	</script>
</head>
<body>

<h4>Click the image to edit</h4>
<br />


<b>Open "image editor" as overlay</b><br />
<a href="javascript:pixlr.overlay.show({image:'http://developer.pixlr.com/_image/example1.jpg', title:'Example image 1'});"><img src="http://developer.pixlr.com/_image/example1_thumb.jpg" width="250" height="150" title="Edit in pixlr" /></a><br /><br />
<br /><br />

<b>Open "photo express" as overlay</b><br />
<a href="javascript:pixlr.overlay.show({image:'http://developer.pixlr.com/_image/example2.jpg', title:'Example image 2', service:'express'});"><img src="http://developer.pixlr.com/_image/example2_thumb.jpg" width="250" height="150" title="Edit in pixlr" /></a><br /><br />
<br /><br />

<b>Open "image editor" as link</b><br />
<a href="javascript:pixlr.open({image:'http://developer.pixlr.com/_image/example3.jpg', method:'get', title:'Example image 3', service:'express', target:'http://developer.pixlr.com/save_get.php', exit:'http://developer.pixlr.com/'});"><img src="http://developer.pixlr.com/_image/example3_thumb.jpg" width="250" height="150" title="Edit in pixlr" /></a><br /><br />
<br /><br />

<b>Open "photo express" as pop-up</b><br />
<a href="javascript:pixlr.window({image:'http://developer.pixlr.com/_image/example4.jpg', method:'get', title:'Example image 3', service:'express', target:'http://developer.pixlr.com/save_get_pop.php', exit:'http://developer.pixlr.com/exit_pop.php'});"><img src="http://developer.pixlr.com/_image/example4_thumb.jpg" width="250" height="150" title="Edit in pixlr" /></a><br /><br />
<br /><br />

</body>
</html>
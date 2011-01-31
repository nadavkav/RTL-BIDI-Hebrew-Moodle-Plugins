<?php // $Id: insert_table.php,v 1.4 2007/01/27 23:23:44 skodak Exp $
    require("../../../../../config.php");

/*    $id = optional_param('id', SITEID, PARAM_INT);

    require_course_login($id);*/
    @header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title>choose a video</title>

</script>
<style type="text/css">
html, body {
margin: 2px;
background-color: rgb(212,208,200);
font-family: Tahoma, Verdana, sans-serif;
font-size: 11px;
<?php if (right_to_left()) {
  echo "direction:rtl;";
} else {
  echo "direction:ltr;";
} ?>
}
a.videolink:hover {
background-color: darkgreen;
color: #fff;
text-decoration:none;
font-size: 15px;
}

a.videolink {
text-decoration:none;
background-color: #cfc;
font-size: 15px;
}
</style>

</head>
<body>

<div style="text-align:right;"><?php echo get_string("chooseembedsite","insertembed",'',$CFG->dirroot."/lib/editor/htmlarea/custom_plugins/insertembed/lang/");?></div>
<div style="text-align:left;">
<a class="videolink" href="http://video.google.com"> Google Video </a><br/><br/>
<a class="videolink" href="http://www.youtube.com"> You Tube - videos </a><br/><br/>
<a class="videolink" href="http://vimeo.com"> Vimeo - videos </a><br/><br/>
<a class="videolink" href="http://www.voki.com/"> Voki - speach </a><br/><br/>
<a class="videolink" href="http://www.toondoo.com/"> ToonDoo - cartoons </a><br/><br/>
<a class="videolink" href="http://www.teachertube.com/"> Teacher Tube </a><br/><br/>
<a class="videolink" href="http://www.blinkx.com/"> Super Search for Videos</a><br/><br/>
<a class="videolink" href="http://www.scribd.com/"> Scribd - pdf,ppt,docs... </a><br/><br/>
<a class="videolink" href="http://www.slideshare.net//"> SlideShare - pdf,ppt,docs... </a><br/><br/>
<a class="videolink" href="http://www.xtimeline.com/"> TimeLine editor </a><br/><br/>
<a class="videolink" href="http://www.ustream.tv/"> UStream - Live Video Broadcast </a><br/><br/>
<a class="videolink" href="http://www.mogulus.com/"> Mogulus - Live Video Broadcast </a><br/><br/>


</div>

</body>
</html>

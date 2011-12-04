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
<a class="videolink" target="_new" href="http://video.google.com/?hl=en&tab=vv"> Google Video </a><br/><br/>
<a class="videolink" target="_new" href="http://www.mindmeister.com/"> Mind Meister - Mind Maps - מפות חשיבה</a><br/><br/>
<a class="videolink" href="http://www.khanacademy.org"> Khan Academy org </a><br/><br/>
<a class="videolink" href="http://www.refseek.com/directory/educational_videos.html"> 25 Best Sites for Free Educational Videos </a><br/><br/>
<a class="videolink" href="http://vimeo.com"> Vimeo - videos </a><br/><br/>
<a class="videolink" href="http://www.voki.com/"> Voki - speach </a><br/><br/>
<a class="videolink" href="http://www.toondoo.com/"> ToonDoo - cartoons </a><br/><br/>
<a class="videolink" href="http://www.teachertube.com/"> Teacher Tube - Video</a><br/><br/>
<a class="videolink" href="http://www.blinkx.com/"> Meta Search for Videos</a><br/><br/>
<a class="videolink" href="http://www.scribd.com/"> Scribd - pdf,ppt,docs... (אחסון מסמכים)</a><br/><br/>
<a class="videolink" href="http://www.slideshare.net//"> SlideShare - pdf,ppt,docs... (אחסון מצגות)</a><br/><br/>
<a class="videolink" href="http://www.xtimeline.com/"> TimeLine editor </a><br/><br/>
<a class="videolink" href="http://www.ustream.tv/"> UStream - Live Video Broadcast </a><br/><br/>
<a class="videolink" href="http://www.mogulus.com/"> Mogulus - Live Video Broadcast </a><br/><br/>
<a class="videolink" href="http://goanimate.com/"> Go Animate - Create you own animation stories</a><br/><br/>



</div>

</body>
</html>

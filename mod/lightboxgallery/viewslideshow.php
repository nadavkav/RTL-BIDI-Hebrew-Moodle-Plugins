<HTML>
<BODY bgcolor="#FFFFFF">

<?php
/// http://www.maani.us/slideshow/index.php

  require_once('../../config.php');
  $id = optional_param('id', 0, PARAM_INT);

  //include slideshow.php to access the Insert_Slideshow function

  include "slideshow.php";

  //insert the slideshow.swf flash file into the web page
  //tell slideshow.swf to get the slideshow's data from sample.php created in the first step
  //set the slideshow's width to 320 pixels and the height to 240

  echo Insert_Slideshow ( "slideshow.swf", "slideshowdata.php?id=".$id, 640, 480 );

?>

</BODY>
</HTML>

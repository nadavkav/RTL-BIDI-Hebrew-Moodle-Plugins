<?php
  ///http://www.maani.us/slideshow/index.php
  require_once('../../config.php');
  global $COURSE,$CFG;

  //include slideshow.php in your script
  require_once ("slideshow.php");

  $imagedir = optional_param('imagedir', '', PARAM_TEXT);

  $directory = opendir($CFG->dataroot."/".$imagedir);
  $imagelist = array();
  while (false !== ($file = readdir($directory))) {
      if ($file == "." || $file == "..") {
	  continue;
      }
      // notice : function mime-content-type() is depricated in php 5.3
      // http://us3.php.net/manual/en/function.mime-content-type.php
      // this IF should change in the near future :-)
      if ( is_file("$CFG->dataroot/$imagedir/$file") and preg_match("/jpeg|jpg/i", $file) ) {
		$imagelist[] = $imagedir."/".$file;
		//echo "$file =".preg_match("/jpeg|jpg/i", $file)."<br/>";
      }

  }
  closedir($directory);

	//show the control bar
	$slideshow [ 'control' ][ 'bar_visible' ] = "on";
	//$slideshow [ 'license' ] = "DUBKOMOOWEKJUJHLRTO9DN6IKN49JK";

  $i=0;
  foreach($imagelist as $image) {
    $slideshow[ 'slide' ][ $i++ ] = array ( 'url' => $CFG->wwwroot."/file.php/".$image );
  }

  //send the slideshow data
  Send_Slideshow_Data ( $slideshow );

?>

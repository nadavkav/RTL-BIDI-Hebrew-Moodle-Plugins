<?php
  ///http://www.maani.us/slideshow/index.php
  
  require_once('../../config.php');
  //include($CFG->libdir . '/filelib.php');
  include('lib.php');

  //include slideshow.php in your script
  include "slideshow.php";

  $id = optional_param('id', 0, PARAM_INT);
  $l = optional_param('l', 0, PARAM_INT);

  if ($id and $id >= 1) {
      if (! $cm = get_coursemodule_from_id('lightboxgallery', $id)) {
	  error('Course module ID was incorrect');
      }   

      if (! $course = get_record('course', 'id', $cm->course)) {
	  error('Course is misconfigured');
      }    
      if (! $gallery = get_record('lightboxgallery', 'id', $cm->instance)) {
	  error('Course module is incorrect');
      }
  } else {
      if (! $gallery = get_record('lightboxgallery', 'id', $l)) {
	  error('Course module is incorrect');
      }
      if (! $course = get_record('course', 'id', $gallery->course)) {
	  error('Course is misconfigured');
      }
      if (! $cm = get_coursemodule_from_instance('lightboxgallery', $gallery->id, $course->id)) {
	  error('Course module ID was incorrect');
      }
  }

  //show the control bar
  $slideshow [ 'control' ][ 'bar_visible' ] = "on";

  $dataroot = $CFG->dataroot . '/' . $course->id . '/' . $gallery->folder;
  $webroot = lightboxgallery_get_image_url($gallery->id);

  $allimages = lightboxgallery_directory_images($dataroot);

  $i=0;
  foreach($allimages as $image) {
    $slideshow[ 'slide' ][ $i++ ] = array ( 'url' => $webroot."/".$image );
  }

  //add 3 slides
  // $slideshow[ 'slide' ][ 0 ] = array ( 'url' => "http://www.tikshuv.org.il/moodle/mod/lightboxgallery/pic.php/172/41561bc5.jpg" );
  // $slideshow[ 'slide' ][ 1 ] = array ( 'url' => "http://www.tikshuv.org.il/moodle/mod/lightboxgallery/pic.php/172/93227-1.jpg" );
  // $slideshow[ 'slide' ][ 2 ] = array ( 'url' => "http://www.tikshuv.org.il/moodle/mod/lightboxgallery/pic.php/172/bg3.jpg" );
  // $slideshow[ 'slide' ][ 3 ] = array ( 'url' => "http://www.tikshuv.org.il/moodle/mod/lightboxgallery/pic.php/172/93227-1.jpg" );

							  
  //send the slideshow data
  Send_Slideshow_Data ( $slideshow );

?>

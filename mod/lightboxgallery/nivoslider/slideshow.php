<?php
  ///http://nivo.dev7studios.com/

  require_once('../../../config.php');
  include('../lib.php');

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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="rtl" lang="he" xml:lang="he">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="stylesheet" href="<?php echo $CFG->wwwroot.'/mod/lightboxgallery/nivoslider/' ?>nivo-slider.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="<?php echo $CFG->wwwroot.'/mod/lightboxgallery/nivoslider/' ?>custom-nivo-slider.css" type="text/css" media="screen" />

    <!--script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js" type="text/javascript"></script-->
    <script src="<?php echo $CFG->wwwroot.'/mod/lightboxgallery/nivoslider/' ?>jquery-1.3.2.js" type="text/javascript"></script>
    <script src="<?php echo $CFG->wwwroot.'/mod/lightboxgallery/nivoslider/' ?>jquery.nivo.slider.pack.js" type="text/javascript"></script>

</head>

<body>

<div id="slider" style="margin:auto;width:640px;height:480px;">
<?php

  $dataroot = $CFG->dataroot . '/' . $course->id . '/' . $gallery->folder;
  $webroot = lightboxgallery_get_image_url($gallery->id);

  $allimages = lightboxgallery_directory_images($dataroot);

  $i=0;
  foreach($allimages as $image) {
    echo '<a href=""><img width="640px" height="480px" src="'.$webroot."/".$image.'" alt="" title=""></a>';
    $i++ ;
  }
?>

</div>

<div id="htmlcaption" class="nivo-html-caption">
    <strong>This</strong> is an example of a <em>HTML</em> caption with <a href="#">a link</a>.
</div>

<script type="text/javascript">
$(window).load(function() {
  $('#slider').nivoSlider({
    effect:'random', //Specify sets like: 'fold,fade,sliceDown'
    slices:15,
    animSpeed:500, //Slide transition speed
    pauseTime:3000,
    startSlide:0, //Set starting Slide (0 index)
    directionNav:true, //Next & Prev
    directionNavHide:true, //Only show on hover
    controlNav:true, //1,2,3...
    controlNavThumbs:true, //Use thumbnails for Control Nav
      controlNavThumbsFromRel:false, //Use image rel for thumbs
    controlNavThumbsSearch: '.jpg', //Replace this with...
    controlNavThumbsReplace: '_thumb.jpg', //...this in thumb Image src
    keyboardNav:true, //Use left & right arrows
    pauseOnHover:true, //Stop animation while hovering
    manualAdvance:false, //Force manual transitions
    captionOpacity:0.8, //Universal caption opacity
    beforeChange: function(){},
    afterChange: function(){},
    slideshowEnd: function(){} //Triggers after all slides have been shown
  });
});
</script>

</body>
</html>
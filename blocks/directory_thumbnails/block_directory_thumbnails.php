<?php
// This block display a limited list of images in a course's Folder
//
// code    : Nadav Kavalerchik (nadavkav@gmail.com) :-)
// version : 0.1 (2009092001)
//
class block_directory_thumbnails extends block_base {

  function init() {
    $this->title = get_string('imagegallery', 'block_directory_thumbnails');
    $this->version = 2009090601;
  }

  function get_content() {
    global $COURSE, $CFG;

    if ($this->content !== NULL) {
    return $this->content;
    }

    if(empty($this->config->width)){
      $width = 220;
    } else {
      $width = $this->config->width;
    }

    if(empty($this->config->height)){
      $height = 180;
    } else {
      $height = $this->config->height;
    }

    if(empty($this->config->maxcount)){
      $maxcount = 5;
    } else {
      $maxcount = $this->config->maxcount;
    }

    if(empty($this->config->folder)){
      $folder = "/";
    } else {
      $folder = $this->config->folder;
    }

    $this->content = new stdClass;
    $this->content->text = !empty($this->config->text) ? $this->config->text."...<br/>" : "...<br/>";

    // get all the images from the folder
    $directory = opendir($CFG->dataroot."/".$COURSE->id."/".$folder);
    $imagelist = array();
    while (false !== ($file = readdir($directory))) {
        if ($file == "." || $file == "..") {
          continue;
        }
        // notice : function mime-content-type() is depricated in php 5.3
        // http://us3.php.net/manual/en/function.mime-content-type.php
        // this IF should change in the near future :-)
        //$filemime = mime_content_type($CFG->dataroot."/".$COURSE->id."/".$folder."/".$file);
        if ( is_file($CFG->dataroot."/".$COURSE->id."/".$folder."/".$file) and  preg_match("/(png|jpeg|jpg)/i",$file) ) {
          $imagelist[] = $COURSE->id."/".$folder."/".$file;
        }

    }
    closedir($directory);

	if ( empty($imagelist) ) {
		$this->content = get_string('noimages','block_directory_thumbnails');
		return $this->content;
	}

	if ( $this->config->randomize == '1' ) {
		$tmpcount = 0;
		while ( $tmpcount++ < $maxcount ) {
			$image = $imagelist[rand(0 , count($imagelist) - 1)];
			$this->content->text .= "<img onclick=\"window.open('$CFG->wwwroot/file.php/$image','imagepopup','width=800,height=600,resizable=yes,scrollbars=no');\" height=\"$height\" width=\"$width\" src=\"$CFG->wwwroot/file.php/$image\"><hr>";
		}
	} else {
		$tmpcount = 0;
		foreach($imagelist as $image) {
		if ($tmpcount++ >= $maxcount) continue;
		//$this->content->text .= "<a target=\"_blank\" href=\"$CFG->wwwroot/file.php/$image\" ><img height=\"$height\" width=\"$width\" src=\"$CFG->wwwroot/file.php/$image\"></a><hr>";
		$this->content->text .= "<img onclick=\"window.open('$CFG->wwwroot/file.php/$image','imagepopup','width=800,height=600,resizable=yes,scrollbars=no');\" height=\"$height\" width=\"$width\" src=\"$CFG->wwwroot/file.php/$image\"><hr>";
		}
	}
    //$this->content->footer = !empty($this->config->text) ? $this->config->text : '';

    return $this->content;
  }

  function instance_allow_config() {
    return true;
  }

  function specialization() {
    if(!empty($this->config->title)){
      $this->title = $this->config->title;
    }else{
      $this->config->title = get_string('title','block_directory_thumbnails');
    }
    if(empty($this->config->text)){
      $this->config->text = get_string('imagegallery','block_directory_thumbnails');
    }
  }

  function applicable_formats() {
    return array(
	    'all' => false,
	    'course-view' => true,
	    'category' => true
    );
  }

}
?>
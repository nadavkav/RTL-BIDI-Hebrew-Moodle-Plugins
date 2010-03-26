<?php
// This block display a Slideshow of a JPGs in a course's Folder
// using slideshow component ""PHP/SWF Slideshow"" written by :maani.us
// please see more info: http://www.maani.us/slideshow/
//
// code    : Nadav Kavalerchik (nadavkav@gmail.com) :-)
// version : 0.1 (2009083001)
//
class block_directory_slideshow extends block_base {

  function init() {
    $this->title = get_string('imagegallery', 'block_directory_slideshow');
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

    if (!function_exists('Insert_Slideshow')) require_once("slideshow.php");

    if(empty($this->config->imagedir)){
      $data = get_string('content','block_directory_slideshow');
    } else {
      $data =  "<div style=\"text-align:center;\"><br/><br/>";
      $data .= Insert_Slideshow ( "$CFG->wwwroot/blocks/directory_slideshow/slideshow.swf",
		"$CFG->wwwroot/blocks/directory_slideshow/slideshowdata.php?imagedir=$COURSE->id/".$this->config->imagedir, $width, $height,"DUBKOMOOWEKJUJHLRTO9DN6IKN49JK" );
      $data .= "</div>";
    }

    $this->content = new stdClass;
    $this->content->text = $data;
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
      $this->config->title = get_string('title','block_directory_slideshow');
    }
    if(empty($this->config->text)){
      $this->config->text = get_string('imagegallery','block_directory_slideshow');
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

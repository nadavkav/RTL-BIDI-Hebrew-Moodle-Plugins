<?php
// This block display a Slideshow of Images
// which are located inside a course's Folder.
//
// Images can be filtered by image type png|jpeg|jpg|gif
//
// A teacher can choose Transitions by which the Slideshow is played
//
// based on jQuery library from: http://malsup.com/jquery/cycle/
//
// code    : Nadav Kavalerchik (nadavkav@gmail.com) :-)
// version : 0.1 (2010060604)
//
class block_jquery_slideshow extends block_base {

  function init() {
    $this->title = get_string('jqueryslideshow', 'block_jquery_slideshow');
    $this->version = 2010110101;
    $this->cron = 1;
  }

  function get_content() {
    global $COURSE, $CFG;

    if ($this->content !== NULL) {
		return $this->content;
    }

    if(empty($this->config->width)){
      $this->config->width = 180;
    }

    if(empty($this->config->height)){
      $this->config->height = 160;
    }

    if(empty($this->config->maxfiles)){
      $this->config->maxfiles = 30;
    }

    if(empty($this->config->filesfilter) or $this->config->filesfilter == ''){
      $this->config->filesfilter = "jpeg|jpg|png|gif";
    }

//     if ($this->config->includejpeg) {
//         $this->config->filesfilter = "jpeg|jpg";
//     }
//     if ($this->config->includepng) {
//         $this->config->filesfilter .= "|png";
//     }
//     if ($this->config->includegif) {
//         $this->config->filesfilter .= "|gif";
//     }
//
//     $this->config->filesfilter = ltrim($this->config->filesfilter,'|');

    if (!empty($this->config->imagedir)) {

    $data = '
<style type="text/css">
.slideshow { height: '.(string)((int)$this->config->height+30).'px; width: '.(string)((int)$this->config->width+30).'px; margin: auto }
.slideshow img { padding: 15px; border: 1px solid #ccc; background-color: #eee; }
</style>

<!-- include jQuery library -->
<!--script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script-->
<script src="'.$CFG->wwwroot.'/blocks/jquery_slideshow/jquery.min.js" type="text/javascript"></script>
<!-- include Cycle plugin -->
<!--script src="http://cloud.github.com/downloads/malsup/cycle/jquery.cycle.all.latest.js" type="text/javascript"></script-->
<script src="'.$CFG->wwwroot.'/blocks/jquery_slideshow/jquery.cycle.all.latest.js" type="text/javascript"></script>
<script type="text/javascript">
    $(\'.slideshow\').cycle({
        fx: \''.$this->config->transition.'\', // choose your transition type, ex: fade, scrollUp, shuffle, etc...
        timeout: \''.(string)((int)($this->config->timeout)*1000).'\' //
    });
</script>
';

        $directory = opendir($CFG->dataroot."/".$COURSE->id."/".$this->config->imagedir);
        $imagelist = array();
        $pregfilesfilter = "/{$this->config->filesfilter}/i";
        $maxfilescounter = 0;
        while (false !== ($file = readdir($directory))) {
            if ($file == "." || $file == ".." || $maxfilescounter++ >= $this->config->maxfiles) {
            continue;
            }
            // notice : function mime-content-type() is depricated in php 5.3
            // http://us3.php.net/manual/en/function.mime-content-type.php
            // this IF should change in the near future :-)
            if ( is_file("$CFG->dataroot/{$COURSE->id}/{$this->config->imagedir}/$file") and preg_match($pregfilesfilter , $file) ) {
                $imagelist[] = $COURSE->id."/".$this->config->imagedir."/".$file;
            }

        }
        closedir($directory);

        if (!empty($imagelist)) {
        $data .= $this->config->text;
        $data .= "<div class=\"slideshow\" style=\"position: relative;\">";

        // generate image list
        foreach($imagelist as $image) {
             $data .= "<img width=\"{$this->config->width}\" height=\"{$this->config->height}\" src=\"$CFG->wwwroot/file.php/$image\">";
        }
        $data .= "</div>";
        } else {
            // no images inside the choosen folder, please pick up a new folder or change files filter
            $data = '<div style=\"text-align:center;\"><br/>'.get_string('noimagesinfolder','block_jquery_slideshow').'<br/><br/></div>';
        }
        } else {
            // please setup initial folder, where iamges are.
            $data = '<div style=\"text-align:center;\"><br/>'.get_string('initialsetup','block_jquery_slideshow').'<br/><br/></div>';
    }


    $this->content = new stdClass;
    $this->content->text = $data;
    $this->content->footer = '';  // !empty($this->config->text) ? $this->config->text : '';

    return $this->content;
  }

//     function instance_allow_config() {
//         return true;
//     }

    function instance_allow_multiple(){
        return true;
    }

    function specialization() {
        if(!empty($this->config->title)){
            $this->title = $this->config->title;
        } else {
            $this->config->title = get_string('title','block_jquery_slideshow');
        }

        //if(empty($this->config->text)){
        //    $this->config->text = get_string('jqueryslideshow','block_jquery_slideshow');
        //}
    }

/// enable this function if you wish to limit the availability of this block to specific pages
//   function applicable_formats() {
//     // you can play around with the availability of this block ;-)
//     return array(
//      'all' => false, // no available to site level (front page)
//      'course-view' => true, // available on Course level
//      'category' => true // available on Categories level
//     );
//   }

    /**
     * Function to be run periodically according to the moodle cron
     * This function searches for things that need to be done, such
     * as sending out mail, toggling flags etc ...
     *
     * @uses $CFG
     * @return boolean
     * @todo Finish documenting this function
     **/
    function cron() {

       global $CFG;

        // Get the block type from the name
        $blocktype = get_record('block', 'name', 'jquery_slideshow');
        $ok = set_field('block','lastcron',time(),'name','jquery_slideshow');

        if (time() - $blocktype->lastcron < 8640) return; // run only one time per 24h

        // Get the instances of the block
        $instances = get_records('block_instance','blockid',$blocktype->id);

        // Iterate over the instances
        foreach ($instances as $instance) {

            // Recreate block object
            $block = block_instance('jquery_slideshow', $instance);

            // $block is now the equivalent of $this in 'normal' block
            // usage, e.g.
            //$someconfigitem = $block->config->imagedir;
            mtrace('Course ID:'.$block->instance->pageid);

            $directory = opendir($CFG->dataroot."/".$block->instance->pageid."/".$block->config->imagedir);
            //$imagelist = array();
            $pregfilesfilter = "/{$this->config->filesfilter}/i";
            $maxfilescounter = 0;
            while (false !== ($file = readdir($directory))) {
               if ($file == "." || $file == ".." || $maxfilescounter++ >= $block->config->maxfiles) {
               continue;
               }
               // notice : function mime-content-type() is depricated in php 5.3
               // http://us3.php.net/manual/en/function.mime-content-type.php
               // this IF should change in the near future :-)
               if ( is_file("$CFG->dataroot/{$block->instance->pageid}/{$block->config->imagedir}/$file") and preg_match($pregfilesfilter , $file) ) {
                   //$imagelist[] = $block->instance->pageid."/".$block->config->imagedir."/".$file;
                   mtrace("{$CFG->dataroot}/{$block->instance->pageid}/{$block->config->imagedir}/{$file}");
                   $ok = gd_resize_image("{$CFG->dataroot}/{$block->instance->pageid}/{$block->config->imagedir}/{$file}" ,
                       "{$CFG->dataroot}/{$block->instance->pageid}/{$block->config->imagedir}/{$file}" ,640,480 );
               }

            }
            closedir($directory);
        }

    }


}

function gd_resize_image($image, $newimage, $newwidth, $newheight) {
    global $CFG;

    if (filesize($image) < 250000) return false;  // no need to resize

     $gdversion = check_gd_version();

     if ( $gdversion < 1 ) {
         return false;
     }

     $size = getimagesize($image);

     $width  = $size[0];
     $height = $size[1];
     $type   = $size[2];

     if ($im = read_image_from_file($image, $type)) {
         if ($newheight && ($width < $height)) {
             $newwidth = ($newheight / $height) * $width;
         } else {
             $newheight = ($newwidth / $width) * $height;
         }

            echo get_string('originaldimentions','files','',$CFG->dirroot.'/files/lang/').": ".get_string('width','files','',$CFG->dirroot.'/files/lang/')." = $width , ".get_string('height','files','',$CFG->dirroot.'/files/lang/')." = $height \n";
            echo get_string('newdimentions','files','',$CFG->dirroot.'/files/lang/').": ".get_string('width','files','',$CFG->dirroot.'/files/lang/')." = $newwidth , ".get_string('height','files','',$CFG->dirroot.'/files/lang/')." = $newheight \n";


         if ($gdversion > 1) {
             $im2 = ImageCreateTrueColor($newwidth,$newheight);
         } else {
             $im2 = ImageCreate($newwidth,$newheight);
         }

         if ($gdversion > 1) {
             ImageCopyResampled($im2,$im,0,0,0,0,$newwidth,$newheight,$width,$height);
         } else {
             ImageCopyResized($im2,$im,0,0,0,0,$newwidth,$newheight,$width,$height);
         }

            echo get_string('originalsize','files','',$CFG->dirroot.'/files/lang/')." = ".filesize($image)." \n";
            clearstatcache();
         unlink($image); // delete image before overwrite
         if (write_image_to_file($im2, $newimage, $type)) {
            echo get_string('newsize','files','',$CFG->dirroot.'/files/lang/')." = ".filesize($newimage)." \n";
            return true;
         }
     }
     return false;
 }

 function read_image_from_file ($filename, $type) {
     $imagetypes = ImageTypes();

     switch ($type) {
         case 1 :
             if ($imagetypes & IMG_GIF) {
                 return $im = ImageCreateFromGIF($filename);
             }
         break;
         case 2 :
             if ($imagetypes & IMG_JPEG) {
                 return ImageCreateFromJPEG($filename);
             }
             break;
         case 3 :
             if ($imagetypes & IMG_PNG)
                 return ImageCreateFromPNG($filename);
             break;
         default:
         return 0;
     }
 }

function write_image_to_file ($im, $filename, $type) {
     switch ($type) {
         case 1 : return ImageGIF($im, $filename); break;
         case 2 : return ImageJpeg($im, $filename,75); break;
         case 3 : return ImagePNG($im, $filename); break;
         default: return false;
     }
 }

?>
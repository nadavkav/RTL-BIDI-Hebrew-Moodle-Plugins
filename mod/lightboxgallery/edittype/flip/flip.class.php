<?php

define('FLIP_VERTICAL', 1);
define('FLIP_HORIZONTAL', 2);

class edittype_flip extends edittype_base {

    function edittype_flip($gallery, $image, $tab) {
        parent::edittype_base($gallery, $image, $tab, true);      
    }

    function output() {
        $result = get_string('selectflipmode', 'lightboxgallery').'<br /><br />'.
                  '<label><input type="radio" name="mode" value="'.FLIP_VERTICAL.'" />Vertical</label><br />'.
                  '<label><input type="radio" name="mode" value="'.FLIP_HORIZONTAL.'" />Horizontal</label>'.
                  '<br /><br /><input type="submit" value="'.get_string('edit_flip', 'lightboxgallery').'" />';
        return $this->enclose_in_form($result);        
    }

    function process_form() {
        global $CFG;
        $mode = required_param('mode', PARAM_INT);
        $info = lightboxgallery_image_info($this->imagepath);
        $w = $info->imagesize[0];
        $h = $info->imagesize[1];
        if ($im = lightboxgallery_imagecreatefromtype($info->imagesize[2], $this->imagepath)) { 
            $truecolor = (function_exists('ImageCreateTrueColor') and $CFG->gdversion >= 2);
            $flipped = ($truecolor ? ImageCreateTrueColor($w, $h) : ImageCreate($w, $h));
            if ($mode & FLIP_VERTICAL) {
                if ($truecolor) {
                    for ($x = 0; $x < $w; $x++) {
                        for ($y = 0; $y < $h; $y++) {
                            imagecopy($flipped, $im, $w - $x - 1, $y, $x, $y, 1, 1);
                        }
                    }
                } else {
                    for ($y = 0; $y < $h; $y++) {
                        imagecopy($flipped, $im, 0, $y, 0, $h - $y - 1, $w, 1);
                    }
                }
            }
            if ($mode & FLIP_HORIZONTAL) {
                if ($truecolor) {
                    for ($x = 0; $x < $w; $x++) {
                        for ($y = 0; $y < $h; $y++) {
                            imagecopy($flipped, $im, $x, $h - $y - 1, $x, $y, 1, 1);
                        }
                    }
                } else {
                    for ($x = 0; $x < $w; $x++) {
                        imagecopy($flipped, $im, $x, 0, $w - $x - 1, 0, 1, $h);
                    }
                }
            }
            $this->save_image_resource($flipped, $info->imagesize[2]);
        }
    }

}

?>

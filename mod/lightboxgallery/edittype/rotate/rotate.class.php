<?php

class edittype_rotate extends edittype_base {

    function edittype_rotate($gallery, $image, $tab) {
        parent::edittype_base($gallery, $image, $tab, true);      
    }

    function output() {
        $result = get_string('selectrotation', 'lightboxgallery').'<br /><br />'.
                  '<label><input type="radio" name="angle" value="90" />-90&#176;</label>'.
                  '<label><input type="radio" name="angle" value="180" />180&#176;</label>'.
                  '<label><input type="radio" name="angle" value="270" />90&#176;</label>'.
                  '<br /><br /><input type="submit" value="'.get_string('edit_rotate', 'lightboxgallery').'" />';
        return $this->enclose_in_form($result);        
    }

    function process_form() {
        $angle = required_param('angle', PARAM_INT);
        $info = lightboxgallery_image_info($this->imagepath);
        if ($im = lightboxgallery_imagecreatefromtype($info->imagesize[2], $this->imagepath)) {
            $rotated = imagerotate($im, $angle, 0);
            $this->save_image_resource($rotated, $info->imagesize[2]);
        }
    }

}

?>

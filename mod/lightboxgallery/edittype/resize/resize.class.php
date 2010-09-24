<?php

class edittype_resize extends edittype_base {

    var $buttonresize;
    var $buttonscale;
    var $resizeoptions;

    function edittype_resize($gallery, $image, $tab) {
        $this->buttonresize = get_string('edit_resize', 'lightboxgallery');
        $this->buttonscale = get_string('edit_resizescale', 'lightboxgallery');
        $this->resizeoptions = lightboxgallery_resize_options();
        parent::edittype_base($gallery, $image, $tab, true);     
    }

    function output() {
        $info = lightboxgallery_image_info($this->imagepath);

        $currentsize = sprintf('%s: %dx%d', get_string('currentsize', 'lightboxgallery'), $info->imagesize[0], $info->imagesize[1]).'<br /><br />';

        $sizeselect = '<select name="size">';
        foreach ($this->resizeoptions as $index => $option) {
            $sizeselect .= '<option value="' . $index . '">' . $option . '</option>';
        }
        $sizeselect .= '</select>&nbsp;<input type="submit" name="button" value="' . $this->buttonresize . '" /><br /><br />';

        $scaleselect = '<select name="scale">'.
                       '  <option value="150">150&#37;</option>'.
                       '  <option value="125">125&#37;</option>'.
                       '  <option value="75">75&#37;</option>'.
                       '  <option value="50">50&#37;</option>'.
                       '  <option value="25">25&#37;</option>'.
                       '</select>&nbsp;<input type="submit" name="button" value="' . $this->buttonscale . '" />';

        return $this->enclose_in_form($currentsize . $sizeselect . $scaleselect);        
    }

    function process_form() {
        $button = required_param('button', PARAM_TEXT);
        switch ($button) {
            case $this->buttonresize:
                $size = required_param('size', PARAM_INT);
                list($width, $height) = explode('x', $this->resizeoptions[$size]);
            break;
            case $this->buttonscale:
                $scale = required_param('scale', PARAM_INT);
                $imagesize = getimagesize($this->imagepath);
                $width = $imagesize[0] * ($scale / 100);
                $height = $imagesize[1] * ($scale / 100);
            break;
        }
        $info = lightboxgallery_image_info($this->imagepath);
        if ($im = lightboxgallery_imagecreatefromtype($info->imagesize[2], $this->imagepath)) {
            $resized = lightboxgallery_resize_image($im, $info, $width, $height);
            $this->save_image_resource($resized, $info->imagesize[2]);
        }
    }

}

?>

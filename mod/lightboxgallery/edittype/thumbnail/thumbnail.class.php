<?php

class edittype_thumbnail extends edittype_base {

    function edittype_thumbnail($gallery, $image, $tab) {
        parent::edittype_base($gallery, $image, $tab, true);      
    }

    function output() {
        $info = lightboxgallery_image_info($this->imagepath);
        $result = get_string('selectthumbpos', 'lightboxgallery').'<br /><br />';
        if ($info->imagesize[0] < $info->imagesize[1]) {
            $result .= '<label><input type="radio" name="move" value="1" />'.get_string('dirup', 'lightboxgallery').'</label>&nbsp;'.
                       '<label><input type="radio" name="move" value="2" />'.get_string('dirdown', 'lightboxgallery').'</label>';
        } else {
            $result .= '<label><input type="radio" name="move" value="3" />'.get_string('dirleft', 'lightboxgallery').'</label>&nbsp;'.
                       '<label><input type="radio" name="move" value="4" />'.get_string('dirright', 'lightboxgallery').'</label>';
        }
        $result .= '<br /><br />'.get_string('thumbnailoffset', 'lightboxgallery').': <input type="text" name="offset" value="20" size="4" /><br /><br />'.
                   '<input type="submit" value="'.get_string('move').'" />&nbsp;<input type="submit" name="reset" value="'.get_string('reset').'" />';
        return $this->enclose_in_form($result);        
    }

    function process_form() {
        $reset = optional_param('reset', '', PARAM_TEXT);
        if ($reset) {
            lightboxgallery_image_thumbnail($this->gallery->course, $this->gallery, $this->image);
        } else {
            $move = required_param('move', PARAM_INT);
            $offset = optional_param('offset', 20, PARAM_INT);
            switch ($move) {
                case 1:
                    lightboxgallery_image_thumbnail($this->gallery->course, $this->gallery, $this->image, 0, -$offset);
                    break;
                case 2:
                    lightboxgallery_image_thumbnail($this->gallery->course, $this->gallery, $this->image, 0, $offset);
                    break;
                case 3:
                    lightboxgallery_image_thumbnail($this->gallery->course, $this->gallery, $this->image, -$offset, 0);
                    break;
                case 4:
                    lightboxgallery_image_thumbnail($this->gallery->course, $this->gallery, $this->image, $offset, 0);
                    break;
            }
        }
    }

}

?>

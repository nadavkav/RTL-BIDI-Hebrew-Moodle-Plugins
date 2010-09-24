<?php

class edittype_base {

    var $gallery;
    var $image;
    var $imagepath;
    var $tab;
    var $showthumb;

    function edittype_base($_gallery, $_image, $_tab, $_deletethumb = false, $_showthumb = true) {
        global $CFG;
        $this->gallery = $_gallery;
        $this->image = $_image;
        $this->imagepath = $CFG->dataroot.'/'.$_gallery->course.'/'.$_gallery->folder.'/'.$_image;
        $this->tab = $_tab;
        $this->showthumb = $_showthumb;
        if ($_deletethumb && $this->processing()) {
            $thumb = $CFG->dataroot.'/'.$_gallery->course.'/'.$_gallery->folder.'/_thumb/'.$_image.'.jpg';
            unlink($thumb);
        }
    }

    function save_image_resource($imageres, $type) {
        switch ($type) {
            case 1:
                $function = 'ImageGIF';
                break;
            case 2:
                $function = 'ImageJPEG';
                break;
            case 3:
                $function = 'ImagePNG';
                break;
        }
        if (function_exists($function)) {
            return $function($imageres, $this->imagepath, ($type == 3 ? 9 : 100));
        } else {
            return false;
        }
    }

    function processing() {
        return optional_param('process', false, PARAM_BOOL);
    }

    function enclose_in_form($text, $name='') {
        global $CFG, $USER;
        return '<form action="'.$CFG->wwwroot.'/mod/lightboxgallery/imageedit.php" method="post" name="'.$name.'">'.
               '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'" />'.
               '<input type="hidden" name="id" value="'.$this->gallery->id.'" />'.
               '<input type="hidden" name="image" value="'.$this->image.'" />'.
               '<input type="hidden" name="tab" value="'.$this->tab.'" />'.
               '<input type="hidden" name="process" value="1" />'.$text.'</form>';
    }

}

?>

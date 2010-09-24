<?php

require_once($CFG->libdir.'/gdlib.php');

class edittype_crop extends edittype_base {

    function edittype_crop($gallery, $image, $tab) {
        parent::edittype_base($gallery, $image, $tab, true, false);      
    }

    function output() {
        global $CFG;
        $result =  '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/lightboxgallery/js/prototype.js"></script>    
                    <script type="text/javascript" src="'.$CFG->wwwroot.'/mod/lightboxgallery/js/scriptaculous.js?load=builder,dragdrop"></script>
                    <script type="text/javascript" src="'.$CFG->wwwroot.'/mod/lightboxgallery/js/cropper.js"></script>
                    <script type="text/javascript" charset="utf-8">
                        function onEndCrop( coords, dimensions ) {
                            $( \'x1\' ).value = coords.x1;
                            $( \'y1\' ).value = coords.y1;
                            $( \'x2\' ).value = coords.x2;
                            $( \'y2\' ).value = coords.y2;
                            $( \'cropInfo\' ).innerHTML = \''.get_string('from').': \' + coords.x1 + \'x\' + coords.y1 + \', '.get_string('size').': \' + dimensions.width + \'x\' + dimensions.height;
                        }
                        Event.observe( 
                            window, 
                            \'load\', 
                            function() { 
                                new Cropper.Img( 
                                    \'cropImage\',
                                    {
                                        onEndCrop: onEndCrop 
                                    }
                                ) 
                            }
                        );
                    </script>';
        $result .= '<input type="hidden" name="x1" id="x1" value="0" />
                    <input type="hidden" name="y1" id="y1" value="0" />
                    <input type="hidden" name="x2" id="x2" value="0" />
                    <input type="hidden" name="y2" id="y2" value="0" />
                    <table>
                      <tr>
                        <td>'.lightboxgallery_make_img(lightboxgallery_get_image_url($this->gallery->id, $this->image), 'cropImage').'</td>
                      </tr>
                      <tr>
                        <td><span id="cropInfo">&nbsp;</span></td>
                      </tr>
                      <tr>
                        <td><input type="submit" value="'.get_string('savechanges').'" /></td>
                      </tr>
                    </table>';

        return $this->enclose_in_form($result, 'coord');        
    }

    function process_form() {
        $x1 = required_param('x1', PARAM_INT);
        $y1 = required_param('y1', PARAM_INT);
        $x2 = required_param('x2', PARAM_INT);
        $y2 = required_param('y2', PARAM_INT);
        $width = $x2 - $x1;
        $height = $y2 - $y1;
        $info = lightboxgallery_image_info($this->imagepath);
        if ($width > 0 && $height > 0) {
            if ($image = lightboxgallery_imagecreatefromtype($info->imagesize[2], $this->imagepath)) {
                $cropped = lightboxgallery_image_create($width, $height);
                imagecopybicubic($cropped, $image, 0, 0, $x1, $y1, $width, $height, $width, $height);
                $this->save_image_resource($cropped, $info->imagesize[2]);
            }
        }
    }

}

?>

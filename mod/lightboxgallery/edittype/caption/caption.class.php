<?php

class edittype_caption extends edittype_base {

    function edittype_caption($gallery, $image, $tab) {
        parent::edittype_base($gallery, $image, $tab, false);      
    }

    function output() {
        if ($caption = $this->get_caption_object()) {
            $captiontext = $caption->description;
        } else {
            $captiontext = '';
        }
        $result = '<textarea name="caption" cols="24" rows="4">'.$captiontext.'</textarea><br /><br />'.
                  '<input type="submit" value="'.get_string('update').'" />&nbsp;<input type="submit" name="remove" value="'.get_string('remove').'" />';
        return $this->enclose_in_form($result);        
    }

    function process_form() {
        $caption = required_param('caption', PARAM_TEXT);
        $remove = optional_param('remove', '', PARAM_TEXT);
        if ($remove) {
            delete_records('lightboxgallery_image_meta', 'metatype', 'caption', 'gallery', $this->gallery->id, 'image', $this->image);
        } else {
            lightboxgallery_set_image_caption($this->gallery->id, $this->image, $caption);
        }
    }

    function get_caption_object() {
        return get_record('lightboxgallery_image_meta', 'metatype', 'caption', 'gallery', $this->gallery->id, 'image', $this->image);
    }

}

?>

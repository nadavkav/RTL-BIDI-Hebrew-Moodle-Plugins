<?php

class edittype_tag extends edittype_base {

    function edittype_tag($gallery, $image, $tab) {
        parent::edittype_base($gallery, $image, $tab, false);      
    }

    function output() {
        global $CFG, $USER;
        $input = '<input type="text" name="tag" /><input type="submit" value="' . get_string('add') . '" />';
        $tags = '';
        $select = "metatype = 'tag' AND gallery = {$this->gallery->id} AND image = '$this->image'";
        if ($records = get_records_select('lightboxgallery_image_meta', $select, 'description')) {
            $tags .= '<ul>';
            foreach ($records as $record) {
                $tags .= '<li>' . $record->description . ' <a href="' . $CFG->wwwroot . '/mod/lightboxgallery/imageedit.php?id=' . $this->gallery->id . '&amp;image=' . $this->image . '&amp;tab=tag&amp;delete=' . $record->id . '&amp;process=1&amp;sesskey=' . $USER->sesskey . '" title="Delete"><span style="color: red;">&#x2718;</span></a></li>';
            }
            $tags .= '</ul>';
        }
        return $this->enclose_in_form($input) . $tags;         
    }

    function process_form() {
        $tag = optional_param('tag', '', PARAM_TEXT);
        $delete = optional_param('delete', 0, PARAM_INT);
        if (!empty($tag)) {
            $record = new object;
            $record->gallery = $this->gallery->id;
            $record->image = $this->image;
            $record->metatype = 'tag';
            $record->description = strtolower($tag);
            insert_record('lightboxgallery_image_meta', $record);
        } else if ($delete) {
            $select = "metatype = 'tag' AND id = $delete AND gallery = {$this->gallery->id} AND image = '$this->image'";
            if ($id = get_field_select('lightboxgallery_image_meta', 'id', $select)) {
                delete_records('lightboxgallery_image_meta', 'id', $id);
            }
        }
    }

}

?>

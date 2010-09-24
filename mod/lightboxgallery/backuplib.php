<?php

    include($CFG->dirroot . '/mod/lightboxgallery/lib.php');

    function lightboxgallery_backup_mods($bf, $preferences) {
        $status = true;

        if ($galleries = get_records('lightboxgallery', 'course', $preferences->backup_course, 'id')) {
            foreach ($galleries as $gallery) {
                if (backup_mod_selected($preferences, 'lightboxgallery', $gallery->id)) {
                    $status = lightboxgallery_backup_one_mod($bf, $preferences, $gallery);
                }
            }
        }

        return $status;
    }

    function lightboxgallery_backup_one_mod($bf, $preferences, $gallery) {
        $status = true;

        if (is_numeric($gallery)) {
            $gallery = get_record('lightboxgallery', 'id', $gallery);
        }

        fwrite($bf, start_tag('MOD', 3, true));

        fwrite($bf, full_tag('ID', 4, false, $gallery->id));
        fwrite($bf, full_tag('MODTYPE', 4, false, 'lightboxgallery'));
        fwrite($bf, full_tag('FOLDER', 4, false, $gallery->folder));
        fwrite($bf, full_tag('NAME', 4, false, $gallery->name));
        fwrite($bf, full_tag('DESCRIPTION', 4, false, $gallery->description));
        fwrite($bf, full_tag('PERPAGE', 4, false, $gallery->perpage));
        fwrite($bf, full_tag('COMMENTS', 4, false, $gallery->comments));
        fwrite($bf, full_tag('PUBLIC', 4, false, $gallery->public));
        fwrite($bf, full_tag('RSS', 4, false, $gallery->rss));
        fwrite($bf, full_tag('AUTORESIZE', 4, false, $gallery->autoresize));
        fwrite($bf, full_tag('RESIZE', 4, false, $gallery->resize));
        fwrite($bf, full_tag('EXTINFO', 4, false, $gallery->extinfo));

        $status = backup_lightboxgallery_files_instance($bf, $preferences, $gallery);

        if ($status) {
            if (backup_userdata_selected($preferences, 'lightboxgallery', $gallery->id)) {
                $status = backup_lightboxgallery_metadata($bf, $preferences, $gallery->id);
            }
        }

        $status = fwrite($bf, end_tag('MOD', 3, true));

        return $status;
    }

    function backup_lightboxgallery_metadata($bf, $preferences, $galleryid) {
        $status = true;

        if ($records = get_records('lightboxgallery_image_meta', 'gallery', $galleryid, 'id')) {
            $status = fwrite($bf, start_tag('IMAGEMETAS', 4, true));
            foreach ($records as $record) {
                fwrite($bf, start_tag('IMAGEMETA', 5, true));

                fwrite($bf, full_tag('ID', 6, false, $record->id));
                fwrite($bf, full_tag('IMAGE', 6, false, $record->image));
                fwrite($bf, full_tag('METATYPE', 6, false, $record->metatype));
                fwrite($bf, full_tag('DESCRIPTION', 6, false, $record->description));

                $status = fwrite($bf, end_tag('IMAGEMETA', 5,true));
            }
            $status = fwrite($bf, end_tag('IMAGEMETAS', 4, true));
        }

        return $status;
    }

    function backup_lightboxgallery_files_instance($bf, $preferences, $gallery) {
        global $CFG;

        $status = true;

        if (is_numeric($gallery)) {
            $gallery = get_record('lightboxgallery', 'id', $gallery);
        }

        $tmppath = $CFG->dataroot . '/temp/backup/' . $preferences->backup_unique_code . '/' . $gallery->folder;

        $status = check_dir_exists($tmppath, true, true);

        if ($status) {
            $oldpath = $CFG->dataroot . '/' . $preferences->backup_course . '/' . $gallery->folder;
            if (is_dir($oldpath)) {
                $status = backup_copy_file($oldpath, $tmppath);
            }
        }

        return $status;   
    }

    /***************************************************************************************************************************************************/

    function lightboxgallery_check_backup_mods($course, $user_data = false, $backup_unique_code, $instances = null) {
       if (!empty($instances) && is_array($instances) && count($instances)) {
           $info = array();
           foreach ($instances as $id => $instance) {
               $info += lightboxgallery_check_backup_mods_instances($instance, $backup_unique_code);
           }
           return $info;
       }

        $info[0][0] = get_string('modulenameplural', 'lightboxgallery');
        if ($ids = lightboxgallery_ids($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        if ($user_data) {
            $info[2][0] = get_string('metadata', 'lightboxgallery');
            if ($ids = lightboxgallery_meta_ids_by_course($course)) {
                $info[2][1] = count($ids);
            } else {
                $info[2][1] = 0;
            }
        }

        return $info;
    }

   function lightboxgallery_check_backup_mods_instances($instance, $backup_unique_code) {
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';

        $info[$instance->id.'1'][0] = get_string('imagecount', 'lightboxgallery');
        if ($images = lightboxgallery_images_by_instance($instance->id)) {
            $info[$instance->id.'1'][1] = count($images);
        } else {
            $info[$instance->id.'1'][1] = 0;
        }

        if (!empty($instance->userdata)) {
            $info[$instance->id.'2'][0] = get_string('metadata', 'lightboxgallery');
            if ($ids = lightboxgallery_meta_ids_by_instance($instance->id)) {
                $info[$instance->id.'2'][1] = count($ids);
            } else {
                $info[$instance->id.'2'][1] = 0;
            }
        }

        return $info;
    }

    /***************************************************************************************************************************************************/    

    function lightboxgallery_encode_content_links($content, $preferences) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = "/(".$base."\/mod\/lightboxgallery\/index.php\?id\=)([0-9]+)/";
        $result = preg_replace($search, '$@GALLERYINDEX*$2@$', $content);

        $search = "/(".$base."\/mod\/lightboxgallery\/view.php\?id\=)([0-9]+)/";
        $result = preg_replace($search, '$@GALLERYVIEWBYID*$2@$', $result);

        $search = "/(".$base."\/mod\/lightboxgallery\/view.php\?l\=)([0-9]+)/";
        $result = preg_replace($search, '$@GALLERYVIEWBYL*$2@$', $result);

        return $result;
    }

    function lightboxgallery_ids($course) {
        global $CFG;
        return get_records_sql("SELECT l.id, l.course
                                FROM {$CFG->prefix}lightboxgallery l
                                WHERE l.course = '$course'");
    }
   
    function lightboxgallery_meta_ids_by_course($course) {
        global $CFG;
        return get_records_sql("SELECT m.id, m.gallery
                                FROM {$CFG->prefix}lightboxgallery_image_meta m,
                                     {$CFG->prefix}lightboxgallery l
                                WHERE l.course = '$course' AND
                                      m.gallery = l.id");
    }

    function lightboxgallery_meta_ids_by_instance($instanceid) {
        global $CFG;
        return get_records_sql("SELECT m.id, m.gallery
                                FROM {$CFG->prefix}lightboxgallery_image_meta m
                                WHERE m.gallery = $instanceid");
    }

    function lightboxgallery_images_by_instance($instanceid) {
        global $CFG;

        $result = false;

        if ($gallery = get_record('lightboxgallery', 'id', $instanceid)) {
            $directory = $CFG->dataroot . '/' . $gallery->course . '/' . $gallery->folder;
            $result = lightboxgallery_directory_images($directory);
        }

        return $result;
    }


?>

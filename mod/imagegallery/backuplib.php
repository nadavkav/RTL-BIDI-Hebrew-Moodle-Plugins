<?php //$Id: backuplib.php,v 1.2 2006/10/19 11:16:30 janne Exp $

    /** This php script contains all the stuff to backup/restore
     *  label mods
     *
     * This is the "graphical" structure of the label mod:
     *
     *                      imagegallery
     *                     (CL,pk->id)
     *                          |
     *                          |
     *                  imagegallery_categories
     *            (UL,pk->id, fk->galleryid, fk->userid)
     *                          |
     *                          |
     *                  imagegallery_images
     *    (UL,pk->id, fk->galleryid, fk->categoryid,fk->userid)
     *
     * Meaning: pk->primary key field of the table
     *          fk->foreign key to link with parent
     *          nt->nested field (recursive data)
     *          CL->course level info
     *          UL->user level info
     *          files->table may have files)
     *
     * -----------------------------------------------------------
     */

    //This function executes all the backup procedure about this mod
    function imagegallery_backup_mods($bf,$preferences) {
        global $CFG;

        $status = true;

        ////Iterate over imagegallery table
        if ($galleries = get_records ("imagegallery","course", $preferences->backup_course,"id")) {
            foreach ($galleries as $imagegallery) {
                if (backup_mod_selected($preferences,'imagegallery',$imagegallery->id)) {
                    $status = imagegallery_backup_one_mod($bf,$preferences,$imagegallery);
                }
            }
        }
        return $status;
    }

    function imagegallery_backup_one_mod($bf,$preferences,$imagegallery) {
        global $CFG;

        if (is_numeric($imagegallery)) {
            $imagegallery = get_record('imagegallery','id',$imagegallery);
        }

        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print imagegallery data
        fwrite ($bf,full_tag("ID",4,false,$imagegallery->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"imagegallery"));
        fwrite ($bf,full_tag("NAME",4,false,$imagegallery->name));
        fwrite ($bf,full_tag("INTRO",4,false,$imagegallery->intro));
        fwrite ($bf,full_tag("MAXBYTES",4,false,$imagegallery->maxbytes));
        fwrite ($bf,full_tag("MAXWIDTH",4,false,$imagegallery->maxwidth));
        fwrite ($bf,full_tag("MAXHEIGHT",4,false,$imagegallery->maxheight));
        fwrite ($bf,full_tag("ALLOWSTUDENTUPLOAD",4,false,$imagegallery->allowstudentupload));
        fwrite ($bf,full_tag("IMAGESPERPAGE",4,false,$imagegallery->imagesperpage));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$imagegallery->timemodified));
        fwrite ($bf,full_tag("REQUIRELOGIN",4,false,$imagegallery->requirelogin));
        fwrite ($bf,full_tag("RESIZE",4,false,$imagegallery->resize));
        fwrite ($bf,full_tag("DEFAULTCATEGORY",4,false,$imagegallery->defaultcategory));
        fwrite ($bf,full_tag("SHADOW",4,false,$imagegallery->shadow));

         //if we've selected to backup users info, then execute backup_imagegallery_categories
        if (backup_userdata_selected($preferences,'imagegallery',$imagegallery->id)) {
            $status = backup_imagegallery_categories($bf,$preferences,$imagegallery->id);
        }

        if (backup_userdata_selected($preferences, 'imagegallery', $imagegallery->id)) {
            $status = backup_imagegallery_images($bf,$preferences,$imagegallery->id);
        }

        //End mod
        $status = fwrite($bf,end_tag("MOD",3,true));

        return $status;

    }

    //Backup imagegallery_categories (executed from imagegallery_backup_mods)
    function backup_imagegallery_categories ($bf,$preferences,$imagegallery) {

        global $CFG;

        $status = true;

        $categories = get_records("imagegallery_categories", "galleryid",$imagegallery,"id");
        //If there is categories.
        if ($categories) {
            //Write start tag
            $status = fwrite($bf,start_tag("CATEGORIES",4,true));
            //Iterate over each category
            foreach ($categories as $category) {
                //Start category
                $status = fwrite($bf,start_tag("CATEGORY",5,true));
                //Print answer contents
                fwrite ($bf,full_tag("ID",6,false,$category->id));
                fwrite ($bf,full_tag("GALLERYID",6,false,$category->galleryid));
                fwrite ($bf,full_tag("USERID",6,false,$category->userid));
                fwrite ($bf,full_tag("NAME",6,false,$category->name));
                fwrite ($bf,full_tag("DESCRIPTION",6,false,$category->description));
                fwrite ($bf,full_tag("TIMECREATED",6,false,$category->timecreated));
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$category->timemodified));
                //End answer
                $status = fwrite($bf,end_tag("CATEGORY",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("CATEGORIES",4,true));
        }
        return $status;
    }

    //Backup imagegallery_images (executed from imagegallery_backup_mods)
    function backup_imagegallery_images ($bf,$preferences,$imagegallery) {

        global $CFG;

        $status = true;

        $images = get_records("imagegallery_images", "galleryid",$imagegallery,"id");
        //If there is images.
        if ($images) {
            //Write start tag
            $status = fwrite($bf,start_tag("IMAGES",4,true));
            //Iterate over each image
            foreach ($images as $image) {
                //Start image
                $status = fwrite($bf,start_tag("IMAGE",5,true));
                //Print answer contents
                fwrite ($bf,full_tag("ID",6,false,$image->id));
                fwrite ($bf,full_tag("GALLERYID",6,false,$image->galleryid));
                fwrite ($bf,full_tag("CATEGORYID",6,false,$image->categoryid));
                fwrite ($bf,full_tag("USERID",6,false,$image->userid));
                fwrite ($bf,full_tag("NAME",6,false,$image->name));
                fwrite ($bf,full_tag("SIZE",6,false,$image->size));
                fwrite ($bf,full_tag("MIME",6,false,$image->mime));
                fwrite ($bf,full_tag("WIDTH",6,false,$image->width));
                fwrite ($bf,full_tag("HEIGHT",6,false,$image->height));
                fwrite ($bf,full_tag("PATH",6,false,$image->path));
                fwrite ($bf,full_tag("DESCRIPTION",6,false,$image->description));
                fwrite ($bf,full_tag("TIMECREATED",6,false,$image->timecreated));
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$image->timemodified));
                //End answer
                $status = fwrite($bf,end_tag("IMAGE",5,true));
                // Copy image.
                $status = backup_imagegallery_files($bf,$preferences, $image);
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("IMAGES",4,true));
        }
        return $status;
    }

    ////Return an array of info (name,value)
   function imagegallery_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {

        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += imagegallery_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        //First the course data
        $info[0][0] = get_string("modulenameplural","imagegallery");
        if ($ids = imagegallery_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string("categories","imagegallery");
            if ($ids = imagegallery_category_ids_by_course ($course)) {
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
            $info[2][0] = get_string("images","imagegallery");
            if ( $ids = imagegallery_image_ids_by_course($course) ) {
                $info[2][1] = count($ids);
            } else {
               $info[2][1] = 0;
           }
        }
        return $info;
    }

   ////Return an array of info (name,value)
   function imagegallery_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';

        //Now, if requested, the user_data
        if (!empty($instance->userdata)) {
            $info[$instance->id.'1'][0] = get_string("categories","imagegallery");
            if ($ids = imagegallery_category_ids_by_instance ($instance->id)) {
                $info[$instance->id.'1'][1] = count($ids);
            } else {
                $info[$instance->id.'1'][1] = 0;
            }
            $info[$instance->id.'2'][0] = get_string("images","imagegallery");
            if ( $ids = imagegallery_image_ids_by_instance($instance->id) ) {
                $info[$instance->id.'2'][1] = count($ids);
            } else {
                $info[$instance->id.'2'][1] = 0;
            }
        }
        return $info;
    }


    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function imagegallery_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of imagegalleries
        $buscar="/(".$base."\/mod\/imagegallery\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@IMAGEGALLERYINDEX*$2@$',$content);

        //Link to imagegallery view by moduleid
        $buscar="/(".$base."\/mod\/imagegallery\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@IMAGEGALLERYVIEWBYID*$2@$',$result);

        return $result;

    }

    // This function is called from backup_imagegallery_images.
    // Copy image and thumbnail. Not very sophisticated but works.
    function backup_imagegallery_files($bf,$preferences,$image) {
        global $CFG;
        $status = true;
        //First we check to moddata exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = check_and_create_moddata_dir($preferences->backup_unique_code);
        //Now we check that moddata/glossary dir exists and create it as necessary
        //in temp/backup/$backup_code/moddata dir
        $gal_dir_to = $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code.
                      "/".$CFG->moddata."/imagegallery";
        //Let's create it as necessary
        $status = check_dir_exists($gal_dir_to,true);

        // Check imagegalleryid directory.
        $gal_dir_to .= '/'. $image->galleryid;
        $status = check_dir_exists($gal_dir_to, true);

        // structure for imagegallery data directory starting from moddata:
        // moddata
        //    |
        //    |- imagegallery
        //            |
        //            |- <imagegalleryid>
        //                      |
        //                      |- non-categorized images
        //                      |- <categoryid>
        //                              |
        //                              |- categorized images.

        if ( !empty($image->categoryid) ) {
            // Copy categorized image under it's directory ( create directory if necessary ).
            $gal_dir_to .= '/'. $image->categoryid;
            $status = check_dir_exists($gal_dir_to, true);
            $filesource = $CFG->dataroot . $image->path;
            $filedestin = $gal_dir_to .'/'. $image->name;
            if ( is_dir($gal_dir_to) && file_exists($filesource) ) {
                $status = backup_copy_file($filesource, $filedestin);
            }
            // Copy thumbnail
            $filesource = $CFG->dataroot . str_replace($image->name, "thumb_". $image->name, $image->path);
            $filedestin = $gal_dir_to .'/thumb_'. $image->name;
            if ( is_dir($gal_dir_to) && file_exists($filesource) ) {
                $status = backup_copy_file($filesource, $filedestin);
            }

        } else {
            // Copy uncategorized image under imagegallery id.
            $filesource = $CFG->dataroot . $image->path;
            $filedestin = $gal_dir_to .'/'. $image->name;
            if ( is_dir($gal_dir_to) && file_exists($filesource) ) {
                $status = backup_copy_file($filesource, $filedestin);
            }
            // Copy thumbnail
            $filesource = $CFG->dataroot . str_replace($image->name, "thumb_". $image->name, $image->path);
            $filedestin = $gal_dir_to .'/thumb_'. $image->name;
            if ( is_dir($gal_dir_to) && file_exists($filesource) ) {
                $status = backup_copy_file($filesource, $filedestin);
            }
        }

        return $status;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of imagegallery id
    function imagegallery_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}imagegallery a
                                 WHERE a.course = '$course'");
    }

    function imagegallery_category_ids_by_course ($course) {
        global $CFG;
        return get_records_sql("SELECT c.id , c.galleryid
                                FROM {$CFG->prefix}imagegallery_categories c,
                                     {$CFG->prefix}imagegallery i
                                WHERE i.course = '2' AND
                                c.galleryid = i.id");
    }

    function imagegallery_image_ids_by_course ($course) {
        global $CFG;
        return get_records_sql("SELECT c.id , c.galleryid
                                FROM {$CFG->prefix}imagegallery_images c,
                                     {$CFG->prefix}imagegallery i
                                WHERE i.course = '2' AND
                                c.galleryid = i.id");
    }

    function imagegallery_category_ids_by_instance ($instanceid) {
        global $CFG;
        return get_records_sql("SELECT id, galleryid
                                FROM {$CFG->prefix}imagegallery_categories
                                WHERE galleryid = '$instanceid'");
    }

    function imagegallery_image_ids_by_instance ($instanceid) {
        global $CFG;
        return get_records_sql("SELECT id, galleryid
                                FROM {$CFG->prefix}imagegallery_images
                                WHERE galleryid = '$instanceid'");
    }
?>
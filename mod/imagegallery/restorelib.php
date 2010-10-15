<?php // $Id: restorelib.php,v 1.1 2006/10/19 11:15:42 janne Exp $

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

     function imagegallery_restore_mods($mod,$restore) {

        global $CFG,$db;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //Now, build the learningdiary record structure
            $gallery = new stdClass;
            $gallery->course = $restore->course_id;
            $gallery->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $gallery->intro = backup_todb($info['MOD']['#']['INTRO']['0']['#']);
            $gallery->maxbytes = backup_todb($info['MOD']['#']['MAXBYTES']['0']['#']);
            $gallery->maxwidth = backup_todb($info['MOD']['#']['MAXWIDTH']['0']['#']);
            $gallery->maxheight = backup_todb($info['MOD']['#']['MAXHEIGHT']['0']['#']);
            $gallery->allowstudentupload = backup_todb($info['MOD']['#']['ALLOWSTUDENTUPLOAD']['0']['#']);
            $gallery->imagesperpage = backup_todb($info['MOD']['#']['IMAGESPERPAGE']['0']['#']);
            $gallery->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);
            $gallery->requirelogin = backup_todb($info['MOD']['#']['REQUIRELOGIN']['0']['#']);
            $gallery->resize = backup_todb($info['MOD']['#']['RESIZE']['0']['#']);
            $gallery->defaultcategory = backup_todb($info['MOD']['#']['DEFAULTCATEGORY']['0']['#']);
            $gallery->shadow = backup_todb($info['MOD']['#']['SHADOW']['0']['#']);

            // Add to database ( creates a copy if there is a existing one ).
            $newid = insert_record("imagegallery", $gallery);

            //Do some output
            echo "<li>".get_string("modulename","imagegallery")." \"".format_string(stripslashes($gallery->name),true)."\"";
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
                //Now check if want to restore user data and do it.
                if ($restore->mods['imagegallery']->userinfo) {
                    //Restore imagegallery_categories
                    $status = imagegallery_categories_restore_mods($newid, $info, $restore, $gallery);
                    $status = imagegallery_images_restore_mods($newid, $info, $restore, $gallery);
                }
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

    function imagegallery_categories_restore_mods ($newgalleryid, $info, $restore, $gallery) {
        global $CFG;

        $status = true;

        //Get the discussions array
        $categories = $info['MOD']['#']['CATEGORIES']['0']['#']['CATEGORY'];

        for ( $i = 0; $i < sizeof($categories); $i++ ) {
            $cat_info = $categories[$i];

            $oldid = backup_todb($cat_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($cat_info['#']['USERID']['0']['#']);
            $category = new stdClass;
            $category->galleryid = $newgalleryid;
            $category->userid = backup_todb($cat_info['#']['USERID']['0']['#']);
            $category->name = backup_todb($cat_info['#']['NAME']['0']['#']);
            $category->description = backup_todb($cat_info['#']['DESCRIPTION']['0']['#']);
            $category->timecreated = backup_todb($cat_info['#']['TIMECREATED']['0']['#']);
            $category->timemodified = backup_todb($cat_info['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$category->userid);
            if ($user) {
                $category->userid = $user->new_id;
            }

            //The structure is equal to the db, so insert the forum_subscription
            $newid = insert_record ("imagegallery_categories", $category);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"imagegallery_categories",$oldid,
                             $newid);
            } else {
                $status = false;
            }
        }
        return $status;
    }

    function imagegallery_images_restore_mods($newgalleryid, $info, $restore, $gallery) {
        global $CFG;
        $status = true;

        $images = $info['MOD']['#']['IMAGES']['0']['#']['IMAGE'];
        for ( $i = 0; $i < sizeof($images); $i++ ) {
            $img_info = $images[$i];
            $oldid = backup_todb($img_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($img_info['#']['USERID']['0']['#']);
            $oldgalleryid = backup_todb($img_info['#']['GALLERYID']['0']['#']);
            $oldcategoryid = backup_todb($img_info['#']['CATEGORYID']['0']['#']);
            $image = new stdClass;
            $image->galleryid = $newgalleryid;
            $image->categoryid = backup_todb($img_info['#']['CATEGORYID']['0']['#']);
            $image->userid = backup_todb($img_info['#']['USERID']['0']['#']);
            $image->name = backup_todb($img_info['#']['NAME']['0']['#']);
            $image->size = backup_todb($img_info['#']['SIZE']['0']['#']);
            $image->mime = backup_todb($img_info['#']['MIME']['0']['#']);
            $image->width = backup_todb($img_info['#']['WIDTH']['0']['#']);
            $image->height = backup_todb($img_info['#']['HEIGHT']['0']['#']);
            $image->path = backup_todb($img_info['#']['PATH']['0']['#']);
            $image->description = backup_todb($img_info['#']['DESCRIPTION']['0']['#']);
            $image->timecreated = backup_todb($img_info['#']['TIMECREATED']['0']['#']);
            $image->timemodified = backup_todb($img_info['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$image->userid);
            if ($user) {
                $image->userid = $user->new_id;
            }

            // We have to recode the categoryid field
            $category = backup_getid($restore->backup_unique_code,
                                     "imagegallery_categories",
                                     $image->categoryid);
            if ( $category ) {
                $image->categoryid = $category->new_id;
            }

            // Recode path
            $newbasedir = '/'. $restore->course_id .'/'. $CFG->moddata .
                           '/imagegallery/'. $newgalleryid;
            $image->path  = $newbasedir;
            $image->path .= (!empty($image->categoryid)) ?
                            '/'. $image->categoryid : '';
            $image->path .= '/'. $image->name;

            //The structure is equal to the db, so insert the forum_subscription
            $newid = insert_record ("imagegallery_images", $image);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"imagegallery_images",$oldid,
                             $newid);
                $status = imagegallery_images_restore_file($newgalleryid,
                                                           $oldgalleryid,
                                                           $oldcategoryid,
                                                           $olduserid,
                                                           $restore,
                                                           $image);
            } else {
                $status = false;
            }

        }
        return $status;
    }

    function imagegallery_images_restore_file($newgalleryid, $oldgalid, $oldcatid, $olduserid, $restore, $image) {
        global $CFG;
        $status = true;

        // Check that course's folder exists
        $dest_dir = $CFG->dataroot . '/'. $restore->course_id;
        $status = check_dir_exists($dest_dir,true);
        // Check that moddata dir exists
        $dest_dir .= '/'. $CFG->moddata;
        $status = check_dir_exists($dest_dir, true);
        // Check imagegallery directory.
        $dest_dir .= '/imagegallery';
        $status = check_dir_exists($dest_dir, true);
        // Check that imagegallery instance directory exists
        $dest_dir .= '/'. $newgalleryid;
        $status = check_dir_exists($dest_dir, true);

        echo $dest_dir . "<br />\n";
        if ( !empty($oldcatid) ) {
            // Copy categorized image.
            $dest_dir .= '/'. $image->categoryid;
            $status = check_dir_exists($dest_dir, true);
            $filesource = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code.
                         "/moddata/imagegallery/$oldgalid/$oldcatid/". $image->name;
            $filedestin = $CFG->dataroot . $image->path;
            echo $filesource ."<br />\n";
            echo $filedestin ."<br />\n";
            $status = backup_copy_file($filesource, $filedestin);
            $thumbsource = str_replace($image->name, "thumb_". $image->name, $filesource);
            $thumbdestin = $CFG->dataroot . str_replace($image->name, "thumb_" . $image->name, $image->path);
            $status = backup_copy_file($thumbsource, $thumbdestin);

        } else {
            // Copy uncategorized image.
            $filesource = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code.
                         "/moddata/imagegallery/$oldgalid/". $image->name;
            $filedestin = $CFG->dataroot . $image->path;
            echo $filesource ."<br />\n";
            echo $filedestin ."<br />\n";
            $status = backup_copy_file($filesource, $filedestin);
            $thumbsource = str_replace($image->name, "thumb_". $image->name, $filesource);
            $thumbdestin = $CFG->dataroot . str_replace($image->name, "thumb_" . $image->name, $image->path);
            $status = backup_copy_file($thumbsource, $thumbdestin);
        }
        return $status;
    }

?>
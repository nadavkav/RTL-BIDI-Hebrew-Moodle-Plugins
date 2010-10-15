<?php  // $Id: categories.php,v 1.2.2.1 2006/11/30 07:32:15 janne Exp $

/// This page prints a particular instance of learningdiary

    require_once("../../config.php");
    require_once("lib.php");

    $sesskey = required_param('sesskey', PARAM_ALPHANUM);

    // Get actions
    $addnew = optional_param('add', '', PARAM_RAW);
    $edit   = optional_param('edit', '', PARAM_RAW);
    $delete = optional_param('delete', '', PARAM_RAW);

    if ( !empty($addnew) ) {
        $action = 'add';
    } else if ( !empty($edit) ) {
        $action = 'edit';
    } else if ( !empty($delete) ) {
        $action = 'delete';
    } else {
        $action = optional_param('action', '', PARAM_ALPHA);
    }

    if ( !confirm_sesskey($sesskey) ) {
        error("Session key error!!!");
    }

    $gallery = new modImagegallery(); // Instantiate imagegallery object.

    if ( !$gallery->isteacher ) {
        error("You're not allowed to use this page!!!",
              "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
    }

    // Check directory
    if ( !$gallery->file_area() ) {
        error("Could not create necessary directory!",
              "$CFG->wwwroot/course/view.php?id={$gallery->course->id}");
    }

    if ( $data = data_submitted() ) {
        if ( empty($data->cancel) ) {
            $form = new stdClass;
            $form->galleryid = clean_param($data->galleryid, PARAM_INT);
            $form->userid    = clean_param($USER->id, PARAM_INT);
            switch ( $data->action ) {
                case 'add':
                    $form->timecreated  = time();
                    $form->timemodified = time();
                    $form->name = addslashes(trim(strip_tags($data->name)));
                    $form->description = addslashes(trim(strip_tags($data->description)));
                    if ( !empty($form->name) ) {
                        // Insert only if it doesn't exists.
                        if ( !record_exists("imagegallery_categories",
                                            "galleryid", $gallery->module->id,
                                            "name", $form->name) ) {

                            if ( !$newid = insert_record("imagegallery_categories", $form) ) {
                                error("Could not add new category ". s($form->name),
                                      "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
                            }
                            $gallery->file_area($newid);

                            if ( !empty($data->defaultcategory) ) {
                                set_field("imagegallery", "defaultcategory", $newid, "id", $gallery->module->id);
                            }
                            $action = NULL;
                        }
                    } else {
                        $form->errname = get_string('categorynamemissing','imagegallery');
                    }
                break;
                case 'edit':
                    $form->id = clean_param($data->categoryid, PARAM_INT);
                    $form->timemodified = time();
                    $form->name = addslashes(trim(strip_tags($data->name)));
                    $form->description = addslashes(trim(strip_tags($data->description)));

                    if ( !empty($form->name) ) {

                        if ( !update_record("imagegallery_categories", $form) ) {
                            error("Could not update category ". s($form->name),
                                  "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
                        }

                        if ( !empty($data->defaultcategory) ) {
                            set_field("imagegallery", "defaultcategory", $form->id, "id", $gallery->module->id);
                        } else {
                            set_field("imagegallery", "defaultcategory", "0", "id", $gallery->module->id);
                        }

                        $action = NULL;
                    } else {
                        $form->errname = get_string('categorynamemissing','imagegallery');
                    }
                break;
                case 'delete':
                    // Delete selected categories and images
                    // associated with them.
                    $notify = array();
                    $categories = explode(",", $data->catid);
                    foreach ( $categories as $key => $value ) {
                        $categories[$key] = clean_param($value, PARAM_INT);
                    }
                    $strcategories = implode(",", $categories);
                    if ( $cats = get_records_select("imagegallery_categories",
                                                    "id IN ($strcategories)") ) {
                        foreach ( $cats as $category ) {
                            // Check are this user able to remove this category.
                            if ( !$gallery->isadmin && !$USER->id != $category->userid ) {
                                $notify[] = get_string('useridmismatch','imagegallery', s($category->name));
                            }
                            if ( empty($notify) ) {
                                // Get images in this category.
                                $images = get_records("imagegallery_images", "categoryid", $category->id);
                                if ( !empty($images) ) {
                                    foreach ( $images as $image ) {
                                        if ( !unlink($CFG->dataroot . $image->path) ) {
                                            error("Could not remove image ". s(basename($image->path)),
                                                  "$CFG->wwwroot/mod/imagegallery/view.php?id={$gallery->cm->id}");
                                        }
                                        // Delete image
                                        delete_records("imagegallery_images", "id", $image->id);
                                    }
                                }
                                // Delete category.
                                delete_records("imagegallery_categories", "id", $category->id);
                            }
                            unset($images, $image);
                        } // End of category loop.
                    }
                    unset($categories, $strcategories, $cats, $category);
                    if ( empty($notify) ) {
                        redirect("view.php?id={$gallery->cm->id}");
                    } else {
                        $gallery->print_notify_page($notify);
                    }
                break;
            }
        } else { // Cancel button was pressed.
            $action = NULL; // Clear action.
        }
    }

    $strimagegalleries   = get_string("modulenameplural", "imagegallery");
    $strimagegallery     = get_string("modulename", "imagegallery");
    $strmanagecategories = get_string('managecategories','imagegallery');

    //add_to_log($gallery->course->id, "imagegallery", "view", "view.php?id={$gallery->cm->id}", "{$gallery->module->name}");

    $navigation = '';
    if ($gallery->course->category) {
        $navigation = "<a href=\"../../course/view.php?id={$gallery->course->id}\">{$gallery->course->shortname}</a> ->";
    }

    $navigation .= " <a href=\"index.php?id={$gallery->course->id}\">";
    $navigation .= "$strimagegalleries</a> -> <a href=\"view.php?id=";
    $navigation .= "{$gallery->cm->id}\">{$gallery->module->name}</a>";

    print_header("{$gallery->course->shortname}: {$gallery->module->name}", "{$gallery->course->fullname}",
                 "$navigation -> $strmanagecategories", "", "", true,
                 update_module_button($gallery->cm->id, $gallery->course->id, $strimagegallery),
                 navmenu($gallery->course, $gallery->cm));
    print_heading($strmanagecategories);
    print_simple_box_start("center");

    switch ( $action ) {
        case 'add': // Print form.
            if ( empty($form) ) {
                $form = new stdClass;
            }
            $form->id = $gallery->cm->id;
            $form->sesskey = $USER->sesskey;
            $form->galleryid = $gallery->module->id;
            $form->action = 'add';
            $gallery->print_category_form($form);
        break;
        case 'edit': // Fetch data, print form.
            $catid = required_param('catid', PARAM_INT);
            if ( $form = get_record("imagegallery_categories", "id", $catid) ) {
                $form->categoryid = $form->id;
                $form->id = $gallery->cm->id;
                $form->action = 'edit';
                $form->sesskey = $USER->sesskey;
                $gallery->print_category_form($form);
            }
        break;
        case 'delete': // Ask confirmation.

            $categories = array();
            if ( !empty($_GET['catid']) ) {
                foreach ( $_GET['catid'] as $key => $value ) {
                    $categories[$key] = clean_param($value, PARAM_INT);
                }
            }

            $strcatids = implode(",", $categories);

            if ( !empty($strcatids) ) {
                if ( $cats = get_records_select("imagegallery_categories",
                                                "id IN ($strcatids)",
                                                "",
                                                "id, name") ) {
                    $gallery->print_category_delete_confirm_form($cats);
                } else {
                    error("Category unavailable!!!");
                }
            } else {
            }

        break;
        default: // List categories.

            $strname = get_string('name');
            $strdescription = get_string('description');
            $strmodified = get_string('modified');
            $strcreatedby = get_string('createdby', 'imagegallery');

            echo '<form method="get" action="categories.php">'."\n";
            echo '<input type="hidden" name="id" value="'. $gallery->cm->id .'" />'."\n";
            echo '<input type="hidden" name="sesskey" value="'. $USER->sesskey .'" />'."\n";
            echo '<input type="hidden" name="galleryid" value="'. $gallery->module->id .'" />'."\n";

            $tbl = new stdClass;
            $tbl->head = array("", $strname, $strdescription, $strmodified, $strcreatedby);
            $tbl->width = "100%";
            $tbl->cellpadding = "5";
            $tbl->align = array("right", "left", "left", "center", "center");
            $tbl->wrap = array("", "nowrap", "", "nowrap", "nowrap");
            $tbl->data = array();

            if ( $categories = get_records("imagegallery_categories",
                                           "galleryid", $gallery->module->id) ) {
                foreach ( $categories as $category ) {
                    $row = array();
                    $row[] = '<input type="checkbox" name="catid[]" value="'. $category->id .'" />';
                    $row[] = ($category->id != $gallery->module->defaultcategory) ?
                             s($category->name) :
                             '<strong><em>'. s($category->name) .'</em></strong>';
                    $row[] = s($category->description);
                    $row[] = userdate($category->timemodified, "%x %X");
                    $row[] = $gallery->get_user_fullname($category->userid);
                    array_push($tbl->data, $row);
                }
            }
            print_table($tbl);

            echo '<table border="0" align="center">';
            echo '<tr><td>';
            echo '<input type="submit" name="add" value="'. get_string('add') .'" />'."\n";
            echo '</td><td>';
            echo '<input type="submit" name="edit" value="'. get_string('edit') .'" />'."\n";
            echo '</td><td>';
            echo '<input type="submit" name="delete" value="'. get_string('deleteselected') .'" />'."\n";
            echo '</td></tr>';
            echo '</table>';
            echo '</form>';

    }

    print_simple_box_end();
    print_footer($gallery->course);

?>
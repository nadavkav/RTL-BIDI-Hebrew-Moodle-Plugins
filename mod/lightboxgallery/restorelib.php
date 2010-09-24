<?php

    function lightboxgallery_restore_mods($mod, $restore) {
        $status = true;

        if ($data = backup_getid($restore->backup_unique_code, $mod->modtype, $mod->id)) {
            $info = $data->info;

            $gallery->course = $restore->course_id;
            $gallery->folder = backup_todb($info['MOD']['#']['FOLDER']['0']['#']);
            $gallery->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $gallery->description = backup_todb($info['MOD']['#']['DESCRIPTION']['0']['#']);
            $gallery->perpage = backup_todb($info['MOD']['#']['PERPAGE']['0']['#']);
            $gallery->comments = backup_todb($info['MOD']['#']['COMMENTS']['0']['#']);

            $gallery->public = backup_todb($info['MOD']['#']['PUBLIC']['0']['#']);
            $gallery->rss = backup_todb($info['MOD']['#']['RSS']['0']['#']);
            $gallery->autoresize = backup_todb($info['MOD']['#']['AUTORESIZE']['0']['#']);
            $gallery->resize = backup_todb($info['MOD']['#']['RESIZE']['0']['#']);

            $gallery->extinfo = backup_todb($info['MOD']['#']['EXTINFO']['0']['#']);
            $gallery->timemodified = time();

            $newid = insert_record('lightboxgallery', $gallery);

            if (! defined('RESTORE_SILENTLY')) {
                echo('<li>' . get_string('modulename', 'lightboxgallery') . ' "' . format_string(stripslashes($gallery->name), true) . '"</li>');
            }

            backup_flush(300);

            if ($newid) {
                backup_putid($restore->backup_unique_code, $mod->modtype, $mod->id, $newid);
                $status = lightboxgallery_restore_files($newid, $restore);
                if ($status) {
                    $status = lightboxgallery_restore_metadata($newid, $info, $restore);
                }
            } else {
                $status = false;
            }

        } else {
            $status = false;
        }

        return $status;
    }

    function lightboxgallery_restore_metadata($galleryid, $info, $restore) {
        $status = true;

        if (isset($info['MOD']['#']['IMAGEMETAS']['0']['#']['IMAGEMETA'])) {
            $imagemetas = $info['MOD']['#']['IMAGEMETAS']['0']['#']['IMAGEMETA'];
        } else {
            $imagemetas = array();
        }

        for ($i = 0; $i < sizeof($imagemetas); $i++) {
            $sub_info = $imagemetas[$i];

            $oldid = backup_todb($sub_info['#']['ID']['0']['#']);

            $record = new object;
            $record->gallery = $galleryid;
            $record->image = backup_todb($sub_info['#']['IMAGE']['0']['#']);
            $record->metatype = backup_todb($sub_info['#']['METATYPE']['0']['#']);
            $record->description = backup_todb($sub_info['#']['DESCRIPTION']['0']['#']);

            $newid = insert_record('lightboxgallery_image_meta', $record);

            if (($i + 1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo('.');
                    if (($i + 1) % 1000 == 0) {
                        echo('<br />');
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                backup_putid($restore->backup_unique_code, 'lightboxgallery_image_meta', $oldid, $newid);
            } else {
                $status = false;
            }
        }

        return $status;
    }

    function lightboxgallery_restore_files($gallery, $restore) {
        global $CFG;

        $status = true;

        if (is_numeric($gallery)) {
            $gallery = get_record('lightboxgallery', 'id', $gallery);
        }

        $newpath = $CFG->dataroot . '/' . $gallery->course . '/' . $gallery->folder;

        $status = check_dir_exists($newpath, true, true);

        if ($status) {
            $tmppath = $CFG->dataroot . '/temp/backup/' . $restore->backup_unique_code . '/' . $gallery->folder;
            if (is_dir($tmppath)) {
                $status = backup_copy_file($tmppath, $newpath);
            }
        }

        return $status;
    }

    function lightboxgallery_decode_content_links($content, $restore) {
        global $CFG;
            
        $result = $content;
                
        $searchstring = '/\$@(GALLERYINDEX)\*([0-9]+)@\$/';
        preg_match_all($searchstring, $content, $foundset);
        if ($foundset[0]) {
            foreach ($foundset[2] as $old_id) {
                $rec = backup_getid($restore->backup_unique_code, 'course', $old_id);
                $searchstring = '/\$@(GALLERYINDEX)\*('.$old_id.')@\$/';
                if (!empty($rec->new_id)) {
                    $result = preg_replace($searchstring, $CFG->wwwroot.'/mod/lightboxgallery/index.php?id='.$rec->new_id, $result);
                } else { 
                    $result = preg_replace($searchstring, $restore->original_wwwroot.'/mod/lightboxgallery/index.php?id='.$old_id, $result);
                }
            }
        }

        $searchstring = '/\$@(GALLERYVIEWBYID)\*([0-9]+)@\$/';
        preg_match_all($searchstring, $result, $foundset);
        if ($foundset[0]) {
            foreach($foundset[2] as $old_id) {
                $rec = backup_getid($restore->backup_unique_code, 'course_modules', $old_id);
                $searchstring = '/\$@(GALLERYVIEWBYID)\*('.$old_id.')@\$/';
                if (!empty($rec->new_id)) {
                    $result = preg_replace($searchstring, $CFG->wwwroot.'/mod/lightboxgallery/view.php?id='.$rec->new_id, $result);
                } else {
                    $result = preg_replace($searchstring, $restore->original_wwwroot.'/mod/lightboxgallery/view.php?id='.$old_id, $result);
                }
            }
        }

        $searchstring = '/\$@(GALLERYVIEWBYL)\*([0-9]+)@\$/';
        preg_match_all($searchstring, $result, $foundset);
        if ($foundset[0]) {
            foreach($foundset[2] as $old_id) {
                $rec = backup_getid($restore->backup_unique_code, 'lightboxgallery', $old_id);
                $searchstring = '/\$@(GALLERYVIEWBYL)\*('.$old_id.')@\$/';
                if (!empty($rec->new_id)) {
                    $result = preg_replace($searchstring, $CFG->wwwroot.'/mod/lightboxgallery/view.php?l='.$rec->new_id, $result);
                } else {
                    $result = preg_replace($searchstring, $restore->original_wwwroot.'/mod/lightboxgallery/view.php?l='.$old_id, $result);
                }
            }
        }

        return $result;
    }

    function lightboxgallery_decode_content_links_caller($restore) {
        global $CFG;

        $status = true;

        if ($galleries = get_records_sql("SELECT l.id, l.description FROM {$CFG->prefix}lightboxgallery l WHERE l.course = $restore->course_id")) {
            $i = 0;
            foreach ($galleries as $gallery) {
                $i++;

                $content = $gallery->description;
                $result = restore_decode_content_links_worker($content, $restore);

                if ($content != $result) {
                    $gallery->description = addslashes($result);
                    $status = update_record('lightboxgallery', $gallery);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                }

                if (($i + 1) % 50 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo('.');
                        if (($i + 1) % 1000 == 0) {
                            echo('<br />');
                        }
                    }
                    backup_flush(300);
                }
            }
        }

        return $status;
    }

    function lightboxgallery_restore_logs($restore, $log) {
        $status = false;

        switch ($log->action) {
            case 'view':
            case 'comment':
            case 'addimage':
            case 'editimage':
                if ($log->cmid) {
                    if ($mod = backup_getid($restore->backup_unique_code, $log->module, $log->info)) {
                        $log->url = 'view.php?id=' . $log->cmid;
                        $log->info = $mod->new_id;
                        $status = true;
                    }
                }
                break;
            case 'search':
                if ($log->cmid) {
                    if ($mod = backup_getid($restore->backup_unique_code, $log->module, $log->info)) {
                        $log->url = 'search.php?id=' . $log->course . '&l=' . $log->cmid;
                        $log->info = $mod->new_id;
                        $status = true;
                    }
                }
                break;
            case 'view all':
                $log->url = 'index.php?id=' . $log->course;
                $status = true;
                break;
            default:
                if (!defined('RESTORE_SILENTLY')) {
                    echo('action ('.$log->module.'-'.$log->action.') unknown. Not restored<br />');
                }
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }

?>

<?php
/**
 * Backup Routine
 *
 * @author Mark Nielsen
 * @version $Id: restorelib.php,v 1.1 2009/12/21 01:01:26 michaelpenne Exp $
 * @package pagemenu
 **/

/**
 * This is the "graphical" structure of the pagemenu mod:
 *
 *         pagemenu
 *        (CL,pk->id)
 *             |
 *             |
 *             |
 *        pagemenu_links
 *    (pk->id,fk->pagemenuid)
 *             |
 *             |
 *             |
 *     pagemenu_link_data
 *     (pk->id,fk->linkid)
 *
 * Meaning: pk->primary key field of the table
 *          fk->foreign key to link with parent
 *          CL->course level info
 *          UL->user level info
 **/

/**
 * Restores pagemenu instances
 *
 * @param int $pagemenu ID of the pagemenu instance being restored
 * @param object $info xmlized object
 * @param object $restore Restore object
 * @return boolean
 **/
function pagemenu_restore_mods($mod, $restore) {
    static $loaded = false;

    if (!$loaded) {
        global $CFG;

        // Backup routine has enough performance problems - only call this once
        require_once($CFG->dirroot.'/mod/pagemenu/locallib.php');
        $loaded = true;
    }


    $status = true;

    // Get record from backup_ids
    $data = backup_getid($restore->backup_unique_code, $mod->modtype, $mod->id);

    if ($data) {
        // Now get completed xmlized object
        $info = $data->info;
        //traverse_xmlize($info);                                                                     //Debug
        //print_object ($GLOBALS['traverse_array']);                                                  //Debug
        //$GLOBALS['traverse_array']="";                                                              //Debug

        // Now, build the pagemenu record
        $pagemenu = new stdClass;
        $pagemenu->course = $restore->course_id;
        $pagemenu->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
        $pagemenu->render = isset($info['#']['RENDER']['0']['#']) ? backup_todb($info['#']['RENDER']['0']['#']) : 'list';
        $pagemenu->displayname = backup_todb($info['MOD']['#']['DISPLAYNAME']['0']['#']);
        $pagemenu->useastab = backup_todb($info['MOD']['#']['USEASTAB']['0']['#']);
        $pagemenu->taborder = backup_todb($info['MOD']['#']['TABORDER']['0']['#']);
        $pagemenu->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

        $newid = insert_record('pagemenu', $pagemenu);

        // Do some output
        if (!defined('RESTORE_SILENTLY')) {
            echo '<li>'.get_string('modulename', 'pagemenu').': "'.format_string(stripslashes($pagemenu->name)).'"</li>';
        }
        backup_flush(300);

        if ($newid) {
            // We have the newid, update backup_ids
            backup_putid($restore->backup_unique_code, $mod->modtype, $mod->id, $newid);

            if (!$status = pagemenu_links_restore_mods($newid, $info, $restore)) {
                debugging('Failed to restore page menu links, pagemenuid = '.$newid);
            }
        } else {
            $status = false;
        }
    }

    return $status;
}

/**
 * Restores the links for a pagemenu instance
 *
 * @param int $pagemenu ID of the pagemenu instance being restored
 * @param object $info xmlized object
 * @param object $restore Restore object
 * @return boolean
 **/
function pagemenu_links_restore_mods($pagemenuid, $info, $restore) {
    $status = true;

    // Get the data
    if (!empty($info['MOD']['#']['LINKS'])) {
        $data = $info['MOD']['#']['LINKS']['0']['#']['LINK'];
    } else {
        $data = array();
    }

    $previd = 0;
    for($i = 0; $i < sizeof($data); $i++) {
        $linkinfo = $data[$i];
        // traverse_xmlize($linkinfo);                                       //DEBUG
        // print_object ($GLOBALS['traverse_array']);                         //DEBUG
        // $GLOBALS['traverse_array']="";                                     //DEBUG

        // We'll need this later!!
        $oldid = backup_todb($linkinfo['#']['ID']['0']['#']);

        // Not super important - just back them up
        backup_todb($linkinfo['#']['PREVID']['0']['#']);
        backup_todb($linkinfo['#']['NEXTID']['0']['#']);

        $link = new stdClass;
        $link->pagemenuid = $pagemenuid;
        $link->previd = $previd;
        $link->nextid = 0;
        $link->type = backup_todb($linkinfo['#']['TYPE']['0']['#']);

        $link = pagemenu_append_link($link, $previd);

        // Do some output
        if (($i+1) % 50 == 0) {
            if (!defined('RESTORE_SILENTLY')) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
            }
            backup_flush(300);
        }

        if (!empty($link->id)) {
            backup_putid($restore->backup_unique_code, 'pagemenu_links', $oldid, $link->id);
            $previd = $link->id;

            if (!$status = pagemenu_link_data_restore_mods($link->id, $linkinfo, $restore)) {
                debugging('Failed to restore link data');
                break;
            }
        } else {
            debugging('Failed to Insert Link Record!');
            $status = false;
            break;
        }
    }

    return $status;
}

/**
 * Restores the link data for a link
 *
 * @param int $linkid ID of the link being restored
 * @param object $info xmlized object
 * @param object $restore Restore object
 * @return boolean
 **/
function pagemenu_link_data_restore_mods($linkid, $info, $restore) {
    $status = true;

    // Get the data
    if (!empty($info['#']['DATA'])) {
        $data = $info['#']['DATA']['0']['#']['DATUM'];
    } else {
        $data = array();
    }

    for ($i = 0; $i < sizeof($data); $i++) {
        $datainfo = $data[$i];
        //traverse_xmlize($datainfo);                                       //DEBUG
        //print_object ($GLOBALS['traverse_array']);                         //DEBUG
        //$GLOBALS['traverse_array']="";                                     //DEBUG

        // We'll need this later!!
        $oldid = backup_todb($datainfo['#']['ID']['0']['#']);

        $linkdata = new stdClass;
        $linkdata->linkid = $linkid;
        $linkdata->name = backup_todb($datainfo['#']['NAME']['0']['#']);
        $linkdata->value = backup_todb($datainfo['#']['VALUE']['0']['#']);

        if ($newid = insert_record('pagemenu_link_data', $linkdata)) {
            backup_putid($restore->backup_unique_code, 'pagemenu_link_data', $oldid, $newid);
        } else {
            debugging('Failed to insert data record');
            $status = false;
            break;
        }
    }

    return $status;
}

/**
 * Restore pagemenu logs
 *
 * @param object $restore Restore object
 * @param object $log Log object to be restored
 * @return mixed boolean false or the updated log
 **/
function pagemenu_restore_logs($restore, $log) {

    $status = false;

    if ($log->action != 'view all' and !$log->cmid) {
        // All logs in pagemenu set cmid except for view all in index.php
        return $status;
    }

    // Depending of the action, we recode different things
    switch ($log->action) {
        case 'view':
            $log->url = "view.php?id=$log->cmid";
            $status = true;
            break;

        case 'edit':
            $log->url = "edit.php?id=$log->cmid";
            $status = true;
            break;

        case 'view all':
            $log->url = "index.php?id=$log->course";
            $status = true;
            break;

        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
    }

    if ($status) {
        $status = $log;
    }

    return $status;
}

/**
 * Return a content decoded to support interactivities linking.
 * This is called automatically from
 * pagemenu_decode_content_links_caller() function
 * in the restore process
 *
 * @param string $content Content to be decoded
 * @param object $restore Restore object
 * @return string
 **/
function pagemenu_decode_content_links($content, $restore) {
    global $CFG;

    $result = $content;  // Yes, it is silly

    $decodekeys = array('PAGEMENUINDEXBYID' => 'index',
                        'PAGEMENUVIEWBYID'  => 'view',
                        'PAGEMENUEDITBYID'  => 'edit');

    foreach ($decodekeys as $token => $file) {
        $searchstring = '/\$@('.$token.')\*([0-9]+)@\$/';
        $foundset     = array();

        preg_match_all($searchstring, $result, $foundset);
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            foreach($foundset[2] as $oldcid) {
                // Get new IDs
                switch ($file) {
                    case 'index':
                        $newid = backup_getid($restore->backup_unique_code, 'course', $oldcid);
                        break;
                    case 'view':
                    case 'edit':
                    default:
                        $newid = backup_getid($restore->backup_unique_code, 'course_modules', $oldcid);
                        break;
                }

                // Update the searchstring
                $searchstring='/\$@('.$token.')\*('.$oldcid.')@\$/';

                if (isset($newid->new_id)) {
                    // It is a link to this course, update the link to its new location
                    $result = preg_replace($searchstring, "$CFG->wwwroot/mod/pagemenu/$file.php?id=$newid->new_id", $result);
                } else {
                    // It's a foreign link so leave it as original
                    $result = preg_replace($searchstring, "$restore->original_wwwroot/mod/pagemenu/$file.php?id=$oldcid", $result);
                }
            }
        }
    }

    return $result;
}

/**
 * This function makes all the necessary calls to {@link restore_decode_content_links_worker()}
 * function in order to mantain inter-activities during the restore process.
 * It's called from {@link restore_decode_content_links()}
 * function in restore process.
 *
 * @uses $CFG
 * @param object $restore Restore object
 * @return boolean
 **/
function pagemenu_decode_content_links_caller($restore) {
    global $CFG;

    $status = true;

    if ($links = get_records_sql("SELECT l.id, l.type
                                    FROM {$CFG->prefix}pagemenu p,
                                         {$CFG->prefix}pagemenu_links l
                                   WHERE p.id = l.pagemenuid
                                     AND p.course = $restore->course_id")) {

        // Include all link type classes
        require_once($CFG->dirroot.'/mod/pagemenu/link.class.php');

        foreach (pagemenu_get_links() as $type) {
            $path = "$CFG->dirroot/mod/pagemenu/links/$type.class.php";
            if (file_exists($path)) {
                require_once($path);
            }
        }

        $i = 0;
        foreach ($links as $link) {
            $deleteme = true;
            if ($data = get_records('pagemenu_link_data', 'linkid', $link->id)) {
                // Call statically for speed
                if (call_user_func(array("mod_pagemenu_link_$link->type", 'restore_data'), $data, $restore)) {
                    $deleteme = false;
                }
            }
            if ($deleteme) {
                // Restore of data failed, link is useless
                pagemenu_delete_link($link->id);
            }

            // Do some output
            if (($i+1) % 5 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 100 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
            $i++;
        }
    }

    return $status;
}

?>
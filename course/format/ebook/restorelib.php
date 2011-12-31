<?php
/**
 * Backup routine for this format
 *
 * @author Jeff Graham (original page format author)
 * @author Mark Nielsen (original page format author)
 * @author Nadav Kavalerchik (ebook format author)
 * @version $Id: restorelib.php,v 1.2 2011/05/10 01:00:29 michaelpenne Exp $
 * @package course_format_ebook
 **/

/**
 * eBook course format restore routine
 *
 * @param object $restore Restore object
 * @param array $data This is the xmlized information underneath FORMATDATA in the backup XML file.
 **/
function ebook_restore_format_data($restore, $data) {
    global $CFG;

    //require_once($CFG->dirroot.'/course/format/ebook/lib.php');

    $status = true;

    // Get the backup data
    if (!empty($data['FORMATDATA']['#']['PAGES']['0']['#']['PAGE'])) {
        //$newpageids = array();

        // Get all the pages and restore them.
        $pages = $data['FORMATDATA']['#']['PAGES']['0']['#']['PAGE'];
        for ($i = 0; $i < count($pages); $i++) {
            $pageinfo = $pages[$i];
            $page = new stdClass;
            $page->courseid = $restore->course_id;
            //$page->id = backup_todb($pageinfo['#']['ID']['0']['#']);
            //$page->courseid = backup_todb($pageinfo['#']['COURSEID']['0']['#']);
            $page->chapter = backup_todb($pageinfo['#']['CHAPTER']['0']['#']);
            $page->page = backup_todb($pageinfo['#']['PAGE']['0']['#']);
            $page->section = backup_todb($pageinfo['#']['SECTION']['0']['#']);
            $page->title = backup_todb($pageinfo['#']['TITLE']['0']['#']);

            $oldid = backup_todb($pageinfo['#']['ID']['0']['#']);

            if ($newid = insert_record('course_format_ebook', $page)) {
                //$newpageids[$oldid] = $newid;
                backup_putid($restore->backup_unique_code, 'course_format_ebook', $oldid, $newid);
            } else {
                $status = false;
                break;
            }
        }

        // Need to remap parentids
//        foreach($newpageids as $oldid => $newid) {
//            $parent = get_field('course_format_ebook', 'parent', 'id', $newid);
//            if (!empty($parent)) {
//                set_field('course_format_ebook', 'parent', $newpageids[$parent], 'id', $newid);
//            }
//        }
        // Need to fix sortorder for old courses - doesn't do much of anything if sortorder is already OK
        //$status = $status and page_fix_page_sortorder($restore->course_id);
        //$status = $status and page_fix_pageitem_sortorder($restore->course_id);  // Helps to repair sortorder if an item fails
    }
    return $status;
}

/**
 * This function makes all the necessary calls to {@link restore_decode_content_links_worker()}
 * function inorder to decode contents of this block from the backup 
 * format to destination site/course in order to mantain inter-activities 
 * working in the backup/restore process. 
 * 
 * This is called from {@link restore_decode_content_links()}
 * function in the restore process.  This function is called regarless of
 * the return value from {@link backuprestore_enabled()}.
 *
 * @param object $restore Standard restore object
 * @return boolean
 **/
function page_decode_format_content_links_caller($restore) {
    global $CFG;

    $status = true;
    // not sure what to do with this function, yet. (nadavkav 10-5-2011)
    return $status;

    // Only need to run this when the restore to course is being
    // deleted and created anew or when creating a new one
    if ($restore->restoreto == 0 || $restore->restoreto == 2) {
        require_once($CFG->dirroot.'/course/format/page/lib.php');

        $pageitems = get_records_sql("SELECT i.*
                                        FROM {$CFG->prefix}format_page p,
                                             {$CFG->prefix}format_page_items i
                                       WHERE p.id = i.pageid
                                         AND p.courseid = $restore->course_id
                                         AND i.blockinstance != 0");
        if (!empty($pageitems)) {
            foreach($pageitems as $pageitem) {
                $blockinstance = backup_getid($restore->backup_unique_code, 'block_instance', $pageitem->blockinstance);
                if (!empty($blockinstance)) {
                    set_field('format_page_items', 'blockinstance', $blockinstance->new_id, 'id', $pageitem->id);
                } else {
                    // Delete page item
                    if (page_block_delete($pageitem)) {
                        if (debugging() and !defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />Failed to remap block instance ID and successfully deleted page item ID: '.$pageitem->id.'<hr /><br />';
                        }
                    } else if (debugging() and !defined('RESTORE_SILENTLY')) {
                        echo '<br /><hr />Failed to remap block instance ID and failed to delete page item ID: '.$pageitem->id.'<hr /><br />';
                    }
                }
            }
        }

        // Relink locks (might be activity based, etc)
        $pages = get_records_sql("SELECT p.id, p.locks
                                    FROM {$CFG->prefix}format_page p
                                   WHERE p.courseid = $restore->course_id
                                     AND p.locks != ''");

        if (!empty($pages)) {
            foreach ($pages as $page) {
                if (!empty($page->locks)) {
                    $newlocks = format_page_lock::restore($restore, $page->locks);

                    if ($newlocks != $page->locks) {
                        set_field('format_page', 'locks', $newlocks, 'id', $page->id);
                    }
                }
            }
        }
    }
    return $status;
}
    
/**
 * Return content decoded to support interactivities linking.
 * This is called automatically from
 * {@link restore_decode_content_links_worker()} function
 * in the restore process.
 *
 * @param string $content Content to be dencoded
 * @param object $restore Restore preferences object
 * @return string The dencoded content
 **/
function page_decode_format_content_links($content, $restore) {
    global $CFG;

    // not sure what to do with this function, yet. (nadavkav 10-5-2011)
    return $content;

    $searchstring = '/\$@(COURSEFORMATFRONTPAGE)\*([0-9]+)@\$/';

    preg_match_all($searchstring, $content, $foundset);
    if ($foundset[0]) {
        // iterate of $foundset[2] and $foundset[3] they are the old_ids
        foreach($foundset[2] as $key => $old_id) {
            $rec = backup_getid($restore->backup_unique_code, 'format_page', $old_id);

            $searchstring = '/\$@(COURSEFORMATFRONTPAGE)\*('.$old_id.')@\$/';

            if (!empty($rec->new_id)) {
                $content = preg_replace($searchstring, $CFG->wwwroot.'/index.php?page='.$rec->new_id, $content);
            } else {
                // it's a link to an external site so leave alone
                $content = preg_replace($searchstring, $restore->original_wwwroot.'/index.php?page='.$old_id, $content);
            }
        }
    }

    $searchstring = '/\$@(COURSEFORMATPAGE)\*([0-9]+)\*([0-9]+)@\$/';

    preg_match_all($searchstring, $content, $foundset);
    if ($foundset[0]) {
        // iterate of $foundset[2] and $foundset[3] they are the old_ids
        foreach($foundset[2] as $key => $old_id) {
            $old_id2 = $foundset[3][$key];
            $rec = backup_getid($restore->backup_unique_code, 'course', $old_id);
            $rec2 = backup_getid($restore->backup_unique_code, 'format_page', $old_id2);

            $searchstring = '/\$@(COURSEFORMATPAGE)\*('.$old_id.')\*('.$old_id2.')@\$/';

            if (!empty($rec->new_id) && !empty($rec2->new_id)) {
                $content = preg_replace($searchstring, $CFG->wwwroot.'/course/view.php?id='.$rec->new_id.'&page='.$rec2->new_id, $content);
            } else {
                // it's a link to an external site so leave alone
                $content = preg_replace($searchstring, $restore->original_wwwroot.'/course/view.php?id='.$old_id.'&page='.$old_id2, $content);
            }
        }
    }

    $searchstring = '/\$@(COURSEFORMATMANAGEMENU)\*([0-9]+)@\$/';

    preg_match_all($searchstring, $content, $foundset);
    if ($foundset[0]) {
        // iterate of $foundset[2] and $foundset[3] they are the old_ids
        foreach($foundset[2] as $key => $old_id) {
            $rec = backup_getid($restore->backup_unique_code, 'course', $old_id);

            $searchstring = '/\$@(COURSEFORMATMANAGEMENU)\*('.$old_id.')@\$/';

            if (!empty($rec->new_id)) {
                $content = preg_replace($searchstring, $CFG->wwwroot.'/course/format/page/managemenu.php?id='.$rec->new_id, $content);
            } else {
                // it's a link to an external site so leave alone
                $content = preg_replace($searchstring, $restore->original_wwwroot.'/course/format/page/managemenu.php?id='.$old_id, $content);
            }
        }
    }

    return $content;
}

?>
 
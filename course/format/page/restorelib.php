<?php
/**
 * Backup routine for this format
 *
 * @author Jeff Graham
 * @author Mark Nielsen
 * @version $Id: restorelib.php,v 1.1 2009/12/21 01:00:29 michaelpenne Exp $
 * @package format_page
 **/

/**
 * Page formats restore routine
 *
 * @param object $restore Restore object
 * @param array $data This is the xmlized information underneath FORMATDATA in the backup XML file.
 **/
function page_restore_format_data($restore, $data) {
    global $CFG;

    require_once($CFG->dirroot.'/course/format/page/lib.php');
    require_once($CFG->dirroot.'/course/format/page/plugin/lock.php');

    $status = true;

    // Get the backup data
    if (!empty($data['FORMATDATA']['#']['PAGES']['0']['#']['PAGE'])) {
        $newpageids = array();

        // Get all the pages and restore them, restoring page items along the way.
        $pages = $data['FORMATDATA']['#']['PAGES']['0']['#']['PAGE'];
        for ($i = 0; $i < count($pages); $i++) {
            $pageinfo = $pages[$i];
            $page = new stdClass;
            $page->courseid = $restore->course_id;
            $page->nameone = backup_todb($pageinfo['#']['NAMEONE']['0']['#']);
            $page->nametwo = backup_todb($pageinfo['#']['NAMETWO']['0']['#']);
            $page->display = backup_todb($pageinfo['#']['DISPLAY']['0']['#']);
            $page->prefleftwidth = backup_todb($pageinfo['#']['PREFLEFTWIDTH']['0']['#']);
            $page->prefcenterwidth = backup_todb($pageinfo['#']['PREFCENTERWIDTH']['0']['#']);
            $page->prefrightwidth = backup_todb($pageinfo['#']['PREFRIGHTWIDTH']['0']['#']);
            $page->parent = backup_todb($pageinfo['#']['PARENT']['0']['#']); // will remap later when we know all ids are present
            $page->sortorder = backup_todb($pageinfo['#']['SORTORDER']['0']['#']);
            $page->template = backup_todb($pageinfo['#']['TEMPLATE']['0']['#']);
            $page->showbuttons = backup_todb($pageinfo['#']['SHOWBUTTONS']['0']['#']);
            $page->locks = isset($pageinfo['#']['LOCKS']['0']['#']) ? backup_todb($pageinfo['#']['LOCKS']['0']['#']) : '';

            $oldid = backup_todb($pageinfo['#']['ID']['0']['#']);

            if ($newid = insert_record('format_page', $page)) {
                $newpageids[$oldid] = $newid;
                backup_putid($restore->backup_unique_code, 'format_page', $oldid, $newid);

                // Now restore the page_items
                if (isset($pageinfo['#']['ITEMS'])) {
                    $items = $pageinfo['#']['ITEMS']['0']['#']['ITEM'];
                    for ($j = 0; $j < count($items); $j++) {
                        $iteminfo = $items[$j];

                        $item = new stdClass;
                        $item->pageid = $newid;
                        $item->cmid = backup_todb($iteminfo['#']['CMID']['0']['#']);

                        if (!empty($item->cmid)) {
                            // Try to remap the cm ID
                            $cmid = backup_getid($restore->backup_unique_code, 'course_modules', $item->cmid);

                            if ($cmid) {
                                $item->cmid = $cmid->new_id;
                            } else {
                                // Failed to remap - could be for various valid reasons - skip this item
                                continue;
                            }
                        }

                        $item->blockinstance = $iteminfo['#']['BLOCKINSTANCE']['0']['#']; // we'll remap blockids when we decode contentlinks
                        $item->position = backup_todb($iteminfo['#']['POSITION']['0']['#']);
                        $item->sortorder = backup_todb($iteminfo['#']['SORTORDER']['0']['#']);
                        $item->visible = backup_todb($iteminfo['#']['VISIBLE']['0']['#']);
                        $itemoldid = backup_todb($pageinfo['#']['ID']['0']['#']);

                        if ($itemnewid = insert_record('format_page_items', $item)) {
                            backup_putid($restore->backup_unique_code, 'format_page_items', $itemoldid, $itemnewid);
                        } else {
                            $status = false;
                            break;
                        }
                    }
                }
            } else {
                $status = false;
                break;
            }
        }

        // Need to remap parentids
        foreach($newpageids as $oldid => $newid) {
            $parent = get_field('format_page', 'parent', 'id', $newid);
            if (!empty($parent)) {
                set_field('format_page', 'parent', $newpageids[$parent], 'id', $newid);
            }
        }
        // Need to fix sortorder for old courses - doesn't do much of anything if sortorder is already OK
        $status = $status and page_fix_page_sortorder($restore->course_id);
        $status = $status and page_fix_pageitem_sortorder($restore->course_id);  // Helps to repair sortorder if an item fails
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
 
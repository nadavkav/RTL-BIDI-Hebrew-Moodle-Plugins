<?php
/**
 * Backup routine for this format
 *
 * @author Jeff Graham
 * @version $Id: backuplib.php,v 1.1 2009/12/21 01:00:28 michaelpenne Exp $
 * @package format_page
 **/

    /**
     * Format's backup routine
     *
     * @param handler $bf Backup file handler
     * @param object $preferences Backup preferences
     * @return boolean
     **/
    function page_backup_format_data($bf, $preferences) {
        $status = true;

        if ($pages = get_records('format_page', 'courseid', $preferences->backup_course)) {
            fwrite ($bf,start_tag('PAGES',3,true));
            foreach ($pages as $page) {
                fwrite ($bf, start_tag('PAGE', 4, true));
                fwrite ($bf, full_tag('ID', 5, false, $page->id));
                fwrite ($bf, full_tag('NAMEONE', 5, false, $page->nameone));
                fwrite ($bf, full_tag('NAMETWO', 5, false, $page->nametwo));
                fwrite ($bf, full_tag('DISPLAY', 5, false, $page->display));
                fwrite ($bf, full_tag('PREFLEFTWIDTH', 5, false, $page->prefleftwidth));
                fwrite ($bf, full_tag('PREFCENTERWIDTH', 5, false, $page->prefcenterwidth));
                fwrite ($bf, full_tag('PREFRIGHTWIDTH', 5, false, $page->prefrightwidth));
                fwrite ($bf, full_tag('PARENT', 5, false, $page->parent));
                fwrite ($bf, full_tag('SORTORDER', 5, false, $page->sortorder));
                fwrite ($bf, full_tag('TEMPLATE', 5, false, $page->template));
                fwrite ($bf, full_tag('SHOWBUTTONS', 5, false, $page->showbuttons));
                fwrite ($bf,full_tag('LOCKS',5,false,$page->locks));

                // Now grab the page items
                if ($items = get_records('format_page_items', 'pageid', $page->id, 'position, sortorder')) {
                    fwrite ($bf, start_tag('ITEMS', 5, true));
                    foreach($items as $item) {
                        fwrite ($bf, start_tag('ITEM', 6, true));
                        fwrite ($bf, full_tag('ID', 7, false, $item->id));
                        fwrite ($bf, full_tag('CMID', 7, false, $item->cmid));
                        fwrite ($bf, full_tag('BLOCKINSTANCE', 7, false, $item->blockinstance));
                        fwrite ($bf, full_tag('POSITION', 7, false, $item->position));
                        fwrite ($bf, full_tag('SORTORDER', 7, false, $item->sortorder));
                        fwrite ($bf, full_tag('VISIBLE', 7, false, $item->visible));
                        fwrite ($bf, end_tag('ITEM', 6, true));
                    }
                    fwrite ($bf, end_tag('ITEMS', 5, true));
                }
                fwrite ($bf, end_tag('PAGE', 4, true));
            }
    
            $status = fwrite ($bf,end_tag('PAGES',3,true));
        }

        return $status;
    }

    /**
     * Return a content encoded to support interactivities linking. This function is
     * called automatically from the backup procedure by {@link backup_encode_absolute_links()}.
     *
     * @param string $content Content to be encoded
     * @param object $restore Restore preferences object
     * @return string The encoded content
     **/
    function page_encode_format_content_links($content, $restore) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,'/');

        $search = "/(".$base."\/index.php\?page\=)([0-9]+)/";
        $content = preg_replace($search, '$@COURSEFORMATFRONTPAGE*$2@$', $content);

        $search = "/(".$base."\/course\/view.php\?id\=)([0-9]+)(\&amp;|\&)page\=([0-9]+)/";
        $content = preg_replace($search, '$@COURSEFORMATPAGE*$2*$4@$', $content);

        $search = "/(".$base."\/course\/format\/page\/managemenu.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@COURSEFORMATMANAGEMENU*$2@$', $content);

        return $content;
    }
?>
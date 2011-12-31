<?php
/**
 * Backup routine for this format
 *
 * @author Jeff Graham (original page format author)
 * @author Nadav Kavalerchik (ebook format author)
 * @version $Id: backuplib.php,v 1.2 2011/05/10 01:00:28 michaelpenne Exp $
 * @package course format eBook
 **/

    /**
     * Format's backup routine
     *
     * @param handler $bf Backup file handler
     * @param object $preferences Backup preferences
     * @return boolean
     **/
    function ebook_backup_format_data($bf, $preferences) {
        $status = true;

        if ($pages = get_records('course_format_ebook', 'courseid', $preferences->backup_course)) {
            fwrite ($bf,start_tag('PAGES',3,true));
            foreach ($pages as $page) {
                fwrite ($bf, start_tag('PAGE', 4, true));
                fwrite ($bf, full_tag('ID', 5, false, $page->id));
                fwrite ($bf, full_tag('COURSEID', 5, false, $page->courseid));
                fwrite ($bf, full_tag('CHAPTER', 5, false, $page->chapter));
                fwrite ($bf, full_tag('PAGE', 5, false, $page->page));
                fwrite ($bf, full_tag('SECTION', 5, false, $page->section));
                fwrite ($bf, full_tag('TITLE', 5, false, $page->title));
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

        // not sure what to do with this function, yet. (nadavkav 10-5-2011)
        return $content;

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
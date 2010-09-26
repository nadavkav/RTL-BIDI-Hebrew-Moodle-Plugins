<?php
/**
 * Page Item Definition
 *
 * @author Mark Nielsen
 * @version $Id: label.php,v 1.1 2009/12/21 01:00:31 michaelpenne Exp $
 * @package format_page
 **/

require_once($CFG->dirroot.'/course/format/page/plugin/pageitem.php');

class format_page_pageitem_label extends format_page_pageitem {
    /**
     * Add content to a block instance. This
     * method should fail gracefully.  Do not
     * call something like error()
     *
     * @param object $block Passed by refernce: this is the block instance object
     *                      Course Module Record is $block->cm
     *                      Module Record is $block->module
     *                      Module Instance Record is $block->moduleinstance
     *                      Course Record is $block->course
     *
     * @return boolean If an error occures, just return false and 
     *                 optionally set error message to $block->content->text
     *                 Otherwise keep $block->content->text empty on errors
     **/
    function set_instance(&$block) {
        global $CFG;

        $block->title = get_string('modulename', 'label');

        $options = new stdClass;
        $options->noclean = true;

        $block->content->text = format_text($block->moduleinstance->content, FORMAT_HTML, $options);

        return true;
    }
}
?>
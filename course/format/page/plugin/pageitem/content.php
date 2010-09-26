<?php
/**
 * Page Item Definition
 *
 * @author Mark Nielsen
 * @version $Id: content.php,v 1.1 2009/12/21 01:00:30 michaelpenne Exp $
 * @package format_page
 **/

require_once($CFG->dirroot.'/course/format/page/plugin/pageitem.php');

class format_page_pageitem_content extends format_page_pageitem {
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

        require_once($CFG->dirroot.'/mod/content/locallib.php');

        $module = mod_content_plugin::factory('module', $block->cm->id);

        if (!$text = $module->pageitem($block)) {
            // Run the default
            return parent::set_instance($block);
        }

        return true;
    }
}
?>
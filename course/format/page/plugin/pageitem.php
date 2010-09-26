<?php
/**
 * Page Item Definition
 *
 * @author Mark Nielsen
 * @version $Id: pageitem.php,v 1.1 2009/12/21 01:00:30 michaelpenne Exp $
 * @package format_page
 **/

class format_page_pageitem {
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

        $modinfo = get_fast_modinfo($block->course);

        // Get module icon
        if (!empty($modinfo->cms[$block->cm->id]->icon)) {
            $icon = $CFG->pixpath.'/'.urldecode($modinfo->cms[$block->cm->id]->icon);
        } else {
            $icon = "$CFG->modpixpath/{$block->module->name}/icon.gif";
        }

        $name = format_string($block->moduleinstance->name);
        $alt  = get_string('modulename', $block->module->name);
        $alt  = s($alt);

        $block->content->text  = "<img src=\"$icon\" alt=\"$alt\" class=\"icon\" />";
        $block->content->text .= "<a title=\"$alt\" href=\"$CFG->wwwroot/mod/{$block->module->name}/view.php?id={$block->cm->id}\">$name</a>";

        return true;
    }
}
?>
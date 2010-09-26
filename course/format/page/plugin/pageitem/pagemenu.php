<?php
/**
 * Page Item Definition
 *
 * @author Mark Nielsen
 * @version $Id: pagemenu.php,v 1.1 2009/12/21 01:00:31 michaelpenne Exp $
 * @package pagemenu
 **/

require_once($CFG->dirroot.'/course/format/page/plugin/pageitem.php');

class format_page_pageitem_pagemenu extends format_page_pageitem {
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

        if ($block->moduleinstance->displayname) {
            $block->hideheader = false;
        } else {
            $block->hideheader = true;
        }

        if (has_capability('mod/pagemenu:view', get_context_instance(CONTEXT_MODULE, $block->cm->id))) {
            require_once($CFG->dirroot.'/mod/pagemenu/locallib.php');
            $block->content->text = pagemenu_build_menu($block->moduleinstance->id, $block->moduleinstance->render);
        }

        return true;
    }
}
?>
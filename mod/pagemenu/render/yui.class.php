<?php
/**
 * Render the menu as list to be used for the YUI
 *
 * @author Mark Nielsen
 * @version $Id: yui.class.php,v 1.1 2009/12/21 01:01:27 michaelpenne Exp $
 * @package pagemenu
 **/

require_once($CFG->dirroot.'/mod/pagemenu/render.class.php');
require_once($CFG->dirroot.'/mod/pagemenu/render/list.class.php');

class mod_pagemenu_render_yui extends mod_pagemenu_render_list {
    /**
     * Add YUI prefix to class names
     *
     * @var string
     **/
    protected $classprefix = 'yui';

    /**
     * YUI needs whole structure
     *
     * @var boolean
     **/
    protected $descend = true;

    /**
     * Need to wrap UL element is extra DIVs
     *
     * @return string
     **/
    protected function ul($html, $depth) {
        $output = parent::ul($html, $depth);
        $output = "<div class=\"bd\">$output</div>\n";

        if ($depth != 0) {
            // Cannot have this div on root list
            $output = "<div class=\"yuimenu\">$output</div>";
        }

        return $output;
    }

    /**
     * Need to clear out post in the menuitem
     * which is usually an image and the YUI
     * uses its own image
     *
     * @return string
     **/
    protected function a($menuitem) {
        $cloned = clone($menuitem);
        $cloned->post = '';

        return parent::a($cloned);
    }
}

?>
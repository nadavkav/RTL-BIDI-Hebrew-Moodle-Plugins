<?php
/**
 * Render the menu as drop down menus
 *
 * @author Mark Nielsen
 * @version $Id: select.class.php,v 1.1 2009/12/21 01:01:27 michaelpenne Exp $
 * @package pagemenu
 **/

require_once($CFG->dirroot.'/mod/pagemenu/render.class.php');

class mod_pagemenu_render_select extends mod_pagemenu_render {

    protected function menuitems_to_html($menuitems, $depth = 0) {
        if (empty($menuitems)) {
            return '';
        }
        $options  = array();
        $children = array();
        $selected = '';

        foreach ($menuitems as $menuitem) {
            $options[$menuitem->url] = $menuitem->title;

            if ($menuitem->childtree) {
                // Sort of hackish, but works
                $selected = $menuitem->url;
                $children[] = $this->menuitems_to_html($menuitem->childtree, $depth+1);
            }
            // This selected takes priority
            if (!empty($menuitem->active)) {
                $selected = $menuitem->url;
            }
        }
        $html  = popup_form('', $options, "pagemenudropdown$depth", $selected, 'choose', '', '', true);
        $html .= "<br /><br />\n".implode("<br /><br />\n", $children);

        return $html;
    }
}

?>
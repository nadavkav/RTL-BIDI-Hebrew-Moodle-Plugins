<?php
/**
 * Render the menu as a list
 *
 * @author Mark Nielsen
 * @version $Id: list.class.php,v 1.1 2009/12/21 01:01:27 michaelpenne Exp $
 * @package pagemenu
 **/

require_once($CFG->dirroot.'/mod/pagemenu/render.class.php');

class mod_pagemenu_render_list extends mod_pagemenu_render {
    /**
     * Prefix the class names
     *
     * @var string
     **/
    protected $classprefix = '';

    protected function menuitems_to_html($menuitems, $depth = 0) {
        if (empty($menuitems)) {
            return '';
        }
        $html  = '';
        $first = true;
        $last  = false;
        $count = 1;
        $end   = count($menuitems);

        foreach ($menuitems as $menuitem) {
            if ($count == $end) {
                $last = true;
            }
            $item = $this->a($menuitem);
            if ($menuitem->childtree) {
                $item .= $this->menuitems_to_html($menuitem->childtree, $depth+1);
            }
            $html .= $this->li($item, $depth, $menuitem->active, $first, $last);

            if ($first) {
                $first = false;
            }
            $count++;
        }

        return $this->ul($html, $depth);
    }

    /**
     * Wrap content in a ul element
     *
     * @param string $html HTML to be wrapped
     * @param int $depth Current menu depth
     * @return string
     **/
    protected function ul($html, $depth) {
        if ($depth == 0) {
            $class = 'menutree';
        } else {
            $class = "childtree depth$depth";
        }
        $class = $this->prefix_class_names($class);

        return "<ul class=\"$class\">$html</ul>\n";
    }

    /**
     * Wrap content in a list element
     *
     * @param string $html HTML to be wrapped
     * @param int $depth Current menu depth
     * @param boolean $first This is the first list item
     * @param boolean $last This is the last list item
     * @return string
     **/
    protected function li($html, $depth, $active, $first, $last) {
        $class = "menuitem depth$depth";

        if ($active) {
            $class .= ' current';
        }
        if ($last) {
            $class .= ' lastmenuitem';
        }
        if ($first) {
            $class .= ' firstmenuitem';
        }
        $class = $this->prefix_class_names($class);

        return "<li class=\"$class\">$html</li>\n";
    }

    /**
     * Build a link tag from a menu item
     *
     * @param object $menuitem Menu item object
     * @return string
     **/
    protected function a($menuitem) {
        $menuitem->class .= ' menuitemlabel';

        if ($menuitem->active) {
            $menuitem->class .= ' current';
        }
        $menuitem->class = $this->prefix_class_names($menuitem->class);

        $title = s(trim(strip_tags($menuitem->title)));

        return "$menuitem->pre<a href=\"$menuitem->url\" title=\"$title\" onclick=\"this.target='_top'\" class=\"$menuitem->class\">$menuitem->title</a>$menuitem->post";
    }

    /**
     * Prefix all class names
     *
     * @param string $class The class string
     * @return string
     **/
    protected function prefix_class_names($class) {
        $classes = explode(' ', trim($class));
        array_walk($classes, create_function('&$item, $key', '$item = "'.$this->classprefix.'$item";'));
        return implode(' ', $classes);
    }
}

?>
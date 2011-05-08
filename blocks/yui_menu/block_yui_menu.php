<?php
/* This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 * The course navigation menu block
 *
 * This course menu is inspired by the Course Menu+ from NetSapiensis.com
 * and the one from Humbolt State University.
 *
 * @author Alan Trick
 * @copyright Trinity Western University and others
 * @license http://www.gnu.org/copyleft/gpl-3.0.html
 */
require_once $CFG->dirroot.'/mod/resource/lib.php';
require_once $CFG->dirroot.'/blocks/yui_menu/settingslib.php';

/**
 * The main class
 *
 * I don't think we support muliple instances, but it should be that
 * difficult to do so.
 */
class block_yui_menu extends block_base {

    function init() {
        $this->title = get_string('blockname','block_yui_menu');
        $this->version = '2007092000';
    }

    function instance_allow_config() {
        return true;
    }
    function has_config() {
        return true;
    }
    function applicable_formats() {
        return array(
            'course' => true,
            'my-index' => false,
         );
    }

    function get_content() {
        // cache content
        if (isset($this->content)) return $this->content;

        global $USER, $CFG, $COURSE, $PAGE;

        require_once $CFG->dirroot . '/course/lib.php';

        if (!empty($this->config->title)) {
          $this->title = $this->config->title;
        }

        $plugins = $this->load_plugins($CFG->dirroot . '/blocks/yui_menu/plugin');

        $itemlist = array();
        foreach ($plugins as $p) $p->add_items(&$itemlist, $this);
        $itemlist = $this->order_items($itemlist);

        // ouput stuff
        $menu = '';
        $yui_menu_scripts = array(); // id=>script
        $even = true;
        foreach ($itemlist as $name=>$item) {
            $prop = "item_$name";
            if (empty($this->config->$prop)) {
                if (!$item->plugin->visible) continue;
            } else {
                if ($this->config->$prop == 'hide') continue;
            }
            $mod = 'r' . ($even ? '0' : '1'); // even/odd classes
            $even = !$even;
            // add to menu
            $class = "$mod yui_menu_item_{$name}";
            if (!empty($item->children)) $class .= " yui_menu_tree";
            $menu .= "
<li class='$class'>
<div class='icon column c0'>{$item->icon()}</div>
<div class='column c1'>{$item->html()}</div>";
            if (!empty($item->children)) {
                // display the first level of children
                $menuid = "yui_menu_{$name}_tree_{$this->instance->id}";
                $menu .= "<div id='$menuid' class='yui_menu_js_tree'></div>";
                $script = $this->script_children($item->children, 'root', $menuid);
                $yui_menu_scripts[$menuid] = $script;
            }
            $menu .= "</li>";
        }
        if (!empty($menu)) {
            $output = "<ul class='list'>$menu</ul>";
        }
        if (!empty($yui_menu_scripts)) {
            $output .= "
<script type='text/javascript'>//<![CDATA[

function addTreeIcons(node) {
    for(var c in node.children) {
        child = node.children[c];
        // e might be null, meaning the child hasn't been expanded yet
        if (child._yui_menu_icon && (e = child.getLabelEl())) {
            e.style.backgroundImage = 'url('+child._yui_menu_icon+')';
            // more efficent if this is added as and event
            child._yui_menu_icon = null;
        }
    }
}
";
            foreach ($yui_menu_scripts as $id=>$script) {
                require_once $CFG->libdir . '/ajax/ajaxlib.php';
                echo require_js(array('yui_yahoo', 'yui_dom','yui_event','yui_treeview'));
                $output .= "
var tree = new YAHOO.widget.TreeView('$id');
var root = tree.getRoot();
$script
tree.draw();
// configure icons for elements that have already been loaded
for(var c in root.children) addTreeIcons(root.children[c]);
// for icons not yet loaded
tree.subscribe('expandComplete', addTreeIcons);";
            }
            $output .= "
//]]>
</script>";
        }
        $this->content = new stdClass;
        $this->content->text = $output;
        $this->content->footer = '';
        return $this->content;
    }

    /**
     * @param array $children list of yui_menu_item
     * @param string $parent javascript name of parent node
     * @param string $jsprefix javascript prefix
     * */
    function script_children($children, $parent, $jsprefix) {
        $script = '';
        foreach ($children as $k => $item) {
            $childname = "{$jsprefix}_{$k}";
            $expand = $item->expand ? 'true':'false';
            //$html = "<div class='icon column c0'>{$item->icon()}</div>"."<div class='column c1'>{$item->html()}</div>";
	    //$html = "<div class='column c0'>{$item->html()}</div>"."<div class='icon column c1'>{$item->icon()}</div>";
	    $html = "<div class='icon column c0'>{$item->icon()} {$item->html()}</div>";
            $html = str_replace('"', '\"', $html);
            $script .= "var $childname = new YAHOO.widget.HTMLNode(\"$html\", $parent, $expand);";
            if (isset($item->style)) {
                $script .= "{$childname}.contentStyle = '{$item->style}';";
            }
            if (!empty($item->children)) {
                $script .= $this->script_children($item->children, $childname, $childname);
            }
        }
        return $script;
    }
    /**
     * this is a rather nifty function, but I can't remember exactly
     * how it works anymore
     * */
    function order_items($list) {
        $ordered_list = array();
        // order items
        // $this->config->order_k = k-th entry in the tree
        $startorder = array_keys($list);
        $order = array();

        for ($i=0; $i < count($startorder); $i++) {
            $item = 'order_'.$i;
            if (!isset($this->config->$item)) continue;
            $attr = $this->config->$item;
            if (array_key_exists($attr, $list)) {
                // use stored value
                $order[$i] = $attr;
            }
        }
        foreach ($startorder as $item) {
            if (!in_array($item, $order)) {
                array_push($order, $item);
            }
        }
        foreach ($order as $item) {
            $ordered_list[$item] = $list[$item];
        }
        return $ordered_list;
    }
    /**
     * Searches through the directory and lists all of the eligable
     * files
     *
     * @return array string of names
     * */
    static function list_all_plugins($path) {
        $list = array();
        foreach(scandir($path) as $file) {
            // make sure file conforms to a valid php identifier
            // this has the nice property automatically skipping . and ..
            if (preg_match('/^([a-z][a-z0-9]*)\.php$/i', $file, $matches)) {
                $list[$matches[1]] = $file;
            }
        }
        return $list;
    }
    /**
     * Searches through the directory and includes all of the eligable
     * files an initialises the plugin classes
     *
     * @return array list of plugin objects
     * */
    static function load_plugins($path) {
        $settings = new yui_menu_plugin_settings();
        $config = $settings->get_setting();
        $return = array();
        $list = self::list_all_plugins($path);
        foreach ($config as $plugin=>$options) {
            if ($options['enabled']) {
                $class = "yui_menu_plugin_$plugin";
                require_once $path.'/'.$options['file'];
                $return[$plugin] = new $class($plugin, $options);
            }
        }
        return $return;
    }
}


abstract class yui_menu_plugin {

    public $id;
    public $visible; // set by configuration data

    function __construct($id, $options) {
        $this->id = $id;
        $this->visible = $options['visible'];
    }

    /*Add items to an array*/
    abstract function add_items($list, $block);

}

class yui_menu_item {

    public $plugin;
    public $text;
    public $icon;
    public $style;
    public $expand = true;

    function __construct($plugin, $text, $icon) {
        $this->plugin = $plugin;
        $this->text = $text;
        $this->icon_url = $icon;
        $this->children = array();
    }
    function icon() {
        if (empty($this->icon_url)) return '';
        $ico = htmlspecialchars($this->icon_url);
        return "<img src='$ico' alt='' />";
    }

    function html() {
        return htmlspecialchars($this->text);
    }
}

class yui_menu_item_link extends yui_menu_item {

    public $url;
    public $title;

    function __construct($plugin, $text, $url, $icon, $title = null) {
        $this->link_url = $url;
        if (isset($title)) $this->title = $title;
        parent::__construct($plugin, $text, $icon, $title);
    }

    function html() {
        // required parameters
        $url = htmlspecialchars($this->link_url);
        $txt = htmlspecialchars($this->text);
        // optional title attribute
        if (isset($this->title)) {
            $title = " title = '".htmlspecialchars($this->title)."'";
        } else {
            $title = '';
        }
        return "<a href='$url'$title>$txt</a>";
    }
}

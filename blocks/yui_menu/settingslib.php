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
 * Global configuration class
 *
 * 
 * @author Alan Trick
 * @copyright Copyright Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl-3.0.html
 */

require_once $CFG->dirroot.'/blocks/yui_menu/block_yui_menu.php';
require_once $CFG->libdir.'/adminlib.php';

class yui_menu_plugin_settings extends admin_setting {
    
    public $items;

    function __construct() {
        global $CFG;
        parent::__construct("plugins", get_string('plugin_settings', 'block_yui_menu'),
        get_string('configplugin_settings', 'block_yui_menu'), array());
        $pluginpath = $CFG->dirroot.'/blocks/yui_menu/plugin';
        $this->items = block_yui_menu::list_all_plugins($pluginpath);
        $this->plugin = 'block_yui_menu';
    }

    function get_setting() {
        // sorted value
        $result = $this->config_read($this->name);
        $return = array();
        if (!is_null($result) && $result !== '')
            foreach (explode(';', $result) as $value) {
                list($plugin,$enabled,$visible) = explode(',', $value);
                if (isset($this->items[$plugin])) {
                    $return[$plugin] = array(
                        'enabled'=> ($enabled == '1'),
                        'visible'=>($visible == '1'),
                        'file'=>$this->items[$plugin]
                    );
                }
            }
        foreach ($this->items as $item=>$file) {
            if (!isset($return[$item])) {
                $return[$item] = array('enabled'=>true, 'visible'=>true,
                    'file'=>$file);
            }
        }
        return $return;
    }

    function write_setting($data) {
        if (!is_array($data)) return ''; // ignore it
        foreach ($data as &$d) {
            $d['order'] = (int) $d['order'];
        }
        uasort($data, array($this, 'order'));
        
        $plugindata = array();
        foreach ($data as $k=>$v) {
            $entry = $k;
            foreach (array('enabled', 'visible') as $s) {
                if (isset($v[$s]) && $v[$s] == 1) $entry .= ',1';
                else $entry .= ',0';
            }
            $plugindata[] = $entry;
        }
        if ($this->config_write($this->name,implode(';', $plugindata))) {
            return '';
        }
        return get_string('errorsetting', 'admin');
    }

    function output_html($data, $query='') {
        global $CFG;
        $id = $this->get_id();
        $return = "<table id='$id' class='flexible generaltable generalbox'>
        <tr><th class='header'>".get_string('plugin','block_yui_menu')."</th>";
        $settings = array('enabled', 'visible', 'order');
        foreach ($settings as $s) {
            $return .= "<th class='header'>".get_string($s, 'block_yui_menu')."</th>";
        }
        $return .= "</tr>\n";
        
        $plugins = $this->get_setting();
        $i = 0;
        foreach ($plugins as $plugin=>$options) {
            $id = $this->get_id().'_'.$plugin;
            $name = $this->get_full_name();//.'_'.$plugin;
            $return .="<tr><td class='cell'>$plugin</td>";
            foreach ($settings as $s) {
                if ($s === 'order') {
                    $return .= "
    <td class='form-text'><input type='text' value='$i' id='{$id}_$s'
        name='{$name}[$plugin][$s]' size='2' /></td>\n";
                } else {
                    $check = $options[$s] ? ' checked="checked"':'';
                    $return .= "
    <td class='form-checkbox'><input type='checkbox'  value = '1'
        id='{$id}_$s' name='{$name}[$plugin][$s]'$check /></td>";
                }
            }
            $i++;
            $return .= "</tr>\n";
        }
        $return .= "</table>\n";
        
        return format_admin_setting($this, $this->visiblename, $return,
                    $this->description, false, '', NULL, $query);
    }
    // 'order' must be numeric
    function order($a, $b) {
        if ($a['order'] >= $b['order']) return 1;
        if ($a['order'] <= $b['order']) return -1;
        return 0;
    }
}

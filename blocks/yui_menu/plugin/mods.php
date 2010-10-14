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
 * Creats links to the modules indexes within a course
 */

class yui_menu_plugin_mods extends yui_menu_plugin {
    
    function add_items($list, $block) {
        global $CFG, $COURSE;
        
        // get list of mods used in course
        $modrecords = get_records_sql(
        "SELECT DISTINCT m.id, m.name
        FROM {$CFG->prefix}modules m, {$CFG->prefix}course_modules cm
        WHERE cm.course = '{$COURSE->id}'
        AND cm.module = m.id
        AND cm.visible > 0
        AND m.name != 'label'"); // ignore labels
        
        if (!$modrecords) return;
        
        foreach ($modrecords as $mod) {
            $list["mod_{$mod->name}"] = new yui_menu_item_link($this,
                get_string('modulenameplural', $mod->name),
                "{$CFG->wwwroot}/mod/{$mod->name}/?id={$COURSE->id}",
                "{$CFG->modpixpath}/{$mod->name}/icon.gif"
                );
        }
    }
}

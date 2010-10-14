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
 * Links to the gradebook if grades are enabled
 */
class yui_menu_plugin_gradebook extends yui_menu_plugin {
    
    function add_items($list, $block) {
        global $CFG, $COURSE;
        if ($COURSE->showgrades) {
            $list[$this->id] = new yui_menu_item_link($this,
                get_string('gradebook', 'grades'),
                "{$CFG->wwwroot}/grade/?id={$COURSE->id}",
                "{$CFG->pixpath}/i/grades.gif");
        }
    }
}

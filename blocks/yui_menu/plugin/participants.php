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
 * Adds a link to the participants page
 */

class yui_menu_plugin_participants extends yui_menu_plugin {
    
    function add_items($list, $block) {
        global $CFG, $COURSE;
        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        if (has_capability('moodle/course:viewparticipants', $context)) {
            $list[$this->id] = new yui_menu_item_link($this,
                get_string('participants'),
                "{$CFG->wwwroot}/user/?contextid={$context->id}",
                "{$CFG->pixpath}/i/users.gif",
                get_string('listofallpeople'));
        }
    }
}

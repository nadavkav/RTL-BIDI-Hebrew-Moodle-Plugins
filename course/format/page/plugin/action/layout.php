<?php
/**
 * More or less, the default action - displays
 * a page
 *
 * @author Mark Nielsen, Jeff Graham
 * @version $Id: layout.php,v 1.1 2009/12/21 01:00:30 michaelpenne Exp $
 * @package format_page
 **/

require_once($CFG->dirroot.'/course/format/page/plugin/action.php');

class format_page_action_layout extends format_page_action {

    function display() {
        global $PAGE, $COURSE;

        $editing = $PAGE->user_is_editing();
        $pageblocks = page_blocks_setup();

    /// Make sure we can see this page
        if (!($this->page->display & DISP_PUBLISH) and !(has_capability('format/page:editpages', $this->context) and $editing)) {
            error(get_string('thispageisnotpublished', 'format_page'));
        }

    /// Finally, we can print the page
        if ($editing) {
            $PAGE->print_tabs('layout');
            page_print_jump_menu();
            page_print_add_mods_form($this->page, $COURSE);
            $class = 'format-page editing';
        } else {
            $class = 'format-page';
        }

        echo '<table id="layout-table" class="'.$class.'" cellspacing="0" summary="'.get_string('layouttable').'">';

    /// Check if the page is locked, if so, print lock message, otherwise print three columns
        if (page_is_locked($this->page)) {
            echo '<tr><td colspan="3">';
            page_print_lock_prerequisites($this->page);
            echo '</tr></td>';
        } else {
            echo '<tr>';
            page_print_position($pageblocks, BLOCK_POS_LEFT, $this->page->prefleftwidth);
            page_print_position($pageblocks, BLOCK_POS_CENTER, $this->page->prefcenterwidth);
            page_print_position($pageblocks, BLOCK_POS_RIGHT, $this->page->prefrightwidth);
            echo '</tr>';
        }

    /// Silently attempts to call a function from the block_recent_history block
        @block_method_result('recent_history', 'block_recent_history_record', $this->page);

    /// Display navigation buttons
        if ($this->page->showbuttons) {
            $nav     = page_get_next_previous_pages($this->page->id, $this->page->courseid);
            $buttons = '';

            if ($nav->prev and ($this->page->showbuttons & BUTTON_PREV)) {
                $title    = get_string('previous', 'format_page', page_get_name($nav->prev));
                $buttons .= '<span class="prevpage"><a href="'.$PAGE->url_build('page', $nav->prev->id)."\" title=\"$title\">$title</a></span>";
            }
            if ($nav->next and ($this->page->showbuttons & BUTTON_NEXT)) {
                $title    = get_string('next', 'format_page', page_get_name($nav->next));
                $buttons .= '<span class="nextpage"><a href="'.$PAGE->url_build('page', $nav->next->id)."\" title=\"$title\">$title</a></span>";
            }
            // Make sure we have something to print
            if (!empty($buttons)) {
                echo "\n<tr><td></td><td>$buttons</td><td></td></tr>\n";
            }
        }

        echo '</table>';
    }
}

?>
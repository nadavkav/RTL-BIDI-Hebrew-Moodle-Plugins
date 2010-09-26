<?php
/**
 * Edit page settings definition
 *
 * @author Mark Nielsen, Jeff Graham
 * @version $Id: editpage.php,v 1.1 2009/12/21 01:00:30 michaelpenne Exp $
 * @package format_page
 **/

require_once($CFG->dirroot.'/course/format/page/plugin/action/form/editpage.php');
require_once($CFG->dirroot.'/course/format/page/plugin/action.php');

class format_page_action_editpage extends format_page_action {

    function display() {
        global $CFG, $PAGE, $COURSE;

        $returnaction = optional_param('returnaction', '', PARAM_ALPHA);

        if ($pageid = optional_param('page', 0, PARAM_INT)) {
            require_capability('format/page:editpages', $this->context);

            if ($returnaction) {
                $currenttab = $returnaction;
            } else {
                $currenttab = 'settings';
            }
            if (!$page = get_record('format_page', 'id', $pageid)) {
                error('Invalid page ID');
            }
        } else {
            require_capability('format/page:addpages', $this->context);

            $currenttab = 'addpage';
            $page       = NULL;
        }

        // Find possible parents for this page
        if ($parents = page_get_possible_parents($pageid, $COURSE->id)) {
            $possibleparents = array(0 => get_string('none'));
            foreach ($parents as $parent) {
                $possibleparents[$parent->id] = page_name_menu($parent->nameone, $parent->depth);
            }
        } else {
            $possibleparents = array();
        }

        $mform = new format_page_editpage_form($CFG->wwwroot.'/course/format/page/format.php', $possibleparents);

        if ($mform->is_cancelled()) {
            if ($returnaction) {
                // Return back to a specific action
                redirect($PAGE->url_build('page', $page->id, 'action', $returnaction));
            } else {
                redirect($PAGE->url_get_full());
            }

        } else if ($data = $mform->get_data()) {
            // Save/update routine
            $page                  = new stdClass;
            $page->nameone         = $data->nameone;
            $page->nametwo         = $data->nametwo;
            $page->courseid        = $data->id;
            $page->display         = $data->publish | $data->dispmenu | $data->disptheme;
            $page->prefleftwidth   = $data->prefleftwidth;
            $page->prefcenterwidth = $data->prefcenterwidth;
            $page->prefrightwidth  = $data->prefrightwidth;
            $page->template        = $data->template;
            $page->showbuttons     = $data->showbuttons;
            $page->parent          = $data->parent;

            // There can only be one!
            if ($page->template) {
                // only one template page allowed
                set_field('format_page', 'template', 0, 'courseid', $page->courseid);
            }

            if ($data->page) {
                // Updating
                $page->id = $data->page;

                if ($page->parent != get_field('format_page', 'parent', 'id', $page->id)) {
                    // Moving - re-assign sortorder
                    $page->sortorder = page_get_next_sortorder($page->parent, $page->courseid);

                    // Remove from old parent location
                    page_remove_from_ordering($page->id);
                }
                if (!update_record('format_page', $page)) {
                    error(get_string('couldnotupdatepage', 'format_page'));
                }
            } else {
                // Creating new
                $page->sortorder = page_get_next_sortorder($page->parent, $page->courseid);

                if (!$page->id = insert_record('format_page', $page)) {
                    error(get_string('couldnotinsertnewpage', 'format_page'));
                }
            }

            if ($returnaction) {
                // Return back to a specific action
                redirect($PAGE->url_build('page', $page->id, 'action', $returnaction));
            } else {
                // Default, view the page
                redirect($PAGE->url_build('page', $page->id));
            }

        } else if ($mform->is_submitted() and !defined('HEADER_PRINTED')) {
            // This is for when JavaScript is turned off and validation failed
            print_error('editformvalidationfailed', 'format_page', $PAGE->url_build('action', 'editpage'));

        } else {
            // Set up data to be sent to the form
            // Might come from a page or page template record
            $toform = new stdClass;

            if (!empty($page)) {
                $toform       = $page;
                $toform->page = $page->id;
            } else if ($template = get_record('format_page', 'template', 1, 'courseid', $COURSE->id, '', '', 
                                              'prefleftwidth, prefcenterwidth, prefrightwidth, showbuttons, display')) {
                $toform = $template;
            }

            // Special handling for display field
            if (!empty($toform->display)) {
                if ($toform->display & DISP_PUBLISH) {
                    $toform->publish = DISP_PUBLISH;
                }
                if ($toform->display & DISP_MENU) {
                    $toform->dispmenu = DISP_MENU;
                }
                if ($toform->display & DISP_THEME) {
                    $toform->disptheme = DISP_THEME;
                }
            }

            // Done here on purpose
            $toform->id = $COURSE->id;
            $toform->returnaction = $returnaction;

            $mform->set_data($toform);

            $PAGE->print_tabs($currenttab);
            $mform->display();
        }
    }
}

?>
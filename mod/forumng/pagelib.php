<?php
require_once($CFG->libdir.'/pagelib.php');
require_once($CFG->dirroot.'/course/lib.php'); // needed for some blocks

define('PAGE_FORUMNG_VIEW', 'mod-forumng-view');
page_map_class(PAGE_FORUMNG_VIEW, 'page_forumng_view');

$DEFINEDPAGES = array(PAGE_FORUMNG_VIEW);

class page_forumng_view extends page_generic_activity {

    function get_type() {
        return PAGE_FORUMNG_VIEW;
    }

    function init_quick($data) {
        if(empty($data->pageid)) {
            error('Cannot quickly initialize page: empty course id');
        }
        $this->activityname = 'forumng';
        parent::init_quick($data);
    }

    function init_full() {
        global $CURRENTFORUM;
        if($this->full_init_done) {
            return;
        }
        $this->modulerecord = $CURRENTFORUM->get_course_module();
        $this->modulerecord->section = get_field('course_modules', 'section', 'id', $this->modulerecord->id);
        $this->courserecord = $CURRENTFORUM->get_course();
        $this->activityrecord = (object)array('id'=>$CURRENTFORUM->get_id(), 'name'=>$CURRENTFORUM->get_name());
        $this->full_init_done = true;
    }

    // The following is a copy-paste of the code for page_generic_activity.
    // It has then been modified slightly.
    // I had to copy the whole thing because that piece of cr*p doesn't let
    // you change the $buttontext
    function print_header($title, $morenavlinks = NULL, $bodytags = '', $meta = '',
        $extrabuttons='') {
        global $USER, $CFG;

        $this->init_full();
        $replacements = array(
            '%fullname%' => format_string($this->activityrecord->name)
        );
        foreach ($replacements as $search => $replace) {
            $title = str_replace($search, $replace, $title);
        }

        if (empty($morenavlinks) && $this->user_allowed_editing()) {
            // mmmm, tables
            $buttons = '<table><tr>';
            $buttons .= '<td>' . $extrabuttons . '</td>';
            $buttons .= '<td>'.update_module_button(optional_param('clone',
                $this->modulerecord->id, PARAM_INT),
                $this->courserecord->id, get_string('modulename', $this->activityname)).'</td>';
            if (!empty($CFG->showblocksonmodpages)) {
                $clonething = optional_param('clone', 0, PARAM_INT);
                $clonething = $clonething ?
                    '<input type="hidden" name="clone" value="' . $clonething . '" />' : '';
                $buttons .= '<td><form '.$CFG->frametarget.' method="get" action="view.php"><div>'.
                    '<input type="hidden" name="id" value="'.$this->modulerecord->id.'" />'.
                    $clonething .
                    '<input type="hidden" name="edit" value="'.($this->user_is_editing()?'off':'on').'" />'.
                    '<input type="submit" value="'.get_string($this->user_is_editing()?'blockseditoff':'blocksediton').'" /></div></form></td>';
            }
            $buttons .= '</tr></table>';
        } else {
            $buttons = $extrabuttons ? $extrabuttons : '&nbsp;';
        }

        if (empty($morenavlinks)) {
            $morenavlinks = array();
        }
        $navigation = build_navigation($morenavlinks, $this->modulerecord);
        print_header($title, $this->courserecord->fullname, $navigation, '', $meta, true, $buttons, navmenu($this->courserecord, $this->modulerecord), false, $bodytags);
    }

}

?>

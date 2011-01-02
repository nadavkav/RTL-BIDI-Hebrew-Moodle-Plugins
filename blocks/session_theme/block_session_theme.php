<?php

class block_session_theme extends block_base {
    function init() {
        $this->title = get_string('blockname', 'block_session_theme');
        $this->version = 2007111500;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function get_content () {
        global $CFG, $ME, $COURSE;

        //get list of themes
        $themes = get_list_of_themes();

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content->footer = '';
        $this->content->text = '';

        $context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);

        if (has_capability('block/session_theme:switchthemes', $context)) {

            $this->content->text .= popup_form($ME. '?id=' . $COURSE->id . '&amp;theme=', $themes, 'sessionthemeform', current_theme(), 'choose', '', '', true);

            $this->content->footer .= '';
        } else {
            $this->content = '';
        }

        return $this->content;
    }
}

    function instance_allow_multiple() {
        return true;
    }

    function has_config() {
        return false;
    }


?>
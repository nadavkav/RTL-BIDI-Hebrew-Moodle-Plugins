<?php //$Id: block_fn_site_groups.php,v 1.6 2009/08/12 19:47:08 mchurch Exp $

require_once ($CFG->dirroot.'/blocks/fn_site_groups/lib.php');

class block_fn_site_groups extends block_base {

    function init() {
        $this->title = get_string('title', 'block_fn_site_groups');
        $this->version = 2009050107;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function specialization() {
        $this->title = isset($this->config->title) ? $this->config->title : get_string('displaytitle', 'block_fn_site_groups');
    }

    function has_config() {
        return true;
    }

    function instance_allow_multiple() {
        return false;
    }

    /**
     * Default behavior: save all variables as $CFG properties
     * You don't need to override this if you 're satisfied with the above
     *
     * @param array $data
     * @return boolean
     */
    function config_save($data) {
        print_object($data); die;
        return parent::config_save($data);
    }

    function get_content() {
        global $CFG, $course;

        if ($this->content !== NULL) {
            return $this->content;
        }

        if (!empty($this->instance->pageid)) {
            $context = get_context_instance(CONTEXT_COURSE, $this->instance->pageid);
        }

        if (empty($context)) {
            $context = get_context_instance(CONTEXT_SYSTEM);
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if ((empty($course) || ($course->id == SITEID)) && !empty($CFG->block_fn_site_groups_enabled) &&
            has_capability('block/fn_site_groups:managegroups', $context)) {  // Just return
//            $this->content->text .= '<a href="'.$CFG->wwwroot.'/group/index.php?id='.SITEID.'">'.
//                                    get_string('managegroups', 'block_fn_site_groups').'</a><br />';
            $this->content->text .= '<a href="'.$CFG->wwwroot.'/blocks/fn_site_groups/sitegroups.php?courseid='.SITEID.'">'.
                                    get_string('managegroups', 'block_fn_site_groups').'</a><br />';
            }
//        if (!empty($course) && ($course->id != SITEID) &&
//            has_capability('block/fn_site_groups:managegroupmembers', $context)) {
//            $this->content->text .= '<a href="'.$CFG->wwwroot.'/blocks/fn_site_groups/g8_registration.php?id='.$course->id.'">'.
//                                    get_string('registration', 'block_fn_site_groups').'</a><br />';
//        }

        return $this->content;
    }
}
?>
<?php //$Id: block_fn_admin.php,v 1.5 2009/05/05 12:35:57 mchurch Exp $

class block_fn_admin extends block_list {
    function init() {
        $this->title = get_string('blockname','block_fn_admin');
        $this->version = 2008040100;
    }

    function specialization() {
        global $course;
        /// Need the bigger course object.
        $this->course = $course;

        if (!empty($this->config->displaytitle)) {
            $this->title = $this->config->displaytitle;
        }
    }

    function has_config() {
        return true;
    }

    function instance_allow_config() {
        return true;
    }

    function get_content() {

        global $CFG, $USER, $SITE;

        $adminblockexists = file_exists($CFG->dirroot.'/blocks/admin/block_admin.php');

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content = '';
        } else if ($this->instance->pageid == SITEID) {
            // return $this->content = '';
        }

        if (!empty($this->instance->pageid)) {
            $context = get_context_instance(CONTEXT_COURSE, $this->instance->pageid);
        }

        if (empty($context)) {
            $context = get_context_instance(CONTEXT_SYSTEM);
        }

        if (!$course = get_record('course', 'id', $this->instance->pageid)) {
            $course = $SITE;
        }

        if (!has_capability('moodle/course:view', $context)) {  // Just return
            return $this->content;
        }

        if (empty($CFG->loginhttps)) {
            $securewwwroot = $CFG->wwwroot;
        } else {
            $securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
        }

    /// Get admin block content:
        if (((isset($this->config->showadminmenu) && $this->config->showadminmenu) ||
             (!isset($this->config->showadminmenu) && !empty($CFG->block_fn_admin_showadminmenu))) && $adminblockexists) {
            require_once($CFG->dirroot.'/blocks/admin/block_admin.php');

            $adminblock = new block_admin();
            $adminblock->init();
            $admincontent = $adminblock->get_content();

			if (!empty($admincontent)) {
				$this->content->items = array_merge($this->content->items, $admincontent->items);
				$this->content->icons = array_merge($this->content->icons, $admincontent->icons);
			}
        }


    /// Now update any existing settings according to specific FN needs:

        /// Check for custom course settings and change/add trhe link as necessary.
        $customcourse = file_exists($CFG->dirroot.'/course/format/'.$course->format.'/settings.php');
        /// Change to custom course settings if there are any:
        if ($customcourse &&
            (($key = array_search('<a href="'.$CFG->wwwroot.'/course/edit.php?id='.$this->instance->pageid.'">'.get_string('settings').'</a>',
                                 $this->content->items)) !== false)) {
            $this->content->items[$key] = '<a href="'.$CFG->wwwroot.'/course/format/'.$course->format.'/settings.php?id='.
                                          $this->instance->pageid.'">'.get_string('settings').'</a>';
        }

        /// Check to see if the unenrol link should be hidden:
        if (((isset($this->config->showunenrol) && empty($this->config->showunenrol)) ||
             (!isset($this->config->showunenrol) && empty($CFG->block_fn_admin_showunenrol))) &&
            (($key = array_search('<a href="'.$CFG->wwwroot.'/course/unenrol.php?id='.$this->instance->pageid.'">'.
                                   get_string('unenrolme', '', format_string($course->shortname)).'</a>',
                                  $this->content->items)) !== false)) {
            unset($this->content->items[$key]);
            unset($this->content->icons[$key]);
        }

        /// Check to see if the enrol link should be hidden:
        if (((isset($this->config->showunenrol) && empty($this->config->showunenrol)) ||
             (!isset($this->config->showunenrol) && empty($CFG->block_fn_admin_showunenrol))) &&
            (($key = array_search('<a href="'.$CFG->wwwroot.'/course/enrol.php?id='.$this->instance->pageid.'">'.
                                   get_string('enrolme', '', format_string($course->shortname)).'</a>',
                                  $this->content->items)) !== false)) {
            unset($this->content->items[$key]);
            unset($this->content->icons[$key]);
        }

        /// Check to see if the profile link should be hidden:
        if (((isset($this->config->showprofile) && empty($this->config->showprofile)) ||
             (!isset($this->config->showprofile) && empty($CFG->block_fn_admin_showprofile))) &&
            (($key = array_search('<a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&amp;course='.$course->id.'">'.get_string('profile').'</a>',
                                  $this->content->items)) !== false)) {
            unset($this->content->items[$key]);
            unset($this->content->icons[$key]);
        }

    /// Now list the special items:
        if (!empty($this->content->items)) {
            $this->content->items[] = '<hr />';
            $this->content->icons[] = '';
        }

        if ($customcourse && ($course->id !== SITEID) && has_capability('moodle/course:update', $context)) {
            $this->content->items[] = '<a href="'.$CFG->wwwroot.'/course/format/'.$course->format.'/settings.php?id='.$course->id.'&extraonly=1">'.
                                      get_string('coursesettings', 'block_fn_admin').'</a>';
            $this->content->icons[]='<img src="'.$CFG->pixpath.'/i/edit.gif" class="icon" alt="" />';
        }

        if (file_exists($CFG->dirroot.'/blocks/fn_locations/locations.php') &&
            has_capability('block/fn_locations:managelocations', $context)) {
            $this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/fn_locations/locations.php?id='.$course->id.'">'.
                                      get_string('locations', 'block_fn_locations').'</a><br />';
            $this->content->icons[]='<img src="'.$CFG->pixpath.'/i/edit.gif" class="icon" alt="" />';
        }

        return $this->content;
    }

    function applicable_formats() {
        return array('*' => true);   // Not needed on site
    }
}

?>

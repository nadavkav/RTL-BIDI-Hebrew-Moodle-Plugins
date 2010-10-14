<?php // $Id: block_quickmail.php,v 1.10 2007/03/02 03:06:53 mark-nielsen Exp $
/**
 * Quickmail - Allows teachers and students to email one another
 *      at a course level.  Also supports group mode so students
 *      can only email their group members if desired.  Both group
 *      mode and student access to Quickmail are configurable by
 *      editing a Quickmail instance.
 *
 * @author Mark Nielsen
 * @version $Id: block_quickmail.php,v 1.10 2007/03/02 03:06:53 mark-nielsen Exp $
 * @package quickmail
 **/ 

/**
 * This is the Quickmail block class.  Contains the necessary
 * functions for a Moodle block.  Has some extra functions as well
 * to increase its flexibility and useability
 *
 * @package quickmail
 * @todo Make a global config so that admins can set the defaults (default for student (yes/no) default for groupmode (select a groupmode or use the courses groupmode)) NOTE: make sure email.php and emaillog.php use the global config settings
 **/
class block_quickmail extends block_list {
    
    /**
     * Sets the block name and version number
     *
     * @return void
     **/
    function init() {
        $this->title = get_string('blockname', 'block_quickmail');
        $this->version = 2006021501;  // YYYYMMDDXX
    }
    
    /**
     * Gets the contents of the block (course view)
     *
     * @return object An object with an array of items, an array of icons, and a string for the footer
     **/
    function get_content() {
        global $USER, $CFG;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->items = array();
        $this->content->icons = array();
        
        if (empty($this->instance) or !$this->check_permission()) {
            return $this->content;
        }

    /// link to composing an email
        $this->content->items[] = "<a href=\"$CFG->wwwroot/blocks/quickmail/email.php?id={$this->course->id}&amp;instanceid={$this->instance->id}\">".
                                    get_string('compose', 'block_quickmail').'</a>';

        $this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/email.gif" height="16" width="16" alt="'.get_string('email').'" />';

    /// link to history log
        $this->content->items[] = "<a href=\"$CFG->wwwroot/blocks/quickmail/emaillog.php?id={$this->course->id}&amp;instanceid={$this->instance->id}\">".
                                    get_string('history', 'block_quickmail').'</a>';

        $this->content->icons[] = '<img src="'.$CFG->pixpath.'/t/log.gif" height="14" width="14" alt="'.get_string('log').'" />';

        return $this->content;
    }

    /**
     * Loads the course
     *
     * @return void
     **/
    function specialization() {
        global $COURSE;

        $this->course = $COURSE;
    }

    /**
     * Cleanup the history
     *
     * @return boolean
     **/
    function instance_delete() {
        return delete_records('block_quickmail_log', 'courseid', $this->course->id);
    }

    /**
     * Set defaults for new instances
     *
     * @return boolean
     **/
    function instance_create() {
        $this->config = new stdClass;
        $this->config->groupmode = $this->course->groupmode;
        $pinned = (!isset($this->instance->pageid));
        return $this->instance_config_commit($pinned);
    }

    /**
     * Allows the block to be configurable at an instance level.
     *
     * @return boolean
     **/
    function instance_allow_config() {
        return true;
    }

    /**
     * Check to make sure that the current user is allowed to use Quickmail.
     *
     * @return boolean True for access / False for denied
     **/
    function check_permission() {
        return has_capability('block/quickmail:cansend', get_context_instance(CONTEXT_BLOCK, $this->instance->id));
    }

    /**
     * Get the groupmode of Quickmail.  This function pays
     * attention to the course group mode force.
     *
     * @return int The group mode of the block
     **/
    function groupmode() {
        return groupmode($this->course, $this->config);
    }
}
?>

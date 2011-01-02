<?php

/**
 * OU search block. Not really much of a block yet :) But contains our 
 * database-driven search implementation.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @author m.kassaei@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ousearch
 */
class block_ousearch extends block_base {

    function init() {
        $this->title = get_string('coursesearch','block_ousearch');
        $this->version = '2010062500'; 
        $this->cron = 0;
    }

    function get_content() {
        if ($this->content!==NULL) {
            return $this->content;
        }

        global $CFG, $PAGE;
        $this->content = new stdClass;
        $this->content->footer = ' ';

        $plugin = '';
        $searchtype = 'choose';
        if ($this->config) {
            $searchtype = $this->config->searchtype;
            if ($searchtype === 'forumng') {
                $title = get_string('searchforums','block_ousearch');
                $this->title = $title. ' '.
                helpbutton('search_forums', $title, 'forumng', true, false, '', true);
                $plugin = 'mod/forumng';
            } elseif ($searchtype === 'multiactivity') {
                $this->title = get_string('searchthiswebsite','block_ousearch');
            }
        }

        //If search type is not set print a warning or hide the block
        if ($searchtype === 'choose') {
            //Print a warning when editing is on
            if ($PAGE->user_is_editing()) {
                $this->content->text = get_string('seachtypeisnotselected', 'block_ousearch');
            //Hide the block when editing is off
            } else {
                $this->title = '';
                $this->content->text = '';
            }
            return $this->content;
        }

        $this->content->text="
<form method='get' action='{$CFG->wwwroot}/blocks/ousearch/search.php'>
<div>
<input type='hidden' name='course' value='{$this->instance->pageid}'/>
<input type='hidden' name='plugin' value='{$plugin}'/>
<input type='text' name='query' size='12'
/><input type='submit' value='".get_string('search')."'/>
</div>
</form>
";

        // Print a notice for user when edisting
        if ($searchtype === 'multiactivity' &&  $PAGE->user_is_editing()) {
            $this->content->text .= get_string('searchthiswebsitenotice', 'block_ousearch');
        }
 
        return $this->content;
    }

    function applicable_formats() {
        return array('course' => true, 'all' => false);
    }

    function after_install() {
        global $CFG;
        require_once(dirname(__FILE__).'/searchlib.php');
        // Give all modules a chance to set up their save data
        $modules=get_records('modules');
        print '<ul>';
        foreach($modules as $module) {
            if(!@include_once($CFG->dirroot.'/mod/'.$module->name.'/lib.php')) {
                continue;
            }
            $function=$module->name.'_ousearch_update_all';
            if(!function_exists($function)) {
                continue;
            }
            print '<li>Filling search tables for '.$module->name.'<ul>'; 
            $function(true);
            print '</ul></li>';
        }
        print '</ul>';
        
    }

    function has_config() {
        return true;
    }
    function instance_allow_config() {
        return true;
    }

    function instance_config_print() {
        global $CFG;
        // This is necessary to get out of the existing form so that we can use an mform
        print '</form>';
        include(dirname(__FILE__).'/form.php');
        $mform = new ousearch_form($CFG->wwwroot . '/course/view.php');
        $inputdata = new stdClass;
        $inputdata->id = $this->instance->pageid;
        $inputdata->instanceid = $this->instance->id;
        $inputdata->blockaction = 'config';
        $inputdata->searchtype = $this->config ? $this->config->searchtype : 'choose';
        $mform->set_data($inputdata);
        $mform->display();
        print '<form action="." method="post">';
        return true;
    }

}

?>
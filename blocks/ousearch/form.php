<?php
/**
 * Form for setting ousearch
 *
 * @copyright &copy; 2010 The Open University
 * @author m.kassaei@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ousearch
 */

require_once("$CFG->libdir/formslib.php");

class ousearch_form extends moodleform {

    function definition() {
        global $CFG;
        $mform = $this->_form;
        $mform->addElement('header','choicesheader', get_string('searchtypeheader', 'block_ousearch'));
        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'instanceid');
        $mform->addElement('hidden', 'blockaction');

        //Setup the default options
        $options = array(
                'choose' => get_string('choose', 'block_ousearch'),
                'multiactivity' => get_string('multiactivity', 'block_ousearch'));

        //Add to the options if the module exists
        if ($this->module_exists('forumng')) {
            $options['forumng'] = get_string('forumng', 'block_ousearch');
        }

        $mform->addElement('select', 'searchtype', get_string('searchtype', 'block_ousearch'), $options);

        $this->add_action_buttons(false);
    }

    private function module_exists($modtable) {
        global $COURSE;
        return record_exists($modtable, 'course', $COURSE->id);
    }
}
?>
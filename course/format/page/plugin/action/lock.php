<?php
/**
 * Page lock editing interface
 *
 * @author Mark Nielsen
 * @version $Id: lock.php,v 1.1 2009/12/21 01:00:30 michaelpenne Exp $
 * @package format_page
 **/

require_once($CFG->dirroot.'/course/format/page/plugin/action.php');
require_once($CFG->dirroot.'/course/format/page/plugin/action/form/lock.php');
require_once($CFG->dirroot.'/course/format/page/plugin/lock.php');

class format_page_action_lock extends format_page_action {

    function display() {
        global $CFG, $PAGE;

        require_capability('format/page:managepages', $this->context);

        $locks = format_page_lock::get_locks();

        $mform = new format_page_lock_form(
            $CFG->wwwroot.'/course/format/page/format.php',
            format_page_lock::decode($this->page->locks)
        );

        if ($mform->is_cancelled()) {
            redirect($PAGE->url_build('action', 'manage'));

        } else if ($data = $mform->get_data()) {
            $lockdata = array();
            foreach ($locks as $lock) {
                $lockdata = array_merge($lockdata, $lock->process_form($data));
            }

            $lockinfo                = array();
            $lockinfo['showprereqs'] = $data->showprereqs;
            $lockinfo['visible']     = $data->visible;
            $lockinfo['locks']       = $lockdata;

            if (empty($lockinfo['locks'])) {
                $lockinfo = '';
            } else {
                $lockinfo = format_page_lock::encode($lockinfo);
            }
            if (!set_field('format_page', 'locks', $lockinfo, 'id', $this->page->id)) {
                error('Failed to save lock information');
            }
            redirect($PAGE->url_build('page', $this->page->id, 'action', 'lock'));

        } else {
            $PAGE->print_tabs('manage');
            $mform->display();
        }
    }
}

?>
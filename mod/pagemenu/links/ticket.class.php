<?php
/**
 * Ticket class definition
 *
 * @author Mark Nielsen
 * @version $Id: ticket.class.php,v 1.1 2009/12/21 01:01:27 michaelpenne Exp $
 * @package pagemenu
 **/

/**
 * Ticket Class Definition - defines
 * properties for a trouble ticket link
 */
class mod_pagemenu_link_ticket extends mod_pagemenu_link {

    public function get_data_names() {
        return array('ticketname', 'ticketsubject');
    }

    public function edit_form_add(&$mform) {
        $mform->addElement('text', 'ticketname', get_string('ticketname', 'pagemenu'), array('size'=>'47'));
        $mform->setType('ticketname', PARAM_TEXT);
        $mform->addElement('text', 'ticketsubject', get_string('ticketsubject', 'pagemenu'), array('size'=>'47'));
        $mform->setType('ticketsubject', PARAM_TEXT);
    }

    /**
     * Override because ticketsubject is optional
     *
     * @return void
     **/
    public function save($data) {
        if (!empty($data->ticketname)) {
            if (!empty($data->linkid)) {
                $linkid = $data->linkid;
            } else {
                $linkid = $this->add_new_link($data->a);
            }
            $this->save_data($linkid, 'ticketname', $data->ticketname);

            if (!empty($data->ticketsubject)) {
                $this->save_data($linkid, 'ticketsubject', $data->ticketsubject);
            } else {
                $this->save_data($linkid, 'ticketsubject', '');
            }
        }
    }

    public function is_enabled() {
        return record_exists('block', 'name', 'trouble_ticket');
    }

    public function get_menuitem($editing = false, $descend = false) {
        global $CFG, $COURSE;

        if (empty($this->link->id) or empty($this->config->ticketname)) {
            return false;
        }
        // Subject is optional
        if (empty($this->config->ticketsubject)) {
            $this->config->ticketsubject = '';
        }

        $menuitem         = $this->get_blank_menuitem();
        $menuitem->title  = format_string($this->config->ticketname);
        $menuitem->url    = "$CFG->wwwroot/blocks/trouble_ticket/ticket.php?id=$COURSE->id&amp;subject=".urlencode($this->config->ticketsubject);
        $menuitem->active = $this->is_active($menuitem->url);

        return $menuitem;
    }
}
?>
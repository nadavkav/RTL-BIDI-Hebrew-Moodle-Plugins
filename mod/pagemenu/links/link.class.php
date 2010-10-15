<?php
/**
 * Link class definition
 *
 * @author Mark Nielsen
 * @version $Id: link.class.php,v 1.1 2009/12/21 01:01:27 michaelpenne Exp $
 * @package pagemenu
 **/

/**
 * Link Class Definition - defines
 * properties for a regular HTML Link
 */
class mod_pagemenu_link_link extends mod_pagemenu_link {

    public function get_data_names() {
        return array('linkname', 'linkurl');
    }

    public function edit_form_add(&$mform) {
        $mform->addElement('text', 'linkname', get_string('linkname', 'pagemenu'), array('size'=>'47'));
        $mform->setType('linkname', PARAM_TEXT);
        $mform->addElement('text', 'linkurl', get_string('linkurl', 'pagemenu'), array('size'=>'47'));
        $mform->setType('linkurl', PARAM_TEXT);
    }

    public function get_menuitem($editing = false, $descend = false) {
        if (empty($this->link->id) or empty($this->config->linkname) or empty($this->config->linkurl)) {
            return false;
        }

        $menuitem         = $this->get_blank_menuitem();
        $options = null;
        $options->para = false; // don't add <p> tags they mess up styling
        $menuitem->title  = format_text($this->config->linkname,FORMAT_MOODLE,$options);
        $menuitem->url    = $this->config->linkurl;
        $menuitem->active = $this->is_active($this->config->linkurl);

        return $menuitem;
    }

    public static function restore_data($data, $restore) {
        $linknamestatus = $linkurlstatus = false;

        foreach ($data as $datum) {
            switch ($datum->name) {
                case 'linkname':
                    // We just want to know that it is there
                    $linknamestatus = true;
                    break;
                case 'linkurl':
                    $content = $datum->value;
                    $result  = restore_decode_content_links_worker($content, $restore);
                    if ($result != $content) {
                        $datum->value = addslashes($result);
                        if (debugging() and !defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                        $linkurlstatus = update_record('pagemenu_link_data', $datum);
                    } else {
                        $linkurlstatus = true;
                    }
                    break;
                default:
                    debugging('Deleting unknown data type: '.$datum->name);
                    // Not recognized
                    delete_records('pagemenu_link_data', 'id', $datum->id);
                    break;
            }
        }

        return ($linkurlstatus and $linknamestatus);
    }
}
?>
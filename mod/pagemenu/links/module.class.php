<?php
/**
 * Link class definition
 *
 * @author Mark Nielsen
 * @version $Id: module.class.php,v 1.1 2009/12/21 01:01:27 michaelpenne Exp $
 * @package pagemenu
 **/

/**
 * Link Class Definition - defines
 * properties for link to a module
 */
class mod_pagemenu_link_module extends mod_pagemenu_link {

    public function get_data_names() {
        return array('moduleid');
    }

    public function edit_form_add(&$mform) {
        global $COURSE, $CFG;

        require_once($CFG->dirroot.'/course/lib.php');

        $modinfo = get_fast_modinfo($COURSE);

        $modules = array();
        foreach ($modinfo->cms as $cm) {
            $modules[$cm->modplural][$cm->id] = shorten_text(format_string($cm->name, true,  $COURSE->id), 28);
        }

        // Fix the sorting
        $options  = array();
        $modnames = array_keys($modules);
        natcasesort($modnames);
        foreach ($modnames as $modname) {
            $mods = $modules[$modname];
            natcasesort($mods);

            $options[$modname] = $mods;
        }

        // Add our choose option to the front
        $options = array('' => array(0 => get_string('choose', 'pagemenu'))) + $options;

        $mform->addElement('selectgroups', 'moduleid', get_string('addmodule', 'pagemenu'), $options);
        $mform->setType('moduleid', PARAM_INT);
    }

    public function get_menuitem($editing = false, $descend = false) {
        global $CFG, $COURSE;

        if (empty($this->link->id) or empty($this->config->moduleid)) {
            return false;
        }

        $modinfo = get_fast_modinfo($COURSE);

        if (!array_key_exists($this->config->moduleid, $modinfo->cms)) {
            return false;
        }
        $cm = $modinfo->cms[$this->config->moduleid];

        if ($cm->uservisible) {

            $menuitem         = $this->get_blank_menuitem();
            $menuitem->title  = format_string($cm->name, true, $cm->course);
            $menuitem->url    = "$CFG->wwwroot/mod/$cm->modname/view.php?id=$cm->id";
            $menuitem->active = $this->is_active($menuitem->url);

            if (!$cm->visible) {
                $menuitem->class .= ' dimmed';
            }

            return $menuitem;
        }

        return false;
    }

    public static function restore_data($data, $restore) {
        $status = false;

        foreach ($data as $datum) {
            switch ($datum->name) {
                case 'moduleid':
                    // Relink module ID
                    $newid = backup_getid($restore->backup_unique_code, 'course_modules', $datum->value);
                    if (isset($newid->new_id)) {
                        $datum->value = $newid->new_id;
                        $status = update_record('pagemenu_link_data', $datum);
                    }
                    break;
                default:
                    debugging('Deleting unknown data type: '.$datum->name);
                    // Not recognized
                    delete_records('pagemenu_link_data', 'id', $datum->id);
                    break;
            }
        }

        return $status;
    }
}

?>
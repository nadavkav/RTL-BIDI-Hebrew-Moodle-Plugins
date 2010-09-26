<?php
/**
 * Access item lock - the lock is based
 * on activity logs - if one exists for
 * an activity then the lock is satisfied
 *
 * @author Mark Nielsen
 * @version $Id: access.php,v 1.1 2009/12/21 01:00:30 michaelpenne Exp $
 * @package format_page
 **/

require_once($CFG->dirroot.'/course/format/page/plugin/lock.php');

class format_page_lock_access extends format_page_lock {
    protected function restore_hook($restore, $lock) {
        if (!empty($lock['cmid'])) {
            // Get the new course module ID
            $cmid = backup_getid($restore->backup_unique_code, 'course_modules', $lock['cmid']);

            if ($cmid and !empty($cmid->new_id)) {
                $lock['cmid'] = $cmid->new_id;

                return $lock;
            }
        }
        return false;
    }

    public function add_form(&$mform, $locks) {
        global $COURSE;

        $optgrps = array('' => array(0 => get_string('choose')));

        // Need to filter out modules have have already been added
        $modules = page_get_modules($COURSE, 'name');
        foreach ($modules as $modnameplural => $instances) {
            foreach ($instances as $cmid => $name) {
                foreach ($locks as $lock) {
                    if ($lock['type'] == 'access' and $lock['cmid'] == $cmid) {
                        continue 2;
                    }
                }
                $optgrps[$modnameplural][$cmid] = $name;
            }
        }

        if (count($optgrps) == 1) {
            $mform->addElement('static', 'addaccessnone', '', get_string('activitiesfound', 'format_page'));
        } else {
            $mform->addElement('selectgroups', 'addaccess', get_string('activityaccessed', 'format_page'), $optgrps);
            $mform->setHelpButton('addaccess', array('accesslock', get_string('activityaccessed', 'format_page'), 'format_page'));
            $mform->setDefault('addaccess', 0);
            $mform->setType('addaccess', PARAM_INT);
        }
    }

    public function edit_form(&$mform, $locks) {
        global $COURSE;

        $modinfo = get_fast_modinfo($COURSE);
        foreach ($locks as $lock) {
            if ($lock['type'] == 'access' and isset($modinfo->cms[$lock['cmid']])) {
                $cm   = $modinfo->cms[$lock['cmid']];
                $name = format_string($cm->name);

                $mform->addElement('header', 'access_'.$lock['cmid'].'_header', get_string('activityaccesslockx', 'format_page', $name));

                $fieldname = 'access['.$lock['cmid'].'][cmid]';

                $mform->addElement('hidden', $fieldname, $lock['cmid']);
                $mform->setType($fieldname, PARAM_INT);

                $mform->addElement('checkbox', 'access['.$lock['cmid'].'][delete]', get_string('removelock', 'format_page'));
            }
        }
    }

    public function process_form($data) {
        $locks = array();

        // Process the edit_form() section
        if (!empty($data->access)) {
            foreach ($data->access as $cmid => $info) {
                if (!isset($info['delete'])) {
                    $locks[] = array(
                        'type'  => 'access',
                        'cmid'  => $info['cmid']
                    );
                }
            }
        }

        // Process the add_form() section
        if (!empty($data->addaccess)) {
            $locks[] = array(
                'type'  => 'access',
                'cmid'  => $data->addaccess
            );
        }

        return $locks;
    }

    public function locked($lock) {
        global $COURSE, $USER;

        static $modinfo;

        if (empty($modinfo)) {
             $modinfo = get_fast_modinfo($COURSE);
        }

        if ($lock['type'] == 'access' and isset($lock['cmid']) and isset($modinfo->cms[$lock['cmid']])) {

            $cm = $modinfo->cms[$lock['cmid']];

            // Primary check - course module ID
            if (record_exists('log', 'userid', $USER->id, 'module', $cm->modname, 'cmid', $cm->id)) {
                return false;
            }
            // Secondary check - instance ID
            if (record_exists('log', 'userid', $USER->id, 'module', $cm->modname, 'info', $cm->instance)) {
                return false;
            }
            // Failed to find log to confirm access
            return true;
        }
        // Lock is probably bad/outdated - don't lock
        return false;
    }

    public function get_prerequisite($lock) {
        global $CFG, $COURSE;

        static $modinfo;

        if (empty($modinfo)) {
             $modinfo = get_fast_modinfo($COURSE);
        }

        if (isset($modinfo->cms[$lock['cmid']])) {
            $cm = $modinfo->cms[$lock['cmid']];
            $a  = "<a href=\"$CFG->wwwroot/mod/$cm->modname/view.php?id=$cm->id\">".format_string($cm->name).'</a>';

            return get_string('accessprereq', 'format_page', $a);
        }
    }
}

?>
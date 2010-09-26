<?php
/**
 * Graded item lock - currently only does activity
 * graded items
 *
 * @author Mark Nielsen
 * @version $Id: grade.php,v 1.1 2009/12/21 01:00:30 michaelpenne Exp $
 * @package format_page
 **/

require_once($CFG->dirroot.'/course/format/page/plugin/lock.php');
require_once($CFG->libdir.'/gradelib.php');

class format_page_lock_grade extends format_page_lock {
    protected function restore_hook($restore, $lock) {
        if (!empty($lock['id'])) {
            $item = backup_getid($restore->backup_unique_code,'grade_items',$lock['id']);

            if ($item and !empty($item->new_id)) {
                $lock['id'] = $item->new_id;

                return $lock;
            }
        }
        return false;
    }

    public function add_form(&$mform, $locks) {
        global $COURSE;

        $items = grade_item::fetch_all(array('itemtype'=>'mod', 'courseid'=>$COURSE->id));

        $optgrps = array('' => array(0 => get_string('choose')));
        if (!empty($items)) {
            foreach ($items as $item) {
                // Exclude options for locks already added
                foreach ($locks as $lock) {
                    if ($lock['type'] == 'grade' and $lock['id'] == $item->id) {
                        continue 2;
                    }
                }

                $modname = get_string('modulename', $item->itemmodule);

                $optgrps[$modname][$item->id] = $this->get_grade_item_name($item);
            }
        }

        if (count($optgrps) == 1) {
            $mform->addElement('static', 'addgradeditemnone', '', get_string('nogradeditemsfound', 'format_page'));
        } else {
            $mform->addElement('selectgroups', 'addgradeditem', get_string('activitygradeditem', 'format_page'), $optgrps);
            $mform->setHelpButton('addgradeditem', array('gradelock', get_string('activitygradeditem', 'format_page'), 'format_page'));
            $mform->setDefault('addgradeditem', 0);
            $mform->setType('addgradeditem', PARAM_INT);
        }
    }

    public function edit_form(&$mform, $locks) {
        global $COURSE;

        foreach ($locks as $lock) {
            if ($lock['type'] == 'grade' and $item = $this->get_grade_item($lock['id'])) {
                $name = $this->get_grade_item_name($item);

                $mform->addElement('header', 'grade_'.$lock['id'].'_header', get_string('activitygradelockx', 'format_page', $name));

                $fieldname = 'grade['.$lock['id'].'][grade]';
                $maxgrade  = grade_format_gradevalue($item->grademax, $item);

                $gradegroup   =  array();
                $gradegroup[] =& $mform->createElement('text', $fieldname, get_string('requiredgrade', 'format_page'), array('size'=>'5'));
                $gradegroup[] =& $mform->createElement('static', "{$fieldname}_maxgrade", '', get_string('maxgradex', 'format_page', $maxgrade));
                $mform->addGroup($gradegroup, "group$fieldname", get_string('requiredgrade', 'format_page'), ' ', false);
                $mform->setHelpButton("group$fieldname", array('reqgrade', get_string('requiredgrade', 'format_page'), 'format_page'));

                $mform->setDefault($fieldname, $lock['grade']);

                $mform->addElement('checkbox', 'grade['.$lock['id'].'][delete]', get_string('removelock', 'format_page'));
            }
        }
    }

    public function process_form($data) {
        $locks = array();

        // Process the edit_form() section
        if (!empty($data->grade)) {
            foreach ($data->grade as $itemid => $info) {
                if (!isset($info['delete'])) {
                    if ($item = $this->get_grade_item($itemid)) {

                        $grades = explode(':', $info['grade']);

                        foreach ($grades as $key => $grade) {
                            $grade = clean_param($grade, PARAM_NUMBER);

                            if ($grade < 0) {
                                $grade = 0;
                            } else if ($grade > $item->grademax) {
                                $grade = clean_param($item->grademax, PARAM_NUMBER);
                            }
                            $grades[$key] = $grade;
                        }
                        if (count($grades) == 2 and $grades[0] >= $grades[1]) {
                            $grades = array($grades[1]);
                        }
                        $grade = implode(':', $grades);

                        $locks[] = array(
                            'type'  => 'grade',
                            'id'    => $item->id,
                            'grade' => $grade
                        );
                    }
                }
            }
        }

        // Process the add_form() section
        if (!empty($data->addgradeditem)) {
            $locks[] = array(
                'type'  => 'grade',
                'id'    => $data->addgradeditem,
                'grade' => 0
            );
        }

        return $locks;
    }

    public function locked($lock) {
        global $COURSE, $USER;

        static $grades;

        if ($lock['type'] == 'grade' and isset($lock['id']) and isset($lock['grade'])) {
            if (!isset($grades)) {
                $grades = grade_grade::fetch_all(array('userid' => $USER->id));
            }
            if (!empty($grades)) {
                $lockgrades = explode(':', $lock['grade']);

                foreach ($grades as $grade) {
                    if ($grade->itemid == $lock['id']) {
                        if (count($lockgrades) == 2) {
                            if ($grade->finalgrade >= $lockgrades[0] and $grade->finalgrade <= $lockgrades[1]) {
                                // Passed criteria for ranged lock - don't lock
                                return false;
                            }
                        } else if ($grade->finalgrade >= $lockgrades[0]) {
                            // Passed criteria for lock - don't lock
                            return false;
                        }
                    }
                }
            }
            // Very important - may not have a grade record yet.
            // No grade record == locked
            return true;
        }
        // Lock is probably bad/outdated - don't lock
        return false;
    }

    public function get_prerequisite($lock) {
        global $CFG;

        if ($item = $this->get_grade_item($lock['id'])) {
            $grades = explode(':', $lock['grade']);

            $a = new stdClass;
            $a->name = $this->get_grade_item_name($item);

            if ($cm = get_coursemodule_from_instance($item->itemmodule, $item->iteminstance, $item->courseid)) {
                $a->name = "<a href=\"$CFG->wwwroot/mod/$item->itemmodule/view.php?id=$cm->id\">$a->name</a>";
            }

            if (count($grades) == 2) {
                $a->lowgrade  = $grades[0];
                $a->highgrade = $grades[1];

                return get_string('graderangedprereq', 'format_page', $a);
            } else {
                $a->grade = $grades[0];

                return get_string('gradeprereq', 'format_page', $a);
            }
        }
    }

    /**
     * Gets a graded item
     *
     * @param int $id Graded item ID
     * @return mixed
     **/
    protected function get_grade_item($id) {
        global $COURSE;

        $items = grade_item::fetch_all(array('id' => $id, 'courseid' => $COURSE->id));

        if (!empty($items) and count($items) == 1) {
            return current($items);
        } else {
            return false;
        }
    }

    /**
     * Generates a unique name for a grade item
     *
     * @param object $item The graded item
     * @return string
     **/
    protected function get_grade_item_name($item) {
        // Default
        $name = format_string($item->itemname);

        if ($item->itemnumber > 0) {
            if ($instancename = get_field($item->itemmodule, 'name', 'id', $item->iteminstance)) {
                $name = "$instancename: $name";
            }
        }
        return $name;
    }
}

?>
<?php
require_once ($CFG->dirroot . '/lib/formslib.php');

class mod_ouwiki_annotate_form extends moodleform {

    function definition() {

        global $CFG, $COURSE;
        $mform =& $this->_form;
        $annotations = $this->_customdata[0];
        $pageid = $this->_customdata[1]->pageid;
        $pagename = $this->_customdata[2];
        $currentuserid = $this->_customdata[3];
        $orphaned = false;

        $mform->addElement('hidden', 'page', $pagename);
        $mform->addElement('hidden', 'user', $currentuserid);
        $mform->addElement('header', 'annotations', get_string('annotations','ouwiki'));

        if (count($annotations != 0)) {
            usort($annotations, array("mod_ouwiki_annotate_form","ouwiki_internal_position_sort"));
            $editnumber = 1;
            foreach($annotations as $annotation) {
                if(!$annotation->orphaned) {
                    $mform->addElement('textarea', 'edit'.$annotation->id, $editnumber, array('cols'=>'40', 'rows'=>'3'));
                    $mform->setDefault('edit'.$annotation->id,$annotation->content);
                    $editnumber++;
                } else {
                    $orphaned = true;
                }
            }
        }

        // only display this checkbox if there are orphaned annotations
        if ($orphaned) {
            $mform->addElement('checkbox', 'deleteorphaned', get_string('deleteorphanedannotations','ouwiki'));
        }
        $mform->addElement('checkbox', 'lockediting', get_string('lockediting','ouwiki'));

        if (ouwiki_is_page_editing_locked($pageid)) {
            $mform->setDefault('lockediting',true);
        } else {
            $mform->setDefault('lockediting',false);
        }
        $this->add_action_buttons();
     }

    private function ouwiki_internal_position_sort($a, $b) {
        return intval($a->position) - intval($b->position); 
    }
}
?>

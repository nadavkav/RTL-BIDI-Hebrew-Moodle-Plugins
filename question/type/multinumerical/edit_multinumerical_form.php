<?php  // $Id: edit_multinumerical_form.php,v 1.10.2.5 2009/02/19 01:09:33 tjhunt Exp $
/**
 * Defines the editing form for the multinumerical question type.
 *
 * @copyright &copy; 2007 Jamie Pratt
 * @author Jamie Pratt me@jamiep.org
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 */

/**
 * multinumerical editing form definition.
 */
class question_edit_multinumerical_form extends question_edit_form {
    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    function definition_inner(&$mform) {
        
        $mform->removeElement('image');
        
        $mform->addElement('static', 'help_multinumerical', get_string('help'), get_string('helponquestionoptions', 'qtype_multinumerical'));

        $mform->addElement('text', 'parameters', get_string('parameters', 'qtype_multinumerical'),
                array('size' => 30));

        $mform->addElement('textarea', 'conditions', get_string('conditions', 'qtype_multinumerical'),
                array('rows' => 5, 'cols' => 60, 'course' => $this->coursefilesid));

        $mform->addElement('textarea', 'feedbackperconditions', get_string('feedbackperconditions', 'qtype_multinumerical'),
                array('rows' => 5, 'cols' => 60, 'course' => $this->coursefilesid));

        $colorfboptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        $mform->addElement('select', 'usecolorforfeedback', get_string("usecolorforfeedback", "qtype_multinumerical"), $colorfboptions);
                
        $displaycalcoptions = array( 0 => get_string('no'), 1 => get_string('yes'), 2 => get_string('onlyforcalculations', 'qtype_multinumerical'));
        $mform->addElement('select', 'displaycalc', get_string("displaycalc", "qtype_multinumerical"), $displaycalcoptions);

        $binarygradeoptions = array( 0 => get_string('gradefractional', 'qtype_multinumerical'), 1 => get_string('gradebinary', 'qtype_multinumerical'));
        $mform->addElement('select', 'binarygrade', get_string("binarygrade", "qtype_multinumerical"), $binarygradeoptions);
        
        $creategrades = get_grade_options();
    }

    function validation($data, $files) {
    	$errors = array();
        return $errors;
    }

    function qtype() {
        return 'multinumerical';
    }
}
?>
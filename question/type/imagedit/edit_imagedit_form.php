<?php
/**
 * Defines the editing form for the imagedit question type.
 *
 * @author Nadav Kavalerchik
 * based on the work of : Adriane Boyd (adrianeboyd@gmail.com) with fileresponse
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package aab_imagedit
 */
class question_edit_imagedit_form extends question_edit_form {
    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    function definition_inner(&$mform) {
        global $COURSE, $CFG;

        // don't need these default elements :
        $mform->removeElement('defaultgrade');
        $mform->removeElement('penalty');

        $mform->addElement('html', '<a target="_new" href="'.$CFG->wwwroot.'/files/index.php?id='.$COURSE->id.'">'.get_string("openinnewwindow","qtype_imagedit").'</a>');

        // this element will hold the URL of the HTML's IMG SRC value
        // of the altered image file that was uploaded by the user
        // to the current course's "users" folder
        //$mform->addElement('hidden', 'imgurl', '');

        // add feedback
        $mform->addElement('htmleditor', 'feedback', get_string("feedback", "quiz"));
        $mform->setType('feedback', PARAM_RAW);

        // add default elements
        $mform->addElement('hidden', 'defaultgrade', 0);
        $mform->addElement('hidden', 'fraction', 0);

        // add max upload limit menu
        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $mform->addElement('select', 'maxbytes', get_string('maximumupload'), $choices);
        $mform->setDefault('maxbytes', $COURSE->maxbytes);

        // add essay area checkbox
        $mform->addElement('advcheckbox', 'essay', get_string('addessay', 'qtype_imagedit'), null, null, array(0,1));

    }

    function set_data($question) {
        if (!empty($question->options)){
            $question->essay = $question->options->essay;
            $question->maxbytes = $question->options->maxbytes;
	    if(!empty($question->options->answers)) {
            	$answer = reset($question->options->answers);
            	$question->feedback = $answer->feedback;
	    }
        }
        $question->penalty = 0;
        parent::set_data($question);
    }


    function qtype() {
        return 'imagedit';
    }


}
?>

<?php
/**
 * Defines the editing form for the order question type.
 * (based on the match question edit order form by Jamie Pratt)
 *
 * @copyright &copy; 2007 Jamie Pratt, Adriane Boyd
 * @author Jamie Pratt me@jamiep.org, Adriane Boyd adrianeboyd@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package aab_order
 */

/**
 * order editing form definition.
 */
class question_edit_order_form extends question_edit_form {
    /**
     * Add question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    function definition_inner(&$mform) {
        $mform->addElement('static', 'answersinstruct', get_string('choices', 'quiz'), get_string('filloutthreeitems', 'qtype_order'));
        $mform->closeHeaderBefore('answersinstruct');

	    $mform->addElement('advcheckbox', 'horizontal', get_string('horizontal', 'qtype_order'), null, null, array(0,1));

        $repeated = array();
        $repeated[] =& $mform->createElement('header', 'choicehdr', get_string('itemno', 'qtype_order', '{no}'));
        // change elements to support htmleditor (nadavkav)
        $repeated[] =& $mform->createElement('htmleditor', 'subquestions', '', array('cols'=>40, 'rows'=>13));

        if (isset($this->question->options)){
            $countsubquestions = count($this->question->options->subquestions);
        } else {
            $countsubquestions = 0;
        }

        $repeatsatstart = (QUESTION_NUMANS_START > ($countsubquestions + QUESTION_NUMANS_ADD))?
                            QUESTION_NUMANS_START : ($countsubquestions + QUESTION_NUMANS_ADD);
        $mform->setType('subquestion', PARAM_TEXT);

        $this->repeat_elements($repeated, $repeatsatstart, array(), 'noanswers', 'addanswers', QUESTION_NUMANS_ADD, get_string('addmoreqblanks', 'qtype_order'));

        $repeats = optional_param('noanswers', '', PARAM_INT);
        $addfields = optional_param('addanswers', '', PARAM_TEXT);
        if (!empty($addfields)){
            $repeats += QUESTION_NUMANS_ADD;
        }

        for ($count = 0; $count < $repeats; $count++) {
            $mform->addElement('hidden', 'subanswers['.$count.']', $count + 1);
        }
    }

    function set_data($question) {
        if (isset($question->options)){
            $subquestions = $question->options->subquestions;
            $question->horizontal = $question->options->horizontal;
            if (count($subquestions)) {
                $key = 0;
                foreach ($subquestions as $subquestion){
                    $default_values['subanswers['.$key.']'] = $subquestion->answertext;
                    $default_values['subquestions['.$key.']'] = $subquestion->questiontext;
                    $key++;
                }
            }
            $question = (object)((array)$question + $default_values);
        }
        parent::set_data($question);
    }

    function qtype() {
        return 'order';
    }

    function validation($data){
        $errors = array();
        $answers = $data['subanswers'];
        $questions = $data['subquestions'];
        $questioncount = 0;
        foreach ($questions as $key => $question){
            $trimmedquestion = trim($question);
            $trimmedanswer = trim($answers[$key]);
            if (!empty($trimmedanswer) && !empty($trimmedquestion)){
                $questioncount++;
            }
            if (!empty($trimmedquestion) && empty($trimmedanswer)){
                $errors['subanswers['.$key.']'] = get_string('nomatchinganswerforq', 'qtype_order', $trimmedquestion);
            }
        }
        if ($questioncount==0){
            $errors['subquestions[0]'] = get_string('notenoughquestions', 'qtype_order', 3);
            $errors['subquestions[1]'] = get_string('notenoughquestions', 'qtype_order', 3);
            $errors['subquestions[2]'] = get_string('notenoughquestions', 'qtype_order', 3);
        } elseif ($questioncount==1){
            $errors['subquestions[1]'] = get_string('notenoughquestions', 'qtype_order', 3);
            $errors['subquestions[2]'] = get_string('notenoughquestions', 'qtype_order', 3);

        } elseif ($questioncount==2){
            $errors['subquestions[2]'] = get_string('notenoughquestions', 'qtype_order', 3);
        }
        return $errors;
    }
}
?>

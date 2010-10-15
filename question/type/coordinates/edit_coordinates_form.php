<?php
/**
 * Defines the editing form for the coordinates question type.
 *
 * @copyright &copy; 2010 Hon Wai, Lau
 * @author Hon Wai, Lau <lau65536@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * @package questionbank
 * @subpackage questiontypes
 */

require_once($CFG->dirroot.'/question/type/edit_question_form.php');

/**
 * coodinate question type editing form definition.
 */
class question_edit_coordinates_form extends question_edit_form {
    
    /**
    * Add question-type specific form fields.
    *
    * @param MoodleQuickForm $mform the form being built.
    */
    function definition_inner(&$mform) {
        $mform->addElement('header','globalvarshdr',get_string('globalvarshdr','qtype_coordinates'));

        $mform->removeElement('defaultgrade');        
        $mform->addElement('hidden', 'defaultgrade');        
        $mform->setType('defaultgrade', PARAM_RAW);
        
        $mform->removeElement('penalty');        
        $mform->addElement('hidden', 'penalty');        
        $mform->setType('penalty', PARAM_NUMBER);
        $mform->setDefault('penalty', 0.1);
        
        $mform->addElement('static', 'help_coordinates', get_string('help'),
            get_string('helponquestionoptions', 'qtype_coordinates'));
            
        $mform->addElement('textarea', 'varsrandom', get_string('varsrandom', 'qtype_coordinates'),
            array('rows' => 4, 'cols' => 70, 'course' => $this->coursefilesid));
            
        $mform->addElement('textarea', 'varsglobal', get_string('varsglobal', 'qtype_coordinates'),
            array('rows' => 6, 'cols' => 70, 'course' => $this->coursefilesid));
        
        $mform->addElement('select', 'showperanswermark', get_string('showperanswermark', 'qtype_coordinates'),
            array(get_string('choiceno', 'qtype_coordinates'), get_string('choiceyes', 'qtype_coordinates')));
        $mform->setDefault('showperanswermark', 1);
        
        $mform->addElement('select', 'peranswersubmit', get_string('peranswersubmit', 'qtype_coordinates'),
            array(get_string('choiceno', 'qtype_coordinates'), get_string('choiceyes', 'qtype_coordinates')));
        $mform->setDefault('peranswersubmit', 1);
        
        $mform->addElement('text', 'retrymarkseq', get_string('retrymarkseq', 'qtype_coordinates'),
            array('size' => 30));
        
        $show_group=array();
        $show_group[] =& $mform->createElement('checkbox','vars2','',get_string('vars2','qtype_coordinates'),
            'onclick="coordinates_form_display(\'vars2\', this.checked)"');
        $show_group[] =& $mform->createElement('checkbox','preunit','',get_string('preunit','qtype_coordinates'),
            'onclick="coordinates_form_display(\'preunit\', this.checked)"');
        $show_group[] =& $mform->createElement('checkbox','otherrule','',get_string('otherrule','qtype_coordinates'),
            'onclick="coordinates_form_display(\'otherrule\', this.checked)"');
        //$show_group[] =& $mform->createElement('checkbox','subqtext','',get_string('subqtext','qtype_coordinates'),
        //    'onclick="coordinates_form_display(\'subqtext\', this.checked)"');
        //$show_group[] =& $mform->createElement('checkbox','feedback','',get_string('feedback','qtype_coordinates'),
        //    'onclick="coordinates_form_display(\'feedback\', this.checked)"');
        $show_group[] =& $mform->createElement('checkbox','correctnessraw','',get_string('correctnessraw','qtype_coordinates'),
            'onclick="coordinates_form_correctness(this.checked)"');
        $mform->addGroup($show_group,'showoptions',get_string('showoptions','qtype_coordinates'),array(' '),true);
        
        $creategrades = get_grade_options();
        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_coordinates', '{no}'),
            $creategrades->gradeoptions, 1, 1);
    }
    
    
    /**
    * Add the answer field for a particular subquestion labelled by placeholder.
    * 
    * @param MoodleQuickForm $mform the form being built.
    */
    function get_per_answer_fields(&$mform, $label, $gradeoptions, &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $repeated[] =& $mform->createElement('header', 'answerhdr', $label);
        
        $repeated[] =& $mform->createElement('text', 'answermark', get_string('answermark', 'qtype_coordinates'),
            array('size' => 3));
        $repeated[] =& $mform->createElement('text', 'placeholder', get_string('placeholder', 'qtype_coordinates'),
            array('size' => 60));
        $repeated[] =& $mform->createElement('textarea', 'vars1', get_string('vars1', 'qtype_coordinates'),
            array('rows' => 5, 'cols' => 70, 'course' => $this->coursefilesid));
        $repeated[] =& $mform->createElement('text', 'answer', get_string('answer', 'qtype_coordinates'),
            array('size' => 60));
        $repeated[] =& $mform->createElement('textarea', 'vars2', get_string('vars2', 'qtype_coordinates'),
            array('rows' => 5, 'cols' => 70, 'course' => $this->coursefilesid));
        $repeated[] =& $mform->createElement('text', 'correctness', get_string('correctness', 'qtype_coordinates'),
            array('size' => 60));
        
        $repeated[] =& $mform->createElement('text', 'unitpenalty', get_string('unitpenalty', 'qtype_coordinates'),
            array('size' => 3));
        $repeatedoptions['unitpenalty']['default'] = 0.2;
        $repeated[] =& $mform->createElement('text', 'preunit', get_string('preunit', 'qtype_coordinates'),
            array('size' => 60));
        $repeated[] =& $mform->createElement('text', 'postunit', get_string('postunit', 'qtype_coordinates'),
            array('size' => 60));
        
        global $basic_unit_conversion_rules;
        foreach ($basic_unit_conversion_rules as $id => $entry)  $default_rule_choice[$id] = $entry[0];
        $repeated[] =& $mform->createElement('select', 'ruleid', get_string('ruleid', 'qtype_coordinates'),
            $default_rule_choice);
        $repeatedoptions['ruleid']['default'] = 1;
        $repeated[] =& $mform->createElement('textarea', 'otherrule', get_string('otherrule', 'qtype_coordinates'),
            array('rows' => 2, 'cols' => 70, 'course' => $this->coursefilesid));
        
        //$repeated[] =& $mform->createElement('htmleditor', 'subqtext', get_string('subqtext', 'qtype_coordinates'),
        //    array('course' => $this->coursefilesid));
        //$repeated[] =& $mform->createElement('textarea', 'feedback', get_string('feedback', 'qtype_coordinates'),
        //    array('rows' => 5, 'cols' => 70, 'course' => $this->coursefilesid));
        
        $answersoption = 'answers';
        return $repeated;
    }
    
    
    /**
    * Sets the existing values into the form for the question specific data.
    * It sets the answers before calling the parent function.
    *
    * @param $question the question object from the database being used to fill the form
    */
    function set_data($question) {
        if (isset($question->options)){
            global $QTYPES;
            $extras = $QTYPES[$this->qtype()]->subquestion_option_extras();
            foreach ($extras as $extra)  $default_values[$extra] = $question->options->extra->$extra;
            $show_tags = array('vars2', 'preunit', 'otherrule', 'subqtext', 'feedback');
            foreach ($show_tags as $tag)  $is_show[$tag] = false;
            if (count($question->options->answers)) {
                foreach ($question->options->answers as $key => $answer) {
                    $tags = $QTYPES[$this->qtype()]->subquestion_answer_tags();
                    foreach ($tags as $tag)  $default_values[$tag.'['.$key.']'] = $answer->$tag;
                    foreach ($show_tags as $tag)  $is_show[$tag] = $is_show[$tag] || (strlen(trim($answer->$tag)) != 0);
                }
            }
            foreach ($show_tags as $tag)  if ($is_show[$tag])  $default_values['showoptions['.$tag.']'] = 1;
            $default_values['showoptions[correctnessraw]'] = 0;
            $question = (object)((array)$question + $default_values);
        }
        parent::set_data($question);
    }
    
    
    /**
    * Validating the data returning from the client.
    * 
    * The check the basic error as well as the formula error by evaluating one instantiation.
    */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        global $basic_unit_conversion_rules;
        global $QTYPES;
        $qt = & $QTYPES[$this->qtype()];
        $form = (object)$data;
        
        try {
            $tmpquestion->options->extra->retrymarkseq = $form->retrymarkseq;
            $qt->get_trial_mark_fraction(&$tmpquestion, 0);
        } catch (Exception $e) {
            $errors["retrymarkseq"] = $e->getMessage();
        }
        
        $validanswers = $qt->check_form_answers($form);
        if (isset($validanswers->error)) {
            $errors = $errors + $validanswers->errors;
            $validanswers = $validanswers->answers;
        }
        
        $res = $qt->get_subquestion_structure($form->questiontext, $validanswers);
        if (isset($res->errors))  $errors = $errors + $res->errors;
        
        try {
            $vars = new question_variables($form->varsrandom);   // checking random variables by instantiating one set
        } catch (Exception $e) {
            $errors["varsrandom"] = $e->getMessage();
            return $errors;
        }
        
        try {
            $vars->add_local_variables($form->varsglobal);
        } catch (Exception $e) {
            $errors["varsglobal"] = $e->getMessage();
            return $errors;
        }
        $globalvars = $vars->get_variables();
        
        /// Attempt to compute the answer so that it can see whether it is wrong
        foreach ($validanswers as $idx => $ans) {
            $unitcheck = new answer_unit_conversion;
            if ($ans->unitpenalty < 0 || $ans->unitpenalty > 1)
                $errors["unitpenalty[$idx]"] = get_string('error_unitpenalty','qtype_coordinates') . $e->getMessage();
            try {
                $unitcheck->parse_targets($ans->postunit);
            } catch (Exception $e) {
                $errors["postunit[$idx]"] = get_string('error_validation_parse_rule','qtype_coordinates') . $e->getMessage();
            }
            try {
                $unitcheck->parse_targets($ans->preunit);
            } catch (Exception $e) {
                $errors["preunit[$idx]"] = get_string('error_validation_parse_rule','qtype_coordinates') . $e->getMessage();
            }
            try {
                $unitcheck->assign_additional_rules($ans->otherrule);
                $unitcheck->reparse_all_rules();
            } catch (Exception $e) {
                $errors["otherrule[$idx]"] = get_string('error_validation_parse_rule','qtype_coordinates') . $e->getMessage();
            }
            try {
                $entry = $basic_unit_conversion_rules[$ans->ruleid];
                if ($entry === null || $entry[1] === null)  throw new Exception(get_string('error_validation_ruleid','qtype_coordinates'));
                $unitcheck->assign_default_rules($ans->ruleid, $entry[1]);
                $unitcheck->reparse_all_rules();
            } catch (Exception $e) {
                $errors["ruleid[$idx]"] = $e->getMessage();
            }
            try {
                $vars->add_local_variables($ans->vars1);
            } catch (Exception $e) {
                $errors["vars1[$idx]"] = get_string('error_validation_eval','qtype_coordinates') . $e->getMessage();
                continue;
            }
            try {
                $input = $qt->evaluate_answer($ans, $globalvars);
            } catch (Exception $e) {
                $errors["answer[$idx]"] = get_string('error_validation_eval','qtype_coordinates') . $e->getMessage();
                continue;
            }
            try {
                $qt->add_special_correctness_variables($vars, $input, $input);
                $vars->add_local_variables($ans->vars2);
            } catch (Exception $e) {
                $errors["vars2[$idx]"] = get_string('error_validation_eval','qtype_coordinates') . $e->getMessage();
                continue;
            }
            try {
                $correctness = $qt->grade_response_correctness($ans, $globalvars, '('.implode(',',$input->coordinates).')', $unitcheck);
            } catch (Exception $e) {
                $errors["correctness[$idx]"] = get_string('error_validation_eval','qtype_coordinates') . $e->getMessage();
                continue;
            }
        }
        
        return $errors;
    }


    function qtype() {
        return 'coordinates';
    }
}
?>

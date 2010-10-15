<?php  // $Id: questiontype.php,v 1.20.2.12 2009/03/16 01:52:24 tjhunt Exp $

//////////////////////
/// multinumerical ///
//////////////////////

/// QUESTION TYPE CLASS //////////////////

///
/// This class contains some special features in order to make the
/// question type embeddable within a multianswer (cloze) question
///
/**
 * @package questionbank
 * @subpackage questiontypes
 */
require_once("$CFG->dirroot/question/type/questiontype.php");

class question_multinumerical_qtype extends default_questiontype {

    function name() {
        return 'multinumerical';
    }

    function extra_question_fields() {
        return array('question_multinumerical','parameters','conditions','feedbackperconditions','binarygrade', 'displaycalc', 'usecolorforfeedback');
    }

    function questionid_column_name() {
        return 'question';
    }

    function save_question_options($question) {
        $result = new stdClass;
        $conditions = array();
        $conditions_uncleaned = explode("\r\n", $question->conditions);
        foreach ($conditions_uncleaned as $condition_uncleaned) {
        	if (strlen(trim($condition_uncleaned)) > 0) {
        		$conditions[] = trim($condition_uncleaned);
        	}
        }
        $question->conditions = implode("\r\n", $conditions);
        
        $parentresult = parent::save_question_options($question);
        if($parentresult !== null) { // Parent function returns null if all is OK
            return $parentresult;
        }
    }

    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        global $CFG;
    /// This implementation is also used by question type 'numerical'
        $readonly = empty($options->readonly) ? '' : 'readonly="readonly"';
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->para = false;
        $nameprefix = $question->name_prefix;

        /// Print question text and media
        $questiontext = format_text($question->questiontext,$question->questiontextformat,$formatoptions, $cmoptions->course);
        $image = get_question_image($question);

		$this->compute_feedbackperconditions($question, $state);

        /// Print input controls
        $values = array();
        $params = explode(",", $question->options->parameters);
        foreach ($params as &$param) {
        	$param = trim($param);
        	$paramparts = explode(" ", $param);
        	$paramname = trim($paramparts[0]);
        	$paramunity = trim($paramparts[1]); 
	        if (isset($state->responses[$paramname]) && $state->responses[$paramname] != '') {
	            $value[$paramname] = ' value="'.s($state->responses[$paramname], true).'" ';
	        } else {
	            $value[$paramname] = ' value="" ';
	        }
	        $inputname[$paramname] = ' name="'.$nameprefix.$paramname.'" ';
        }
        
        $feedback = '';
        $class = '';
        $feedbackimg = '';
        $feedbackperconditions = '';

        if ($options->feedback) {
            $class = question_get_feedback_class($state->raw_grade);
            $feedbackimg = question_get_feedback_image($state->raw_grade);
            $feedbackperconditions = $question->options->computedfeedbackperconditions;
        }

        include("$CFG->dirroot/question/type/multinumerical/display.html");
    }
    
    function grade_responses(&$question, &$state, $cmoptions) {
    	$score = $this->compute_feedbackperconditions($question, &$state);
       	$conditions = explode("\r\n", $question->options->conditions);
        $correctness = $score / sizeof($conditions);
        if ($question->options->binarygrade) {
            $correctness = floor($correctness);
        }
        $state->penalty = ($correctness < 1) ? ($question->penalty) : (0);
        $state->raw_grade = $correctness * $question->maxgrade;
        $state->grade = $state->raw_grade - ($state->penalty * $question->maxgrade);
        $state->grade = ($state->grade <= 0) ? (0) : ($state->grade);
		$state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;
    	return true;
    }
        
    function compute_feedbackperconditions(&$question, &$state) {
    	global $CFG;
    	$score = 0;
    	$feedbackperconditions = explode("\r\n", $question->options->feedbackperconditions);
    	$conditionsfullfilled = array();
    	$feedbackperconditions_computed = array();
    	$parameters = explode(',', $question->options->parameters);
    	$givenanswer = array();
    	foreach ($parameters as $param) {
    		$param = trim($param);
    		$paramparts = explode(" ", $param);
    		$param = $paramparts[0];
    		$givenanswer[$param] = optional_param("resp".$question->id."_".$param, $state->responses[$param]);
    	}
    	$question->givenanswer = $givenanswer;
    	//$SESSION->flextable['qtype-multinumerical'][$question->id]['givenanswers'] = $question->givenanswer; 
     	$conditions = explode("\r\n", $question->options->conditions);
    	foreach ($conditions as $conditionid => $condition) {
    		if (strlen(trim($condition)) == 0) {
    			unset($conditions[$conditionid]);
    			continue;
    		}
    		$feedbackforthiscondition = explode('|', $feedbackperconditions[$conditionid]);
    		$values = '';
    		if ($this->check_condition($condition, $parameters, &$values, $question)) {
    			$score++;
    			$conditionsfullfilled[] = 1;
    			if (strlen(trim($feedbackforthiscondition[0])) > 0) {
    				if ($question->options->usecolorforfeedback) {
	    				$feedbackperconditions_computed[$conditionid] = '<span style="color:#090">';
	    				$feedbackperconditions_computed[$conditionid] .= (preg_match('/(usepackage{color})/', $CFG->filter_tex_latexpreamble)) ? (preg_replace('/(.*)\$\$(.*)\$\$(.*)/', '${1}\$\$\\textcolor{green}{${2}}\$\$${3}', $feedbackforthiscondition[0])) : ($feedbackforthiscondition[0]);
	    				$feedbackperconditions_computed[$conditionid] .= '</span>';
    				}
    				else {
    					$feedbackperconditions_computed[$conditionid] = $feedbackforthiscondition[0];
    				}
     			}
    			else {
    				unset($feedbackperconditions[$conditionid]);
    			}
    		}
    		else {
    			$conditionsfullfilled[] = 0;
    			if (strlen(trim($feedbackforthiscondition[1])) > 0) {
    				if ($question->options->usecolorforfeedback) {
	    				$feedbackperconditions_computed[$conditionid] = '<span style="color:#f00">';
	    				$feedbackperconditions_computed[$conditionid] .= (preg_match('/(usepackage{color})/', $CFG->filter_tex_latexpreamble)) ? (preg_replace('/(.*)\$\$(.*)\$\$(.*)/', '${1}\$\$\\textcolor{red}{${2}}\$\$${3}', $feedbackforthiscondition[1])) : ($feedbackforthiscondition[1]);
	    				$feedbackperconditions_computed[$conditionid] .= '</span>';
    				}
    				else {
    					$feedbackperconditions_computed[$conditionid] = $feedbackforthiscondition[1];
    				}
    			}
    			else {
    				unset($feedbackperconditions[$conditionid]);
    			}
    		}
    		if ($question->options->displaycalc && $feedbackperconditions[$conditionid] && (!preg_match('/^\s*([A-Za-z]+\d*)\s*[=|<|>].*$/', $condition, $matches) || $question->options->displaycalc == 1)) {
    		    $feedbackperconditions_computed[$conditionid] .= '<ul><li>'.$values.'</li></ul>';
    		}
    	}
    	$question->options->computedfeedbackperconditions = implode('</li></ul><ul><li>', $feedbackperconditions_computed);
    	return $score;
    }
    
    function check_condition($condition, $parameters, &$values, $question) {
        global $CFG;
        $values = '';
        $interval = false;
        $operators = array('<=', '>=', '<', '>', '='); // ND : careful with operators relative positions here, see following foreach()
        foreach ($operators as $operator) {
            $operatorposition = strpos($condition, $operator);
            if ($operatorposition !== false) {
                $conditionsides = explode($operator, $condition);
                $left = trim($conditionsides[0]);
                $right = trim($conditionsides[1]);
                break;
            }
        }
        include_once("$CFG->dirroot/question/type/multinumerical/evalmath.class.php");
        $math = new EvalMath();
        $math->suppress_errors = true;
        // filling variables :
        foreach ($question->givenanswer as $param => $value) {
        	$param = strtolower($param); // EvalMath n'aime pas les noms de variables avec majuscules
        	$math->evaluate($param.'='.$value);
        }
        $leftvalue = $math->evaluate($left);
        if ($operator == '=') {
            $operator = '==';
            if (preg_match('/^\s*([A-Z]*[a-z]*\w*)\s*=\s*([\[|\]])(.+);(.+)([\[|\]])$/', $condition, $matches)) {
                    $interval = true;
                    $operator = "";
                    $rightvalue = ($matches[2] == "[") ? (">=") : (">");
                    $val1 = $math->evaluate($matches[3]);
                    $val2 = $math->evaluate($matches[4]);
                    $rightvalue .= $val1 . " && " . $leftvalue;
                    $rightvalue .= ($matches[5] == "]") ? ("<=") : ("<");
                    $rightvalue .= $val2;
            }
        }
        if (!$interval) {
            $rightvalue = $math->evaluate($right);
            $values .= number_format($leftvalue,2,'.',"'").' '.$operator.' '.number_format($rightvalue,2,'.',"'");
        }
        else {
            $values .= $leftvalue.' = '.$matches[2].number_format($val1,3,'.',"'").';'.number_format($val2,3,'.',"'").$matches[5];
        }
    	if (eval('return('.$leftvalue.$operator.$rightvalue.');')) {
    	    $valuesspan = '<span';
    	    $valuesspan .= ($question->options->usecolorforfeedback) ? (' style="color:#090"') : ('');
    	    $valuesspan .= '>'.get_string('conditionverified', 'qtype_multinumerical').' : '.$values.'</span>';
    	    $values = $valuesspan;
    		return true;
        }
        $valuesspan = '<span';
        $valuesspan .= ($question->options->usecolorforfeedback) ? (' style="color:#f00"') : ('');
        $valuesspan .= '>'.get_string('conditionnotverified', 'qtype_multinumerical').' : '.$values.'</span>';
        $values = $valuesspan;
     	return false;
    }

    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {
        $state->responses = array();
        $params = explode(",", $question->options->parameters);
        foreach ($params as &$param) {
        	$param = trim($param);
        	$paramparts = explode(" ", $param);
        	$paramname = trim($paramparts[0]);
        	$state->responses[$paramname] = '';
        }
        return true;
    }

    function restore_session_and_responses(&$question, &$state) {
        $responses = explode(' ; ', $state->responses['']);
        foreach ($responses as $response) {
            preg_match('/^(.+)\s=\s(\d*)$/', $response, $matches);
            $state->responses[$matches[1]] = $matches[2];
            $i++;
        }
        return true;
    }

    function save_session_and_responses(&$question, &$state) {
        $responses = $state->responses;
        $responses_str = '';
        $params = explode(",", $question->options->parameters);
        foreach ($params as &$param) {
        	$param = trim($param);
        	$paramparts = explode(" ", $param);
        	$parameter = trim($paramparts[0]);
        	$value = ($responses[$parameter]) ? ($responses[$parameter]) : (0);
        	$responses_str .= ' ; '.$parameter.' = '.$value;
        }
        $responses_str = trim($responses_str, ' ;');
        if (!set_field('question_states', 'answer', $responses_str, 'id', $state->id)) {
            return false;
        }
        return true;
    }
    
/*
     * Override the parent class method, to remove escaping from asterisks.
     */
    function get_correct_responses(&$question, &$state) {
        $response = array();
        $params = explode(',', $question->options->parameters);
        foreach ($params as $param) {
        	$param = trim($param);
            $response[$param] = get_string('noncomputable', 'qtype_multinumerical');
        }
        return $response;
    }

/// RESTORE FUNCTIONS /////////////////

    /*
     * Restores the data in the question
     *
     * This is used in question/restorelib.php
     */
    function restore($old_question_id,$new_question_id,$info,$restore) {

        $status = parent::restore($old_question_id, $new_question_id, $info, $restore);

        if ($status) {
            $extraquestionfields = $this->extra_question_fields();
            $questionextensiontable = array_shift($extraquestionfields);

            //We have to recode the answers field (a list of answers id)
            $questionextradata = get_record($questionextensiontable, $this->questionid_column_name(), $new_question_id);
            if (isset($questionextradata->answers)) {
                $answers_field = "";
                $in_first = true;
                $tok = strtok($questionextradata->answers, ",");
                while ($tok) {
                    // Get the answer from backup_ids
                    $answer = backup_getid($restore->backup_unique_code,"question_answers",$tok);
                    if ($answer) {
                        if ($in_first) {
                            $answers_field .= $answer->new_id;
                            $in_first = false;
                        } else {
                            $answers_field .= ",".$answer->new_id;
                        }
                    }
                    // Check for next
                    $tok = strtok(",");
                }
                // We have the answers field recoded to its new ids
                $questionextradata->answers = $answers_field;
                // Update the question
                $status = $status && update_record($questionextensiontable, $questionextradata);
            }
        }

        return $status;
    }


        /**
    * Prints the score obtained and maximum score available plus any penalty
    * information
    *
    * This function prints a summary of the scoring in the most recently
    * graded state (the question may not have been submitted for marking at
    * the current state). The default implementation should be suitable for most
    * question types.
    * @param object $question The question for which the grading details are
    *                         to be rendered. Question type specific information
    *                         is included. The maximum possible grade is in
    *                         ->maxgrade.
    * @param object $state    The state. In particular the grading information
    *                          is in ->grade, ->raw_grade and ->penalty.
    * @param object $cmoptions
    * @param object $options  An object describing the rendering options.
    */
    function print_question_grading_details(&$question, &$state, $cmoptions, $options) {
        /* The default implementation prints the number of marks if no attempt
        has been made. Otherwise it displays the grade obtained out of the
        maximum grade available and a warning if a penalty was applied for the
        attempt and displays the overall grade obtained counting all previous
        responses (and penalties) */
        global $QTYPES ;
        if (!empty($question->maxgrade) && $options->scores) {
            if (question_state_is_graded($state->last_graded)) {
                // Display the grading details from the last graded state
                $grade = new stdClass;
                $grade->cur = round($state->last_graded->grade, $cmoptions->decimalpoints);
                $grade->max = $question->maxgrade;
                $grade->raw = round($state->last_graded->raw_grade, $cmoptions->decimalpoints);

                // let student know wether the answer was correct
                echo '<div class="correctness ';
                if ($state->last_graded->raw_grade >= $question->maxgrade/1.01) { // We divide by 1.01 so that rounding errors dont matter.
                    echo ' correct">';
                    print_string('correct', 'quiz');
                } else if ($state->last_graded->raw_grade > 0) {
                    echo ' partiallycorrect">';
                    print_string('partiallycorrect', 'quiz');
                } else {
                    echo ' incorrect">';
                    // MDL-7496
                    print_string('incorrect', 'quiz');
                }
                echo '</div>';

                echo '<div class="gradingdetails">';
                // print grade for this submission
                print_string('gradingdetails', 'quiz', $grade);
                if ($cmoptions->penaltyscheme) {
                    // print details of grade adjustment due to penalties
                    if ($state->last_graded->raw_grade > $state->last_graded->grade){
                        echo ' ';
                        print_string('gradingdetailsadjustment', 'quiz', $grade);
                    }
                    // print info about new penalty
                    // penalty is relevant only if the answer is not correct and further attempts are possible
                    if (($state->last_graded->raw_grade < $question->maxgrade) and (QUESTION_EVENTCLOSEANDGRADE != $state->event)) {
                        if ('' !== $state->last_graded->penalty && ((float)$state->last_graded->penalty) > 0.0) {
                            // A penalty was applied so display it
                            echo ' ';
                            print_string('gradingdetailspenalty', 'quiz', $state->last_graded->penalty);
                        } else {
                            /* No penalty was applied even though the answer was
                            not correct (eg. a syntax error) so tell the student
                            that they were not penalised for the attempt */
                            echo ' ';
                            print_string('gradingdetailszeropenalty', 'quiz');
                        }
                    }
                }
                echo '</div>';
            }
        }
    }

    /**
     * Runs all the code required to set up and save an essay question for testing purposes.
     * Alternate DB table prefix may be used to facilitate data deletion.
     */
    function generate_test($name, $courseid = null) {
        list($form, $question) = parent::generate_test($name, $courseid);
        $question->category = $form->category;

        $form->questiontext = "What is the purpose of life, the universe, and everything";
        $form->generalfeedback = "Congratulations, you may have solved my biggest problem!";
        $form->penalty = 0.1;
        $form->usecase = false;
        $form->defaultgrade = 1;
        $form->noanswers = 3;
        $form->answer = array('42', 'who cares?', 'Be happy');
        $form->fraction = array(1, 0.6, 0.8);
        $form->feedback = array('True, but what does that mean?', 'Well you do, dont you?', 'Yes, but thats not funny...');
        $form->correctfeedback = 'Excellent!';
        $form->incorrectfeedback = 'Nope!';
        $form->partiallycorrectfeedback = 'Not bad';

        if ($courseid) {
            $course = get_record('course', 'id', $courseid);
        }

        return $this->save_question($question, $form, $course);
    }
}
//// END OF CLASS ////

//////////////////////////////////////////////////////////////////////////
//// INITIATION - Without this line the question type is not in use... ///
//////////////////////////////////////////////////////////////////////////
question_register_questiontype(new question_multinumerical_qtype());
?>

<?php
/**
 * Moodle coordinates question type class.
 *
 * @copyright &copy; 2010 Hon Wai, Lau
 * @author Hon Wai, Lau <lau65536@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * @package questionbank
 * @subpackage questiontypes
 */

require_once("$CFG->dirroot/question/type/questiontype.php");
require_once("$CFG->dirroot/question/type/coordinates/variables.php");
require_once("$CFG->dirroot/question/type/coordinates/answer_unit.php");
require_once("$CFG->dirroot/question/type/coordinates/conversion_rules.php");

/**
 * The coordinates question class
 *
 * TODO give an overview of how the class works here.
 */
class question_coordinates_qtype extends default_questiontype {

    function name() {
        return 'coordinates';
    }
    
    
    /// return the tags of subquestion answer field of the database/variable
    function subquestion_answer_tags() {
        return array('placeholder','answermark','vars1','answer','vars2','correctness'
            ,'unitpenalty','preunit','postunit','ruleid','otherrule','subqtext','feedback');
    }
    
    
    /// return the extra options field of the coordinates question type
    function subquestion_option_extras() {
        return array('varsrandom', 'varsglobal', 'retrymarkseq', 'peranswersubmit', 'showperanswermark');
    }
    
    
    /// Get the data of $question->id from the database and put them in the $question->options
    function get_question_options(&$question) {
        // get the basic option of the question including the varsdeftext
        if (!$question->options->extra = get_record('question_coordinates', 'questionid', $question->id)) {
            //notify('Error: Missing coordinate question options for questionid ' . $question->id);
            return false;
        }
        
        // get the subquestion answers which contains the id and the field in subquestion_answer_tags()
        if (!$question->options->answers = get_records('question_coordinates_answers', 'questionid', $question->id, 'id ASC')) {
            //notify('Error: Missing coordinate question answers for questionid ' . $question->id);
            return false;
        }
        $question->options->answers = array_values($question->options->answers);
        if (count($question->options->answers) == 0)  return false; // It must have at least one answer
        
        $question->subpart = $this->get_subquestion_structure($question->questiontext, $question->options->answers);
        if (isset($question->subpart->error))  return false;
        return true;
    }
    
    
    /// Attempt to insert or update a record in the database. May throw error
    function question_options_insertdb($dbname, &$record, $oldid) {
        if (isset($oldid)) {
            $record->id = $oldid;    // if there is old id, reuse it.
            if (!update_record($dbname, $record))
                throw new Exception("Could not update quiz record in database $dbname! (id=$oldid)");
        }
        else {
            if (!$record->id = insert_record($dbname, $record))
                throw new Exception("Could not insert quiz record in database $dbname! (id=$oldid)");
        }
    }


    /// Save the varsdef, answers and units to the database tables from the editing form
    function save_question_options($form) {
        // Get old versions of the objects
        $question = clone $form;
        $this->get_question_options($question);
        $oldextra = $question->options->extra;
        $oldanswers = $question->options->answers;
        
        try {
            $newanswers = $this->check_form_answers($form); // it should have no error as it has passed the validation
            $idcount = 0;
            foreach ($newanswers as $i=>$ans) {
                $this->question_options_insertdb('question_coordinates_answers', $ans, $oldanswers[$idcount++]->id);
                $newanswerids[$i] = $ans->id;
            }
            
            // delete remaining used records
            for ($i=count($newanswers); $i<count($oldanswers); ++$i)
                delete_records('question_coordinates_answers', 'id', $oldanswers[$i]->id);
            
            $newextra = new stdClass;
            $newextra->questionid  = $form->id;
            $extras = $this->subquestion_option_extras();
            foreach ($extras as $extra)  $newextra->$extra = trim($form->$extra);
            $newextra->answerids   = implode(',',$newanswerids);
            $this->question_options_insertdb('question_coordinates', $newextra, $oldextra->id);
        } catch (Exception $e) {
            return (object)array('error' => $e->getMessage());
        }
        
        return true;
    }


    /// Override the parent save_question in order to change the defaultgrade.
    function save_question($question, $form, $course) {
        $form->defaultgrade = array_sum($form->answermark);
        return parent::save_question($question, $form, $course);
    }


    /// Deletes question from the question-type specific tables with $questionid
    function delete_question($questionid) {
        delete_records('question_coordinates', 'questionid', $questionid);
        delete_records('question_coordinates_answers', 'questionid', $questionid);
        return true;
    }
    
    
    /// Parses the variable texts and then generates a random set of variables for this session
    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {
        try {
            $vars = new question_variables($question->options->extra->varsrandom);
            $state->randomvars = $vars->get_variables();
            $vars->add_local_variables($question->options->extra->varsglobal);
            $state->globalvars = $vars->get_variables();
            $state->trials = array_fill(0, count($question->subpart->parts), 0);
            $state->raw_grades = array_fill(0, count($question->subpart->parts), 0);
            $state->fractions = array_fill(0, count($question->subpart->parts), 0);
            $state->corrunits = array_fill(0, count($question->subpart->parts), 0);
            $state->responses = array_fill(0, count($question->subpart->parts), '');
            return true;    // success
        } catch (Exception $e) {
            return false;   // fail
        }
    }


    /// Restore the variables and answers from the last session
    function restore_session_and_responses(&$question, &$state) {
        try {
            $lines = explode("\n", $state->responses['']);
            $vars = new question_variables(array()); // the first line is the random variables of the session
            $vars->add_local_variables($lines[0]);   // the $lines[0] is a simple (variable => value) pairs
            $state->randomvars = $vars->get_variables();
            $vars->add_local_variables($question->options->extra->varsglobal);
            $state->globalvars = $vars->get_variables();
            $submitinfo = explode(';',$lines[2]);   // get the submission information of this state
            $grading = explode(';',$lines[3]);      // get the grading information of the state
            $state->subanum = intval($submitinfo[0]);
            $state->trials = explode(",",$submitinfo[1]);
            $state->raw_grades = explode(",",$grading[0]);
            $state->fractions = explode(",",$grading[1]);
            $state->corrunits = explode(",",$grading[2]);
            // the remaining lines are responses of subquestions, put them in state responses
            $state->responses = array_merge($state->responses, array_slice($lines,4,-1));
            return true;    // success
        } catch (Exception $e) {
            return false;   // fail
        }
    }
    
    
    /// The first line stores the variables and the following lines store the responses for each subquestions
    function save_session_and_responses(&$question, &$state) {
        foreach ($state->randomvars as $name => $value)
            $varsstr[] = $name . '=' . (is_array($value) ? '('.implode(',',$value).')' : (string)$value);
        $responses_str = implode(';', $varsstr) . ";\n"; // the first line is the variables of the session
        $responses_str .= "\n"; // reserved
        $responses_str .= $state->subanum . ";"; // this line is submission info, start by submitted answer number
        $responses_str .= implode(',', $state->trials) . "\n"; // this array is the number of wrong trials
        $responses_str .= implode(',', $state->raw_grades) . ";"; // this array is the raw_grades computed
        $responses_str .= implode(',', $state->fractions) . ";"; // this array is the fractions grade
        $responses_str .= implode(',', $state->corrunits) . "\n"; // this array is the correctness of unit

        foreach ($question->subpart->parts as $i => $part)
            $responses_str .= $state->responses[$i] . "\n";
        
        // Set the legacy answer field
        if (!set_field('question_states', 'answer', $responses_str, 'id', $state->id)) {
            return false;
        }
        return true;
    }
    
    
    /// Replace variables and format the text before print it out
    function print_question_texts($question, $cmoptions, $vars, $text, $class_names) {
        if (strlen(trim($text)) == 0)  return;
        $subtext = $vars->substitute_variables($text);
        $restext = $this->format_text($subtext, $question->questiontextformat, $cmoptions);
        echo ($class_names === '') ? $restext : '<div class="'.$class_names.'">'.$restext.'</div>'."\n";
    }
    
    
    /// Print the marks for each answer, if the showperanswermark is checked
    function print_per_answer_mark(&$question, $state, $cmoptions, $options, $i) {
        if ($question->options->extra->showperanswermark) {
            $maxmark = $question->subpart->parts[$i]->answermark; // there is no scaling of grade...
            if ($state->last_graded->trials[$i] < 1)    // if not graded, display -/mark
                echo '<div class="grade local_grade"> Mark: -/'.$maxmark.'</div>';
            else {
                echo '<div class="grade local_grade"';
                if ($cmoptions->penaltyscheme) {
                    $tmf = $this->get_trial_mark_fraction($question, $state->last_graded->trials[$i]);
                    echo ' title="Grading result for the previous submission. Trial #'.$state->last_graded->trials[$i]
                        .', Trial maximum mark: '.($tmf[0]*100).'%'
                        .', Raw mark: '.($maxmark*$state->last_graded->fractions[$i])
                        .', Result: '.($maxmark*$state->last_graded->fractions[$i]*$tmf[0]).'/'.$maxmark.'"';
                }
                echo '> Mark: '.$state->last_graded->raw_grades[$i].'/'.$maxmark.'</div>';
            }
        }
    }
    
    
    /// Print the question text and its subquestions answer box, give feedback if submitted.
    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        global $CFG;
        $readonly = empty($options->readonly) ? '' : 'disabled="disabled"';
        $submit_button_name = $question->name_prefix.'submit';
        $submit_location_id = $question->name_prefix.'submit';    // it should have no same question on the same page (?)
        $subans_track_id = $question->name_prefix.'subanum';
        
        if ($options->feedback) {
            foreach ($question->subpart->parts as $i => $part)  if ($state->subanum == -1 || $state->subanum == $i) {
                $classes[$i] = question_get_feedback_class($state->fractions[$i]);
                $feedbackimgs[$i] = question_get_feedback_image($state->fractions[$i]);
            }
        }
        
        // -------------- print the image if any --------------
        if ($image = get_question_image($question, $cmoptions->course)) {
            echo '<img class="qimage" src="'.$image.'" alt="" /><br />'."\n";
        }
        
        // -------------- display question body --------------
        $vars = new question_variables($state->globalvars);
        foreach ($question->subpart->parts as $i => $part) {
            $this->print_question_texts($question, $cmoptions, $vars, $question->subpart->subtexts[$i], '');
            $this->print_question_texts($question, $cmoptions, $vars, $part->subqtext, 'subqtext');
            echo '<div class="ablock clearfix">'."\n";
            
            echo '<div class="answer subanswer">'."\n";
            echo '<input type="text" class="coordinate_answer '.$classes[$i].'" '.$readonly.' name="'.$question->name_prefix.$i
                .'" value="'.$state->responses[$i].'"/>';
            if ($question->options->extra->peranswersubmit && ($cmoptions->optionflags & QUESTION_ADAPTIVE) && !$options->readonly) {
                if ($cmoptions->penaltyscheme) {
                    $tmf = $this->get_trial_mark_fraction($question, $state->last_graded->trials[$i]+1);
                    if ($tmf[0] >= 0)
                        echo '<input type="button" class="btn local_submit" value="Submit" onclick="coordinates_submit'
                    ."('$submit_location_id','$submit_button_name','$subans_track_id','$i','{$question->id}')".'" '
                    .'title="'.($tmf[1]>0 ? ($tmf[1]-$state->last_graded->trials[$i]).' trial remains. ' : 'Trial unlimited. ')
                    .'Maximum mark for this submission: '.($tmf[0]*100).'%">';
                }
            }
            $this->print_per_answer_mark($question, $state, $cmoptions, $options, $i);
            if ($options->feedback && ($state->subanum == -1 || $state->subanum == $i))
                echo $feedbackimgs[$i];
            echo '</div>'."\n";
            
            if ($options->feedback && ($state->subanum == -1 || $state->subanum == $i))  // some bug here, missing feedback...
                $this->print_question_texts($question, $cmoptions, $vars, $part->feedback, 'feedback');
            
            echo '</div>'."\n";
        }
        $this->print_question_texts($question, $cmoptions, $vars, $question->subpart->posttext, '');
        
        if ($question->options->extra->peranswersubmit == 1) {  // special parameter to store which submit answer
            echo '<input type="hidden" '.$readonly.' id="'.$subans_track_id.'" name="'.$subans_track_id.'" value="-1"/>';
            // The following placeholder allows the additional POST name (respid_submit) to trigger the grading process...
            echo '<div id="'.$submit_location_id.'" style="display:none""></div>';
        }
        else {
            $this->print_question_submit_buttons($question, $state, $cmoptions, $options);
            echo '<br/>';
        }
    }


    /// Return a summary string of student responses. Need to override because it prints the data...
    function response_summary($question, $state, $length = 80) {
        $responses = $this->get_actual_response($question, $state);
        $summaries = '';
        foreach ($question->subpart->parts as $idx => $part)  if ($state->subanum == -1 || $state->subanum == $idx) {
            $c = question_get_feedback_class($state->fractions[$idx]);
            $summaries .= '<div class="'.$c.'">'.'<i>('.($idx+1).'.) </i>'.shorten_text($state->responses[$idx], $length).'</div>';
        }
        return $summaries;
    }
    
    
    /// given a particular trial $trial_number, return the pair of maximum mark fraction and maximum number of trials
    function get_trial_mark_fraction(&$question, $trial_number) {
        if ($question->options->extra->retrymarkseq_parsed == null) {   // for the reuse of parsing result
            if (strlen(trim($question->options->extra->retrymarkseq)) == 0)  $mseq = array();
            else $mseq = explode(',', $question->options->extra->retrymarkseq);
            array_unshift($mseq, 1.0, 1.0);  // append two 1.0 (100%, full mark) for easy computation later.
            foreach ($mseq as $i => &$v)  if(is_numeric($v)) {
                $v = floatval($v);
                if (($i > 0 && $mseq[$i] > $mseq[$i-1]) || $mseq[$i]<0)
                    throw new Exception(get_string('error_retry_mark_order','qtype_coordinates'));
            }
            else {
                if ($i == count($mseq)-1 && strlen(trim(end($mseq))) == 0) {
                    array_pop($mseq);
                    $question->options->extra->retrymarkseq_loop = true;
                    break;
                }
                else throw new Exception(get_string('error_retry_mark_nonnumeric','qtype_coordinates'));
            }
            $question->options->extra->retrymarkseq_parsed = $mseq;
        }
        $mseq = $question->options->extra->retrymarkseq_parsed;
        
        if ($question->options->extra->retrymarkseq_loop) { // different of the last two elements is being repeated
            if ($trial_number < count($mseq))  return array($mseq[$trial_number], -1);
            $repeat_penalty = $mseq[count($mseq)-2] - $mseq[count($mseq)-1];
            return array(max(0, $mseq[count($mseq)-1] - $repeat_penalty*($trial_number-count($mseq)+1)), -1);
        }
        else {  // with finite trial
            if ($trial_number < count($mseq))  return array($mseq[$trial_number], count($mseq)-1);
            return array(-1, count($mseq)-1);  // -1 indicates no further submission is allowed
        }
    }
    
    
    /// Override. A different grading scheme is used because we need to give a grade to each subanswer.
    function grade_responses(&$question, &$state, $cmoptions) {
        $responses = $state->responses;
        if ($question->options->extra->peranswersubmit == 1 && isset($responses['subanum']) &&
            $responses['subanum'] >= 0 && $responses['subanum'] < count($question->subpart->parts) )
            $state->subanum = $responses['subanum'];    // the number indicate one particular answer is submitted
        else
            $state->subanum = -1;   // negative means all answers are submitted at once, all answers are graded
        
        $unit = new answer_unit_conversion; // it is defined here for the possibility of reusing parsed default set
        try {
            $state->raw_grades = $state->last_graded->raw_grades;
            $state->fractions = $state->last_graded->fractions;
            $state->trials = $state->last_graded->trials;
            foreach ($question->subpart->parts as $idx => $part)  if ($state->subanum == -1 || $state->subanum == $idx) {
                $tmf = $this->get_trial_mark_fraction($question, $state->trials[$idx]+1);
                if ($tmf[0] < 0)  continue;  // No further grading is allowed, which may be caused by browser resubmission
                $state->trials[$idx]++;
                $state->fractions[$idx] = $this->grade_response_correctness($part, $state->globalvars, $state->responses[$idx], $unit);
                $raw_grade = $part->answermark * $state->fractions[$idx] * $tmf[0];
                $state->raw_grades[$idx] = max($raw_grade, $state->last_graded->raw_grades[$idx]);
            }
        } catch (Exception $e) {
            notify('Grading error! Probably result of incorrect import file or database corruption.');
            return false;// it should have no error when grading students question ...............
        }
        
        // The default additive penalty scheme is not used, so set penalty=0 and the raw_grade with penalty are directly computed
        $state->raw_grade = array_sum($state->raw_grades);
        $state->penalty = 0;

        // mark the state as graded
        $state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;
        return true;
    }
    
    
    /// parse the user input in the form of pre-unit, answer and post-unit
    function parse_response_into_coordinate_unit($response) {
        if (strlen(trim($response)) == 0)  throw new Exception('No response!');
        $r = explode(',',$response);
        
        // find the first coordinate, which should be numerical value indicated by a 'digit' or '.'
        $tmp = explode("\n", preg_replace('/[-+.0-9]/', "\n$0", trim($r[0]), 1));
        // if the response consist of string only (maybe separated by ','), it is error
        if (count($tmp)==1)  throw new Exception('Response with wrong format!');
        $r[0] = $tmp[1];
        $pre = trim($tmp[0]);
        if (strlen($pre) > 0 && $pre[strlen($pre)-1] == '(') {
            $pre = substr($pre, 0, -1);
            $open_bracket = true;
        }
        else
            $open_bracket = false;
        $input->preunit = $pre;
        // Note that the preunit can only be a simple string, while postunit can use dimension
        
        // find the first number, and the remaining part is treated as post-unit
        $last = count($r)-1;
        $tmp = explode("\n", preg_replace('/^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?/', "$0\n", trim($r[$last]), 1));
        if (count($tmp)==1)  throw new Exception('Response with wrong format!');
        // there should have one space...
        $r[$last] = $tmp[0];
        $post = trim($tmp[1]);
        if ($open_bracket)  if (strlen($post) == 0 || $post[0] != ')') 
            throw new Exception('Response with wrong format!');
        else
            $post = substr($post, 1);
        $input->postunit = $post;
        
        // bracket is required for more than one coordinate
        if ( count($r) > 1 && !$open_bracket)  throw new Exception('Response with wrong format!');
        
        foreach ($r as $v)  if (is_numeric($v))
            $input->coordinates[] = floatval($v);
        else
            throw new Exception('Answer possibly contains non-numeric value: ' . $response);
        
        return $input;
    }
    
    
    /// grade the response and the unit together and return a single mark
    function grade_response_correctness($part, $globalvars, $response, $unit) {
        // split the response into the coordinate and unit part
        try {
            $input = $this->parse_response_into_coordinate_unit($response);
        } catch (Exception $e) { return 0; } // give it 0 marks if the input is not in the coordinate, unit format
        
        // check whether the unit part of the response is possibly correct
        global $basic_unit_conversion_rules;
        $unit->assign_default_rules($part->ruleid, $basic_unit_conversion_rules[$part->ruleid][1]);
        $unit->assign_additional_rules($part->otherrule);
        $res1 = $unit->check_convertibility($input->preunit, $part->preunit);
        $res2 = $unit->check_convertibility($input->postunit, $part->postunit);
        $cfactor = $res1->cfactor * $res2->cfactor;
        $unit_correct = $res1->convertible && $res2->convertible;
        // unit should have no effect (i.e. always true) if the coordinates are all 0
        $is_origin = true;
        foreach ($input->coordinates as $i => $v)  $is_origin = $is_origin && ($v == 0);
        if ($is_origin)  $unit_correct = true;
        
        // check the correctness of the coordinate part
        $ans = $this->evaluate_answer($part, $globalvars);    // compute the predefined answer with vars, if any
        if (count($input->coordinates) != $ans->size)  return false;
        $vars = $ans->vars;    // it contains the global and local variables before answer
        
        foreach ($input->coordinates as $idx => &$coord)  $coord *= $cfactor; // rescale response to match answer
        $this->add_special_correctness_variables($vars, $ans, $input);
        // evaluated the set of local variables preceding the correctness expression
        $vars->add_local_variables($part->vars2);
        // evaluate the $correctness_expression to determine whether the question is correct
        $correctness = $vars->evaluate_expression($part->correctness);
        
        $fraction = min(max((float) $correctness, 0.0), 1.0) * ($unit_correct ? 1 : (1-$part->unitpenalty));
        return $fraction;
    }
    
    
    /// add the set of special variables that may be useful to guage the correctness of the user input
    function add_special_correctness_variables(&$vars, $ans, $input) {
        //$vars->add('{_res}', $input->coordinates);
        foreach ($input->coordinates as $idx => $coord)  $vars->add('{_'.$idx.'}', $coord);
        if ($ans->fixedpoint == false)  return;
        //$vars->add('{_ans}', $ans->coordinates);
        $sum0 = $sum1 = $sum2 = $maxerr = $relerr = $norm_sqr = 0;
        foreach ($ans->coordinates as $idx => $coord) {
            $norm_sqr += $coord*$coord;
            $diff[$idx] = $coord - $input->coordinates[$idx];
            //$match[$idx] = ($diff[$idx] == 0) ? 0 : 1;
            //$d1[$idx] = abs($diff[$idx]);
            $d2[$idx] = $diff[$idx]*$diff[$idx];
            $sum0 += ($diff[$idx] == 0) ? 0 : 1;
            $sum1 += abs($diff[$idx]);
            $sum2 += $d2[$idx];
            $maxerr = max($maxerr, abs($diff[$idx]));
        }
        $vars->add('{_diff}', $diff);
        $vars->add('{_d}', $d2);
        $vars->add('{_wrong}', $sum0);
        $vars->add('{_sumerr}', $sum1);
        $vars->add('{_err}', sqrt($sum2));
        $vars->add('{_maxerr}', $maxerr);
        $vars->add('{_relerr}', sqrt($sum2/$norm_sqr));
    }
    
    
    /// return the evaluated answer. The $fixedpoint indicates whether the answer is a fixed precise value.
    function evaluate_answer($answerfield, $globalvars) {
        $anstext = trim($answerfield->answer);
        if (strlen($anstext) == 0)  throw new Exception(get_string('error_answer_missing','qtype_coordinates'));
        $res->vars = new question_variables($globalvars);
        $res->vars->add_local_variables($answerfield->vars1);
        /*if ($anstext[0] === '#') {  // test whether only the number of point is specified, signify by '#'
            $res->fixedpoint = false;   // whether the answer is a precise point 
            $res->size = floatval(substr($anstext, 1));
            for($ii=0; $ii<$input->size; ++$ii)  $input->coordinates[$ii] = 0;   // fill with dummy zero
            if ($res->size <= 0 || $res->size>1000)  throw new Exception(get_string('error_answer_wrong','qtype_coordinates'));
        } else {*/
            $coords = explode(';', $answerfield->answer);
            foreach ($coords as $coord)
                if (strlen(trim($coord)) == 0) {
                    throw new Exception(get_string('error_answer_empty','qtype_coordinates'));
                } else {
                    $res->coordinates[] = floatval($res->vars->evaluate_expression($coord));
                }
            $res->fixedpoint = true;
            $res->size = count($res->coordinates);
        //}
        return $res;
    }
    
    
    /// compute the correct response of each subquestion, if any
    function get_correct_responses(&$question, &$state) {
        foreach ($question->subpart->parts as $idx => $part) {
            try {
                $tmp = $this->evaluate_answer($part, $state->globalvars);
            } catch (Exception $e)  { return null; }
            if ($tmp->fixedpoint == false)
                $responses[$idx] = get_string('answer_not_fixed', 'qtype_coordinates');
            else {
                if ($tmp->size > 1)  $responses[$idx] = ' (' . implode(',',$tmp->coordinates) . ') ';
                else $responses[$idx] = ' ' . implode(',',$tmp->coordinates) . ' ';
                $r1 = explode('=', $part->preunit, 2);
                $r2 = explode('=', $part->postunit, 2);
                $responses[$idx] = trim(trim($r1[0]) . $responses[$idx] . trim($r2[0]));
            }
            $responses[$idx] = trim($responses[$idx]);
        }
        return $responses;
    }
    
    
    /**
     * Imports the question from Moodle XML format.
     *
     * @param $data structure containing the XML data
     * @param $question question object to fill: ignored by this function (assumed to be null)
     * @param $format format class exporting the question
     * @param $extra extra information (not required for importing this question in this format)
     */
    function import_from_xml(&$data,&$question,&$format,&$extra) {
        // return if type in the data is not coordinate question
        if ($data['@']['type'] != $this->name())  return false;
        // Import the common question headers and set the corresponding field
        $qo = $format->import_headers($data);
        $qo->qtype = $this->name();
        $extras = $this->subquestion_option_extras();
        foreach ($extras as $extra)
            $qo->$extra = $format->getpath($data, array('#',$extra,0,'#','text',0,'#'),'',true);
        
        // Loop over each answer block found in the XML
        $tags = $this->subquestion_answer_tags();
        $answers = $data['#']['answers'];  
        foreach($answers as $answer) {
            foreach($tags as $tag) {
                $qotag = &$qo->$tag;
                $qotag[] = $format->getpath($answer, array('#',$tag,0,'#','text',0,'#'),'0',false,'error');
            }
        }
        $qo->defaultgrade = array_sum($qo->answermark); // make the defaultgrade consistent if not specified

        return $qo;
    }
    
    
    /**
     * Exports the question to Moodle XML format.
     *
     * @param $question question to be exported into XML format
     * @param $format format class exporting the question
     * @param $extra extra information (not required for exporting this question in this format)
     * @return text string containing the question data in XML format
     */
    function export_to_xml(&$question,&$format,&$extra) {
        $expout = '';
        $extras = $this->subquestion_option_extras();
        foreach ($extras as $extra)
            $expout .= "<$extra>".$format->writetext($question->options->extra->$extra)."</$extra>\n";
        
        $tags = $this->subquestion_answer_tags();
        foreach ($question->options->answers as $answer) {
            $expout .= "<answers>\n";
            foreach ($tags as $tag)
                $expout .= " <$tag>\n  ".$format->writetext($answer->$tag)." </$tag>\n";
            $expout .= "</answers>\n";
        }
        return $expout;
    }
    
    
    /**
     * Backup the data in the question to a backup file.
     *
     * This function is used by question/backuplib.php to create a copy of the data
     * in the question so that it can be restored at a later date. The method writes
     * all the supplementary coordinate data, including the answers of the subquestions.
     *
     * @param $bf the backup file to write the information to
     * @param $preferences backup preferences in effect (not used)
     * @param $questionid the ID number of the question being backed up
     * @param $level the indentation level of the data being written
     * 
     * @return bool true if the backup was successful, false if it failed.
     */
    function backup($bf,$preferences,$questionid,$level=6) {
        $question->id = $questionid;
        $this->get_question_options($question); // assume no error
        
        // Start tag of data
        $status = true;
        $status = $status && fwrite ($bf,start_tag('COORDINATES',$level,true));
        $extras = $this->subquestion_option_extras();
        foreach ($extras as $extra)
            fwrite ($bf,full_tag(strtoupper($extra), $level+1, false, $question->options->extra->$extra));
        
        // Iterate over each answer and write out its fields
        $tags = $this->subquestion_answer_tags();
        foreach ($question->options->answers as $var) {
            $status = $status && fwrite ($bf,start_tag('ANSWERS',$level+1,true));
            foreach ($tags as $tag)
                fwrite ($bf, full_tag(strtoupper($tag), $level+2, false, $var->$tag));
            $status = $status && fwrite ($bf,end_tag('ANSWERS',$level+1,true));
        }
        
        // End tag of data
        $status = $status && fwrite ($bf,end_tag('COORDINATES',$level,true));
        return $status;
    }
    
    
    /**
     * Restores the data in a backup file to produce the original question.
     *
     * This method is used by question/restorelib.php to restore questions saved in
     * a backup file to the database. It reads the file directly and writes the information
     * straight into the database.
     *
     * @param $old_question_id the original ID number of the question being restored
     * @param $new_question_id the new ID number of the question being restored
     * @param $info the XML parse tree containing all the restore information
     * @param $restore information about the current restore in progress
     * 
     * @return bool true if the backup was successful, false if it failed.
     */
    function restore($old_question_id,$new_question_id,$info,$restore) {
        $data = $info['#']['COORDINATES'][0];
        $qo = new stdClass;
        $qo->id          = $new_question_id;
        $qo->qtype       = $this->name();
        $extras = $this->subquestion_option_extras();
        foreach ($extras as $extra)
            $qo->$extra = backup_todb($data['#'][strtoupper($extra)]['0']['#']);
        
        // Loop over each answer block found in the XML
        $tags = $this->subquestion_answer_tags();
        $answers = $data['#']['ANSWERS'];  
        foreach($answers as $answer) {
            foreach($tags as $tag) {
                $qotag = &$qo->$tag;
                $qotag[] = backup_todb($answer['#'][strtoupper($tag)]['0']['#']);
            }
        }
        return is_bool($this->save_question_options($qo)) ? true : false;
    }
    
    
    /**
     * Check the validity of answer fields in the form.
     * 
     * @param $form all the input form data
     * @return the answer data structure for all valid answer. Otherwise, return error message
     */
    function check_form_answers($form) {
        $newanswers = array();
        $placeholders = array();
        foreach ($form->answermark as $i=>$a) {
            if (strlen(trim($form->answermark[$i])) == 0)
                continue;   // if no mark, then skip this answers
            if (floatval($form->answermark[$i]) <= 0)
                $err->errors["answermark[$i]"] = get_string('error_mark','qtype_coordinates');
            $skip = false;
            if (strlen(trim($form->answer[$i])) == 0) {
                $err->errors["answer[$i]"] = get_string('error_answer_missing','qtype_coordinates');
                $skip = true;
            }
            if (strlen(trim($form->correctness[$i])) == 0) {
                $err->errors["correctness[$i]"] = get_string('error_correctness_missing','qtype_coordinates');
                $skip = true;
            }
            if (isset($placeholders[trim($form->placeholder[$i])])) {
                $err->errors["placeholder[$i]"] = get_string('error_placeholder_sub_duplicate','qtype_coordinates');
                $skip = true;
            }
            $placeholders[trim($form->placeholder[$i])] = true;
            if ($skip)  continue;   // if no answer or correctness conditions, it cannot check other parts, so skip
            $ans = new stdClass;
            $ans->questionid = $form->id;
            $tags = $this->subquestion_answer_tags();
            foreach ($tags as $tag)  $ans->$tag = trim($form->{$tag}[$i]);
            $newanswers[$i] = $ans;
        }
        if (count($newanswers) == 0)
            $err->errors["answermark[0]"] = get_string('error_no_answer','qtype_coordinates');
        if (isset($err->errors)) {
            $err->error = "Some error";
            $err->answers = $newanswers;
            return $err;
        }
        else
            return $newanswers;
    }
    
    
    /**
     * Split the subquestion by the placeholder.
     * 
     * @param string $questiontext The input question text containing a set of placeholder
     * @param array $answers Array of answers, containing the placeholder name  (must not empty)
     * @return Return the object with field subtexts, subanswers, max_grades and posttext
     */
    function get_subquestion_structure($questiontext, $answers) {
        if (!isset($questiontext) || !isset($answers))  return (object)array('error' => 'get_subquestion_structure(): input is null.');
        $err = $this->check_placeholder($questiontext, $answers);
        if (isset($err))  return $err;
        
        $subanswers = array();
        foreach ($answers as $answer)
            $subanswers[$answer->placeholder] = $answer;
        $placeholders = array_keys($subanswers);
        $locations = array();
        foreach ($placeholders as $idx => $placeholder) {
            // By default, the empty placeholder is located at the end of question
            $tmppos = strlen($placeholder) == 0 ? 9999999 : strpos($questiontext, $placeholder);
            $locations[$idx] = 1000*$tmppos + $idx; // shift two numbers, used to emulate stable sort
        }
        
        sort($locations);   // performs stable sort of location and answerorder pair
        $ss = new stdClass();
        foreach ($placeholders as $i => $placeholder) {
            $answerorder = $locations[$i]%1000;
            $ss->answerorders[] = $answerorder;
            $placeholder = $placeholders[$answerorder];
            $ss->placeholders[] = $placeholder;
            $ss->parts[] = $subanswers[$placeholder];
            $texts = $placeholder ? explode($placeholder,$questiontext) : array($questiontext,'');
            list($ss->subtexts[$i],$questiontext) = $texts;
        }
        $ss->posttext = $questiontext;  // add the post-question text
        return $ss;
    }
    
    
    /// check whether the placeholder in the $answers is correct and compatible with $questiontext
    function check_placeholder($questiontext, $answers) {
        $placeholder_format = '\{#[A-Za-z0-9]+\}';
        $err = null;
        foreach ($answers as $idx => $answer) {
            if ( strlen($answer->placeholder) == 0 )  continue; // no error for empty placeholder
            if ( strlen($answer->placeholder) >= 40 ) 
                $err->errors["placeholder[$idx]"] = get_string('error_placeholder_too_long','qtype_coordinates');
            if ( !preg_match('/^'.$placeholder_format.'$/', $answer->placeholder) )
                $err->errors["placeholder[$idx]"] .= get_string('error_placeholder_format','qtype_coordinates');
            $expl = explode($answer->placeholder, $questiontext);
            if (count($expl)<2)
                $err->errors["placeholder[$idx]"] .= get_string('error_placeholder_missing','qtype_coordinates');
            if (count($expl)>2)
                $err->errors["placeholder[$idx]"] .= get_string('error_placeholder_main_duplicate','qtype_coordinates');
        }
        if (isset($err->errors))
            $err->error = "Placeholder error";
        return $err;
    }
    
}

// Register this question type with the system.
question_register_questiontype(new question_coordinates_qtype());
?>

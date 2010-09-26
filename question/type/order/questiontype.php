<?php
/**
 *
 * The question type class for the ordering question type.
 *
 * It is based on the matching question type.
 * The database structure is very similar to the matching question.
 *
 * The teaching interface lacks the answer fields since the answers are
 * always the item numbers.
 *
 * In the student interface, the items are always shuffled.  In the non-javascript
 * version, the answers are always sorted.
 *
 * @copyright &copy; 2007 Adriane Boyd
 * @author adrianeboyd@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package aab_order
 *
 *
 */
class question_order_qtype extends default_questiontype {

    function name() {
        return 'order';
    }

    function get_question_options(&$question) {
        $question->options = get_record('question_order', 'question', $question->id);
        $question->options->subquestions = get_records('question_order_sub', 'question', $question->id, 'id ASC');
        return true;
    }

    function save_question_options($question) {
        $result = new stdClass;

        if (!$oldsubquestions = get_records("question_order_sub", "question", $question->id, "id ASC")) {
            $oldsubquestions = array();
        }

        // $subquestions will be an array with subquestion ids
        $subquestions = array();

        $ordercount = 1;

        // Insert all the new question+answer pairs
        foreach ($question->subquestions as $key => $questiontext) {
            $answertext = $question->subanswers[$key];
            if (!empty($questiontext)) {
                if ($subquestion = array_shift($oldsubquestions)) {  // Existing answer, so reuse it
                    $subquestion->questiontext = $questiontext;
                    $subquestion->answertext   = $answertext;
                    if (!update_record("question_order_sub", $subquestion)) {
                        $result->error = "Could not insert order subquestion! (id=$subquestion->id)";
                        return $result;
                    }
                    $ordercount += 1;
                } else {
                    $subquestion = new stdClass;
                    // Determine a unique random code
                    $subquestion->code = rand(1,999999999);
                    while (record_exists('question_order_sub', 'code', $subquestion->code, 'question', $question->id)) {
                        $subquestion->code = rand();
                    }

                    $subquestion->question = $question->id;
                    $subquestion->questiontext = $questiontext;
                    $subquestion->answertext   = $ordercount;
                    $ordercount += 1;

                    if (!$subquestion->id = insert_record("question_order_sub", $subquestion)) {
                        $result->error = "Could not insert order subquestion!";
                        return $result;
                    }
                }
                $subquestions[] = $subquestion->id;
            }
        }

        // delete old subquestions records
        if (!empty($oldsubquestions)) {
            foreach($oldsubquestions as $os) {
                delete_records('question_order_sub', 'id', $os->id);
            }
        }

        if ($options = get_record("question_order", "question", $question->id)) {
            $options->subquestions = implode(",",$subquestions);
            $options->horizontal = $question->horizontal;
            if (!update_record("question_order", $options)) {
                $result->error = "Could not update order options! (id=$options->id)";
                return $result;
            }
        } else {
            unset($options);
            $options->question = $question->id;
            $options->subquestions = implode(",",$subquestions);
            $options->horizontal = $question->horizontal;
            if (!insert_record("question_order", $options)) {
                $result->error = "Could not insert order options!";
                return $result;
            }
        }

        if (!empty($result->notice)) {
            return $result;
        }

        if (count($subquestions) < 3) {
            $result->notice = get_string('notenoughanswers', 'quiz', 3);
            return $result;
        }

        return true;
    }

    /**
    * Deletes question from the question-type specific tables
    *
    * @return boolean Success/Failure
    * @param integer $question->id
    */
    function delete_question($questionid) {
        delete_records("question_order", "question", $questionid);
        delete_records("question_order_sub", "question", $questionid);
        return true;
    }

    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {
        if (!$subquestions = get_records('question_order_sub', 'question', $question->id, 'id ASC')) {
            notify('Error: Missing subquestions!');
            return false;
        }

        // Place the questions into the state options keyed by code
        foreach ($subquestions as $subquestion) {
            $state->options->subquestions[$subquestion->code] = $subquestion;
        }

        foreach ($state->options->subquestions as $key => $subquestion) {
            // This seems rather over complicated, but it is useful for the
            // randomsamatch questiontype, which can then inherit the print
            // and grading functions. This way it is possible to define multiple
            // answers per question, each with different marks and feedback.
            $answer = new stdClass();
            $answer->id       = $key;
            $answer->answer   = $subquestion->answertext;
            $answer->fraction = 1.0;
            $state->options->subquestions[$key]->options
                    ->answers[$key] = clone($answer);

            $state->responses[$key] = '';
        }

        // Add default defaultresponse value
        $state->options->defaultresponse = "no";

        // Shuffle until the questions are not initially in a correct order
        // In a case where all items are identical, this will loop, so limit it to 10 tries.
        $correctorder = 1;
        $reshufflecount = 0;
        while ($correctorder && $reshufflecount < 10) {
            // Shuffle the questions
            $state->options->subquestions = swapshuffle_assoc($state->options->subquestions);

            // Assign default responses in order to check that the items are not already
            // in a correct order
            $state->responses = array();
            $i = 1;
            foreach ($state->options->subquestions as $subquestion) {
                $state->responses[$subquestion->code] = $i;
                $i += 1;
            }

            // If all items are not in the correct order, accept the current shuffle
            // and continue
            $evaluatedresponses = $this->get_evaluated_responses($question, $state);
            $correctresponses = array();
            foreach ($state->options->subquestions as $key => $subquestion) {
                if ($subquestion->questiontext) {
                    // If some item is incorrect
                    if (!$evaluatedresponses[$key]) {
                        $correctorder = 0;
                    }
                }
            }

            $reshufflecount += 1;
        }

        // Unset responses array
        $state->responses = array();

        return true;
    }

    function restore_session_and_responses(&$question, &$state) {
        // The serialized format for ordering questions is a comma separated
        // list of question answer pairs (e.g. 1-1,2-3,3-2), where the ids of
        // both refer to the id in the table question_order_sub.
        $responses = explode(',', $state->responses['']);

        $state->options->defaultresponse = array_pop($responses);

        // Map responses
        $responses = array_map(create_function('$val',
         'return explode("-", $val);'), $responses);

        if (!$subquestionsbyid = get_records('question_order_sub', 'question', $question->id, 'id ASC')) {
            notify('Error: Missing subquestions!');
            return false;
        }

        // Rekey subquestions by code to use in the next step
        $subquestions = array();
        foreach ($subquestionsbyid as $subquestion) {
            $subquestions[$subquestion->code] = $subquestion;
        }

        // Place the questions into the state options keyed by code and restore the
        // previous responses
        $state->options->subquestions = array();
        $state->responses = array();
        foreach ($responses as $response) {
            $state->options->subquestions[$response[0]] = $subquestions[$response[0]];
            $state->responses[$response[0]] = $response[1];
        }

        foreach ($state->options->subquestions as $key => $subquestion) {
            // This seems rather over complicated, but it is useful for the
            // randomsamatch questiontype, which can then inherit the print
            // and grading functions. This way it is possible to define multiple
            // answers per question, each with different marks and feedback.
            $answer = new stdClass();
            $answer->id       = $key;
            $answer->answer   = $subquestion->answertext;
            $answer->fraction = 1.0;
            $state->options->subquestions[$key]->options
             ->answers[$key] = clone($answer);
        }

        return true;
    }

    function save_session_and_responses(&$question, &$state) {
        $subquestions = &$state->options->subquestions;
        $responses = &$state->options->responses;

        if (isset($responses['defaultresponse']) and $responses['defaultresponse'] == 'on') {
            $state->options->defaultresponse = 'yes';
        }
        // If it's not set at all, default is no
        else if (isset($responses['defaultresponse']) and $responses['defaultresponse'] != 'on') {
            $state->options->defaultresponse = 'no';
        }
        else {
            $state->options->defaultresponse = 'no';
        }

        // Serialize responses
        $responses = array();
        foreach ($subquestions as $key => $subquestion) {
            $response = 0;
            if ($subquestion->questiontext) {
                if (isset($state->responses[$key])) {
                    $response = $state->responses[$key];
                }
            }
            $responses[] = $key.'-'.$response;
        }
        $responses[] = $state->options->defaultresponse;
        $responses = implode(',', $responses);

        // Set the legacy answer field
        if (!set_field('question_states', 'answer', $responses, 'id', $state->id)) {
            return false;
        }
        return true;
    }

    function get_correct_responses(&$question, &$state) {
        $responses = array();
        foreach ($question->options->subquestions as $subquestion) {
            $responses[$subquestion->code] = $subquestion->answertext;
        }
        return empty($responses) ? null : $responses;
    }

    /**
     * Return a 2D array useful for checking answers in the case of duplicate items.
     * $array[questiontext][answer] = true for any correct pair
     *
     * @param object $question
     * @param object $state
     * @return array
     */
    function get_answer_array(&$question, &$state) {
        $subquestions = &$state->options->subquestions;
        $responses    = &$state->responses;

        $itemtexts = array();
        foreach ($subquestions as $subquestion) {
            $questiontext = $subquestion->questiontext;
            if (!isset($itemtexts[$questiontext])) {
                $itemtexts[$questiontext] = array();
            }
            $ans = reset($subquestion->options->answers);
            $itemtexts[$questiontext][$ans->answer] = true;
        }

        return $itemtexts;
    }

    /**
     * Returns an array
     *
     * @param object $question
     * @param object $state
     * @return array
     */
    function get_evaluated_responses(&$question, &$state) {
        $subquestions = &$state->options->subquestions;

        // (The responses array is not assigned by reference so that the modifications
        // during the defaultresponse check do not affect the position of the items in
        // the state.)
        $responses = $state->responses;

        // Check to see if defaultresponse has changed
        if (isset($responses['defaultresponse']) and $responses['defaultresponse'] == 'on') {
            $state->options->defaultresponse = 'yes';
        }
        // If it's not set at all, default is no
        else if (isset($responses['defaultresponse'])) {
            $state->options->defaultresponse = 'no';
        }
        else {
            $state->options->defaultresponse = 'no';
        }

        // If the javascript "defaultresponse" is checked, treat it just like the
        // undecided response from the non-javascript version where the undecided
        // response is 0
        if ($state->options->defaultresponse == 'yes') {
            foreach ($subquestions as $key => $subquestion) {
                $responses[$key] = 0;
            }
        }

        // 2D array to help when disambiguating equal items
        $itemtexts = $this->get_answer_array($question, $state);

        // Add up the grades from each subquestion while keeping track of seen item numbers.
        // (It is necessary to keep track of seen item numbers to differentiate the
        // partially incorrect answer 1,2,1 from the correct answers 1,2,3 or 3,2,1 in the
        // case of a sequence such as C,D,C.)
        $evaluatedresponses = array();
        $seenpositions = array();
        foreach ($subquestions as $key => $subquestion) {
            if ($subquestion->questiontext) {
                $questiontext = $subquestion->questiontext;
                $response = isset($responses[$key]) ? $responses[$key] : '0';
                // To be correct:
                //  - a response needs to be provided
                //  - the same item number cannot have been used for a previous item
                //  - the response needs to be correct for the given questiontext
                if ($response && !array_key_exists($response, $seenpositions) && array_key_exists($response, $itemtexts[$questiontext])) {
                    $evaluatedresponses[$key] = true;
                }
                else {
                    $evaluatedresponses[$key] = false;
                }
                $seenpositions[$response] = true;
            }
        }

        return $evaluatedresponses;
    }

    /**
     * If this question type requires extra CSS or JavaScript to function,
     * then this method will return an array of <link ...> tags that reference
     * those stylesheets. This function will also call require_js()
     * from ajaxlib.php, to get any necessary JavaScript linked in too.
     *
     * The YUI libraries needed for dragdrop have been added to the default
     * set of libraries.
     *
     * The two parameters match the first two parameters of print_question.
     *
     * @param object $question The question object.
     * @param object $state    The state object.
     *
     * @return an array of bits of HTML to add to the head of pages where
     * this question is print_question-ed in the body. The array should use
     * integer array keys, which have no significance.
     */
    function get_html_head_contributions(&$question, &$state) {
        // Load YUI libraries
        require_js("yui_yahoo");
        require_js("yui_event");
        require_js("yui_dom");
        require_js("yui_dragdrop");
        require_js("yui_animation");

        $contributions = parent::get_html_head_contributions($question, $state);

        return $contributions;
    }

    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        global $CFG;
        $subquestions   = $state->options->subquestions;
        $nameprefix     = $question->name_prefix;
        $answers        = array();
        $responses      = &$state->responses;

        // Check browser version to see if YUI is supported properly.
        // This is similar to ajaxenabled() from lib/ajax/ajaxlib.php,
        // except it doesn't check the site-wide AJAX settings.
        $fallbackonly = false;

        $ie = check_browser_version('MSIE', 6.0);
        $ff = check_browser_version('Gecko', 20051106);
        $op = check_browser_version('Opera', 9.0);
        $sa = check_browser_version('Safari', 412);

        if ((!$ie && !$ff && !$op && !$sa) or !empty($USER->screenreader)) {
            $fallbackonly = true;
        }

        // Check to see if the defaultresponse option has changed from the previous
        // submission
        /*if (isset($responses['defaultresponse'])) {
            $state->options->defaultresponse = $responses['defaultresponse'];
        }
        // If it's not set at all, default is no
        else if (!isset($state->options->defaultresponse)) {
            $state->options->defaultresponse = "no";
        }*/



        if (isset($responses['defaultresponse']) and $responses['defaultresponse'] == 'on') {
            $state->options->defaultresponse = 'yes';
        }
        // If it's not set at all, default is no
        else if (isset($responses['defaultresponse']) and $responses['defaultresponse'] != 'on') {
            $state->options->defaultresponse = 'no';
        }
        else {
            $state->options->defaultresponse = 'no';
        }

        // Determine which responses are correct.
        $evaluatedresponses = $this->get_evaluated_responses($question, $state);
        $correctresponses = array();
        foreach ($subquestions as $key => $subquestion) {
            if ($subquestion->questiontext) {
                if ($evaluatedresponses[$key]) {
                    $correctresponses[$key] = true;
                }
                else {
                    $correctresponses[$key] = false;
                }
            }
        }

        // Generate question intro for both versions
        $questiontext = $this->format_text($question->questiontext,
                $question->questiontextformat, $cmoptions);
        $image = get_question_image($question);

        ////////////////////////////////
        // Generate fallback version
        ////////////////////////////////

        // Prepare a list of answers and sort them
        foreach ($subquestions as $subquestion) {
            foreach ($subquestion->options->answers as $ans) {
                $answers[$ans->answer] = $ans->answer;
            }
        }
        asort($answers);

        // Add each subquestion to the output
        foreach ($subquestions as $key => $subquestion) {
            if ($subquestion->questiontext) {
                // Subquestion text:
                $a = new stdClass;
                $a->text = $this->format_text($subquestion->questiontext,
                        $question->questiontextformat, $cmoptions);

                // Drop-down list:
                $menuname = $nameprefix.$subquestion->code;
                $response = isset($state->responses[$subquestion->code])
                            ? $state->responses[$subquestion->code] : '0';

                $a->class = ' ';
                $a->feedbackimg = ' ';
                if ($options->readonly) {
                    if ($correctresponses[$subquestion->code]) {
                        $correctresponse = 1;
                    }
                    else {
                        $correctresponse = 0;
                    }

                    if ($options->feedback && $response) {
                        $a->class = question_get_feedback_class($correctresponse);
                        $a->feedbackimg = question_get_feedback_image($correctresponse);
                    }
                }

                $a->control = choose_from_menu($answers, $menuname, $response, 'choose',
                                               '', 0, true, $options->readonly);

                $anss[] = $a;
            }
        }

        //////////////////////////////////
        // Generate javascript version
        //////////////////////////////////

        // Initialize variables
        $ulname = 'ul'.$question->id;
        $liname = 'li'.$question->id;
        $defaultresponsename = $nameprefix.'defaultresponse';



        if ($state->options->defaultresponse == 'yes') {
            $ulclass .= 'deactivateddraglist';
        }
        else {
            $ulclass = 'draglist';
        }

        // Javascript to initialize variables and event callbacks
        $jsinit = array();
        // HTML to write in javascript version
        $jswrite = array();

        // Javascript Array initialization of list ids
        $lielems = array();
        foreach ($subquestions as $subquestion) {
            $lielems[] = '"'.$liname.'_'.$subquestion->code.'"';
        }
        $liarraystring = 'Array('.implode(',', $lielems).')';

        // Set up event callbacks for interactive version
        if (!$options->readonly) {
            $jsinit[] = 'var hiddennames = new Object();';
            $jsinit[] = 'hiddennames.ulname = "'.$ulname.'";';
            $jsinit[] = 'hiddennames.respname = "hidden'.$nameprefix.'";';
            $jsinit[] = 'var checkboxnames = new Object();';
            $jsinit[] = 'checkboxnames.defaultresponsename = "cb'.$defaultresponsename.'";';
            $jsinit[] = 'checkboxnames.ulname = "'.$ulname.'";';
            $jsinit[] = 'YAHOO.util.Event.addListener("responseform", "click", ddOrderingSetHiddens, hiddennames);';
            $jsinit[] = 'YAHOO.util.Event.addListener("cb'.$defaultresponsename.'", "click", processGradeCheckbox, checkboxnames);';
        }
        // Otherwise it's the readonly version
        else {
            $ulclass .= ' readonly';
        }

        // List class options
        if ($question->options->horizontal) {
            $ulclass .= ' inline';
        }

        // HTML for javascript version

        // Generate the hidden variables that store the responses
        $inputs = array();
        $i = 1;
        foreach ($subquestions as $subquestion) {
            if (!empty($subquestion->questiontext)) {
                $inputs[$subquestion->code] = array();
                $inputs[$subquestion->code]['id'] = 'hidden'.$nameprefix.$subquestion->code;
                $inputs[$subquestion->code]['name'] = $nameprefix.$subquestion->code;
                // If the order is defined by a previous response, use that item order
                if (isset($responses[$subquestion->code]) && $responses[$subquestion->code] > 0) {
                    $inputs[$subquestion->code]['value'] = $responses[$subquestion->code];
                }
                // Otherwise number the items as they appear in the array (the default
                // shuffled order)
                else {
                    $inputs[$subquestion->code]['value'] = $i;
                    $responses[$subquestion->code] = $i;
                }
                $i += 1;
            }
        }

        // Sort the items by response position to display them in the same order as they
        // were submitted in (or in the default shuffled order for the initial version)
        $items = array();
        foreach ($subquestions as $key => $subquestion) {
            $items[$responses[$key]] = $subquestion;
        }
        ksort($items);

        $lis = array();
        foreach ($items as $subquestion) {
            $response = isset($responses[$subquestion->code])
                ? $responses[$key] : '0';

            // If readonly, set up feedback for each item
            $liclass = '';
            if ($options->readonly) {
                $liclass .= ' readonly';
            }

            $feedbackimg = '';
            if ($options->readonly) {
                if ($correctresponses[$subquestion->code]) {
                    $correctresponse = 1;
                }
                else {
                    $correctresponse = 0;
                }

                if ($options->feedback && $response) {
                    if ($state->options->defaultresponse == 'no') {
                        $liclass .= ' '.question_get_feedback_class($correctresponse);
                        $feedbackimg = ' '.question_get_feedback_image($correctresponse);
                    }
                }
            }

            if ($subquestion->questiontext) {
                // Clean up text and replace " with \\" for use in javascript
                $subquestiontext = $this->format_text($subquestion->questiontext,
                        $question->questiontextformat, $cmoptions);
                $subquestiontext = preg_replace('/\r\n/', ' ', $subquestiontext);
                $subquestiontext = preg_replace('/"/', '\\"', $subquestiontext);

                // Add the list element to the output
                $lis[$subquestion->code] = array();
                $lis[$subquestion->code]['class'] = $liclass;
                $lis[$subquestion->code]['id'] = $liname.'_'.$subquestion->code;
                $lis[$subquestion->code]['text'] = $subquestiontext;
                if (preg_match('/[<[a-z].*>/', $subquestiontext)) {
                    $lis[$subquestion->code]['ishtml'] = true;
                }
                if ($feedbackimg) {
                    preg_match('/src="([^"]*)"/', $feedbackimg, $matches);
                    $lis[$subquestion->code]['feedbackimgsrc'] = $matches[1];
                    preg_match('/alt="([^"]*)"/', $feedbackimg, $matches);
                    $lis[$subquestion->code]['feedbackimgalt'] = $matches[1];
                    preg_match('/class="([^"]*)"/', $feedbackimg, $matches);
                    $lis[$subquestion->code]['feedbackimgclass'] = $matches[1];
                }
            }
        }


        // Restore defaultresponse option from state
        $defaultresponsechecked = '';
        $defaultresponsevalue = 'no';
        if ($state->options->defaultresponse == 'yes') {
            $defaultresponsechecked = 'checked="checked"';
            $defaultresponsevalue = 'yes';
        }
        $defaultresponsestr = get_string('defaultresponse', 'qtype_order');

        include("$CFG->dirroot/question/type/order/display.html");
    }

    function grade_responses(&$question, &$state, $cmoptions) {
        $subquestions = &$state->options->subquestions;

        $evaluatedresponses = $this->get_evaluated_responses($question, $state);
        $sumgrade = 0;
        $totalgrade = 0;
        $correctresponses = array();
        foreach ($subquestions as $key => $subquestion) {
            if ($subquestion->questiontext) {
                $totalgrade += 1;
                if ($evaluatedresponses[$key]) {
                    $sumgrade += $subquestion->options->answers[$key]->fraction;
                }
            }
        }

        $state->raw_grade = $sumgrade/$totalgrade;
        if (empty($state->raw_grade)) {
            $state->raw_grade = 0;
        }

        // Make sure we don't assign negative or too high marks
        $state->raw_grade = min(max((float) $state->raw_grade,
                            0.0), 1.0) * $question->maxgrade;
        $state->penalty = $question->penalty * $question->maxgrade;

        // mark the state as graded
        $state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;

        return true;
    }

    function compare_responses($question, $state, $teststate) {
        // If the defaultresponse checkbox is in one state and not the other
        if (count($state->responses) != count($teststate->responses)) {
            return false;
        }

        // Compare individual responses if array lengths are equal
        foreach ($state->responses as $i=>$sr) {
            if (empty($teststate->responses[$i])) {
                if (!empty($state->responses[$i])) {
                    return false;
                }
            } else if ($state->responses[$i] != $teststate->responses[$i]) {
                return false;
            }
        }
    }

    // ULPGC ecastro for stats report
    function get_all_responses($question, $state) {
        $answers = array();
        if (is_array($question->options->subquestions)) {
            foreach ($question->options->subquestions as $aid => $answer) {
                if ($answer->questiontext) {
                    $r = new stdClass;
                    $r->answer = $answer->questiontext . ": " . $answer->answertext;
                    $r->credit = 1;
                    $answers[$aid] = $r;
                }
            }
        }
        $result = new stdClass;
        $result->id = $question->id;
        $result->responses = $answers;
        return $result;
    }

    // ULPGC ecastro
    function get_actual_response($question, $state) {
       $subquestions = &$state->options->subquestions;
       $responses    = &$state->responses;
       $results = array();
       foreach ($subquestions as $key => $sub) {
           foreach ($responses as $ind => $code) {
               if (isset($sub->options->answers[$code])) {
                   $results[$ind] =  $subquestions[$ind]->questiontext . ": " . $sub->options->answers[$code]->answer;
               }
           }
       }
       return $results;
   }

    function response_summary($question, $state, $length=80) {
        // This should almost certainly be overridden
        return substr(implode(', ', $this->get_actual_response($question, $state)), 0, $length);
    }

    /*
     * Backup the data in the question
     *
     * This is used in question/backuplib.php
     */
    function backup($bf, $preferences, $question, $level=6) {

        $status = true;

        $order = get_record('question_order', 'question', $question);
        $status = $status && fwrite($bf, full_tag("HORIZONTAL", 6, false, $order->horizontal));

        $orders = get_records('question_order_sub', 'question', $question, 'id ASC');
        // If there are orders
        if ($orders) {
            $status = fwrite($bf, start_tag("ORDERS", 6, true));
            // Iterate over each order
            foreach ($orders as $order) {
                $status = $status && fwrite($bf, start_tag("ORDER", 7, true));
                // Print order contents
                $status = $status && fwrite($bf, full_tag("ID", 8, false, $order->id));
                $status = $status && fwrite($bf, full_tag("CODE",8,false, $order->code));
                $status = $status && fwrite($bf, full_tag("QUESTIONTEXT", 8, false, $order->questiontext));
                $status = $status && fwrite($bf, full_tag("ANSWERTEXT", 8, false, $order->answertext));
                $status = $status && fwrite($bf, end_tag("ORDER", 7, true));
            }
            $status = $status && fwrite($bf, end_tag("ORDERS", 6, true));
        }
        return $status;
    }

    /*
     * Restores the data in the question
     *
     * This is used in question/restorelib.php
     */
    function restore($oldquestionid, $newquestionid, $info, $restore) {

        $status = true;

        // Get the orders array
        $orders = $info['#']['ORDERS']['0']['#']['ORDER'];

        // We have to build the subquestions field (a list of order_sub id)
        $subquestionsfield = "";
        $infirst = true;

        // Iterate over orders
        for($i = 0; $i < sizeof($orders); $i++) {
            $orderinfo = $orders[$i];

            // We'll need this later!!
            $oldid = backup_todb($orderinfo['#']['ID']['0']['#']);

            // Now, build the question_order_sub record structure
            $ordersub = new stdClass;
            $ordersub->question = $newquestionid;
            $ordersub->code = isset($orderinfo['#']['CODE']['0']['#'])?backup_todb($orderinfo['#']['CODE']['0']['#']):'';
            if (!$ordersub->code) {
                $ordersub->code = $oldid;
            }
            $ordersub->questiontext = backup_todb($orderinfo['#']['QUESTIONTEXT']['0']['#']);
            $ordersub->answertext = backup_todb($orderinfo['#']['ANSWERTEXT']['0']['#']);

            // The structure is equal to the db, so insert the question_order_sub
            $newid = insert_record("question_order_sub", $ordersub);

            // Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                // We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, 'question_order_sub', $oldid, $newid);
                // We have a new order_sub, append it to subquestions_field
                if ($infirst) {
                    $subquestionsfield .= $newid;
                    $infirst = false;
                } else {
                    $subquestionsfield .= ",".$newid;
                }
            } else {
                $status = false;
            }
        }

        // We have created every order_sub, now create the order
        $order = new stdClass;
        $order->question = $newquestionid;
        $order->subquestions = $subquestionsfield;
        $order->horizontal = $info['#']['HORIZONTAL']['0']['#'];

        // The structure is equal to the db, so insert the question_order_sub
        $newid = insert_record("question_order", $order);

        if (!$newid) {
            $status = false;
        }

        return $status;
    }

    function restore_map($oldquestionid, $newquestionid, $info, $restore) {

        $status = true;

        // Get the ordering array
        $orders = $info['#']['ORDERS']['0']['#']['ORDER'];

        // We have to build the subquestions field (a list of order_sub id)

        // Iterate over orderings
        for($i = 0; $i < sizeof($orders); $i++) {
            $orderinfo = $orders[$i];

            // We'll need this later!!
            $oldid = backup_todb($orderinfo['#']['ID']['0']['#']);

            // Now, build the question_order_sub record structure
            $ordersub->question = $newquestionid;
            $ordersub->questiontext = backup_todb($orderinfo['#']['QUESTIONTEXT']['0']['#']);
            $ordersub->answertext = backup_todb($orderinfo['#']['ANSWERTEXT']['0']['#']);

            // If we are in this method is because the question exists in DB, so its
            // order_sub must exist too.
            // Now, we are going to look for that order_sub in DB and to create the
            // mappings in backup_ids to use them later where restoring states (user level).

            // Get the order_sub from DB (by question, questiontext and answertext)
            $dbordersub = get_record ("question_order_sub","question", $newquestionid,
                                                      "questiontext", $ordersub->questiontext,
                                                      "answertext", $ordersub->answertext);
            print_r($dbordersub);
            // Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            // We have the database ordersub, so update backup_ids
            if ($dbordersub) {
                // We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, "question_order_sub", $oldid, $dbordersub->id);
            } else {
                $status = false;
            }
        }

        return $status;
    }

    /**
     * Decode links in question type specific tables.
     * @return bool success or failure.
     */
    function decode_content_links_caller($questionids, $restore, &$i) {
        $status = true;

        // Decode links in the question_order_sub table.
        if ($subquestions = get_records_list('question_order_sub', 'question',
                implode(',',  $questionids), '', 'id, questiontext')) {

            foreach ($subquestions as $subquestion) {
                $questiontext = restore_decode_content_links_worker($subquestion->questiontext, $restore);
                if ($questiontext != $subquestion->questiontext) {
                    $subquestion->questiontext = addslashes($questiontext);
                    if (!update_record('question_order_sub', $subquestion)) {
                        $status = false;
                    }
                }

                // Do some output.
                if (++$i % 5 == 0 && !defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if ($i % 100 == 0) {
                        echo "<br />";
                    }
                    flush(300);
                }
            }
        }

        return $status;
    }

    function import_from_xml($data, $question, $format, $extra=null) {
        if (!array_key_exists('@', $data)) {
            return false;
        }
        if (!array_key_exists('type', $data['@'])) {
            return false;
        }
        if ($data['@']['type'] == 'order') {
            $question = $format->import_headers($data);

            // header parts particular to ordering
            $question->qtype = 'order';
            $question->horizontal = $format->getpath($data, array('#', 'horizontal', 0, '#'), 0);
            if ($question->horizontal != 0 and $question->horizontal != 1) {
                $question->horizontal = 0;
            }

            // get subquestions
            $subquestions = $data['#']['subquestion'];
            $question->subquestions = array();
            $question->subanswers = array();

            // run through subquestions
            foreach ($subquestions as $subquestion) {
                $question->subquestions[] = $format->getpath($subquestion, array('#','text',0,'#'), '', true);
                $question->subanswers[] = $format->getpath($subquestion, array('#','answer',0,'#','text',0,'#'), '', true);
            }

            $subanswersvalues = array_values($question->subanswers);
            sort($subanswersvalues);

            for ($i = 0; $i < count($subanswersvalues); $i++) {
                if ($subanswersvalues[$i] != $i + 1) {
                    echo "Incorrect ordering sequence.";
                    return false;
                }
            }

            return $question;
        }

        return false;
    }

    function export_to_xml($question, $format, $extra=null) {
        $expout = '';

        foreach ($question->options->subquestions as $subquestion) {
            $expout .= " <subquestion>\n";
            $expout .= $format->writetext($subquestion->questiontext, 2);
            $expout .= "   <answer>\n";
            $expout .= $format->writetext($subquestion->answertext, 4);
            $expout .= "   </answer>\n";
            $expout .= " </subquestion>\n";
        }

        $expout .= " <horizontal>" . $question->options->horizontal . "</horizontal>\n";

        return $expout;
    }

    function import_from_gift($data, $question, $format, $extra=null) {
        if ($extra[0] == '>') {
            $itemstring = substr($extra, 1);
            $items = explode('=', $itemstring);
            if (isset($items[0])) {
                $items[0] = trim($items[0]);
            }
            if (empty($items[0])) {
                array_shift($items);
            }

            if (count($items) > 2) {
                $question->qtype = 'order';
                $question->horizontal = 0;
                $question->subquestions = array();
                $question->subanswers = array();

                $subqcount = 0;
                foreach ($items as $item) {
                    $question->subquestions[$subqcount] = addslashes(trim($format->escapedchar_post($item)));
                    $question->subanswers[$subqcount] = $subqcount + 1;

                    $subqcount++;
                }

                return $question;
            }
        }

        return false;
    }

    function export_to_gift($question, $format, $extra=null) {
        $expout = '';

        // get question text format
        $textformat = $question->questiontextformat;
        $tfname = '';
        if ($textformat!=FORMAT_MOODLE) {
            $tfname = text_format_name((int) $textformat);
            $tfname = "[$tfname]";
        }

        $expout .= '::'.$format->repchar($question->name).'::';
        $expout .= $tfname;
        $expout .= $format->repchar($question->questiontext, $textformat) . " {>\n";

        foreach($question->options->subquestions as $subquestion) {
            $expout .= ' ='.$format->repchar($subquestion->questiontext)."\n";
        }
        $expout .= "}\n";

        return $expout;
    }
}

// Register this question type with the system.
question_register_questiontype(new question_order_qtype());
?>

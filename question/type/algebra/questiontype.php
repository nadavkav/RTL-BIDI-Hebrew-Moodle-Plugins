<?php

// Moodle algebra question type class
// Author: Roger Moore <rwmoore 'at' ualberta.ca>
// License: GNU Public License version 3
    
    
require_once("$CFG->dirroot/question/type/questiontype.php");
require_once("$CFG->dirroot/question/type/algebra/parser.php");
require_once("$CFG->dirroot/question/type/algebra/xmlrpc-utils.php");

/**
 * ALGEBRA QUESTION TYPE CLASS
 *
 * This class contains some special features in order to make the
 * question type embeddable within a multianswer (cloze) question
 *
 * This question type behaves like shortanswer in most cases.
 * Therefore, it extends the shortanswer question type...
 * @package questionbank
 * @subpackage questiontypes
 */
class question_algebra_qtype extends default_questiontype {

    function name() {
        return 'algebra';
    }

    /**
     * Defines the table which extends the question table. This allows the base questiontype
     * to automatically save, backup and restore the extra fields.
     *
     * @return an array with the table name (first) and then the column names (apart from id and questionid)
     */
    function extra_question_fields() {
        return array('question_algebra',
                     'compareby',        // Name of comparison algorithm to use
                     'variables',        // Comma separated list of variable names in the question
                     'nchecks',          // Number of evaluate checks to make when comparing by evaluation
                     'tolerance',        // Max. fractional difference allowed for evaluation checks
                     'allowedfuncs',     // Comma separated list of functions allowed in responses
                     'disallow',         // Response which may be correct but which is not allowed
                     'answerprefix'      // String which is placed in front of the asnwer box
                     );
    }

	/**
	 * Saves the questions answers to the database
	 *
	 * This is called by {@link save_question_options()} to save the answers to the question to
	 * the database from the data in the submitted form. This method should probably be in the 
	 * questin base class rather than in the algebra subclass since the code is common to multiple
	 * question types and originally comes from the shortanswer question type. The method returns
     * a list of the answer ID written to the database or throws an exception if an error is detected.
	 *
	 * @param object $question  This holds the information from the editing form,
	 *                          it is not a standard question object.
	 * @return array of answer IDs which were written to the database
	 */
    function save_question_answers($question) {
		// Create the results class
        $result = new stdClass;
		
		// Get all the old answers from the database as an array
        if (!$oldanswers = get_records('question_answers', 'question', $question->id, 'id ASC')) {
            $oldanswers = array();
        }
		
		// Create an array of the answer IDs for the question
        $answers = array();
		// Set the maximum answer fraction to be -1. We will check this at the end of our
		// loop over the questions and if it is not 100% (=1.0) then we will flag an error
        $maxfraction = -1;
		
        // Loop over all the answers in the question form and write them to the database
        foreach ($question->answer as $key => $dataanswer) {
			// Check to see that there is an answer and skip any which are empty
            if ($dataanswer == '') {
                continue;
            }
            // Get the old answer from the array and overwrite what is required, if there 
            // is not old answer then we skip to the 'else' clause
            if ($oldanswer = array_shift($oldanswers)) {  // Existing answer, so reuse it
                $answer = $oldanswer;
                $answer->answer   = trim($dataanswer);
                $answer->fraction = $question->fraction[$key];
                $answer->feedback = $question->feedback[$key];
                // Update the record in the database to denote this change.
                if (!update_record('question_answers', $answer)) {
                    throw new Exception("Could not update quiz answer! (id=$answer->id)");
                }
            } 
            // This is a completely new answer so we have to create a new record
            else {
                $answer = new stdClass;
                $answer->answer   = trim($dataanswer);
                $answer->question = $question->id;
                $answer->fraction = $question->fraction[$key];
                $answer->feedback = $question->feedback[$key];
                // Insert a new record into the database table
                if (!$answer->id = insert_record('question_answers', $answer)) {
                    throw new Exception('Could not insert quiz answer!');
                }
            }
            // Add the answer ID to the array of IDs
            $answers[] = $answer->id;
            // Increase the value of the maximum grade fraction if needed
            if ($question->fraction[$key] > $maxfraction) {
                $maxfraction = $question->fraction[$key];
            }
        }     // end loop over answers
		
		// Perform sanity check on the maximum fractional grade which should be 100%
        if ($maxfraction != 1) {
            $maxfraction = $maxfraction * 100;
            throw new Exception(get_string('fractionsnomax', 'quiz', $maxfraction));
        }
		
		// Finally we are all done so return the result!
		return $answers;
	}
	
	/**
	 * Saves the questions variables to the database
	 *
	 * This is called by {@link save_question_options()} to save the variables of the question to
	 * the database from the data in the submitted form. The method returns a list of the variable
	 * IDs written to the database or, in the event of an error, throws an exception.
	 *
	 * @param object $question  This holds the information from the editing form,
	 *                          it is not a standard question object.
	 * @return array of variable object IDs
	 */
    function save_question_variables($question) {
		// Create the results class
        $result = new stdClass;
		
		// Get all the old answers from the database as an array
        if (!$oldvars = get_records('question_algebra_variables', 'question', $question->id, 'id ASC')) {
            $oldvars = array();
        }
		
		// Create an array of the variable IDs for the question
        $variables = array();
		
        // Loop over all the answers in the question form and write them to the database
        foreach ($question->variable as $key => $varname) {
			// Check to see that there is a variable and skip any which are empty
            if ($varname == '') {
                continue;
            }
            // Get the old variable from the array and overwrite what is required, if there 
            // is no old variable then we skip to the 'else' clause
            if ($oldvar = array_shift($oldvars)) {  // Existing variable, so reuse it
                $var = $oldvar;
                $var->name = trim($varname);
                $var->min  = trim($question->varmin[$key]);
                $var->max  = trim($question->varmax[$key]);
                // Update the record in the database to denote this change.
                if (!update_record('question_algebra_variables', $var)) {
                    throw new Exception("Could not update algebra question variable (id=$var->id)");
                }
            }
            // This is a completely new answer so we have to create a new record
            else {
                $var = new stdClass;
                $var->name     = trim($varname);
                $var->question = $question->id;
                $var->min      = trim($question->varmin[$key]);
                $var->max      = trim($question->varmax[$key]);
                // Insert a new record into the database table
                if (!$var->id = insert_record('question_algebra_variables', $var)) {
                    throw new Exception("Could not insert algebra question variable '$varname'!");
                }
            }
            // Add the variable ID to the array of IDs
            $variables[] = $var->id;
        }   // end loop over variables
		
		// Finally we are all done so return the result!
		return $variables;
	}
	
    /**
	 * Saves question-type specific options
	 *
	 * This is called by {@link save_question()} to save the question-type specific data from a
	 * submitted form. This method takes the form data and formats into the correct format for
	 * writing to the database. It then calls the parent method to actually write the data.
	 *
	 * @param object $question  This holds the information from the editing form,
	 *                          it is not a standard question object.
	 * @return object $result->error or $result->noticeyesno or $result->notice
	 */
    function save_question_options($question) {
		// Start a try block to catch any exceptions generated when we attempt to parse and
        // then add the answers and variables to the database
        try {
            // Loop over all the answers in the question form and parse them to generate
            // a parser string. This ensures a constant formatting is stored in the database
            foreach ($question->answer as &$answer) {
                $expr=$this->parse_expression($answer);
                $answer=$expr->sage();
            }
            // Now we need to write out all the answers to the question to the database
            $answers=$this->save_question_answers($question);
            $question->answers=implode(',',$answers);
            // The next task is to write out all the variables associated with the question
            $variables=$this->save_question_variables($question);
            $question->variables=implode(',',$variables);
        } catch (Exception $e) {
            // Error when adding answers orvariables to the database so create a result class
            // and put the error string in the error member funtion and then return the class
            // This keeps us compatible with the existing save_question_options methods.
            $result=new stdClass;
            $result->error=$e->getMessage();
            return $result;
        }
        
        // Process the allowed functions field. This code just sets up the variable, it is saved
        // in the parent class' save_question_options method called at the end of this method
        // Look for the 'all' option. If we find it then set the string to an empty value
        if(array_key_exists('all',$question->allowedfuncs)) {
            $question->allowedfuncs='';
        }
        // Not all functions are allowed so set allowed functions to those which are
        else {
            // Create a comma separated string of the function names which are stored in the
            // keys of the array
            $question->allowedfuncs=implode(',',array_keys($question->allowedfuncs));
        }
		
		// Call the parent method to write the extensions fields to the database. This either returns null
		// or an error object so if we get anything then return it otherwise return our existing
		if($res=parent::save_question_options($question)) {
			return $res;
		}
		// Otherwise just return true - this mimics the shortanswer return format
		else {
			return true;
		}
	}
	
    /**
	 * Loads the question type specific options for the question.
	 *
	 * This function loads the compare algorithm type, disallowed strings and variables
	 * into the class from the database table in which they are stored. It first uses the
	 * parent class method to get the database information.
	 *
	 * @param object $question The question object for the question. This object
	 *                         should be updated to include the question type
	 *                         specific information (it is passed by reference).
	 * @return bool            Indicates success or failure.
	 */
    function get_question_options(&$question) {
		// Get the information from the database table. If this fails then immediately bail.
        // Note unlike the save_question_options base class method this method DOES get the question's
        // answers along with any answer extensions
		if(!parent::get_question_options($question)) {
			return false;
		}
		
		// Check that we have answers and if not then bail since this question type requires answers
		if(count($question->options->answers)==0) {
			notify('Failed to load question answers from the table question_answers for questionid ' .
				   $question->id);
			return false;
		}

        // Now get the variables from the database as well
        $question->options->variables = 
            get_records('question_algebra_variables', 'question', $question->id, 'id ASC');
		// Check that we have variables and if not then bail since this question type requires variables
		if(count($question->options->variables)==0) {
			notify('Failed to load question variables from the table question_algebra_variables '.
                   "for questionid $question->id");
			return false;
		}
        
        // Check to see if there are any allowed functions
        if($question->options->allowedfuncs!='') {
            // Extract the allowed functions as an array
            $question->options->allowedfuncs=explode(',',$question->options->allowedfuncs);
        }
        // Otherwise just create an empty array
        else {
            $question->options->allowedfuncs=array();
        }
        
        // Everything worked so return true
        return true;
	}
		
	/**
	 * Prints the main content of the question including any interactions
	 *
	 * This function prints the main content of the question including the
	 * interactions for the question in the state given. The last graded responses
	 * are printed or indicated and the current responses are selected or filled in.
	 * Any names (eg. for any form elements) are prefixed with $question->name_prefix.
	 * This method is called from the print_question method. The algebra question type
	 * changes this from the default method to include a "display formula" button which
	 * interprets the formula in the response box and gives a graphical representation
	 * for the student to check.
	 *
	 * @param object $question The question to be rendered. Question type
	 *                         specific information is included. The name
	 *                         prefix for any named elements is in ->name_prefix.
	 * @param object $state    The state to render the question in. The grading
	 *                         information is in ->grade, ->raw_grade and
	 *                         ->penalty. The current responses are in
	 *                         ->responses. This is an associative array (or the
	 *                         empty string or null in the case of no responses
	 *                         submitted). The last graded state is in
	 *                         ->last_graded (hence the most recently graded
	 *                         responses are in ->last_graded->responses). The
	 *                         question type specific information is also
	 *                         included.
	 *                         The state is passed by reference because some adaptive
	 *                         questions may want to update it during rendering
	 * @param object $cmoptions
	 * @param object $options  An object describing the rendering options.
	 */
    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        global $CFG;
		/// This implementation is copied from short answer
        $readonly = empty($options->readonly) ? '' : 'readonly="readonly"';
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->para = false;
        $nameprefix = $question->name_prefix;
		
        // Print the text associated with the question
        $questiontext = format_text($question->questiontext,
									$question->questiontextformat,
									$formatoptions, $cmoptions->course);

		// Get the image, if any, associated with the question
        $image = get_question_image($question);
		
		// Sets the value of the input box for the question to the current student
		// response if there is one
        if (isset($state->responses['']) && $state->responses[''] != '') {
            $value = ' value="'.s($state->responses[''], true).'" ';
        } else {
            $value = ' value="" ';
        }
		// Defines the name of the input box where the student enters a response for the question
        $inputname = ' name="'.$nameprefix.'" ';
		
        $feedback = '';
        $class = '';
        $feedbackimg = '';
		
        if ($options->feedback) {
            $class = question_get_feedback_class(0);
            $feedbackimg = question_get_feedback_image(0);
            // Parse the response here since this saves time parsing once for each comparison
			$response=$this->parse_expression($state,$question);
            // Loop over all the answers to the question
            foreach($question->options->answers as $answer) {
                // Compare the response against the current answer
                if ($this->test_response($question, $response, $answer)) {
                    // Answer was correct or partially correct.
                    $class = question_get_feedback_class($answer->fraction);
                    $feedbackimg = question_get_feedback_image($answer->fraction);
                    if ($answer->feedback) {
                        $feedback = format_text($answer->feedback, true, $formatoptions, $cmoptions->course);
                    }
                    break;
                }
            }
        }
		
		// The name for the iframe which displays the rendered formula
		$iframename = ' name="'.$nameprefix.'if"';
		// Name of the javascript function which causes the entered formula to be rendered
		$df_name=$nameprefix.'_display';
        // Create an array of variable names to use when displaying the function entered
        $varnames=array();
        if($question and isset($question->options->variables)) {
            foreach($question->options->variables as $var) {
                $varnames[]=$var->name;
            }
        }
        $varnames=implode(',',$varnames);
		// Javascript function which the button uses to display the rendering
		// This function sents the source of the iframe to the 'displayformula.php' script giving
		// it an argument of the formula entered by the student.
		$displayfunction =
			'function '.$df_name."() {\n".
            '    var text="vars='.$varnames.'&expr="+escape(document.getElementsByName("'.$nameprefix.'")[0].value);'."\n".
			"    if(text.length != 0) {\n".
		    '      document.getElementsByName("'.$nameprefix.'if")[0].src="'.
			$CFG->wwwroot.'/question/type/algebra/displayformula.php?"+'.
			'text.replace(/\+/g,"%2b")'."\n".
			"    }\n".
			"  }\n";
        // Include the HTML/php file which uses the variables above to render the question
        include("$CFG->dirroot/question/type/algebra/display.html");
    }

    /**
	 * Compares two question states for equivalence of the student's responses
	 *
	 * The responses for the two states must be examined to see if they represent
	 * equivalent answers to the question by the student. This method will be
	 * invoked for each of the previous states of the question before grading
	 * occurs. If the student is found to have already attempted the question
	 * with equivalent responses then the attempt at the question is ignored;
	 * grading does not occur and the state does not change. Thus they are not
	 * penalized for this case.
	 * @return boolean
	 * @param object $question  The question for which the states are to be
	 *                          compared. Question type specific information is
	 *                          included.
	 * @param object $state     The state of the question. The responses are in
	 *                          ->responses. This is the only field of $state
	 *                          that it is safe to use.
	 * @param object $teststate The state whose responses are to be
	 *                          compared. The state will be of the same age or
	 *                          older than $state. If possible, the method should
	 *                          only use the field $teststate->responses, however
	 *                          any field that is set up by restore_session_and_responses
	 *                          can be used.
	 */	
    function compare_responses(&$question, $state, $teststate) {
		// Check that both states have valid responses
        if (!isset($state->responses['']) or !isset($teststate->responses[''])) {
            // At last one of the states did not have a response set so return false by default
            return false;
        }
        // Parse the state response
        $expr=$this->parse_expression($state,$question);
        // Parse the test state response
        $testexpr=$this->parse_expression($teststate,$question);
        // The type of comparison done depends on the comparision algorithm selected by
        // the question. Use the defined algorithm to select which comparison function
        // to call...
        if($question->options->compareby == 'sage') {
            // Uses an XML-RPC server with SAGE to perform a full symbollic comparision
            return $this->test_response_by_sage($question,$expr,$testexpr);
        } else if($question->options->compareby == 'eval') {
            // Tests the response by evaluating it for a certain range of each variable
            return $this->test_response_by_evaluation($question,$expr,$testexpr);
        } else {
            // Tests the response by performing a simple parse tree equivalence algorithm
            return $this->test_response_by_equivalence($question,$expr,$testexpr);
        }
    }

    /**
	 * Checks whether a response matches a given answer
	 *
	 * This method will look to see which type of comparison algorithm the question uses
	 * and will then call the appropriate function to implement it.
	 *
	 * @return boolean true if the response matches the answer, false otherwise
	 */
    function test_response(&$question, &$response, $answer) {
        // Deal with the match anything answer by returning true
        if ($answer->answer == '*') {
            return true;
        }
		// Parse the response string here if needed - sometimes this method will be called
        // with unparsed responses. This is not very efficient - a better way
		// would be to replace all the functions which call 'test_response' and have
		// them parse the response once and then pass that to all tests. To allow for
		// this use the parse expression call which checks for the type of argument
        $expr=$this->parse_expression($response,$question);
        // Check that there is a response and if not return false. We do this here
        // because even an empty response should match a widlcard answer.
        if(is_a($expr,'qtype_algebra_parser_nullterm')) {
            return false;
        }
        // Now parse the answer
        $ansexpr=$this->parse_expression($answer->answer,$question);
		// The type of comparison done depends on the comparision algorithm selected by
		// the question. Use the defined algorithm to select which comparison function
		// to call...
		if($question->options->compareby == 'sage') {
			// Uses an XML-RPC server with SAGE to perform a full symbollic comparision
			return $this->test_response_by_sage($question,$expr,$ansexpr);
		} else if($question->options->compareby == 'eval') {
			// Tests the response by evaluating it for a certain range of each variable
			return $this->test_response_by_evaluation($question,$expr,$ansexpr);
		} else {
			// Tests the response by performing a simple parse tree equivalence algorithm
			return $this->test_response_by_equivalence($question,$expr,$ansexpr);
		}
	}
	
    /**
	 * Checks whether a response matches a given answer using SAGE
	 *
	 * This method will compare the given response to the given answer using the SAGE
	 * open source algebra computation software. The software is run by a remote
	 * XML-RPC server which is called with both the asnwer and the response and told to
	 * compare the two algebraic expressions.
	 *
	 * @return boolean true if the response matches the answer, false otherwise
	 */
	function test_response_by_sage(&$question, &$response, $answer) {
		// TODO: Store server information in the Moodle configuration
		$request=array(
					   'host'   => 'localhost',
					   'port'   => 7777,
					   'uri'    => ''
		);
		// Sets the name of the method to call to full_symbolic_compare
		$request['method']='full_symbolic_compare';
        // Get a list of all the variables to declare
        $vars=$response->get_variables();
        $vars=array_merge($vars,array_diff($vars,$answer->get_variables()));
		// Sets the arguments to the sage string of the response and the list of variables
		$request['args']=array($answer->sage(),$response->sage(),$vars);
		// Calls the XML-RPC method on the server and returns the response
		return xu_rpc_http_concise($request)==0;
    }
	
    /**
	 * Checks whether a response matches a given answer using an evaluation method
	 *
	 * This method will compare the given response to the given answer by evaluating both
	 * for given values of the variables. Each variable must have a predefined range over
	 * which it can be checked and then both expressions will be evalutated several times
	 * using values randomly chosen to be within the range.
	 *
	 * @return boolean true if the response matches the answer, false otherwise
	 */
	function test_response_by_evaluation(&$question, &$response, $answer) {
        // Flag used to denote mismatch in response and answer
        $same=true;
        // Run the evaluation loop 10 times with different random variables...
        for($i=0;$i<$question->options->nchecks;$i++) {
            // Create an array to store the values of all the variables
            $values=array();
            // Loop over all the variables in the question
            foreach($question->options->variables as $var) {
                // Set the value of the variable to a random number between the min and max
                $values[$var->name]=$var->min+lcg_value()*abs($var->max-$var->min);
            }
            $resp_value=$response->evaluate($values);
            $ans_value=$answer->evaluate($values);
            // Return false if only one of the reponse or answer gives NaN
            if(is_nan($resp_value) xor is_nan($ans_value)) {
                return false;
            }
            // Return false if only one of the reponse or answer is infinite
            if(is_infinite($resp_value) xor is_infinite($ans_value)) {
                return false;
            }
            // Use the fractional difference method if the answer has a value
            // which is clearly distinguishable from zero
            if(abs($ans_value)>1e-300) {
                // Get the difference between the response and answer evaluations
                $diff=abs(($resp_value-$ans_value)/$ans_value);
            }
            // Otherwise use an arbitrary minimum value
            else {
                // Get the difference between the response and answer evaluations
                $diff=abs(($resp_value-$ans_value)/1e-300);
            }
            // Check to see if the difference is greater than tolerance
            if($diff > $question->options->tolerance) {
                // Return false since the formulae have been shown not to be the same
                return false;
            }
        }
        // We made it through the loop so now return true
		return true;
	}

    /**
	 * Checks whether a response matches a given answer using a simple equivalence algorithm
	 *
	 * This method will compare the given response to the given answer by simply checking to
	 * see if the two parse trees are equivalent. This allows for a slightly more sophisticated
	 * check than a simple text compare but will not, neccessarily, catch two equivalent but
	 * different algebraic expressions.
	 *
	 * @return boolean true if the response matches the answer, false otherwise
	 */
	function test_response_by_equivalence(&$question, &$response, $answer) {
		// Use the parser's equivalent method to see if the response is the same as the answer
		return $response->equivalent($answer);
	}
	
	/**
	  * Checks if the response given is correct and returns the id
	  *
	  * @return int             The ID number for the stored answer that matches the response
	  *                         given by the user in a particular attempt.
	  * @param object $question The question for which the correct answer is to
	  *                         be retrieved. Question type specific information is
	  *                         available.
	  * @param object $state    The state object that corresponds to the question,
	  *                         for which a correct answer is needed. Question
	  *                         type specific information is included.
	  */
    function check_response(&$question, &$state) {
		// Convert the given response into a parse tree
		$response = $this->parse_expression($state,$question);
        $answers = &$question->options->answers;
		// Loop over all the answers and test the response for a match, returning
		// the ID of the first answer which matches the response
        foreach($answers as $aid => $answer) {
            if($this->test_response($question, $response, $answer)) {
                return $aid;
            }
        }
		// No match so return false here
        return false;
    }

    /**
     * Imports the question from Moodle XML format.
     *
     * This method is called by the format class when importing an algebra question from the 
     * Moodle XML format.
     *
     * @param $data structure containing the XML data
     * @param $question question object to fill: ignored by this function (assumed to be null)
     * @param $format format class exporting the question
     * @param $extra extra information (not required for importing this question in this format)
     * @return text string containing the question data in XML format
     */
	function import_from_xml(&$data,&$question,&$format,&$extra) {
        // Import the common question headers
        $qo = $format->import_headers($data);
        // Set the question type
        $qo->qtype='algebra';
        
        $qo->compareby = $format->getpath($data, array('#','compareby',0,'#'),'eval');
        $qo->tolerance = $format->getpath($data, array('#','tolerance',0,'#'),'0');
        $qo->nchecks   = $format->getpath($data, array('#','nchecks',0,'#'),'10');
        $qo->disallow  = $format->getpath($data, array('#','disallow',0,'#','text',0,'#'),'',true);
        $allowedfuncs  = $format->getpath($data, array('#','allowedfuncs',0,'#'), '');
        if($allowedfuncs=='') {
            $qo->allowedfuncs=array('all' => 1);
        }
        // Need to separate the allowed functions into an array of strings and then
        // flip the values of this array into the keys because this is what the
        // save options method requires
        else {
            $qo->allowedfuncs=array_flip(explode(',',$allowedfuncs));
        }
        $qo->answerprefix = $format->getpath($data, array('#','answerprefix',0,'#','text',0,'#'),'',true);
        
        // Import all the answers
        $answers = $data['#']['answer'];
        $a_count = 0;
        // Loop over each answer block found in the XML
        foreach($answers as $answer) {
            // Use the common answer import function in the format class to load the data
            $ans = $format->import_answer($answer);
            $qo->answer[$a_count] = $ans->answer;
            $qo->fraction[$a_count] = $ans->fraction;
            $qo->feedback[$a_count] = $ans->feedback;
            ++$a_count;
        }
        
        // Import all the variables
        $vars = $data['#']['variable'];  
        $v_count = 0;
        // Loop over each answer block found in the XML
        foreach($vars as $var) {
            $qo->variable[$v_count] = $format->getpath($var, array('@','name'),0);
            $qo->varmin[$v_count]   = $format->getpath($var, array('#','min',0,'#'),'0',false,get_string('novarmin','qtype_algebra'));
            $qo->varmax[$v_count]   = $format->getpath($var, array('#','max',0,'#'),'0',false,get_string('novarmax','qtype_algebra'));
            ++$v_count;
        }

        return $qo;
    }
    
    
    /**
     * Exports the question to Moodle XML format.
     *
     * This method is called by the format class when exporting an algebra question into then
     * Moodle XML format.
     *
     * @param $question question to be exported into XML format
     * @param $format format class exporting the question
     * @param $extra extra information (not required for exporting this question in this format)
     * @return text string containing the question data in XML format
     */
	function export_to_xml(&$question,&$format,&$extra) {
        $expout='';
        // Create a text string of the allowed functions from the array
        $allowedfuncs=implode(',',$question->options->allowedfuncs);
        // Write out all the extra fields belonging to the algebra question type
        $expout .= "    <compareby>{$question->options->compareby}</compareby>\n";
        $expout .= "    <tolerance>{$question->options->tolerance}</tolerance>\n";
        $expout .= "    <nchecks>{$question->options->nchecks}</nchecks>\n";
        $expout .= "    <disallow>".$format->writetext($question->options->disallow,1,true)."</disallow>\n";
        $expout .= "    <allowedfuncs>$allowedfuncs</allowedfuncs>\n";
        $expout .= "    <answerprefix>".$format->writetext($question->options->answerprefix,1,true).
            "</answerprefix>\n";
        // Loop over all the answers to the question and write out the text, feedback and fraction
        foreach ($question->options->answers as $answer) {
            $percent = 100 * $answer->fraction;
            $expout .= "<answer fraction=\"$percent\">\n";
            $expout .= $format->writetext($answer->answer,2,true);
            $expout .= "    <feedback>".$format->writetext($answer->feedback)."</feedback>\n";
            $expout .= "</answer>\n";
        }
        // Loop over all the variables for the question and write out all their details
        foreach ($question->options->variables as $var) {
            $percent = 100 * $answer->fraction;
            $expout .= "<variable name=\"{$var->name}\">\n";
            $expout .= "    <min>{$var->min}</min>\n";
            $expout .= "    <max>{$var->max}</max>\n";
            $expout .= "</variable>\n";
        }
        return $expout;
    }
    
    
	/**
	  * Parses the given expression with the parser if required.
	  *
	  * This method will check to see if the argument it is given is already a parsed
	  * expression and if not will attempt to parse it.
	  *
      * @param $expr expression which will be parsed
      * @param $question question containing the expression or null if none
	  * @return top term of the parse tree or a string if an exception is thrown
	  */
	function parse_expression($expr,&$question=null) {
        // Check to see if this is already a parsed expression
        if(is_a($expr,'qtype_algebra_parser_term')) {
            // It is a parsed expression so simply return it
            return $expr;
        }
        // Check whether we have a state object or a simple string. If a state
        // then replace it with the response string
        if(isset($expr->responses[''])) {
            $expr=$expr->responses[''];
        }
        // Create an array of variable names for the parser from the question if defined
        $varnames=array();
        if($question and isset($question->options->variables)) {
            foreach($question->options->variables as $var) {
                $varnames[]=$var->name;
            }
        }
		// We now assume that we have a string to parse. Create a parser instance to
        // to this and return the parser expression at the top of the parse tree
		$p=new qtype_algebra_parser;
        // Perform the actual parsing inside a try-catch block so that any exceptions
        // can be caught and converted into errors
		try {
			return $p->parse($expr,$varnames);
		} catch(Exception $e) {
			// If the expression cannot be parsed then return a null term. This will
            // make Moodle treat the answer as wrong.
            // TODO: Would be nice to have support for 'invalid answer' in the quiz
            // engine since an unparseable response is usually caused by a silly typo
			return new qtype_algebra_parser_nullterm;
		}
	}
	
    // Gets all the question responses
    function get_all_responses(&$question, &$state) {
        $result = new stdClass;
        $answers = array();
		// Loop over all the answers
        if (is_array($question->options->answers)) {
            foreach ($question->options->answers as $aid=>$answer) {
                $r = new stdClass;
                $r->answer = $answer->answer;
                $r->credit = $answer->fraction;
                $answers[$aid] = $r;
            }
        }
        $result->id = $question->id;
        $result->responses = $answers;
        return $result;
    }

	
    /// BACKUP FUNCTIONS ////////////////////////////

    /**
     * Backs up all the variables used by the question.
     *
     * This method is called by the general backup method and will save all the information
     * on the variables used by the question.
     *
     * @param $bf the backup file to write the information to
     * @param $preferences backup preferences in effect (not used)
     * @param $question the ID number of the question being backed up
     * @param $level the indentation level of the data being written
     * 
     * @return bool true if the backup was successful, false if it failed.
     */
    function backup_variables($bf,$preferences,$question, $level = 7) {
        $status = true;
        $vars = get_records('question_algebra_variables', 'question', $question, 'id ASC');
        // Check to see whether there are any variables to write out
        if ($vars) {
            $status = $status && fwrite ($bf,start_tag('VARLIST',$level,true));
            // Iterate over each variable and write out its fields
            foreach ($vars as $var) {
                $status = $status && fwrite ($bf,start_tag('VARIABLE',$level + 1,true));
                // Print the variable's name and min and max values
                fwrite ($bf,full_tag('NAME',$level + 2, false, $var->name));
                fwrite ($bf,full_tag('MIN', $level + 2, false, $var->min ));
                fwrite ($bf,full_tag('MAX', $level + 2, false, $var->max ));
                $status = $status && fwrite ($bf,end_tag('VARIABLE',$level + 1,true));
            }
            $status = $status && fwrite ($bf,end_tag('VARLIST',$level,true));
        }
        return $status;
    }
    
    /**
     * Backup the data in the question to a backup file.
     *
     * This function is used by question/backuplib.php to create a copy of the data
     * in the question so that it can be restored at a later date. The method writes
     * all the supplementary algebra type-specific information from the question_algebra
     * data base table as well as writing out all the variables used by the question.
     * It also uses the question_backup_answers function from question/backuplib.php to
     * backup all the question answers.
     *
     * @param $bf the backup file to write the information to
     * @param $preferences backup preferences in effect (not used)
     * @param $question the ID number of the question being backed up
     * @param $level the indentation level of the data being written
     * 
     * @return bool true if the backup was successful, false if it failed.
     */
    function backup($bf,$preferences,$question,$level=6) {
        // Set the devault return value, $status, to be true
        $status = true;
        $algebraqs = get_records('question_algebra','questionid',$question,'id ASC');
        // If there are algebra questions
        if ($algebraqs) {
            // Iterate over each algebra question
            foreach ($algebraqs as $algebra) {
                $status = $status && fwrite ($bf,start_tag('ALGEBRA',$level,true));
                // Print algebra question contents
                fwrite ($bf,full_tag('COMPAREBY',    $level+1, false, $algebra->compareby    ));
                fwrite ($bf,full_tag('VARIABLES',    $level+1, false, $algebra->variables    ));
                fwrite ($bf,full_tag('NCHECKS',      $level+1, false, $algebra->nchecks      ));
                fwrite ($bf,full_tag('TOLERANCE',    $level+1, false, $algebra->tolerance    ));
                fwrite ($bf,full_tag('ALLOWEDFUNCS', $level+1, false, $algebra->allowedfuncs ));
                fwrite ($bf,full_tag('DISALLOW',     $level+1, false, $algebra->disallow     ));
                fwrite ($bf,full_tag('ANSWERPREFIX', $level+1, false, $algebra->answerprefix ));
                // Backup all the variables
                $status = $status && $this->backup_variables($bf,$preferences,$question,7);
                // End algebra data
                $status = $status && fwrite ($bf,end_tag('ALGEBRA',$level,true));
            }
            // Backup the answers
            $status = $status && question_backup_answers($bf,$preferences,$question);
        }
        return $status;
    }

    /// RESTORE FUNCTIONS /////////////////

    /**
     * Restores variables associated with a question.
     *
     * This method is used by the general restore method and will read and write to the
     * database all the variables used by the question being restored.
     *
     * @param $old_question_id the original ID number of the question being restored
     * @param $new_question_id the new ID number of the question being restored
     * @param $info the XML parse tree containing all the restore information
     * @param $restore information about the current restore in progress
     * @param $newvarids array of new variable IDs to fill
     * 
     * @return bool true if the backup was successful, false if it failed.
     */
    function restore_variables($old_question_id,$new_question_id,$info,$restore,&$newvarids) {
        // Set the devault return value, $status, to be true
        $status = true;
        // Get the array of variables
        $vars = $info['#']['VARLIST']['0']['#']['VARIABLE'];
        // Iterate over the variables stored in the backup data
        foreach($vars as $var_info) {
            // Create an empty class to store the databse record
            $var = new stdClass;
            // Fill in the variable information from the backup information
            $var->question = $new_question_id;
            $var->name = backup_todb($var_info['#']['NAME']['0']['#']);
            $var->min = backup_todb($var_info['#']['MIN']['0']['#']);
            $var->max = backup_todb($var_info['#']['MAX']['0']['#']);
            // The structure is now equal to the db, so insert the question_algebra_variable object
            // and check the the database insert call worked
            if (!$newid = insert_record('question_algebra_variables',$var)) {
                echo get_string('qtype_algebra','restorevardbfailed'),"\n";
                $status = false;
            }
            // The database write did work so add the ID returned to the array
            else {
                $newvarids[]=$newid;
            }
        }
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
        // Set the devault return value, $status, to be true
        $status = true;
        // Get the array of algebra questions
        $algebraqs = $info['#']['ALGEBRA'];
        // Iterate over the algebra questions in the backup data
        for($i=0; $i<sizeof($algebraqs); $i++) {
            $alg_info = $algebraqs[$i];
            // Create an empty class to store the question's database record
            $algebra = new stdClass;
            // Fill the algebra specific variables for this object
            $algebra->questionid   = $new_question_id;
            $algebra->compareby    = backup_todb($alg_info['#']['COMPAREBY']['0']['#']);
            $algebra->variables    = backup_todb($alg_info['#']['VARIABLES']['0']['#']);
            $algebra->nchecks      = backup_todb($alg_info['#']['NCHECKS']['0']['#']);
            $algebra->tolerance    = backup_todb($alg_info['#']['TOLERANCE']['0']['#']);
            $algebra->allowedfuncs = backup_todb($alg_info['#']['ALLOWEDFUNCS']['0']['#']);
            $algebra->disallow     = backup_todb($alg_info['#']['DISALLOW']['0']['#']);
            $algebra->answerprefix = backup_todb($alg_info['#']['ANSWERPREFIX']['0']['#']);
            // Create an array to store the new variable IDs
            $newvarids=array();
            // Restore the variables for this question
            $status = $status && $this->restore_variables($old_question_id,$new_question_id,$alg_info,$restore,$newvarids);
            // Convert the new variable IDs into a string to place in the question's data structure
            $algebra->variables = implode(',',$newvarids);
            
            // The structure is now equal to the db, so insert the question_algebra object
            // and check the the database insert call worked
            if (!insert_record('question_algebra',$algebra)) {
                echo get_string('qtype_algebra','restoreqdbfailed'),"\n";
                $status = false;
            }
            // Generate output so that the user can see questions being restored
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }
        return $status;
    }

}

// INITIATION - Without this line the question type is not in use.
question_register_questiontype(new question_algebra_qtype());
?>

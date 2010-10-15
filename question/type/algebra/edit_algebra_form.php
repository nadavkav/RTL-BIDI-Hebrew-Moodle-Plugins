<?php
/**
 * Defines the editing form for the algebra question type.
 *
 * @copyright &copy; 2008 Roger Moore
 * @author Roger Moore <rwmoore@ualberta.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 */

require_once("parser.php");
    
// Override the default number of answers and the number to add to avoid clutter.
// Algebra questions will likely not have huge number of different answers...
define("SYMB_QUESTION_NUMANS_START", 2);
define("SYMB_QUESTION_NUMANS_ADD", 1);

// Override the default number of answers and the number to add to avoid clutter.
// algebra questions will likely not have huge number of different answers...
define("SYMB_QUESTION_NUMVAR_START", 2);
define("SYMB_QUESTION_NUMVAR_ADD", 1);

/**
 * symoblic editing form definition.
 */
class question_edit_algebra_form extends question_edit_form {
    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    function definition_inner(&$mform) {
		// Add the select control which will select the comparison type to use
        $mform->addElement('select', 'compareby', get_string('compareby','qtype_algebra'), 
						   array( "sage"  => get_string('comparesage', 'qtype_algebra'),
								  "eval"  => get_string('compareeval', 'qtype_algebra'),
								  "equiv" => get_string('compareequiv','qtype_algebra')
								 ));
        $mform->setDefault('compareby','eval');
        $mform->setHelpButton('compareby',array('comp_algorithm',get_string('compalgorithm','qtype_algebra'),
                                                'qtype_algebra'));

		// Add the control to select the number of checks to perform
        // First create an array with all the allowed values. We will then use this array
        // with the array_combine function to create a single array where the keys are the
        // same as the array values
        $chk_array=array(  '1',   '2',   '3',   '5',   '7',
                          '10',  '20',  '30',  '50',  '70',
                         '100', '200', '300', '500', '700', '1000');
        // Add the select element using the array_combine method discussed above
        $mform->addElement('select', 'nchecks', get_string('nchecks','qtype_algebra'), 
                            array_combine($chk_array,$chk_array));
        // Set the default number of checks to perform
        $mform->setDefault('nchecks','10');
        $mform->setHelpButton('nchecks',array('eval_checks',get_string('evalchecks','qtype_algebra'),
                                              'qtype_algebra'));

        // Add the box to set the tolerance to use when performing evaluation checks
        $mform->addElement('text', 'tolerance', get_string('tolerance','qtype_algebra'));
        $mform->setType('tolerance', PARAM_NUMBER);
        $mform->setDefault('tolerance','0.001');
        $mform->setHelpButton('tolerance',array('check_tolerance',get_string('checktolerance','qtype_algebra'),
                                                'qtype_algebra'));
        
        // Add an entry for the answer box prefix
        $mform->addElement('text', 'answerprefix', get_string('answerprefix','qtype_algebra'),array('size'=>55));
        $mform->setType('answerprefix', PARAM_RAW);
        $mform->setHelpButton('answerprefix',array('answer_prefix',get_string('answerboxprefix','qtype_algebra'),
                                               'qtype_algebra'));
        
        // Add an entry for a disallowed expression
        $mform->addElement('text', 'disallow', get_string('disallow','qtype_algebra'),array('size'=>55));
        $mform->setType('disallow', PARAM_RAW);
        $mform->setHelpButton('disallow',array('disallowed_ans',get_string('disallowanswer','qtype_algebra'),
                                               'qtype_algebra'));
        
        // Create an array which will store the function checkboxes
        $func_group=array();
        // Create an array to add spacers between the boxes
        $spacers=array('<br>');
        // Add the initial all functions box to the list of check boxes
        $func_group[] =& $mform->createElement('checkbox','all','',get_string('allfunctions','qtype_algebra'));
        // Create a checkbox element for each function understood by the parser
        for($i=0;$i<count(qtype_algebra_parser::$functions);$i++) {
            $func=qtype_algebra_parser::$functions[$i];
            $func_group[] =& $mform->createElement('checkbox',$func,'',$func);
            if(($i % 6) == 5) {
                $spacers[]='<br>';
            } else {
                $spacers[]=str_repeat('&nbsp;',8-strlen($func));
            }
        }
		// Create and add the group of function controls to the form
        $mform->addGroup($func_group,'allowedfuncs',get_string('allowedfuncs','qtype_algebra'),$spacers,true);
        $mform->disabledIf('allowedfuncs','allowedfuncs[all]','checked');
        $mform->setDefault('allowedfuncs[all]','checked');
        $mform->setHelpButton('allowedfuncs',array('allowed_funcs',get_string('allowedfunctions','qtype_algebra'),
                                                   'qtype_algebra'));
        
		// Create the array for the list of variables used in the question
		$repeated=array();
		// Create the array for the list of repeated options used by the variable subforms
        $repeatedoptions = array();
		
		// Add the form elements to enter the variables
        $repeated[] =& $mform->createElement('header','variablehdr',get_string('variableno','qtype_algebra','{no}'));
        $repeatedoptions['variablehdr']['helpbutton'] = array('variable',get_string('variable','qtype_algebra'),
                                                              'qtype_algebra');
		$repeated[] =& $mform->createElement('text','variable',get_string('variablename','qtype_algebra'),array('size'=>20));
        $mform->setType('variable', PARAM_RAW);
		$repeated[] =& $mform->createElement('text','varmin',get_string('varmin','qtype_algebra'),array('size'=>20));
		$mform->setType('varmin', PARAM_RAW);
        $repeatedoptions['varmin']['default'] = '';
		$repeated[] =& $mform->createElement('text','varmax',get_string('varmax','qtype_algebra'),array('size'=>20));
		$mform->setType('varmax', PARAM_RAW);
        $repeatedoptions['varmax']['default'] = '';

		// Get the current number of variables defined, if any
		if (isset($this->question->options)) {
            $countvars = count($this->question->options->variables);
        } else {
            $countvars = 0;
        }
		// Come up with the number of variable entries to add to the form at the start
        if ($this->question->formoptions->repeatelements){
            $repeatsatstart = (SYMB_QUESTION_NUMVAR_START > ($countvars + SYMB_QUESTION_NUMVAR_ADD))?
			SYMB_QUESTION_NUMVAR_START : ($countvars + SYMB_QUESTION_NUMVAR_ADD);
        } else {
            $repeatsatstart = $countvars;
        }
        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions, 'novariables', 'addvariables',
							   SYMB_QUESTION_NUMVAR_ADD, get_string('addmorevariableblanks', 'qtype_algebra'));
        
        // Add the instructions for entering answers to the question
        $mform->addElement('static', 'answersinstruct', get_string('correctanswers', 'quiz'), get_string('filloutoneanswer', 'quiz'));
        $mform->closeHeaderBefore('answersinstruct');

        $creategrades = get_grade_options();
        $gradeoptions = $creategrades->gradeoptions;
        $repeated = array();
        $repeated[] =& $mform->createElement('header', 'answerhdr', get_string('answerno', 'qtype_algebra', '{no}'));
        $repeated[] =& $mform->createElement('text', 'answer', get_string('answer', 'quiz'), array('size' => 54));
        $repeated[] =& $mform->createElement('select', 'fraction', get_string('grade'), $gradeoptions);
        $repeated[] =& $mform->createElement('htmleditor', 'feedback', get_string('feedback', 'quiz'),
                                array('course' => $this->coursefilesid));		
		
        if (isset($this->question->options)){
            $countanswers = count($this->question->options->answers);
        } else {
            $countanswers = 0;
        }
        if ($this->question->formoptions->repeatelements){
            $repeatsatstart = (SYMB_QUESTION_NUMANS_START > ($countanswers + SYMB_QUESTION_NUMANS_ADD))?
                                SYMB_QUESTION_NUMANS_START : ($countanswers + SYMB_QUESTION_NUMANS_ADD);
        } else {
            $repeatsatstart = $countanswers;
        }
        $repeatedoptions = array();
        $mform->setType('answer', PARAM_RAW);
        $repeatedoptions['fraction']['default'] = 0;
        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions, 'noanswers', 'addanswers',
							   SYMB_QUESTION_NUMANS_ADD, get_string('addmoreanswerblanks', 'qtype_algebra'));

    }

    /**
     * Sets the existing values into the form for the question specific data.
	 *
	 * This method copies the data from the existing database record into the form fields as default
	 * values for the various elements.
     *
     * @param $question the question object from the database being used to fill the form
     */
    function set_data($question) {
		// Check to see if there are any existing question options, if not then just call
		// the base class set data method and exit
        if (!isset($question->options)) {
			return parent::set_data($question);
		}
        
		// Our first job is to set the defaults for the answers. Start by getting the answers...
		$answers = $question->options->answers;
		// If we found any answers then loop over them using a numerical key to provide an index
		// to the arrays we need to access in the form
		if (count($answers)) {
			$key = 0;
			foreach ($answers as $answer) {
				// For every answer set the default values
				$default_values['answer['.$key.']'] = $answer->answer;
				$default_values['fraction['.$key.']'] = $answer->fraction;
				$default_values['feedback['.$key.']'] = $answer->feedback;
				$key++;
			}
		}
		
		// Now we do exactly the same for the variables...
		$vars = $question->options->variables;
		// If we found any answers then loop over them using a numerical key to provide an index
		// to the arrays we need to access in the form
		if (count($vars)) {
			$key = 0;
			foreach ($vars as $var) {
				// For every variable set the default values
				$default_values['variable['.$key.']'] = $var->name;
				// Only set the mon and max defaults if this variable has a range
				if($var->min!='') {
					$default_values['varmin['.$key.']'] = $var->min;
					$default_values['varmax['.$key.']'] = $var->max;
				}
				$key++;
			}
		}
		        
        // Add the default values for the allowed functions controls
        // First check to see if there are any allowed functions defined
        if(count($question->options->allowedfuncs)>0) {
            // Clear the 'all functions' flag since functions are restricted
            $default_values['allowedfuncs[all]']=0;
            // Loop over all the functions which the parser understands
            foreach(qtype_algebra_parser::$functions as $func) {
                // For each function see if the function is in the allowed function
                // list and if so set the check box otherwise remove the check box
                if(in_array($func,$question->options->allowedfuncs)) {
                    $default_values['allowedfuncs['.$func.']']=1;
                } else {
                    $default_values['allowedfuncs['.$func.']']=0;
                }
            }
        }
        // There are no allowed functions defined so all functions are allowed
        else {
            $default_values['allowedfuncs[all]']=1;
        }
        
		// Add the default values to the question object in a form which the parent 
		// set data method will be able to use to find the default values
		$question = (object)((array)$question + $default_values);
		
		// Finally call the parent set data method to handle everything else
        parent::set_data($question);
    }
	
    /**
     * Validates the form data ensuring there are no obvious errors in the submitted data.
	 *
	 * This method performs some basic sanity checks on the form data before it gets converted
	 * into a database record.
     *
     * @param $data the data from the form which needs to be checked
	 * @param $files some files - I don't know what this is for! - files defined in the form??
     */
    function validation($data, $files) {
		// Call the base class validation method and keep any errors it generates
        $errors = parent::validation($data, $files);
		
        // Regular expression string to match a number
        $renumber='/([+-]*(([0-9]+\.[0-9]*)|([0-9]+)|(\.[0-9]+))|'.
            '(([0-9]+\.[0-9]*)|([0-9]+)|(\.[0-9]+))E([-+]?\d+))/A';
        
		// Perform sanity checks on the variables.
        $vars = $data['variable'];
        // Create an array of defined variables
        $varlist=array();
        foreach ($vars as $key => $var) {
            $trimvar = trim($var);
			$trimmin = trim($data['varmin'][$key]);
			$trimmax = trim($data['varmax'][$key]);
            // Check that there is a valid variable name otherwise skip
            if ($trimvar == '') {
                continue;
            }
            // Check that this variable does not have the same name as a function
            if(in_array($trimvar,qtype_algebra_parser::$functions) or in_array($trimvar,qtype_algebra_parser::$specials)) {
                $errors['variable['.$key.']'] = get_string('illegalvarname','qtype_algebra',$trimvar);
            }
            // Check that this variable has not been defined before
            if(in_array($trimvar,$varlist)) {
                $errors['variable['.$key.']'] = get_string('duplicatevar','qtype_algebra');
            } else {
                // Add the variable to the list of defined variables
                $varlist[]=$trimvar;
            }
            // If the comparison algorithm selected is evaluate then ensure that each variable
            // has a valid minimum and maximum defined. For the other types of comparison we can
            // ignore the range
            if($data['compareby']=='eval') {
                // Check that a minimum has been defined
                if ($trimmin == '') {
                    $errors['varmin['.$key.']'] = get_string('novarmin','qtype_algebra');
                }
                // If there is one check that it is a number
                else if(!preg_match($renumber,$trimmin)) {
                    $errors['varmin['.$key.']'] = get_string('notanumber','qtype_algebra');
                }
                if ($trimmax == '') {
                    $errors['varmax['.$key.']'] = get_string('novarmax','qtype_algebra');
                }
                // If there is one check that it is a number
                else if(!preg_match($renumber,$trimmax)) {
                    $errors['varmax['.$key.']'] = get_string('notanumber','qtype_algebra');
                }
                // Check that the minimum is less that the maximum!
                if ((float)$trimmin > (float)$trimmax) {
                    $errors['varmin['.$key.']'] = get_string('varmingtmax','qtype_algebra');
                }
            } // end check for eval type
        }     // end loop over variables
        // Check that at least one variable is defined
        if (count($varlist)==0) {
            $errors['variable[0]'] = get_string('notenoughvars', 'qtype_algebra');
        }

		// Now perform the sanity checks on the answers
        // Create a parser which we will use to check that the answers are understandable
        $p = new qtype_algebra_parser;
        $answers = $data['answer'];
        $answercount = 0;
        $maxgrade = false;
        // Create an empty array to store the used variables
        $ansvars=array();
        // Create an empty array to store the used functions
        $ansfuncs=array();
        // Loop over all the answers in the form
        foreach ($answers as $key => $answer) {
            // Try to parse the answer string using the parser. If this fails it will
            // throw an exception which we catch to generate the associated error string
            // for the expression
            try {
                $expr=$p->parse($answer);
                // Add any new variables to the list we are keeping. First we get the list
                // of variables in this answer. Then we get the array of variables which are
                // in this answer that are not in any previous answer (using array_diff).
                // Finally we merge this difference array with the list of all variables so far
                $tmpvars=$expr->get_variables();
                $ansvars=array_merge($ansvars,array_diff($tmpvars,$ansvars));
                // Check that all the variables in this answer have been declared
                // Do this by looking for a non-empty array to be returned from the array_diff
                // between the list of all declared variables and the variables in this answer
                if($d=array_diff($tmpvars,$varlist)) {
                    $errors['answer['.$key.']'] = get_string('undefinedvar','qtype_algebra',"'".implode("', '",$d)."'");                    
                }
                // Do the same for functions which we did for variables
                $ansfuncs=array_merge($ansfuncs,array_diff($expr->get_functions(),$ansfuncs));
                // Check that this is not an empty answer
                if (!is_a($expr,"qtype_algebra_parser_nullterm")) {
                    // Increase the number of answers
                    $answercount++;
                    // Check to see if the answer has the maximum grade
                    if ($data['fraction'][$key] == 1) {
                        $maxgrade = true;
                    }
                }
            } catch (Exception $e) {
                $errors['answer['.$key.']']=$e->getMessage();
                // Return here because subsequent errors may be wrong due to not counting the answer
                // which just failed to parse
                return $errors;
            }
        }
        // Check that we have at least one answer!
        if ($answercount==0){
            $errors['answer[0]'] = get_string('notenoughanswers', 'quiz', 1);
        }
        // Check that at least one question has the maximum possible grade
        if ($maxgrade == false) {
            $errors['fraction[0]'] = get_string('fractionsnomax', 'question');
        }
		        
        // Check for variables which are defined but never used.
        // Do this by looking for a non-empty array to be returned from array_diff.
        if($d=array_diff($varlist,$ansvars)) {
            // Loop over all the variables in the form
            foreach ($vars as $key => $var) {
                $trimvar = trim($var);
                // If the variable is in the unused array then add the error message to that variable
                if(in_array($trimvar,$d)) {
                    $errors['variable['.$key.']'] = get_string('unusedvar','qtype_algebra');
                }
            }
        }
        
        // Check that the tolerance is greater than or equal to zero
        if($data['tolerance']<0) {
            $errors['tolerance']=get_string('toleranceltzero','qtype_algebra');
        }
        
        return $errors;
    }
	
    function qtype() {
        return 'algebra';
    }
}
?>
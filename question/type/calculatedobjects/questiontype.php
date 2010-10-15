<?php  // $Id: questiontype.php,v 1.1 2010/09/04 11:36:31 deraadt Exp $
/**
* The CALCULATED OBJECTS question type.
*
* Teachers can create questions like "How much is {apples} + {oranges}?"
*  - where the {wildcards} become M and N x images of apples and oranges respectively.
*
* @author N.D.Freear, 14 August 2010.
* @copyright &copy; 2010 Nicholas Freear (except images, see readme).
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package questionbank
* @subpackage questiontypes
*
* (Useful searches: 'calculated[objects]', qtype_calculated, 'question_calculated'.)
*/

/// QUESTION TYPE CLASS //////////////////
require_once("$CFG->dirroot/question/type/calculated/questiontype.php");


class question_calculatedobjects_qtype extends question_calculated_qtype {
    //(...extends question_dataset_dependent_questiontype)

    // We have images for the following objects.
    // (Extend? Allow people to upload images/theme?)
    #protected static $wildcards = array(
    #    'apple', 'orange', 'pear', 'pineapple', 'walnut', 'coffee', 'cookie'
    #);

    protected static $default_pix = array(
        'apple' => 'apple-75.png',
        'orange'=> 'orange-juice-75.png',
        'pear'  => 'pear-75.png',
        'pineapple'=>'pineapple-75.png',
        'cookie'=> 'cookie-tulliana-75.png',
        'coffee'=> 'coffee-icon-75.png',
        'walnut'=> 'walnut-60.png',
    );

    /*// Moodle 2.0 specific - test!
    public function __construct() {
        global $PAGE;
        if (isset($PAGE->requires)) {
            $PAGE->requires->css(
            'question/type/calculatedobjects/styles.css');#css.php?d='.$data->id)
        }
        return parent::__construct();
    }*/

    function name() {
        return 'calculatedobjects';
    }

    /** Substitute variables in questiontext to give a copy of
     *  the question with pictures, and a 'plain' copy.
     */
    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        // Substitute variables in questiontext before giving the data to the
        // virtual type for printing
        $virtualqtype = $this->get_virtual_qtype();
        if($unit = $virtualqtype->get_default_numerical_unit($question)){
             $unit = $unit->unit;
        } else {
            $unit = '';
        }
        // We modify the question to look like a numerical question
        $numericalquestion = fullclone($question);
        foreach ($numericalquestion->options->answers as $key => $answer) {
          $answer = fullclone($numericalquestion->options->answers[$key]);
            $numericalquestion->options->answers[$key]->answer = $this->substitute_variables($answer->answer,
             $state->options->dataset);
        }
        # This gives error "Illegal formula syntax starting with 'howmuchis'"
        # So disable for now.
        #$numericalquestion->questiontext = parent::substitute_variables(
        #    $numericalquestion->questiontext, $state->options->dataset);
        //evaluate the equations i.e {=5+4)
        $qtext = "";
        $qtextremaining = $numericalquestion->questiontext ;
        while  (ereg('\{=([^[:space:]}]*)}', $qtextremaining, $regs1)) {
        #@TODO.N. Hmm, at present, this isn't working:(
        } /*
            $qtextsplits = explode($regs1[0], $qtextremaining, 2);
            $qtext =$qtext.$qtextsplits[0];
            $qtextremaining = $qtextsplits[1];
            if (empty($regs1[1])) {
                    $str = '';
                } else {
                    if( $formulaerrors = qtype_calculatedobjects_find_formula_errors($regs1[1])){
                        $str=$formulaerrors ;
                    }else {
                        eval('$str = '.$regs1[1].';');
                    }
                }
                $qtext = $qtext.$str ;
        }*/

        $qtext  = $numericalquestion->questiontext;
        $dataset= $state->options->dataset;
#var_dump($state->options, $numericalquestion);

        // Find the first math operator/symbol (+-*/)
        // - prevent mis-matches on <br /> etc. below.
        $classes = '';
        $op = $op_replace = '+';
        if (preg_match('#[^<]([\+\-\*\/%])[^>]#', $qtext, $regs_op)) {
        #if (ereg('([\+\-\*\/])', $qtext, $regs_op)) {
            $ops= array('+'=>'+', '-'=>'&ndash;', '*'=>'&times;', '/'=>'<hr class="o"/>', '%'=>'%');
            $op = $regs_op[1];
            $op_replace = $ops[$regs_op[1]];
            if ('/' == $op) {
                $classes .= 'vertical';
            }
        }
        #ELSE: error.
        // Generate an array of N repeated objects/pictures.
        $objects = array();
        $patterns= array();
        foreach ($dataset as $key => $multiply) {
            // Trim plural 's' and check against supported wildcards.
            // (Later we may take the first or last '-' separated token(s). For $class='file-uploads-apple-2')
            $name = preg_replace('#_?\d$#', '', $key);
            $class= rtrim($name, 's');

            if (strlen($key) == 1) {
                $class= $key;
                $items = " <i class='big'>$multiply</i>";
                $plains[] = $items;
            }
            elseif (!isset(self::$default_pix[$class])) { #in_array($class, self::$wildcards)) {
                $class = 'unknown';
                $items = " <i>?</i>";
                $plains[] = $items;
            } else {
                $pix = self::$default_pix[$class];
                global $CFG;
                $src = "$CFG->wwwroot/question/type/calculatedobjects/pix/$pix";
                $item = "<img\n alt='' src='$src' />";

                $items = str_repeat($item, $multiply);
                $plains[] = "<i>$multiply $name</i>"; #i18n.
            }
            $objects[] = "<div align='center'>$items</div>";
            $patterns[]= '{'.$key.'}';
        }
        // Create the objects/pictures string.
        $object_str = $objects[0] ."<p class='op $classes'>$op_replace</p>". $objects[1];
        $plain_str = str_replace($patterns, $plains, $qtext);
        $plain_str = preg_replace("#\[[\+\-\*\/%]\]#", '', $plain_str);

        $numericalquestion->questiontext = <<<EOT
        <h3 class="qco-text" style="text-align:center">$plain_str</h3>
        <div class="qco-objects $classes">$object_str
          <br style="clear:both;height:1px;" /></div>
      
EOT;
        #$numericalquestion->questiontext =  $qtext.$qtextremaining ; // end replace equations

        $virtualqtype->print_question_formulation_and_controls($numericalquestion, $state, $cmoptions, $options);
    }

    function __create_virtual_qtype() {
        global $CFG;
        require_once("$CFG->dirroot/question/type/numerical/questiontype.php");
        return new question_numerical_qtype();
    }

    function supports_dataset_item_generation() {
    // Calculated support generation of randomly distributed number data
        return true;
    }

    function dataset_options($form, $name, $mandatory=true,$renameabledatasets=false) {
    // Takes datasets from the parent implementation but
    // filters options that are currently not accepted by calculated
    // It also determines a default selection...
    //$renameabledatasets not implemented anmywhere
        list($options, $selected) = parent::dataset_options($form, $name,'','qtype_calculatedobjects');  #@TODO.N.
  //  list($options, $selected) = $this->dataset_optionsa($form, $name);

        foreach ($options as $key => $whatever) {
            if (!ereg('^'.LITERAL.'-', $key) && $key != '0') {
                unset($options[$key]);
            }
        }
        if (!$selected) {
            if ($mandatory){
            $selected = LITERAL . "-0-$name"; // Default
            }else {
                $selected = "0"; // Default
            }
        }
        return array($options, $selected);
    }


    function substitute_variables($str, $dataset) { #@TODO.N.
        $formula = NULL;
        #$formula = question_dataset_dependent_questiontype::substitute_variables($str, $dataset);
        $formula = parent::substitute_variables($str, $dataset);

        #if ($error = qtype_calculatedobjects_find_formula_errors($formula)) {
        #    return $error;
        #}
        /// Calculate the correct answer
        if (empty($formula)) {
            $str = '';
        } else if ($formula === '*'){
            $str = '*';
        } else {
            eval('$str = '.$formula.';');
        }
        
        $str = str_replace(array_keys($dataset), $dataset, $str);
        
        return "$str";
    }

  /**
   * Runs all the code required to set up and save an essay question for testing purposes.
   * Alternate DB table prefix may be used to facilitate data deletion.
   */
  function generate_test($name, $courseid = null) {
      list($form, $question) = parent::generate_test($name, $courseid);
      $form->feedback = 1;
      $form->multiplier = array(1, 1);
      $form->shuffleanswers = 1;
      $form->noanswers = 1;
      $form->qtype ='calculated';  #@TODO.N. Do we change this line? (Change next ones!)
      $question->qtype ='calculatedobjects';
      $form->answers = array('{apples} + {oranges}');
      $form->fraction = array(1);
      $form->tolerance = array(0.01);
      $form->tolerancetype = array(1);
      $form->correctanswerlength = array(2);
      $form->correctanswerformat = array(1);
      $form->questiontext = "What is {apples} + {oranges}?";

      if ($courseid) {
          $course = get_record('course', 'id', $courseid);
      }

      $new_question = $this->save_question($question, $form, $course);

      $dataset_form = new stdClass();
      $dataset_form->nextpageparam["forceregeneration"]= 1;
      $dataset_form->calcmin = array(1 => 1.0, 2 => 1.0);
      $dataset_form->calcmax = array(1 => 10.0, 2 => 10.0);
      $dataset_form->calclength = array(1 => 1, 2 => 1);
      $dataset_form->number = array(1 => 5.4 , 2 => 4.9);
      $dataset_form->itemid = array(1 => '' , 2 => '');
      $dataset_form->calcdistribution = array(1 => 'uniform', 2 => 'uniform');
      $dataset_form->definition = array(1 => "1-0-a",
                                        2 => "1-0-b");
      $dataset_form->nextpageparam = array('forceregeneration' => false);
      $dataset_form->addbutton = 1;
      $dataset_form->selectadd = 1;
      $dataset_form->courseid = $courseid;
      $dataset_form->cmid = 0;
      $dataset_form->id = $new_question->id;
      $this->save_dataset_items($new_question, $dataset_form);

      return $new_question;
  }
}
//// END OF CLASS ////



//////////////////////////////////////////////////////////////////////////
//// INITIATION - Without this line the question type is not in use... ///
//////////////////////////////////////////////////////////////////////////
question_register_questiontype(new question_calculatedobjects_qtype());

function qtype_calculatedobjects_calculate_answer($formula, $individualdata,
        $tolerance, $tolerancetype, $answerlength, $answerformat='1', $unit='') {
/// The return value has these properties:
/// ->answer    the correct answer
/// ->min       the lower bound for an acceptable response
/// ->max       the upper bound for an accetpable response

    /// Exchange formula variables with the correct values...
    global $QTYPES;
    $answer = $QTYPES['calculatedobjects']->substitute_variables($formula, $individualdata);
    if ('1' == $answerformat) { /* Answer is to have $answerlength decimals */
        /*** Adjust to the correct number of decimals ***/
        if (stripos($answer,'e')>0 ){
            $answerlengthadd = strlen($answer)-stripos($answer,'e');
        }else {
            $answerlengthadd = 0 ;
        }
        $calculated->answer = round(floatval($answer), $answerlength+$answerlengthadd);

        if ($answerlength) {
            /* Try to include missing zeros at the end */

            if (ereg('^(.*\\.)(.*)$', $calculated->answer, $regs)) {
                $calculated->answer = $regs[1] . substr(
                        $regs[2] . '00000000000000000000000000000000000000000x',
                        0, $answerlength)
                        . $unit;
            } else {
                $calculated->answer .=
                        substr('.00000000000000000000000000000000000000000x',
                        0, $answerlength + 1) . $unit;
            }
        } else {
            /* Attach unit */
            $calculated->answer .= $unit;
        }

    } else if ($answer) { // Significant figures does only apply if the result is non-zero

        // Convert to positive answer...
        if ($answer < 0) {
            $answer = -$answer;
            $sign = '-';
        } else {
            $sign = '';
        }

        // Determine the format 0.[1-9][0-9]* for the answer...
        $p10 = 0;
        while ($answer < 1) {
            --$p10;
            $answer *= 10;
        }
        while ($answer >= 1) {
            ++$p10;
            $answer /= 10;
        }
        // ... and have the answer rounded of to the correct length
        $answer = round($answer, $answerlength);

        // Have the answer written on a suitable format,
        // Either scientific or plain numeric
        if (-2 > $p10 || 4 < $p10) {
            // Use scientific format:
            $eX = 'e'.--$p10;
            $answer *= 10;
            if (1 == $answerlength) {
                $calculated->answer = $sign.$answer.$eX.$unit;
            } else {
                // Attach additional zeros at the end of $answer,
                $answer .= (1==strlen($answer) ? '.' : '')
                        . '00000000000000000000000000000000000000000x';
                $calculated->answer = $sign
                        .substr($answer, 0, $answerlength +1).$eX.$unit;
            }
        } else {
            // Stick to plain numeric format
            $answer *= "1e$p10";
            if (0.1 <= $answer / "1e$answerlength") {
                $calculated->answer = $sign.$answer.$unit;
            } else {
                // Could be an idea to add some zeros here
                $answer .= (ereg('^[0-9]*$', $answer) ? '.' : '')
                        . '00000000000000000000000000000000000000000x';
                $oklen = $answerlength + ($p10 < 1 ? 2-$p10 : 1);
                $calculated->answer = $sign.substr($answer, 0, $oklen).$unit;
            }
        }
    } else {
        $calculated->answer = 0.0;
    }

    /// Return the result
    return $calculated;
}


function qtype_calculatedobjects_find_formula_errors($formula) { #@TODO.N.
/// Validates the formula submitted from the question edit page.
/// Returns false if everything is alright.
/// Otherwise it constructs an error message

    // Strip away dataset names
    while (ereg('\\{[[:alpha:]][^>} <{"\']*\\}', $formula, $regs)) {
        $formula = str_replace($regs[0], '1', $formula);
    }

    // Strip away empty space and lowercase it
    $formula = strtolower(str_replace(' ', '', $formula));

    $safeoperatorchar = '-+/*%>:^~<?=&|!'; /* */
    $operatorornumber = "[$safeoperatorchar.0-9eE]";


    while (ereg("(^|[$safeoperatorchar,(])([a-z0-9_]*)\\(($operatorornumber+(,$operatorornumber+((,$operatorornumber+)+)?)?)?\\)",
            $formula, $regs)) {

        switch ($regs[2]) {
            // Simple parenthesis
            case '':
                if ($regs[4] || strlen($regs[3])==0) {
var_dump(">> E 1");
                    return get_string('__illegalformulasyntax', 'quiz', $regs[0]);
                }
                break;

            // Zero argument functions
            case 'pi':
                if ($regs[3]) {
                    return get_string('functiontakesnoargs', 'quiz', $regs[2]);
                }
                break;

            // Single argument functions (the most common case)
            case 'abs': case 'acos': case 'acosh': case 'asin': case 'asinh':
            case 'atan': case 'atanh': case 'bindec': case 'ceil': case 'cos':
            case 'cosh': case 'decbin': case 'decoct': case 'deg2rad':
            case 'exp': case 'expm1': case 'floor': case 'is_finite':
            case 'is_infinite': case 'is_nan': case 'log10': case 'log1p':
            case 'octdec': case 'rad2deg': case 'sin': case 'sinh': case 'sqrt':
            case 'tan': case 'tanh':
                if ($regs[4] || empty($regs[3])) {
                    return get_string('functiontakesonearg','quiz',$regs[2]);
                }
                break;

            // Functions that take one or two arguments
            case 'log': case 'round':
                if ($regs[5] || empty($regs[3])) {
                    return get_string('functiontakesoneortwoargs','quiz',$regs[2]);
                }
                break;

            // Functions that must have two arguments
            case 'atan2': case 'fmod': case 'pow':
                if ($regs[5] || empty($regs[4])) {
                    return get_string('functiontakestwoargs', 'quiz', $regs[2]);
                }
                break;

            // Functions that take two or more arguments
            case 'min': case 'max':
                if (empty($regs[4])) {
                    return get_string('functiontakesatleasttwo','quiz',$regs[2]);
                }
                break;

            default:
                return get_string('unsupportedformulafunction','quiz',$regs[2]);
        }

        // Exchange the function call with '1' and then chack for
        // another function call...
        if ($regs[1]) {
            // The function call is proceeded by an operator
            $formula = str_replace($regs[0], $regs[1] . '1', $formula);
        } else {
            // The function call starts the formula
            $formula = ereg_replace("^$regs[2]\\([^)]*\\)", '1', $formula);
        }
    }

    if (ereg("[^$safeoperatorchar.0-9eE]+", $formula, $regs)) {
var_dump(">> E 2");
        return get_string('__illegalformulasyntax', 'quiz', $regs[0]);
    } else {
        // Formula just might be valid
        return false;
    }
}

?>
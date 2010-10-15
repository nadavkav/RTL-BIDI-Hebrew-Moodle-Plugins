<?php
/**
 * The question_variables class to parse and evaluate variable string.
 *
 * @copyright &copy; 2010 Hon Wai, Lau
 * @author Hon Wai, Lau <lau65536@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 */


/**
 * Class contains method to parse variables text and evaluate variables.
 * 
 * It stores a set of name/values pair by parsing a string of assignments. 
 * These can be added to the class by either calling the constructor or using add_local_variables.
 * All variables evaluated a prior will be used evaluate_expression() and substitute_variables() later.
 * 
 * Note that variables is a string of alpha-numeric character and '_' (not at the beginning)
 * and enclosed by the bracket '{', '}'. For example, '{A}', '{is_added}'
 */
class question_variables {
    private $all = array();  // store all variables that have been evaluated, always contains global variables
    
    /**
     * Initialize the variables by the set of assignment of random value. May throw error
     * 
     * @param string $input Either an array of already parsed variables or a string of assignments.
     *   The variables definition are string in the form of 'variables = expression' separated by ';'
     *   There are three type of expressions: (1) set of numbers (2) set of tuple (3) shuffle
     *   (1) When instantiating, one number will be drawn from the set, it also allow a range format of numbers
     *   (2) When instantiating, a tuple, say (0,0,0), will be drawn randomly from the set
     *   (3) When instantiating, a random shuffled tuple of the numbers will be created
     *   e.g. $input = "{A}={1,2,3}; {B}={1, 3~5:1, 8~9:.1}; {C}={(1,4),(1,9)}; shuffle(2,3,4,5);" ;
     */
    function __construct($input) {
        if (is_array($input))
            $this->all = $input;  // no error check, assume it is associative array of (variable name => values)
        else if (is_string($input)) {
            $this->all = array();
            $splittedvars = $this->split_variables_text($input);
            $parsedvars = $this->parse_random_variables($splittedvars);
            $this->instantiate_random_variables($parsedvars);
        }
        else throw new Exception(get_string('error_vars_init','qtype_coordinates'));
    }
    
    
    /**
     * Return the array of all variables. No throw
     * 
     * @return an associative array of all variables in the form of (variable name => values)
     */
    function get_variables() {
        return $this->all;
    }
    
    
    /**
     * Add one variable to the variable lists in this class. No throw
     * 
     * @param string $name the name of the variable
     * @param mixed $data the data of the variable, it should be a number or array of number (for tuple)
     */
    function add($name, $data) {
        $this->all[$name] = $data;
    }
    
    
    /**
     * Evaluate local variables of the input text, one by one. May throw error
     * 
     * @param string $varstext The string containing the definition of variables assignment
     */
    function add_local_variables($varstext) {
        $splittedvars = $this->split_variables_text($varstext);
        foreach ($splittedvars as $i => $pair) {
            list($name, $expression) = $pair;
            $this->add( $name , $this->evaluate_expression($expression) );
        }
    }
    
    
    /**
     * Evaluate the $expression with given all variables in this class. May throw error
     * 
     * @param string $expression The expression being evaluated
     * @return The evaluated result.
     */
    function evaluate_expression($expression) {
        if (is_numeric($expression))  return floatval($expression);
        if (preg_match('/[)(, 0-9eE.+-]+/', $expression)) {   // if it contains these characters, test whether it is a tuple
            $res = $this->parse_tuple($expression);
            if (!($res === null)) {
                if (count($res) == 1)  return $res[0];  // if it contains one element only, it should be a number such as (-9)
                else return $res;
            }
        }
        // check for possible error before directly calling eval
        $res = $this->find_formula_errors($expression);
        if ($res)  throw new Exception('For expression: ' . $expression . "\n" . $res);
        $subs = $this->substitute_variables($expression, true);
        $found1 = !(strpos($subs, '{') === false);
        $found2 = !(strpos($subs, '}') === false);
        if ($found1 || $found2)     // find whether the expression contains not replacable variables
            throw new Exception(get_string('error_evaluation_bracket','qtype_coordinates').'<br/>'.$expression.'<br/>'.$subs);
        eval('$evaluated = '.$subs.';');
        if ($evaluated === null)  throw new Exception(get_string('error_evaluation_general','qtype_coordinates') . $expression);
        return $evaluated;
    }
    
    
    /**
     * Replace the variables in the input $str by all variables in this class. No throw
     * 
     * @param string $str The string being replaced
     * @return The replaced string
     */
    function substitute_variables($str, $is_evaluation=false) {
        foreach ($this->all as $name => $value) {
            if (!is_array($value)) {
                if ($is_evaluation)  $value = '('.(is_bool($value) ? ($value?1:0) : $value).')';
                $str = str_replace($name, $value, $str);
            }
            else foreach ($value as $i => $v) {
                if ($is_evaluation)  $v = '('.$v.')';
                $str = str_replace($name.'['.$i.']', $v, $str);
            }
        }
        return $str;
    }
    
    
    
    
    /**
     * Split the variable text in the form of 'name = value;'. Throw on parsing error
     * 
     * @param string $varsdeftext the text of variables definition
     * @return associative array of the form (name => value)
     */
    function split_variables_text($varsdeftext) {
        $variable_format = '\{[A-Za-z][A-Za-z0-9_]*\}';
        /// split the $varsdeftext into different $assignments, separated by ';'
        $varsdeftext = trim($varsdeftext);
        if (strlen($varsdeftext) == 0)  return array();
        $assignments = explode(';', $varsdeftext);
        if (strlen(array_pop($assignments)) != 0)
            throw new Exception(get_string('error_vars_end_separator','qtype_coordinates'));
        
        /// split the $assignment into the pairs form of name = expression;
        $splittedvars = array();
        foreach ($assignments as $idx => $assignment)  if (strlen(trim($assignment)) != 0) {
            $tmp = explode('=',$assignment,2);
            if(count($tmp)!=2)
                throw new Exception(get_string('error_vars_parse','qtype_coordinates') . $assignment);
            $name = trim($tmp[0]);
            $expression = trim($tmp[1]);
            if (!preg_match('/^'.$variable_format.'$/', $name))
                throw new Exception(get_string('error_vars_format','qtype_coordinates') . $name);
            $splittedvars[] = array($name, $expression);
        }
        return $splittedvars;
    }
    
    
    /**
     * Parse a tuple of number into an array.
     * 
     * @param string $expression The input tuple enclosed by bracket, e.g. (1,2,3,4,(5),6)
     * @return an array of number, or null if any error
     */
    function parse_tuple($expression) {
        $expression = trim($expression);
        if (strlen($expression) > 2 && $expression[0] == '(' && $expression[strlen($expression)-1] == ')') {
            $exploded = explode(',', substr($expression, 1, -1));
            $res = array();
            foreach ($exploded as $e) {
                $e = trim($e);
                if (strlen($e) > 2 && $e[0] == '(' && $e[strlen($e)-1] == ')')  $e = substr($e, 1, -1);
                if (!is_numeric($e))  return null;
                $res[] = floatval($e);
            }
            return $res;
        }
        return null;
    }
    
    
    /**
     * Parse the elements in the $varsdeftext for later instantiation of a random dataset. Throw on parsing error
     *
     * @param array $splittedvars The variables array in the form of (name => expression)
     * @return A data structure that can be used by the instantiate_random_variables() later.
     */
    function parse_random_variables($splittedvars) {
        $number_format = '[0-9eE.+-]+';  // no need to exact match, there is a conversion test later
        $range_format = '('.$number_format.')\s*~\s*('.$number_format.')\s*:\s*('.$number_format.')';
        $number_or_range_format = '(' . $number_format  . '|' . $range_format . ')';
        $tuple_format = '\(\s*'.$number_format.'(\s*,\s*'.$number_format.')+\s*\)';
        $set_number_format = '\{\s*' . $number_or_range_format . '(\s*,\s*' . $number_or_range_format . ')*\s*\}';
        $set_tuple_format = '\{\s*' . $tuple_format . '(\s*,\s*'. $tuple_format . ')*\s*\}';
        
        /// identify different types and parse correspondingly. It also generate a particular instantiation of variable value
        $parsedvars = array();
        foreach ($splittedvars as $i => $pair) {
            list($name, $expression) = $pair;
            $var = new stdClass;
            $var->expression = $expression;
            if ( preg_match('/^'.$set_number_format.'$/', $expression) ) {
                $var->type = 'set_number';  // a set of number or range, e.g. {1,2,3} or {1,3~10:1}
                $var->elementsraw = preg_split('/\s*,\s*/', substr($var->expression,1,-1));
                foreach ($var->elementsraw as $ele)  if ( preg_match( '/'.$range_format.'/', trim($ele), $matches) ) {
                    list($dummy, $a, $b, $interval) = $matches;
                    if ($b < $a || $interval <= 0)  throw new Exception(get_string('error_randvars_range','qtype_coordinates') . $name . ': ' . $ele);
                    $sz = ceil( ($b-$a)/$interval );
                    if ($a+$sz*$interval <= $b)  $sz += 1;
                    $var->elementssize[] = $sz;
                    $var->elements[] = array($a, $b, $interval);
                }
                else {
                    $var->elementssize[] = 1;
                    $var->elements[] = floatval($ele);
                }
                $var->numelement = array_sum($var->elementssize);
            }
            else if ( preg_match('/^'.$set_tuple_format.'$/', $expression) ) {
                $var->type = 'set_tuple';   // a set of tuple of number, in the form of {(1,2),(3,4)}
                $trimmedbracket = substr( trim(substr($var->expression,1,-1)),1,-1 );
                $var->elementsraw = preg_split('/\)\s*,\s*\(/', $trimmedbracket);
                $tuple_size = count(explode(',',$var->elementsraw[0]));
                foreach ($var->elementsraw as $raw)  {
                    $numbers = $this->parse_tuple('('.$raw.')');    // it should have no error cos it is checked as a tuple
                    if (count($numbers) != $tuple_size)  throw new Exception(get_string('error_randvars_tuple_size','qtype_coordinates') . $name);
                    $var->elements[] = $numbers;
                }
                $var->numelement = count($var->elementsraw);
            }
            else if ( preg_match('/^shuffle\s*('.$tuple_format.')$/', $expression, $matches) ) {
                $var->type = 'shuffle';
                $var->elements = $this->parse_tuple($matches[1]);
                //$var->numelement = 0;
            }
            else throw new Exception(get_string('error_randvars_general','qtype_coordinates') . $name);
            $parsedvars[$name] = $var;
        }
        return $parsedvars;
    }
    
    
    /**
     * Instantiate a particular set of random variables. No throw
     * 
     * @param object $parsedvars It should the object returned by the parse_random_variables()
     * @return An array of variable name => value pairs (Note value can be a tuple)
     */
    function instantiate_random_variables($parsedvars) {
        foreach ($parsedvars as $name => $var) {
            if ( $var->type == 'set_number' ) {
                $id = mt_rand(0,$var->numelement-1);
                foreach ($var->elementssize as $eleidx => $sz) {
                    if ( $id < $sz) {
                        $this->add( $name , is_array($v = $var->elements[$eleidx]) ? $v[0] + $id*$v[2] : $v );
                        break;
                    }
                    $id -= $sz;
                }
            }
            else if ( $var->type == 'set_tuple' ) {
                $id = mt_rand(0,$var->numelement-1);
                $this->add( $name , $var->elements[$id] );
            }
            else if ( $var->type == 'shuffle') {
                $tmp = $var->elements;
                shuffle($tmp);
                $this->add( $name , $tmp );
            }
        }
    }
    
    
    /**
     * Check the validity of formula. From calculated question type. Modified.
     * 
     * @param string $formula The input formula
     * @return false for possible valid formula, otherwise error message
     */
    function find_formula_errors($formula) {
    /// Validates the formula submitted from the question edit page.
    /// Returns false if everything is alright.
    /// Otherwise it constructs an error message
        // Strip away dataset names
        while (preg_match('~\\{[[:alpha:]0-9_][^>} <{"\']*\\}(\[[0-9]+\])?~', $formula, $regs)) {
            $formula = str_replace($regs[0], '1', $formula);
        }

        // Strip away empty space and lowercase it
        $formula = strtolower(str_replace(' ', '', $formula));

        $safeoperatorchar = '-+/*%>:^\~<?=&|!'; /* */
        $operatorornumber = "[$safeoperatorchar.0-9eE]";

        while ( preg_match("~(^|[$safeoperatorchar,(])([a-z0-9_]*)\\(($operatorornumber+(,$operatorornumber+((,$operatorornumber+)+)?)?)?\\)~",
                $formula, $regs)) {

            switch ($regs[2]) {
                // Simple parenthesis
                case '':
                    if ($regs[4] || strlen($regs[3])==0) {
                        return get_string('illegalformulasyntax', 'quiz', $regs[0]);
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
                    if (!empty($regs[4]) || empty($regs[3])) {
                        return get_string('functiontakesonearg','quiz',$regs[2]);
                    }
                    break;

                // Functions that take one or two arguments
                case 'log': case 'round':
                    if (!empty($regs[5]) || empty($regs[3])) {
                        return get_string('functiontakesoneortwoargs','quiz',$regs[2]);
                    }
                    break;

                // Functions that must have two arguments
                case 'atan2': case 'fmod': case 'pow':
                    if (!empty($regs[5]) || empty($regs[4])) {
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
                $formula = preg_replace("~^$regs[2]\\([^)]*\\)~", '1', $formula);
            }
        }

        if (preg_match("~[^$safeoperatorchar.0-9eE]+~", $formula, $regs)) {
            return get_string('illegalformulasyntax', 'quiz', $regs[0]);
        } else {
            // Formula just might be valid
            return false;
        }

    }
    
}


?>

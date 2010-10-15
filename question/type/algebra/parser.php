<?php

// Parser code for the Moodle Algebra question type
// Moodle algebra question type class
// Author: Roger Moore <rwmoore 'at' ualberta.ca>
// License: GNU Public License version 3
    
    
// From the PHP manual: check for the existance of lcfirst and
// if not found create one.
if(!function_exists('lcfirst')) {
	/**
	 * Make a string's first character lowercase
	 *
	 * @param string $str
	 * @return string the resulting string.
	 */
	function lcfirst( $str ) {
		$str[0] = strtolower($str[0]);
		return (string)$str;
	}
}

/**
 * Helper function which will compare two strings using their length only.
 *
 * This function is intended for use in sorting arrays of strings by their string
 * length. This is used to order arrays for regular expressions so that the longest
 * expressions are checked first.
 *
 * @param $a first string to compare
 * @param $b second string to compare
 * @return -1 if $a is longer than $b, 0 if they are the same length and +1 if $a is shorter
 */
function qtype_algebra_parser_strlen_sort($a,$b) {
    // Get the two string lengths once so we don't have to repeat the function call
    $alen=strlen($a);
    $blen=strlen($b);
    // If the two lengths are equal return zero
    if($alen==$blen) return 0;
    // Otherwise return +1 if a>b or -1 if a<b
    return ($alen>$blen) ? -1 : +1;
}
    

/**
 * Class which represents a single term in an algebraic expression.
 *
 * A single algebraic term is considered to be either an operation, for example addition,
 * subtraction, raising to a power etc. or something operated on, such as a number or
 * variable. Each type of term implements a subclass of this base class.
 */
class qtype_algebra_parser_term {
    
    /**
     * Constructor for the generic parser term.
     *
     * This method is called by all subclasses to initialize the base class for use.
     * It initializes the number of arguments required, the format strings to use
     * when converting the term in various strng formats, the parser text associated
     * with the term and whether the term is one which commutes.
     *
     * @param $nargs number of arguments which this type of term requires
     * @param $formats an array of the format strings for this term keyed by type
     * @param $text the text from the expression associated with the array
     * @param $commutes if set to true then this term commutes (only for 2 argument terms)
     */
    function qtype_algebra_parser_term($nargs,$formats,$text='',$commutes=false) {
        $this->_value=$text;
        $this->_nargs=$nargs;
        $this->_formats=$formats;
        $this->_commutes=$commutes;
    }

    /**
     * Generates the list of arguments needed when converting the term into a string.
     *
     * This method returns an array with the arguments needed when converting the term
     * into a string. The arrys can then be used with a format string to generate the
     * string representation. The method is recursive because it needs to convert the
     * arguments of the term into strings and so it will walk down the parse tree.
     *
     * @param $method name of method to call to convert arguments into strings
     * @return array of the arguments that, with a format string, can be passed to sprintf
     */
    function print_args($method) {
        // Create an empty array to store the arguments in
        $args=array();
        // Handle zero argument terms differently by making the 
	    // first 'argument' the value of the term itself
        if($this->_nargs==0) {
            $args[]=$this->_value;
        } else {
            foreach($this->_arguments as $arg) {
                $args[]=$arg->$method();
            }
        }
        // Return the array of arguments
        return $args;
    }
    
    /**
     * Produces a 'prettified' string of the expression using the standard input syntax.
     *
     * This method will use the {@link print_args} method to convert the term and all its
     * arguments into a string.
     *
     * @return input syntax format string of the expression
     */
    function str() {
        // First check to see if the class has been given all the arguments
		$this->check_arguments();
        // Get an array of all the arguments except for the format string
        $args=$this->print_args('str');
	    // Insert the format string at the front of the argument array
	    array_unshift($args,$this->_formats['str']);
        // Call sprintf using the argument array as the arguments
        return call_user_func_array('sprintf',$args);
    }

    /**
     * Produces a LaTeX formatted string of the expression.
     *
     * This method will use the {@link print_args} method to convert the term and all its
     * arguments into a LaTeX formatted string. This can then be given to the main Moodle
     * engine, with TeX filter enabled, to produce a graphical representation of the
     * expression.
     *
     * @return LaTeX format string of the expression
     */
    function tex() {
        // First check to see if the class has been given all the arguments
		$this->check_arguments();
        // Get an array of all the arguments except for the format string
        $args=$this->print_args('tex');
	    // Insert the format string at the front of the argument array
	    array_unshift($args,$this->_formats['tex']);
        // Call sprintf using the argument array as the arguments
        return call_user_func_array('sprintf',$args);
    }
    
    /**
     * Produces a SAGE formatted string of the expression.
     *
     * This method will use the {@link print_args} method to convert the term and all its
     * arguments into a SAGE formatted string. This can then be passed to SAGE via XML-RPC
     * for symbolic comparisons. The format is very similar to the {@link str} method but
     * has all multiplications made explicit with an asterix.
     *
     * @return SAGE format string of the expression
     */
    function sage() {
        // First check to see if the class has been given all the arguments
		$this->check_arguments();
        // Get an array of all the arguments except for the format string
        $args=$this->print_args('sage');
	    // Insert the format string at the front of the argument array. First we
	    // check to see if there is a format element called 'sage' if not then we
        // default to the standard string format
		if(array_key_exists('sage',$this->_formats)) {
            // Insert the sage format string at the front of the argument array
            array_unshift($args,$this->_formats['sage']);
		} else {
            // Insert the normal format string at the front of the argument array
            array_unshift($args,$this->_formats['str']);
		}
        // Call sprintf using the argument array as the arguments
        return call_user_func_array('sprintf',$args);
    }
    
    /**
     * Sets the arguments of the term to the values in the given array.
     *
     * The code here overrides the base class's method. The code uses this method to actually
     * set the arguments in the given array but a second stage to choose the format of the
     * multiplication operator is required. This is because a 'x' symbol is required when
     * multiplying two numbers. However this can be omitted when multiplying two variables,
     * a variable and a function etc.
     *
     * @param $args array to set the arguments of the term to
     */
    function set_arguments($args) {
        if (count($args)!=$this->_nargs) {
            throw new Exception(get_string('nargswrong','qtype_algebra_parser',$this->_value));
        }
        $this->_arguments=$args;
    }
	
    /**
     * Checks to ensure that the correct number of arguments are defined.
     *
     * Note that this method just checks for the number or arguments it does not check
     * whether they are valid arguments. If the parameter passed is true (default value)
     * an exception will be thrown if the correct number of arguments are not present. Otherwise
     * the function returns false.
     *
     * @param $exc if true then an exception will be thrown if the number of arguments is incorrect
     * @return true if the correct number of arguments are present, false otherwise
     */
	function check_arguments($exc=true) {
		$retval=(count($this->_arguments)==$this->_nargs);
		if($exc && !$retval) {
           throw new Exception(get_string('nargswrong','qtype_algebra_parser',$this->_value));
        } else {
			return $retval;
        }
	}

    /**
     * Returns a list of all the variable names found in the expression.
     *
     * This method uses the {@link collect} method to walk down the parse tree and collect
     * a list of all the variables which the parser has found in the expression. The names
     * of the variables are then returned.
     *
     * @return an array containing all the variables names in the expression
     */
	function get_variables() {
		$list=array();
		$this->collect($list,'qtype_algebra_parser_variable');
		return array_keys($list);
	}
	
    /**
     * Returns a list of all the function names found in the expression.
     *
     * This method uses the {@link collect} method to walk down the parse tree and collect
     * a list of all the functions which the parser has found in the expression. The names
     * of the functions are then returned.
     *
     * @return an array containing all the function names used in the expression
     */
	function get_functions() {
		$list=array();
		$this->collect($list,'qtype_algebra_parser_function');
		return array_keys($list);
	}
	
    /**
     * Collects all the terms of a given type with unique values in the parse tree
     *
     * This method walks recursively down the parse tree by calling itself for the arguments
     * of the current term. The method simply adds the current term to the given imput array
     * using a key set to the value of the term but only if the term matches the selected type.
     * In this way terms only a single entry per term value is return which is the functionality
     * required for the {@link get_variables} and {@link get_functions} methods.
     *
     * @param $list the array to add the term to if it matches the type
     * @param $type the name of the type of term to collect.
     * @return an array containing all the terms of the selected type keyed by their value
     */
	function collect(&$list,$type) {
        // Add this class to the list if of the correct type
        if(is_a($this,$type)) {
            // Add a key to the array with the value of the term, this means
            // that multiple terms with the same value will overwrite each
            // other so only one will remain.
            $list[$this->_value]=0;
        }
        // Now loop over all the argument for this term (if any) and check them
		foreach($this->_arguments as $arg) {
			// Collect terms from the arguments as well
			$arg->collect($list,$type);
		}
	}
    
    /**
     * Checks to see if this term is equal to another term ignoring arguments.
     *
     * This method compares the current term to another term. The default method simply compares
     * the class of each term. Terms which require more than this, for example comparing values
     * too, override this method in theor own classes.
     *
     * @param $term the term to compare to the current one
     * @return true if the terms match, false otherwise
     */
    function equals($term) {
        // Default method just checks to ensure that the Terms are both of the same type
        return is_a($term,get_class($this));
    }
    
    /**
     * Compares this term, including any arguments, with another term.
     *
     * This method uses the {@link equals} method to see if the current and given term match.
     * It then looks at any arguments which the two terms have and, recursively, calls their
     * compare methods to determine if they also match. For terms with two arguments which
     * also commute the reverse ordering of the arguments is also tried if the first order
     * fails to match.
     *
     * @param $expr top level term of an expression to compare against
     * @return true if the expressions match, false otherwise
     */
    function equivalent($expr) {
        // Check that the argument is also a term
        if(!is_a($expr,'qtype_algebra_parser_term')) {
            throw new Exception(get_string('badequivtype','qtype_algebra_parser'));
        }
        // Now check that this term is the same as the given term
        if(!$this->equals($expr)) {
            // Terms are not equal immediately return false since the two do not match
            return false;
        }
        // Now compare the arguments recursively...
        switch($this->_nargs) {
            case 0:
                // For zero arguments we already compared this class and found it the same so
                // because there are no arguments to check we are equivalent!
                return true;
            case 1:
                // For one argument we also need to compare the argument of each term
                return $this->_arguments[0]->equivalent($expr->_arguments[0]);
            case 2:
                // Now it gets interesting. First we compare the two arguments in the same
                // order and see what we get...
                if($this->_arguments[0]->equivalent($expr->_arguments[0]) and
                   $this->_arguments[1]->equivalent($expr->_arguments[1])) {
                    // Both arguments are equivalent so we have a match
                    return true;
                }
                // Otherwise if the operator commutes we can see if the first argument matches
                // the second argument and vice versa
                else if($this->_commutes and $this->_arguments[0]->equivalent($expr->_arguments[1]) and
                        $this->_arguments[1]->equivalent($expr->_arguments[0])) {
                    return true;
                } else {
                    return false;
                }
            default:
                throw new Exception(get_string('morethantwoargs','qtype_algebra_parser'));
        }
    }
    
    /**
     * Returns the number of arguments required by the term.
     *
     * @return the number of arguments required by the term
     */
    function n_args() {
        return $this->_nargs;
    }
    
    /**
     * Evaluates the term numerically using the given variable values.
     *
     * The given parameter array is keyed by the name of the variable and the numerical
     * value to assign it is stored in the array value. This method is an abstract one
     * which must be implemented by all subclasses. Failure to do so will generate an
     * exception when the method is called.
     *
     * @param $params array of values keyed by variable name
     * @return the numerical value of the term given the provided values for the variables
     */
    function evaluate($params) {
        throw new Exception(get_string('noevaluate','qtype_algebra_parser',$this->_value));
    }

    /**
     * Dumps the term and its arguments to standard out.
     *
     * This method will recursively call the entire parse tree attached to it and produce
     * a nicely formatted dump of the term structure. This is mainly useful for debugging
     * purposes.
     *
     * @param $indent string containing the indentation to use
     * @param $params variable values to use if an evaluation is also desired
     * @return a string indicating the type of the term
     */    
	function dump(&$params=array(),$indent='') {
		echo "$indent<Term type '".get_class($this).'\' with value \''.$this->_value;
        if(!empty($params)) {
            echo ' eval=\''.$this->evaluate($params)."'>\n";
        } else {
            echo "'>\n";
        }
        foreach($this->_arguments as $arg) {
            $arg->dump($params,$indent.'  ');
        }
	}
    
    /**
     * Special casting operator method to convert the term object to a string.
     *
     * This is primarily a debug method. It is called when the term object is cast into a
     * string, such as happens when echoing or printing it. It simply returns a string
     * indicating the type of the parser term.
     *
     * @return a string indicating the type of the term
     */    
	function __toString() {
		return '<Algebraic parser term of type \''.get_class($this).'\'>';
	}
        
    // Member variables
    var $_value;             // String of the actual term itself
    var $_arguments=array(); // Array of arguments in class form
    var $_formats;           // Array of format strings
    var $_nargs;             // Number of arguments for this term
}

/**
 * Class representing a null, or empty, term.
 *
 * This is the type of term returned when the parser is given an empty string to parse.
 * It takes no arguments and will never be found in a parser tree. This term is solely
 * to give a valid return type for an empty string condition and so avoids the need to
 * throw an exception in such cases.
 */
class qtype_algebra_parser_nullterm extends qtype_algebra_parser_term {

    /**
     * Constructs an instance of a null term.
     *
     * Initializes a null term class. Since this class represents nothing no special
     * initialization is required and no arguments are needed.
     */
	function qtype_algebra_parser_nullterm() {
		parent::qtype_algebra_parser_term(self::NARGS,self::$formats,'');
	}
    
    /**
     * Returns the array of arguments needed to convert this class into a string.
     *
     * Since this class is represented by an empty string which has no formatting fields
     * we override the base class method to return an empty array.
     *
     * @param $method name of method to call to convert arguments into strings
     * @return array of the arguments that, with a format string, can be passed to sprintf
     */
	function print_args($method) {
		return array();
	}
	
    /**
     * Evaluates the term numerically.
     *
     * Since this is an empty term we define the evaluation as zero regardless of the parameters.
     *
     * @param $params array of the variable values to use
     */
	function evaluate($params) {
        // Return something which is not a number
		return acos(2.0);
	}
	
	// Static class properties
    const NARGS=0;
	private static $formats=array('str' => '',
                                  'tex' => '');
}
	

/**
 * Class representing a number.
 *
 * All purely numerical quantities will be represented by this type of class. There are
 * two basic types of numbers: non-exponential and exponential. Both types are handled by
 * this single class.
 */
class qtype_algebra_parser_number extends qtype_algebra_parser_term {
 
    /**
     * Constructs an instance of a number term.
     *
     * This function initializes an instance of a number term using the string which
     * matches the number's regular expression.
     *
     * @param $text string matching the number regular expression
     */
    function qtype_algebra_parser_number($text='') {
        // Unfortunately PHP maths will only support a '.' as a decimal point and will not support
        // ',' as used in Danish, French etc. To allow for this we always convert any commas into
        // decimal points before we parse the string
        $text=str_replace(',','.',$text);
        $this->_sign='';
        // Now determine whether this is in exponent form or just a plain number
	    if(preg_match('/([\.0-9]+)E([-+]?\d+)/',$text,$m)) {
	        $this->_base=$m[1];
	        $this->_exp=$m[2];
            $eformats=array('str' => '%sE%s',
                            'tex' => '%s '.get_string('multiply','qtype_algebra_parser').' 10^{%s}');
	        parent::qtype_algebra_parser_term(self::NARGS,$eformats,$text);
    	} else {
	        $this->_base=$text;
	        $this->_exp='';
	        parent::qtype_algebra_parser_term(self::NARGS,self::$formats,$text);
	    }
    }
    
    /**
     * Sets this number to be negative.
     *
     * This method will convert the number into a nagetive one. It is called when
     * the parser finds a subtraction operator in front of the number which does
     * not have a variable or another number preceding it.
     */
    function set_negative() {
        // Prepend a minus sign to both the base and total value strings
        $this->_base='-'.$this->_base;
        $this->_value='-'.$this->_value;
        $this->_sign='-';
    }
    
    /**
     * Checks to see if this number is equal to another number.
     *
     * This is a two step process. First we use the base class equals method to ensure
     * that we are comparing two numbers. Then we check that the two have the same value.
     *
     * @param $expt the term to compare to the current one
     * @return true if the terms match, false otherwise
     */
    function equals($expr) {
        // Call the default method first to check type
        if(parent::equals($expr)) {
            return (float)$this->_value==(float)$expr->_value;
        } else {
            return false;
        }
    }
    
    /**
     * Generates the list of arguments needed when converting the term into a string.
     *
     * For number terms there are two possible formats: those with an exponent and those
     * without an exponent. This method determines which to use and then pushes the correct
     * arguments into the array which is returned.
     *
     * @param $method name of method to call to convert arguments into strings
     * @return array of the arguments that, with a format string, can be passed to sprintf
     */
    function print_args($method) {
        // When displaying the number we need to worry about whether to use a decimal point
        // or a comma depending on the language currently selected/ Do this by replacing the
        // decimal point (which we have to use internally because of the PHP math standard)
        // with the correct string from the language pack
        $base=str_replace('.',get_string('decimal','qtype_algebra_parser'),$this->_base);
        // Put the base part of the number into the argument array
        $args=array($base);
        // Check to see if we have an exponent...
        if($this->_exp) {
            // ...we do so add it to the argument array as well
	        $args[]=$this->_exp;
        }
        // Return the list of arguments
        return $args;
    }
    
    /**
     * Evaluates the term numerically.
     *
     * All this method does is return the string representing the number cast as a double
     * precision floating point variable.
     *
     * @param $params array of the variable values to use
     */
    function evaluate($params) {
        return doubleval($this->_value);
    }
    
    // Static class properties
    const NARGS=0;
    private static $formats=array('str' => '%s',
                                  'tex' => '%s ');
}

/**
 * Class representing a variable term in an algebraic expression.
 *
 * When the parser finds a text string which does not correspond to a function it creates
 * this type of term and puts the contents of that text into it. Variables with names
 * corresponding to the names of the greek letters are replaced by those letters when
 * rendering the term in LaTeX. Other variables display their first letter with all
 * subsequent letters being lowercase. This reduces confusion when rendering expressions
 * consisting of multiplication of two variables.
 */
class qtype_algebra_parser_variable extends qtype_algebra_parser_term {
    // Define the list of variable names which will be replaced by greek letters
	public static $greek = array (
		'alpha',
		'beta',
		'gamma',
		'delta',
		'epsilon',
		'zeta',
		'eta',
		'theta',
		'iota',
		'kappa',
		'lambda',
		'mu',
		'nu',
		'xi',
		'omicron',
		'pi',
		'rho',
		'sigma',
		'tau',
		'upsilon',
		'phi',
		'chi',
		'psi',
		'omega'
	 );
	
    /**
     * Constructor for an algebraic term cass representing a variable.
     *
     * Initializes an instance of the variable term subclass. The method is given the text
     * in the expression corresponding to the variable name. This is then parsed to get the
     * variable name which is split into a base and subscript. If the start of the string
     * matches the name of a greek letter this is taken as the base and the remainder as the
     * subscript. Failing that either the subscript must be explicitly specified using an
     * underscore character or the first character is taken as the base.
     *
     * @param $text text matching the variable name
     */
    function qtype_algebra_parser_variable($text) {
        // Create the array to store the regular expression matches in
        $m=array();
        // Set the sign of the variable to be empty
        $this->_sign='';
        // Try to match the text to a greek letter
        if(preg_match('/('.implode('|',self::$greek).')/A',$text,$m)) {
            // Take the base name of the variable to be the greek letter
            $this->_base=$m[1];
            // Extract the remaining characters for use as the subscript
            $this->_subscript=substr($text,strlen($m[1]));
            // If the first letter of the subscript is an underscore then remove it
            if($this->_subscript[0] == '_') {
                $this->_subscript=substr($this->_subscript,1);
            }
            // Call the base class constructor with the variable text set to the combination of the
            // base name and the subscript without an underscore between them
            parent::qtype_algebra_parser_term(self::NARGS,self::$formats['greek'],
                                              $this->_base.$this->_subscript);
        }
        // Otherwise we have a simple multi-letter variable name. Treat the fist letter as the base
        // name and the rest as the subscript
        else {
            // Get the variable's base name
            $this->_base=substr($text,0,1);
            // Now set the subscript to the remaining letters
            $this->_subscript=substr($text,1);
            // If the first letter of the subscript is an underscore then remove it
            if($this->_subscript[0] == '_') {
                $this->_subscript=substr($this->_subscript,1);
            }
            // Call the base class constructor with the variable text set to the combination of the
            // base name and the subscript without an underscore between them
            parent::qtype_algebra_parser_term(self::NARGS,self::$formats['std'],
                                              $this->_base.$this->_subscript);
        }
    }
    
    /**
     * Sets this variable to be negative.
     *
     * This method will convert the number into a nagetive one. It is called when
     * the parser finds a subtraction operator in front of the number which does
     * not have a variable or another number preceding it.
     */
    function set_negative() {
        // Set the sign to be a '-'
        $this->_sign='-';
    }
    
    /**
     * Generates the list of arguments needed when converting the term into a string.
     *
     * The string of the variable depends solely on the name and subscript and hence these
     * are the only two arguments returned in the array.
     *
     * @param $method name of method to call to convert arguments into strings
     * @return array of the arguments that, with a format string, can be passed to sprintf
     */
    function print_args($method) {
        return array($this->_sign,$this->_base,$this->_subscript);
    }

    /**
     * Evaluates the number numerically.
     *
     * Overrides the base class method to simply return the numerical value of the number the
     * class represents.
     *
     * @param $params array of values keyed by variable name
     * @return the numerical value of the term given the provided values for the variables
     */    
    function evaluate($params) {
        if($this->_sign=='-') {
            $mult=-1;
        } else {
            $mult=1;
        }
        if(array_key_exists($this->_value,$params)) {
            return $mult*doubleval($params[$this->_value]);
        } else {
            // Found an indefined variable. Cannot evaluate numerically so throw exception
            throw new Exception(get_string('undefinedvariable','qtype_algebra_parser',$this->_value));
        }
    }
    
    /**
     * Checks to see if this variable is equal to another variable.
     *
     * This is a two step process. First we use the base class equals method to ensure
     * that we are comparing two variables. Then we check that the two are the same variable.
     *
     * @param $expr the term to compare to the current one
     * @return true if the terms match, false otherwise
     */
    function equals($expr) {
        // Call the default method first to check type
        if(parent::equals($expr)) {
            return $this->_value==$expr->_value and $this->_sign==$expr->_sign;
        } else {
            return false;
        }
    }
    
    // Static class properties
    const NARGS=0;
    private static $formats=array(
		'greek' =>  array('str' => '%s%s%s',
                          'tex' => '%s\%s_{%s}'),
		'std'   =>  array('str' => '%s%s%s',
                          'tex' => '%s%s_{%s}')
    );
}


/**
 * Class representing a power operation in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the power
 * operator's syntax. The string which corresponds to the term is passed to the constructor
 * of this subclass.
 */
class qtype_algebra_parser_power extends qtype_algebra_parser_term {

    /**
     * Constructs an instance of a power operator term.
     *
     * This function initializes an instance of a power operator term using the string which
     * matches the power operator expression. Since this is simply the character representing
     * the operator it is not used except when producing a string representation of the term.
     *
     * @param $text string matching the term's regular expression
     */
    function qtype_algebra_parser_power($text) {
        parent::qtype_algebra_parser_term(self::NARGS,self::$formats,$text);
    }
    
    /**
     * Evaluates the power operation numerically.
     *
     * Overrides the base class method to simply return the numerical value of the power
     * operation. The method evaluates the two arguments of the term and then passes them to
     * the 'pow' function from the maths library.
     *
     * @param $params array of values keyed by variable name
     * @return the numerical value of the term given the provided values for the variables
     */    
    function evaluate($params) {
		$this->check_arguments();
		return pow(doubleval($this->_arguments[0]->evaluate($params)),
				   doubleval($this->_arguments[1]->evaluate($params)));
    }

    // Static class properties
    const NARGS=2;
    private static $formats=array(
        'str' => '%s^%s',
        'tex' => '%s^{%s}'
    );
}


/**
 * Class representing a divide operation in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the divide
 * operator's syntax. The string which corresponds to the term is passed to the constructor
 * of this subclass.
 */
class qtype_algebra_parser_divide extends qtype_algebra_parser_term {
        
    /**
     * Constructs an instance of a divide operator term.
     *
     * This function initializes an instance of a divide operator term using the string which
     * matches the divide operator expression. Since this is simply the character representing
     * the operator it is not used except when producing a string representation of the term.
     *
     * @param $text string matching the term's regular expression
     */
    function qtype_algebra_parser_divide($text) {
        parent::qtype_algebra_parser_term(self::NARGS,self::$formats,$text);
    }
    
    /**
     * Evaluates the divide operation numerically.
     *
     * Overrides the base class method to simply return the numerical value of the divide
     * operation. The method evaluates the two arguments of the term and then simply divides
     * them to get the return value.
     *
     * @param $params array of values keyed by variable name
     * @return the numerical value of the term given the provided values for the variables
     */    
    function evaluate($params) {
		$this->check_arguments();
        // Get the value we are trying to divide by
        $divby=$this->_arguments[1]->evaluate($params);
        // Check to see if this is zero
        if($divby==0) {
            // Check the sign of the other argument and use to determine whether we return
            // plus or minus infinity
            return INF*$this->_arguments[0]->evaluate($params);
        } else {
            return $this->_arguments[0]->evaluate($params)/$divby;
        }
    }

    // Static class properties
    const NARGS=2;
    private static $formats=array(
        'str' => '%s/%s',
        'tex' => '\\frac{%s}{%s}'
    );
}


/**
 * Class representing a multiplication operation in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the multiplication
 * operator's syntax. The string which corresponds to the term is passed to the constructor
 * of this subclass.
 */
class qtype_algebra_parser_multiply extends qtype_algebra_parser_term {

    /**
     * Constructs an instance of a multiplication operator term.
     *
     * This function initializes an instance of a multiplication operator term using the string which
     * matches the multiplication operator expression. Since this is simply the character representing
     * the operator it is not used except when producing a string representation of the term.
     *
     * @param $text string matching the term's regular expression
     */
    function qtype_algebra_parser_multiply($text) {
        $this->mformats=array('*' =>  array('str' => '%s*%s',
                                            'tex' => '%s '.get_string('multiply','qtype_algebra_parser').' %s'),
                              '.' =>  array('str' => '%s %s',
                                            'tex' => '%s %s',
                                            'sage'=> '%s*%s')
                              );
        parent::qtype_algebra_parser_term(self::NARGS,$this->mformats['*'],$text,true);
    }

    /**
     * Sets the arguments of the term to the values in the given array.
     *
     * This method sets the term's arguments to those in the given array.
     *
     * @param $args array to set the arguments of the term to
     */
    function set_arguments($args) {
        // First perform default argument setting method. This will generate
        // an error if there is a problem with the number of arguments
        parent::set_arguments($args);
        // Set the default explicit format
        $this->_formats=$this->mformats['*'];
        // Only allow the implicit multipication if the second argument is either a
        // special, variable, function or bracket and not negative. In all other cases the operator must be
        // explicitly written
        if(is_a($args[1],'qtype_algebra_parser_bracket') or
           is_a($args[1],'qtype_algebra_parser_variable') or
           is_a($args[1],'qtype_algebra_parser_special') or
           is_a($args[1],'qtype_algebra_parser_function')) {
            if(!method_exists($args[1],'set_negative') or $args[1]->_sign=='') {
                $this->_formats=$this->mformats['.'];
            } 
        }
    }
    
    /**
     * Evaluates the multiplication operation numerically.
     *
     * Overrides the base class method to simply return the numerical value of the multiplication
     * operation. The method evaluates the two arguments of the term and then simply multiplies
     * them to get the return value.
     *
     * @param $params array of values keyed by variable name
     * @return the numerical value of the term given the provided values for the variables
     */    
    function evaluate($params) {
		$this->check_arguments();
		return $this->_arguments[0]->evaluate($params)*
			   $this->_arguments[1]->evaluate($params);
    }

    // Static class properties
    const NARGS=2;
}


/**
 * Class representing a addition operation in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the addition
 * operator's syntax. The string which corresponds to the term is passed to the constructor
 * of this subclass.
 */
class qtype_algebra_parser_add extends qtype_algebra_parser_term {

    /**
     * Constructs an instance of a addition operator term.
     *
     * This function initializes an instance of a addition operator term using the string which
     * matches the addition operator expression. Since this is simply the character representing
     * the operator it is not used except when producing a string representation of the term.
     *
     * @param $text string matching the term's regular expression
     */
    function qtype_algebra_parser_add($text) {
        parent::qtype_algebra_parser_term(self::NARGS,self::$formats,$text,true);
    }
    
    /**
     * Evaluates the addition operation numerically.
     *
     * Overrides the base class method to simply return the numerical value of the addition
     * operation. The method evaluates the two arguments of the term and then simply adds
     * them to get the return value.
     *
     * @param $params array of values keyed by variable name
     * @return the numerical value of the term given the provided values for the variables
     */    
    function evaluate($params) {
		$this->check_arguments();
		return $this->_arguments[0]->evaluate($params)+
			   $this->_arguments[1]->evaluate($params);
    }

    // Static class properties
    const NARGS=2;
    private static $formats=array(
        'str' => '%s+%s',
        'tex' => '%s + %s'
    );
}


/**
 * Class representing a subtraction operation in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the subtraction
 * operator's syntax. The string which corresponds to the term is passed to the constructor
 * of this subclass.
 */
class qtype_algebra_parser_subtract extends qtype_algebra_parser_term {

    /**
     * Constructs an instance of a subtraction operator term.
     *
     * This function initializes an instance of a subtraction operator term using the string which
     * matches the subtraction operator expression. Since this is simply the character representing
     * the operator it is not used except when producing a string representation of the term.
     *
     * @param $text string matching the term's regular expression
     */
    function qtype_algebra_parser_subtract($text) {
        parent::qtype_algebra_parser_term(self::NARGS,self::$formats,$text);
    }
    
    /**
     * Evaluates the subtraction operation numerically.
     *
     * Overrides the base class method to simply return the numerical value of the subtraction
     * operation. The method evaluates the two arguments of the term and then simply subtracts
     * them to get the return value.
     *
     * @param $params array of values keyed by variable name
     * @return the numerical value of the term given the provided values for the variables
     */    
    function evaluate($params) {
		$this->check_arguments();
		return $this->_arguments[0]->evaluate($params)-
			   $this->_arguments[1]->evaluate($params);
    }

    // Static class properties
    const NARGS=2;
    private static $formats=array(
        'str' => '%s-%s',
        'tex' => '%s - %s'
    );
}


/**
 * Class representing a special constant in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the a predefined
 * special constant such as pi or 'e' (from natural logarithms).
 */
class qtype_algebra_parser_special extends qtype_algebra_parser_term {

    /**
     * Constructs an instance of a special constant term.
     *
     * This function initializes an instance of a special term using the string which
     * matches the regular expression of a special constant.
     *
     * @param $text string matching a constant's regular expression
     */
    function qtype_algebra_parser_special($text) {
        parent::qtype_algebra_parser_term(self::NARGS,self::$formats[$text],$text);
        $this->_sign='';
    }
    
    /**
     * Sets this special to be negative.
     *
     * This method will convert the number into a nagetive one. It is called when
     * the parser finds a subtraction operator in front of the number which does
     * not have a variable or another number preceding it.
     */
    function set_negative() {
        // Set the sign to be a '-'
        $this->_sign='-';
    }
    
    /**
     * Evaluates the special constant numerically.
     *
     * Overrides the base class method to simply return the numerical value of the special
     * constant which is defined by an internal switch based on the constant's name.
     *
     * @param $params array of values keyed by variable name
     * @return the numerical value of the term given the provided values for the variables
     */    
    function evaluate($params) {
        if($this->_sign=='-') {
            $mult=-1;
        } else {
            $mult=1;
        }
        switch($this->_value) {
            case 'pi':
                return $mult*pi();
            case 'e':
                return $mult*exp(1);
            default:
                return 0;
        }
    }

    /**
     * Checks to see if this constant is equal to another term.
     *
     * This is a two step process. First we use the base class equals method to ensure
     * that we are comparing two variables. Then we check that the two are the same constant.
     *
     * @param $expr the term to compare to the current one
     * @return true if the terms match, false otherwise
     */
    function equals($expr) {
        // Call the default method first to check type
        if(parent::equals($expr)) {
            return $this->_value==$expr->_value and $this->_sign==$this->_sign;
        } else {
            return false;
        }
    }
    
    // Static class properties
    const NARGS=0;
    private static $formats=array(
		'pi' =>  array(  'str' => '%spi',
					     'tex' => '%s\\pi'),
		'e'  =>  array(  'str' => '%se',
						 'tex' => '%se')
	);
}


/**
 * Class representing a function in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the function's
 * syntax. The string which corresponds to the term is passed to the constructor
 * of this subclass.
 */
class qtype_algebra_parser_function extends qtype_algebra_parser_term {

    /**
     * Constructs an instance of a function term.
     *
     * This function initializes an instance of a function term using the string which
     * matches the name of a function.
     *
     * @param $text string matching the function's regular expression
     */
    function qtype_algebra_parser_function($text) {
        if(!function_exists($text) and !array_key_exists($text,self::$fnmap)) {
            throw new Exception(get_string('undefinedfunction','qtype_algebra_parser',$text));
        }
        $formats=array( 'str'   =>  '%s'.$text.'%s');
		if(array_key_exists($text,self::$texmap)) {
            $formats['tex']='%s'.self::$texmap[$text].' %s';
		} else {
            $formats['tex']='%s\\'.$text.' %s';
		}
        $this->_sign='';
        parent::qtype_algebra_parser_term(self::NARGS,$formats,$text);
    }

    /**
     * Sets this function to be negative.
     *
     * This method will convert the function into a negative one. It is called when
     * the parser finds a subtraction operator in front of the function which does
     * not have a variable or another number preceding it e.g. 3*-sin(x)
     */
    function set_negative() {
        // Set the sign to be a '-'
        $this->_sign='-';
    }
    
    /**
     * Sets the arguments of the term to the values in the given array.
     *
     * The code here overrides the base class's method. The code uses this method to actually
     * set the arguments in the given array but a second stage to insert brackets around the
     * function's argument is required.
     *
     * @param $args array to set the arguments of the term to
     */
    function set_arguments($args) {
        if(count($args)!=$this->_nargs) {
            throw new Exception(get_string('badfuncargs','qtype_algebra_parser',$this->_value));
        }
        if(!is_a($args[0],'qtype_algebra_parser_bracket')) {
            // Check to see if this function requires a special bracket
            if(in_array($this->_value,self::$bracketmap)) {
                $b=new qtype_algebra_parser_bracket('<');
            }
            // Does not require special brackets so create normal ones
            else {
                $b=new qtype_algebra_parser_bracket('(');
            }
            $b->set_arguments($args);
            $this->_arguments=array($b);
        }
        // First term already a bracket
        else {
            // Check to see if we need a special bracket
            if(in_array($this->_value,self::$bracketmap)) {
                // Make the bracket special
                $args[0]->make_special();
            }
            // Set the arguments to the given type
            $this->_arguments=$args;
        }
    }

    /**
     * Generates the list of arguments needed when converting the term into a string.
     *
     * The string of the function depends solely on the function argument and the sign.
     * The name has already been coded in at construction time.
     *
     * @param $method name of method to call to convert arguments into strings
     * @return array of the arguments that, with a format string, can be passed to sprintf
     */
    function print_args($method) {
        // First ensure that there are the correct number of arguments
		$this->check_arguments();
        return array($this->_sign,$this->_arguments[0]->$method());
    }
    
    /**
     * Evaluates the function numerically.
     *
     * Overrides the base class method to simply return the numerical value of the function.
     * Each function name is first checked against an internal map to determine the corresponding
     * PHP math function to call. If the function is not in the map it is assumed to already be
     * the correct name for a PHP math function.
     *
     * @param $params array of values keyed by variable name
     * @return the numerical value of the term given the provided values for the variables
     */    
    function evaluate($params) {
        // First ensure that there are the correct number of arguments
		$this->check_arguments();
        // Get the correct sign to multiply the value by
        if($this->_sign=='-') {
            $mult=-1;
        } else {
            $mult=1;
        }
        // Check to see if there is an entry to map the function name to a PHP function 
        if(array_key_exists($this->_value,self::$fnmap)) {
            $func=self::$fnmap[$this->_value];
            return $mult*$func($this->_arguments[0]->evaluate($params));
        }
        // No map entry so the function name must already be a PHP function...
        else {
            $tmp=$this->_value;
            return $mult*$tmp($this->_arguments[0]->evaluate($params));
        }
    }

    /**
     * Checks to see if this function is equal to another term.
     *
     * This is a two step process. First we use the base class equals method to ensure
     * that we are comparing two variables. Then we check that the two are the same constant.
     *
     * @param $expr the term to compare to the current one
     * @return true if the terms match, false otherwise
     */
    function equals($expr) {
        // Call the default method first to check type
        if(parent::equals($expr)) {
            return $this->_value==$expr->_value and $this->_sign==$this->_sign;
        } else {
            return false;
        }
    }
    
    // Static class properties
    const NARGS=1;
    public static $fnmap = array ('ln'  => 'log',
                                  'log' => 'log10'
                                  );
	public static $texmap = array('asin' => '\\sin^{-1}',
                                  'acos' => '\\cos^{-1}',
                                  'atan' => '\\tan^{-1}',
                                  'sqrt' => '\\sqrt'
                                  );
    // List of functions requiring special brackets
    public static $bracketmap = array ('sqrt'
                                       );    
}


/**
 * Class representing a bracket operation in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the bracket
 * operator's syntax. The string which corresponds to the term is passed to the constructor
 * of this subclass. Note that a pair of brackets is treated as a single term. There are no
 * separate open and close bracket operators.
 */
class qtype_algebra_parser_bracket extends qtype_algebra_parser_term {

    function qtype_algebra_parser_bracket($text) {
        parent::qtype_algebra_parser_term(self::NARGS,self::$formats[$text],$text);
        $this->_open=$text;
        switch($this->_open) {
            case '(':
                $this->_close=')';
                break;
            case '[':
                $this->_close=']';
                break;
            case '{':
                $this->_close='}';
                break;
            // Special kind of bracket. This behaves as normal brackets for a string but as invisible
            // curly brackets '{}' with LaTeX.
            case '<':
                $this->_close='>';
                break;
        }
    }
            
    /**
     * Evaluates the bracket operation numerically.
     *
     * Overrides the base class method to simply return the numerical value of the bracket
     * operation. The method evaluates the argument of the term, i.e. what is inside the
     * brackets, and then returns the value.
     *
     * @param $params array of values keyed by variable name
     * @return the numerical value of the term given the provided values for the variables
     */    
    function evaluate($params) {
        if(count($this->_arguments)!=$this->_nargs) {
            return 0;
        }
        return $this->_arguments[0]->evaluate($params);            
    }

    /**
     * Set the bracket type to 'special'.
     *
     * The method converts the bracket to the special type. The special type appears as a
     * normal bracket in string mode but produces the invisible curly brackets for LaTeX.
     */
    function make_special() {
        $this->_open='<';
        $this->_close='>';
        // Call the base class constructor as if this were a new instance of the bracket
        parent::qtype_algebra_parser_term(self::NARGS,self::$formats['<'],'<');
    }
    
    // Member variables
    var $_open='(';
    var $_close=')';
    
    // Static class properties
    const NARGS=1;
    private static $formats=array(
        '(' =>  array('str' => '(%s)',
                      'tex' => '\\left( %s \\right)'),
        '[' =>  array('str' => '[%s]',
                      'tex' => '\\left[ %s \\right]'),
        '{' =>  array('str' => '{%s}',
                      'tex' => '\\left\\lbrace %s \\right\\rbrace'),
        '<' =>  array('str' => '(%s)',
                      'tex' => '{%s}')
    );
}



/**
 * The main parser class.
 *
 * This class implements the methods needed to parse an expression. It uses a series of
 * regular expressions to indentify the different terms in the expression and then creates
 * instances of the correct subclass to handle them.
 */
class qtype_algebra_parser {
    // Special constants which the parser will understand
    public static $specials = array (
        'pi',
        'e'
    );
    
    // Functions which the parser will understand. These should all be standard PHP math functions.
    public static $functions = array ('sqrt',
                                      'ln',
                                      'log',
                                      'cosh',
                                      'sinh',
                                      'sin',
                                      'cos',
                                      'tan',
                                      'asin',
                                      'acos',
                                      'atan'
                                      );
        
    // Array to define the priority of the different operations. The parser implements the standard BODMAS priority:
    // brackets, order (power), division, mulitplication, addition, subtraction
    private static $priority = array (
        array('qtype_algebra_parser_power'),
        array('qtype_algebra_parser_function'),
        array('qtype_algebra_parser_divide','qtype_algebra_parser_multiply'),
        array('qtype_algebra_parser_add','qtype_algebra_parser_subtract')
    );
    
    // Regular experssion to match an open bracket
    private static $OPENB        = '/[\{\(\[]/A';
    // Regular experssion to match a close bracket
    private static $CLOSEB       = '/[\}\)\]]/A';
    // Regular expression to match a plain float or integer number without exponent
    private static $PLAIN_NUMBER = '(([0-9]+(\.|,)[0-9]*)|([0-9]+)|((\.|,)[0-9]+))';
    // Regular expression to match a float or integer number with an exponent
    private static $EXP_NUMBER   = '(([0-9]+(\.|,)[0-9]*)|([0-9]+)|((\.|,)[0-9]+))E([-+]?\d+)';
    // Array to associate close brackets with the correct open bracket type
    private static $BRACKET_MAP  = array(')' => '(', ']' => '[', '}' => '{');
    
    /**
     * Constructor for the main parser class.
     *
     * This constructor initializes the token map of the main parser class. It constructs a map of 
     * regular expressions to class types. As it parses a string it uses these regular expressions to
     * find tokens in the input string which are then fed to the corresponding term class for
     * interpretation.
     */
    function qtype_algebra_parser() {
        $this->_tokens = array (
            array ('/(\^|\*\*)/A',                       	 'qtype_algebra_parser_power'    ),
            array ('/('.implode('|',self::$functions).')/A', 'qtype_algebra_parser_function'   ),
            array ('/\//A',                               	 'qtype_algebra_parser_divide'   ),
            array ('/\*/A',                              	 'qtype_algebra_parser_multiply' ),
            array ('/\+/A',                              	 'qtype_algebra_parser_add'      ),
            array ('/-/A',                               	 'qtype_algebra_parser_subtract' ),
            array ('/('.implode('|',self::$specials).')/A',  'qtype_algebra_parser_special'  ),
            array ('/('.self::$EXP_NUMBER.'|'.self::$PLAIN_NUMBER.')/A',	'qtype_algebra_parser_number'   ),
            array ('/[A-Za-z][A-Za-z0-9_]*/A',           	 'qtype_algebra_parser_variable' )
            );
    }

    /**
     * Parses a given string containing an algebric epxression and returns the corresponding parse tree.
     *
     * This method loops over the string using the regular expressions in the token map to break down the
     * string into tokens. These tokens are arranged into a structured stack, taking account of the
     * bracket structure. Finally then method calls the {@link interpret} method to convert the structured
     * token strings into a fully parsed term structure. The method can optionally be passed a list of
     * variables which are used in the expression. If such a list is passed then the parser will attempt
     * to match the current position in the string with one of these given variables before any other
     * token. When passing a variable list a third parameter allows a choice of whether to allow additional
     * undeclared variables. This defaults to false when a list of variables is passed and is ignored otherwise.
     *
     * @param $text string containing the expression to parse
     * @param $variables array containing known variable names
     * @param $undecvars whether to allow (true) undeclared variable names
     * @return top term of the parsed expression
     */
    function parse($text,$variables=array(),$undecvars=false) {
        // Create a regular expression to match the known variables if an array is specified
        if(!empty($variables)) {
            // Create an empty array to store the list of extra regular expressions to match
            $reextra=array();
            // Loop over all the variable names we are given
            foreach($variables as $var) {
                // Create a temporary varible term using the current name
                $tmpvar=new qtype_algebra_parser_variable($var);
                // If the variable name has a subscript then create a new regular expression to
                // search for which includes an underscore
                if(!empty($tmpvar->_subscript)) {
                    $reextra[]=$tmpvar->_base.'_'.$tmpvar->_subscript;
                }
            }
            // Merge the variable name array with the array of extra regular expressions to match
            $variables=array_merge($variables,$reextra);
            // Sort the array in order of increasing variable length in order to prevent 'x1' matching
            // a variable 'x' before 'x1'. Do this using a helper function, which will compare two
            // strings using their length only, and use this with the usort function.
            usort($variables,'qtype_algebra_parser_strlen_sort');
            // Generate a single regular expression which will match both all known variables
            $revar='/('.implode('|',$variables).')/A';
        } else {
            $revar='';
        }
        $i=0;
        // Create an array to store the parse tree
        $tree=array();
        // Create an array to act as a temporary storage stack. This stack is used to 
        // push higher levels of the parse tree as it is assembled from the expression
        $stack=array();
        // Array used to store the match results from regular expression searches
        $m=array();
        // Loop over the expression string moving along it using the offset variable $i while
        // there are still characters left to parse
        while($i<strlen($text)) {
            // Match any white space at the start of the string and 'remove' it by advancing
            // the pointer by the length of the string matching the regular expression white
            // space pattern
            if(preg_match('/\s+/A',substr($text,$i),$m)) {
                $i+=strlen($m[0]);
                // Return to the start of the loop in case this was white space characters at
                // the end of the string
                continue;
            }
            // Since we don't have any white space the first thing we look for (top priority)
            // are open brackets
            if(preg_match(self::$OPENB,substr($text,$i),$m)) {
                // Check for a non-operator and if one is found assume implicit multiplication
                if(count($tree)>0 and (is_array($tree[count($tree)-1]) or
                    (is_object($tree[count($tree)-1]) 
                     and $tree[count($tree)-1]->n_args()==0))) {
                    // Make the implicit assumption explicit by adding an appropriate
                    // multiplication operator
                    array_push($tree,new qtype_algebra_parser_multiply('*'));
                }
                // Push the current parse tree onto the stack
                array_push($stack,$tree);
                // Create a new parse tree starting with a bracket term
                $tree=array(new qtype_algebra_parser_bracket($m[0]));
                // Increment the string pointer by the length of the string that was matched
                $i+=strlen($m[0]);
                // Return to the start of the loop
                continue;
            }
            // Now see if we have a close bracket here
            if(preg_match(self::$CLOSEB,substr($text,$i),$m)) {
                // First check that the current parse tree has at least one term
                if(count($tree)==0) {
                    throw new Exception(get_string('badclosebracket','qtype_algebra_parser'));
                }
                // Now check that the current tree started with a bracket
                if(!is_a($tree[0],'qtype_algebra_parser_bracket')) {
                    throw new Exception(get_string('mismatchedcloseb','qtype_algebra_parser'));
                }
                // Check that the open and close bracket are of the same type
                else if($tree[0]->_value != self::$BRACKET_MAP[$m[0]]) {
                    throw new Exception(get_string('mismatchedbracket','qtype_algebra_parser',$tree[0]->_value.$m[0]));
                }                
                // Append the current tree to the tree one level up on the stack
                array_push($stack[count($stack)-1],$tree);
                // The new tree is the lowest level tree on the stack so we
                // pop the new tree off the stack
                $tree=array_pop($stack);
                $i+=strlen($m[0]);
                continue;
            }
            // If a list of predefined variables was given to the method then check for them here
            if(!empty($revar) and preg_match($revar,substr($text,$i),$m)) {
                // Check for a zero argument term preceding the variable and if there is one then
                // add the implicit multiplication operation
                if(count($tree)>0 and !is_array($tree[count($tree)-1]) and $tree[count($tree)-1]->n_args()==0) {
                    array_push($tree,new qtype_algebra_parser_multiply('*'));
                }
                // Increment the string index by the length of the variable's name
                $i+=strlen($m[0]);
                // Push a new variable term onto the parse tree
                array_push($tree,new qtype_algebra_parser_variable($m[0]));
                continue;
            }
            // Here we have not found any open or close brackets or known variables so we can
            // parse the string for a normal token
            foreach($this->_tokens as $token) {
                //echo 'Looking for token ',$token[1],"\n";
                if(preg_match($token[0],substr($text,$i),$m)) {
                    //echo 'Found a ',$token[1],"!\n";
                    // Check for a variable and throw an exception if undeclared variables are
                    // not allowed and a list of defined variables was passed
                    if(!empty($revar) and !$undecvars and $token[1]=='qtype_algebra_parser_variable') {
                        throw new Exception(get_string('undeclaredvar','qtype_algebra_parser',$m[0]));
                    }
                    // Check for a zero argument term preceding a variable or a function and then
                    // add the implicit multiplication
                    if(count($tree)>0 and ($token[1]=='qtype_algebra_parser_variable' or
                        $token[1]=='qtype_algebra_parser_function') and 
                        !is_array($tree[count($tree)-1]) and
                        $tree[count($tree)-1]->n_args()==0) {
                        array_push($tree,new qtype_algebra_parser_multiply('*'));
                    }
                    $i+=strlen($m[0]);
                    array_push($tree,new $token[1]($m[0]));
                    continue 2;
                }
            }
            throw new Exception(get_string('unknownterm','qtype_algebra_parser',substr($text,$i)));
        } // end while loop over tokens
        // If all the open brackets have been closed then the stack will be empty and the
        // tree will contain the entire parsed expression
        if(count($stack)>0) {
            throw new Exception(get_string('mismatchedopenb','qtype_algebra_parser'));
        }
        //print_r($tree);
        //print_r($stack);
        return $this->interpret($tree);
    }

    /**
     * Takes a structured token map and converts it into a parsed term structure.
     *
     * This is an internal method of the parser class and is called by the {@link parse}
     * method. It performs the final stage of the parsing process and returns the fully
     * parsed term structure.
     *
     * @param $tree structured token array
     * @return top term of the fully parsed structure
     */
    function interpret($tree) {
        // First check to see if we are passed anything at all. If not then simply
        // return a qtype_algebra_parser_nullterm
        if(count($tree)==0) {
			return new qtype_algebra_parser_nullterm();
        }
        // Now we check to see if this tree is inside brackets. If so then
		// we remove the bracket object from the tree and store it in a
		// temporary variable. We will then parse the remainder of the tree
		// and make the top level term the bracket's argument if applicable.
        if(is_a($tree[0],'qtype_algebra_parser_bracket')) {
            $bracket=array_splice($tree,0,1);
            $bracket=$bracket[0];
        } else {
            $bracket='';
        }
        // Next we loop over the tree and look for arrays. These represent
		// brackets inside our tree and so we need to process them first.
        for($i=0;$i<count($tree);$i++) {
            // Check for a list type if we find one then replace
            // it with the interpreted term
            if(is_array($tree[$i])) {
                $tree[$i]=$this->interpret($tree[$i]);
            }
        }
        // The next job is to check the subtraction operations to determine whether they are
        // really subtraction operations or whether they are minus signs for negative numbers
        $toremove=array();
        for($i=0;$i<count($tree);$i++) {
            // Check that this element is an addition or subtraction operator
            if(is_a($tree[$i],'qtype_algebra_parser_subtract') or is_a($tree[$i],'qtype_algebra_parser_add')) {
                // Check whether the precedding argument (if there is one) is a number or
                // a variable. In either case this is a addition/subtraction operation so we continue
                if($i>0 and (is_a($tree[$i-1],'qtype_algebra_parser_variable') or 
                             is_a($tree[$i-1],'qtype_algebra_parser_number') or
                             is_a($tree[$i-1],'qtype_algebra_parser_bracket'))) {
                    continue;
                }
                // Otherwise we have found a minus sign indicating a positive or negative quantity...
                else {
                    // Check that we do have a number following otherwise generate an exception...
                    if($i==(count($tree)-1) or !method_exists($tree[$i+1],'set_negative')) {
                        throw new Exception(get_string('illegalplusminus','qtype_algebra_parser'));
                    }
                    // If we have a subtract operation then we need to make the following number negative
                    if(is_a($tree[$i],'qtype_algebra_parser_subtract')) {
                        // Set the number to be negative
                        $tree[$i+1]->set_negative();
                    }
                    // Add the term to the removal list
                    $toremove[$i]=1;
                }
            }
        }
        // Remove the elements from the tree who's keys are found in the removal list
        $tree=array_diff_key($tree,$toremove);
        // Re-key the tree array so that the keys are sequential
        $tree=array_values($tree);
        foreach(self::$priority as $ops) {
            $i=0;
            //echo 'Looking for ',$ops,"\n";
            while($i<count($tree)) {
                if(in_array(get_class($tree[$i]),$ops)) {
                    //echo 'Found a ',get_class($tree[$i]),"\n";
                    if($tree[$i]->n_args()==1) {
                        if(($i+1)<count($tree)) {
                            $tree[$i]->set_arguments(array_splice($tree,$i+1,1));
                            $i++;
                            continue;
                        } else {
                            throw new Exception(get_string('missingonearg','qtype_algebra_parser',$op));
                        }
                    } elseif($tree[$i]->n_args() == 2) {
                        if($i>0 and $i<(count($tree)-1)) {
                            $tree[$i]->set_arguments(array($tree[$i-1],
                                                    $tree[$i+1]));
                            array_splice($tree,$i+1,1);
                            array_splice($tree,$i-1,1);
                            continue;
                        } else {
                            throw new Exception(get_string('missingtwoargs','qtype_algebra_parser',$op));
                        }
                    }
                } else {
                    $i++;
                }
            }
        }
		// If there are no terms in the parse tree then we were passed an empty string
		// in which case we create a null term and return it
		if(count($tree)==0) {
			return new qtype_algebra_parser_nullterm();
		} else if(count($tree)!=1) {
            //print_r($tree);
            throw new Exception(get_string('notopterm','qtype_algebra_parser'));
        }
        if($bracket) {
            $bracket->set_arguments(array($tree[0]));
            return $bracket;
        } else {
            return $tree[0];
        }
    }
}

// Sort static arrays once here by inverse string length
usort(qtype_algebra_parser_variable::$greek,'qtype_algebra_parser_strlen_sort');
usort(qtype_algebra_parser::$functions,'qtype_algebra_parser_strlen_sort');
?>

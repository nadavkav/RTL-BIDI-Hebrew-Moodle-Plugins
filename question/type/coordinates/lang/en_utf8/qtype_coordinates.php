<?php
/**
 * The language strings for the coordinates question type.
 *    
 * @copyright &copy; 2010 Hon Wai, Lau
 * @author Hon Wai, Lau <lau65536@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * @package questionbank
 * @subpackage questiontypes
 */

$string['addingcoordinates'] = 'Adding a coordinates question';
$string['editingcoordinates'] = 'Editing a coordinates question';
$string['answerno'] = 'Subquestion answer $a';
$string['coordinates'] = 'Coordinates';

$string['globalvarshdr'] = 'Global variables and subquestion options';
$string['helponquestionoptions'] = 'For more information of the options, please click the help button at the top of this form.';
$string['varsrandom'] = 'Variables (random)';
$string['varsglobal'] = 'Variables (global)';
$string['retrymarkseq'] = 'Retry mark fraction sequence';
$string['peranswersubmit'] = 'Per answer submit button';
$string['showperanswermark'] = 'Per answer grading result';
$string['choiceyes'] = 'Yes';
$string['choiceno'] = 'No';
$string['showoptions'] = 'Show';
$string['correctnessraw'] = 'Raw condition';

$string['placeholder'] = 'Placeholder name';
$string['answermark'] = 'Default answer mark*';
$string['vars1'] = 'Variables (local)';
$string['answer'] = 'Answer*';
$string['vars2'] = 'Variables (condition)';
$string['correctness'] = 'Correctness condition*';
$string['preunit'] = 'Pre-unit';
$string['postunit'] = 'Post-unit';
$string['unitpenalty'] = 'Mark penalty for wrong unit';
$string['ruleid'] = 'Basic conversion rules';
$string['otherrule'] = 'Other rules';
$string['subqtext'] = 'Subquestion text';
$string['feedback'] = 'Feedback';

$string['error_retry_mark_order'] = 'The retry mark sequence must be in descending order and its value must be between 0 and 1.';
$string['error_retry_mark_nonnumeric'] = 'The retry mark sequence contains non-numeric values.';
$string['error_no_answer'] = 'At least one answer is required.';
$string['error_mark'] = 'The answer mark must take value larger than 0.';
$string['error_placeholder_too_long'] = 'The size of placeholder must be smaller than 40.';
$string['error_placeholder_format'] = 'The format of placeholder is wrong or contain characters that is not allowed.';
$string['error_placeholder_missing'] = 'The placeholder does not appear in the question text.';
$string['error_placeholder_main_duplicate'] = 'There is more than one same placeholder exists in the question text.';
$string['error_placeholder_sub_duplicate'] = 'Same named placeholder cannot be used more than once in the subquestion answer part.';
$string['error_answer_missing'] = 'Comma separated answer must be given).';
$string['error_answer_empty'] = 'Answer contains some empty expressions.';
$string['error_correctness_missing'] = 'The correctness condition must be specified. (That can be evaluated to be either true or false)';
$string['error_vars_init'] = 'Only string and array can be used to initialize the class question_variables.';
$string['error_vars_end_separator'] = 'Missing an assignment separator at the end.';
$string['error_vars_parse'] = 'Parsing error of the variables text. Variables: ';
$string['error_vars_format'] = 'Variable is not in the allowed format: ';
$string['error_randvars_range'] = 'Range error for the variable ';
$string['error_randvars_tuple_size'] = 'The size of tuple is different in the variable ';
$string['error_randvars_general'] = 'The format is not correct for the random variable ';
$string['error_evaluation_bracket'] = 'Evaluation possibly contains variables that are not defined: ';
$string['error_evaluation_general'] = 'Evaluation failure for the expression: ';
$string['error_validation_eval'] = 'Trial evalution error! ';
$string['error_validation_parse_unit'] = 'Unit parsing error! ';
$string['error_validation_ruleid'] = 'No such rule exists in the file with the id/name.';
$string['error_validation_parse_rule'] = 'Rule parsing error! ';
$string['error_unitpenalty'] = 'The penalty must be a number between 0 and 1.';

?>

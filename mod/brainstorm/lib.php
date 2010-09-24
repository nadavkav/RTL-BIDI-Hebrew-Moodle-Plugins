<?PHP // $Id: lib.php,v 1.2 2004/08/24 16:36:18 cmcclean Exp $

/**
* Module Brainstorm V2
* @author Martin Ellermann
* @reengeniering Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/
include_once("{$CFG->dirroot}/mod/brainstorm/locallib.php");

$BRAINSTORM_MAX_RESPONSES = 12;
$BRAINSTORM_MAX_CATEGORIES = 8;
$BRAINSTORM_MAX_COLUMNS = 6;

define('PHASE_COLLECT', '0');
define('PHASE_PREPARE', '1');
define('PHASE_ORGANIZE', '2');
define('PHASE_DISPLAY', '3');
define('PHASE_FEEDBACK', '4');

/// Standard functions /////////////////////////////////////////////////////////

function brainstorm_user_outline($course, $user, $mod, $brainstorm) {
    if ($responses = brainstorm_get_responses($brainstorm->id, $user->id, 0, false)) {
        $responses_values = array_values($responses);
        
        /// printing last entered response for that user
        $result->info = '"'.$responses_values[count($responses_values) - 1]->response.'"';
        $result->time = $responses_values[count($responses_values) - 1]->timemodified;
        return $result;
    }
    return NULL;
}


function brainstorm_user_complete($course, $user, $mod, $brainstorm) {
    if ($responses = brainstorm_get_responses($brainstorm->id, $user->id, 0, false)) {
        $responses_values = array_values($responses);
        
        /// printing last entered response for that user
        $result->info = '"'.$responses_values[count($responses_values) - 1]->response.'"';
        $result->time = $responses_values[count($responses_values) - 1]->timemodified;
        echo get_string('responded', 'brainstorm').": $result->info , last updated ".userdate($result->time);
    } 
    else {
        print_string('notresponded', 'brainstorm');
    }
}

/**
* Given an object containing all the necessary data,
* (defined by the form in mod.html) this function
* will create a new instance and return the id number
* of the new instance.
* @param object $brainstorm
*/
function brainstorm_add_instance($brainstorm) {

    $brainstorm->timemodified = time();

    return insert_record('brainstorm', $brainstorm);
}

/**
* Given an object containing all the necessary data,
* (defined by the form in mod.html) this function
* will update an existing instance with new data.
* @param object $brainstorm
*/
function brainstorm_update_instance($brainstorm) {

    $brainstorm->id = $brainstorm->instance;
    $brainstorm->timemodified = time();

    $context = get_context_instance(CONTEXT_MODULE, $brainstorm->coursemodule);
    
    $oldrecord = get_record('brainstorm', 'id', $brainstorm->id);
    
    // check for some changes that imply some cleaning
    if ($oldrecord->singlegrade != $brainstorm->singlegrade){
        $participants = get_users_by_capability($context, 'mod/brainstorm:gradable', 'id,firstname,lastname', 'lastname');
        if ($brainstorm->singlegrade){ // we are setting up single grades. compile the single grade with dissociated
            foreach($participants as $participant){
                brainstorm_convert_to_single($brainstorm, $participant->id);
            }
        }
        else{ // we are setting dissociated grading for which we MUST delete grades
           delete_records('brainstorm_grades', 'brainstormid', $brainstorm->id);
        }
    }

    return update_record('brainstorm', $brainstorm);
}

/**
* Given an ID of an instance of this module,
* this function will permanently delete the instance
* and any data that depends on it.
* @param int $id
*/
function brainstorm_delete_instance($id) {
    if (! $brainstorm = get_record('brainstorm', 'id', "$id")) {
        return false;
    }

    $result = true;

    delete_records('brainstorm_operators', 'brainstormid', "$brainstorm->id");
    delete_records('brainstorm_operatordata', 'brainstormid', "$brainstorm->id");
    delete_records('brainstorm_responses', 'brainstormid', "$brainstorm->id");
    delete_records('brainstorm_categories', 'brainstormid', "$brainstorm->id");
    delete_records('brainstorm_grades', 'brainstormid', "$brainstorm->id");

    if (! delete_records('brainstorm', 'id', "$brainstorm->id")) {
        $result = false;
    }

    return $result;
}

/**
*
*
*/
function brainstorm_cron(){
    // TODO : may cleanup some old group rubish ??
}

/**
* Returns the users with data in one brainstorm
*(users with records in brainstorm_responses, participants)
* @uses CFG
* @param int $brainstormid
*/
function brainstorm_get_participants($brainstormid) {
    global $CFG;

    //Get all participants
    $sql = "
        SELECT DISTINCT 
            u.*
        FROM 
            {$CFG->prefix}user u,
            {$CFG->prefix}brainstorm_responses c
        WHERE 
            c.brainstormid = {$brainstormid} AND
            u.id = c.userid
    ";
    $participants = get_records_sql($sql);

    //Return students array (it contains an array of unique users)
    return ($participants);
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user. It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $newmoduleid ID of an instance of this module
 * @return mixed null or object with an array of grades and with the maximum grade
 **/
function brainstorm_grades($cmid) {
    global $CFG;

    if (!$module = get_record('course_modules', 'id', $cmid)){
        return NULL;
    }    

    if (!$brainstorm = get_record('brainstorm', 'id', $module->instance)){
        return NULL;
    }

    if ($brainstorm->scale == 0) { // No grading
        return NULL;
    }
    
    $context = get_context_instance(CONTEXT_MODULE, $cmid);

    $participants = get_users_by_capability($context, 'mod/brainstorm:gradable', 'u.id,lastname,firstname', 'lastname');
    if ($participants){
        foreach($participants as $participant){
            $gradeset = brainstorm_get_gradeset($brainstorm->id, $participant->id);
            if (!$gradeset) return null;
            if ($brainstorm->scale > 0 ){ // Grading numerically        
                if ($brainstorm->singlegrade){
                    $finalgrades[$participant->id] = $gradeset->single;
                }
                else{
                    if ($brainstorm->seqaccesscollect && isset($gradeset->participate)){
                        $total[] = $gradeset->participate;
                        $weights[] = $brainstorm->participationweight;
                    }
                    if ($brainstorm->seqaccessprepare && isset($gradeset->prepare)){
                        $total[] = $gradeset->prepare;
                        $weights[] = $brainstorm->preparingweight;
                    }
                    if ($brainstorm->seqaccessorganize && isset($gradeset->organize)){
                        $total[] = $gradeset->organize;
                        $weights[] = $brainstorm->organizeweight;
                    }
                    if ($brainstorm->seqaccessfeedback && isset($gradeset->feedback)){
                        $total[] = $gradeset->feedback;
                        $weights[] = $brainstorm->feedbackweight;
                    }
                    $totalweights = array_sum($weights);
                    $totalgrade = 0;
                    for($i = 0 ; $i < count(@$total) ; $i++){
                        $totalgrade += $total[$i] * $weights[$i];
                    }
                    $totalgrade = ($totalweights != 0) ? round($totalgrade / $totalweights) : 0 ;
                    $finalgrades[$participant->id] = $totalgrade;
                }
                $return->grades = @$finalgrades;
                $return->maxgrade = $brainstorm->scale;
                return $return;
            }
            else { // Scales
                $finalgrades = array();
                $scaleid = - ($brainstorm->grade);
                $maxgrade = '';
                if ($scale = get_record('scale', 'id', $scaleid)) {
                    $scalegrades = make_menu_from_list($scale->scale);
                }        
                if ($brainstorm->singlegrade){
                    $finalgrades[$participant->id] = $scalegrades($gradeset->single);
                }
                else{
                    if ($brainstorm->setaccesscollect){
                        $total[] = $scalegrades($gradeset->participate);
                        $weights[] = $brainstorm->participationweight;
                    }
                    if ($brainstorm->setaccessprepare){
                        $total[] = $scalegrades($gradeset->prepare);
                        $weights[] = $brainstorm->preparingweight;
                    }
                    if ($brainstorm->setaccessorganize){
                        $total[] = $scalegrades($gradeset->organize);
                        $weights[] = $brainstorm->organizeweight;
                    }
                    if ($brainstorm->setaccessfeedback){
                        $total[] = $scalegrades($gradeset->feedback);
                        $weights[] = $brainstorm->feedbackweight;
                    }
                    $totalweights = array_sum($weights);
                    $totalgrade = 0;
                    for($i = 0 ; $i < count(@$total) ; $i++){
                        $totalgrade += $total[$i] * $weights[$i];
                    }
                    $totalgrade = ($totalweights != 0) ? round($totalgrade / $totalweights) : 0 ;
                    $finalgrades[$participant->id] = $totalgrade;
                }
                $return->grades = @$final;
                $return->maxgrade = $maxgrade;
                return $return;
            }
        }
    }
    return null;
}

/**
 * This function returns if a scale is being used by one newmodule
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $newmoduleid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function brainstorm_scale_used($cmid, $scaleid) {
    $return = false;

    // note : scales are assigned using negative index in the grade field of brainstormer (see mod/assignement/lib.php) 
    $rec = get_record('brainstorm','id', $cmid, 'scale', -$scaleid);

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }
    return $return;
}

?>

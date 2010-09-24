<?PHP // $Id: lib.php,v 1.2 2004/08/24 16:36:18 cmcclean Exp $

/**
* Module Brainstorm V2
* @author Martin Ellermann
* @reengineering Valery Fremaux
* @package Brainstorm
* @date 20/12/2007
*/

/**
function brainstorm_get_responses($brainstormid, $userid=null, $groupid=0, $excludemyself=false, $sort='response'){
function brainstorm_count_responses($brainstormid, $userid=null, $groupid=0, $excludemyself=false){
function brainstorm_count_operatorinputs($brainstormid, $userid=null, $groupid=0, $excludemyself=false){
function brainstorm_get_operators($brainstormid){
function brainstorm_get_operatorlist($operators, $separator=','){
function brainstorm_save_operatorconfig($brainstormid, $operatorid){
function print_error_class($errors, $errorkeylist){
function print_error_box($errors){
function choose_multiple_from_menu($options, $name, $selected=null, $nothing='choose', $script='',
function brainstorm_get_accessclauses($userid=null, $groupid=0, $excludemyself=false){
function brainstorm_legal_include(){
function brainstorm_get_grades($brainstormid, $userids){
function brainstorm_get_gradeset($brainstormid, $userid){
function brainstorm_convert_to_single($brainstorm, $userid){
function brainstorm_get_ungraded($brainstormid, $userids){
function brainstorm_get_feedback($brainstormid, $userid=null){
function make_grading_menu(&$brainstorm, $id, $selected = '', $return = false){
function brainstorm_print_responses_cols(&$brainstorm, &$responses, $return = false, $printchecks = false){
function brainstorm_have_reports($brainstormid, &$participantids){
*/

/**
*
* @uses USER
* @param int $brainstormid
* @param int $userid
* @param int $groupid
* @param boolean $excludemyself
* @param string $sort
* @returns array of responses
*/
function brainstorm_get_responses($brainstormid, $userid=null, $groupid=0, $excludemyself=false, $sort='response'){
    global $USER;

    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);
    $select = "
        brainstormid = $brainstormid
        $accessClause
    ";
    if (!$responses = get_records_select('brainstorm_responses AS od', $select, $sort)) {
        $responses = array () ;
    }
    return $responses;
}

/**
*
* @param int $brainstormid
* @param int $userid
* @param int $groupid
* @param boolean $excludemyself
* @param string $sort
* @returns array of responses
*/
function brainstorm_count_responses($brainstormid, $userid=null, $groupid=0, $excludemyself=false){

    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);
    $select = "
        brainstormid = $brainstormid
        $accessClause
    ";

    return count_records_select('brainstorm_responses AS od', $select);
}

/**
*
* @param int $brainstormid
* @param int $userid
* @param int $groupid
* @param boolean $excludemyself
* @param string $sort
* @returns array of responses
*/
function brainstorm_count_operatorinputs($brainstormid, $userid=null, $groupid=0, $excludemyself=false){

    $accessClause = brainstorm_get_accessclauses($userid, $groupid, $excludemyself);
    $select = "
        brainstormid = $brainstormid
        $accessClause
    ";

    return count_records_select('brainstorm_operatordata AS od', $select);
}

/**
* a part of the operator plugin API
* @uses CFG
* @param int $brainstormid
* @returns array of operators
*/
function brainstorm_get_operators($brainstormid){
    global $CFG;

    $operators = array();

    $DIR = opendir($CFG->dirroot.'/mod/brainstorm/operators');
    while($opname = readdir($DIR)){
        if (!is_dir($CFG->dirroot.'/mod/brainstorm/operators/'.$opname)) continue;
        if (ereg("^(\\.|!)", $opname)) continue; // allows masking unused or unimplemented operators
        unset($operator);
        // real operator name
        $operator->id = $opname;
        $oprecord = get_record('brainstorm_operators', 'brainstormid', $brainstormid, 'operatorid', $opname);
        if ($oprecord){
            $operator->active = $oprecord->active;
            $operator->configdata = unserialize($oprecord->configdata);
        }
        else{
            $operator->active = 0;
            $operator->configdata = new Object();
        }
        $operators[$opname] = $operator;
    }
    return $operators;
}

/**
*
* @param array $operators
* @param string $separator
* @returns separated list of operator names
*/
function brainstorm_get_operatorlist($operators, $separator=','){
    $oparray = array();
    foreach($operators as $operator){
        if (!$operator->active) continue;
        $oparray[] = $operator->id;
    }
    return implode($separator, $oparray);
}

/**
* saves an operator configuration as a serialized object
* @param int $brainstormid
* @param int $operatorid
*/
function brainstorm_save_operatorconfig($brainstormid, $operatorid){
    $oprecord->id = get_field('brainstorm_operators', 'id', 'brainstormid', $brainstormid, 'operatorid', $operatorid);
    $configkeys = preg_grep("/^config_/", array_keys($_POST));
    foreach($configkeys as $akey){
        preg_match('/config_(.*)$/', $akey, $matches);
        $key = $matches[1];
        $configdata->$key = required_param($akey, PARAM_CLEANHTML);
    }
    $config = serialize($configdata);
    $oprecord->configdata = addslashes($config);
    if ($oprecord->id){
        if (!update_record('brainstorm_operators', $oprecord)){
            error("Could not update config record");
        }
    }
    else{
        if (!insert_record('brainstorm_operators', $oprecord)){
            error("Could not create config record");
        }
    }
}

/**
* adds an error css marker in case of matching error
* @param array $errors the current error set
* @param string $errorkey
*/
if (!function_exists('print_error_class')){
	function print_error_class($errors, $errorkeylist){
		if ($errors){
			foreach($errors as $anError){
				if ($anError->on == '') continue;
				if (preg_match("/\\b{$anError->on}\\b/" ,$errorkeylist)){
					echo " class=\"formerror\" ";
					return;
				}
			}
		}
	}
}

function print_error_box($errors){
    if (!empty($errors)){
        $errorstr = '';
        foreach($errors as $anError){
            $errorstr .= $anError->message;
        }
        print_simple_box($errorstr, 'center', '70%', '', 5, 'errorbox');
    }
}

/**
* this is a direct clone from the WebLib.php choose_from_menu, patched
* for accepting multiple selection within a list.
* @param array $options the array of options in the list
* @param string $name the name of the select field
* @param $selected
*/
function choose_multiple_from_menu($options, $name, $selected=null, $nothing='choose', $script='',
                           $nothingvalue='0', $return=false, $disabled=false, $tabindex=0, $id='', $size=1){

    if ($nothing == 'choose') {
        $nothing = get_string('choose') .'...';
    }

    if (!$selected) {
        $selected = array();
    }
    else{
        if (!is_array($selected)){
            $selected = explode(',', $selected);
        }
    }

    $attributes = "multiple=\"multiple\" size=\"$size\" ";

    $attributes .= ($script) ? 'onchange="'. $script .'"' : '';
    if ($disabled) {
        $attributes .= ' disabled="disabled"';
    }

    if ($tabindex) {
        $attributes .= ' tabindex="'.$tabindex.'"';
    }

    if ($id ==='') {
        $id = 'menu'.$name;
         // name may contaion [], which would make an invalid id. e.g. numeric question type editing form, assignment quickgrading
        $id = str_replace('[', '', $id);
        $id = str_replace(']', '', $id);
    }

    $output = '<select id="'.$id.'" name="'. $name .'" '. $attributes .'>' . "\n";
    if ($nothing) {
        $output .= '   <option value="'. s($nothingvalue) .'"'. "\n";
        if ($nothingvalue === $selected) {
            $output .= ' selected="selected"';
        }
        $output .= '>'. $nothing .'</option>' . "\n";
    }
    if (!empty($options)) {
        foreach ($options as $value => $label) {
            $output .= '   <option value="'. s($value) .'"';
            if (in_array($value, $selected)) {
                $output .= ' selected="selected"';
            }
            if ($label === '') {
                $output .= '>'. $value .'</option>' . "\n";
            } else {
                $output .= '>'. $label .'</option>' . "\n";
            }
        }
    }
    $output .= '</select>' . "\n";

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
* A utility function that gets a SQL clause controlling the range of ownership in results from other queries.
* will contribute to cleanup code from all locallib files.
* @param int $userid
* @param int $groupid
* @param boolean $excludemyself
*/
function brainstorm_get_accessclauses($userid=null, $groupid=0, $excludemyself=false){
    global $USER;

    if ($userid === null) $userid = $USER->id;
    $userClause = '';
    if ($excludemyself){
        $userClause = " AND od.userid != $USER->id " ;
    }
    else{
        $userClause = ($userid) ? " AND od.userid = $userid " : '' ;
    }
    $groupClause = ($groupid) ? " AND od.groupid = $groupid " : '' ;
    return "$userClause $groupClause";
}

/**
*
*/
function brainstorm_legal_include(){
    if (preg_match("/mod\\/brainstorm\\/view.php$/", $_SERVER['PHP_SELF'])){
        return true;
    }
    return false;
}

/**
* get all grades for a list of users
* @param int $brainstormid
* @param array $userids
*/
function brainstorm_get_grades($brainstormid, $userids){
    global $CFG;

    $useridlist = implode("','", $userids);

    $sql = "
       SELECT
          bg.id as gradeid,
          u.id,
          u.firstname,
          u.lastname,
          u.email,
          u.picture,
          bg.grade,
          bg.gradeitem
       FROM
          {$CFG->prefix}user as u,
          {$CFG->prefix}brainstorm_grades as bg
       WHERE
          bg.brainstormid = {$brainstormid} AND
          u.id = bg.userid AND
          u.id in ('$useridlist')
    ";
    if (!$records = get_records_sql($sql)){
        return array();
    }
    return $records;
}

/**
* get a complete grade set for a user
* @param int $brainstormid
* @param int $userid
*/
function brainstorm_get_gradeset($brainstormid, $userid){
    global $CFG;

    $sql = "
       SELECT
          id,
          userid,
          grade,
          gradeitem
       FROM
          {$CFG->prefix}brainstorm_grades
       WHERE
          brainstormid = {$brainstormid} AND
          userid = {$userid}
    ";
    if ($records = get_records_sql($sql)){
        foreach($records as $gradeitem){
            $gradeset->{$gradeitem->gradeitem} = $gradeitem->grade;
        }
        return $gradeset;
    }
    return null;
}

/**
* get a complete grade set for a user
* @param object $brainstorm
* @param int $userid
*/
function brainstorm_convert_to_single($brainstorm, $userid){
    global $CFG;

    $sql = "
       SELECT
          id,
          userid,
          grade,
          gradeitem,
          timeupdated
       FROM
          {$CFG->prefix}brainstorm_grades
       WHERE
          brainstormid = {$brainstormid} AND
          userid = {$userid}
    ";
    if ($records = get_records_sql($sql)){
        foreach($records as $gradeitem){
            $gradeset->{$gradeitem->gradeitem} = $gradeitem->grade;
        }
        $gradeset->timeupdated = $gradeitem->timeupdated;

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
        for($i = 0 ; $i < count($total) ; $i++){
            $totalgrade += $total[$i] * $weights[$i];
        }
        $totalgrade = ($totalweights != 0) ? $totalgrade / $totalweights : 0 ;

        $graderecord->brainstormid = $brainstorm->id;
        $graderecord->gradeitem = 'single';
        $graderecord->grade = round($totalgrade);
        $graderecord->userid = $userid;
        $graderecord->timeupdated = $gradeset->timeupdated; // keeps old time

        delete_records('brainstorm_grades', 'brainstormid', $brainstorm->id, 'userid', $userid);
        if (!insert_record('brainstorm_grades', $graderecord)){
            error("Could not record converted grade");
        }
    }
}

/**
* get all ungrade user records (partial) in a user set
* @param int $brainstormid
* @param array $userids
*/
function brainstorm_get_ungraded($brainstormid, $userids){
    global $CFG;

    $useridlist = implode("','", $userids);

    $sql = "
       SELECT
          u.id,
          u.firstname,
          u.lastname,
          u.email,
          u.picture
       FROM
          {$CFG->prefix}user as u
       LEFT JOIN
          {$CFG->prefix}brainstorm_grades as bg
       ON
          u.id = bg.userid AND
          bg.brainstormid = {$brainstormid}
       WHERE
          bg.grade IS NULL AND
          u.id in ('$useridlist')
    ";
    if (!$records = get_records_sql($sql)){
        return array();
    }
    return $records;
}

/**
*
*
*/
function brainstorm_get_feedback($brainstormid, $userid=null){
    global $USER;

    if (!$userid) $userid = $USER->id;
    $feedback = get_field('brainstorm_userdata', 'feedback', 'brainstormid', $brainstormid, 'userid', $userid);
    return $feedback;
}

/**
* A small utility function for making scale menus
*
*/
function make_grading_menu(&$brainstorm, $id, $selected = '', $return = false){
    if (!$brainstorm->scale) return '';

    if ($brainstorm->scale > 0){
        for($i = 0 ; $i <= $brainstorm->scale ; $i++)
            $scalegrades[$i] = $i;
    }
    else {
        $scaleid = - ($brainstorm->scale);
        if ($scale = get_record('scale', 'id', $scaleid)) {
            $scalegrades = make_menu_from_list($scale->scale);
        }
    }
    return choose_from_menu($scalegrades, $id, $selected, 'choose', '', '', $return);
}


/**
* prints cols for responses
*
*/
function brainstorm_print_responses_cols(&$brainstorm, &$responses, $return = false, $printchecks = false){

    $index = 0;
    $str = '';
    if ($responses){
        foreach ($responses as $response){
            $deletecheckbox = ($printchecks) ? "<input type=\"checkbox\" name=\"items[]\" value=\"{$response->id}\" /> " : '' ;
            if (($index > 0) && $index % $brainstorm->numcolumns == 0){
                $str .= '</tr><tr valign="top">';
            }
            $str .= '<th>' . ($index + 1) . '</th>';
            $str .= '<td>' . $deletecheckbox.$response->response . '</td>';
            $index++;
        }
        if (!$return){
            echo $str;
            return;
        }
    }
    return $str;
}

/**
*
*
*/
function brainstorm_have_reports($brainstormid, &$participantids){

    $participantlist = implode("','", $participantids);

    $select = "
       brainstormid = $brainstormid AND
       userid IN ('$participantlist') AND
       report IS NOT NULL
    ";
    $records = get_records_select('brainstorm_userdata', $select, '', 'userid,userid');
    return $records;
}
?>
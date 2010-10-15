<?PHP  // $Id: lib.php,v 1.13.10.9 2009/07/29 19:02:13 diml Exp $

/**
* @package mod-scheduler
* @category mod
* @author Gustav Delius, Valery Fremaux > 1.8
*
*/

/// Library of functions and constants for module scheduler
include_once $CFG->dirroot.'/mod/scheduler/locallib.php';

define('SCHEDULER_TIMEUNKNOWN', 0);  // This is used for appointments for which no time is entered
define('SCHEDULER_SELF', 0); // Used for setting conflict search scope 
define('SCHEDULER_OTHERS', 1); // Used for setting conflict search scope 
define('SCHEDULER_ALL', 2); // Used for setting conflict search scope 

define ('MEAN_GRADE', 0); // Used for grading strategy
define ('MAX_GRADE', 1); // Used for grading strategy
/**
* Given an object containing all the necessary data, 
* will create a new instance and return the id number 
* of the new instance.
* @param object $scheduler the current instance
* @return int the new instance id
*/
function scheduler_add_instance($scheduler) {
    $scheduler->timemodified = time();
    $id = insert_record('scheduler', $scheduler);
    return $id;
}

/**
* Given an object containing all the necessary data, 
* (defined by the form in mod.html) this function 
* will update an existing instance with new data.
* @param object $scheduler the current instance
* @return object the updated instance
*/
function scheduler_update_instance($scheduler) {
    $scheduler->timemodified = time();
    $scheduler->id = $scheduler->instance;

    # May have to add extra stuff in here #
    
    return update_record('scheduler', $scheduler);
}


/**
* Given an ID of an instance of this module, 
* this function will permanently delete the instance 
* and any data that depends on it.  
* @param int $id the instance to be deleted
* @return boolean true if success, false otherwise
*/
function scheduler_delete_instance($id) {
    global $CFG;

    if (! $scheduler = get_record('scheduler', 'id', "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records('scheduler', 'id', $scheduler->id)) {
        $result = false;
    }

    $oldslots = get_records('scheduler_slots', 'schedulerid', $scheduler->id, '', 'id, id');
    if ($oldslots){
        foreach(array_keys($oldslots) as $slotId){
            // will delete appointements and remaining related events
            scheduler_delete_slot($slotId);
        }
    }
    
    return $result;
}

/**
* Return a small object with summary information about what a 
* user has done with a given particular instance of this module
* Used for user activity reports.
* $return->time = the time they did it
* $return->info = a short text description
* @param object $course the course instance
* @param object $user the concerned user instance
* @param object $mod the current course module instance
* @param object $scheduler the activity module behind the course module instance
* @return object an information object as defined above
*/
function scheduler_user_outline($course, $user, $mod, $scheduler) {
    $return = NULL;
    return $return;
}

/**
* Prints a detailed representation of what a  user has done with 
* a given particular instance of this module, for user activity reports.
* @param object $course the course instance
* @param object $user the concerned user instance
* @param object $mod the current course module instance
* @param object $scheduler the activity module behind the course module instance
* @param boolean true if the user completed activity, false otherwise
*/
function scheduler_user_complete($course, $user, $mod, $scheduler) {

    return true;
}

/** 
* Given a course and a time, this module should find recent activity 
* that has occurred in scheduler activities and print it out. 
* Return true if there was output, or false is there was none.
* @param object $course the course instance
* @param boolean $isteacher true tells a teacher uses the function
* @param int $timestart a time start timestamp
* @return boolean true if anything was printed, otherwise false 
*/
function scheduler_print_recent_activity($course, $isteacher, $timestart) {

    return false;
}

/**
* Function to be run periodically according to the moodle cron
* This function searches for things that need to be done, such 
* as sending out mail, toggling flags etc ... 
* @return boolean always true
*/
function scheduler_cron () {
    global $CFG;

    $date = make_timestamp(date('Y'), date('m'), date('d'), date('H'), date('i'));

    // for every appointment in all schedulers
    $select = "
        emaildate > 0 AND  
        emaildate <= $date
    ";
    $slots = get_records_select('scheduler_slots', $select, 'starttime');

    if ($slots){
        foreach ($slots as $slot) {
            // get teacher
            $teacher = get_record('user', 'id', $slot->teacherid);

            // get appointed student list
            $select = "
                slotid = {$slot->id}
            ";
            $appointments = get_records_select('scheduler_appointment', $select, '', 'id, studentid');

            //if no email previously sent and one is required
            if ($appointments){
                foreach($appointments as $appointed){
                    $recipient = get_record('user', 'id', $appointed->studentid);                
                    $message = get_string('remindwithwhom', 'scheduler').fullname($teacher).' ';
                    $message .= get_string('on', 'scheduler').' ';
                    $message .= date("l jS F Y",$slot->starttime).' ';
                    $message .= get_string('from').' ';
                    $message .= date("H:i",$slot->starttime).' ';
                    $message .= get_string('to').' ';
                    $message .= date("H:i",$slot->starttime + ($slot->duration * 60)).'. ';
                    $message .= get_string('remindwhere', 'scheduler').$slot->appointmentlocation.'.';
                    if(email_to_user($recicpient, $teacher, $title,$message)){
                    }
                }
            }
            // mark as sent
            $slot->emaildate = -1;
            update_record('scheduler_slots', $slot);
        }
    }
    return true;
}

/**
* Must return an array of grades for a given instance of this module, 
* indexed by user. It also returns a maximum allowed grade.
* @param int $schedulerid the id of the activity module
* @return array an array of grades
*/
function scheduler_grades($cmid) {
    global $CFG;

    if (!$module = get_record('course_modules', 'id', $cmid)){
        return NULL;
    }    

    if (!$scheduler = get_record('scheduler', 'id', $module->instance)){
        return NULL;
    }

    if ($scheduler->scale == 0) { // No grading
        return NULL;
    }

    $query = "
       SELECT
          a.id,
          a.studentid,
          a.grade
       FROM
          {$CFG->prefix}scheduler_slots AS s 
       LEFT JOIN
          {$CFG->prefix}scheduler_appointment AS a
       ON
          s.id = a.slotid
       WHERE
          s.schedulerid = {$scheduler->id} AND 
          a.grade IS NOT NULL
    ";
    // echo $query ;
    $grades = get_records_sql($query);
    if ($grades){
        if ($scheduler->scale > 0 ){ // Grading numerically
            $finalgrades = array();
            foreach($grades as $aGrade){
                $finals[$aGrade->studentid]->sum = @$finals[$aGrade->studentid]->sum + $aGrade->grade;
                $finals[$aGrade->studentid]->count = @$finals[$aGrade->studentid]->count + 1;
                $finals[$aGrade->studentid]->max = (@$finals[$aGrade->studentid]->max < $aGrade->grade) ? $aGrade->grade : @$finalgrades[$aGrade->studentid]->max ;
            }
            
            /// compute the adequate strategy
            foreach($finals as $student => $aGradeSet){
                switch ($scheduler->gradingstrategy){
                    case MAX_GRADE:
                        $finalgrades[$student] = $aGradeSet->max;
                        break;
                    case MEAN_GRADE:
                        $finalgrades[$student] = $aGradeSet->sum / $aGradeSet->count ;
                        break;
                }
            }

            $return->grades = $finalgrades;
            $return->maxgrade = $scheduler->scale;
        }
        else { // Scales
            $finalgrades = array();
            $scaleid = - ($scheduler->scale);
            $maxgrade = '';
            if ($scale = get_record('scale', 'id', $scaleid)) {
                $scalegrades = make_menu_from_list($scale->scale);
                foreach ($grades as $aGrade) {
                    $finals[$aGrade->studentid]->sum = @$finals[$aGrade->studentid]->sum + $scalegrades[$aGgrade->grade];
                    $finals[$aGrade->studentid]->count = @$finals[$aGrade->studentid]->count + 1;
                    $finals[$aGrade->studentid]->max = (@$finals[$aGrade->studentid]->max < $aGrade) ? $scalegrades[$aGgrade->grade] : @$finals[$aGrade->studentid]->max ;
                }
                $maxgrade = $scale->name;
            }

            /// compute the adequate strategy
            foreach($finals as $student => $aGradeSet){
                switch ($scheduler->gradingstrategy){
                    case MAX_GRADE:
                        $finalgrades[$student] = $aGradeSet->max;
                        break;
                    case MEAN_GRADE:
                        $finalgrades[$student] = $aGradeSet->sum / $aGradeSet->count ;
                        break;
                }
            }

            $return->grades = $finalgrades;
            $return->maxgrade = $maxgrade;
        }
        return $return;
    }
    return NULL;
}

/**
* Returns the users with data in one scheduler
* (users with records in journal_entries, students and teachers)
* @param int $schedulerid the id of the activity module
*/
function scheduler_get_participants($schedulerid) {
    global $CFG;

    //Get students using slots they have
    $sql = "
        SELECT DISTINCT 
            u.*
        FROM 
            {$CFG->prefix}user u,
            {$CFG->prefix}scheduler_slots s,
            {$CFG->prefix}scheduler_appointment a
        WHERE 
            s.schedulerid = '{$schedulerid}' AND
            s.id = a.slotid AND
            u.id = a.studentid
    ";
    $students = get_records_sql($sql);

    //Get teachers using slots they have
    $sql = "
        SELECT DISTINCT 
            u.*
        FROM 
            {$CFG->prefix}user u,
            {$CFG->prefix}scheduler_slots s
        WHERE 
            s.schedulerid = '{$schedulerid}' AND
            u.id = s.teacherid
    ";
    $teachers = get_records_sql($sql);

    if ($students and $teachers){
        $participants = array_merge(array_values($students), array_values($teachers));
    }
    elseif ($students) {
        $participants = array_values($students);
    }
    elseif ($teachers){
        $participants = array_values($teachers);
    }
    else{
      $participants = array();
    }

    //Return students array (it contains an array of unique users)
    return ($participants);
}

/**
 * This function returns if a scale is being used by one newmodule
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $newmoduleid ID of an instance of this module
 * @return mixed
 **/
function scheduler_scale_used($cmid, $scaleid) {
    $return = false;

    // note : scales are assigned using negative index in the grade field of the appointment (see mod/assignement/lib.php) 
    $rec = get_record('scheduler', 'id', $cmid, 'scale', -$scaleid);

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }
   
    return $return;
}

/**
 * Course resetting API
 * Called by course/reset.php
 * // OBSOLETE WAY
 */
function scheduler_reset_course_form($course) {
    echo get_string('resetschedulers', 'scheduler'); echo ':<br />';
    print_checkbox('reset_appointments', 1, true, get_string('appointments','scheduler'), '', '');  echo '<br />';
    print_checkbox('reset_slots', 1, true, get_string('slots','scheduler'), '', '');  echo '<br />';
    echo '</p>';
}

/**
 * Called by course/reset.php
 * @param $mform form passed by reference
 */
function scheduler_reset_course_form_definition(&$mform) {
    global $COURSE;

    $mform->addElement('header', 'schedulerheader', get_string('modulenameplural', 'scheduler'));
    
    if(!$schedulers = get_records('scheduler', 'course', $COURSE->id)){
        return;
    }

    $mform->addElement('static', 'hint', get_string('resetschedulers', 'scheduler'));
    $mform->addElement('checkbox', 'reset_slots', get_string('resetting_slots', 'scheduler'));
    $mform->addElement('checkbox', 'reset_apointments', get_string('resetting_appointments', 'scheduler'));
}

/**
* This function is used by the remove_course_userdata function in moodlelib.
* If this function exists, remove_course_userdata will execute it.
* This function will remove all posts from the specified forum.
* @param data the reset options
* @return void
*/
function scheduler_reset_userdata($data) {
    global $CFG;

    $status = array();
    $componentstr = get_string('modulenameplural', 'scheduler');
    
    $sql_appointments = "
        DELETE FROM 
            {$CFG->prefix}scheduler_appointment
        WHERE 
            slotid 
        IN ( SELECT 
                s.id 
             FROM 
                {$CFG->prefix}scheduler_slots s,
                {$CFG->prefix}scheduler sc
             WHERE 
                sc.id = s.schedulerid AND
                sc.course = {$data->courseid} 
        )
    ";

    $sql_slots = "
        DELETE FROM 
            {$CFG->prefix}scheduler_slots
        WHERE 
            schedulerid 
        IN ( SELECT 
                sc.id 
             FROM 
                {$CFG->prefix}scheduler sc
             WHERE 
                sc.course = {$data->courseid} 
        )
    ";

    $strreset = get_string('reset');

    if (!empty($data->reset_appointments) || !empty($data->reset_slots)) {
        if (execute_sql($sql_appointments, false)) {
            $status[] = array('component' => $componentstr, 'item' => get_string('resetting_appointments','scheduler'), 'error' => false);
            notify($strreset.': '.get_string('appointments','scheduler'), 'notifysuccess');
        }
    }
    if (!empty($data->reset_slots)) {
        $status[] = array('component' => $componentstr, 'item' => get_string('resetting_slots','scheduler'), 'error' => false);
        execute_sql($sql_slots, false);
    }
    
    return $status;
}

?>

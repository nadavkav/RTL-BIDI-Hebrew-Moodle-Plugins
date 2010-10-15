<?php  // $Id: lib.php,v 1.16 2008/12/01 13:18:25 jamiesensei Exp $
/**
 * Library of functions and constants for module qcreate
 * This file should have two well differenced parts:
 *   - All the core Moodle functions, neeeded to allow
 *     the module to work integrated in Moodle.
 *   - All the qcreate specific functions, needed
 *     to implement all the module logic. Please, note
 *     that, if the module become complex and this lib
 *     grows a lot, it's HIGHLY recommended to move all
 *     these module specific functions to a new php file,
 *     called "locallib.php" (see forum, quiz...). This will
 *     help to save some memory when Moodle is performing
 *     actions across all modules.
 */
/**
 * The options used when popping up a question preview window in Javascript.
 */
define('QCREATE_EDIT_POPUP_OPTIONS', 'scrollbars=yes,resizable=yes,width=800,height=540');

/**
 * If start and end date for the quiz are more than this many seconds apart
 * they will be represented by two separate events in the calendar
 */
define("QCREATE_MAX_EVENT_LENGTH", 5*24*60*60);   // 5 days maximum

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted qcreate record
 **/
function qcreate_add_instance($qcreate) {
    $qcreate->timecreated = time();

    $qcreate->allowed = join(array_keys($qcreate->allowed), ',');
    if ($qcreate->id =insert_record("qcreate", $qcreate)){
        $qtypemins = array_filter($qcreate->qtype);
        if (count($qtypemins)){
            foreach ($qtypemins as $key => $qtypemin){
                $toinsert = new object();
                $toinsert->no = $qcreate->minimumquestions[$key];
                $toinsert->qtype = $qtypemin;
                $toinsert->qcreateid = $qcreate->id;
                $toinsert->timemodified = time();
                insert_record('qcreate_required', $toinsert);
            }
        }
        qcreate_after_add_or_update($qcreate);
    }
    return $qcreate->id;
}
/**
 * Called from cron and update_instance. Not called from add_instance as the contexts are not set up yet.
 */
function qcreate_student_q_access_sync($qcreate, $cmcontext=null, $course=null, $forcesync= false){
    //check if a check is needed
    $timenow = time();
    $activityopen = ($qcreate->timeopen == 0 ||($qcreate->timeopen < $timenow)) &&
        ($qcreate->timeclose == 0 ||($qcreate->timeclose >   $timenow));
    $activitywasopen = ($qcreate->timeopen == 0 ||($qcreate->timeopen <   $qcreate->timesync)) &&
        ($qcreate->timeclose == 0 ||($qcreate->timeclose >   $qcreate->timesync));
    $needsync = (empty($qcreate->timesync) || //no sync has happened yet
            ($activitywasopen != $activityopen));
   if ($forcesync || $needsync){
        if ($cmcontext == null){
            $cm = get_coursemodule_from_instance('qcreate', $qcreate->id);
            $cmcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
        }
        if ($course == null){
            $course = get_record('course', 'id', $qcreate->course);
        }
        $studentrole = get_default_course_role($course);
        if ($activityopen){
            $capabilitiestoassign = array (
                0=> array('moodle/question:add'=> 1, 'moodle/question:usemine'=> -1, 'moodle/question:viewmine'=> -1, 'moodle/question:editmine'=> -1),
                1=> array('moodle/question:add'=> 1, 'moodle/question:usemine'=> 1, 'moodle/question:viewmine'=> -1, 'moodle/question:editmine'=> -1),
                2=> array('moodle/question:add'=> 1, 'moodle/question:usemine'=> 1, 'moodle/question:viewmine'=> 1, 'moodle/question:editmine'=> -1),
                3=> array('moodle/question:add'=> 1, 'moodle/question:usemine'=> 1, 'moodle/question:viewmine'=> 1, 'moodle/question:editmine'=> 1));
            foreach ($capabilitiestoassign[$qcreate->studentqaccess] as $capability => $permission) {
                    assign_capability($capability, $permission, $studentrole->id, $cmcontext->id, true);
            }
        } else {
            $capabilitiestounassign = array (
                'moodle/question:add', 'moodle/question:usemine', 'moodle/question:viewmine', 'moodle/question:editmine');
            foreach ($capabilitiestounassign as $capability) {
                    unassign_capability($capability, $studentrole->id, $cmcontext->id);
            }
        }
        set_field('qcreate', 'timesync', $timenow, 'id', $qcreate->id);

    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function qcreate_update_instance($qcreate) {
    global $COURSE;

    $qcreate->timemodified = time();
    $qcreate->id = $qcreate->instance;

    delete_records('qcreate_required', 'qcreateid', $qcreate->id);

    $qtypemins = array_filter($qcreate->qtype);
    if (count($qtypemins)){
        foreach ($qtypemins as $key => $qtypemin){
            $toinsert = new object();
            $toinsert->no = $qcreate->minimumquestions[$key];
            $toinsert->qtype = $qtypemin;
            $toinsert->qcreateid = $qcreate->id;
            $toinsert->timemodified = time();
            insert_record('qcreate_required', $toinsert);
        }
    }
    $qcreate->allowed = join(array_keys($qcreate->allowed), ',');

    $toreturn = update_record("qcreate", $qcreate);
    
    $qcreate = get_record('qcreate', 'id', $qcreate->id);

    qcreate_student_q_access_sync($qcreate, null, $COURSE, true);

    qcreate_after_add_or_update($qcreate);
    return $toreturn;
}
/**
 * This function is called at the end of qcreate_add_instance
 * and qcreate_update_instance, to do the common processing.
 *
 * @param object $qcreate the qcreate object.
 */
function qcreate_after_add_or_update($qcreate) {
    global $COURSE;

    // Update the events relating to this qcreate.
    // This is slightly inefficient, deleting the old events and creating new ones. However,
    // there are at most two events, and this keeps the code simpler.
    if ($events = get_records_select('event', "modulename = 'qcreate' and instance = '$qcreate->id'")) {
        foreach($events as $event) {
            delete_event($event->id);
        }
    }

    $event = new stdClass;
    $event->description = $qcreate->intro;
    $event->courseid    = $qcreate->course;
    $event->groupid     = 0;
    $event->userid      = 0;
    $event->modulename  = 'qcreate';
    $event->instance    = $qcreate->id;
    $event->timestart   = $qcreate->timeopen;
    $event->timeduration = $qcreate->timeclose - $qcreate->timeopen;
    $event->visible     = instance_is_visible('qcreate', $qcreate);
    $event->eventtype   = 'open';

    if ($qcreate->timeclose and $qcreate->timeopen and $event->timeduration <= QCREATE_MAX_EVENT_LENGTH) {
        // Single event for the whole qcreate.
        $event->name = $qcreate->name;
        add_event($event);
    } else {
        // Separate start and end events.
        $event->timeduration  = 0;
        if ($qcreate->timeopen) {
            $event->name = $qcreate->name.' ('.get_string('qcreateopens', 'qcreate').')';
            add_event($event);
            unset($event->id); // So we can use the same object for the close event.
        }
        if ($qcreate->timeclose) {
            $event->name      = $qcreate->name.' ('.get_string('qcreatecloses', 'qcreate').')';
            $event->timestart = $qcreate->timeclose;
            $event->eventtype = 'close';
            add_event($event);
        }
    }

    //update related grade item
    qcreate_grade_item_update(stripslashes_recursive($qcreate));

}
/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function qcreate_delete_instance($id) {

    if (! $qcreate = get_record("qcreate", "id", "$id")) {
        return false;
    }

    $result = true;

    if (! delete_records("qcreate_grades", "qcreateid", "$qcreate->id")) {
        $result = false;
    }
    if (! delete_records("qcreate_required", "qcreateid", "$qcreate->id")) {
        $result = false;
    }

    if (! delete_records("qcreate", "id", "$qcreate->id")) {
        $result = false;
    }

    return $result;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 **/
function qcreate_user_outline($course, $user, $mod, $qcreate) {
    return null;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function qcreate_user_complete($course, $user, $mod, $qcreate) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in qcreate activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function qcreate_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function qcreate_cron () {
    global $CFG;
    $sql = "SELECT q.*, cm.id as cmidnumber, q.course as courseid
              FROM {$CFG->prefix}qcreate q, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
             WHERE m.name='qcreate' AND m.id=cm.module AND cm.instance=q.id";
    $rs = get_recordset_sql($sql);
    while ($qcreate = rs_fetch_next_record($rs)) {
        $context = get_context_instance(CONTEXT_MODULE, $qcreate->cmidnumber);
        if ($users = get_users_by_capability($context, 'mod/qcreate:submit', '', '', '', '', '', '', false)){
            $users = array_keys($users);
            $sql = 'SELECT q.* FROM  '.$CFG->prefix.'question_categories qc, '.$CFG->prefix.'question q '.
                   'LEFT JOIN '.$CFG->prefix.'qcreate_grades g ON q.id = g.questionid '.
                   'WHERE g.timemarked IS NULL AND q.createdby IN ('.implode(',',$users).') '.
                           'AND qc.id = q.category ' .
                           'AND q.hidden=\'0\' AND q.parent=\'0\' ' .
                           'AND qc.contextid ='.$context->id;
            $questionrs = get_recordset_sql($sql);
            $toupdates = array();
            while ($question = rs_fetch_next_record($questionrs)) {
                qcreate_process_local_grade($qcreate, $question, true);
                $toupdates[] = $question->createdby;
            }
            rs_close($questionrs);
            $toupdates = array_unique($toupdates);
            foreach ($toupdates as $toupdate){
                qcreate_update_grades($qcreate, $toupdate);
            }
        }
        qcreate_student_q_access_sync($qcreate);
    }
    rs_close($rs);


    return true;
}

/**
 * Must return an array of grades for a given instance of this module,
 * indexed by user.  It also returns a maximum allowed grade.
 *
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $qcreateid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function qcreate_grades($qcreateid) {
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of qcreate. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $qcreateid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function qcreate_get_participants($qcreateid) {
    return false;
}

/**
 * This function returns if a scale is being used by one qcreate
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $qcreateid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function qcreate_scale_used ($qcreateid,$scaleid) {
    $return = false;

    //$rec = get_record("qcreate","id","$qcreateid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other qcreate functions go here.  Each of them must have a name that
/// starts with qcreate_
/// Remember (see note in first lines) that, if this section grows, it's HIGHLY
/// recommended to move all funcions below to a new "localib.php" file.

/**
 * Create one or all grade items for given qcreate
 *
 * @param object $qcreate object with extra cmidnumber
 * @return int 0 if ok, error code otherwise
 */
function qcreate_grade_item_update($qcreate) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (!isset($qcreate->courseid)) {
        $qcreate->courseid = $qcreate->course;
    }

    $params = array('itemname'=>$qcreate->name, 'idnumber'=>$qcreate->cmidnumber);

    if ($qcreate->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $qcreate->grade;
        $params['grademin']  = 0;

    } else if ($qcreate->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$qcreate->grade;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }
    $params['itemnumber'] = 0;
    return grade_update('mod/qcreate', $qcreate->courseid, 'mod', 'qcreate', $qcreate->id, 0, NULL, $params);
}
/**
 * Process submitted grades.
 * @param opbject qcreate the qcreate object with cmidnumber set to $cm->id
 * @param object cm coursemodule object
 * @param array users array of ids of users who can take part in this activity.
 */
function qcreate_process_grades($qcreate, $cm, $users){
    global $USER;
    ///do the fast grading stuff
    $grading    = false;
    $commenting = false;
    $qids        = array();
    if (isset($_POST['gradecomment'])) {
        $commenting = true;
        //process array of submitted comments
        $submitcomments = optional_param('gradecomment', 0, PARAM_RAW);
        $qids = array_keys($_POST['gradecomment']);
    }
    if (isset($_POST['menu'])) {
        $grading = true;
        //process array of submitted grades
        $submittedgrades = optional_param('menu', 0, PARAM_INT);
        $qids = array_unique(array_merge($qids, array_keys($_POST['menu'])));
    }
    if (!$qids) {
        return;
    }
    //get the cleaned keys which are the questions ids
    $qids = clean_param($qids, PARAM_INT);
    if ($qids){
        $toupdates = array();
        $questions = get_records_select('question', 'id IN ('.implode(',', $qids).') AND '.
                                        'createdby IN ('.implode(',', $users).')');
        foreach ($qids as $qid){
            //test that qid is a question created by one of the users we can grade
            if (isset($questions[$qid])){
                $question = $questions[$qid];
                //TODO fix outcomes
                //qcreate_process_outcomes($qcreate, $id);
                if ($grading) {
                    $submittedgrade = $submittedgrades[$qid];
                } else {
                    $submittedgrade = -1; //not graded
                }
                if ($commenting) {
                    $submitcomment = $submitcomments[$qid];
                } else {
                    $submitcomment = ''; //no comment
                }
                
                if (qcreate_process_local_grade($qcreate, $question, false, $submittedgrade, $submitcomment)){
                    $toupdates[] = $question->createdby;
                }
            }

        }
        $toupdates = array_unique($toupdates);
        foreach ($toupdates as $toupdate){
            qcreate_update_grades($qcreate, $toupdate);
        }

    }

    $message = notify(get_string('changessaved'), 'notifysuccess', 'center', true);

    return $message;
}

function qcreate_process_local_grade($qcreate, $question, $forcenewgrade = false, $submittedgrade=-1, $submittedcomment=''){
    global $USER;
    if ($forcenewgrade || !$grade = qcreate_get_grade($qcreate, $question->id)) {
        $grade = qcreate_prepare_new_grade($qcreate, $question);
        $newgrade = true;
    } else {
        $newgrade = false;
    }
    unset($grade->data1);  // Don't need to update this.
    unset($grade->data2);  // Don't need to update this.

    //for fast grade, we need to check if any changes take place
    $updatedb = false;

    $updatedb = $updatedb || ($grade->grade != $submittedgrade);
    $grade->grade = $submittedgrade;

    $submittedcomment = trim($submittedcomment);
    $updatedb = $updatedb || ($grade->gradecomment != stripslashes($submittedcomment));
    $grade->gradecomment = $submittedcomment;

    $grade->userid    = $question->createdby;
    $grade->teacher    = $USER->id;
    if ($grade->grade != -1){
        $grade->timemarked = time();
    } else {
        $grade->timemarked = 0;
    }

    //if it is not an update, we don't change the last modified time etc.
    //this will also not write into database if no gradecomment and grade is entered.

    if ($forcenewgrade || $updatedb){
        if ($newgrade) {
            if (!$sid = insert_record('qcreate_grades', $grade)) {
                return false;
            }
            $grade->id = $sid;
        } else {
            if (!update_record('qcreate_grades', $grade)) {
                return false;
            }
        }

        // triger grade event
        //add to log only if updating
        add_to_log($qcreate->course, 'qcreate', 'update grades',
                   'grades.php?id='.$qcreate->id.'&user='.$grade->userid,
                   $grade->userid, $qcreate->cmidnumber);
    }
    return $updatedb;

}

function qcreate_process_outcomes($qcreate, $userid) {
    global $CFG, $COURSE;

    if (empty($CFG->enableoutcomes)) {
        return;
    }

    require_once($CFG->libdir.'/gradelib.php');

    if (!$formdata = data_submitted()) {
        return;
    }

    $data = array();
    $grading_info = grade_get_grades($COURSE->id, 'mod', 'qcreate', $qcreate->id, $userid);

    if (!empty($grading_info->outcomes)) {
        foreach($grading_info->outcomes as $n=>$old) {
            $name = 'outcome_'.$n;
            if (isset($formdata->{$name}[$userid]) and $old->grades[$userid]->grade != $formdata->{$name}[$userid]) {
                $data[$n] = $formdata->{$name}[$userid];
            }
        }
    }
    if (count($data) > 0) {
        grade_update_outcomes('mod/qcreate', $COURSE->id, 'mod', 'qcreate', $qcreate->id, $userid, $data);
    }

}
/**
 * Load the local grade object for a particular user
 *
 * @param $userid int The id of the user whose grade we want or 0 in which case USER->id is used
 * @param $qid int The id of the question whose grade we want
 * @param $createnew boolean optional Defaults to false. If set to true a new grade object will be created in the database
 * @return object The grade
 */
function qcreate_get_grade($qcreate, $qid, $createnew=false) {
    $grade = get_record('qcreate_grades', 'qcreateid', $qcreate->id, 'questionid', $qid);

    if ($grade || !$createnew) {
        return $grade;
    }
    $newgrade = qcreate_prepare_new_grade($qcreate, $qid);
    if (!insert_record("qcreate_grades", $newgrade)) {
        error("Could not insert a new empty grade");
    }

    return get_record('qcreate_grades', 'qcreate', $qcreate->id, 'questionid', $qid);
}

/**
 * Instantiates a new grade object for a given user
 *
 * Sets the qcreate, userid and times, everything else is set to default values.
 * @param $userid int The userid for which we want a grade object
 * @return object The grade
 */
function qcreate_prepare_new_grade($qcreate, $question) {
    $grade = new Object;
    $grade->qcreateid   = $qcreate->id;
    $grade->questionid  = $question->id;
    $grade->numfiles     = 0;
    $grade->data1        = '';
    $grade->data2        = '';
    $grade->grade        = -1;
    $grade->gradecomment      = '';
    $grade->teacher      = 0;
    $grade->timemarked   = 0;
    $grade->mailed       = 0;
    return $grade;
}
/**
 * Update grades.
 *
 * @param object $qcreate null means all qcreates
 * @param int $userid specific user only, 0 mean all
 */
function qcreate_update_grades($qcreate=null, $userid=0) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if ($qcreate != null) {
        if ($gradesbyuserids = qcreate_get_user_grades($qcreate, $userid)) {
            foreach ($gradesbyuserids as $userid => $gradesbyuserid){
                qcreate_grade_item_update($qcreate);
                grade_update('mod/qcreate', $qcreate->courseid, 'mod', 'qcreate', $qcreate->id, 0, $gradesbyuserid);
            }
        }
    } else {
        $sql = "SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
                  FROM {$CFG->prefix}qcreate a, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
                 WHERE m.name='qcreate' AND m.id=cm.module AND cm.instance=a.id";
        $rs = get_recordset_sql($sql);
        while ($qcreate = rs_fetch_next_record($rs)) {
            qcreate_grade_item_update($qcreate);
            if ($qcreate->grade != 0) {
                qcreate_update_grades($qcreate);
            }
        }
        rs_close($rs);
    }
}
/**
 * Return local grades for given user or all users.
 *
 * @param object $qcreate
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function qcreate_get_user_grades($qcreate, $userid=0) {
    global $CFG;
    if (is_array($userid)){
       $user =  "u.id IN (".implode(',', $userid).") AND";
    } else if ($userid){
       $user = "u.id = $userid AND";
    } else {
       $user = '';
    }
    $modulecontext = get_context_instance(CONTEXT_MODULE, $qcreate->cmidnumber);
    $sql = "SELECT q.id, u.id AS userid, g.grade AS rawgrade, g.gradecomment AS feedback, g.teacher AS usermodified, q.qtype AS qtype
              FROM {$CFG->prefix}user u, {$CFG->prefix}question_categories qc, {$CFG->prefix}question q
              LEFT JOIN {$CFG->prefix}qcreate_grades g ON g.questionid = q.id
             WHERE $user u.id = q.createdby AND qc.id = q. category AND qc.contextid={$modulecontext->id}
             ORDER BY rawgrade DESC";
    $localgrades = get_records_sql($sql);
    $gradesbyuserids = array();
    foreach($localgrades as $k=>$v) {
        if (!isset($gradesbyuserids[$v->userid])){
            $gradesbyuserids[$v->userid] = array();
        }
        if ($v->rawgrade == -1) {
            $v->rawgrade = null;
        }
        $gradesbyuserids[$v->userid][$k] = $v;
    }
    $aggregategradebyuserids  = array();
    foreach ($gradesbyuserids as $userid => $gradesbyuserid){
        $aggregategradebyuserids[$userid] = qcreate_grade_aggregate($gradesbyuserid, $qcreate);
    }
    return $aggregategradebyuserids;
}
/**
 * @param array gradesforuser an array of objects from local grades tables
 * @return aggregated grade
 */
function qcreate_grade_aggregate($gradesforuser, $qcreate){
    $aggregated = new object();
    $aggregated->rawgrade = 0;
    $aggregated->usermodified = 0;
    $requireds = qcreate_required_qtypes($qcreate);
    //need to make sure that we grade required questions and then any extra.
    //grades are sorted for descending raw grade
    $counttotalrequired = $qcreate->totalrequired;
    if ($requireds){
	    foreach ($requireds as $required){
	        foreach ($gradesforuser as $key => $gradeforuser){
	            if ($gradeforuser->qtype == $required->qtype){
	                $aggregated->rawgrade += ($gradeforuser->rawgrade / $qcreate->totalrequired);
	                $aggregated->userid = $gradeforuser->userid;
	                unset($gradesforuser[$key]);
	                $required->no--;
	                $counttotalrequired--;
	                if ($required->no == 0){
	                    //go on to the next required type
	                    break;
	                }
	            }
	        }
	    }
    }
    if ($counttotalrequired != 0){
        //now grade the remainder of the questions
        if ($qcreate->allowed != 'ALL'){
            $allowall = false;
            $allowed = explode(',', $qcreate->allowed);
        } else {
            $allowall = true;
        }
        foreach ($gradesforuser as $key => $gradeforuser){
            if ($allowall || in_array($gradeforuser->qtype, $allowed)){
                $aggregated->rawgrade += ($gradeforuser->rawgrade / $qcreate->totalrequired);
                $aggregated->userid = $gradeforuser->userid;
                $counttotalrequired--;
                if ($counttotalrequired == 0){
                    break;
                }
            }
        }
    }
    $totalrequireddone = $qcreate->totalrequired - $counttotalrequired;

    $aggregated->rawgrade = $aggregated->rawgrade * ((100 - $qcreate->graderatio) / 100) +
                 (($totalrequireddone*$qcreate->grade / $qcreate->totalrequired) * ($qcreate->graderatio/ 100));

    return $aggregated;
}

/**
 * Get required qtypes for this qcreate activity.
 *
 * @param object qcreate the qcreate object
 * @return array an array of objects
 */
function qcreate_required_qtypes($qcreate){
    static $requiredcache = array();
    if (!isset($requiredcache[$qcreate->id])){
        $requiredcache[$qcreate->id] = get_records('qcreate_required', 'qcreateid', $qcreate->id, 'qtype', 'qtype, no, id');
    }
    return $requiredcache[$qcreate->id];
}

function qcreate_time_status($qcreate){
    $timenow = time();
    $available = ($qcreate->timeopen < $timenow &&
         ($timenow < $qcreate->timeclose || !$qcreate->timeclose));
    if ($available) {
        $string = get_string("activityopen", "qcreate");
    } else {
        $string = get_string("activityclosed", "qcreate");
    }
    $string = "<strong>$string</strong>";
    if (!$qcreate->timeopen && !$qcreate->timeclose){
        return $string.' '.get_string('timenolimit', 'qcreate');
    }
    if ($qcreate->timeopen){
        if ($timenow < $qcreate->timeopen) {
            $string .= ' '.get_string("timewillopen", "qcreate", userdate($qcreate->timeopen));
        } else {
            $string .= ' '.get_string("timeopened", "qcreate", userdate($qcreate->timeclose));
        }
    }
    if ($qcreate->timeclose){
        if ($timenow < $qcreate->timeclose) {
            $string .= ' '.get_string("timewillclose", "qcreate", userdate($qcreate->timeopen));
        } else {
            $string .= ' '.get_string("timeclosed", "qcreate", userdate($qcreate->timeclose));
        }
    }
    return $string;
}

?>

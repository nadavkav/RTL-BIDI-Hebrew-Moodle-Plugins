<?php  // $Id: lib.php,v 1.7.2.5 2009/04/22 21:30:57 skodak Exp $

/**
 * Library of functions and constants for module hotquestion
 * This file should have two well differenced parts:
 *   - All the core Moodle functions, neeeded to allow
 *     the module to work integrated in Moodle.
 *   - All the hotquestion specific functions, needed
 *     to implement all the module logic. Please, note
 *     that, if the module become complex and this lib
 *     grows a lot, it's HIGHLY recommended to move all
 *     these module specific functions to a new php file,
 *     called "locallib.php" (see forum, quiz...). This will
 *     help to save some memory when Moodle is performing
 *     actions across all modules.
 */


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $hotquestion An object from the form in mod_form.php
 * @return int The id of the newly inserted hotquestion record
 */
function hotquestion_add_instance($hotquestion) {

    $hotquestion->timecreated = time();

    # You may have to add extra stuff in here #

    $id = insert_record('hotquestion', $hotquestion);
    if ($id) {
        // Create the first round
        $round->starttime = time();
        $round->endtime = 0;
        $round->hotquestion = $id;
        if (insert_record('hotquestion_rounds', $round)) {
            return $id;
        }
    }

    return false;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $hotquestion An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function hotquestion_update_instance($hotquestion) {

    $hotquestion->timemodified = time();
    $hotquestion->id = $hotquestion->instance;

    # You may have to add extra stuff in here #

    return update_record('hotquestion', $hotquestion);
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function hotquestion_delete_instance($id) {

    if (! $hotquestion = get_record('hotquestion', 'id', $id)) {
        return false;
    }

    $result = true;

    $questions = get_records('hotquestion_questions', 'hotquestion', $hotquestion->id);
    foreach ($questions as $question) {
        if (! delete_records('hotquestion_votes', 'question', $question->id))
            $result = false;
    }
    
    if (! delete_records('hotquestion_questions', 'hotquestion', $hotquestion->id)) {
        $result = false;
    }

    if (! delete_records('hotquestion_rounds', 'hotquestion', $hotquestion->id)) {
        $result = false;
    }

    if (! delete_records('hotquestion', 'id', $hotquestion->id)) {
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
 */
function hotquestion_user_outline($course, $user, $mod, $hotquestion) {
    return $return;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function hotquestion_user_complete($course, $user, $mod, $hotquestion) {
    return true;
}


/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in hotquestion activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function hotquestion_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function hotquestion_cron () {
    return true;
}


/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of hotquestion. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $hotquestionid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function hotquestion_get_participants($hotquestionid) {
    return false;
}


/**
 * This function returns if a scale is being used by one hotquestion
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $hotquestionid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 */
function hotquestion_scale_used($hotquestionid, $scaleid) {
    $return = false;

    //$rec = get_record("hotquestion","id","$hotquestionid","scale","-$scaleid");
    //
    //if (!empty($rec) && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}


/**
 * Checks if scale is being used by any instance of hotquestion.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any hotquestion
 */
function hotquestion_scale_used_anywhere($scaleid) {
    if ($scaleid and record_exists('hotquestion', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
}


/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function hotquestion_install() {
    return true;
}


/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function hotquestion_uninstall() {
    return true;
}


//////////////////////////////////////////////////////////////////////////////////////
/// Any other hotquestion functions go here.  Each of them must have a name that
/// starts with hotquestion_
/// Remember (see note in first lines) that, if this section grows, it's HIGHLY
/// recommended to move all funcions below to a new "localib.php" file.


?>

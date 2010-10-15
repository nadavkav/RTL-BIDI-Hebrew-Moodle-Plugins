<?php  // $Id: lib.php,v 1.1 2009/12/21 01:01:26 michaelpenne Exp $
/**
 * Library of functions and constants for module pagemenu
 *
 * @author Mark Nielsen
 * @version $Id: lib.php,v 1.1 2009/12/21 01:01:26 michaelpenne Exp $
 * @package pagemenu
 **/

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted pagemenu record
 **/
function pagemenu_add_instance($pagemenu) {

    pagemenu_process_settings($pagemenu);

    return insert_record('pagemenu', $pagemenu);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function pagemenu_update_instance($pagemenu) {

    pagemenu_process_settings($pagemenu);
    $pagemenu->id = $pagemenu->instance;

    return update_record('pagemenu', $pagemenu);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function pagemenu_delete_instance($id) {

    $result = true;

    if ($links = get_records('pagemenu_links', 'pagemenuid', $id, '', 'id')) {
        $linkids = implode(',', array_keys($links));

        $result = delete_records_select('pagemenu_link_data', "linkid IN($linkids)");

        if ($result) {
            $result = delete_records('pagemenu_links', 'pagemenuid', $id);
        }
    }
    if ($result) {
        $result = delete_records('pagemenu', 'id', $id);
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
 * @uses $CFG
 * @param object $course  Might not be object :\
 * @param object $user User object
 * @param mixed $mod Don't know
 * @param object $pagemenu pagemenu instance object
 * @return object
 **/
function pagemenu_user_outline($course, $user, $mod, $pagemenu) {
    return false;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course  Might not be object :\
 * @param object $user User object
 * @param mixed $mod Don't know
 * @param object $pagemenu pagemenu instance object
 * @return boolean
 **/
function pagemenu_user_complete($course, $user, $mod, $pagemenu) {
    return false;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in pagemenu activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function pagemenu_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG, $USER;

    $printed = false;

    return $printed;  //  True if anything was printed, otherwise false
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
function pagemenu_cron () {
    global $CFG;

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
 * @param int $pagemenuid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function pagemenu_grades($pagemenuid) {
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of pagemenu. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $pagemenuid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function pagemenu_get_participants($pagemenuid) {
    return false;
}

/**
 * This function returns if a scale is being used by one pagemenu
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $pagemenuid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function pagemenu_scale_used ($pagemenuid,$scaleid) {
    $return = false;

    //$rec = get_record("pagemenu","id","$pagemenuid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}

/**
 * Any other pagemenu functions go here.  Each of them must have a name that
 * starts with pagemenu_
 **/

/**
 * General pagemenu Functions
 *
 **/

/**
 * Processes common settings from {@link pagemenu_update_instance}
 * and {@link pagemenu_add_instance}
 *
 * @return void
 **/
function pagemenu_process_settings(&$pagemenu) {
    $pagemenu->timemodified = time();
    $pagemenu->taborder     = round($pagemenu->taborder, 0);
}

?>
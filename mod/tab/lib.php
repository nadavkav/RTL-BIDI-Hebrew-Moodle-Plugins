<?php  // $Id: lib.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * Library of functions and constants for module tab
 *
 * @author : Patrick Thibaudeau
 * @version $Id: version.php,v 1.0 2007/07/01 16:41:20
 * @package tab
 **/

/// (replace tab with the name of your module and delete this line)


$tab_CONSTANT = 7;     /// for example

/// Make tab a resource
function tab_get_types() {
global $CFG;

$types = array();

$type = new object();
$type->modclass = MOD_CLASS_RESOURCE;
$type->type = 'tab';
$type->typestr = get_string('modulename', 'tab');
$types[] = $type;

return $types;
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted tab record
 **/
function tab_add_instance($tab) {
    
    $tab->timemodified = time();

    # May have to add extra stuff in here #
    
    return insert_record("tab", $tab);
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function tab_update_instance($tab) {

    $tab->timemodified = time();
    $tab->id = $tab->instance;

    # May have to add extra stuff in here #

    return update_record("tab", $tab);
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function tab_delete_instance($id) {

    if (! $tab = get_record("tab", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records("tab", "id", "$tab->id")) {
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
function tab_user_outline($course, $user, $mod, $tab) {
  $sql = "SELECT * FROM mdl_log WHERE course = '$course->id' AND userid = '$user->id' AND module LIKE 'tab' AND action LIKE 'view'";
  $items = get_records_sql($sql);
  $return->info = count($items)." ".get_string('views');
  return $return;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function tab_user_complete($course, $user, $mod, $tab) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in tab activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function tab_print_recent_activity($course, $isteacher, $timestart) {
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
function tab_cron () {
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
 * @param int $tabid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function tab_grades($tabid) {
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of tab. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $tabid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function tab_get_participants($tabid) {
    return false;
}

/**
 * This function returns if a scale is being used by one tab
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $tabid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function tab_scale_used ($tabid,$scaleid) {
    $return = false;

    //$rec = get_record("tab","id","$tabid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other tab functions go here.  Each of them must have a name that 
/// starts with tab_

function tab_get_view_actions() {
    return array('view','view all');
}

?>

<?PHP  // $Id: lib.php,v 1.3 2004/06/09 22:35:27 gustav_delius Exp $

/// Library of functions and constants for module metadatalom
/// (replace metadatalom with the name of your module and delete this line)


$metadatalom_CONSTANT = 7;     /// for example


function metadatalom_add_instance($metadatalom) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will create a new instance and return the id number 
/// of the new instance.

    $metadatalom->timemodified = time();

    # May have to add extra stuff in here #
    
    return insert_record("metadatalom", $metadatalom);
}


function metadatalom_update_instance($metadatalom) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will update an existing instance with new data.

    $metadatalom->timemodified = time();
    $metadatalom->id = $metadatalom->instance;

    # May have to add extra stuff in here #

    return update_record("metadatalom", $metadatalom);
}


function metadatalom_delete_instance($id) {
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  

    if (! $metadatalom = get_record("metadatalom", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records("metadatalom", "id", "$metadatalom->id")) {
        $result = false;
    }

    return $result;
}

function metadatalom_user_outline($course, $user, $mod, $metadatalom) {
/// Return a small object with summary information about what a 
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description

    return $return;
}

function metadatalom_user_complete($course, $user, $mod, $metadatalom) {
/// Print a detailed representation of what a  user has done with 
/// a given particular instance of this module, for user activity reports.

    return true;
}

function metadatalom_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity 
/// that has occurred in metadatalom activities and print it out. 
/// Return true if there was output, or false is there was none.

    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

function metadatalom_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such 
/// as sending out mail, toggling flags etc ... 

    global $CFG;

    return true;
}

function metadatalom_grades($metadatalomid) {
/// Must return an array of grades for a given instance of this module, 
/// indexed by user.  It also returns a maximum allowed grade.
///
///    $return->grades = array of grades;
///    $return->maxgrade = maximum allowed grade;
///
///    return $return;

   return NULL;
}

function metadatalom_get_participants($metadatalomid) {
//Must return an array of user records (all data) who are participants
//for a given instance of metadatalom. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.

    return false;
}

function metadatalom_scale_used ($metadatalomid,$scaleid) {
//This function returns if a scale is being used by one metadatalom
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.
   
    $return = false;

    //$rec = get_record("metadatalom","id","$metadatalomid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other metadatalom functions go here.  Each of them must have a name that 
/// starts with metadatalom_

?>

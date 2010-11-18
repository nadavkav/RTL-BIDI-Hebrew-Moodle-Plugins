<?PHP  // $Id: view.php,v 1.1 2003/09/30 02:45:19 moodler Exp $

/// This page prints a particular instance of bookings
/// (Replace bookings with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");

 //CZW   optional_variable($id);    // Course Module ID, or
 //CZW   optional_variable($a);     // bookings ID
	
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // bookings ID

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
    
        if (! $bookings = get_record("bookings", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $bookings = get_record("bookings", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $bookings->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("bookings", $bookings->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    require ("$CFG->dirroot/mod/bookings/type/$bookings->type/bookings.class.php");
    $bookingsclass = "bookings_$bookings->type";
    $bookingsinstance = new $bookingsclass($cm->id, $bookings, $cm, $course);

    $bookingsinstance->view();   // Actually display the assignment!

?>

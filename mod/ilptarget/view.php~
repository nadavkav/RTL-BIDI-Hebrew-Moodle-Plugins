<?php  

/*

 * @copyright &copy; 2007 University of London Computer Centre

 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk

 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License

 * @package ILP

 * @version 1.0

 */



    require_once("../../config.php");

    require_once("lib.php");



    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or

    $a  = optional_param('a', 0, PARAM_INT);  // target ID

	$mode  = optional_param('mode', 0, PARAM_INT);  // target mode

	

    if ($id) {

        if (! $cm = get_record("course_modules", "id", $id)) {

            error("Course Module ID was incorrect");

        }

    

        if (! $course = get_record("course", "id", $cm->course)) {

            error("Course is misconfigured");

        }

    

        if (! $target = get_record("ilptarget", "id", $cm->instance)) {

            error("Course module is incorrect");

        }



    } else {

        if (! $target = get_record("ilptarget", "id", $a)) {

            error("Course module is incorrect");

        }

        if (! $course = get_record("course", "id", $target->course)) {

            error("Course is misconfigured");

        }

        if (! $cm = get_coursemodule_from_instance("ilptarget", $target->id, $course->id)) {

            error("Course Module ID was incorrect");

        }

    }



    require_login($course->id);



    add_to_log($course->id, "target", "view", "view.php?id=$cm->id", "$target->id");



    

/// Print the main part of the page

	$context = get_context_instance(CONTEXT_MODULE, $cm->id);

	

	has_capability('mod/ilptarget:view', $context);

		

	if (!has_capability('mod/ilptarget:viewclass', $context)) {

	    include('target_view.php');

		//target_view_individual($USER->id, $target, $cm, $course);

	}else{

		include('view_students.php');		

		//target_view_class($target, $cm, $course);

	}



/// Finish the page

    //print_footer($course);

?>


<?php // $Id: details.php,v 1.4 2005/05/16 22:02:35 stronk7 Exp $
      // This script prints the setup screen for any assignment
      // It does this by calling the setup method in the appropriate class

    require_once("../../config.php");
    require_once("lib.php");

    if (!$form = data_submitted($CFG->wwwroot.'/course/mod.php')) {
        error("This script was called wrongly");
    }

    if (!$course = get_record('course', 'id', $form->course)) {
        error("Non-existent course!");
    }

    require_login($course->id);

    if (!isteacheredit($course->id)) {
        redirect($CFG->wwwroot.'/course/view.php?id='.$course->id);
    }


    require_once("$CFG->dirroot/mod/bookings/type/$form->type/bookings.class.php");

    $bookingsclass = "bookings_$form->type";

    $bookingsinstance = new $bookingsclass();

    echo $bookingsinstance->setup($form);     /// The actual form is all printed here


?>

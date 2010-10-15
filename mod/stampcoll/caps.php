<?php  // $Id: caps.php,v 1.2 2008/02/20 23:58:35 mudrd8mz Exp $

/**
 * Common definitions of $cap_* variables for Stamp Collection module
 *
 * @author David Mudrak
 * @package mod/stampcoll
 */

    if (empty($course) or empty($context)) {
        die('You cannot call this script in that way');
    }

/// Get capabilities. Somewhere we want to ignore admin's doanything
    $cap_managestamps = has_capability('mod/stampcoll:managestamps', $context);
    // if you can't collect, you can't view your own stamps
    $cap_viewownstamps = has_capability('mod/stampcoll:collectstamps', $context, NULL, false) 
                        && has_capability('mod/stampcoll:viewownstamps', $context, NULL, false);
    $cap_viewotherstamps = has_capability('mod/stampcoll:viewotherstamps', $context)
                        || $cap_managestamps;
    $cap_viewsomestamps = $cap_viewownstamps || $cap_viewotherstamps;
    $cap_viewonlyownstamps = $cap_viewownstamps && (!$cap_viewotherstamps);

    $cap_givestamps = has_capability('mod/stampcoll:givestamps', $context)
                        || $cap_managestamps;
    // allows to use editstamps.php
    $cap_editstamps = $cap_givestamps || $cap_managestamps;

?>

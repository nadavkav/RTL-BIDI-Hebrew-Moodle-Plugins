<?php // $Id: checkseats.php,v 1.1.2.2 2009/03/18 16:45:54 mchurch Exp $

/**
 * Checks to see if the seats requested are available over a given period of time.
 * 
 * @version $Id: checkseats.php,v 1.1.2.2 2009/03/18 16:45:54 mchurch Exp $
 * @author Justin Filip <jfilip@oktech.ca>
 * @author Remote Learner - http://www.remote-learner.net/
 */


	require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
    require_once dirname(__FILE__) . '/lib.php';

	$id          = required_param('cid', PARAM_INT);
    $seatcount   = required_param('reservedSeatCount', PARAM_INT);
    $meetingname = stripslashes(required_param('meetingName', PARAM_NOTAGS));
    $cmid        = optional_param('cmid', 0, PARAM_INT);
    $meetingid   = optional_param('meetingID', 0, PARAM_INT);
    $timestart   = optional_param('startTime', 0, PARAM_INT);
    $timeend     = optional_param('endTime', 0, PARAM_INT);

    if (!empty($cmid)) {
        if (! $cm = get_coursemodule_from_id('elluminate', $cmid)) {
            error("Course Module ID was incorrect");
        }
    } else {
        $cm = null;
    }
    if (! $course = get_record("course", "id", $id)) {
        error("Course is misconfigured");
    }

/// Some capability checks.
    require_course_login($course, true, $cm);
    if (!empty($cm)) {
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    } else {
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
    }
    require_capability('mod/elluminate:manageseats', $context);



    $strtitle = !empty($meetingname) ?
                get_string('checkavailabilityfor', 'elluminate', $meetingname) :
                get_string('checkavailabilityfor', 'elluminate', get_string('meeting', 'elluminate'));

    print_header();


/// Create the meeting start and end times based on individually passed date parameters
/// if the exact timestamp values weren't passed already.
    if (empty($timestart)) {
        $startday    = required_param('startDay', PARAM_INT);
        $startmonth  = required_param('startMonth', PARAM_INT);
        $startyear   = required_param('startYear', PARAM_INT);
        $starthour   = required_param('startHour', PARAM_INT);
        $startminute = required_param('startMinute', PARAM_INT);

        $timestart = mktime($starthour, $startminute, 0, $startmonth, $startday, $startyear);
    }

    if (empty($timeend)) {
        $endday    = required_param('endDay', PARAM_INT);
        $endmonth  = required_param('endMonth', PARAM_INT);
        $endyear   = required_param('endYear', PARAM_INT);
        $endhour   = required_param('endHour', PARAM_INT);
        $endminute = required_param('endMinute', PARAM_INT);

        $timeend = mktime($endhour, $endminute, 0, $endmonth, $endday, $endyear);
    }

/// Get the maximum seats avaialble over the specified time (excluding the meeting we're
/// checking for if this is trying to adjust seat reservation for an exsting meeting).
    if (!empty($meetingid)) {
        $seats = elluminate_get_max_available_seats($timestart, $timeend, $meetingid);
    } else {
        $seats = elluminate_get_max_available_seats($timestart, $timeend);
    }

    $a = new stdClass;
    $a->seats       = $seatcount;
    $a->timestart   = userdate($timestart);
    $a->timeend     = userdate($timeend);
    $a->actualseats = $seats;

    print_simple_box_start('center', '100%');

    if ($seats === false) {
        print_heading(get_string('couldnotgetavailableseatinfo', 'elluminate'));
    } else if ($seatcount > $seats) {
        notify(get_string('reservedseatsno', 'elluminate', $a), 'notifyproblem', 'left');
    } else {
        notify(get_string('reservedseatsyes', 'elluminate', $a), 'notifysuccess', 'left');
    }

    echo '<center><input type="button" onclick="self.close();" value="' . get_string('closewindow') . '" /></center>';

    print_simple_box_end();
    print_footer('none');

?>

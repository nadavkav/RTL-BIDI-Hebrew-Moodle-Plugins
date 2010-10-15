<?php

/**
* Controller for student view
*
* @package mod-scheduler
* @category mod
* @author Gustav Delius, Valery Fremaux > 1.8
*
* @usecase 'savechoice'
* @usecase 'disengage'
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/scheduler
}

/************************************************ Saving choice ************************************************/
if ($action == 'savechoice') {
    // get parameters
    $slotid = optional_param('slotid', '', PARAM_INT);
    $appointgroup = optional_param('appointgroup', 0, PARAM_INT);
    // $notes = optional_param('notes', '', PARAM_TEXT);

    if (!$slotid) {
        notice(get_string('notselected', 'scheduler'), "view.php?id={$cm->id}");
    }

    if (!$slot = get_record('scheduler_slots', 'id', $slotid)) {
        error('Invalid slot ID');
    }
    
    $available = scheduler_get_appointments($slotid);
    $consumed = ($available) ? count($available) : 0 ;

    // if slot is already overcrowded
    if ($slot->exclusivity > 0 && $slot->exclusivity <= $consumed) {
       print_simple_box_start('center');
       echo '<h2 style="color:red;">'.get_string('slot_is_just_in_use', 'scheduler').'</h2>';
       print_simple_box_end();
       print_continue("{$CFG->wwwroot}/mod/scheduler/view.php?id={$cm->id}");
       print_footer($course);
       exit();
    }
    
    /// If we are scheduling a full group we must discard all pending appointments of other participants of the scheduled group
    /// just add the list of other students for searching slots to delete
    if ($appointgroup){
        if (!function_exists('build_navigation')){
            // we are still in 1.8
            $oldslotownersarray = groups_get_members($appointgroup, 'student');
        } else {
            // we are still in 1.8
            $oldslotownersarray = groups_get_members($appointgroup);
        }
        // special hack for 1.8 / 1.9 compatibility for groups_get_members()
        foreach($oldslotownersarray as $oldslotownermember){
            if (is_numeric($oldslotownermember)){
                // we are in 1.8
                if (has_capability("mod/scheduler:appoint", $context, $oldslotownermember)){
                    $oldslotowners[] = $oldslotownermember;
                }
            } else {
                // we are in 1.9
                if (has_capability("mod/scheduler:appoint", $context, $oldslotownermember->id)){
                    $oldslotowners[] = $oldslotownermember->id;
                }
            }
        }
    } else {
        // single user appointment : get current user in
        $oldslotowners[] = $USER->id;
    }
    $oldslotownerlist = implode("','", $oldslotowners);
    
    /// cleans up old slots if not attended (attended are definitive results, with grades)
    $sql = "
        SELECT 
            s.*,
            a.id as appointmentid
        FROM 
            {$CFG->prefix}scheduler_slots AS s,
            {$CFG->prefix}scheduler_appointment AS a 
        WHERE 
            s.id = a.slotid AND
            s.schedulerid = '{$slot->schedulerid}' AND 
            a.studentid IN ('$oldslotownerlist') AND
            a.attended = 0
    ";
    if ($scheduler->schedulermode == 'onetime'){
        $sql .= " AND s.starttime > ".time();
    }
    if ($oldappointments = get_records_sql($sql)){
        foreach($oldappointments as $oldappointment){
            scheduler_delete_appointment($oldappointment->appointmentid, $oldappointment, $scheduler);
    
            // notify teacher
            if ($scheduler->allownotifications){
                $student = get_record('user', 'id', $USER->id);
                $teacher = get_record('user', 'id', $oldappointment->teacherid);
                include_once($CFG->dirroot.'/mod/scheduler/mailtemplatelib.php');
                $vars = array( 'SITE' => $SITE->shortname,
                               'SITE_URL' => $CFG->wwwroot,
                               'COURSE_SHORT' => $COURSE->shortname,
                               'COURSE' => $COURSE->fullname,
                               'COURSE_URL' => $CFG->wwwroot.'/course/view.php?id='.$COURSE->id,
                               'MODULE' => $scheduler->name,
                               'USER' => fullname($student),
                               'DATE' => userdate($oldappointment->starttime,get_string('strftimedate')),   // BUGFIX CONTRIB-937
 	                           'TIME' => userdate($oldappointment->starttime,get_string('strftimetime')),   // BUGFIX end
                               'DURATION' => $oldappointment->duration );
                $notification = compile_mail_template('cancelled', $vars );
                $notificationHtml = compile_mail_template('cancelled_html', $vars );
                email_to_user($teacher, $student, get_string('cancelledbystudent', 'scheduler', $SITE->shortname), $notification, $notificationHtml);
            }
            
            // delete all calendar events for that slot
            scheduler_delete_calendar_events($oldappointment);
            // renew all calendar events as some appointments may be left for other students
            scheduler_add_update_calendar_events($oldappointment, $course);
        }
    }
    

    /// create new appointment and add it for each member of the group
    foreach($oldslotowners as $astudentid){
        $appointment->slotid = $slotid;
        // $appointment->notes = $notes;
        $appointment->studentid = $astudentid;
        $appointment->attended = 0;
        $appointment->timecreated = time();
        $appointment->timemodified = time();
        if (!insert_record('scheduler_appointment', $appointment)) {
           error('Couldn\'t save choice to database');
        }
        scheduler_events_update($slot, $course);
        
        // notify teacher
        if ($scheduler->allownotifications){
            $student = get_record('user', 'id', $appointment->studentid);
            $teacher = get_record('user', 'id', $slot->teacherid);
            include_once($CFG->dirroot.'/mod/scheduler/mailtemplatelib.php');
            $vars = array( 'SITE' => $SITE->shortname,
                           'SITE_URL' => $CFG->wwwroot,
                           'COURSE_SHORT' => $COURSE->shortname,
                           'COURSE' => $COURSE->fullname,
                           'COURSE_URL' => $CFG->wwwroot.'/course/view.php?id='.$COURSE->id,
                           'MODULE' => $scheduler->name,
                           'USER' => fullname($student),
                           'DATE' => userdate($slot->starttime,get_string('strftimedate')),   // BUGFIX CONTRIB-937
 	                       'TIME' => userdate($slot->starttime,get_string('strftimetime')),   // BUGFIX end
                           'DURATION' => $slot->duration );
            $notification = compile_mail_template('applied', $vars );
            $notificationHtml = compile_mail_template('applied_html', $vars );
            email_to_user($teacher, $student, get_string('newappointment', 'scheduler', $SITE->shortname), $notification, $notificationHtml);
        }
    }
}
// *********************************** Disengage alone from the slot ******************************/
if ($action == 'disengage') {
    $appointments = get_records_select('scheduler_appointment', "studentid = $USER->id AND attended = 0");
    if ($appointments){
        foreach($appointments as $appointment){
            $oldslot = get_record('scheduler_slots', 'id', $appointment->slotid);
            scheduler_delete_appointment($appointment->id, $oldslot, $scheduler);
    
            // notify teacher
            if ($scheduler->allownotifications){
                $student = get_record('user', 'id', $USER->id);
                $teacher = get_record('user', 'id', $oldslot->teacherid);
                include_once($CFG->dirroot.'/mod/scheduler/mailtemplatelib.php');
                $vars = array( 'SITE' => $SITE->shortname,
                               'SITE_URL' => $CFG->wwwroot,
                               'COURSE_SHORT' => $COURSE->shortname,
                               'COURSE' => $COURSE->fullname,
                               'COURSE_URL' => $CFG->wwwroot.'/course/view.php?id='.$COURSE->id,
                               'MODULE' => $scheduler->name,
                               'USER' => fullname($student), 
                               'DATE' => userdate($oldslot->starttime,get_string('strftimedate')),  // BUGFIX CONTRIB-937
 	                           'TIME' => userdate($oldslot->starttime,get_string('strftimetime')),  // BUGFIX end
                               'DURATION' => $oldslot->duration );
                $notification = compile_mail_template('cancelled', $vars );
                $notificationHtml = compile_mail_template('cancelled_html', $vars );
                email_to_user($teacher, $student, get_string('cancelledbystudent', 'scheduler', $SITE->shortname), $notification, $notificationHtml);
            }                    
        }

        // delete calendar events for that slot
        scheduler_delete_calendar_events($oldslot);  
        // renew all calendar events as some appointments may be left for other students
        scheduler_add_update_calendar_events($oldslot, $course);
    }
}

?>
<?php // $Id: teacherview.php,v 1.2.2.11 2009/06/24 23:02:59 diml Exp $

/**
* @package mod-scheduler
* @category mod
* @author Gustav Delius, Valery Fremaux > 1.8
*
* This page prints the screen view for the teachers. It realizes all "view" related use cases.
*
* @usecase addslot
* @usecase updateslot
* @usecase addsession
* @usecase schedule
* @usecase schedulegroup
* @usecase viewstatistics
* @usecase viewstudent
* @usecase downloads
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/scheduler
}

/**
*
*/
function get_slot_data(&$form){
    if (!$form->hideuntil = optional_param('hideuntil', '', PARAM_INT)){
        $form->displayyear = required_param('displayyear', PARAM_INT);
        $form->displaymonth = required_param('displaymonth', PARAM_INT);
        $form->displayday = required_param('displayday', PARAM_INT);
        $form->hideuntil = make_timestamp($form->displayyear, $form->displaymonth, $form->displayday);
    }
    if (!$form->starttime = optional_param('starttime', '', PARAM_INT)){    
        $form->year = required_param('year', PARAM_INT);
        $form->month = required_param('month', PARAM_INT);
        $form->day = required_param('day', PARAM_INT);
        $form->hour = required_param('hour', PARAM_INT);
        $form->minute = required_param('minute', PARAM_INT);
        $form->starttime = make_timestamp($form->year, $form->month, $form->day, $form->hour, $form->minute);
    }
    $form->exclusivity = required_param('exclusivity', PARAM_INT);
    $form->reuse = required_param('reuse', PARAM_INT);
    $form->duration = required_param('duration', PARAM_INT);
    $form->notes = required_param('notes', PARAM_TEXT);
    $form->teacherid = required_param('teacherid', PARAM_INT);
    $form->appointmentlocation = required_param('appointmentlocation', PARAM_CLEAN);
}

/**
*
*/
function get_session_data(&$form){
    if (!$form->rangestart = optional_param('rangestart', '', PARAM_INT)){    
        $year = required_param('startyear', PARAM_INT);
        $month = required_param('startmonth', PARAM_INT);
        $day = required_param('startday', PARAM_INT);
        $form->rangestart = make_timestamp($year, $month, $day);
        $form->starthour = required_param('starthour', PARAM_INT);
        $form->startminute = required_param('startminute', PARAM_INT);
        $form->timestart = make_timestamp($year, $month, $day, $form->starthour, $form->startminute);
    }
    if (!$form->rangeend = optional_param('rangeend', '', PARAM_INT)){    
        $year = required_param('endyear', PARAM_INT);
        $month = required_param('endmonth', PARAM_INT);
        $day = required_param('endday', PARAM_INT);
        $form->rangeend = make_timestamp($year, $month, $day);
        $form->endhour = required_param('endhour', PARAM_INT);
        $form->endminute = required_param('endminute', PARAM_INT);
        $form->timeend = make_timestamp($year, $month, $day, $form->endhour, $form->endminute);
    }
    $form->monday = optional_param('monday', 0, PARAM_INT);
    $form->tuesday = optional_param('tuesday', 0, PARAM_INT);
    $form->wednesday = optional_param('wednesday', 0, PARAM_INT);
    $form->thursday = optional_param('thursday', 0, PARAM_INT);
    $form->friday = optional_param('friday', 0, PARAM_INT);
    $form->saturday = optional_param('saturday', 0, PARAM_INT);
    $form->sunday = optional_param('sunday', 0, PARAM_INT);
    $form->forcewhenoverlap = required_param('forcewhenoverlap', PARAM_INT);
    $form->exclusivity = required_param('exclusivity', PARAM_INT);
    $form->reuse = required_param('reuse', PARAM_INT);
    $form->divide = optional_param('divide', 0, PARAM_INT);
    $form->duration = optional_param('duration', 15, PARAM_INT);
    $form->teacherid = required_param('teacherid', PARAM_INT);
    $form->appointmentlocation = optional_param('appointmentlocation', '', PARAM_CLEAN);
    $form->emailfrom = required_param('emailfrom', PARAM_CLEAN);
    $form->displayfrom = required_param('displayfrom', PARAM_CLEAN);
}

    /**
    *
    */
    if (!defined('MOODLE_INTERNAL')){
        error("This file cannot be loaded directly");
    }

    if ($action){
        include('teacherview.controller.php');
    }

/************************************ View : New single slot form ****************************************/
if ($action == 'addslot'){
    print_heading(get_string('addsingleslot', 'scheduler'));

    if (empty($subaction)){
        $form->what = 'doaddupdateslot';
        // blank appointment data
        if (empty($form->appointments)) $form->appointments = array();
        $form->starttime = time();
        $form->duration = 15;
        $form->reuse = 1;
        $form->exclusivity = 1;
        $form->hideuntil = $scheduler->timemodified; // supposed being in the past so slot is visible
        $form->notes = '';
        $form->teacherid = $USER->id;
        $form->appointmentlocation = scheduler_get_last_location($scheduler);
    } elseif($subaction == 'cancel') {
        get_slot_data($form);
        $form->what = 'doaddupdateslot';
        $form->appointments = unserialize(stripslashes(required_param('appointmentssaved', PARAM_RAW)));
    } else {
        $retcode = include "teacherview.subcontroller.php";
        if ($retcode == -1) return -1; 
    }

    /// print errors
    if (!empty($errors)){
        $errorstr = '';
        foreach($errors as $anError){
            $errorstr .= $anError->message;
        }
        print_simple_box($errorstr, 'center', '70%', '', 5, 'errorbox');
    }

    /// print form
    print_simple_box_start('center', '', '');
    include('oneslotform.html');
    print_simple_box_end();
    echo '<br />';
    
    // return code for include
    return -1;
}
/************************************ View : Update single slot form ****************************************/
if ($action == 'updateslot') {
    $slotid = required_param('slotid', PARAM_INT);

    print_heading(get_string('updatesingleslot', 'scheduler'));

    if(empty($subaction)){
        if(!empty($errors)){ // if some errors, get data from client side
            get_slot_data($form);
            $form->appointments = unserialize(stripslashes(required_param('appointments', PARAM_RAW)));
        } else {
            /// get data from the last inserted
            $slot = get_record('scheduler_slots', 'id', $slotid);
            $form = &$slot;
            // get all appointments for this slot
            $form->appointments = array();
            $appointments = get_records('scheduler_appointment', 'slotid', $slotid);
            // convert appointement keys to studentid
            if ($appointments){
                foreach($appointments as $appointment){
                    $form->appointments[$appointment->studentid] = $appointment;
                }
            }
        }
    } elseif($subaction == 'cancel') {
        get_slot_data($form);
        $form->appointments = unserialize(stripslashes(required_param('appointmentssaved', PARAM_RAW)));
        $form->studentid = required_param('studentid', PARAM_INT);
        $form->slotid = required_param('slotid', PARAM_INT);
        $form->what = 'doaddupdateslot';
    } elseif($subaction != '') {
        $retcode = include "teacherview.subcontroller.php";
        if ($retcode == -1) return -1; 
    }

    // print errors and notices
    if (!empty($errors)){
        $errorstr = '';
        foreach($errors as $anError){
            $errorstr .= $anError->message;
        }
        print_simple_box($errorstr, 'center', '70%', '', 5, 'errorbox');
    }

    /// print form
    $form->what = 'doaddupdateslot';

    print_simple_box_start('center', '', '');
    include('oneslotform.html');
    print_simple_box_end();
    echo '<br />';
    
    // return code for include
    return -1;
}
/************************************ Add session multiple slots form ****************************************/
if ($action == 'addsession') {
    // if there is some error from controller, display it
    if (!empty($errors)){
        $errorstr = '';
        foreach($errors as $anError){
            $errorstr .= $anError->message;
        }
        print_simple_box($errorstr, 'center', '70%', '', 5, 'errorbox');
    }
    
    if (!empty($errors)){
        get_session_data($data);
        $form = &$data;
    } else {
        $form->rangestart = time();
        $form->rangeend = time();
        $form->timestart = time();
        $form->timeend = time() + HOURSECS;
        $form->hideuntil = $scheduler->timemodified;
        $form->duration = $scheduler->defaultslotduration;
        $form->forcewhenoverlap = 0;
        $form->teacherid = $USER->id;
        $form->exclusivity = 1;
        $form->duration = $scheduler->defaultslotduration;
        $form->reuse = 1;
        $form->monday = 1;
        $form->tuesday = 1;
        $form->wednesday = 1;
        $form->thursday = 1;
        $form->friday = 1;
        $form->saturday = 0;
        $form->sunday = 0;
    }

    print_heading(get_string('addsession', 'scheduler'));
    print_simple_box_start('center', '', '');
    include_once('addslotsform.html');
    print_simple_box_end();
    echo '<br />';
    
    // return code for include
    return -1;
}
/************************************ Schedule a student form ***********************************************/
if ($action == 'schedule') {    
    if ($subaction == 'dochooseslot'){
        /// set an advice message
        unset($erroritem);
        $erroritem->message = get_string('dontforgetsaveadvice', 'scheduler');
        $erroritem->on = '';
        $errors[] = $erroritem;

        $slotid = required_param('slotid', PARAM_INT);
        $studentid = required_param('studentid', PARAM_INT);
        if ($slot = get_record('scheduler_slots', 'id', $slotid)){
            $form = &$slot;

            $form->studentid = $studentid;
            $form->what = 'doaddupdateslot';
            $form->slotid = $slotid;
            $form->availableslots = scheduler_get_available_slots($studentid, $scheduler->id);            
            $appointment->studentid = $studentid;
            $appointment->attended = optional_param('attended', 0, PARAM_INT);
            $appointment->grade = optional_param('grade', 0, PARAM_INT);
            $appointment->appointmentnote = optional_param('appointmentnote', '', PARAM_TEXT);
            $appointment->timecreated = time();
            $appointment->timemodified = time();
            $appointments = get_records('scheduler_appointment', 'slotid', $slotid);
            $appointments[$appointment->studentid] = $appointment;
            $form->appointments = $appointments;
        } else {
            $form->studentid = $studentid;
            $form->what = 'doaddupdateslot';
            $form->slotid = 0;
            $form->starttime = time();
            $form->duration = 15;
            $form->reuse = 1;
            $form->exclusivity = 1;
            $form->hideuntil = $scheduler->timemodified; // supposed being in the past so slot is visible
            $form->notes = '';
            $form->teacherid = $USER->id;
            $form->appointmentlocation = scheduler_get_last_location($scheduler);
            $form->availableslots = scheduler_get_available_slots($studentid, $scheduler->id);            
            $form->appointments = unserialize(stripslashes(required_param('appointments', PARAM_RAW)));
        }
    } elseif($subaction == 'cancel') {
        get_slot_data($form);
        $form->appointments = unserialize(stripslashes(required_param('appointments', PARAM_RAW)));
        $form->studentid = required_param('studentid', PARAM_INT);
        $form->slotid = required_param('slotid', PARAM_INT);
        $form->availableslots = scheduler_get_available_slots($form->studentid, $scheduler->id);            
        $form->what = 'doaddupdateslot';
    } elseif(empty($subaction)) {
        if (!empty($errors)){
            get_slot_data($form);
            $form->availableslots = scheduler_get_available_slots($form->studentid, $scheduler->id);            
            $form->studentid = required_param('studentid', PARAM_INT);
            $form->seen = optional_param('seen', 0, PARAM_INT);
            $form->slotid = optional_param('slotid', -1, PARAM_INT);
        } else {
            $form->studentid = required_param('studentid', PARAM_INT);
            $form->seen = optional_param('seen', 0, PARAM_INT);
                
            /// getting available slots
            $form->availableslots = scheduler_get_available_slots($form->studentid, $scheduler->id);            
            $form->what = 'doaddupdateslot' ;
            $form->starttime = time();
            $form->duration = $scheduler->defaultslotduration;
            $form->reuse = 1;
            $form->exclusivity = 1;
            $form->hideuntil = $scheduler->timemodified; // supposed being in the past so slot is visible
            $form->notes = '';
            $form->teacherid = $USER->id;
            $form->appointmentlocation = scheduler_get_last_location($scheduler);
            $form->slotid = 0;
            $appointment->slotid = -1;
            $appointment->studentid = $form->studentid;
            $appointment->appointmentnote = '';
            $appointment->attended = $form->seen;
            $appointment->grade = '';
            $appointment->timecreated = time();
            $appointment->timemodified = time();
            $form->appointments[$form->studentid] = $appointment;
        }
    } elseif($subaction != '') {
        $retcode = include "teacherview.subcontroller.php";
        if ($retcode == -1) return -1; 
    }

    // display error or advices
    if (!empty($errors)){
        $errorstr = '';
        foreach($errors as $anError){
            $errorstr .= $anError->message;
        }
        print_simple_box($errorstr, 'center', '70%', '', 5, 'errorbox');
    }

    // diplay form
    $form->student = get_record('user', 'id', $form->studentid);
    $studentname = fullname($form->student, true);
    print_heading(get_string('scheduleappointment', 'scheduler', $studentname));
    print_simple_box_start('center', '', '');
    include('oneslotform.html');
    print_simple_box_end();
    echo '<br />';
    
    // return code for include
    return -1;
}
/************************************ Schedule a whole group in form ***********************************************/
if ($action == 'schedulegroup') { 
    if($subaction == 'dochooseslot'){
        /// set an advice message
        unset($erroritem);
        $erroritem->message = get_string('dontforgetsaveadvice', 'scheduler');
        $erroritem->on = '';
        $errors[] = $erroritem;

        $slotid = required_param('slotid', PARAM_INT);
        if ($slot = get_record('scheduler_slots', 'id', $slotid)){
            $form = &$slot;
            $form->groupid = required_param('groupid', PARAM_INT);
            $form->what = 'doaddupdateslot';
            $form->slotid = $slotid;
            $form->availableslots = scheduler_get_unappointed_slots($scheduler->id);
            $appointments = array();
            $members = groups_get_members($form->groupid);

            // add all group members to the slot, and match exclusivity
            foreach($members as $member){
                unset($appointment);
                // hack for 1.8 / 1.9 compatibility of groups_get_members() call
                if (is_numeric($member)){
                    $appointment->studentid = $member;
                } else {
                    $appointment->studentid = $member->id;
                }
                $appointment->attended = optional_param('attended', 0, PARAM_INT);
                $appointment->grade = optional_param('grade', 0, PARAM_INT);
                $appointment->appointmentnote = optional_param('appointmentnote', '', PARAM_TEXT);
                $appointment->timecreated = time();
                $appointment->timemodified = time();
                $appointments[$appointment->studentid] = $appointment;
            }
            $form->appointments = $appointments;
        } else {
            $form->groupid = $groupid;
            $form->what = 'doaddupdateslot';
            $form->slotid = 0;
            $form->starttime = time();
            $form->duration = 15;
            $form->reuse = 1;
            $form->exclusivity = 1;
            $form->hideuntil = $scheduler->timemodified; // supposed being in the past so slot is visible
            $form->notes = '';
            $form->teacherid = $USER->id;
            $form->appointmentlocation = scheduler_get_last_location($scheduler);
            $form->availableslots = scheduler_get_unappointed_slots($scheduler->id);            
            $form->appointments = unserialize(stripslashes(required_param('appointments', PARAM_RAW)));
        }
    } elseif($subaction == 'cancel') {
        get_slot_data($form);
        $form->appointments = unserialize(stripslashes(required_param('appointments', PARAM_RAW)));
        $form->studentid = required_param('studentid', PARAM_INT);
        $form->slotid = required_param('slotid', PARAM_INT);
        $form->availableslots = scheduler_get_unappointed_slots($scheduler->id);            
        $form->what = 'doaddupdateslot';
    } elseif(empty($subaction)) {
        if (!empty($errors)){
            get_slot_data($form);
            $form->availableslots = scheduler_get_unappointed_slots($scheduler->id);            
            $form->groupid = required_param('groupid', PARAM_INT);
            $form->seen = optional_param('seen', 0, PARAM_INT);
            $form->slotid = optional_param('slotid', -1, PARAM_INT);
        } else {
            $form->groupid = required_param('groupid', PARAM_INT);
            $form->seen = optional_param('seen', 0, PARAM_INT);
                
            /// getting available slots
            $form->availableslots = scheduler_get_unappointed_slots($scheduler->id);   
            $form->what = 'doaddupdateslot' ;
            $form->starttime = time();
            $form->duration = $scheduler->defaultslotduration;
            $form->reuse = 1;
            $form->hideuntil = $scheduler->timemodified; // supposed being in the past so slot is visible
            $form->notes = '';
            $form->teacherid = $USER->id;
            $form->appointmentlocation = scheduler_get_last_location($scheduler);
            $form->slotid = 0;
            $members = groups_get_members($form->groupid);
            $form->exclusivity = count($members);
            foreach($members as $member){
                unset($appointment);
                $appointment->slotid = -1;
                // hack for 1.8 / 1.9 compatibility of groups_get_members() call
                if (is_numeric($member)){
                    $appointment->studentid = $member;
                } else {
                    $appointment->studentid = $member->id;
                }
                $appointment->appointmentnote = '';
                $appointment->attended = $form->seen;
                $appointment->grade = '';
                $appointment->timecreated = time();
                $appointment->timemodified = time();
                $form->appointments[$appointment->studentid] = $appointment;
            }
        }
    } elseif($subaction != '') {
        $retcode = include "teacherview.subcontroller.php";
        if ($retcode == -1) return -1; 
    }

    // display error or advices
    if (!empty($errors)){
        $errorstr = '';
        foreach($errors as $anError){
            $errorstr .= $anError->message;
        }
        print_simple_box($errorstr, 'center', '70%', '', 5, 'errorbox');
    }

    // diplay form
    $form->group = get_record('groups', 'id', $form->groupid);
    print_heading(get_string('scheduleappointment', 'scheduler', $form->group->name));
    print_simple_box_start('center', '', '');
    include('oneslotform.html');
    print_simple_box_end();
    echo '<br />';
    
    // return code for include
    return -1;
}
//****************** Standard view ***********************************************//


/// print top tabs

$tabrows = array();
$row  = array();

switch ($action){
    case 'viewstatistics':{
        $currenttab = get_string('statistics', 'scheduler');
        break;
    } 
    case 'datelist':{
        $currenttab = get_string('datelist', 'scheduler');
        break;
    } 
    case 'viewstudent':{
        $currenttab = get_string('studentdetails', 'scheduler');
        $row[] = new tabobject($currenttab, '', $currenttab);
        break;
    } 
    case 'downloads':{
        $currenttab = get_string('downloads', 'scheduler');
        break;
    } 
    default: {
        $currenttab = get_string($page, 'scheduler');
    }
}

$tabname = get_string('myappointments', 'scheduler');
$row[] = new tabobject($tabname, "view.php?id={$cm->id}&amp;page=myappointments", $tabname);
if (count_records('scheduler_slots', 'schedulerid', $scheduler->id) > count_records('scheduler_slots', 'schedulerid', $scheduler->id, 'teacherid', $USER->id)) {
    $tabname = get_string('allappointments', 'scheduler');
    $row[] = new tabobject($tabname, "view.php?id={$cm->id}&amp;page=allappointments", $tabname);
} else {
    // we are alone in this scheduler
    if ($page == 'allappointements') {
        $currenttab = get_string('myappointments', 'scheduler');
    }
}
$tabname = get_string('datelist', 'scheduler');
$row[] = new tabobject($tabname, "view.php?id={$cm->id}&amp;what=datelist", $tabname);
$tabname = get_string('statistics', 'scheduler');
$row[] = new tabobject($tabname, "view.php?what=viewstatistics&amp;id={$cm->id}&amp;course={$scheduler->course}&amp;page=overall", $tabname);
$tabname = get_string('downloads', 'scheduler');
$row[] = new tabobject($tabname, "view.php?what=downloads&amp;id={$cm->id}&amp;course={$scheduler->course}", $tabname);
$tabrows[] = $row;
print_tabs($tabrows, $currenttab);

/// print heading
print_heading($scheduler->name);

/// print page
if ($scheduler->description) {
    print_simple_box(format_text($scheduler->description), 'center');
}

if ($page == 'allappointments'){
    $select = "schedulerid = '". $scheduler->id ."'";
} else {
    $select = "schedulerid = '". $scheduler->id ."' AND teacherid = '{$USER->id}'";
    $page = 'myappointments';
}
$sqlcount = count_records_select('scheduler_slots',$select);

if (($offset == '') && ($sqlcount > 25)){
    $offsetcount = count_records_select('scheduler_slots', $select." AND starttime < '".strtotime('now')."'");
    $offset = floor($offsetcount/25);
}

/*
$sql = "
    SELECT 
        s.*,
        COUNT(IF(a.studentid IS NOT NULL, 1, NULL)) as isappointed,
        COUNT(IF(a.attended IS NOT NULL AND a.attended > 0, 1, NULL)) as isattended
    FROM 
        {$CFG->prefix}scheduler_slots AS s
    LEFT JOIN
        {$CFG->prefix}scheduler_appointment AS a
    ON
        s.id = a.slotid
    WHERE 
        {$select} 
    GROUP BY
        s.id
    ORDER BY 
        starttime ASC
";
$slots = get_records_sql($sql, $offset * 25, 25);
*/

// More compatible way to do it :

$slots = get_records_select('scheduler_slots', $select, 'starttime', '*', $offset * 25, 25);
if ($slots){
    foreach(array_keys($slots) as $slotid){
        $slots[$slotid]->isappointed = count_records('scheduler_appointment', 'slotid', $slotid);
        $slots[$slotid]->isattended = record_exists('scheduler_appointment', 'slotid', $slotid, 'attended', 1);
    }
}

$straddsession = get_string('addsession', 'scheduler');
$straddsingleslot = get_string('addsingleslot', 'scheduler');
$strdownloadexcel = get_string('downloadexcel', 'scheduler');

/// some slots already exist
if ($slots){
    // print instructions and button for creating slots
    print_simple_box_start('center', '', '');
    print_string('addslot', 'scheduler');

    // print add session button
    $strdeleteallslots = get_string('deleteallslots', 'scheduler');
    $strdeleteallunusedslots = get_string('deleteallunusedslots', 'scheduler');
    $strdeleteunusedslots = get_string('deleteunusedslots', 'scheduler');
    $strdeletemyslots = get_string('deletemyslots', 'scheduler');
    $strstudents = get_string('students', 'scheduler');
    $displaydeletebuttons = 1;
    echo '<center>';
    include "commands.html";
    echo '</center>';        
    print_simple_box_end();
    
    // prepare slots table
    if ($page == 'myappointments'){
        $table->head  = array ('', $strdate, $strstart, $strend, $strstudents, $straction);
        $table->align = array ('CENTER', 'LEFT', 'LEFT', 'CENTER', 'CENTER', 'CENTER', 'LEFT', 'CENTER');
        $table->width = '80%';
    } else {
        $table->head  = array ('', $strdate, $strstart, $strend, $strstudents, format_string($scheduler->staffrolename), $straction);
        $table->align = array ('CENTER', 'LEFT', 'LEFT', 'CENTER', 'CENTER', 'CENTER', 'LEFT', 'LEFT', 'CENTER');
        $table->width = '80%';
    }
    $offsetdatemem = '';
    foreach($slots as $slot) {
        if (!$slot->isappointed && $slot->starttime + (60 * $slot->duration) < time()) {
            // This slot is in the past and has not been chosen by any student, so delete
            delete_records('scheduler_slots', 'id', $slot->id);
            continue;
        }

        /// Parameter $local in scheduler_userdate and scheduler_usertime added by power-web.at
        /// When local Time or Date is needed the $local Param must be set to 1 
        $offsetdate = scheduler_userdate($slot->starttime,1);
        $offsettime = scheduler_usertime($slot->starttime,1);
        $endtime = scheduler_usertime($slot->starttime + ($slot->duration * 60),1);

        /// make a slot select box 
        if ($USER->id == $slot->teacherid || has_capability('mod/scheduler:manageallappointments', $context)){
            $selectcheck = "<input type=\"checkbox\" id=\"sel_{$slot->id}\" name=\"sel_{$slot->id}\" onclick=\"document.forms['deleteslotsform'].items.value = toggleListState(document.forms['deleteslotsform'].items.value, 'sel_{$slot->id}', '{$slot->id}');\" />";
        } else {
            $selectcheck = '';
        }

        // slot is appointed
        $studentArray = array();
        if ($slot->isappointed) {
            $appointedstudents = get_records('scheduler_appointment', 'slotid', $slot->id);
            $studentArray[] = "<form name=\"appointementseen_{$slot->id}\" method=\"post\" action=\"view.php\">";
            $studentArray[] = "<input type=\"hidden\" name=\"id\" value=\"".$cm->id."\" />";
            $studentArray[] = "<input type=\"hidden\" name=\"slotid\" value=\"".$slot->id."\" />";
            $studentArray[] = "<input type=\"hidden\" name=\"what\" value=\"saveseen\" />";
            $studentArray[] = "<input type=\"hidden\" name=\"page\" value=\"".$page."\" />";
            foreach($appointedstudents as $appstudent){
                $student = get_record('user', 'id', $appstudent->studentid);
                $picture = print_user_picture($appstudent->studentid, $course->id, $student->picture, 0, true, true);
                $name = "<a href=\"view.php?what=viewstudent&amp;id={$cm->id}&amp;studentid={$student->id}&amp;course={$scheduler->course}&amp;order=DESC\">".fullname($student).'</a>';

                
                /// formatting grade
                $grade = $appstudent->grade;
                if ($scheduler->scale > 0 and $grade != ''){
                    $grade = $grade . '/' . $scheduler->scale;
                }
                if ($grade != ''){
                    $grade = "($grade)";
                }
                
                if ($USER->id == $slot->teacherid || has_capability('mod/scheduler:manageallappointments', $context)){
                    $checked = ($appstudent->attended) ? 'checked="checked"' : '' ; 
                    $checkbox = "<input type=\"checkbox\" name=\"seen[]\" value=\"{$appstudent->id}\" {$checked} />";
                } else {
                    // same thing but no link
                    if ($appstudent->attended == 1) {
                        $checkbox .= '<img src="pix/ticked.gif" border="0">';
                    } else {
                        $checkbox .= '<img src="pix/unticked.gif" border="0">';
                    }
                }
                $studentArray[] = "$checkbox $picture $name $grade<br/>";
            }
            $studentArray[] = "<a href=\"javascript:document.forms['appointementseen_{$slot->id}'].submit();\">".get_string('saveseen','scheduler').'</a>';
            $studentArray[] = "</form>";
        } else {
            // slot is free
            $picture = '';
            $name = '';
            $checkbox = '';
        }

        $actions = '<span style="font-size: x-small;">';
        if ($USER->id == $slot->teacherid || has_capability('mod/scheduler:manageallappointments', $context)){
            $actions .= "<a href=\"view.php?what=deleteslot&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\"><img src=\"{$CFG->pixpath}/t/delete.gif\" alt=\"".get_string('delete')."\" /></a>";
            $actions .= "&nbsp;<a href=\"view.php?what=updateslot&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\"><img src=\"{$CFG->pixpath}/t/edit.gif\" alt=\"".get_string('move', 'scheduler')."\" /></a>";
            if ($slot->isattended){
                $actions .= "&nbsp;<img src=\"{$CFG->pixpath}/c/group.gif\" title=\"".get_string('isattended', 'scheduler')."\" />";
            } else {
                if ($slot->isappointed > 1){
                    $actions .= "&nbsp;<img src=\"{$CFG->pixpath}/c/group.gif\" title=\"".get_string('isnoexclusive', 'scheduler')."\" />";
                } else {
                    if ($slot->exclusivity == 1){
                        $actions .= "&nbsp;<a href=\"view.php?what=allowgroup&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\"><img src=\"{$CFG->pixpath}/t/groupn.gif\" alt=\"".get_string('allowgroup', 'scheduler')."\" /></a>";
                    } else {
                        $actions .= "&nbsp;<a href=\"view.php?what=forbidgroup&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\"><img src=\"{$CFG->pixpath}/t/groupv.gif\" alt=\"".get_string('forbidgroup', 'scheduler')."\" /></a>";
                    }
                }
            }
            if ($slot->isappointed){
                $actions .= "&nbsp;<a href=\"view.php?what=revokeall&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\"><img src=\"{$CFG->pixpath}/s/no.gif\" alt=\"".get_string('revoke', 'scheduler')."\" /></a>";
            }
        } else {
            // just signal group status
            if ($slot->isattended){
                $actions .= "&nbsp;<img src=\"{$CFG->pixpath}/c/group.gif\" title=\"".get_string('isattended', 'scheduler')."\" />";
            } else {
                if ($slot->isappointed > 1){
                    $actions .= "&nbsp;<img src=\"{$CFG->pixpath}/c/group.gif\" title=\"".get_string('isnonexclusive', 'scheduler')."\" />";
                } else {
                    if ($slot->exclusivity == 1){
                        $actions .= "&nbsp;<img src=\"{$CFG->pixpath}/t/groupn.gif\" title=\"".get_string('allowgroup', 'scheduler')."\" />";
                    } else {
                        $actions .= "&nbsp;<img src=\"{$CFG->pixpath}/t/groupv.gif\" alt=\"".get_string('forbidgroup', 'scheduler')."\" />";
                    }
                }
            }
        }
        if ($slot->exclusivity > 1){
            $actions .= ' ('.$slot->exclusivity.')';
        }
        if ($slot->reuse){
            $actions .= "&nbsp;<a href=\"view.php?what=unreuse&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\"><img src=\"pix/volatile_shadow.gif\" title=\"".get_string('setunreused', 'scheduler')."\" border=\"0\" /></a>";
        } else {
            $actions .= "&nbsp;<a href=\"view.php?what=reuse&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\"><img src=\"pix/volatile.gif\" title=\"".get_string('setreused', 'scheduler')."\" border=\"0\" /></a>";
        }
        $actions .= '</span>';
        if($page == 'myappointments'){
            $table->data[] = array ($selectcheck, ($offsetdate == $offsetdatemem) ? '' : $offsetdate, $offsettime, $endtime, implode("\n",$studentArray), $actions);
        } else {
            $teacherlink = "<a href=\"$CFG->wwwroot/user/view.php?id={$slot->teacherid}\">".fullname(get_record('user', 'id', $slot->teacherid))."</a>";
            $table->data[] = array ($selectcheck, ($offsetdate == $offsetdatemem) ? '' : $offsetdate, $offsettime, $endtime, implode("\n",$studentArray), $teacherlink, $actions);
        }
        $offsetdatemem = $offsetdate;
    }

    // print slots table
    print_heading(get_string('slots' ,'scheduler'));
    print_table($table);
?>
<center>
<table width="80%"> 
    <tr>
        <td align="left">
            <script src="<?php echo "{$CFG->wwwroot}/mod/scheduler/scripts/listlib.js" ?>"></script>
            <form name="deleteslotsform" style="display : inline">
            <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
            <input type="hidden" name="page" value="<?php echo $page ?>" />
            <input type="hidden" name="what" value="deleteslots" />
            <input type="hidden" name="items" value="" />
            </form>
            <a href="javascript:document.forms['deleteslotsform'].submit()"><?php print_string('deleteselection','scheduler') ?></a>
            <br />
        </td>
    </tr>
</table>

<?php
    if ($sqlcount > 25){
        echo "Page : ";
        $pagescount = ceil($sqlcount/25);
        for ($n = 0; $n < $pagescount; $n ++){
            if ($n == $offset){
                echo ($n+1).' ';
            } else {
                echo "<a href=view.php?id={$cm->id}&amp;page={$page}&amp;offset={$n}>".($n+1)."</a> ";
            }
        }
    }

    echo '</center>';

    // Instruction for teacher to click Seen box after appointment
    echo '<br /><center>' . get_string('markseen', 'scheduler') . '</center>';

} else if ($action != 'addsession') { 
    /// There are no slots, should the teacher be asked to make some
    print_simple_box_start('center', '', '');
    print_string('welcomenewteacher', 'scheduler');
    echo '<center>';
    $displaydeletebuttons = 0;
    include "commands.html";
    echo '</center>';
    print_simple_box_end();
}

/// print table of outstanding appointer (students) 
?>
<center>
<table width="90%">
    <tr valign="top">
        <td width="50%">
<?php
print_heading(get_string('schedulestudents', 'scheduler'));

if ($cm->groupmembersonly){
    $groups = groups_get_all_groups($COURSE->id, 0, $cm->groupingid);
    $usergroups = array_keys($groups);
} else {
    $groups = get_groups($COURSE->id);
    $usergroups = '';
}

$students = get_users_by_capability ($context, 'mod/scheduler:appoint', 'u.id,lastname,firstname,email,picture', 'lastname', '', '', $usergroups);
if (!$students) {
    $nostudentstr = get_string('noexistingstudents');
    if ($COURSE->id == SITEID){
        $nostudentstr .= '<br/>'.get_string('howtoaddstudents','scheduler');
    }
    notify($nostudentstr);
} else {
    $mtable->head  = array ('', $strname, $stremail, $strseen, $straction);
    $mtable->align = array ('CENTER','LEFT','LEFT','CENTER','CENTER');
    $mtable->width = array('', '', '', '', '');
    $mtable->data = array();
    // In $mailto the mailing list for reminder emails is built up
    $mailto = '<a href="mailto:';
    $date = usergetdate(time());
    foreach ($students as $student) {
        if (!scheduler_has_slot($student->id, $scheduler, true, $scheduler->schedulermode == 'onetime')) {
            $picture = print_user_picture($student->id, $course->id, $student->picture, false, true);
            $name = "<a href=\"../../user/view.php?id={$student->id}&amp;course={$scheduler->course}\">";
            $name .= fullname($student);
            $name .= '</a>';
            $email = obfuscate_mailto($student->email);
            if (scheduler_has_slot($student->id, $scheduler, true, false) == 0){
                // student has never scheduled
                $mailto .= $student->email.', ';
            }
            $checkbox = "<a href=\"view.php?what=schedule&amp;id={$cm->id}&amp;studentid={$student->id}&amp;page={$page}&amp;seen=1\">";
            $checkbox .= '<img src="pix/unticked.gif" border="0" />';
            $checkbox .= '</a>';
            $actions = '<span style="font-size: x-small;">';
            $actions .= "<a href=\"view.php?what=schedule&amp;id={$cm->id}&amp;studentid={$student->id}&amp;page={$page}\">";
            $actions .= get_string('schedule', 'scheduler');
            $actions .= '</a></span>';
            $mtable->data[] = array($picture, $name, $email, $checkbox, $actions);
        }
    }

    // dont print if allowed to book multiple appointments
    // There are students who still have to make appointments
    if (($num = count($mtable->data)) > 0) { 

        // Print number of students who still have to make an appointment
        print_heading(get_string('missingstudents', 'scheduler', $num), 'center', 3);

        // Print links to print invitation or reminder emails
        $strinvitation = get_string('invitation', 'scheduler');
        $strreminder = get_string('reminder', 'scheduler');
        $mailto = rtrim($mailto, ', ');

        $subject = $strinvitation . ': ' . $scheduler->name;
        $body = $strinvitation . ': ' . $scheduler->name . "\n\n";
        $body .= get_string('invitationtext', 'scheduler');
        $body .= "{$CFG->wwwroot}/mod/scheduler/view.php?id={$cm->id}";
        echo '<center>'.get_string('composeemail', 'scheduler').
            $mailto.'?subject='.htmlentities(rawurlencode($subject)).
            '&amp;body='.htmlentities(rawurlencode($body)).
            '"> '.$strinvitation.'</a> ';

        $subject = $strreminder . ': ' . $scheduler->name;
        $body = $strreminder . ': ' . $scheduler->name . "\n\n";
        $body .= get_string('remindertext', 'scheduler');
        $body .= "{$CFG->wwwroot}/mod/scheduler/view.php?id={$cm->id}";
        echo $mailto.'?subject='.htmlentities(rawurlencode($subject)).
            '&amp;body='.htmlentities(rawurlencode($body)).
            '"> '.$strreminder.'</a></center><br />';

        // print table of students who still have to make appointments
        print_table($mtable);
    } else {
        notify(get_string('nostudents', 'scheduler'));
    }
}
?>
        </td>
<?php
if ($groupmode){
?>
        <td width="50%">
<?php

/// print table of outstanding appointer (groups) 

    print_heading(get_string('schedulegroups', 'scheduler'));
    
    if (empty($groups)){
        notify(get_string('nogroups', 'scheduler'));
    } else {
        $mtable->head  = array ('', $strname, $straction);
        $mtable->align = array ('CENTER','LEFT','CENTER');
        $mtable->width = array('', '', '');
        $mtable->data = array();
        foreach($groups as $group){
            $members = get_group_users($group->id, 'lastname', '', 'u.id, lastname, firstname, email, picture');
            if (empty($members)) continue;
            if (!scheduler_has_slot(implode(',', array_keys($members)), $scheduler, true, $scheduler->schedulermode == 'onetime')) {
                $actions = '<span style="font-size: x-small;">';
                $actions .= "<a href=\"view.php?what=schedulegroup&amp;id={$cm->id}&amp;groupid={$group->id}&amp;page={$page}\">";
                $actions .= get_string('schedule', 'scheduler');
                $actions .= '</a></span>';
                $groupmembers = array();
                foreach($members as $member){
                    $groupmembers[] = fullname($member);
                }
                $groupcrew = '['. implode(", ", $groupmembers) . ']';
                $mtable->data[] = array('', $groups[$group->id]->name.' '.$groupcrew, $actions);
            }
        }
        // print table of students who still have to make appointments
        if (!empty($mtable->data)){
            print_table($mtable);
        } else {
            notify(get_string('nogroups', 'scheduler'));
        }
    }
?>
        </td>
<?php
}
?>
    </tr>
</table>
</center>

<center>
<form action="<?php echo "{$CFG->wwwroot}/course/view.php" ?>" method="get">
    <input type="hidden" name="id" value="<?php p($course->id) ?>" />
    <input type="submit" name="go_btn" value="<?php print_string('return', 'scheduler') ?>" />
</form>
<center>
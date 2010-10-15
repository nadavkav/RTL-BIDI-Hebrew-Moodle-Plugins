<?php

    /**
    * @package mod-scheduler
    * @category mod
    * @author Valery Fremaux > 1.8
    */

    /**
    * Security traps
    */
    if (!defined('MOODLE_INTERNAL')){
        error("This file cannot be loaded directly");
    }
    
     // a function utility for sorting stat results
     function byName($a, $b){
         return strcasecmp($a[0],$b[0]);
     }

    // precompute groups in case partial popuation is considered by grouping 

    if ($cm->groupmembersonly){
        $groups = groups_get_all_groups($COURSE->id, 0, $cm->groupingid);
        $usergroups = array_keys($groups);
    } else {
        $groups = get_groups($COURSE->id);
        $usergroups = '';
    }

    //display statistics tabs

    $tabs = array('overall', 'studentbreakdown', 'staffbreakdown','lengthbreakdown','groupbreakdown');
    $tabrows = array();
    $row  = array();
    $currenttab = '';
    foreach ($tabs as $tab) {
        $a = ($tab == 'staffbreakdown') ? format_string($scheduler->staffrolename) : '';
        $tabname = get_string($tab, 'scheduler', strtolower($a));
        $row[] = new tabobject($tabname, "view.php?what=viewstatistics&amp;id=$cm->id&amp;course=$scheduler->course&amp;page=".$tab, $tabname);
    }
    $tabrows[] = $row;

    print_tabs($tabrows, get_string($page, 'scheduler'));

    //display correct type of statistics by request

    $attendees = get_users_by_capability($context, 'mod/scheduler:appoint', '*', 'u.lastname', '', '', $usergroups);

    switch ($page) {
    case 'overall':
        $sql = "
            SELECT 
                COUNT(DISTINCT(a.studentid))
            FROM
                {$CFG->prefix}scheduler_slots s,
                {$CFG->prefix}scheduler_appointment a
            WHERE
                s.id = a.slotid AND
                s.schedulerid = {$scheduler->id} AND
                a.attended = 1
        ";
        $attended = count_records_sql($sql);

        $sql = "
            SELECT 
                COUNT(DISTINCT(a.studentid))
            FROM
                {$CFG->prefix}scheduler_slots s,
                {$CFG->prefix}scheduler_appointment a
            WHERE
                s.id = a.slotid AND
                s.schedulerid = {$scheduler->id} AND
                a.attended = 0
        ";
        $registered = count_records_sql($sql);

        $sql = "
            SELECT 
                COUNT(DISTINCT(s.id))
            FROM
                {$CFG->prefix}scheduler_slots s
            LEFT JOIN
                {$CFG->prefix}scheduler_appointment a
            ON
                s.id = a.slotid
            WHERE
                s.schedulerid = {$scheduler->id} AND
                s.teacherid = {$USER->id} AND
                a.attended IS NULL
        ";
        $freeowned = count_records_sql($sql);

        $sql = "
            SELECT 
                COUNT(DISTINCT(s.id))
            FROM
                {$CFG->prefix}scheduler_slots s
            LEFT JOIN
                {$CFG->prefix}scheduler_appointment a
            ON
                s.id = a.slotid
            WHERE
                s.schedulerid = {$scheduler->id} AND
                s.teacherid != {$USER->id} AND
                a.attended IS NULL
        ";
        $freenotowned = count_records_sql($sql);
        
        $allattendees = ($attendees) ? count($attendees) : 0 ;
        
        $str = '<h3>'.get_string('attendable', 'scheduler').'</h3>';
        $str .= '<b>'.get_string('attendablelbl', 'scheduler').'</b>: ' . $allattendees . '<br/>';
        $str .= '<h3>'.get_string('attended', 'scheduler').'</h3>';
        $str .= '<b>'.get_string('attendedlbl', 'scheduler').'</b>: ' . $attended . '<br/><br/>';
        $str .= '<h3>'.get_string('unattended', 'scheduler').'</h3>';
        $str .= '<b>'.get_string('registeredlbl', 'scheduler').'</b>: ' . $registered . '<br/>';
        $str .= '<b>'.get_string('unregisteredlbl', 'scheduler').'</b>: ' . ($allattendees - $registered) . '<br/>';
        $str .= '<h3>'.get_string('availableslots', 'scheduler').'</h3>';
        $str .= '<b>'.get_string('availableslotsowned', 'scheduler').'</b>: ' . $freeowned . '<br/>';
        $str .= '<b>'.get_string('availableslotsnotowned', 'scheduler').'</b>: ' . $freenotowned . '<br/>';
        $str .= '<b>'.get_string('availableslotsall', 'scheduler').'</b>: ' . ($freeowned + $freenotowned) . '<br/>';
        
        print_simple_box($str);
        
        break;
    case 'studentbreakdown':
        //display the ammount of time each student has received

        if (!empty($attendees)) {
           $table->head  = array (get_string('student', 'scheduler'), get_string('duration', 'scheduler'));
           $table->align = array ('LEFT', 'CENTER');
           $table->width = '70%';
           $table->data = array();
           $sql = "
                SELECT 
                    a.studentid,
                    SUM(s.duration) as totaltime
                FROM 
                    {$CFG->prefix}scheduler_slots s,
                    {$CFG->prefix}scheduler_appointment a
                WHERE 
                    s.id = a.slotid AND
                    a.studentid > 0 AND
                    s.schedulerid = '{$scheduler->id}'
                GROUP BY
                    a.studentid
           ";
           if ($statrecords = get_records_sql($sql)) {
             foreach($statrecords as $aRecord){
                $table->data[] = array (fullname($students[$aRecord->studentid]), $aRecord->totaltime);
             }

             uasort($table->data, 'byName');
           }
           print_table($table);
        }
        else{
            print_simple_box(get_string('nostudents', 'scheduler'), 'center', '70%');
        }
        break;
    case 'staffbreakdown':
        //display break down by member of staff
        $sql = "
            SELECT 
                s.teacherid,
                SUM(s.duration) as totaltime
            FROM 
                {$CFG->prefix}scheduler_slots s
            LEFT JOIN
                {$CFG->prefix}scheduler_appointment a 
            ON 
                a.slotid = s.id
            WHERE 
                s.schedulerid = '{$scheduler->id}' AND
                s.teacherid = '{$scheduler->teacher}' AND 
                a.studentid IS NOT NULL
            GROUP BY
                s.teacherid
        ";
        if ($statrecords = get_records_sql($sql)) {
           $table->width = '70%';
           $table->head  = array (format_string($scheduler->staffrolename), get_string('cumulatedduration', 'scheduler'));
           $table->align = array ('LEFT', 'CENTER');
           foreach($statrecords as $aRecord){
              $aTeacher = get_record('user', 'id', $aRecord->teacherid);
              $table->data[] = array (fullname($aTeacher), $aRecord->totaltime);
           }
           uasort($table->data, 'byName');
           print_table($table);
        }
        break;
    case 'lengthbreakdown':
        //display break down my duration
        $sql = "
            SELECT 
                s.* 
            FROM 
                {$CFG->prefix}scheduler_slots s
            LEFT JOIN
                {$CFG->prefix}scheduler_appointment a
            ON 
                a.slotid = s.id
            WHERE 
                a.studentid IS NOT NULL AND
                schedulerid = '{$scheduler->id}'
        ";
        if ($slots = get_records_sql($sql)) {
           $table->head  = array (get_string('duration', 'scheduler'), get_string('appointments', 'scheduler'));
           $table->align = array ('LEFT', 'CENTER');
           $table->width = '70%';

           $durationcount = array();
           foreach($slots as $slot) {
               if (array_key_exists($slot->duration, $durationcount)) {
                   $durationcount[$slot->duration] ++;
               } else {
                   $durationcount[$slot->duration] = 1;
               }
           }
           foreach ($durationcount as $key => $duration) {
               $table->data[] = array ($key, $duration);
           }        
          print_table($table);
        }         
        break;
    case 'groupbreakdown':
        //display by number of atendees to one member of staff
        $sql = "
            SELECT
                s.starttime,
                COUNT(*) as groupsize,
                MAX(s.duration) as duration
            FROM 
                {$CFG->prefix}scheduler_slots s
            LEFT JOIN
                {$CFG->prefix}scheduler_appointment a
            ON 
                a.slotid = s.id
            WHERE 
                a.studentid IS NOT NULL AND
                schedulerid = '{$scheduler->id}'
            GROUP BY
                s.starttime
            ORDER BY
                groupsize DESC
        ";
        if ($groupslots = get_records_sql($sql)){
            $table->head  = array (get_string('groupsize', 'scheduler'), get_string('occurrences', 'scheduler'), get_string('cumulatedduration', 'scheduler'));
            $table->align = array ('LEFT', 'CENTER', 'CENTER');
            $table->width = '70%';
            $grouprows = array();
            foreach($groupslots as $aGroup){
                if (!array_key_exists($aGroup->groupsize, $grouprows)){
                    $grouprows[$aGroup->groupsize]->occurrences = 0;
                    $grouprows[$aGroup->groupsize]->duration = 0;
                }                
                $grouprows[$aGroup->groupsize]->occurrences++;
                $grouprows[$aGroup->groupsize]->duration += $aGroup->duration;
            }
            foreach(array_keys($grouprows) as $aGroupSize){
                $table->data[] = array ($aGroupSize,$grouprows[$aGroupSize]->occurrences, $grouprows[$aGroupSize]->duration);
            }
            print_table($table);
        }
   }
   echo '<br/>';
   print_continue("$CFG->wwwroot/mod/scheduler/view.php?id=".$cm->id);
   /// Finish the page
   print_footer($course);
   exit;
?>
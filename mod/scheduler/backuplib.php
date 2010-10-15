<?php //$Id: backuplib.php,v 1.2.10.3 2008/04/01 23:16:01 diml Exp $

    /**
    * This php script contains all the stuff to backup/restore
    * scheduler mods
    *
    * @package mod-scheduler
    * @category mod
    * @author Valery Fremaux (admin@ethnoinformatique.fr)
    * 
    */

    //This is the "graphical" structure of the scheduler mod:
    //
    //                     scheduler                                      
    //                    (CL, pk->id)
    //                        |
    //                        |
    //                        |
    //                   scheduler_slots 
    //               (UL, pk->id, fk->scheduler)     
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files
    //
    //-----------------------------------------------------------
    
    /**
    * Includes and requires
    */
    include_once $CFG->dirroot."/mod/scheduler/locallib.php";

    /**
    *
    */
    function scheduler_backup_mods($bf, $preferences) {
        //Iterate over scheduler table
        $schedulers = get_records ('scheduler', 'course', $preferences->backup_course, 'id');
        if ($schedulers) {
            foreach ($schedulers as $scheduler) {
                scheduler_backup_one_mod($bf, $preferences, $scheduler);
            }
        }
    }

    /**
    *
    */
    function scheduler_backup_one_mod($bf, $preferences, $scheduler) {
        global $CFG;
        
        if (is_numeric($scheduler)) {
            $scheduler = get_record('scheduler', 'id', $scheduler);
        }

        $status = true;

        //Start mod
        $status = $status && fwrite ($bf, start_tag('MOD', 3, true));
        //Print scheduler data
        fwrite ($bf, full_tag('ID', 4, false, $scheduler->id));
        fwrite ($bf, full_tag('MODTYPE', 4, false, 'scheduler'));
        fwrite ($bf, full_tag('NAME', 4, false, $scheduler->name));
        fwrite ($bf, full_tag('DESCRIPTION', 4, false, $scheduler->description));
        fwrite ($bf, full_tag('TEACHER', 4, false, $scheduler->teacher));
        fwrite ($bf, full_tag('SCALE', 4, false, $scheduler->scale));
        fwrite ($bf, full_tag('STAFFROLENAME', 4, false, $scheduler->staffrolename));
        fwrite ($bf, full_tag('SCHEDULERMODE', 4, false, $scheduler->schedulermode));
        fwrite ($bf, full_tag('REUSEGUARDTIME', 4, false, $scheduler->reuseguardtime));
        fwrite ($bf, full_tag('DEFAULTSLOTDURATION', 4, false, $scheduler->defaultslotduration));
        fwrite ($bf, full_tag('ALLOWNOTIFICATIONS', 4, false, $scheduler->allownotifications));
        fwrite ($bf, full_tag('TIMEMODIFIED', 4, false, $scheduler->timemodified));

        //if we've selected to backup users info, then execute backup_scheduler_slots and appointments
        if ($preferences->mods['scheduler']->userinfo) {
            $scheduler_slots = get_records('scheduler_slots', 'schedulerid', $scheduler->id, 'id');
            $status = $status && backup_scheduler_slots($bf, $preferences, $scheduler->id, $scheduler_slots);
            $status = $status && backup_scheduler_appointments($bf, $preferences, $scheduler_slots);
        }
        //End mod
        $status = $status && fwrite($bf, end_tag('MOD', 3, true));
        return $status;
    }

    /**
    * Backup scheduler_slots contents (executed from scheduler_backup_mods)
    */
    function backup_scheduler_slots ($bf, $preferences, $schedulerid, $slots) {
        global $CFG;

        $status = true;

        //If there is slots
        if ($slots) {
            //Write start tag
            $status = $status && fwrite ($bf, start_tag('SLOTS', 4, true));
            //Iterate over each slot
            foreach ($slots as $slot) {
                //Start slot
                $status = $status && fwrite ($bf, start_tag('SLOT', 5, true));
                //Print scheduler_slots contents
                fwrite ($bf, full_tag('ID', 6, false, $slot->id));
                fwrite ($bf, full_tag('SCHEDULERID', 6, false, $schedulerid));
                fwrite ($bf, full_tag('STARTTIME', 6, false, $slot->starttime));
                fwrite ($bf, full_tag('DURATION', 6, false, $slot->duration));
                fwrite ($bf, full_tag('TEACHERID', 6, false, $slot->teacherid));
                fwrite ($bf, full_tag('APPOINTMENTLOCATION', 6, false, $slot->appointmentlocation));
                fwrite ($bf, full_tag('REUSE', 6, false, $slot->reuse));
                fwrite ($bf, full_tag('TIMEMODIFIED', 6, false, $slot->timemodified));
                fwrite ($bf, full_tag('NOTES', 6, false, $slot->notes));
                fwrite ($bf, full_tag('EXCLUSIVITY', 6, false, $slot->exclusivity));
                fwrite ($bf, full_tag('APPOINTMENTNOTE', 6, false, $slot->appointmentnote));
                fwrite ($bf, full_tag('EMAILDATE', 6, false, $slot->emaildate));
                fwrite ($bf, full_tag('HIDEUNTIL', 6, false, $slot->hideuntil));
                //End slot
                $status = $status && fwrite ($bf, end_tag('SLOT', 5, true));
            }
            //Write end tag
            $status = $status && fwrite($bf, end_tag('SLOTS', 4, true));
        }
        return $status;
    }

    /**
    * Backup scheduler_slots appointments (executed from scheduler_backup_mods)
    */
    function backup_scheduler_appointments($bf, $preferences, $slots) {
        global $CFG;

        $status = true;

        //Write start tag
        $status = $status && fwrite ($bf, start_tag('APPOINTMENTS', 4, true));

        foreach($slots as $slot){
            $appointments = scheduler_get_appointments($slot->id);
            //If there is slots
            if ($appointments) {
                //Iterate over each slots
                foreach ($appointments as $appointment) {
                    //Start slot
                    $status = $status && fwrite ($bf, start_tag('APPOINTMENT', 5, true));
                    //Print appointment data
                    fwrite ($bf, full_tag('ID', 6, false, $appointment->id));
                    fwrite ($bf, full_tag('SLOTID', 6, false, $appointment->slotid)); 
                    fwrite ($bf, full_tag('STUDENTID', 6, false, $appointment->studentid)); 
                    fwrite ($bf, full_tag('ATTENDED', 6, false, $appointment->attended)); 
                    fwrite ($bf, full_tag('GRADE', 6, false, $appointment->grade));
                    fwrite ($bf, full_tag('APPOINTMENTNOTE', 6, false, $appointment->appointmentnote)); 
                    fwrite ($bf, full_tag('TIMECREATED', 6, false, $appointment->timecreated)); 
                    fwrite ($bf, full_tag('TIMEMODIFIED', 6, false, $appointment->timemodified)); 
                    //End appointment
                    $status = $status && fwrite ($bf, end_tag('APPOINTMENT', 5, true));
                }
            }
        }

        //Write end tag
        $status = $status && fwrite($bf, end_tag('APPOINTMENTS', 4, true));
        return $status;
    }
 
    /**
    * Return an array of info (name, value)
    */
   function scheduler_check_backup_mods($course, $user_data=false, $backup_unique_code) {
        //First the course data
        $info[0][0] = get_string('modulenameplural', 'scheduler');
        if ($ids = scheduler_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string('slots', 'scheduler');
            if ($ids = scheduler_slot_ids_by_course ($course)) {
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
            $info[2][0] = get_string('appointments', 'scheduler');
            if ($ids = scheduler_appointments_ids_by_course($course)) {
                $info[2][1] = count($ids);
            } else {
                $info[2][1] = 0;
            }
        }
        return $info;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    /**
    * Returns an array of schedulers id
    */
    function scheduler_ids ($course) {
        global $CFG;

        $sql = "
            SELECT 
                s.id, 
                s.course
            FROM 
                {$CFG->prefix}scheduler AS s
            WHERE 
                s.course = '{$course}'
        ";
        return get_records_sql ($sql);
    }
   
    /**
    * Returns an array of scheduler slots id
    */
    function scheduler_slot_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                sl.id , 
                sl.schedulerid
            FROM 
                {$CFG->prefix}scheduler_slots AS sl, 
                {$CFG->prefix}scheduler AS s
            WHERE 
                s.course = '{$course}' AND
                sl.schedulerid = s.id
        ";
        return get_records_sql($sql);
    }

    /**
    * Returns an array of scheduler appointments id
    */
    function scheduler_appointments_ids_by_course ($course) {
        global $CFG;

        $sql = "
            SELECT 
                a.id,
                sl.schedulerid
            FROM 
                {$CFG->prefix}scheduler_appointment AS a, 
                {$CFG->prefix}scheduler_slots AS sl, 
                {$CFG->prefix}scheduler AS s
            WHERE 
                s.course = '{$course}' AND
                sl.schedulerid = s.id AND
                sl.id = a.slotid
        ";
        return get_records_sql($sql);
    }
?>
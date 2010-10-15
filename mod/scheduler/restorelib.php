<?php //$Id: restorelib.php,v 1.2.10.5 2009/11/06 18:29:18 diml Exp $

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
    //                    (CL,pk->id)
    //                        |
    //                        |
    //                        |
    //                   scheduler_slots 
    //               (IL,pk->id, fk->schedulerid)     
    //                        |
    //                        |
    //                        |
    //                   scheduler_appointment
    //                (UL, pk->id, fk->slotid)
    //                    
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          IL->instance level info
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files
    //
    //-----------------------------------------------------------

    // Includes the lib to use scheduler_add_event(), that generate 
    // calendar entries on teachers and students. 
    // FIXME: should it be done this way? 
    require_once ("$CFG->dirroot/mod/scheduler/lib.php");
    require_once ("$CFG->dirroot/mod/scheduler/locallib.php");

    //This function executes all the restore procedure about this mod
    function scheduler_restore_mods($mod,$restore) {
        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //Now, build the SCHEDULER record structure
            $scheduler->course = $restore->course_id;
            $scheduler->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $scheduler->description = backup_todb($info['MOD']['#']['DESCRIPTION']['0']['#']);
            $scheduler->schedulermode = backup_todb($info['MOD']['#']['SCHEDULERMODE']['0']['#']);
            $scheduler->reuseguardtime = backup_todb($info['MOD']['#']['REUSEGUARDTIME']['0']['#']);
            $scheduler->defaultslotduration = backup_todb($info['MOD']['#']['DEFAULTSLOTDURATION']['0']['#']);
            $scheduler->staffrolename = backup_todb($info['MOD']['#']['STAFFROLENAME']['0']['#']);
            $scheduler->allownotifications = backup_todb($info['MOD']['#']['ALLOWNOTIFICATIONS']['0']['#']);
            $scheduler->teacher = backup_todb($info['MOD']['#']['TEACHER']['0']['#']);
            $scheduler->scale = backup_todb($info['MOD']['#']['SCALE']['0']['#']);
            $scheduler->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the teacher field
            $user = backup_getid($restore->backup_unique_code, 'user', $scheduler->teacher);
            if ($user) {
                $scheduler->teacher = $user->new_id;
            }

            //The structure is equal to the db, so insert the scheduler
            $newid = insert_record ('scheduler', $scheduler);

            //Do some output
            echo "<li>".get_string('modulename', 'scheduler')." \"".format_string(stripslashes($scheduler->name),true)."\"</li>";
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
                //Now check if want to restore user data and do it.
                if ($restore->mods['scheduler']->userinfo) {
                    //Restore scheduler_slots
                    $status = scheduler_slots_restore_mods ($mod->id, $newid, $info, $restore);
                    $status = scheduler_appointments_restore_mods ($mod->id, $newid, $info, $restore);

                    // FIXME: Shouldn't we check for conflicts?
                    // We have to add the events to the calendar as well
                    $slots = get_records('scheduler_slots', 'schedulerid', $newid);
                    $course = get_record('course', 'id', $restore->course_id);
                    foreach($slots as $slot){
                        scheduler_add_update_calendar_events($slot, $course);
                    }
                }
            } 
            else {
                $status = false;
            }
        } 
        else {
            $status = false;
        }
        return $status;
    }


    //This function restores the scheduler_slots
    function scheduler_slots_restore_mods($old_scheduler_id, $new_scheduler_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the slots array
        $slots = $info['MOD']['#']['SLOTS']['0']['#']['SLOT'];

        //Iterate over slots
        for($i = 0; $i < sizeof($slots); $i++) {
            $slot_info = $slots[$i];
            //traverse_xmlize($slot_info);                                                               //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($slot_info['#']['ID']['0']['#']);

            //Now, build the SCHEDULER_SLOTS record structure
            $slot->schedulerid = $new_scheduler_id;
            $slot->starttime = backup_todb($slot_info['#']['STARTTIME']['0']['#']);
            $slot->duration = backup_todb($slot_info['#']['DURATION']['0']['#']);
            $slot->teacherid = backup_todb($slot_info['#']['TEACHERID']['0']['#']);
            $slot->appointmentlocation = backup_todb($slot_info['#']['APPOINTMENTLOCATION']['0']['#']);
            $slot->reuse = backup_todb($slot_info['#']['REUSE']['0']['#']);
            $slot->timemodified = backup_todb($slot_info['#']['TIMEMODIFIED']['0']['#']);
            $slot->notes = backup_todb($slot_info['#']['NOTES']['0']['#']);
            $slot->exclusivity = backup_todb($slot_info['#']['EXCLUSIVITY']['0']['#']);
            $slot->appointmentnote = backup_todb($slot_info['#']['APPOINTMENTNOTE']['0']['#']);
            $slot->emaildate = backup_todb($slot_info['#']['EMAILDATE']['0']['#']);
            $slot->hideuntil = backup_todb($slot_info['#']['HIDEUNTIL']['0']['#']);

            //We have to recode the teacher field
            $user = backup_getid($restore->backup_unique_code,"user",$slot->teacherid);
            if ($user) {
                $slot->teacherid = $user->new_id;
            }

            //The structure is equal to the db, so insert the scheduler_slot
            $newid = insert_record ('scheduler_slots', $slot);

            // FIXME: Shouldn't we check for conflicts?
            // We have to add the events to the calendar as well
            $course = get_record('course', 'id', $restore->course_id);
            
            //restore slot id for events generation
            $slot->id = $newid;
            scheduler_add_update_calendar_events($slot, $course);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, 'scheduler_slots', $oldid,
                             $newid);
            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function restores the scheduler appointments
    function scheduler_appointments_restore_mods($old_scheduler_id, $new_scheduler_id, $info, $restore) {
        global $CFG;

        $status = true;

        //Get the slots array
        $appointments = $info['MOD']['#']['APPOINTMENTS']['0']['#']['APPOINTMENT'];

        //Iterate over slots
        for($i = 0; $i < sizeof($appointments); $i++) {
            $appointment_info = $appointments[$i];
            //traverse_xmlize($appointment_info);                                                         //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            if(empty($appointment_info)) {
                continue;
            }
            $oldid = backup_todb($appointment_info['#']['ID']['0']['#']);

            //Now, build the SCHEDULER_SLOTS record structure
            $appointment->slotid = backup_todb($appointment_info['#']['SLOTID']['0']['#']);
            $appointment->studentid = backup_todb($appointment_info['#']['STUDENTID']['0']['#']);
            $appointment->attended = backup_todb($appointment_info['#']['ATTENDED']['0']['#']);
            $appointment->grade = backup_todb($appointment_info['#']['GRADE']['0']['#']);
            $appointment->appointmentnote = backup_todb($appointment_info['#']['APPOINTMENTNOTE']['0']['#']);
            $appointment->timecreated = backup_todb($appointment_info['#']['TIMECREATED']['0']['#']);
            $appointment->timemodified = backup_todb($appointment_info['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the studentid field
            $user = backup_getid($restore->backup_unique_code, 'user', $appointment->studentid);
            if ($user) {
                $appointment->studentid = $user->new_id;
            }

            //We have to recode the slotid field
            $slot = backup_getid($restore->backup_unique_code, 'scheduler_slots', $appointment->slotid);
            if ($slot) {
                $appointment->slotid = $slot->new_id;
            }

            //The structure is equal to the db, so insert the scheduler appointment
            $newid = insert_record ('scheduler_appointment', $appointment);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br />";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, 'scheduler_appointment', $oldid,
                             $newid);
            } else {
                $status = false;
            }
        }

        return $status;
    }

?>
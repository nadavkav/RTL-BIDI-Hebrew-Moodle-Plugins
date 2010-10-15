<?php //$Id: backuplib.php,v 1.5 2008/09/01 20:09:12 mudrd8mz Exp $

/**
 * Backup library for Stamp collection (stampcoll) module
 *
 * The structure of the stampcoll mod:
 *
 *                                 stampcoll
 *                                (CL, pk->id)
 *                                     |
 *                                     |
 *                                     |
 *                              stampcoll_stamps
 *                         (UL,pk->id, fk->stampcoll)
 *
 *  Legend:  pk->primary key field of the table
 *           fk->foreign key to link with parent
 *           nt->nested field (recursive data)
 *           CL->course level info
 *           UL->user level info
 *           files->table may have files)
 *
 * @author David Mudrak
 * @package mod/stampcoll
 */

require_once(dirname(__FILE__).'/lib.php');

    /**
     * Backup all selected module instances
     *
     * Iterates thru all stamp collections; for each it checks if the one is 
     * included in the backup and calls stampcoll_backup_one_mod().
     *
     * @param resource $bf backup file resource
     * @param object $preferences Backup settings
     * @return boolean True if success, False if failure
     */
    function stampcoll_backup_mods($bf, $preferences) {
        $status = true;
        $stampcolls = get_records('stampcoll', 'course', $preferences->backup_course, 'id');
        foreach ($stampcolls as $stampcoll) {
            if (backup_mod_selected($preferences, 'stampcoll' ,$stampcoll->id)) {
                $status = stampcoll_backup_one_mod($bf, $preferences, $stampcoll);
            }
        }
        return $status;
    }

    /**
     * Write the info about the given module instance into the backup file
     *
     * @param resource $bf backup file resource
     * @param object $preferences Backup settings
     * @param object $stampcoll Stampcoll record (if object) or its ID (if numeric)
     * @return boolean True if success, False if failure
     */
    function stampcoll_backup_one_mod($bf, $preferences, $stampcoll) {
        if (is_numeric($stampcoll)) {
            $stampcoll = get_record('stampcoll', 'id', $stampcoll);
        }
        $status = true;
        $status = $status && fwrite($bf, start_tag('MOD', 3, true));

        $status = $status && fwrite($bf, full_tag('ID', 4, false, $stampcoll->id));
        $status = $status && fwrite($bf, full_tag('MODTYPE', 4, false, 'stampcoll'));
        $status = $status && fwrite($bf, full_tag('MODVERSION', 4, false, stampcoll_modversion()));
        $status = $status && fwrite($bf, full_tag('NAME', 4, false, $stampcoll->name));
        $status = $status && fwrite($bf, full_tag('TEXT', 4, false, $stampcoll->text));
        $status = $status && fwrite($bf, full_tag('FORMAT', 4, false, $stampcoll->format));
        $status = $status && fwrite($bf, full_tag('IMAGE', 4, false, $stampcoll->image));
        $status = $status && fwrite($bf, full_tag('TIMEMODIFIED', 4, false, $stampcoll->timemodified));
        $status = $status && fwrite($bf, full_tag('DISPLAYZERO', 4, false, $stampcoll->displayzero));
        $status = $status && fwrite($bf, full_tag('ANONYMOUS', 4, false, $stampcoll->anonymous));

        //If we've selected to backup users info, then backup collected stamps
        if (backup_userdata_selected($preferences, 'stampcoll', $stampcoll->id)) {
            $status = $status && stampcoll_backup_collected_stamps($bf, $preferences, $stampcoll->id);
        }
        //End module tag
        $status = $status && fwrite($bf,end_tag('MOD', 3, true));

        return $status;
    }

    /**
     * Backup stamps collected in the given stamp collection
     *
     * Is executed from stampcoll_backup_one_mod()
     *
     * @param resource $bf backup file resource
     * @param object $preferences Backup settings
     * @return boolean True if success, False if failure
     * @param int $stampcollid ID of the stampcoll record
     * @return boolean True if success, False if failure
     */
    function stampcoll_backup_collected_stamps($bf, $preferences, $stampcollid) {
        $status = true;
        $stamps = get_records('stampcoll_stamps', 'stampcollid', $stampcollid, 'id');
        if (is_array($stamps)) {
            $status = $status && fwrite($bf, start_tag('COLLECTEDSTAMPS', 4, true));
            foreach ($stamps as $stamp) {
                $status = $status && fwrite($bf, start_tag('STAMP', 5, true));

                $status = $status && fwrite($bf, full_tag('ID', 6, false, $stamp->id));
                $status = $status && fwrite($bf, full_tag('USERID', 6, false, $stamp->userid));
                $status = $status && fwrite($bf, full_tag('GIVER', 6, false, $stamp->giver));
                $status = $status && fwrite($bf, full_tag('TEXT', 6, false, $stamp->text));
                $status = $status && fwrite($bf, full_tag('TIMEMODIFIED', 6, false, $stamp->timemodified));
            
                $status = $status && fwrite($bf, end_tag('STAMP', 5, true));
            }
            $status = $status && fwrite($bf, end_tag('COLLECTEDSTAMPS', 4, true));
        }
        return $status;
    }
 
    /**
     * Return an array of info (name, value) to display at Backup Details page
     *
     * @param int $course Course ID
     * @param boolean $user_data Include user data in the backup
     * @param string $backup_unique_code
     * @param array $instances ???
     * @todo Documenting the function
     * @return array Two-dimensional array
     */
    function stampcoll_check_backup_mods($course, $user_data=false, $backup_unique_code, $instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += stampcoll_check_backup_mods_instances($instance, $backup_unique_code);
            }
            return $info;
        }

        //First the course data
        $info[0][0] = get_string('modulenameplural', 'stampcoll');
        if ($ids = stampcoll_ids_by_course($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string('numberofstamps', 'stampcoll');
            if ($ids = stampcoll_stamps_ids_by_course($course)) {
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
        }
        return $info;
    }

    /**
    * Return an array of info (name, value) to display at Backup Details page
    *
    * @param object $instance The module instance???
    * @param string $backup_unique_code
    * @return array Two-dimensional array
    *
    * @todo Documenting the function
    */
   function stampcoll_check_backup_mods_instances($instance, $backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<strong>'.$instance->name.'</strong>';   // XXX ugly HTML here
        $info[$instance->id.'0'][1] = '';

        //Now, if requested, the user_data
        if (!empty($instance->userdata)) {
            $info[$instance->id.'1'][0] = get_string('numberofstamps', 'stampcoll');
            if ($ids = stampcoll_stamps_ids_by_instance($instance->id)) {
                $info[$instance->id.'1'][1] = count($ids);
            } else {
                $info[$instance->id.'1'][1] = 0;
            }
        }
        return $info;
    }

    /**
     * INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE
     */

    /**
     * Returns an array of stampcoll ids in the given course
     *
     * @param int $course Course ID
     * @return array Array of records
     */
    function stampcoll_ids_by_course($course) {
        return get_records('stampcoll', 'course', $course, '', 'id');
    }
   
    /**
     * Returns an array of collected stamps ids in the given course
     *
     * @uses $CFG
     * @param int $course Course ID
     * @return array Array of records
     */
    function stampcoll_stamps_ids_by_course($course) {
        global $CFG;
        if (!is_numeric($course)) {
            return array();
        }
        return get_records_sql("SELECT stamp.id , stamp.stampcollid
                                 FROM {$CFG->prefix}stampcoll_stamps stamp,
                                      {$CFG->prefix}stampcoll
                                 WHERE stampcoll.course = $course 
                                   AND stamp.stampcollid = stampcoll.id");
    }

    /**
     * Returns an array of collected stamps ids in the given collection
     *
     * @param int $instanceid Stamp collection ID
     * @return array Array of records
     */
    function stampcoll_stamps_ids_by_instance($instanceid) {
        return get_records('stampcoll_stamps', 'stampcollid', $instanceid, '', 'id');
    }

?>

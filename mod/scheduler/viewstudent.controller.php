<?php

/**
* @package mod-scheduler
* @category mod
* @author Gustav Delius, Valery Fremaux > 1.8
*
* Controller for the viewstudent page.
*
* @usecase updatenote
* @usecase updategrades
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/scheduler
}

if ($subaction == 'updatenote' and (has_capability('mod/scheduler:manage', $context) or has_capability('mod/scheduler:manageallappointments', $context))){
    $app->id = required_param('appid', PARAM_INT);
    $distribute = optional_param('distribute', 0, PARAM_INT);
    
    if ($app->id){
        if ($distribute){
            echo "distributing";
            $slotid = get_field('scheduler_appointment', 'slotid', 'id', $app->id);
            $allapps = scheduler_get_appointments($slotid);
            foreach($allapps as $anapp){
                $anapp->appointmentnote = addslashes(required_param('appointmentnote', PARAM_CLEANHTML));
                $anapp->timemodified = time();
                update_record('scheduler_appointment', $anapp);
            }
        }
        else{
            $app->appointmentnote = addslashes(required_param('appointmentnote', PARAM_CLEANHTML));
            update_record('scheduler_appointment', $app);
        }
    }
}
/******************************* Update grades when concerned teacher ************************/
if ($subaction == 'updategrades' and (has_capability('mod/scheduler:manage', $context) or has_capability('mod/scheduler:manageallappointments', $context))){
    $keys = preg_grep("/^gr(.*)/", array_keys($_POST));
    foreach($keys as $key){
        preg_match("/^gr(.*)/", $key, $matches);
        $app->id = $matches[1];
        $app->grade = required_param($key, PARAM_INT);
        $app->timemodified = time();

        $distribute = optional_param('distribute'.$app->id, 0, PARAM_INT);
        if ($distribute){ // distribute to all members
            $slotid = get_field('scheduler_appointment', 'slotid', 'id', $app->id);
            $allapps = scheduler_get_appointments($slotid);
            foreach($allapps as $anapp){
                $anapp->grade = $app->grade;
                $anapp->timemodified = $app->timemodified;
                update_record('scheduler_appointment', $anapp);
            }
        }
        else{ // set to current members
            update_record('scheduler_appointment', $app);
        }
    }
}
?>
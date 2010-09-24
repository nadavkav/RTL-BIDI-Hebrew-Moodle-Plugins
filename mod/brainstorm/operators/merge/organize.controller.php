<?php

/**
* Module Brainstorm V2
* Operator : merge
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/
include_once ($CFG->dirroot."/mod/brainstorm/operators/{$page}/locallib.php");

/********************************** Assign a choice of responses to a slot ********************************/
if ($action == 'assign'){
    $current_target = optional_param('to', null, PARAM_INT);
    /// first discard old assignation - the fastest way to do it
    $select = "
        brainstormid = {$brainstorm->id} AND
        operatorid = 'merge' AND
        groupid = {$currentgroup} AND
        intvalue = {$current_target}
    ";
    if (!delete_records_select('brainstorm_operatordata', $select)){
        // IS NOT AN ERROR ; nothing previously assigned to delete
    }

    /// mark new assignation
    $checks = array_keys(merge_get_dataset_from_query('choose_'));
    $mergerecord->brainstormid = $brainstorm->id;
    $mergerecord->userid = $USER->id;
    $mergerecord->groupid = $currentgroup;
    $mergerecord->operatorid = 'merge';
    $mergerecord->timemodified = time();
    if (count($checks)){
        foreach($checks as $check){
            $mergerecord->itemsource = $check;
            $mergerecord->intvalue = $current_target;
            if (!insert_record('brainstorm_operatordata', $mergerecord)){
                error('Could not insert mere record');
            }
        }
        $nochecks = 1; // mark all checks out as they are not available any more in response list
    }    
}
/********************************** unassign the current target ********************************/
if ($action == 'unassign'){
    $unassigned = required_param('unassigned', PARAM_INT);
    /// first get old assignation to restore checks
    $select = "
        brainstormid = {$brainstorm->id} AND
        operatorid = 'merge' AND
        groupid = {$currentgroup} AND
        intvalue = {$unassigned}
    ";
    $oldassign = get_records_select('brainstorm_operatordata', $select, 'id,id');

    /// discard old assignation - the fastest way to do it    
    if (!delete_records_select('brainstorm_operatordata', $select)){
        // IS NOT AN ERROR ; nothing previously assigned to delete
    }

    /// restore checks
    if ($oldassign){
        foreach($oldassign as $assign){
            $checks[] = $assign->id;
        }
    }    
}
/********************************** saves the merged data ********************************/
if ($action == 'savemerges' || $action == 'saveandreduce'){
    $merges = merge_get_dataset_from_query('merge_');
    $choices = merge_get_dataset_from_query('choice_');
    $kept = array();
    $added = array();
    
    //print_object($merges);
    //print_object($choices);

    /// delete all merging data    
    $sql = "
        UPDATE
            {$CFG->prefix}brainstorm_operatordata
        SET
            blobvalue = NULL,
            itemdest = NULL
        WHERE
            brainstormid = {$brainstorm->id} AND
            operatorid = 'merge' AND
            groupid = {$currentgroup}
    ";
    execute_sql($sql, false);

    /// saving changes, updating when necessary
        
    foreach($choices as $key => $choice){
        unset($mergerecord);
        $mergerecord->brainstormid = $brainstorm->id;
        $mergerecord->userid = $USER->id;
        $mergerecord->groupid = $currentgroup;
        $mergerecord->operatorid = 'merge';
        $mergerecord->timemodified = time();
        $mergerecord->itemsource = $choice;
        $mergerecord->itemdest = $choice;
        $mergerecord->intvalue = $key;
        $mergerecord->blobvalue = $merges[$key];

        $select = "
            brainstormid = {$brainstorm->id} AND
            userid = {$USER->id} AND
            operatorid = 'merge' AND
            itemsource = {$choice} AND
            intvalue = {$key}     
        ";
        if ($oldid = get_field_select('brainstorm_operatordata', 'id', $select)){
            $mergerecord->id = $oldid;
            if ($choice){ // add the real records that should not be deleted when reducing
                $kept[] = $choice;
            }
            if (! update_record('brainstorm_operatordata', $mergerecord)){
                error("Could not update merge record");
            }
        }
        else{
            if (!$newid = insert_record('brainstorm_operatordata', $mergerecord)){
                error("Could not insert merge record");
            }
            else{
                $added[] = $newid;
            }
        }
    }
}
/********************************** Reduces entries ********************************/
if ($action == 'saveandreduce'){
    
    $groupClause = ($groupmode && $currentgroup) ? " AND groupid = $currentgroup " : '' ;
    
    /// adding new entries in responses
    $select = "
        brainstormid = $brainstorm->id AND
        operatorid = 'merge' AND
        itemsource = 0 AND
        itemdest = 0
        $groupClause
    ";
    $newassignations = get_records_select('brainstorm_operatordata', $select);
    if ($newassignations){
        /// get assignation records for addition

        foreach($newassignations as $assignation){
            // print_object($assignation);
            /// save a new response response with merged idea
            $responserecord->brainstormid = $brainstorm->id;
            $responserecord->userid = $assignation->userid;
            $responserecord->groupid = $assignation->groupid;
            $responserecord->response = $assignation->blobvalue;
            $responserecord->timemodified = time();
            // echo " adding response for ". $assignation->blobvalue."<br/>";
            if (!$newresponseid = insert_record('brainstorm_responses', $responserecord)){
                error("Could not insert added responses");
            }
            
            /// tag assignation to refer to the newly created response
            $assignation->itemsource = $newresponseid;
            $assignation->itemdest = $newresponseid;

            /// add to selected so they will not be deleted later
            $kept[] = $newresponseid;

            // echo " updating operatordata for ". $assignation->blobvalue."<br/>";
            if (!update_record('brainstorm_operatordata', $assignation)){
                error("Could not update assignation");
            }
            
        }
    }

    $nottodeletelist = implode("','", $kept);

    /// deleting all instances of my previous merges    
    $select = "
        brainstormid = $brainstorm->id AND
        operatorid = 'merge' AND
        userid = $USER->id
        $groupClause
    ";
    // echo " <br/>deleting operatordata with $select";
    if (!delete_records_select('brainstorm_operatordata', $select)){
        error("Could not delete operatordata records");
    }

    /// deleting in responses
    $select = "
        brainstormid = $brainstorm->id AND
        id NOT IN ('$nottodeletelist')
        $groupClause
    ";
    // echo "<br/>deleting responses with $select";
    if (!delete_records_select('brainstorm_responses', $select)){
        error("Could not delete responses records");
    }

    /// deleting in categorization
    $select = "
        brainstormid = $brainstorm->id AND
        responseid NOT IN ('$nottodeletelist')
        $groupClause
    ";
    // echo "<br/>deleting categories with $select";
    if (!delete_records_select('brainstorm_categorize', $select)){
        error("Could not delete categorization records");
    } 
           
}
?>
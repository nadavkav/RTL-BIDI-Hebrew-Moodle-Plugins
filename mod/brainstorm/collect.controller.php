<?php

include_once "{$CFG->dirroot}/lib/uploadlib.php";


/********************************  Asked for collecting form *******************************/
if ($action == 'docollect'){
    $form->response = required_param('response', PARAM_TEXT);
    
    if (empty($form->response)) {
        $error->message = get_string('emptyresponse', 'brainstorm');
        $error->on = 'response';
        $errors[] = $error;
    }
    else { // we have data from the form
        $newanswer->brainstormid = $brainstorm->id;
        $newanswer->userid = $USER->id;
        $newanswer->groupid = $currentgroup;
        $newanswer->timemodified = time();

        // responses now an array
        foreach ($form->response as $response){
            if ($response == ''){ // ignore unfilled response fields
                continue;
            }
            $newanswer->response = $response;
            if (! insert_record('brainstorm_responses', $newanswer)) {
                error("Could not save your brainstorm");
            }
        }
        add_to_log($course->id, 'brainstorm', 'submit', "view.php?id={$cm->id}", $brainstorm->id, $cm->id);
    }
}
/********************************  Asked for collecting form *******************************/
else if ($action == 'collect'){
    
    /// Allow users to enter their responses
    if (isguest()) {
        notice(get_string('guestscannotparticipate' , 'center'));    
        return -1;
    }
    include 'collect.html';
    return -1;
}
/********************************  Clear all ideas *******************************/
else if ($action == 'clearall'){
    $allusersclear = optional_param('allusersclear', 0, PARAM_INT);
    if ($allusersclear){
        delete_records('brainstorm_responses', 'brainstormid', $brainstorm->id);
        delete_records('brainstorm_operatordata', 'brainstormid', $brainstorm->id);
    }
    else{
        delete_records('brainstorm_responses', 'brainstormid', $brainstorm->id, 'userid', $USER->id);
        delete_records('brainstorm_operatordata', 'brainstormid', $brainstorm->id, 'userid', $USER->id);
    }
}
/********************************  Clear all ideas *******************************/
else if ($action == 'deleteitems'){
    $items = optional_param('items', array(), PARAM_INT);
    if (is_array($items)){
        $idlist = implode("','", $items);
        delete_records_select('brainstorm_responses', "id IN ('$idlist')");
        $select = "
            brainstormid = $brainstorm->id AND
            (itemsource IN ($idlist) OR
            itemdest IN ($idlist))
        ";
        delete_records_select('brainstorm_operatordata', $select);
    }
}
/********************************  Asked for importing form *******************************/
else if ($action == 'import'){
    /// Allow users to enter their responses
    if (!has_capability('mod/brainstorm:import', $context)){
        error("This user cannot import data");
        return -1;
    }
    include 'import.html';
    return -1;
}
/********************************  perform import *******************************/
else if ($action == 'doimport'){
    $clearalldata = optional_param('clearall', 0, PARAM_INT);
    $allusersclear = optional_param('allusersclear', 0, PARAM_INT);
    
    $uploader = new upload_manager('inputs', false, false, $course->id, true, 0, true);
    if ($uploader->preprocess_files()){
        $content = file($uploader->files['inputs']['tmp_name']);
        $ideas = preg_grep("/^[^!\/#].*$/", $content);
        if ($clearalldata){
            if ($allusersclear){
                delete_records('brainstorm_responses', 'brainstormid', $brainstorm->id);
                delete_records('brainstorm_operatordata', 'brainstormid', $brainstorm->id);
            }
            else{
                delete_records('brainstorm_responses', 'brainstormid', $brainstorm->id, 'userid', $USER->id);
                delete_records('brainstorm_operatordata', 'brainstormid', $brainstorm->id, 'userid', $USER->id);
            }
        }
        $response->brainstormid = $brainstorm->id;
        $response->userid = $USER->id;
        $response->groupid = $currentgroup;
        $response->timemodified = time();
        foreach($ideas as $idea){
            $response->response = mb_convert_encoding($idea, 'UTF-8', 'auto');
            if (! insert_record('brainstorm_responses', $response)) {
                error("Could not save your brainstorm");
            }
        }
    }
}

?>
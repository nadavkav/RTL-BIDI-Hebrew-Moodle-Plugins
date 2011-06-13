<?php

require_once($CFG->dirroot.'/mod/quiz/lib.php');

function quizcopy_add_instance(&$quizcopy) {

    $cmid = $quizcopy->quiz;
    $name = $quizcopy->name;
    $cm = get_coursemodule_from_id('quiz', $cmid);

    if (!$quiz = get_record('quiz', 'id', $cm->instance)) {
        print_error('error','quizcopy');
    }
    $origquiz = $quiz->id;
    unset($quiz->id);
    // remove single quote to prevent SQL errors (nadavkav 11-6-11)
    $quiz->name = str_replace("'",'',$name );
    $quiz->intro = str_replace("'",'',$quiz->intro );

    if (!$quiz->id = insert_record('quiz', $quiz)) {
        print_error('error','quizcopy');
    }

    $newquestions = array();
    if (!is_null($quiz->questions) && !empty($quiz->questions)) {
        $questions = explode(',', $quiz->questions);
        foreach ($questions as $question) {
            $newquestions[] = $question;
            if ($question != 0) {
                if (!$instance_object = get_record('quiz_question_instances', 'quiz', $origquiz, 'question', $question)) {
                    print_error('error','quizcopy');
                }
                unset($instance_object->id);
                $instance_object->quiz = $quiz->id;
                if (!insert_record('quiz_question_instances', $instance_object)) {
                    print_error('error','quizcopy');
                }
            }
        }
    }
    if ($feedbacks = get_records('quiz_feedback', 'quizid', $origquiz, 'maxgrade DESC')) {
        $i = 0;
        $quiz->feedbacktext = array();
        $quiz->feedbackboundaries = array();
        foreach ($feedbacks as $feedback) {
            $quiz->feedbacktext[$i] = $feedback->feedbacktext;
            $quiz->feedbackboundaries[$i] = $feedback->mingrade;
            $quiz->feedbackboundaries[$i - 1] = $feedback->maxgrade;
            $i++;
        }
        $quiz->feedbackboundarycount = $i-1;
    }
    $quiz->questions = implode(',', $newquestions);
    if (!update_record('quiz', $quiz)) {
        print_error('error','quizcopy');
    }
    $quizcopy->timecreated = time();
    $quizcopy->module = $cm->module;
    $quizcopy->modulename = 'quiz';
    $quizcopy->visible = $cm->visible;
    $quizcopy->groupmode = $cm->groupmode;
    $quizcopy->groupmembersonly = $cm->groupmembersonly;
    $quizcopy->module = $cm->module;

    quiz_after_add_or_update($quiz);

    return $quiz->id;
}

function quizcopy_update_instance($quizcopy) {
    return false;
}
function quizcopy_delete_instance($id) {
    return false;
}

function quizcopy_user_outline($course, $user, $mod, $quizcopy) {
    return true;
}
function quizcopy_user_complete($course, $user, $mod, $quizcopy) {
    return true;
}
function quizcopy_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}
function quizcopy_cron () {
    return true;
}
function quizcopy_get_participants($quizcopyid) {
    return false;
}
function quizcopy_scale_used($quizcopyid, $scaleid) {
    return false;
}
function quizcopy_scale_used_anywhere($scaleid) {
    return false;
}
function quizcopy_install() {
    return true;
}
function quizcopy_uninstall() {
    return true;
}

?>

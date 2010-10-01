<?php  // $Id: view.php,v 1.6.2.3 2009/04/17 22:06:25 skodak Exp $

/**
 * This page prints a particular instance of hotquestion
 *
 * @author  Your Name <your@email.address>
 * @version $Id: view.php,v 1.6.2.3 2009/04/17 22:06:25 skodak Exp $
 * @package mod/hotquestion
 */

require_once("../../config.php");
require_once($CFG->dirroot . '/mod/hotquestion/mod_form.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$h  = optional_param('h', 0, PARAM_INT);  // hotquestion instance ID

if ($id) {
    if (! $cm = get_coursemodule_from_id('hotquestion', $id)) {
        error('Course Module ID was incorrect');
    }

    if (! $course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }

    if (! $hotquestion = get_record('hotquestion', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }

} else if ($h) {
    if (! $hotquestion = get_record('hotquestion', 'id', $h)) {
        error('Course module is incorrect');
    }
    if (! $course = get_record('course', 'id', $hotquestion->course)) {
        error('Course is misconfigured');
    }
    if (! $cm = get_coursemodule_from_instance('hotquestion', $hotquestion->id, $course->id)) {
        error('Course Module ID was incorrect');
    }

} else {
    error('You must specify a course_module ID or an instance ID');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/hotquestion:view', $context);

/// Print the page header
$strhotquestions = get_string('modulenameplural', 'hotquestion');
$strhotquestion  = get_string('modulename', 'hotquestion');

$navlinks = array();
$navlinks[] = array('name' => $strhotquestions, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($hotquestion->name), 'link' => '', 'type' => 'activityinstance');

$navigation = build_navigation($navlinks);

print_header_simple(format_string($hotquestion->name), '', $navigation, '', '', true,
              update_module_button($cm->id, $course->id, $strhotquestion), navmenu($course, $cm));


if(has_capability('mod/hotquestion:ask', $context)){
    $mform = new hotquestion_form($hotquestion->anonymouspost);

    if ($fromform=$mform->get_data()){

        $data->hotquestion = $hotquestion->id;
        $data->content = trim($fromform->question);
        $data->userid = $USER->id;
        $data->time = time();
        if (isset($fromform->anonymous) && $hotquestion->anonymouspost)
            $data->anonymous = $fromform->anonymous;

        if (!empty($data->content)){
            if(!($questionid = insert_record('hotquestion_questions', $data))){
                error("error in inserting questions!");
            }
        } else {
            redirect('view.php?id='.$cm->id, get_string('invalidquestion', 'hotquestion'));
        }

        add_to_log($course->id, 'hotquestion', 'add question', "view.php?id=$cm->id", $hotquestion->id, $data->content);

        // Redirect to show questions. So that the page can be refreshed
        redirect('view.php?id='.$cm->id, get_string('questionsubmitted', 'hotquestion'));
    }
}

//handle the new votes
$action  = optional_param('action', '', PARAM_ACTION);  // Vote or unvote
if (!empty($action)) {
    switch ($action) {

    case 'vote':
    case 'unvote':
        require_capability('mod/hotquestion:vote', $context);
        $q  = required_param('q', PARAM_INT);  // question ID to vote
        $question = get_record('hotquestion_questions', 'id', $q);
        if ($question && $USER->id != $question->userid) {
            add_to_log($course->id, 'hotquestion', 'vote', "view.php?id=$cm->id", $hotquestion->id);

            if ($action == 'vote') {
                if (!has_voted($q)){
                    $votes->question = $q;
                    $votes->voter = $USER->id;

                    if(!insert_record('hotquestion_votes', $votes)){
                        error("error in inserting the votes!");
                    }
                }
            } else {
                if (has_voted($q)){
                    delete_records('hotquestion_votes', 'question', $q, 'voter', $USER->id);
                }
            }
        }
        break;

    case 'newround':
        // Close the latest round
        $old = array_pop(get_records('hotquestion_rounds', 'hotquestion', $hotquestion->id, 'id DESC', '*', '', 1));
        $old->endtime = time();
        update_record('hotquestion_rounds', $old);
        // Open a new round
        $new->hotquestion = $hotquestion->id;
        $new->starttime = time();
        $new->endtime = 0;
        insert_record('hotquestion_rounds', $new);
    }
}

/// Print the main part of the page


// Print hotquestion description
if (trim($hotquestion->intro)) {
   $formatoptions->noclean = true;
   $formatoptions->para    = false;
   print_box(format_text($hotquestion->intro, FORMAT_MOODLE, $formatoptions), 'generalbox', 'intro');
}


// Ask form
if(has_capability('mod/hotquestion:ask', $context)){
    $mform->display();
}


add_to_log($course->id, "hotquestion", "view", "view.php?id=$cm->id", "$hotquestion->id");

// Look for round
$rounds = get_records('hotquestion_rounds', 'hotquestion', $hotquestion->id, 'id ASC');
$roundid  = optional_param('round', -1, PARAM_INT);

$ids = array_keys($rounds);
if ($roundid != -1 && array_key_exists($roundid, $rounds)) {
    $current_round = $rounds[$roundid];
    $current_key = array_search($roundid, $ids);
    if (array_key_exists($current_key-1, $ids)) {
        $prev_round = $rounds[$ids[$current_key-1]];
    }
    if (array_key_exists($current_key+1, $ids)) {
        $next_round = $rounds[$ids[$current_key+1]];
    }

    $roundnum = $current_key+1;
} else {
    // Use the last round
    $current_round = array_pop($rounds);
    $prev_round = array_pop($rounds);
    $roundnum = array_search($current_round->id, $ids) + 1;
}

// Print round toolbar
echo '<div id="toolbar">';
if (!empty($prev_round))
    echo '<a href="view.php?id='.$cm->id.'&round='.$prev_round->id.'">('.get_string('previous').')</a> ';
print_string('round', 'hotquestion', $roundnum);
/*echo ': ';
echo userdate($current_round->starttime).' - ';
if ($current_round->endtime) {
    echo userdate($current_round->endtime);
} else {
    echo '???';
}*/
if (!empty($next_round))
    echo ' <a href="view.php?id='.$cm->id.'&round='.$next_round->id.'">('.get_string('next').')</a>';
if (has_capability('mod/hotquestion:manage', $context)) {
    $options = array();
    $options['id'] = $cm->id;
    $options['action'] = 'newround';
    print_single_button('view.php', $options, get_string('newround', 'hotquestion'), 'get', '_self', false, '', false, get_string('newroundconfirm', 'hotquestion'));
}
echo '</div>';

// Questions list
if ($current_round->endtime == 0)
    $current_round->endtime = 0xFFFFFFFF;  //Hack
$questions = get_records_sql("SELECT q.*, count(v.voter) as count
                              FROM {$CFG->prefix}hotquestion_questions q
                              LEFT JOIN {$CFG->prefix}hotquestion_votes v
                              ON v.question = q.id
                              WHERE q.hotquestion = $hotquestion->id
                                    AND q.time >= {$current_round->starttime}
                                    AND q.time <= {$current_round->endtime}
                              GROUP BY q.id
                              ORDER BY count DESC, q.time DESC");

if($questions){

    $table->cellpadding = 10;
    $table->class = 'generaltable';
    $table->align = array ('left', 'center');
    $table->size = array('', '1%');

    $table->head = array(get_string('question', 'hotquestion'), get_string('heat', 'hotquestion'));

    foreach ($questions as $question) {
        $line = array();

        $formatoptions->para  = false;
        $content = format_text($question->content, FORMAT_MOODLE, $formatoptions);
        
        $user = get_record('user', 'id', $question->userid);
        if ($question->anonymous) {
            $a->user = get_string('anonymous', 'hotquestion');
        } else {
            $a->user = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&amp;course=' . $course->id . '">' . fullname($user) . '</a>';
        }
        $a->time = userdate($question->time).'&nbsp('.get_string('early', 'assignment', format_time(time() - $question->time)) . ')';
        $info = '<div class="author">'.get_string('authorinfo', 'hotquestion', $a).'</div>';

        $line[] = $content.$info;
        
        $heat = $question->count;
        if (has_capability('mod/hotquestion:vote', $context) && $question->userid != $USER->id){
            if (!has_voted($question->id)){
                $heat .= '&nbsp;<a href="view.php?id='.$cm->id.'&action=vote&q='.$question->id.'"><img src="'.$CFG->pixpath.'/s/yes.gif" title="'.get_string('vote', 'hotquestion') .'" alt="'. get_string('vote', 'hotquestion') .'"/></a>';
            } else {
                /* temply disable unvote to see effect
                $heat .= '&nbsp;<a href="view.php?id='.$cm->id.'&action=unvote&q='.$question->id.'"><img src="'.$CFG->pixpath.'/s/no.gif" title="'.get_string('unvote', 'hotquestion') .'" alt="'. get_string('unvote', 'hotquestion') .'"/></a>';
                 */
            }
        }

        $line[] = $heat;

        $table->data[] = $line;
    }//for

    print_table($table);

}else{
    print_simple_box(get_string('noquestions', 'hotquestion'), 'center', '70%');
}


/// Finish the page
print_footer($course);

//return whether the user has voted on question
function has_voted($question, $user = -1) {
    global $USER;

    if ($user == -1)
        $user = $USER->id;

    return record_exists('hotquestion_votes', 'question', $question, 'voter', $user);
}

?>

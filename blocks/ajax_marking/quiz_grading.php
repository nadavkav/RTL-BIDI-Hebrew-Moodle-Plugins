<?php

require_login(0, false);

class quiz_functions extends module_base {

    function quiz_functions(&$reference) {
        $this->mainobject = $reference;
        // must be the same as the DB modulename
        $this->type = 'quiz';
        $this->capability = 'mod/quiz:grade';
        $this->levels = 4;
        $this->icon = 'mod/quiz/icon.gif';
        $this->functions  = array(
            'quiz' => 'quiz_questions',
            'quiz_question' => 'submissions'
        );
    }


     /**
      * gets all unmarked quiz question from all courses. used for the courses count
      *
      */
     function get_all_unmarked() {
        global $CFG;
        // 
        $sql = "SELECT qst.id as qstid, qa.userid, qsess.questionid, qz.id,
                       qz.name, qz.course, c.id as cmid
                  FROM {$CFG->prefix}quiz qz
            INNER JOIN {$CFG->prefix}course_modules c
                    ON qz.id = c.instance
            INNER JOIN {$CFG->prefix}quiz_attempts qa
                    ON qz.id = qa.quiz
            INNER JOIN {$CFG->prefix}question_sessions qsess
                    ON qsess.attemptid = qa.uniqueid
            INNER JOIN {$CFG->prefix}question_states qst
                    ON qsess.newest = qst.id
            INNER JOIN {$CFG->prefix}question q
                    ON qsess.questionid = q.id
                 WHERE qa.timefinish > 0
                   AND qa.preview = 0
                   AND c.module = {$this->mainobject->modulesettings['quiz']->id}
                   AND c.visible = 1
                   AND q.qtype = 'essay'
                   AND qz.course IN ({$this->mainobject->course_ids})
                   AND qst.event NOT IN (3,6,9)
              ORDER BY qa.timemodified";

            $this->all_submissions = get_records_sql($sql);
            return true;
    }

    function get_all_course_unmarked($courseid) {

        global $CFG;

        $student_sql = $this->get_role_users_sql($this->mainobject->courses[$courseid]->context);
        
        $sql = "SELECT qsess.id as qsessid, qa.userid, qz.id, qz.course,
                       qz.intro as description, qz.name, c.id as cmid
                  FROM {$CFG->prefix}quiz qz
            INNER JOIN {$CFG->prefix}course_modules c
                    ON qz.id = c.instance
            INNER JOIN {$CFG->prefix}quiz_attempts qa
                    ON qz.id = qa.quiz
            INNER JOIN {$CFG->prefix}question_sessions qsess
                    ON qsess.attemptid = qa.uniqueid
            INNER JOIN {$CFG->prefix}question_states qst
                    ON qsess.newest = qst.id
            INNER JOIN {$CFG->prefix}question q
                    ON qsess.questionid = q.id
           
                 WHERE qa.timefinish > 0
                   AND qa.preview = 0
                   AND c.module = {$this->mainobject->modulesettings['quiz']->id}
                   AND c.visible = 1
                   AND qz.course = {$courseid}
                   AND (qa.userid IN ({$student_sql}))
                   AND q.qtype = 'essay'
                   AND qst.event NOT IN (3,6,9)
                 ORDER BY qa.timemodified";

            $submissions = get_records_sql($sql);
            return $submissions;
    }

    /**
     * Gets all of the question attempts for the current quiz. Uses the group
     * filtering function to display groups first if that has been specified via
     * config. Seemed like a better idea than questions then groups as tutors
     * will mostly have a class to mark rather than a question to mark.
     *
     * Uses $this->id as the quiz id
     * @global <type> $CFG
     * @return <type>
     */
    function quiz_questions() {

        $quiz = get_record('quiz', 'id', $this->mainobject->id);
        $courseid = $quiz->course;

        $this->mainobject->get_course_students($quiz->course);

        global $CFG, $USER;

        //permission to grade?
        //$module = get_record('modules','name',$this->type);
        $coursemodule = get_record('course_modules', 'course', $quiz->course, 'module', $this->mainobject->modulesettings['quiz']->id, 'instance', $quiz->id) ;
        $modulecontext = get_context_instance(CONTEXT_MODULE, $coursemodule->id);
        if (!has_capability($this->capability, $modulecontext, $USER->id)) {
            return;
        }

        $csv_sql = "SELECT questions
                      FROM {$CFG->prefix}quiz
                     WHERE id = {$this->mainobject->id}";
        $csv_questions = get_record_sql($csv_sql);

        $student_sql = $this->get_role_users_sql($this->mainobject->courses[$courseid]->context);
        
        $sql = "SELECT qst.id as qstid, qa.userid, qst.event, qs.questionid as id, q.name,
                       q.questiontext as description, q.qtype, qa.timemodified
                  FROM {$CFG->prefix}question_states qst
            INNER JOIN {$CFG->prefix}question_sessions qs
                    ON qs.newest = qst.id
            INNER JOIN {$CFG->prefix}question q
                    ON qs.questionid = q.id
            INNER JOIN {$CFG->prefix}quiz_attempts qa
                    ON qs.attemptid = qa.uniqueid
           
                 WHERE qa.quiz = $quiz->id
                   AND qa.timefinish > 0
                   AND qa.preview = 0
                   AND (qa.userid IN ({$student_sql}))
                   AND qs.questionid IN ($csv_questions->questions)
                   AND q.qtype = 'essay'
                   AND qst.event NOT IN (3,6,9)
              ORDER BY qa.timemodified";

        $question_attempts = get_records_sql($sql);

        // not the same as $csv_questions as some of those questions will have no attempts
        // needing attention
        $questions = $this->mainobject->list_assessment_ids($question_attempts);

        if (!$this->mainobject->group) {

            $group_check = $this->mainobject->try_to_make_group_nodes($question_attempts, 'quiz', $this->mainobject->id, $quiz->course);
            
            if (!$group_check) {
                return;
            }
        }

        // begin json object.   Why course?? Children treatment?
        $this->mainobject->output = '[{"type":"quiz_question"}';      

        foreach ($questions as $question) {

            $count = 0;

            foreach ($question_attempts as $question_attempt) {
                if (!isset($question_attempt->userid)) {continue;}
                // if we have come from a group node, ignore attempts where the user is not in the
                // right group. Also ignore attempts not relevant to this question
                $groupnode     = $this->mainobject->group;
                //echo 'q: '.$this->mainobject->group.' qu: '.$question_attempt->userid;
                $inrightgroup  = $this->mainobject->check_group_membership($this->mainobject->group, $question_attempt->userid);
                $rightquestion = ($question_attempt->id == $question->id);
                //echo $groupnode.' '.$inrightgroup.' '.$rightquestion;
                if (($groupnode && !$inrightgroup) || ! $rightquestion) {
                    //continue;
                }
                $count = $count + 1;
            }

            if ($count > 0) {
                $name = $question->name;
                $questionid = $question->id;
                $sum = $question->description;
                $sumlength = strlen($sum);
                $shortsum = substr($sum, 0, 100);
                if (strlen($shortsum) < strlen($sum)) {
                    $shortsum .= "...";
                }
                $length = 30;
                $this->mainobject->output .= ',';

                $this->mainobject->output .= '{';
                $this->mainobject->output .= '"label":"'.$this->mainobject->add_icon('question');
                $this->mainobject->output .=     '(<span class=\"AMB_count\">'.$count.'</span>) ';
                $this->mainobject->output .=     $this->mainobject->clean_name_text($name, $length).'",';
                $this->mainobject->output .= '"name":"'.$this->mainobject->clean_name_text($name, $length).'",';
                $this->mainobject->output .= '"id":"'.$questionid.'",';
                $this->mainobject->output .= '"icon":"'.$this->mainobject->add_icon('question').'",';

                $this->mainobject->output .= $this->mainobject->group ? '"group":"'.$this->mainobject->group.'",' : '';
              
                $this->mainobject->output .= '"assid":"qq'.$questionid.'",';
                $this->mainobject->output .= '"type":"quiz_question",';
                $this->mainobject->output .= '"summary":"'.$this->mainobject->clean_summary_text($shortsum).'",';
                $this->mainobject->output .= '"count":"'.$count.'",';
                $this->mainobject->output .= '"uniqueid":"quiz_question'.$questionid.'",';
                $this->mainobject->output .= '"dynamic":"true"';
                $this->mainobject->output .= '}';
            }
        }
        // end JSON array
        $this->mainobject->output .= "]"; 
    }


    /**
     * Makes the nodes with the student names for each question. works either with or without a group having been set.
     * @global <type> $CFG
     * @return <type>
     */
    function submissions() {

        global $CFG, $USER;
        
        $quiz = get_record('quiz', 'id', $this->mainobject->secondary_id);
        $courseid = $quiz->course;

        //permission to grade?
        $coursemodule = get_record('course_modules', 'course', $quiz->course, 'module', $this->mainobject->modulesettings['quiz']->id, 'instance', $quiz->id) ;
        $modulecontext = get_context_instance(CONTEXT_MODULE, $coursemodule->id);
        
        if (!has_capability($this->capability, $modulecontext, $USER->id)) {
            return;
        }

        $student_sql = $this->get_role_users_sql($this->mainobject->courses[$courseid]->context);

        $this->mainobject->get_course_students($quiz->course);

        $sql = "SELECT COUNT(qst.id) AS count, qa.userid, qs.questionid, MIN(qst.timestamp) as timestamp
                  FROM {$CFG->prefix}question_states qst
            INNER JOIN {$CFG->prefix}question_sessions qs
                    ON qs.newest = qst.id
            INNER JOIN {$CFG->prefix}quiz_attempts qa
                    ON qs.attemptid = qa.uniqueid
          
                 WHERE qa.quiz = {$this->mainobject->secondary_id}
                   AND qa.timefinish > 0
                   AND qa.preview = 0
                   AND (qa.userid IN ({$student_sql}))
                   AND qs.questionid = {$this->mainobject->id}
                   AND qst.event NOT IN (3,6,9)
              GROUP BY qa.userid, qs.questionid
              ORDER BY timestamp ASC";

        $question_attempts = get_records_sql($sql);

        if($question_attempts) {

            $this->mainobject->output = '[{"type":"submissions"}';

            foreach ($question_attempts as $question_attempt) {
                if (!isset($question_attempt->userid)) {
                    continue;
                }
                // If this is a group node, ignore those where the student is not in the right group
                $groupnode = $this->mainobject->group &&
                $inrightgroup = $this->mainobject->check_group_membership($this->mainobject->group, $question_attempt->userid);
                if ($groupnode && !$inrightgroup) {
                     continue;
                }

                $name = $this->mainobject->get_fullname($question_attempt->userid);
                // Sometimes, a person will have more than 1 attempt for the question.
                // No need to list them twice, so we add a count after their name.
                if ($question_attempt->count > 1) {
                    $name .=' ('.$question_attempt->count.')';
                }

                $now = time();
                $seconds = ($now - $question_attempt->timestamp);
                $summary = $this->mainobject->make_time_summary($seconds);

                $this->output .= $this->mainobject->make_submission_node($name,
                                                                         $question_attempt->userid,
                                                                         $this->mainobject->id,
                                                                         $summary, 
                                                                         'quiz_final',
                                                                         $seconds,
                                                                         $question_attempt->timestamp,
                                                                         $question_attempt->count);

            }
            $this->mainobject->output .= "]"; 
        }
    }

     /**
     * gets all the quizzes for the config screen. still need the check in there for essay questions.
     * @global <type> $CFG
     * @return <type>
     */
     function get_all_gradable_items() {

         global $CFG;

         $sql = "SELECT qz.id, qz.course, qz.intro as summary, qz.name, c.id as cmid
                   FROM {$CFG->prefix}quiz qz
             INNER JOIN {$CFG->prefix}course_modules c
                     ON qz.id = c.instance
             INNER JOIN {$CFG->prefix}quiz_question_instances qqi
                     ON qz.id = qqi.quiz
             INNER JOIN {$CFG->prefix}question q
                     ON qqi.question = q.id
                  WHERE c.module = {$this->mainobject->modulesettings['quiz']->id}
                    AND c.visible = 1
                    AND q.qtype = 'essay'
                    AND qz.course IN ({$this->mainobject->course_ids})
               ORDER BY qz.id";

        $quizzes = get_records_sql($sql);
        $this->assessments = $quizzes;

    }


    function make_html_link($item) {

        global $CFG;
        $address = $CFG->wwwroot.'/mod/quiz/report.php?q='.$item->id.'&mode=grading';
        return $address;
    }

}
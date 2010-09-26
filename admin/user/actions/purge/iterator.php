<?php //$Id: iterator.php,v 1.1 2009/03/10 10:01:57 argentum Exp $
/**
* user bulk action script for batch deleting user activity and full unenroling
*/

require_once('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');
require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->dirroot.'/mod/lesson/lib.php');
require_once($CFG->dirroot.'/mod/assignment/lib.php');

$confirm    = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('userbulk');
check_action_capabilities('purge', true);

$return = $CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk.php';
$langdir = $CFG->dirroot.'/admin/user/actions/purge/lang/';
$pluginname = 'bulkuseractions_purge';

if ($confirm) {
    $SESSION->purge_progress = $SESSION->bulk_users;
}

function is_timeout_close($start)
{
    return (time() - $start >= ini_get('max_execution_time') * 0.8);
}

function iterate_purge($starttime)
{
    global $SESSION, $CFG;
    
    $userid = current($SESSION->purge_progress);
    $incourses = implode(',', $SESSION->bulk_courses);
    
    // delete all quiz activity
    $quizzessql = "SELECT DISTINCT q.* FROM {$CFG->prefix}quiz q INNER JOIN {$CFG->prefix}quiz_attempts a
                    ON a.quiz=q.id AND a.userid=$userid AND q.course IN ($incourses)";
    if ($quizzes = get_records_sql($quizzessql)) {
        foreach ($quizzes as $quiz) {
            $attemptssql = "SELECT a.* FROM {$CFG->prefix}quiz_attempts a
            				WHERE a.quiz=$quiz->id AND a.userid=$userid";
            $attempts = get_records_sql($attemptssql);
            foreach ($attempts as $attempt) {
                quiz_delete_attempt( $attempt, $quiz );
            }
        }
    }

    if (is_timeout_close($starttime)) {
        return false;
    }

    // delete all lesson activity
    $lessons = get_fieldset_select('lesson', 'id', "course IN ($incourses)");
    if (!empty($lessons)) {
        $lessons = implode(',', $lessons);
        
        /// Clean up the timer table
        delete_records_select('lesson_timer', "userid=$userid AND lessonid IN ($lessons)");
    
        /// Remove the grades from the grades and high_scores tables
        delete_records_select('lesson_grades', "userid=$userid AND lessonid IN ($lessons)");
        delete_records_select('lesson_high_scores', "userid=$userid AND lessonid IN ($lessons)");
    
        /// Remove attempts
        delete_records_select('lesson_attempts', "userid=$userid AND lessonid IN ($lessons)");
    
        /// Remove seen branches  
        delete_records_select('lesson_branch', "userid=$userid AND lessonid IN ($lessons)");
    }

    if (is_timeout_close($starttime)) {
        return false;
    }
        
    // delete all assignment submissions
    $assignmentlist = array();
    // delete submission files
    $assignmentssql = "SELECT DISTINCT a.id, a.course FROM {$CFG->prefix}assignment a INNER JOIN {$CFG->prefix}assignment_submissions s
                       ON s.assignment=a.id AND s.userid=$userid AND a.course IN ($incourses)";
    if ($assignments = get_records_sql($assignmentssql)) {
        foreach ($assignments as $assignment) {
            fulldelete($CFG->dataroot.'/'.$assignment->course.'/moddata/assignment/'.$assignment->id.'/'.$userid);
            $assignmentlist[] = $assignment->id;
        }
    }

    // delete submission records
    if (!empty($assignmentlist)) {
        $assignmentlist = implode(',', $assignmentlist);
        delete_records_select('assignment_submissions', "userid=$userid AND assignment IN ($assignmentlist)");
    }

    if (is_timeout_close($starttime)) {
        return false;
    }
    
    // finally, delete all grade records to clean up database
    $sql = "SELECT g.id 
            FROM {$CFG->prefix}grade_grades g INNER JOIN {$CFG->prefix}grade_items i
            ON g.itemid = i.id AND i.courseid IN ($incourses) AND g.userid=$userid";
    $grades = get_fieldset_sql($sql);
    if (!empty($grades)) {
        $grades = implode(',', $grades);
        delete_records_select('grade_grades', "id IN ($grades)");
    }
    
    // unenrol selected users from all courses
    foreach ($SESSION->bulk_courses as $course) {
        $context = get_context_instance(CONTEXT_COURSE, $course);
        role_unassign(0, $userid, 0, $context->id);
    }
    
    array_shift($SESSION->purge_progress);

    if (is_timeout_close($starttime)) {
        return false;
    }
    
    return true;
}

$start = time();
admin_externalpage_print_header();

flush();
$left = count($SESSION->purge_progress);
while($left) {
    $result = iterate_purge($start);
    $left = count($SESSION->purge_progress);
    $all = count($SESSION->bulk_users);
    $counter = ($all-$left).' '.get_string('outof', $pluginname, NULL, $langdir);
    $counter .= ' '.$all.' '.get_string('processed', $pluginname, NULL, $langdir);
    echo('<div align=center>'.$counter.'</div><br/>');
    if ($result === false ) {
        redirect('iterator.php', '', 0.5);
        break;
    }
    flush();
    if($left == 0) {
        unset($SESSION->purge_progress);
        redirect($return, get_string('changessaved'));
    }
}

admin_externalpage_print_footer();

?>
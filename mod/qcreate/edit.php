<?php // $Id: edit.php,v 1.16 2008/11/12 08:23:00 jamiesensei Exp $
/**
* Page to grade questions
*
*
* @version $Id: edit.php,v 1.16 2008/11/12 08:23:00 jamiesensei Exp $
* @author Martin Dougiamas and many others.
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*/
require_once("../../config.php");
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/mod/qcreate/lib.php');
require_once($CFG->dirroot . '/question/editlib.php');


list($thispageurl, $contexts, $cmid, $cm, $qcreate, $pagevars) = question_edit_setup('questions', true);
$qcreate->cmidnumber = $cm->id;
require_capability('mod/qcreate:grade', get_context_instance(CONTEXT_MODULE, $cm->id));
if ($qcreate->graderatio == 100){
    $grading_interface = false;
} else {
    $grading_interface = true;
} 

$page    = optional_param('page', 0, PARAM_INT);

$gradessubmitted   = optional_param('gradessubmitted', 0, PARAM_BOOL);          // grades submitted?
if ($grading_interface){ 
    $showungraded = optional_param('showungraded', 1, PARAM_BOOL);
    $showgraded = optional_param('showgraded', 1, PARAM_BOOL);
    $showneedsregrade = optional_param('showneedsregrade', 1, PARAM_BOOL);
} else {
    $showungraded = true;
    $showgraded =  true;
    $showneedsregrade =  true;
}



/* first we check to see if the form has just been submitted
 * to request user_preference updates
 */
if (isset($_POST['updatepref'])){
    $perpage = optional_param('perpage', 10, PARAM_INT);
    $perpage = ($perpage <= 0) ? 10 : $perpage ;
    set_user_preference('qcreate_perpage', $perpage);
}
/// find out current groups mode
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);

/// Get all ppl that are allowed to submit assignments
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
if (!$users = get_users_by_capability($context, 'mod/qcreate:submit', '', '', '', '', $currentgroup, '', false)){
    $users = array();
}
$users = array_keys($users);
if (!empty($CFG->enablegroupings) && !empty($cm->groupingid)) {
    $groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id');
    $users = array_intersect($users, array_keys($groupingusers));

}
// grades submitted?
if ($gradessubmitted){
    qcreate_process_grades($qcreate, $cm, $users);
}

/* next we get perpage params
 * from database
 */
$perpage    = get_user_preferences('qcreate_perpage', 10);

$grading_info = grade_get_grades($COURSE->id, 'mod', 'qcreate', $qcreate->id);

if (!empty($CFG->enableoutcomes) and !empty($grading_info->outcomes)) {
    $uses_outcomes = true;
} else {
    $uses_outcomes = false;
}

$teacherattempts = true; /// Temporary measure
$strsaveallfeedback = get_string('saveallfeedback', 'assignment');


$tabindex = 1; //tabindex for quick grading tabbing; Not working for dropdowns yet

add_to_log($COURSE->id, 'qcreate', 'grade', 'grades.php?id='.$qcreate->id, $qcreate->id, $cm->id);
$strqcreate = get_string('modulename', 'qcreate');
$strqcreates = get_string('modulenameplural', 'qcreate');
$navlinks = array();
$navlinks[] = array('name' => $strqcreates, 'link' => "index.php?id=$COURSE->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($qcreate->name,true),
                    'link' => "view.php?id={$cm->id}",
                    'type' => 'activityinstance');
$navlinks[] = array('name' => get_string('grading', 'qcreate'), 'link' => '', 'type' => 'title');
$navigation = build_navigation($navlinks);

print_header_simple(format_string($qcreate->name,true), "", $navigation,
        '', '', true, update_module_button($cm->id, $COURSE->id, $strqcreate), navmenu($COURSE, $cm));

$mode = 'editq';

include('tabs.php');

//setting this after tabs.php as these params are just for this page and should not be included in urls for tabs.
$thispageurl->params(compact('showgraded', 'showneedsregrade', 'showungraded', 'page'));

groups_print_activity_menu($cm, $thispageurl->out());

if ($grading_interface){
    $tablecolumns = array('picture', 'fullname', 'qname', 'grade', 'status', 'gradecomment', 'timemodified', 'timemarked', 'finalgrade');
    $tableheaders = array('',
                          get_string('fullname'),
                          get_string('question'),
                          get_string('grade'),
                          get_string('status'),
                          get_string('comment', 'qcreate'),
                          get_string('lastmodified'),
                          get_string('marked', 'qcreate'),
                          get_string('finalgrade', 'grades'));
    if ($uses_outcomes) {
        $tablecolumns[] = 'outcome'; // no sorting based on outcomes column
        $tableheaders[] = get_string('outcome', 'grades');
    }
} else {
    $tablecolumns = array('picture', 'fullname', 'qname', 'gradecomment', 'timemodified', 'finalgrade');
    $tableheaders = array('',
                          get_string('fullname'),
                          get_string('question'),
                          get_string('comment', 'qcreate'),
                          get_string('lastmodified'),
                          get_string('finalgrade', 'grades'));
}

$table = new flexible_table('mod-qcreate-grades');

$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($thispageurl->out());

$table->sortable(true, 'lastname');//sorted by lastname by default
$table->collapsible(true);
$table->initialbars(true);

$table->column_suppress('picture');
$table->column_suppress('fullname');

$table->column_class('picture', 'picture');
$table->column_class('fullname', 'fullname');
$table->column_class('question', 'question');
$table->column_class('gradecomment', 'comment');
$table->column_class('timemodified', 'timemodified');
$table->column_class('finalgrade', 'finalgrade');
if ($grading_interface){ 
    $table->column_class('grade', 'grade');
    $table->column_class('timemarked', 'timemarked');
    $table->column_class('status', 'status');
    if ($uses_outcomes) {
        $table->column_class('outcome', 'outcome');
    }
}
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'attempts');
$table->set_attribute('class', 'grades');
$table->set_attribute('width', '90%');
//$table->set_attribute('align', 'center');

if ($grading_interface){ 
    $table->no_sorting('finalgrade');
    $table->no_sorting('outcome');
}
// Start working -- this is necessary as soon as the niceties are over
$table->setup();



/// Construct the SQL


if (!empty($users) && ($showungraded || $showgraded || $showneedsregrade)){
    if ($sort = $table->get_sql_sort()) {
        $sort = ' ORDER BY '.$sort;
    }

    if ($where = $table->get_sql_where()) {
        $where .= ' AND ';
    }
    //unfortunately we cannot use status in WHERE clause
    switch ($showungraded . $showneedsregrade . $showgraded){
        case '001':
            $where .= '(g.timemarked IS NOT NULL) AND (g.timemarked >= q.timemodified ) AND ';
            break;
        case '010':
            $where .= '(g.timemarked IS NOT NULL) AND (g.timemarked < q.timemodified ) AND ';
            break;
        case '011':
            $where .= '(g.timemarked IS NOT NULL) AND ';
            break;
        case '100':
            $where .= '(g.timemarked IS NULL) AND ';
            break;
        case '101':
            $where .= '((g.timemarked IS NULL) OR g.timemarked >= q.timemodified) AND ';
            break;
        case '110':
            $where .= '((g.timemarked IS NULL) OR g.timemarked < q.timemodified) AND ';
            break;
        case '111': //show everything
            break;
    }
    if ($qcreate->allowed != 'ALL') {
        $allowedparts = explode(',', $qcreate->allowed);
        $allowedlist = "'".join("','", $allowedparts)."'";
        $where .= 'q.qtype IN ('.$allowedlist.') AND ';
    }

    $countsql = 'SELECT COUNT(*) FROM '.$CFG->prefix.'user u, '.$CFG->prefix.'question_categories c, '.$CFG->prefix.'question q '.
           'LEFT JOIN '.$CFG->prefix.'qcreate_grades g ON q.id = g.questionid '.
           'WHERE '.$where.'q.createdby = u.id AND u.id IN ('.implode(',',$users).
            ') AND q.hidden=\'0\' AND q.parent=\'0\' AND q.category = c.id and c.contextid='.$context->id;
    $answercount = count_records_sql($countsql);

    //complicated status calculation is needed for sorting on status column
    $select = 'SELECT q.id AS qid, u.id, u.firstname, u.lastname, u.picture,
                      g.id AS gradeid, g.grade, g.gradecomment,
                      q.timemodified, g.timemarked,
                      q.qtype, q.name AS qname,
                      COALESCE(
                        SIGN(SIGN(g.timemarked) + SIGN(g.timemarked - q.timemodified))
                        ,-1
                      ) AS status ';
    $sql = 'FROM '.$CFG->prefix.'user u, '.$CFG->prefix.'question_categories c,  '.$CFG->prefix.'question q '.
           'LEFT JOIN '.$CFG->prefix.'qcreate_grades g ON q.id = g.questionid
                                                              AND g.qcreateid = '.$qcreate->id.' '.
           'WHERE '.$where.'q.createdby = u.id AND u.id IN ('.implode(',',$users).
            ') AND q.hidden=\'0\' AND q.parent=\'0\' AND q.category = c.id and c.contextid='.$context->id;
} else {
    $answercount = 0;
}
if ($grading_interface){ 
    echo '<form id="showoptions" action="'.$thispageurl->out(true).'" method="post">';
    echo '<div>';
    echo $thispageurl->hidden_params_out(array('showgraded', 'showneedsregrade', 'showungraded'));
    //default value for checkbox when checkbox not checked.
    echo '<input type="hidden" name="showgraded" value="0" />';
    echo '<input type="hidden" name="showneedsregrade" value="0" />';
    echo '<input type="hidden" name="showungraded" value="0" />';
    echo '</div>';
    echo '<div class="mdl-align">';
    print_string('show', 'qcreate');
    $checked =  $showgraded?' checked="checked"':'';
    echo '<input onchange="getElementById(\'showoptions\').submit(); return true;"  type="checkbox" value="1" name="showgraded" id="id_showgraded"'.$checked.'/>';
    echo '<label for="id_showgraded">'.get_string('showgraded', 'qcreate').'</label>';
    $checked =  $showneedsregrade?' checked="checked"':'';
    echo '<input onchange="getElementById(\'showoptions\').submit(); return true;" type="checkbox" value="1" name="showneedsregrade" id="id_showneedsregrade"'.$checked.'/>';
    echo '<label for="id_showneedsregrade">'.get_string('showneedsregrade', 'qcreate').'</label>';
    $checked =  $showungraded?' checked="checked"':'';
    echo '<input onchange="getElementById(\'showoptions\').submit(); return true;" type="checkbox" value="1" name="showungraded" id="id_showungraded"'.$checked.'/>';
    echo '<label for="id_showungraded">'.get_string('showungraded', 'qcreate').'</label>';
    echo '<noscript>';
    echo '<input type="submit" name="go" value="'.get_string('go').'" />';
    echo '</noscript>';
    echo '</div></form>';
}
$table->pagesize($perpage, $answercount);

if ($answercount && false !== ($answers = get_records_sql($select.$sql.$sort, $table->get_page_start(), $table->get_page_size()))) {
    $strupdate = get_string('update');
    $strgrade  = get_string('grade');
    $grademenu = make_grades_menu($qcreate->grade);
    $grading_info = grade_get_grades($COURSE->id, 'mod', 'qcreate', $qcreate->id, $users);
    
    $qtypemenu = question_type_menu();
    
    foreach ($answers as $answer) {
        $final_grade = $grading_info->items[0]->grades[$answer->id];
    /// Calculate user status
        $answer->needsregrading = ($answer->timemarked <= $answer->timemodified);
        $picture = print_user_picture($answer->id, $COURSE->id, $answer->picture, false, true);

        if (empty($answer->gradeid)) {
            $answer->grade = -1; //no grade yet
        }
        if ($grading_interface && !$answer->needsregrading && $answer->timemarked!=0){
            $highlight = true;
        } else {
            $highlight = false;
        }
        $colquestion = $answer->qname;
        //preview?
        $strpreview = get_string("preview","quiz");
        if (question_has_capability_on($answer->qid, 'use')){
            $colquestion .= link_to_popup_window('/question/preview.php?id=' . $answer->qid . '&amp;courseid=' .$COURSE->id, 'questionpreview',
                        "<img src=\"$CFG->pixpath/t/preview.gif\" class=\"iconsmall\" alt=\"$strpreview\" />",
                        0, 0, $strpreview, QUESTION_PREVIEW_POPUP_OPTIONS, true);
        }
        // edit, hide, delete question, using question capabilities, not quiz capabilieies
        if (question_has_capability_on($answer->qid, 'edit') || question_has_capability_on($answer->qid, 'move')) {
            $stredit = get_string("edit");
            $colquestion .= link_to_popup_window('/question/question.php?id=' . $answer->qid . '&amp;cmid=' .$cm->id
                                        . '&amp;inpopup=1', 'questionedit',
                        "<img src=\"$CFG->pixpath/t/edit.gif\" class=\"iconsmall\" alt=\"$stredit\" />",
                        0, 0, $stredit, QCREATE_EDIT_POPUP_OPTIONS, true);
        } elseif (question_has_capability_on($answer->qid, 'view')){
            $strview = get_string("view");
            $colquestion .= link_to_popup_window('/question/question.php?id=' . $answer->qid . '&amp;cmid=' .$cm->id
                                        . '&amp;inpopup=1', 'questionedit',
                        "<img src=\"$CFG->pixpath/t/info.gif\" class=\"iconsmall\" alt=\"$strview\" />",
                        0, 0, $strview, QCREATE_EDIT_POPUP_OPTIONS, true);
        }
        if ($highlight){
            $colquestion = '<span class="highlight">'.$colquestion.'</span>';
        }
        $colquestion .= '<br />('.$qtypemenu[$answer->qtype].')';
        if ($answer->timemodified > 0) {
            $studentmodified = '<div id="ts'.$answer->qid.'">'.userdate($answer->timemodified).'</div>';
        } else {
            $studentmodified = '';
        }
        if (!empty($answer->gradeid)) {
        ///Prints student answer and student modified date
        ///attach file or print link to student answer, depending on the type of the assignment.
        ///Refer to print_student_answer in inherited classes.

        ///Print grade, dropdown or text
            if ($answer->timemarked > 0) {
                $teachermodified = '<div id="tt'.$answer->qid.'">'.userdate($answer->timemarked).'</div>';

                if ($final_grade->locked or $final_grade->overridden) {
                    $grade = '<div id="g'.$answer->qid.'">'.$final_grade->str_grade.'</div>';
                } else {
                    $menu = choose_from_menu(make_grades_menu($qcreate->grade),
                                             'menu['.$answer->qid.']', $answer->grade,
                                             get_string('nograde'),'',-1,true,false,$tabindex++);
                    $grade = '<div id="g'.$answer->qid.'">'. $menu .'</div>';
                }

            } else {
                $teachermodified = '<div id="tt'.$answer->qid.'">&nbsp;</div>';
                if ($final_grade->locked or $final_grade->overridden) {
                    $grade = '<div id="g'.$answer->qid.'">'.$final_grade->str_grade.'</div>';
                } else {
                    $menu = choose_from_menu(make_grades_menu($qcreate->grade),
                                             'menu['.$answer->qid.']', $answer->grade,
                                             get_string('nograde'),'',-1,true,false,$tabindex++);
                    $grade = '<div id="g'.$answer->qid.'">'.$menu.'</div>';
                }
            }
        ///Print Comment
            if ($final_grade->locked or $final_grade->overridden) {
                $comment = '<div id="com'.$answer->qid.'">'.shorten_text(strip_tags($final_grade->str_feedback),15).'</div>';

            } else {
                $comment = '<div id="com'.$answer->qid.'">'
                         . '<textarea tabindex="'.$tabindex++.'" name="gradecomment['.$answer->qid.']" id="gradecomment'
                         . $answer->qid.'" rows="4" cols="30">'.($answer->gradecomment).'</textarea></div>';
            }
        } else {
            $teachermodified = '<div id="tt'.$answer->qid.'">&nbsp;</div>';
            $status          = '<div id="st'.$answer->qid.'">&nbsp;</div>';

            if ($final_grade->locked or $final_grade->overridden) {
                $grade = '<div id="g'.$answer->qid.'">'.$final_grade->str_grade.'</div>';
            } else {   // allow editing
                $menu = choose_from_menu(make_grades_menu($qcreate->grade),
                                         'menu['.$answer->qid.']', $answer->grade,
                                         get_string('nograde'),'',-1,true,false,$tabindex++);
                $grade = '<div id="g'.$answer->qid.'">'.$menu.'</div>';
            }

            if ($final_grade->locked or $final_grade->overridden) {
                $comment = '<div id="com'.$answer->qid.'">'.$final_grade->str_feedback.'</div>';
            } else {
                $comment = '<div id="com'.$answer->qid.'">'
                         . '<textarea tabindex="'.$tabindex++.'" name="gradecomment['.$answer->qid.']" id="gradecomment'
                         . $answer->qid.'" rows="4" cols="30">'.($answer->gradecomment).'</textarea></div>';
            }
        }

        if ($answer->timemarked==0){
            $status = get_string('needsgrading', 'qcreate');
        } else if ($answer->needsregrading){
            $status = get_string('needsregrading', 'qcreate');
        } else {
            $status = get_string('graded', 'qcreate');
        }
        if ($highlight){
            $status = '<span class="highlight">'.$status.'</span>';
        }

        $finalgrade = '<span id="finalgrade_'.$answer->qid.'">'.$final_grade->str_grade.'</span>';

        $outcomes = '';

        if ($uses_outcomes) {

            foreach($grading_info->outcomes as $n=>$outcome) {
                $outcomes .= '<div class="outcome"><label>'.$outcome->name.'</label>';
                $options = make_grades_menu(-$outcome->scaleid);

                if ($outcome->grades[$answer->id]->locked) {
                    $options[0] = get_string('nooutcome', 'grades');
                    $outcomes .= ': <span id="outcome_'.$n.'_'.$answer->qid.'">'.$options[$outcome->grades[$answer->qid]->grade].'</span>';
                } else {
                    $outcomes .= ' ';
                    $outcomes .= choose_from_menu($options, 'outcome_'.$n.'['.$answer->qid.']',
                                $outcome->grades[$answer->qid]->grade, get_string('nooutcome', 'grades'), '', 0, true, false, 0, 'outcome_'.$n.'_'.$answer->qid);
                }
                $outcomes .= '</div>';
            }
        }
        if ($grading_interface){ 
            $row = array($picture, fullname($answer), $colquestion, $grade, $status, $comment, $studentmodified, $teachermodified, $finalgrade);
        } else {
            $row = array($picture, fullname($answer), $colquestion, $comment, $studentmodified, $finalgrade);
        }
        if ($uses_outcomes) {
            $row[] = $outcomes;
        }

        $table->add_data($row);
    }
}
if (!empty($table->data)){
    echo '<form action="'.$thispageurl->out(true).'" id="fastg" method="post">';
    echo '<div>';
    echo '<input type="hidden" name="gradessubmitted" value="1" />';
	echo $thispageurl->hidden_params_out();
    echo '</div>';
}


$table->print_html();  /// Print the whole table

if (!empty($table->data)){
    if ($grading_interface){
        echo '<div style="text-align:center"><input type="submit" name="fastg" value="'.get_string('saveallfeedbackandgrades', 'qcreate').'" /></div>';
    } else {
        echo '<div style="text-align:center"><input type="submit" name="fastg" value="'.get_string('saveallfeedback', 'qcreate').'" /></div>';
    }
    echo '</form>';
    /// End of fast grading form
}

/// Mini form for setting user preference
echo "\n<br />";

$form = '<form id="options" action="'.$thispageurl->out(true).'" method="post">';
$form .=  '<fieldset class="invisiblefieldset">';
$form .= $thispageurl->hidden_params_out();
$form .= '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
$form .= '<p>';
$form .= '<label for="id_perpage">'.get_string('pagesize','qcreate').'</label>';
$form .= '<input type="text" id="id_perpage" name="perpage" size="1" value="'.$perpage.'" /><br />';
$form .= '<input type="submit" value="'.get_string('savepreferences').'" />';
$form .= '</p>';
$form .= '</fieldset>';
$form .= '</form>';
print_box($form, 'generalbox boxaligncenter boxwidthnarrow');
///End of mini form
print_footer($COURSE);
?>

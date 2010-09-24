<?php

/**
* Module Brainstorm V2
* @author Valery Fremaux
* @package Brainstorm
* @date 20/12/2007
*
* this screen is used for grading student's work
*/
include_once("$CFG->dirroot/mod/brainstorm/operators/operator.class.php");

/// print participant selector
$gradefor = optional_param('gradefor', 0, PARAM_INT);
$students = get_users_by_capability($context, 'mod/brainstorm:gradable', 'u.id,lastname,firstname,email,picture', 'lastname');
$grademenu_options[0] = get_string('summary', 'brainstorm');
foreach($students as $student){
    $grademenu_options[$student->id] = fullname($student);
}
echo '<form name="chooseform" method="POST" action="view.php">';
echo "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />";
choose_from_menu($grademenu_options, 'gradefor', $gradefor, '', "document.forms['chooseform'].submit();", '');
echo '</form>';

/// print grade form
echo '<center>';
if ($gradefor == 0){ // implement a global summary to check who has been graded and who has not

    $allstudentsids = array_keys($grademenu_options);
    $grades = brainstorm_get_grades($brainstorm->id, $allstudentsids);
    $ungraded = brainstorm_get_ungraded($brainstorm->id, $allstudentsids);

    print_heading(get_string('gradesummary', 'brainstorm'));
?>
    <table width="80%">
        <tr>
            <td>
                <?php print_heading(get_string('ungraded', 'brainstorm'), '', 3, 'h2') ?>
            </td>
            <td>
                <?php print_heading(get_string('graded', 'brainstorm'), '', 3, 'h2') ?>
            </td>
        </tr>
        <tr>
            <td align="right"><!-- student ungraded -->
<?php
    foreach($ungraded as $student){
        if (!has_capability('mod/brainstorm:gradable', $context)) continue;
        print_user_picture($student->id, $course->id, $student->picture, false, false, true);
        echo fullname($student);
        echo " -> <a href=\"view.php?id={$cm->id}&amp;gradefor={$student->id}\">".get_string('dograde', 'brainstorm').'</a><br/>';
    }
?>
            </td>
            <td><!-- student already graded -->
<?php
    if ($grades){
        foreach($grades as $grade){
            $blendedgrades[$grade->id]->{$grade->gradeitem} = $grade->grade;
        }

        $gradestr = get_string('grade');
        if ($brainstorm->singlegrade){ // print for a single grading
            $table->head = array('', '', "<b>$gradestr</b>", '');
            $table->align = array('center', 'right', 'center', 'right');
            $table->size = array('15%', '60%', '15%', '10%');
            foreach($blendedgrades as $studentid => $gradeset){
                $student = get_record('user', 'id', $studentid, '', 'id,firstname,lastname,picture,email');
                $picture = print_user_picture($student->id, $course->id, $student->picture, false, true, true);
                $studentname = fullname($student);
                $updatelink = "<a href=\"view.php?id={$cm->id}&amp;gradefor={$student->id}\"><img src=\"{$CFG->pixpath}/t/edit.gif\"></a><br/>";
                $deletelink = "<a href=\"view.php?id={$cm->id}&amp;what=deletegrade&amp;for={$student->id}\"><img src=\"{$CFG->pixpath}/t/delete.gif\"></a><br/>";
                $table->data[] = array($picture, $studentname, $gradeset->single, $updatelink.'&nbsp;'.$deletelink);
            }
            print_table($table);
        }
        else{ // print for a dissociated grading
            $participatestr = get_string('participation', 'brainstorm').'<br/>('.($brainstorm->participationweight * 100).'%)';
            $preparingstr = get_string('preparations', 'brainstorm').'<br/>('.($brainstorm->preparingweight * 100).'%)';
            $organizestr = get_string('organizations', 'brainstorm').'<br/>('.($brainstorm->organizeweight * 100).'%)';
            $feedbackstr = get_string('feedback', 'brainstorm').'<br/>('.($brainstorm->feedbackweight * 100).'%)';
            $finalstr = get_string('finalgrade', 'brainstorm');
            $table->head = array('', '', "<b>$participatestr</b>", "<b>$preparingstr</b>", "<b>$organizestr</b>", "<b>$feedbackstr</b>", "<b>$finalstr</b>", '');
            $table->align = array('center', 'right', 'center', 'right');
            $table->size = array('15%', '60%', '15%', '10%');
            foreach($blendedgrades as $studentid => $gradeset){
                $student = get_record('user', 'id', $studentid, '', 'id,firstname,lastname,picture,email');

                // get grade components
                if ($brainstorm->seqaccesscollect && isset($gradeset->participate)){
                    if (isset($gradeset->participate)){
                        $participategrade = $gradeset->participate;
                        $weights[] = $brainstorm->participationweight;
                        $gradeparts[] = $gradeset->participate;
                    }
                    else{
                        $participategrade = '';
                    }
                }
                else{
                    $participategrade = "<img src=\"{$CFG->wwwroot}/mod/brainstorm/teachhat.jpg\" width=\"30\" />" ;
                }
                if ($brainstorm->seqaccessprepare){
                    if (isset($gradeset->prepare)){
                        $preparinggrade = $gradeset->prepare;
                        $weights[] = $brainstorm->preparingweight;
                        $gradeparts[] = $gradeset->prepare;
                    }
                    else{
                        $preparinggrade = '';
                    }
                }
                else{
                    $preparinggrade = "<img src=\"{$CFG->wwwroot}/mod/brainstorm/teachhat.jpg\" width=\"30\" />" ;
                }
                if ($brainstorm->seqaccessorganize){
                    if (isset($gradeset->organize)){
                        $organizegrade = $gradeset->organize;
                        $weights[] = $brainstorm->organizeweight;
                        $gradeparts[] = $gradeset->organize;
                    }
                    else{
                        $organizegrade = '';
                    }
                }
                else{
                    $organizegrade = "<img src=\"{$CFG->wwwroot}/mod/brainstorm/teachhat.jpg\" width=\"30\" />" ;
                }
                if ($brainstorm->seqaccessfeedback){
                    if (isset($gradeset->feedback)){
                        $feedbackgrade = $gradeset->feedback;
                        $weights[] = $brainstorm->feedbackweight;
                        $gradeparts[] = $gradeset->feedback;
                    }
                    else{
                        $feedbackgrade = '';
                    }
                }
                else{
                    $feedbackgrade = "<img src=\"{$CFG->wwwroot}/mod/brainstorm/teachhat.jpg\" width=\"30\" />" ;
                }

                // calculates final
                $weighting = array_sum($weights);
                $finalgrade = 0;
                for ($i = 0 ; $i < count($gradeparts) ; $i++){
                    $finalgrade += $gradeparts[$i] * $weights[$i];
                }
                $finalgrade = sprintf("%0.2f", $finalgrade / $weighting);

                $picture = print_user_picture($student->id, $course->id, $student->picture, false, true, true);
                $studentname = fullname($student);
                $updatelink = "<a href=\"view.php?id={$cm->id}&amp;gradefor={$student->id}\"><img src=\"{$CFG->pixpath}/t/edit.gif\"></a><br/>";
                $deletelink = "<a href=\"view.php?id={$cm->id}&amp;what=deletegrade&amp;for={$student->id}\"><img src=\"{$CFG->pixpath}/t/delete.gif\"></a><br/>";
                $table->data[] = array($picture, $studentname, $participategrade, $preparinggrade, $organizegrade, $feedbackgrade, "<b>$finalgrade</b>", $updatelink.'&nbsp;'.$deletelink);
            }
            print_table($table);
        }
    }
?>
            </td>
        </tr>
    </table>
<?php
}
else{ // grading a user
    /// starting form
    echo '<form name="gradesform" action="view.php" method="post">';
    echo "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />";
    echo "<input type=\"hidden\" name=\"what\" value=\"savegrade\" />";
    echo "<input type=\"hidden\" name=\"for\" value=\"{$gradefor}\" />";

    $gradeset = brainstorm_get_gradeset($brainstorm->id, $gradefor);

    $user = get_record('user', 'id', $gradefor);
    print_heading(get_string('gradingof', 'brainstorm').' '.fullname($user));

    /// printing ideas
    print_heading(get_string('responses', 'brainstorm'), '', 3);
    $responses = brainstorm_get_responses($brainstorm->id, $user->id, 0, false);
    if ($responses){
        echo '<table><tr>';
        brainstorm_print_responses_cols($brainstorm, $responses, false);
        echo '</tr></table>';
    }
    else {
        print_simple_box(get_string('notresponded', 'brainstorm'));
    }
    if (!$brainstorm->singlegrade && $brainstorm->seqaccesscollect){
        print_string('gradeforparticipation', 'brainstorm');
        echo ' : ';
        make_grading_menu($brainstorm, 'participate', @$gradeset->participate, false);
    }

    // getting valid operator list
    $operators = brainstorm_get_operators($brainstorm->id);

    /// printing preparing sets
    print_heading(get_string('preparations', 'brainstorm'), '', 3);
    if ($operators){
        echo '<table width="90%" cellspacing="10"><tr valign="top">';
        $i = 0;
        foreach($operators as $operator){ // print operator settings for each valid operator
            if (!$operator->active) continue;
            if ($i && $i % 2 == 0) echo '</tr><tr valign="top">';
            echo '<td align="right" width="50%">';
            print_heading(get_string($operator->id, 'brainstorm'), '', 4);
            echo '<table width="90%">';
            foreach(get_object_vars($operator->configdata) as $key => $value){
                echo "<tr valign=\"top\"><td align=\"right\" width=\"60%\"><b>".get_string($key, 'brainstorm').'</b>:</td>';
                echo "<td>$value</td></tr>";
            }
            echo '</table></td>';
            $i++;
        }
        echo '</tr></table>';
    }

    if (!$brainstorm->singlegrade && $brainstorm->seqaccessprepare){
        echo '<br/>';
        print_string('gradeforpreparation', 'brainstorm');
        echo ' : ';
        make_grading_menu($brainstorm, 'prepare', @$gradeset->prepare, false);
    }

    /// printing organizations
    print_heading(get_string('organizations', 'brainstorm'), '', 3);
    if ($operators){
        foreach($operators as $operator){ // print organisation result for this operator and this user
            if (!$operator->active) continue;
            print_simple_box_start('center');
            include_once("{$CFG->dirroot}/mod/brainstorm/operators/{$operator->id}/locallib.php");
            $displayfunction = $operator->id.'_display';
            if (function_exists($displayfunction)){
                print_heading(get_string($operator->id, 'brainstorm'), '', 4);
                $brainstorm->cm = &$cm;
                $displayfunction($brainstorm, $gradefor, $currentgroup);
            }
            else{
               echo get_string('notabletodisplayfor', 'brainstorm', get_string($operator->id, 'brainstorm'));
            }
            print_simple_box_end();
        }
    }
    if (!$brainstorm->singlegrade && $brainstorm->seqaccessorganize){
        echo '<br/>';
        print_string('gradefororganisation', 'brainstorm');
        echo ' : ';
        make_grading_menu($brainstorm, 'organize', @$gradeset->organize, false);
    }

    /// printing final feedback and report
    print_heading(get_string('feedback', 'brainstorm'), '', 3);
    $report = get_record('brainstorm_userdata', 'brainstormid', $brainstorm->id, 'userid', $gradefor);
    print_simple_box(format_string(format_text(@$report->report, @$report->reportformat)));

    if (!$brainstorm->singlegrade && $brainstorm->seqaccessfeedback){
        echo '<br/>';
        print_string('gradeforfeedback', 'brainstorm');
        echo ' : ';
        make_grading_menu($brainstorm, 'feedback', @$gradeset->feedback, false);
    }

    // print a final feedback form

    echo '<br/><br/><table width="80%"><tr valign="top"><td><b>'.get_string('feedback').':</b></td><td>';
    $usehtmleditor = can_use_html_editor();
    print_textarea($usehtmleditor, 20, 50, 680, 400, 'teacherfeedback', @$report->feedback);
    if (!$usehtmleditor){
		echo '<p align="right">';
        helpbutton('textformat', get_string('formattexttype'));
        print_string('formattexttype');
        echo ":&nbsp;";
        if (empty($report->feedbackformat)) {
           $report->feedbackformat = FORMAT_MOODLE;
        }
        choose_from_menu(format_text_menu(), 'feedbackformat', $report->feedbackformat, '');
    }
    else{
        $htmleditorneeded = 1;
    }
    echo '</td></tr></table>';

    // if single grading, print a single grade scale
    if ($brainstorm->singlegrade){
        echo '<br/>';
        print_string('grade');
        echo ' : ';
        make_grading_menu($brainstorm, 'grade', @$gradeset->single, false);
    }

    /// print the submit button
    echo '<br/><center>';
    echo "<br/><input type=\"submit\" name=\"go_btn\" value=\"". get_string('update') .'" />';

    /// end form
    echo '</form>';
}
echo '</center>';
?>
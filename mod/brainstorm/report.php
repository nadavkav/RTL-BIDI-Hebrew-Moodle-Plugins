<?php

print_heading(get_string('report', 'brainstorm'));

if (!isadmin() && has_capability('mod/brainstorm:gradable', $context)){
    $form->report = get_field('brainstorm_userdata', 'report', 'brainstormid', $brainstorm->id, 'userid', $USER->id);
    $form->reportformat = get_field('brainstorm_userdata', 'reportformat', 'brainstormid', $brainstorm->id, 'userid', $USER->id);
    echo '<b>'.get_string('myreport', 'brainstorm').':</b><br/>';
    print_simple_box(format_string(format_text(@$form->report, @$form->reportformat)));
    if (!empty($feedback)){
        echo '<b>'.get_string('teacherfeedback', 'brainstorm').':</b><br/>';
        print_simple_box($feedback);
    }
    $options = array('id' => $cm->id, 'what' => 'editreport');
    echo '<center>';
    print_single_button("view.php",  $options, get_string('editareport', 'brainstorm'));
    echo '</center>';
}
else{ // you are "teacher" here, see the list of who posted reports
    $participants = get_users_by_capability($context, 'mod/brainstorm:gradable', 'u.id,firstname,lastname,picture,email', 'lastname');
    $reportstatus = brainstorm_have_reports($brainstorm->id, array_keys($participants));
    $havereported = array_keys($reportstatus);
?>
<table width="90%">
    <tr>
        <th>
            <?php print_string('havereport', 'brainstorm') ?>
        </th>
        <th>
            <?php print_string('reportless', 'brainstorm') ?>
        </th>
    <tr>
        <td>    
            <table>
<?php
    $werereported = false;
    foreach($participants as $participant){
        if (@in_array($participant->id, $havereported)){
            $werereported = true;
            echo '<tr><td>';
            print_user_picture($participant->id, $course->id, $participant->picture, false, false, true);
            echo fullname($participant);
            echo " -> <a href=\"view.php?id={$cm->id}&amp;view=grade&amp;gradefor={$participant->id}\">".get_string('dograde', 'brainstorm').'</a><br/>';
            echo '</td></tr>';
        }
        else{
            $reportless[] = $participant;
        }
    }
    if (!$werereported){
        echo '<tr><td>'.get_string('noreports', 'brainstorm').'</td></tr>';
    }
?>
            </table>
        </td>
        <td>
            <table>
<?php
    if (!empty($reportless)){
        foreach($reportless as $participant){
            echo '<tr><td>';
            print_user_picture($participant->id, $course->id, $participant->picture, false, false, true);
            echo fullname($participant);
            echo '</td></tr>';
        }
    }
?>
            </table>
        </td>
    </tr>
</table>
<?php
}
?>

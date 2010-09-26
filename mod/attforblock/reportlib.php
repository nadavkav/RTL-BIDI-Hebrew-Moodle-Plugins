<?php

global $CFG;

function print_detailed_report($user, $cm,  $course = 0, $printing = null) {
    global $CFG, $COURSE, $mode, $whereclause, $id, $student, $courseselected, $studentselected, $course, $reportselected;

    $statuses = get_statuses($courseselected);

    //	Define the options of the drop down menu for make up notes and sicknotes

    $optionlist = array('notrequired' => get_string('notrequired', 'attforblock'),
                        'outstanding' => get_string('outstanding', 'attforblock'),
                        'submitted' => get_string('submitted', 'attforblock'),
                        'cleared' => get_string('cleared', 'attforblock'));

    if ($course) {
        $stqry = "
            SELECT ats.id,ats.groupid,ats.courseid,ats.sessdate,ats.sessiontitle,ats.subject,ats.teacher,ats.description, al.id, al.studentid, al.statusid,al.remarks,al.makeupnotes,al.sicknote
            FROM {$CFG->prefix}attendance_log al
            JOIN {$CFG->prefix}attendance_sessions ats
            ON al.sessionid = ats.id
            WHERE ".$whereclause.
          ' ORDER BY ats.sessdate asc';
        if ($logs = get_records_sql($stqry)) {
            $statuses = get_allstatuses();
            if ($reportselected === 'all'){
		?>
		<table class="generaltable">
		    <tr>
		        <th id="tshowstudent" name="tshowstudent"><?php print_string('student','attforblock')?></th>
		        <th id="tshowcourse" name="tshowcourse"><?php print_string('course')?></th>
		        <th><?php print_string('date')?></th>
		        <th id="tshowstatus" name="tshowstatus"><?php print_string('status','attforblock')?></th>
		        <th id="tshowtitle" name="tshowtitle"><?php print_string('sessiontitle','attforblock')?></th>
		        <th id="tshowsubject" name="tshowsubject"><?php print_string('subject','attforblock')?></th>
		        <th id="tshowteacher" name="tshowteacher"><?php print_string('teacher','attforblock')?></th>
		        <th id="tshowdescription" name="tshowdescription"><?php print_string('description','attforblock')?></th>
		        <th id="tshowmakeupnotes" name="tshowmakeupnotes"><?php print_string('makeupnotes','attforblock')?></th>
		        <th id="tshowsicknotes" name="tshowsicknotes"><?php print_string('sicknote','attforblock')?></th>
		        <th id="tshowremarks" name="tshowremarks"><?php print_string('remarks','attforblock')?></th>
		    </tr>
		<?php
	    }

    $i = 1;
    foreach($logs as $log) {
        ?>
    <tr>
        <td id="tshowstudent" name="tshowstudent">
        <?php 
       	echo "<a href=\"view.php?id=$id&amp;student={$student->id}\">".' '.fullname($student);
        ?>
        </td>
        <td id="tshowcourse" name="tshowcourse">
        <?php 
       	echo "<a href=\"{$CFG->wwwroot}/course/view.php?id={$course->id}\">".$course->fullname.'</a>';
        ?></td>  
        <td><?php echo "<a href=\"attendances.php?id=$id&amp;sessionid={$log->id}\">".
        userdate($log->sessdate, get_string('strftimedmyw', 'attforblock').
                        '('.get_string('strftimehm', 'attforblock').')').'</a>'; ?></td>
        <td id="tshowstatus" name="tshowstatus"><?php echo $statuses[$log->statusid]->description ?></td>
        <td id="tshowtitle" name="tshowtitle"><?php echo empty($log->sessiontitle) ? get_string('notitle', 'attforblock') : $log->sessiontitle;  ?></td>
        <td id="tshowsubject" name="tshowsubject"><?php echo empty($log->subject) ? get_string('nosubject', 'attforblock') : $log->subject;  ?></td>
        <td id="tshowteacher" name="tshowteacher"><?php echo empty($log->teacher) ? get_string('noteacher', 'attforblock') : $log->teacher;  ?></td>
        <td id="tshowdescription" name="tshowdescription"><?php echo empty($log->description) ? get_string('nodescription', 'attforblock') : $log->description;  ?></td>
        <td id="tshowmakeupnotes" name="tshowmakeupnotes"><?php echo choose_from_menu($optionlist, 'makenote'.'['.$log->id.']', ($log ? $log->makeupnotes : ''),'', "showUser(this.name, this.value)", '',  true);?></td>
        <td id="tshowsicknotes" name="tshowsicknotes"><?php echo choose_from_menu($optionlist, 'sicknote'.'['.$log->id.']', ($log ? $log->sicknote : ''),'', "showUser(this.name, this.value)", '',  true);?></td>
        <td id="tshowremarks" name="tshowremarks"><?php echo '<input type="text" name="myremark['.$log->id.']" size="10" onchange="showUser(this.name, this.value)" value="'.($log ? $log->remarks : '').'">';?></td>
    </tr>
<?php

    }
      if ($reportselected === 'all'){
        echo '</table>';
      }

	} else {
	    echo '<p>'.get_string('noattforperiod','attforblock').'</p>';
        }
    }
}
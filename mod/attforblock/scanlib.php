<?php
// Find the unprocessed sessions for this course which have ended
$timenow = time();
$sessions = get_records_select('attendance_sessions', 'courseid = '.$course->id.' AND processed = 0 AND sessionend < '.$timenow);
// check if any sessions have been logged for this course
if ($sessions) {
    // loop through each session
    foreach ($sessions as $session) {
        $sessionstart = $session->sessdate;
        $sessionend = $session->sessionend;
        $studentlist = get_course_students($course->id);
        echo '<p><hr /></p>';
        echo '<p><h1>Date: '.userdate($sessionstart, get_string('strftimedmyw', 'attforblock'));
        echo ', Start: '.userdate($sessionstart, get_string('strftimehm', 'attforblock'));
        echo ', End: '.userdate($sessionend, get_string('strftimehm', 'attforblock')).'</h1></p>';
        if($studentlist) {
            // For each found student, apply the rules for this course
            foreach ($studentlist as $student) {
                echo '<p><hr /></p>';
                echo '<h2>'.fullname($student).'</h2>';
                $es = "
                SELECT id, MAX(timescanned) AS maxtimescanned
                FROM {$CFG->prefix}attendance_scan_logs
                WHERE timescanned <= $sessionstart
                AND scannedin = 1
                AND (allocated = 0 OR carriedover = 1)
                AND courseid = $course->id
                AND studentid = $student->id";
                $earlystart = get_record_sql($es);
                $earlystartcount = count_records_sql($es);
                $ls = "
                SELECT id, MAX(timescanned) AS maxtimescanned
                FROM {$CFG->prefix}attendance_scan_logs
                WHERE timescanned <= $sessionend
                AND scannedin = 1
                AND (allocated = 0 OR carriedover = 1)
                AND courseid = $course->id
                AND studentid = $student->id";
                $latestart = get_record_sql($ls);
                $latestartcount = count_records_sql($ls);
                $ef = "
                SELECT id, MAX(timescanned) AS maxtimescanned
                FROM {$CFG->prefix}attendance_scan_logs
                WHERE timescanned < $sessionend
                AND scannedin = 0
                AND timescanned > $sessionstart
                AND (allocated = 0 OR carriedover = 1)
                AND courseid = $course->id
                AND studentid = $student->id";
                $earlyfinish = get_record_sql($ef);
                $earlyfinishcount = count_records_sql($ef);
                if($latestartcount == 0) {
                    $start = 9999999999;
                } else {
                    // store late start time
                    $start = $latestart->maxtimescanned;
                }
                If ($earlystartcount == 0) {
                    $start = 9999999999;
                } else {
                    // store early start time
                    $start = $earlystart->maxtimescanned;
                }
                if($earlyfinishcount == 0) {
                    $finish = $sessionend;
                } else {
                    // store early finish time
                    $finish = $earlyfinish->maxtimescanned;
                }
                If ($earlystartcount == 0 && $latestartcount == 0) {
                    echo '<p> Student was absent </p>';
                    $absent = 1;
                } else {
                    echo '<p>Date: '.userdate($start, get_string('strftimedmyw', 'attforblock'));
                    echo ', Arrived at: '.userdate($start, get_string('strftimehm', 'attforblock'));
                    echo ', Left at: '.userdate($finish, get_string('strftimehm', 'attforblock')).'</p>';
                }
                $scanrecords = 'courseid = '.$course->id.
                               ' AND timescanned < '.$session->sessionend.
                               ' AND (allocated = 0 OR carriedover = 1)'.
                               ' AND studentid = '.$student->id.
                               ' AND success = 1';
                $scans = get_records_select('attendance_scan_logs', $scanrecords, 'timescanned asc');
                $scancount = count_records_select('attendance_scan_logs', $scanrecords);
                // calculate total time attended
                if(!$scancount == 0) {
                    $lastscanin = 0;
                    $lastscanout = 0;
                    $timeattended = 0;
                    $timesum = 0;
                    foreach ($scans as $scan) {
                        $scannedin = $scan->scannedin;
                        $scantime = $scan->timescanned;
                        echo '<p>Date: '.userdate($scantime, get_string('strftimedmyw', 'attforblock'));
                        echo ', Scantime: '.userdate($scantime, get_string('strftimehm', 'attforblock'));
                        if(!$lastscanin == 0 && !$lastscanout == 0) {
                            $timeattendend = $lastscanout - $lastscanin;
                            $timesum += $timeattended;
                        }
                        if($scannedin == 1) {
                            $lastscanin = $scantime;
                            echo ', scanned IN</p>';
                            //  process ontime starts
                            if($scantime < $sessionstart) {
                                $lastscanin = 0;
                            } elseif($scantime >= $sessionstart) {
                                //  process late starts
                                $lastscanin = $scantime;
                            }
                        } else {
                            // $scannedin === 0
                            echo ', scanned OUT</p>';

                            if(($scantime <= $sessionend && $scantime > $sessionstart && $scantime > $lastscanin)) {
                                //  process early finishs
                                $lastscanin = $scantime;
                            }
                        }
                        //     set_field('attendance_scan_logs', 'allocated', '1', 'id', $scan->id);
                    }  // end of foreach scan
                    // store the result in $currentrule
                    if($lastscanin > $lastscanout) {
                        $lastscanout = $finish;
                        $timeattendend = $lastscanout - $lastscanin;
                        $timesum += $timeattended;
                        echo '<p>Total time attended: </p>'.$timesum;
                        $sessionduration = $sessionend - $sessionstart;
                        $timepresent = ($timesum/$sessionduration)*100;
                        echo '<p>Percentage of session attended: </p>'.$timepresent;
                    }
                    // find out the attendance rules for the current course
                    $rules = get_records_select('attendance_statuses', 'visible = 1 AND courseid = '.$course->id, 'grade asc' );
                    // check if there are any rules set for this course
                    if(!empty($rules)) {
                        $ruleset = 0;
                        // apply the rules for this course to the current scan (loop through all the rules and apply them)
                        echo '<p></p>';
                        echo 'We found the rules for this course';
                        foreach ($rules as $rule) {

                            //  Rules to apply if the student is late:
                            if ($rule->afterstart > 0 && $rule->startlogic == 'greater') {
                                echo '<p></p>';
                                echo 'RULES were set for starting times more than x';
                                if ($start >= ($sessionstart + $rule->afterstart)) {
                                    $currentrule = $rule->id;
                                    $currentsicknote = $rule->sicknote;
                                    $currentmakeupnote = $rule->makeupnote;
                                    $ruleset = 1;
                                }
                            } elseif ($rule->afterstart > 0 && $rule->startlogic == 'less') {
                                echo '<p></p>';
                                echo 'RULES were set for starting times less than x';
                                if ($start <= ($sessionstart + $rule->afterstart)) {
                                    $currentrule = $rule->id;
                                    $currentsicknote = $rule->sicknote;
                                    $currentmakeupnote = $rule->makeupnote;
                                    $ruleset = 1;
                                }
                            }
                            //  Rules to apply if the student left early:
                            if ($rule->beforefinish > 0 && $rule->finishlogic == 'greater') {
                                echo '<p></p>';
                                echo 'RULES were set for finish times more than x before session end time';
                                if ($finish <= ($sessionend - $rule->beforefinish)) {
                                    $currentrule = $rule->id;
                                    $currentsicknote = $rule->sicknote;
                                    $currentmakeupnote = $rule->makeupnote;
                                    $ruleset = 1;
                                }
                            } elseif ($rule->beforefinish > 0 && $rule->finishlogic == 'less') {
                                echo '<p></p>';
                                echo 'RULES were set for starting times less than x before session end time';
                                if ($finish >= ($sessionend - $rule->beforefinish&& $finish < $sessionend)) {
                                    $currentrule = $rule->id;
                                    $currentsicknote = $rule->sicknote;
                                    $currentmakeupnote = $rule->makeupnote;
                                    $ruleset = 1;
                                }
                            }
                            //  Rules to apply according to what percentage of the session was attended
                            if ($rule->logicoperator == 'none' OR $rule->logicoperator == NULL) {
                                // do nothing
                                echo '<p></p>';
                                echo 'No rules were set for time present';
                            } else {
                                echo '<p></p>';
                                echo 'Time Present RULES have been set';
                                // store the result in $currentrule
                                $sessionduration = $sessionend - $sessionstart;
                                $timepresent = ($timesum/$sessionduration)*100;
                                if ($rule->percentageattended > 0 && $timepresent <= $rule->percentageattended && $rule->logicoperator == 'less') {
                                    $currentrule = $rule->id;
                                    $currentsicknote = $rule->sicknote;
                                    $currentmakeupnote = $rule->makeupnote;
                                    $ruleset = 1;
                                } elseif ($rule->percentageattended > 0 && $timepresent >= $rule->percentageattended && $rule->logicoperator == 'greater') {
                                    $currentrule = $rule->id;
                                    $currentsicknote = $rule->sicknote;
                                    $currentmakeupnote = $rule->makeupnote;
                                    $ruleset = 1;
                                }
                            }
                        }
                        // echo '</td></tr></table>';
                        // Log the result of applying all the rules in sequence in ascending order of severity in attendance_logs
                        if($ruleset == 1) {
                            $statlist = implode(',', array_keys((array)get_statuses($course->id) ));
                            $rec = new Object();
                            $rec->sessionid = $session->id;
                            $rec->studentid = $student->id;
                            $rec->statusid = $currentrule;
                            $rec->statusset = $statlist;
                            $rec->timetaken = $timenow;
                            $rec->takenby = $USER->id;
                            $rec->makeupnotes = $currentmakeupnote;
                            $rec->sicknote = $currentsicknote;
                            // insert_record('attendance_log', $rec);
                        }
                    } else {
                        echo '<p></p>';
                        echo ' No rules were found';
                        echo '<p><hr /></p>';
                    }
                } else {
                    // do something with sessions which have no scans for this student
                    $timesum = 0;  // (the student was absent)
                }
            }
        }
        // Set the session's 'processed' field to 1 (yes)
        // set_field('attendance_sessions', 'processed', 1, 'id', $session->id);
        set_field('attendance_sessions', 'lasttaken', $timenow, 'id', $session->id);
        set_field('attendance_sessions', 'lasttakenby', $USER->id, 'id', $session->id);
        echo '<p><hr /><hr /</p>';
    }
} else {
    echo 'No sessions to be processed for this course';
}
?>
<?php

    /**
    * This page prints the teacher view of a student state.
    *
    * @package mod-scheduler
    * @category mod
    * @author Gustav Delius, Valery Fremaux > 1.8
    */

    /**
    * Security traps
    */
    if (!defined('MOODLE_INTERNAL')){
        error("This file cannot be loaded directly");
    }
    
    $studentid = required_param('studentid', PARAM_INT);
    $usehtmleditor = can_use_html_editor();
    
    if ($subaction != ''){
        include "viewstudent.controller.php"; 
    }
    
    print_user(get_record('user', 'id', $studentid), $course);
    
    //print tabs
    $tabrows = array();
    $row  = array();
    if($page == 'appointments'){
        $currenttab = get_string('appointments', 'scheduler');
    } else {
        $currenttab = get_string('notes', 'scheduler');
    }
    $tabname = get_string('appointments', 'scheduler');
    $order = @$order;
    $row[] = new tabobject($tabname, "view.php?what=viewstudent&amp;id={$cm->id}&amp;studentid={$studentid}&amp;course={$scheduler->course}&amp;order={$order}&amp;page=appointments", $tabname);
    $tabname = get_string('comments', 'scheduler');
    $row[] = new tabobject($tabname, "view.php?what=viewstudent&amp;id={$cm->id}&amp;studentid={$studentid}&amp;course={$scheduler->course}&amp;order={$order}&amp;page=notes", $tabname);
    $tabrows[] = $row;
    print_tabs($tabrows, $currenttab);
    
    /// if slots have been booked
    $sql = "
        SELECT 
            s.*,
            a.id as appid,
            a.studentid,
            a.attended,
            a.appointmentnote,
            a.grade,
            a.timemodified as apptimemodified
        FROM 
            {$CFG->prefix}scheduler_slots AS s,
            {$CFG->prefix}scheduler_appointment AS a 
        WHERE 
            s.id = a.slotid AND        
            schedulerid = '{$scheduler->id}' AND 
            studentid = '{$studentid}' 
        ORDER BY 
            starttime {$order}
    ";
    if ($slots = get_records_sql($sql)) {
        /// provide link to sort in the opposite direction
        if($order == 'DESC'){
            $orderlink = "<a href=\"view.php?what=viewstudent&amp;id=$cm->id&amp;studentid=".$studentid."&amp;course=$scheduler->course&amp;order=ASC&amp;page=$page\">";
        } else {
            $orderlink = "<a href=\"view.php?what=viewstudent&amp;id=$cm->id&amp;studentid=".$studentid."&amp;course=$scheduler->course&amp;order=DESC&amp;page=$page\">";
        }
    
        /// print page header and prepare table headers
        if ($page == 'appointments'){
            print_heading(get_string('slots' ,'scheduler'));
            $table->head  = array ($strdate, $strstart, $strend, $strseen, $strnote, $strgrade, format_string($scheduler->staffrolename));
            $table->align = array ('LEFT', 'LEFT', 'CENTER', 'CENTER', 'LEFT', 'CENTER', 'CENTER');
            $table->width = '80%';
        } else {
            print_heading(get_string('comments' ,'scheduler'));
            $table->head  = array (get_string('studentcomments', 'scheduler'), get_string('comments', 'scheduler'), $straction);
            $table->align = array ('LEFT', 'LEFT');
            $table->width = '80%';
        }
        foreach($slots as $slot) {
            $startdate = scheduler_userdate($slot->starttime,1);
            $starttime = scheduler_usertime($slot->starttime,1);
            $endtime = scheduler_usertime($slot->starttime + ($slot->duration * 60),1);
            $distributecheck = '';
            if ($page == 'appointments'){
                if (count_records('scheduler_appointment', 'slotid', $slot->id) > 1){
                    $distributecheck = "<br/><input type=\"checkbox\" name=\"distribute{$slot->appid}\" value=\"1\" /> ".get_string('distributetoslot', 'scheduler')."\n";
                }
                //display appointments
                if ($slot->attended == 0){
                    $table->data[] = array ($startdate, $starttime, $endtime, "<img src=\"pix/unticked.gif\" border=\"0\" />", $slot->appointmentnote, $slot->grade, fullname(get_record('user', 'id', $slot->teacherid)));
                } 
                else {
                    $slot->appointmentnote .= "<br/><span class=\"timelabel\">[".userdate($slot->apptimemodified)."]</span>";
                    if (($slot->teacherid == $USER->id) || $CFG->scheduler_allteachersgrading){
                        $grade = scheduler_make_grading_menu($scheduler, 'gr'.$slot->appid, $slot->grade, true);
                    }
                    else{
                        $grade = $slot->grade;
                    }

                    $table->data[] = array ($startdate, $starttime, $endtime, "<img src=\"pix/ticked.gif\" border=\"0\" />", $slot->appointmentnote, $grade.$distributecheck, fullname(get_record('user', 'id', $slot->teacherid)));
                }
            } else {
                if (count_records('scheduler_appointment', 'slotid', $slot->id) > 1){
                    $distributecheck = "<input type=\"checkbox\" name=\"distribute\" value=\"1\" /> ".get_string('distributetoslot', 'scheduler')."\n";
                }
                //display notes
                $onsubmitcall = ($usehtmleditor) ? "javascript:document.forms['updatenote{$slot->id}'].onsubmit();" : '' ;
                $actions = "<a href=\"{$onsubmitcall}document.forms['updatenote{$slot->id}'].submit()\">".get_string('savecomment', 'scheduler').'</a>';
                $commenteditor = "<form name=\"updatenote{$slot->id}\" action=\"view.php\" method=\"post\">\n";
                $commenteditor .= "<input type=\"hidden\" name=\"what\" value=\"viewstudent\" />\n";
                $commenteditor .= "<input type=\"hidden\" name=\"subaction\" value=\"updatenote\" />\n";
                $commenteditor .= "<input type=\"hidden\" name=\"page\" value=\"appointments\" />\n";
                $commenteditor .= "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />\n";
                $commenteditor .= "<input type=\"hidden\" name=\"studentid\" value=\"{$studentid}\" />\n";
                $commenteditor .= "<input type=\"hidden\" name=\"appid\" value=\"{$slot->appid}\" />\n";
                $commenteditor .= print_textarea($usehtmleditor, 20, 60, 400, 200, 'appointmentnote', $slot->appointmentnote, $COURSE->id, true);
                if ($usehtmleditor) {
                    $commenteditor .= "<input type=\"hidden\" name=\"format\" value=\"FORMAT_HTML\" />\n";
                } 
                else {
                    $commenteditor .= '<p align="right">';
                    $commenteditor .= helpbutton('textformat', get_string('formattexttype'), 'moodle', true, false, '', true);
                    $commenteditor .= get_string('formattexttype');
                    $commenteditor .= ':&nbsp;';
                    if (!$form->format) {
                        $form->format = 'MOODLE';
                    }
                    $commenteditor .= choose_from_menu(format_text_menu(), 'format', $form->format, '', 'choose', '', 0, true); 
                    $commenteditor .= '</p>';
                }
                $commenteditor .= $distributecheck;
                $commenteditor .= "</form>";
                $table->data[] = array ($slot->notes.'<br/><font size=-2>'.$startdate.' '.$starttime.' to '.$endtime.'</font>', $commenteditor, $actions);
            }
        }
        // print slots table
        if ($page == 'appointments'){
            echo '<form name="studentform" action="view.php" method="post">';
            echo "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />\n";
            echo "<input type=\"hidden\" name=\"subaction\" value=\"updategrades\" />\n";
            echo "<input type=\"hidden\" name=\"what\" value=\"viewstudent\" />\n";
            echo "<input type=\"hidden\" name=\"page\" value=\"appointments\" />\n";
            echo "<input type=\"hidden\" name=\"studentid\" value=\"{$studentid}\" />\n";
        }
        print_table($table);
        if ($page == 'appointments'){
            echo "<p><center><input type=\"submit\" name=\"go_btn\" value=\"".get_string('updategrades', 'scheduler')."\" />";
            echo '</form>';
        }
    }
    echo "<br/>";
    print_continue("{$CFG->wwwroot}/mod/scheduler/view.php?id=".$cm->id);
    
    return;
    /// Finish the page
    print_footer($course);
    exit;
?>
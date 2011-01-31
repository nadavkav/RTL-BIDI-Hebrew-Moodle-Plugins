<?php

/**
* direct log construction implementation
*
*/

    include_once $CFG->dirroot.'/blocks/use_stats/locallib.php';
    include_once $CFG->dirroot.'/course/report/trainingsessions/locallib.php';

// require login and make page start

    $id = required_param('id', PARAM_INT) ; // the course id
    $startday = optional_param('startday', -1, PARAM_INT) ; // from (-1 is from course start)
    $startmonth = optional_param('startmonth', -1, PARAM_INT) ; // from (-1 is from course start)
    $startyear = optional_param('startyear', -1, PARAM_INT) ; // from (-1 is from course start)
    $fromstart = optional_param('fromstart', 0, PARAM_INT) ; // force reset to course startdate
    $from = optional_param('from', -1, PARAM_INT) ; // alternate way of saying from when for XML generation
    $groupid = optional_param('groupid', false, PARAM_INT) ; // admits special values : -1 current group, -2 course users
    $output = optional_param('output', 'html', PARAM_ALPHA) ; // 'html' or 'xls'

// calculate start time

    if ($from == -1){ // maybe we get it from parameters
        if ($startday == -1 || $fromstart){
            $from = $course->startdate;
        } else {
            if ($startmonth != -1 && $startyear != -1)
                $from = mktime(0, 0, 8, $startmonth, $startday, $startyear);
            else 
                print_error('Bad start date');
        }
    }
    
// Pre print the group selector
    if ($output == 'html'){
        // time and group period form
        include "course_selector_form.html";
    }

// compute target group

    if ($groupid){
        $targetusers = groups_get_members($groupid);
    } else {        
        $targetusers = get_users_by_capability($context, 'moodle/course:view', 'u.id, firstname, lastname, email, institution', 'lastname');
    }

// get course structure

    $coursestructure = reports_get_course_structure($course->id, $items);

// print result

    if ($output == 'html'){

        echo "<link rel=\"stylesheet\" href=\"reports.css\" type=\"text/css\" />";

        if (!empty($targetusers)){
            foreach($targetusers as $auser){
        
                $logusers = $auser->id;
                $logs = use_stats_extract_logs($from, time(), $auser->id, $course->id);
                $aggregate = use_stats_aggregate_logs($logs, 'module');
    
                $data->items = $items;
                $data->done = 0;
                if (!empty($aggregate)){
                    foreach(array_keys($aggregate) as $module){
                        $data->done += count($aggregate[$module]);
                    }
                }
                if ($data->done > $items) $data->done = $items;
    
                $data->linktousersheet = 1;
                training_reports_print_header_html($auser->id, $course->id, $data, true);
    
            }
        }

        $options['id'] = $course->id;
        $options['groupid'] = $groupid;
        $options['from'] = $from; // alternate way
        $options['output'] = 'xls'; // ask for XLS
        $options['asxls'] = 'xls'; // force XLS for index.php
        $options['view'] = 'course'; // force course view
        echo '<center>';
        print_single_button($CFG->wwwroot.'/course/report/trainingsessions/index.php', $options, get_string('generateXLS', 'report_trainingsessions'), 'get');
        echo '</center>';
        
    } else {

        /// generate XLS

        if ($groupid){    
            $filename = 'training_group_'.$groupid.'_report_'.date('d-M-Y', time()).'.xls';
        } else {
            $filename = 'training_course_'.$id.'_report_'.date('d-M-Y', time()).'.xls';
        }
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers
        $workbook->send($filename);

        $xls_formats = training_reports_xls_formats($workbook);
        $startrow = 15;
    
        foreach($targetusers as $auser){

            $row = $startrow;
            $worksheet = training_reports_init_worksheet($auser->id, $row, $xls_formats, $workbook);
    
            $logusers = $auser->id;
            $logs = use_stats_extract_logs($from, time(), $auser->id, $course->id);
            $aggregate = use_stats_aggregate_logs($logs, 'module');
            
            $overall = training_reports_print_xls($worksheet, $coursestructure, $aggregate, $done, $row, $xls_formats);
            $data->items = $items;
            $data->done = $done;
            $data->from = $from;
            $data->elapsed = $overall->elapsed;
            $data->events = $overall->events;
            training_reports_print_header_xls($worksheet, $auser->id, $course->id, $data, $xls_formats);    
        }

        $workbook->close();
    }

?>
<?php

    require_once('../../../config.php');

    $searchfor = optional_param('searchfor', '', PARAM_RAW);
    $courseid  = optional_param('courseid', 0, PARAM_INT);


    echo '<tr id="section-search" class="section main">';
    echo '<td class="left side">&nbsp;</td>';
    echo '<td class="content">';

    $sql = "SELECT cm.id,cm.course,cm.instance, m.name
                FROM {$CFG->prefix}course_modules cm
                JOIN {$CFG->prefix}modules as m ON m.id = cm.module
                WHERE cm.course = {$courseid}";

    mb_internal_encoding( 'utf-8' );

    if ($coursemods = get_records_sql($sql)) {
        foreach($coursemods as $cmod){
            $mod = get_record($cmod->name,'id',$cmod->instance);
    //        if (mb_strpos($tempmod->name,$searchfor,0,'UTF-8') !== false) {
    //            //echo mb_strpos($tempmod->name,$searchfor,0,'UTF-8');
    //            echo $tempmod->name.'<br/>';
    //        }
            $haystack = isset($mod->name) ? $mod->name.' ' : '';
            $haystack .= isset($mod->intro) ? $mod->intro.' ' : '';
            $haystack .= isset($mod->summary) ? $mod->summary.' ' : '';
            $haystack .= isset($mod->alttext) ? $mod->alttext.' ' : '';
            $haystack .= isset($mod->description) ? $mod->description.' ' : '';
            $haystack .= isset($mod->content) ? $mod->content.' ' : '';
            $haystack .= isset($mod->tabcontent1) ? $mod->tabcontent1.' ' : '';
            //$haystack .= isset($mod->tabcontent2) ? $mod->tabcontent2.' ' : ''; // Tab mod has 8 text fields (sub pages)
            if (mb_eregi($searchfor,mb_strtolower($haystack))){
                // Display activity/resource link
                $icon = "$CFG->modpixpath/$cmod->name/icon.gif";
                //$linkcss = $mod->visible ? "" : " class=\"dimmed\" ";
                echo '<a '.$linkcss.' '.$extra.
                        ' href="'.$CFG->wwwroot.'/mod/'.$cmod->name.'/view.php?id='.$cmod->id.'">'.
                        '<img src="'.$icon.'" class="activityicon" alt="" /> <span>'.$mod->name.'</span></a>';
                if (!empty($CFG->enablegroupings) && !empty($mod->groupingid) && has_capability('moodle/course:managegroups', get_context_instance(CONTEXT_COURSE, $courseid))) {
                    if (!isset($groupings)) {
                        $groupings = groups_get_all_groupings($course->id);
                    }
                    echo " <span class=\"groupinglabel\">(".format_string($groupings[$mod->groupingid]->name).')</span>';
                }
                echo '<br/>';
                //echo $haystack.'<br/>'; // Debug: what is searched
            }
        }
    }

    echo '</td>';
    echo '<td class="right side">&nbsp;</td>';
    echo '</tr>';
    echo '<tr class="section separator"><td colspan="3" class="spacer"></td></tr>';

?>
<?php

$worker->beginPreferences();

project_backup_get_preferences($section_i, $preferences, $count, $worker->getCourse());
if ($count == 0) {
    notice("No backupable modules are installed!");
}

$worker->endPreferences();


if (empty($to_course_id)) {
    //Start the main table
    echo '<table cellpadding="5">';
    
    //Now print the Backup Name tr
    echo '<tr>';
    echo '<td align="right"><b>'.get_string('name').':</b></td>';
    echo '<td>'.$preferences->backup_name.'</td>';
    echo '</tr>';
    
    //Start the main tr, where all the backup progress is done
    echo '<tr>';
    echo '<td colspan="2">';
    
    //Start the main ul
    echo '<ul>';
    
    $worker->execute();

    //Ends th main ul
    echo '</ul>';

    //End the main tr, where all the backup is done
    echo '</td>';
    echo '</tr>';

    //End the main table
    echo '</table>';

} else {
    $worker->setSilent();

    $worker->execute();
}

//Print final message
if (empty($to_course_id)) {
    print_simple_box(get_string('backupfinished'), 'center');
    print_continue($CFG->wwwroot.'/files/index.php?id='.$preferences->backup_course.'&amp;wdir=/backupdata');
} else {    
    print_simple_box(get_string('importdataexported'),'center');
    if (!empty($preferences->backup_destination)) {
        $filename = $preferences->backup_destination.'/'.$preferences->backup_name;
    } else {
        $filename = $preferences->backup_course."/backupdata/".$preferences->backup_name;
    }
    //error_log($filename);
    $SESSION->import_preferences = $preferences;
    print_continue($CFG->wwwroot.'/course/format/project/import.php?id='.$to_course_id.'&section='.$to_section_i.
                   '&fromcourse='.$course_id.'&fromsection='.$section_i.'&filename='.$filename.'&newdirectoryname='.$newdirectoryname);
}

$SESSION->backupprefs[$course->id] = null; // unset it so we're clear next time.

?>

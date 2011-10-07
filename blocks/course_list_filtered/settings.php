<?php  //$Id: settings.php,v 1.1.2.2 2007/12/19 17:38:47 skodak Exp $


$options = array('all'=>get_string('allcourses', 'block_course_list_filtered'), 'own'=>get_string('owncourses', 'block_course_list_filtered'));

$settings->add(new admin_setting_configselect('block_course_list_filtered_adminview', get_string('adminview', 'block_course_list_filtered'),
                   get_string('configadminview', 'block_course_list_filtered'), 'all', $options));

$settings->add(new admin_setting_configcheckbox('block_course_list_filtered_hideallcourseslink', get_string('hideallcourseslink', 'block_course_list_filtered'),
                   get_string('confighideallcourseslink', 'block_course_list_filtered'), 0));


?>

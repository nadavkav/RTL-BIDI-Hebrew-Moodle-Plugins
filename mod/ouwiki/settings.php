<?php

$module = new stdClass;
require($CFG->dirroot . '/mod/ouwiki/version.php');
$settings->add(new admin_setting_heading('ouwiki_version', '',
    get_string('displayversion', 'ouwiki', $module->displayversion)));

    // In Moodle 2.0, or OU Moodle 1.9, we use a new admin setting type to let you 
// select roles with checkboxes. Otherwise you have to type in role IDs. Reason
// for using IDs is that this makes it compatible with the new system.
if(class_exists('admin_setting_pickroles')) {
    $settings->add(new admin_setting_pickroles('ouwiki_reportroles',
        get_string('reportroles','ouwiki'),
        get_string('configreportroles','ouwiki')));
} else {
    $settings->add(new admin_setting_configtext('ouwiki_reportroles', 
        get_string('reportroles', 'ouwiki'),
        get_string('configreportroles_text', 'ouwiki'), '', PARAM_SEQUENCE));
}

// ouwiki comment system selection
$options = array(0 => get_string('nocommentsystem', 'ouwiki'),
                1 => get_string('annotationsystem', 'ouwiki'),
                2 => get_string('persectionsystem', 'ouwiki'),
                3 => get_string('bothcommentsystems', 'ouwiki'));
$settings->add(new admin_setting_configselect('ouwiki_comment_system',
    get_string('commenting', 'ouwiki'), get_string('commentsystemdesc', 'ouwiki'), 2, $options));

$settings->add(new admin_setting_configtext('ouwiki_computing_guide', get_string('computingguideurl', 'ouwiki'),
    get_string('computingguideurlexplained', 'ouwiki'), '', PARAM_NOTAGS));
?>

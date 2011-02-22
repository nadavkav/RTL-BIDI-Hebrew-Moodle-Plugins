<?php

$ADMIN->add('reports', new admin_externalpage('reportcustomsql',
        get_string('customsql', 'report_customsql'),
        $CFG->wwwroot . '/' . $CFG->admin . '/report/customsql/index.php',
        'moodle/site:config')); // remove report link from admin menu for non admin users (nadavkav)
$temp = new admin_settingpage('customsqlsettings', get_string('customsql', 'report_customsql'));
$temp->add( new admin_setting_configselect('hebrewexcelexport', get_string('latinexcelexport', 'admin'), get_string('configlatinexcelexport', 'admin'), '0', array('0'=>'UTF-8','1'=>'Latin','2'=>'Windows-1255','3'=>'ISO-8859-8')));
$ADMIN->add('language', $temp);

?>


<?php
/* 
 * updates laegrader report to current syntax )lower-case_
 * in case its previously been installed using old syntax (LAE)
 */

// trick the dang thing into thinking this isn't the first install, which it isn't except the plugin is renamed (only changing case)
// something you never want to have to do
$sql = 'update ' . $CFG->prefix . "config set name = replace(name, 'LAEgrader','laegrader')";
$result = mysql_query($sql);
$sql = 'update ' . $CFG->prefix . "capabilities set name = replace(name, 'LAEgrader','laegrader'), component = replace(component, 'LAEgrader', 'laegrader')";
$result = mysql_query($sql);
$sql = 'update ' . $CFG->prefix . "role_capabilities set capability = replace(capability, 'LAEgrader','laegrader')";
$result = mysql_query($sql);
$sql = 'DELETE FROM ' . $CFG->prefix . 'user_preferences WHERE name = "grade_report_studentsperpage"';
$result = mysql_query($sql);

function gradereport_laegrader_upgrade ($oldversion=0) {
    return true;
}
?>

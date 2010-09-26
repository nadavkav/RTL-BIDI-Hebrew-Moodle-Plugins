<?php
require_once('../../config.php');
$q=$_GET["q"];
$logid = ereg_replace("[^0-9]", "", $q);
$newvalue = strchr($q,"=");
$recordvalue = substr($newvalue,1);
$recordtype = substr($q,0,8);
//  echo 'Log id selected: '.$logid;
//  echo ', Value selected: '.$recordvalue;
//  echo ', Record type: '.$recordtype;

if ($recordtype == 'makenote') {
    $success = set_field_select('attendance_log', 'makeupnotes', $recordvalue, 'id='.$logid);
    if($success) {
        echo ' *** Successfully updated makeupnote ***';
    } else {
        echo 'Error updating makeupnote';
    }
}
if ($recordtype == 'sicknote') {
    $success = set_field_select('attendance_log', 'sicknote', $recordvalue, 'id='.$logid);
    if($success) {
        echo ' *** Successfully updated sicknote ***';
    } else {
        echo 'Error updating sicknote';
    }
}

if ($recordtype == 'myremark') {
    $success = set_field_select('attendance_log', 'remarks', $recordvalue, 'id='.$logid);
    if($success) {
        echo ' *** Successfully updated remark ***';
    } else {
        echo 'Error updating remark';
    }
}

?>  